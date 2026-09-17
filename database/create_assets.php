<?php
/**
 * Generates beautiful modern SVG assets for Sakshi Infotech
 */

$imgDir = dirname(__DIR__) . '/public/assets/images';
if (!is_dir($imgDir)) {
    mkdir($imgDir, 0777, true);
}

// Brand Logo
$logoSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 80" width="320" height="80">
  <defs>
    <linearGradient id="siGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#0284c7" />
      <stop offset="100%" stop-color="#2563eb" />
    </linearGradient>
    <linearGradient id="accentGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#f59e0b" />
      <stop offset="100%" stop-color="#d97706" />
    </linearGradient>
  </defs>
  <rect x="5" y="10" width="60" height="60" rx="14" fill="url(#siGrad)" />
  <path d="M22 28 C22 22, 48 22, 48 28 C48 35, 22 35, 22 42 C22 50, 48 50, 48 44" fill="none" stroke="#ffffff" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round" />
  <circle cx="48" cy="24" r="3" fill="url(#accentGrad)" />
  <text x="76" y="42" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-weight="900" font-size="24" fill="#0f172a" letter-spacing="-0.5">SAKSHI</text>
  <text x="168" y="42" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-weight="800" font-size="24" fill="#2563eb" letter-spacing="-0.5">INFOTECH</text>
  <text x="77" y="60" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-weight="600" font-size="10.5" fill="#64748b" letter-spacing="1.5">COMPUTER &amp; STATIONERY HUB</text>
</svg>
SVG;
file_put_contents($imgDir . '/logo.svg', $logoSvg);

// UPI QR Demo code
$upiQrSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" width="240" height="240">
  <rect width="240" height="240" rx="16" fill="#ffffff" stroke="#e2e8f0" stroke-width="2"/>
  <rect x="25" y="25" width="60" height="60" rx="8" fill="#0f172a" />
  <rect x="35" y="35" width="40" height="40" rx="4" fill="#ffffff" />
  <rect x="43" y="43" width="24" height="24" rx="2" fill="#0284c7" />

  <rect x="155" y="25" width="60" height="60" rx="8" fill="#0f172a" />
  <rect x="165" y="35" width="40" height="40" rx="4" fill="#ffffff" />
  <rect x="173" y="43" width="24" height="24" rx="2" fill="#0284c7" />

  <rect x="25" y="155" width="60" height="60" rx="8" fill="#0f172a" />
  <rect x="35" y="165" width="40" height="40" rx="4" fill="#ffffff" />
  <rect x="43" y="173" width="24" height="24" rx="2" fill="#0284c7" />

  <!-- QR Pattern Grid Elements -->
  <rect x="100" y="30" width="12" height="12" fill="#334155"/>
  <rect x="120" y="30" width="12" height="12" fill="#334155"/>
  <rect x="100" y="55" width="25" height="12" fill="#0284c7"/>
  <rect x="135" y="55" width="10" height="25" fill="#334155"/>
  
  <rect x="30" y="100" width="15" height="15" fill="#334155"/>
  <rect x="60" y="105" width="20" height="10" fill="#0284c7"/>
  <rect x="100" y="100" width="40" height="40" rx="8" fill="#f8fafc" stroke="#cbd5e1" stroke-width="2"/>
  <text x="120" y="125" font-family="sans-serif" font-weight="900" font-size="16" text-anchor="middle" fill="#2563eb">SI</text>

  <rect x="155" y="105" width="15" height="15" fill="#334155"/>
  <rect x="180" y="100" width="25" height="15" fill="#0284c7"/>

  <rect x="100" y="160" width="15" height="20" fill="#334155"/>
  <rect x="125" y="155" width="20" height="15" fill="#0284c7"/>
  <rect x="155" y="155" width="25" height="10" fill="#334155"/>
  <rect x="190" y="165" width="25" height="25" fill="#0284c7"/>
  <rect x="110" y="190" width="35" height="15" fill="#334155"/>
  <rect x="160" y="195" width="20" height="20" fill="#0f172a"/>
  
  <text x="120" y="230" font-family="sans-serif" font-size="9.5" font-weight="700" text-anchor="middle" fill="#64748b">SCAN WITH ANY UPI APP</text>
</svg>
SVG;
file_put_contents($imgDir . '/upi-qr.svg', $upiQrSvg);

// Product SVG generator
function makeProductSvg($title, $cat, $tag, $color1, $color2, $iconCode) {
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 320" width="400" height="320">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#f8fafc" />
      <stop offset="100%" stop-color="#e2e8f0" />
    </linearGradient>
    <linearGradient id="accent" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$color1}" />
      <stop offset="100%" stop-color="{$color2}" />
    </linearGradient>
  </defs>
  <rect width="400" height="320" rx="16" fill="url(#bg)" />
  <circle cx="200" cy="140" r="85" fill="url(#accent)" opacity="0.12" />
  <rect x="120" y="70" width="160" height="130" rx="14" fill="#ffffff" stroke="#e2e8f0" stroke-width="2" filter="drop-shadow(0 10px 15px rgba(0,0,0,0.05))" />
  
  <!-- Icon representation -->
  <text x="200" y="148" font-family="sans-serif" font-size="46" text-anchor="middle" fill="{$color1}">{$iconCode}</text>
  
  <!-- Badge -->
  <rect x="25" y="25" width="95" height="26" rx="6" fill="{$color1}" />
  <text x="72.5" y="42" font-family="sans-serif" font-weight="800" font-size="11" text-anchor="middle" fill="#ffffff" letter-spacing="0.5">{$tag}</text>

  <!-- Title & category -->
  <rect x="20" y="225" width="360" height="80" rx="10" fill="#ffffff" stroke="#f1f5f9" />
  <text x="35" y="252" font-family="sans-serif" font-weight="800" font-size="14.5" fill="#0f172a">{$title}</text>
  <text x="35" y="274" font-family="sans-serif" font-weight="600" font-size="12" fill="{$color1}">{$cat}</text>
  <text x="35" y="292" font-family="sans-serif" font-weight="500" font-size="11" fill="#64748b">Sakshi Infotech Certified Original Product</text>
</svg>
SVG;
}

$items = [
    'laptop-lenovo.png' => ['Lenovo ThinkPad E14 i5', 'Laptops & Desktops', 'GENUINE', '#2563eb', '#1d4ed8', '💻'],
    'hp-mini-pc.png' => ['HP ProDesk 400 Mini PC', 'Laptops & Desktops', 'COMMERCIAL', '#0284c7', '#0369a1', '🖥️'],
    'logitech-mk295.png' => ['Logitech MK295 Silent Combo', 'Keyboards & Mice', 'WIRELESS', '#059669', '#047857', '⌨️'],
    'zebronics-rgb.png' => ['Zebronics RGB Gaming Combo', 'Keyboards & Mice', 'GAMING', '#7c3aed', '#6d28d9', '🖱️'],
    'hp-laser-printer.png' => ['HP Laser 1008w Printer', 'Printers & Scanners', 'LASER JET', '#ea580c', '#c2410c', '🖨️'],
    'canon-g3010.png' => ['Canon PIXMA G3010 All-in-One', 'Printers & Scanners', 'INK TANK', '#dc2626', '#b91c1c', '📠'],
    'crucial-nvme-1tb.png' => ['Crucial P3 Plus 1TB NVMe', 'Storage & Memory', 'PCIE GEN4', '#0284c7', '#2563eb', '💾'],
    'kingston-fury-ram.png' => ['Kingston Fury 16GB RAM', 'Storage & Memory', '3200MHz', '#b45309', '#92400e', '⚡'],
    'tplink-ax12.png' => ['TP-Link AX1500 Wi-Fi 6 Router', 'Networking & Cables', 'GIGABIT', '#0d9488', '#0f766e', '📡'],
    'jk-copier-a4.png' => ['JK Copier A4 Paper 75 GSM', 'Paper & Stationery', 'BESTSELLER', '#16a34a', '#15803d', '📄'],
    'jk-copier-box.png' => ['JK Copier Carton (5 Reams)', 'Paper & Stationery', 'BULK B2B', '#0284c7', '#0369a1', '📦'],
    'prodot-12a.png' => ['ProDot 12A Laser Toner', 'Toners & Cartridges', 'HIGH YIELD', '#475569', '#334155', '🖨️'],
    'canon-gi790-set.png' => ['Canon GI-790 Ink Bottle Set', 'Toners & Cartridges', '4-COLOR SET', '#e11d48', '#be123c', '💧'],
    'account-register.png' => ['Executive Hard Bound Register', 'Registers & Files', '200 PAGES', '#4338ca', '#3730a3', '📒'],
    'lever-arch-file.png' => ['Kangaro Lever Arch Files (Pack 5)', 'Registers & Files', 'HEAVY DUTY', '#0891b2', '#0e7490', '📁'],
    'casio-mj120d.png' => ['Casio MJ-120D Plus Calculator', 'Office Electronics', '12 DIGIT', '#0284c7', '#0369a1', '🔢'],
    'reynolds-045.png' => ['Reynolds 045 Ball Pens (Box 50)', 'Pens & Supplies', 'PACK OF 50', '#2563eb', '#1d4ed8', '🖊️'],
];

foreach ($items as $file => $meta) {
    // Generate both .svg and filename for direct inclusion
    $svg = makeProductSvg($meta[0], $meta[1], $meta[2], $meta[3], $meta[4], $meta[5]);
    file_put_contents($imgDir . '/' . $file, $svg);
    file_put_contents($imgDir . '/' . str_replace('.png', '.svg', $file), $svg);
}

echo "Created " . count($items) . " visual assets successfully.\n";
