<?php
require_once dirname(__DIR__) . '/config/config.php';
header("Location: " . (function_exists('url') ? url('sale.php') : '/sale.php'));
exit;
