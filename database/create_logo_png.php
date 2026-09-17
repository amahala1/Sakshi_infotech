<?php
/**
 * Generates PNG logo for images/logo.png
 */

$targetDir = dirname(__DIR__) . '/public/images';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$w = 340;
$h = 70;
$im = imagecreatetruecolor($w, $h);

// Transparency setup
imagealphablending($im, false);
imagesavealpha($im, true);
$transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
imagefilledrectangle($im, 0, 0, $w, $h, $transparent);
imagealphablending($im, true);

// Colors
$primaryBlue = imagecolorallocate($im, 37, 99, 235);
$slateDark = imagecolorallocate($im, 15, 23, 42);
$mutedGray = imagecolorallocate($im, 100, 116, 139);
$pureWhite = imagecolorallocate($im, 255, 255, 255);
$accentAmber = imagecolorallocate($im, 245, 158, 11);

// Rounded icon container
imagefilledellipse($im, 32, 35, 50, 50, $primaryBlue);
imagestring($im, 5, 25, 27, "SI", $pureWhite);
imagefilledellipse($im, 50, 20, 10, 10, $accentAmber);

// Brand Typography
imagestring($im, 5, 68, 18, "SAKSHI INFOTECH", $slateDark);
imagestring($im, 3, 69, 38, "COMPUTERS & STATIONERY", $mutedGray);

$outPath = $targetDir . '/logo.png';
imagepng($im, $outPath);
imagedestroy($im);

// Also convert to webp version
if (function_exists('imagewebp')) {
    $im2 = imagecreatefrompng($outPath);
    imagepalettetotruecolor($im2);
    imagealphablending($im2, true);
    imagesavealpha($im2, true);
    imagewebp($im2, $targetDir . '/logo.webp', 95);
    imagedestroy($im2);
}

echo "Logo generated at {$outPath}\n";
