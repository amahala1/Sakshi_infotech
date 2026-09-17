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
     * Register customer account
     */
    public static function register(array $data): array {
        $db = Database::getConnection();

        // Validations
        if (empty($data['full_name']) || empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'Please fill all required fields.'];
        }

        if (strlen($data['password']) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
        }

        // Check if username or email exists
        $stmtCheck = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmtCheck->execute([trim($data['username']), trim($data['email'])]);
        if ($stmtCheck->fetch()) {
            return ['success' => false, 'message' => 'Username or Email is already registered.'];
        }

        // Hash password securely
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $authTokenHash = HashEngine::generateIntegrityHash($data['username'] . '|' . time());

        $stmtInsert = $db->prepare("
            INSERT INTO users (username, password_hash, full_name, email, phone, address, city, state, pincode, role, status, auth_token_hash)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'customer', 'active', ?)
        ");

        $stmtInsert->execute([
            trim($data['username']),
            $hashedPassword,
            trim($data['full_name']),
            trim($data['email']),
            trim($data['phone'] ?? ''),
            trim($data['address'] ?? ''),
            trim($data['city'] ?? ''),
            trim($data['state'] ?? 'Rajasthan'),
            trim($data['pincode'] ?? ''),
            $authTokenHash
        ]);

        $newUserId = (int)$db->lastInsertId();

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
