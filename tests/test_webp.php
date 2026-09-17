<?php
require_once __DIR__ . '/../config/config.php';

$src = BASE_PATH . '/public/images/logo.png';
$target = BASE_PATH . '/public/assets/images/test_convert.webp';

echo "Testing convertToWebP from PNG to WebP...\n";
$res = convertToWebP($src, $target, 85);
echo "Conversion result: " . ($res ? "SUCCESS" : "FAILED") . "\n";

if (file_exists($target)) {
    echo "WebP file created successfully! Size: " . filesize($target) . " bytes\n";
    $info = getimagesize($target);
    echo "Mime type: " . $info['mime'] . "\n";
    @unlink($target);
} else {
    echo "WebP file does not exist.\n";
}
