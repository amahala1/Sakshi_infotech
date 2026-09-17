<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/src/Auth.php';

Auth::logout();
$dest = function_exists('url') ? url('sale.php') : '/sale.php';
header("Location: {$dest}");
exit;
