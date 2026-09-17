<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';

echo "Test 1: Default Base URL:\n";
echo "get_base_url(): " . get_base_url() . "\n";
echo "url('sale.php'): " . url('sale.php') . "\n";
echo "asset('images/logo.png'): " . asset('images/logo.png') . "\n";

echo "\nTest 2: Simulating /sales/admin/index.php\n";
$_SERVER['SCRIPT_NAME'] = '/sales/admin/index.php';
echo "get_base_url(): " . get_base_url() . "\n";
echo "url('login.php'): " . url('login.php') . "\n";
echo "url('sale.php'): " . url('sale.php') . "\n";
echo "asset('images/logo.png'): " . asset('images/logo.png') . "\n";

echo "\nTest 3: Simulating /sales/sale.php\n";
$_SERVER['SCRIPT_NAME'] = '/sales/sale.php';
echo "get_base_url(): " . get_base_url() . "\n";
echo "url('cart.php'): " . url('cart.php') . "\n";
echo "asset('images/logo.png'): " . asset('images/logo.png') . "\n";

echo "\nTest 4: Auth Methods Check\n";
echo "Auth::requireStaff exists: " . (method_exists('Auth', 'requireStaff') ? 'YES' : 'NO') . "\n";
echo "Auth::requireAdmin exists: " . (method_exists('Auth', 'requireAdmin') ? 'YES' : 'NO') . "\n";
echo "Auth::requireLogin exists: " . (method_exists('Auth', 'requireLogin') ? 'YES' : 'NO') . "\n";
