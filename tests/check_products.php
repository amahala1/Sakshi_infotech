<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Product.php';

$prods = Product::getProducts(['limit' => 20]);
echo "Total products fetched: " . count($prods) . "\n";
foreach ($prods as $p) {
    $img = $p['image_url'];
    $existsInAssets = file_exists(PUBLIC_PATH . '/assets/images/' . $img);
    $existsInImages = file_exists(PUBLIC_PATH . '/images/' . $img);
    echo "ID: {$p['id']} | Name: {$p['name']} | Price: {$p['sale_price']} | Img: {$img} [Assets: " . ($existsInAssets ? 'YES' : 'NO') . ", Images: " . ($existsInImages ? 'YES' : 'NO') . "]\n";
}
