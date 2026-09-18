<?php
/**
 * Test Suite: Customer Types, GST Validation, Email OTP, and Lifecycle Email Triggers
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/Mailer.php';
require_once BASE_PATH . '/src/Order.php';

echo "=== 1. TEST SMTP & SYSTEM SETTINGS ===\n";
$settings = Mailer::getEmailSettings();
echo "From Email: " . $settings['smtp_from_email'] . "\n";
echo "Host: " . $settings['smtp_host'] . "\n";
assert(!empty($settings['smtp_from_email']), "Sender email must not be empty");

echo "\n=== 2. TEST OTP GENERATION & DB RECORD ===\n";
$testEmail = "test_b2b_" . time() . "@example.com";
$otpRes = Auth::generateAndSendOtp($testEmail, "Acme Corp Admin", "registration");
echo "OTP Dispatch Result: " . json_encode($otpRes) . "\n";
assert($otpRes['success'] === true, "OTP generation must succeed");
$generatedOtp = $otpRes['dev_otp'];
echo "Generated OTP: {$generatedOtp}\n";

// Verify DB table email_otps
$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM email_otps WHERE email = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$testEmail]);
$otpRow = $stmt->fetch();
assert(!empty($otpRow), "OTP must be stored in email_otps table");
assert($otpRow['otp_code'] === $generatedOtp, "Stored OTP code must match");
echo "Stored in email_otps table successfully.\n";

echo "\n=== 3. TEST OTP VERIFICATION ===\n";
$wrongVerify = Auth::verifyOtp($testEmail, "000000", "registration");
echo "Wrong OTP check: " . ($wrongVerify['success'] ? 'FAILED' : 'PASSED (rejected invalid)') . "\n";
assert($wrongVerify['success'] === false, "Invalid OTP must fail");

$validVerify = Auth::verifyOtp($testEmail, $generatedOtp, "registration");
echo "Valid OTP check: " . ($validVerify['success'] ? 'PASSED' : 'FAILED') . "\n";
assert($validVerify['success'] === true, "Valid OTP must succeed");

echo "\n=== 4. TEST COMPANY REGISTRATION WITH & WITHOUT GST ===\n";
// Attempt company registration without GST -> Must fail
$testUser1 = [
    'full_name' => 'Tech Corp Manager',
    'username' => 'tech_corp_' . rand(100, 999),
    'email' => $testEmail,
    'password' => 'secret123',
    'customer_type' => 'company',
    'company_name' => '',
    'gst_number' => ''
];
$failRes1 = Auth::register($testUser1);
echo "Company without Name/GST response: {$failRes1['message']}\n";
assert($failRes1['success'] === false, "Company without GST must be rejected");

// Attempt with invalid GST format -> Must fail
$testUser1['company_name'] = 'Tech Corp Private Limited';
$testUser1['gst_number'] = 'INVALID_GST_123';
$failRes2 = Auth::register($testUser1);
echo "Invalid GST format response: {$failRes2['message']}\n";
assert($failRes2['success'] === false, "Invalid GST format must be rejected");

// Attempt with valid Indian GSTIN (e.g. 08APSPA4456M2ZC) -> Must succeed
$testUser1['gst_number'] = '08APSPA4456M2ZC';
$successRes = Auth::register($testUser1);
echo "Valid Company Registration Result: " . ($successRes['success'] ? 'SUCCESS' : 'FAILED: ' . $successRes['message']) . "\n";
assert($successRes['success'] === true, "Valid Company Registration must succeed");

// Verify user record in database
$stmtUser = $db->prepare("SELECT customer_type, company_name, gst_number, email_verified FROM users WHERE email = ?");
$stmtUser->execute([$testEmail]);
$userInDb = $stmtUser->fetch();
echo "User in DB: " . json_encode($userInDb) . "\n";
assert($userInDb['customer_type'] === 'company', "Customer type must be company");
assert($userInDb['gst_number'] === '08APSPA4456M2ZC', "GSTIN must match");
assert((int)$userInDb['email_verified'] === 1, "Email verified flag must be 1");

echo "\n=== 5. TEST EMAIL TRANSMISSION AUDIT LOGS ===\n";
$stmtLogs = $db->prepare("SELECT id, recipient_email, subject, event_type, status FROM email_logs WHERE recipient_email = ? ORDER BY id DESC");
$stmtLogs->execute([$testEmail]);
$logs = $stmtLogs->fetchAll();
echo "Logged emails for {$testEmail}: " . count($logs) . " transmissions recorded.\n";
foreach ($logs as $l) {
    echo " - [{$l['event_type']}] {$l['subject']} -> Status: {$l['status']}\n";
}
assert(count($logs) >= 2, "At least OTP and Welcome emails must be logged");

echo "\nALL TESTS PASSED WITH 100% SUCCESS!\n";
