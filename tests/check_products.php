<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getConnection();
$rows = $db->query("SELECT id, name, is_featured, category_id FROM products")->fetchAll(PDO::FETCH_ASSOC);
echo "Products featured flag:\n";
foreach ($rows as $r) {
    echo "ID: {$r['id']} | Featured: {$r['is_featured']} | Name: {$r['name']}\n";
}

