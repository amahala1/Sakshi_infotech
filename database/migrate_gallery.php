<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getConnection();
$driver = Database::getDriver();

try {
    if ($driver === 'sqlite') {
        // Check if column exists in SQLite
        $cols = $db->query("PRAGMA table_info(products)")->fetchAll(PDO::FETCH_ASSOC);
        $hasCol = false;
        foreach ($cols as $col) {
            if ($col['name'] === 'gallery_images') {
                $hasCol = true;
                break;
            }
        }
        if (!$hasCol) {
            $db->exec("ALTER TABLE products ADD COLUMN gallery_images TEXT DEFAULT NULL");
            echo "Added gallery_images column to SQLite products table.\n";
        } else {
            echo "gallery_images column already exists in SQLite.\n";
        }
    } else {
        // MySQL
        $stmt = $db->query("SHOW COLUMNS FROM products LIKE 'gallery_images'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE products ADD COLUMN gallery_images TEXT DEFAULT NULL AFTER image_url");
            echo "Added gallery_images column to MySQL products table.\n";
        } else {
            echo "gallery_images column already exists in MySQL.\n";
        }
    }
} catch (Throwable $e) {
    echo "Migration note: " . $e->getMessage() . "\n";
}

// Seed sample gallery images for existing demo products so product page immediately has multiple photos
$sampleGalleries = [
    1 => ['laptop-lenovo.webp', 'laptop-lenovo.png', 'hp-mini-pc.webp'],
    2 => ['hp-mini-pc.webp', 'hp-mini-pc.png', 'crucial-nvme-1tb.webp'],
    3 => ['logitech-mk295.webp', 'logitech-mk295.png', 'zebronics-rgb.webp'],
    5 => ['hp-laser-printer.webp', 'hp-laser-printer.png', 'prodot-12a.webp'],
    6 => ['canon-g3010.webp', 'canon-g3010.png', 'canon-gi790-set.webp'],
    7 => ['crucial-nvme-1tb.webp', 'crucial-nvme-1tb.png', 'kingston-fury-ram.webp'],
    10 => ['jk-copier-a4.webp', 'jk-copier-a4.png', 'jk-copier-box.webp'],
    12 => ['prodot-12a.webp', 'prodot-12a.png', 'hp-laser-printer.webp'],
    14 => ['account-register.webp', 'account-register.png', 'lever-arch-file.webp'],
    16 => ['casio-mj120d.webp', 'casio-mj120d.png', 'reynolds-045.webp']
];

$updateStmt = $db->prepare("UPDATE products SET gallery_images = ? WHERE id = ?");
foreach ($sampleGalleries as $pid => $images) {
    $updateStmt->execute([json_encode($images), $pid]);
}
echo "Seeded sample multi-photo galleries for demo products.\n";
