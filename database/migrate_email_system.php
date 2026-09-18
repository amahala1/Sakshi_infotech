<?php
/**
 * Migration: Add customer type & GST fields to users, and create system_settings, email_otps, and email_logs tables.
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/config/database.php';

$db = Database::getConnection();
$driver = Database::getDriver();

echo "Running email & customer type migration on driver: {$driver}...\n";

// 1. Alter users table to add new columns if not present
if ($driver === 'sqlite') {
    $cols = [];
    $stmt = $db->query("PRAGMA table_info(users)");
    while ($row = $stmt->fetch()) {
        $cols[] = $row['name'];
    }

    if (!in_array('customer_type', $cols)) {
        $db->exec("ALTER TABLE users ADD COLUMN customer_type TEXT DEFAULT 'individual'");
        echo "Added customer_type to users.\n";
    }
    if (!in_array('company_name', $cols)) {
        $db->exec("ALTER TABLE users ADD COLUMN company_name TEXT DEFAULT NULL");
        echo "Added company_name to users.\n";
    }
    if (!in_array('gst_number', $cols)) {
        $db->exec("ALTER TABLE users ADD COLUMN gst_number TEXT DEFAULT NULL");
        echo "Added gst_number to users.\n";
    }
    if (!in_array('email_verified', $cols)) {
        $db->exec("ALTER TABLE users ADD COLUMN email_verified INTEGER DEFAULT 0");
        echo "Added email_verified to users.\n";
    }

    // 2. Create system_settings table
    $db->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
            setting_key TEXT PRIMARY KEY,
            setting_value TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "system_settings table ready.\n";

    // 3. Create email_otps table
    $db->exec("
        CREATE TABLE IF NOT EXISTS email_otps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL,
            otp_code TEXT NOT NULL,
            action_type TEXT DEFAULT 'registration',
            expires_at DATETIME NOT NULL,
            is_used INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "email_otps table ready.\n";

    // 4. Create email_logs table
    $db->exec("
        CREATE TABLE IF NOT EXISTS email_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            recipient_email TEXT NOT NULL,
            subject TEXT NOT NULL,
            event_type TEXT NOT NULL,
            status TEXT NOT NULL,
            error_message TEXT,
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "email_logs table ready.\n";

} else {
    // MySQL
    try {
        $db->exec("ALTER TABLE users ADD COLUMN customer_type VARCHAR(20) DEFAULT 'individual'");
    } catch (Exception $e) {}
    try {
        $db->exec("ALTER TABLE users ADD COLUMN company_name VARCHAR(150) NULL");
    } catch (Exception $e) {}
    try {
        $db->exec("ALTER TABLE users ADD COLUMN gst_number VARCHAR(20) NULL");
    } catch (Exception $e) {}
    try {
        $db->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0");
    } catch (Exception $e) {}

    $db->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
            setting_key VARCHAR(60) PRIMARY KEY,
            setting_value TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS email_otps (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(150) NOT NULL,
            otp_code VARCHAR(10) NOT NULL,
            action_type VARCHAR(50) DEFAULT 'registration',
            expires_at DATETIME NOT NULL,
            is_used TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email_otp (email, otp_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS email_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient_email VARCHAR(150) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            status VARCHAR(20) NOT NULL,
            error_message TEXT NULL,
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email_logs (recipient_email, event_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "MySQL tables ready.\n";
}

// Seed default email settings if not present
$defaults = [
    'smtp_from_email' => 'noreply@sitindia.in',
    'smtp_from_name'  => 'Sakshi Infotech',
    'smtp_host'       => 'mail.sitindia.in',
    'smtp_port'       => '587',
    'smtp_secure'     => 'tls',
    'smtp_user'       => 'noreply@sitindia.in',
    'smtp_pass'       => '',
    'mail_driver'     => 'smtp', // 'smtp' or 'mail'
    'otp_expiry_min'  => '15'
];

$stmtCheck = $db->prepare("SELECT setting_key FROM system_settings WHERE setting_key = ?");
$stmtInsert = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES (?, ?, ?)");

$now = date('Y-m-d H:i:s');
foreach ($defaults as $key => $val) {
    $stmtCheck->execute([$key]);
    if (!$stmtCheck->fetch()) {
        $stmtInsert->execute([$key, $val, $now]);
        echo "Seeded setting: {$key} = '{$val}'\n";
    }
}

echo "Migration completed successfully!\n";
