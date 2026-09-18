<?php
/**
 * AJAX: Dispatch Registration OTP
 */
header('Content-Type: application/json');

require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once BASE_PATH . '/src/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$name = trim($_POST['full_name'] ?? 'Valued Customer');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Please enter your email address.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Check if email already registered
$db = Database::getConnection();
$stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([strtolower($email)]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'This email address is already registered. Please sign in instead.']);
    exit;
}

$res = Auth::generateAndSendOtp($email, $name, 'registration');
echo json_encode($res);
exit;
