<?php
/**
 * Migration Script: Add subcategories table and subcategory_id to products
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/config/database.php';

$db = Database::getConnection();

echo "Starting Subcategories Migration...\n";

// 1. Create subcategories table in SQLite
$db->exec("
    CREATE TABLE IF NOT EXISTS subcategories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        slug TEXT UNIQUE NOT NULL,
        description TEXT DEFAULT NULL,
        status INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    )
");
echo "✓ Subcategories table created/verified.\n";

// 2. Add subcategory_id to products table if not exists
$cols = $db->query("PRAGMA table_info(products)")->fetchAll(PDO::FETCH_ASSOC);
$hasSubcat = false;
foreach ($cols as $c) {
    if ($c['name'] === 'subcategory_id') {
        $hasSubcat = true;
        break;
    }
}

if (!$hasSubcat) {
    $db->exec("ALTER TABLE products ADD COLUMN subcategory_id INTEGER DEFAULT NULL");
    echo "✓ Added subcategory_id column to products table.\n";
} else {
    echo "✓ subcategory_id column already present in products.\n";
}

// 3. Seed Sub-Categories for each of the 9 Main Categories
$subcats = [
    // 1: Laptops & Desktops (computer)
    ['id' => 1, 'category_id' => 1, 'name' => 'Business & Commercial Laptops', 'slug' => 'business-laptops', 'desc' => 'Lenovo ThinkPad, HP ProBook, Dell Latitude laptops'],
    ['id' => 2, 'category_id' => 1, 'name' => 'Mini & Desktop PCs', 'slug' => 'mini-desktop-pcs', 'desc' => 'Compact desktop mini PCs, commercial workstations'],
    ['id' => 3, 'category_id' => 1, 'name' => 'Gaming & Creator Laptops', 'slug' => 'gaming-laptops', 'desc' => 'High performance dedicated GPU laptops'],

    // 2: Keyboards & Mice (computer)
    ['id' => 4, 'category_id' => 2, 'name' => 'Wireless Keyboard & Mouse Combos', 'slug' => 'wireless-combos', 'desc' => 'Silent wireless office keyboard and optical mouse sets'],
    ['id' => 5, 'category_id' => 2, 'name' => 'Gaming Keyboards & RGB Mice', 'slug' => 'gaming-keyboards-mice', 'desc' => 'Mechanical switches, backlit LED gaming combos'],
    ['id' => 6, 'category_id' => 2, 'name' => 'Ergonomic & Wired Accessories', 'slug' => 'ergonomic-wired', 'desc' => 'Standard USB keyboards, trackballs, numeric pads'],

    // 3: Printers & Scanners (computer)
    ['id' => 7, 'category_id' => 3, 'name' => 'Single-Function Laser Printers', 'slug' => 'laser-printers', 'desc' => 'Monochrome high-speed Wi-Fi laser printers for office bills'],
    ['id' => 8, 'category_id' => 3, 'name' => 'All-in-One Ink Tank Printers', 'slug' => 'ink-tank-printers', 'desc' => 'Colour print, scan, copy with high yield ink bottles'],
    ['id' => 9, 'category_id' => 3, 'name' => 'Barcode & POS Scanners', 'slug' => 'barcode-pos-scanners', 'desc' => '1D/2D QR code retail barcode handheld scanners'],

    // 4: Storage & Memory (computer)
    ['id' => 10, 'category_id' => 4, 'name' => 'NVMe M.2 Solid State Drives', 'slug' => 'nvme-m2-ssd', 'desc' => 'PCIe Gen4 Gen3 high-speed SSD storage'],
    ['id' => 11, 'category_id' => 4, 'name' => 'Desktop & Laptop RAM', 'slug' => 'ddr4-ddr5-ram', 'desc' => 'DDR4 & DDR5 performance RAM memory modules'],
    ['id' => 12, 'category_id' => 4, 'name' => 'External Portable Hard Drives', 'slug' => 'external-hdd', 'desc' => 'USB 3.0 backup hard disks and flash drives'],

    // 5: Networking & Cables (computer)
    ['id' => 13, 'category_id' => 5, 'name' => 'Wi-Fi 6 Routers & Access Points', 'slug' => 'wifi-6-routers', 'desc' => 'Gigabit dual band wireless routers'],
    ['id' => 14, 'category_id' => 5, 'name' => 'Ethernet LAN Cables (CAT6)', 'slug' => 'ethernet-lan-cables', 'desc' => 'High speed RJ45 networking patch cords & rolls'],
    ['id' => 15, 'category_id' => 5, 'name' => 'Display & Power Adapters', 'slug' => 'display-power-adapters', 'desc' => 'HDMI, VGA, Type-C hubs and laptop chargers'],

    // 6: Printing & Copier Paper (stationery)
    ['id' => 16, 'category_id' => 6, 'name' => 'A4 Copier Paper (75/80 GSM)', 'slug' => 'a4-copier-paper', 'desc' => 'JK Copier 75 GSM & 80 GSM reams of 500 sheets'],
    ['id' => 17, 'category_id' => 6, 'name' => 'Wholesale B2B Paper Cartons', 'slug' => 'wholesale-paper-cartons', 'desc' => 'Bulk cartons of 5 reams / 2500 sheets for institutions'],
    ['id' => 18, 'category_id' => 6, 'name' => 'Legal & Photo Glossy Paper', 'slug' => 'legal-photo-paper', 'desc' => 'FS Legal court fee paper and photo paper'],

    // 7: Toners & Cartridges (stationery)
    ['id' => 19, 'category_id' => 7, 'name' => 'LaserJet Compatible Toners', 'slug' => 'laserjet-compatible-toners', 'desc' => '12A, 88A, 78A, 05A black laser toner cartridges'],
    ['id' => 20, 'category_id' => 7, 'name' => 'Original Ink Bottles & Refills', 'slug' => 'ink-bottles-refills', 'desc' => 'Canon GI-790, HP GT52/GT53, Epson 003 ink bottles'],
    ['id' => 21, 'category_id' => 7, 'name' => 'Refill Toner Powder & OPC Drums', 'slug' => 'toner-powder-drums', 'desc' => 'Bulk toner powder bottles and printer spare drums'],

    // 8: Office Registers & Files (stationery)
    ['id' => 22, 'category_id' => 8, 'name' => 'Hard Bound Account Registers', 'slug' => 'account-registers', 'desc' => '200/400 page ledger, cash book, attendance registers'],
    ['id' => 23, 'category_id' => 8, 'name' => 'Lever Arch Box Files', 'slug' => 'lever-arch-files', 'desc' => 'Heavy duty Kangaro & Solo 75mm box files with ring binder'],
    ['id' => 24, 'category_id' => 8, 'name' => 'Cobra & Document Folders', 'slug' => 'cobra-document-folders', 'desc' => 'Spring cobra files, button folders, conference files'],

    // 9: Pens, Markers & Desktops (stationery)
    ['id' => 25, 'category_id' => 9, 'name' => 'Commercial Desk Calculators', 'slug' => 'desk-calculators', 'desc' => 'Casio MJ-120D, 12-digit check & correct GST calculators'],
    ['id' => 26, 'category_id' => 9, 'name' => 'Ball & Gel Pen Boxes', 'slug' => 'ball-gel-pens', 'desc' => 'Cello Butterflow, Reynolds, Pentonic pen packs'],
    ['id' => 27, 'category_id' => 9, 'name' => 'Whiteboard Markers & Highlighters', 'slug' => 'markers-highlighters', 'desc' => 'Camlin whiteboard markers, Faber Castell highlighters']
];

$stmt = $db->prepare("
    INSERT OR REPLACE INTO subcategories (id, category_id, name, slug, description, status)
    VALUES (?, ?, ?, ?, ?, 1)
");

foreach ($subcats as $s) {
    $stmt->execute([$s['id'], $s['category_id'], $s['name'], $s['slug'], $s['desc']]);
}
echo "✓ Seeded " . count($subcats) . " subcategories across all 9 main categories.\n";

// 4. Map existing products to their proper subcategories
$productSubcatMap = [
    1 => 1,  // Lenovo ThinkPad -> Business & Commercial Laptops
    2 => 2,  // HP ProDesk Mini -> Mini & Desktop PCs
    3 => 4,  // Logitech MK295 -> Wireless Keyboard & Mouse Combos
    4 => 5,  // Zebronics Transformer -> Gaming Keyboards & RGB Mice
    5 => 7,  // HP Laser 1008w -> Single-Function Laser Printers
    6 => 8,  // Canon PIXMA G3010 -> All-in-One Ink Tank Printers
    7 => 10, // Crucial P3 Plus 1TB -> NVMe M.2 Solid State Drives
    8 => 11, // Kingston Fury 16GB -> Desktop & Laptop RAM
    9 => 13, // TP-Link Archer AX12 -> Wi-Fi 6 Routers & Access Points
    10 => 16,// JK Copier A4 75 GSM -> A4 Copier Paper (75/80 GSM)
    11 => 17,// B2B Carton JK Copier -> Wholesale B2B Paper Cartons
    12 => 19,// ProDot 12A Toner -> LaserJet Compatible Toners
    13 => 20,// Canon GI-790 Ink Set -> Original Ink Bottles & Refills
    14 => 22,// Account Register -> Hard Bound Account Registers
    15 => 23,// Kangaro Lever Arch File -> Lever Arch Box Files
    16 => 25,// Casio MJ-120D Calculator -> Commercial Desk Calculators
    17 => 26 // Cello Butterflow Ball Pens -> Ball & Gel Pen Boxes
];

$updateProd = $db->prepare("UPDATE products SET subcategory_id = ? WHERE id = ?");
foreach ($productSubcatMap as $pId => $subcatId) {
    $updateProd->execute([$subcatId, $pId]);
}
echo "✓ Mapped all products to their corresponding subcategories.\n";

echo "Migration finished successfully!\n";
