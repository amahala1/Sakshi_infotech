<?php
/**
 * Automated end-to-end verification script for Sakshi Infotech
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/src/Product.php';
require_once BASE_PATH . '/src/Order.php';
require_once BASE_PATH . '/src/Staff.php';
require_once BASE_PATH . '/src/HashEngine.php';

echo "=== SAKSHI INFOTECH END-TO-END SYSTEM TEST ===\n";

$db = Database::getConnection();
echo "[1] Database connection established. Active driver: " . Database::getDriver() . "\n";

// 1. Check products
$products = Product::getProducts();
echo "[2] Catalog products loaded: " . count($products) . " items.\n";
assert(count($products) >= 15, "Expected at least 15 seeded products");

// 2. Add to cart
$_SESSION['cart'] = [];
Order::addToCart(1, 1); // Lenovo Laptop
Order::addToCart(10, 2); // 2 Reams of JK Copier
$cart = Order::getCartDetails();
echo "[3] Cart items: {$cart['count']}, Total quantity: {$cart['total_quantity']}, Total Amount: ₹{$cart['total_amount']}\n";
assert($cart['count'] === 2, "Cart should have 2 distinct products");

// 3. Place Order
$shipping = [
    'name' => 'Dr. Rajesh Sharma',
    'email' => 'rajesh@example.com',
    'phone' => '9829099887',
    'address' => 'B-44, Surya Nagar, Tonk Road',
    'city' => 'Jaipur',
    'state' => 'Rajasthan',
    'pincode' => '302015',
    'gstin' => '08AAAFS9821M1Z2',
    'business_name' => 'Surya IT Solutions',
    'notes' => 'Please deliver by Friday'
];
$payment = [
    'method' => 'upi_qr',
    'ref_no' => '423981290312'
];

$orderRes = Order::createOrder($shipping, $payment);
echo "[4] Order created: {$orderRes['order_number']}, Order Hash: {$orderRes['order_hash']}\n";
assert($orderRes['success'] === true, "Order creation failed");
$orderId = $orderRes['order_id'];

// 4. Accounts Staff Verifies Payment
$accountsStaff = $db->query("SELECT id FROM users WHERE role = 'staff_accounts' LIMIT 1")->fetch();
$accRes = Order::verifyPayment($orderId, $accountsStaff['id'], 'verified');
echo "[5] Accounts verified payment: {$accRes['payment_status']}, Payment Hash: {$accRes['payment_hash']}\n";
assert(!empty($accRes['payment_hash']), "Payment hash must not be empty");

// 5. Order Checker Staff Checks & Packs Order
$checkerStaff = $db->query("SELECT id FROM users WHERE role = 'staff_checker' LIMIT 1")->fetch();
$chkRes = Order::checkOrder($orderId, $checkerStaff['id']);
echo "[6] Order Checker verified & packed: {$chkRes['order_status']}\n";
assert($chkRes['order_status'] === 'checked', "Order status should be 'checked'");

// 6. Dispatch Staff Dispatches Parcel
$dispatchStaff = $db->query("SELECT id FROM users WHERE role = 'staff_dispatch' LIMIT 1")->fetch();
$dspRes = Order::dispatchOrder($orderId, $dispatchStaff['id'], 'BlueDart Express', 'BLUEDART-AWB-987219');
echo "[7] Dispatch staff dispatched parcel: {$dspRes['order_status']}, Dispatch Hash: {$dspRes['dispatch_hash']}\n";
assert(!empty($dspRes['dispatch_hash']), "Dispatch hash must not be empty");

// 7. Test Hash Engine Lookup on all 3 hashes
$lookupOrder = HashEngine::lookupHash($db, $orderRes['order_hash']);
assert($lookupOrder['success'] === true && $lookupOrder['type'] === 'order', "Lookup by order hash failed");
echo "[8] Lookup Order Hash -> SUCCESS: {$lookupOrder['title']} for {$lookupOrder['record_id']}\n";

$lookupPayment = HashEngine::lookupHash($db, $accRes['payment_hash']);
assert($lookupPayment['success'] === true, "Lookup by payment hash failed");
echo "[9] Lookup Payment Hash -> SUCCESS: {$lookupPayment['title']}\n";

$lookupDispatch = HashEngine::lookupHash($db, $dspRes['dispatch_hash']);
assert($lookupDispatch['success'] === true, "Lookup by dispatch hash failed");
echo "[10] Lookup Dispatch Hash -> SUCCESS: {$lookupDispatch['title']}\n";

// 8. Staff Creation test
$newStaffRes = Staff::createStaff([
    'full_name' => 'Mahesh Verma',
    'username' => 'mahesh_accounts_test_' . rand(100, 999),
    'email' => 'mahesh_' . rand(100, 999) . '@sakshiinfotech.com',
    'phone' => '9829055555',
    'password' => 'secret123',
    'role' => 'staff_accounts'
]);
echo "[11] Admin staff creation test -> SUCCESS: {$newStaffRes['message']}\n";
assert($newStaffRes['success'] === true, "Staff creation failed");

echo "\n>>> ALL 11 TEST PHASES PASSED WITH 100% SUCCESS! <<<\n";
