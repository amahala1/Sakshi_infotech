<?php
/**
 * Sakshi Infotech - Staff Management & Role Permissions
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/HashEngine.php';
require_once __DIR__ . '/Auth.php';

class Staff {

    public static function getRoles(): array {
        return [
            'staff_accounts' => [
                'name' => 'Accounts & Payment Verification',
                'description' => 'Verifies UPI receipts, bank transfers, issues payment approval seal.',
                'badge' => 'badge-accounts',
                'icon' => 'fa-file-invoice-dollar'
            ],
            'staff_checker' => [
                'name' => 'Order Checker & Packing',
                'description' => 'Inspects ordered hardware and stationery items, approves orders for packing.',
                'badge' => 'badge-checker',
                'icon' => 'fa-clipboard-check'
            ],
            'staff_dispatch' => [
                'name' => 'Dispatch & Courier Logistics',
                'description' => 'Assigns courier partners, updates tracking numbers, marks parcels dispatched.',
                'badge' => 'badge-dispatch',
                'icon' => 'fa-truck-fast'
            ]
        ];
    }

    public static function getRoleName(string $role): string {
        $roles = self::getRoles();
        return $roles[$role]['name'] ?? ucfirst($role);
    }

    public static function getAllStaff(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT id, username, full_name, email, phone, role, status, created_at
            FROM users
            WHERE role IN ('staff_accounts', 'staff_checker', 'staff_dispatch')
            ORDER BY id DESC
        ");
        return $stmt->fetchAll();
    }

    public static function createStaff(array $data): array {
        $db = Database::getConnection();

        if (empty($data['full_name']) || empty($data['username']) || empty($data['email']) || empty($data['password']) || empty($data['role'])) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        if (!array_key_exists($data['role'], self::getRoles())) {
            return ['success' => false, 'message' => 'Invalid staff role specified.'];
        }

        // Check duplicates
        $stmtCheck = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmtCheck->execute([trim($data['username']), trim($data['email'])]);
        if ($stmtCheck->fetch()) {
            return ['success' => false, 'message' => 'Username or email is already taken.'];
        }

        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
        $authTokenHash = HashEngine::generateIntegrityHash($data['username'] . '|' . time());

        $stmt = $db->prepare("
            INSERT INTO users (username, password_hash, full_name, email, phone, role, status, auth_token_hash)
            VALUES (?, ?, ?, ?, ?, ?, 'active', ?)
        ");
        $stmt->execute([
            trim($data['username']),
            $passwordHash,
            trim($data['full_name']),
            trim($data['email']),
            trim($data['phone'] ?? ''),
            $data['role'],
            $authTokenHash
        ]);

        $newId = (int)$db->lastInsertId();
        $admin = Auth::user();
        Auth::logAudit('staff', $newId, 'STAFF_CREATED', $admin['id'] ?? null, $admin['full_name'] ?? 'Admin', 'admin', "Created staff member {$data['full_name']} with role {$data['role']}.");

        return ['success' => true, 'staff_id' => $newId, 'message' => "Staff member {$data['full_name']} successfully created!"];
    }

    public static function deleteStaff(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role IN ('staff_accounts', 'staff_checker', 'staff_dispatch')");
        return $stmt->execute([$id]);
    }

    public static function toggleStatus(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE users 
            SET status = CASE WHEN status = 'active' THEN 'suspended' ELSE 'active' END
            WHERE id = ? AND role IN ('staff_accounts', 'staff_checker', 'staff_dispatch')
        ");
        return $stmt->execute([$id]);
    }
}
