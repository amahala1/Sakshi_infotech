<?php
/**
 * Generate Real TrueColor WebP and PNG Product Images using PHP GD
 */
require_once __DIR__ . '/../config/config.php';

$products = [
    'laptop-lenovo' => ['title' => 'Lenovo ThinkPad', 'cat' => 'LAPTOP', 'sub' => 'Core i5 13th Gen | 16GB | 512GB', 'bg1' => [238, 242, 255], 'bg2' => [224, 231, 255], 'accent' => [37, 99, 235], 'icon' => 'LAPTOP'],
    'hp-mini-pc' => ['title' => 'HP ProDesk 400', 'cat' => 'DESKTOP PC', 'sub' => 'Mini G9 Core i5 | 16GB | 512GB', 'bg1' => [240, 249, 255], 'bg2' => [224, 242, 254], 'accent' => [2, 132, 199], 'icon' => 'PC'],
    'logitech-mk295' => ['title' => 'Logitech MK295', 'cat' => 'COMBO', 'sub' => 'Silent Wireless Keyboard & Mouse', 'bg1' => [248, 250, 252], 'bg2' => [226, 232, 240], 'accent' => [71, 85, 105], 'icon' => 'KEYBOARD'],
    'zebronics-rgb' => ['title' => 'Zebronics RGB', 'cat' => 'GAMING', 'sub' => 'Transformer Gaming Combo', 'bg1' => [255, 241, 242], 'bg2' => [255, 228, 230], 'accent' => [225, 29, 72], 'icon' => 'GAMING'],
    'hp-laser-printer' => ['title' => 'HP Laser 1008w', 'cat' => 'PRINTER', 'sub' => 'Single Function Wireless Laser', 'bg1' => [240, 253, 250], 'bg2' => [204, 251, 241], 'accent' => [13, 148, 136], 'icon' => 'PRINTER'],
    'canon-g3010' => ['title' => 'Canon PIXMA G3010', 'cat' => 'INK TANK', 'sub' => 'All-in-One Wireless Printer', 'bg1' => [254, 242, 242], 'bg2' => [254, 226, 226], 'accent' => [220, 38, 38], 'icon' => 'PRINTER'],
    'crucial-nvme-1tb' => ['title' => 'Crucial P3 Plus 1TB', 'cat' => 'STORAGE', 'sub' => 'PCIe 4.0 3D NAND NVMe M.2', 'bg1' => [239, 246, 255], 'bg2' => [219, 234, 254], 'accent' => [29, 78, 216], 'icon' => 'SSD'],
    'kingston-fury-ram' => ['title' => 'Kingston Fury Beast', 'cat' => 'MEMORY', 'sub' => '16GB DDR4 3200MHz RAM', 'bg1' => [254, 243, 199], 'bg2' => [253, 230, 138], 'accent' => [180, 83, 9], 'icon' => 'RAM'],
    'tplink-ax12' => ['title' => 'TP-Link Archer AX12', 'cat' => 'NETWORKING', 'sub' => 'AX1500 Dual Band Wi-Fi 6', 'bg1' => [236, 253, 245], 'bg2' => [209, 250, 229], 'accent' => [5, 150, 105], 'icon' => 'WIFI'],
    'jk-copier-a4' => ['title' => 'JK Copier A4 Paper', 'cat' => 'STATIONERY', 'sub' => '75 GSM | 500 Sheets Ream', 'bg1' => [240, 253, 244], 'bg2' => [220, 252, 231], 'accent' => [22, 163, 74], 'icon' => 'PAPER'],
    'jk-copier-box' => ['title' => 'JK Copier B2B Carton', 'cat' => 'WHOLESALE', 'sub' => '5 Reams / 2500 Sheets Box', 'bg1' => [254, 249, 195], 'bg2' => [254, 240, 138], 'accent' => [161, 98, 7], 'icon' => 'BOX'],
    'prodot-12a' => ['title' => 'ProDot 12A / Q2612A', 'cat' => 'TONER', 'sub' => 'Laser Toner Cartridge (2000 Pgs)', 'bg1' => [248, 250, 252], 'bg2' => [226, 232, 240], 'accent' => [51, 65, 85], 'icon' => 'TONER'],
    'canon-gi790-set' => ['title' => 'Canon GI-790 Set', 'cat' => 'INK BOTTLES', 'sub' => 'Black + Cyan + Magenta + Yellow', 'bg1' => [255, 247, 237], 'bg2' => [254, 215, 170], 'accent' => [234, 88, 12], 'icon' => 'INK'],
    'account-register' => ['title' => 'Executive Register', 'cat' => 'REGISTERS', 'sub' => 'Hard Bound | 200 Pages 70 GSM', 'bg1' => [250, 245, 255], 'bg2' => [243, 232, 255], 'accent' => [147, 51, 234], 'icon' => 'BOOK'],
    'lever-arch-file' => ['title' => 'Kangaro Lever Arch', 'cat' => 'OFFICE FILES', 'sub' => 'Heavy Duty Lever Arch (Pack of 5)', 'bg1' => [241, 245, 249], 'bg2' => [226, 232, 240], 'accent' => [71, 85, 105], 'icon' => 'FILE'],
    'casio-mj120d' => ['title' => 'Casio MJ-120D Plus', 'cat' => 'CALCULATOR', 'sub' => 'Desktop Financial Tax Calculator', 'bg1' => [240, 253, 250], 'bg2' => [204, 251, 241], 'accent' => [13, 148, 136], 'icon' => 'CALC'],
    'reynolds-045' => ['title' => 'Reynolds 045 Pens', 'cat' => 'PENS', 'sub' => 'Fine Carbure Ball Pens (Box of 50)', 'bg1' => [239, 246, 255], 'bg2' => [219, 234, 254], 'accent' => [37, 99, 235], 'icon' => 'PEN']
];

$w = 400;
$h = 320;

foreach ($products as $name => $meta) {
    $im = imagecreatetruecolor($w, $h);
    imagesavealpha($im, true);

    // Gradient background
    for ($y = 0; $y < $h; $y++) {
        $ratio = $y / $h;
        $r = (int)($meta['bg1'][0] + ($meta['bg2'][0] - $meta['bg1'][0]) * $ratio);
        $g = (int)($meta['bg1'][1] + ($meta['bg2'][1] - $meta['bg1'][1]) * $ratio);
        $b = (int)($meta['bg1'][2] + ($meta['bg2'][2] - $meta['bg1'][2]) * $ratio);
        $col = imagecolorallocate($im, $r, $g, $b);
        imageline($im, 0, $y, $w, $y, $col);
    }

    // Border
    $borderCol = imagecolorallocate($im, 203, 213, 225);
    imagerectangle($im, 0, 0, $w - 1, $h - 1, $borderCol);

    // Center Product Card Plate
    $white = imagecolorallocate($im, 255, 255, 255);
    $cardBorder = imagecolorallocate($im, 226, 232, 240);
    imagefilledrectangle($im, 40, 45, 360, 240, $white);
    imagerectangle($im, 40, 45, 360, 240, $cardBorder);

    // Header Category Badge
    $badgeCol = imagecolorallocate($im, $meta['accent'][0], $meta['accent'][1], $meta['accent'][2]);
    imagefilledrectangle($im, 55, 58, 175, 78, $badgeCol);
    imagestring($im, 2, 62, 62, $meta['cat'], $white);

    // Main Icon / Type representation
    $accentMuted = imagecolorallocate($im, $meta['accent'][0], $meta['accent'][1], $meta['accent'][2]);
    imagefilledellipse($im, 200, 135, 70, 70, $white);
    imageellipse($im, 200, 135, 70, 70, $badgeCol);
    imageellipse($im, 200, 135, 68, 68, $badgeCol);
    imagestring($im, 5, 170, 126, "[" . $meta['icon'] . "]", $accentMuted);

    // Product Title
    $titleCol = imagecolorallocate($im, 15, 23, 42);
    $titleX = max(55, (int)(200 - (strlen($meta['title']) * 9 / 2)));
    imagestring($im, 5, $titleX, 185, $meta['title'], $titleCol);

    // Subtitle / Specs
    $subCol = imagecolorallocate($im, 100, 116, 139);
    $subX = max(50, (int)(200 - (strlen($meta['sub']) * 6.5 / 2)));
    imagestring($im, 3, $subX, 210, $meta['sub'], $subCol);

    // Lower Footer: Sakshi Infotech Watermark
    $brandCol = imagecolorallocate($im, $meta['accent'][0], $meta['accent'][1], $meta['accent'][2]);
    imagestring($im, 4, 110, 260, "SAKSHI INFOTECH", $brandCol);
    $tagCol = imagecolorallocate($im, 100, 116, 139);
    imagestring($im, 2, 95, 280, "100% Genuine Certified Hardware & Supplies", $tagCol);

    // Save as WEBP
    $webpPath1 = PUBLIC_PATH . "/assets/images/{$name}.webp";
    $webpPath2 = PUBLIC_PATH . "/images/{$name}.webp";
    imagewebp($im, $webpPath1, 90);
    @copy($webpPath1, $webpPath2);

    // Save as REAL PNG
    $pngPath1 = PUBLIC_PATH . "/assets/images/{$name}.png";
    $pngPath2 = PUBLIC_PATH . "/images/{$name}.png";
    imagepng($im, $pngPath1, 6);
    @copy($pngPath1, $pngPath2);

    imagedestroy($im);
    echo "Generated {$name}.webp and {$name}.png\n";
}

echo "All real truecolor product images generated successfully!\n";
