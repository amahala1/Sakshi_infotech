<?php
/**
 * Sakshi Infotech - Authentication & Access Control
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/HashEngine.php';

class Auth {

    public static function user(): ?array {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function role(): ?string {
        return $_SESSION['user']['role'] ?? null;
    }

    public static function isLoggedIn(): bool {
        return !empty($_SESSION['user']);
    }

    public static function isAdmin(): bool {
        return self::isLoggedIn() && self::role() === 'admin';
    }

    public static function isStaff(): bool {
        return self::isLoggedIn() && in_array(self::role(), ['staff_accounts', 'staff_checker', 'staff_dispatch', 'admin']);
    }

    public static function isAccountsStaff(): bool {
        return self::isLoggedIn() && in_array(self::role(), ['staff_accounts', 'admin']);
    }

    public static function isCheckerStaff(): bool {
        return self::isLoggedIn() && in_array(self::role(), ['staff_checker', 'admin']);
    }

    public static function isDispatchStaff(): bool {
        return self::isLoggedIn() && in_array(self::role(), ['staff_dispatch', 'admin']);
    }

    public static function isCustomer(): bool {
        return self::isLoggedIn() && self::role() === 'customer';
    }

    /**
     * Authenticate user with hashed password verification
     */
    public static function login(string $usernameOrEmail, string $password): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM users 
            WHERE (username = :val OR email = :val) AND status = 'active' 
            LIMIT 1
        ");
        $stmt->execute([':val' => trim($usernameOrEmail)]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid username/email or password.'];
        }

        // Generate session auth token hash
        $sessionToken = bin2hex(random_bytes(16));
        $authTokenHash = HashEngine::generateIntegrityHash($user['id'] . '|' . $sessionToken . '|' . time());

        // Update token hash in DB
        $stmtUp = $db->prepare("UPDATE users SET auth_token_hash = ? WHERE id = ?");
        $stmtUp->execute([$authTokenHash, $user['id']]);

        // Store user in session
        unset($user['password_hash']);
        $user['auth_token_hash'] = $authTokenHash;
        $_SESSION['user'] = $user;

        // Log audit
        self::logAudit('user', $user['id'], 'LOGIN', $user['id'], $user['full_name'], $user['role'], "User logged in successfully.");

        return ['success' => true, 'user' => $user];
    }

    /**
     * Generate and send 6-digit OTP to user email
     */
    public static function generateAndSendOtp(string $email, string $name = 'Customer', string $action = 'registration'): array {
        $email = trim(strtolower($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }

        require_once BASE_PATH . '/src/Mailer.php';

        $db = Database::getConnection();
        $otp = (string)random_int(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', time() + (15 * 60)); // 15 minutes

        // Invalidate prior unexpired OTPs for this email and action
        $stmtInvalidate = $db->prepare("UPDATE email_otps SET is_used = 1 WHERE email = ? AND action_type = ? AND is_used = 0");
        $stmtInvalidate->execute([$email, $action]);

        // Insert new OTP
        $stmtInsert = $db->prepare("
            INSERT INTO email_otps (email, otp_code, action_type, expires_at, is_used, created_at)
            VALUES (?, ?, ?, ?, 0, ?)
        ");
        $stmtInsert->execute([$email, $otp, $action, $expiresAt, date('Y-m-d H:i:s')]);

        // Dispatch via Mailer
        $mailResult = Mailer::sendOtp($email, $name, $otp);

        return [
            'success' => true,
            'message' => "A 6-digit OTP has been sent to {$email}.",
            'dev_otp' => $otp // Helpful for local testing if SMTP credentials are yet to be entered
        ];
    }

    /**
     * Verify submitted OTP for given email
     */
    public static function verifyOtp(string $email, string $otp, string $action = 'registration'): array {
        $email = trim(strtolower($email));
        $otp = trim($otp);

        if (empty($email) || empty($otp)) {
            return ['success' => false, 'message' => 'Email and OTP are required.'];
        }

        $db = Database::getConnection();
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare("
            SELECT id FROM email_otps 
            WHERE email = ? AND otp_code = ? AND action_type = ? AND is_used = 0 AND expires_at >= ?
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$email, $otp, $action, $now]);
        $record = $stmt->fetch();

        if (!$record) {
            return ['success' => false, 'message' => 'Invalid or expired OTP code. Please request a new one.'];
        }

        // Mark OTP as used
        $stmtUse = $db->prepare("UPDATE email_otps SET is_used = 1 WHERE id = ?");
        $stmtUse->execute([$record['id']]);

        // Mark verified in session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['email_verified_' . md5($email)] = true;

        return ['success' => true, 'message' => 'Email verified successfully!'];
    }

    /**
     * Register customer account with Customer Type (Individual/Company), GSTIN, and OTP check
     */
    public static function register(array $data): array {
        $db = Database::getConnection();
        require_once BASE_PATH . '/src/Mailer.php';

        // Validations
        if (empty($data['full_name']) || empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'Please fill all required fields.'];
        }

        if (strlen($data['password']) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
        }

        $customerType = ($data['customer_type'] ?? 'individual') === 'company' ? 'company' : 'individual';
        $companyName = trim($data['company_name'] ?? '');
        $gstNumber = strtoupper(trim($data['gst_number'] ?? ''));

        // If Company is selected, GST and Company Name are mandatory
        if ($customerType === 'company') {
            if (empty($companyName)) {
                return ['success' => false, 'message' => 'Company / Business Name is required for company accounts.'];
            }
            if (empty($gstNumber)) {
                return ['success' => false, 'message' => 'GST Number (GSTIN) is mandatory for company accounts.'];
            }
            // Standard Indian GSTIN Regex: 15 alphanumeric characters
            $gstRegex = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/';
            if (!preg_match($gstRegex, $gstNumber)) {
                return ['success' => false, 'message' => 'Invalid GSTIN format. Example: 08AAAAA0000A1Z5 (15 characters).'];
            }
        }

        // Check Email OTP verification
        $email = trim(strtolower($data['email']));
        $sessionKey = 'email_verified_' . md5($email);
        $isVerified = !empty($_SESSION[$sessionKey]);

        // If not in session, check if an OTP was verified within last 30 minutes in email_otps table
        if (!$isVerified && !empty($data['otp'])) {
            $verifyRes = self::verifyOtp($email, $data['otp'], 'registration');
            if (!$verifyRes['success']) {
                return ['success' => false, 'message' => 'Email verification required: ' . $verifyRes['message']];
            }
            $isVerified = true;
        }

        if (!$isVerified) {
            return ['success' => false, 'message' => 'Please verify your email address via OTP before registering.'];
        }

        // Check if username or email exists
        $stmtCheck = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmtCheck->execute([trim($data['username']), $email]);
        if ($stmtCheck->fetch()) {
            return ['success' => false, 'message' => 'Username or Email is already registered.'];
        }

        // Hash password securely
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $authTokenHash = HashEngine::generateIntegrityHash($data['username'] . '|' . time());

        $stmtInsert = $db->prepare("
            INSERT INTO users (
                username, password_hash, full_name, email, phone, 
                customer_type, company_name, gst_number, email_verified,
                address, city, state, pincode, role, status, auth_token_hash
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, 'customer', 'active', ?)
        ");

        $stmtInsert->execute([
            trim($data['username']),
            $hashedPassword,
            trim($data['full_name']),
            $email,
            trim($data['phone'] ?? ''),
            $customerType,
            $customerType === 'company' ? $companyName : null,
            $customerType === 'company' ? $gstNumber : null,
            trim($data['address'] ?? ''),
            trim($data['city'] ?? ''),
            trim($data['state'] ?? 'Rajasthan'),
            trim($data['pincode'] ?? ''),
            $authTokenHash
        ]);

        $newUserId = (int)$db->lastInsertId();

        // Clear session OTP flag
        unset($_SESSION[$sessionKey]);

        // Dispatch Welcome Email with User ID and Confirmation
        $newUserRecord = [
            'id' => $newUserId,
            'username' => trim($data['username']),
            'full_name' => trim($data['full_name']),
            'email' => $email,
            'customer_type' => $customerType,
            'company_name' => $companyName,
            'gst_number' => $gstNumber
        ];
        Mailer::sendUserWelcome($newUserRecord, $data['password']);

        // Auto login
        return self::login($data['username'], $data['password']);
    }

    public static function logout(): void {
        if (self::isLoggedIn()) {
            $user = self::user();
            self::logAudit('user', $user['id'], 'LOGOUT', $user['id'], $user['full_name'], $user['role'], "User logged out.");
        }
        unset($_SESSION['user']);
        session_destroy();
    }

    public static function requireLogin(string $redirect = ''): void {
        if (!self::isLoggedIn()) {
            $dest = $redirect ?: (function_exists('url') ? url('login.php') : '/login.php');
            header("Location: {$dest}?msg=" . urlencode("Please login to proceed."));
            exit;
        }
    }

    public static function requireRole(array $allowedRoles, string $redirect = ''): void {
        self::requireLogin();
        if (!in_array(self::role(), $allowedRoles) && !self::isAdmin()) {
            $dest = $redirect ?: (function_exists('url') ? url('sale.php') : '/sale.php');
            header("Location: {$dest}?error=" . urlencode("Access denied. Insufficient permissions for this section."));
            exit;
        }
    }

    public static function requireAdmin(): void {
        self::requireRole(['admin']);
    }

    public static function requireStaff(): void {
        self::requireRole(['staff_accounts', 'staff_checker', 'staff_dispatch', 'admin']);
    }

    public static function logAudit(string $entityType, int $entityId, string $action, ?int $performedById, ?string $name, ?string $role, string $details): void {
        try {
            $db = Database::getConnection();
            $timestamp = date('Y-m-d H:i:s');
            $rawSeal = "{$entityType}|{$entityId}|{$action}|{$performedById}|{$timestamp}";
            $recordHash = HashEngine::generateIntegrityHash($rawSeal);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            $stmt = $db->prepare("
                INSERT INTO audit_logs (entity_type, entity_id, action, performed_by_id, performed_by_name, role, record_hash, details, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$entityType, $entityId, $action, $performedById, $name, $role, $recordHash, $details, $ip, $timestamp]);
        } catch (Exception $e) {
            // Ignore audit log errors to prevent interrupting primary operations
        }
    }
}
