<?php
/**
 * Sakshi Infotech - Product Catalog & Inventory Manager
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/HashEngine.php';

class Product {

    public static function getAllCategories(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT c.*, COUNT(p.id) as product_count
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id AND p.status = 1
            WHERE c.status = 1
            GROUP BY c.id
            ORDER BY c.type ASC, c.name ASC
        ");
        return $stmt->fetchAll();
    }

    public static function getCategoriesByType(string $type): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT c.*, COUNT(p.id) as product_count
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id AND p.status = 1
            WHERE c.type = ? AND c.status = 1
            GROUP BY c.id
            ORDER BY c.name ASC
        ");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }

    public static function getCategoriesWithSubcategories(?string $type = null): array {
        $db = Database::getConnection();
        $sql = "
            SELECT c.*, COUNT(DISTINCT p.id) as product_count
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id AND p.status = 1
            WHERE c.status = 1
        ";
        $params = [];
        if ($type) {
            $sql .= " AND c.type = ?";
            $params[] = $type;
        }
        $sql .= " GROUP BY c.id ORDER BY c.name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $cats = $stmt->fetchAll();

        // Fetch subcategories for each category with their individual product count
        foreach ($cats as &$cat) {
            $subStmt = $db->prepare("
                SELECT sc.*, COUNT(DISTINCT p.id) as product_count
                FROM subcategories sc
                LEFT JOIN products p ON sc.id = p.subcategory_id AND p.status = 1
                WHERE sc.category_id = ? AND sc.status = 1
                GROUP BY sc.id
                ORDER BY sc.name ASC
            ");
            $subStmt->execute([$cat['id']]);
            $cat['subcategories'] = $subStmt->fetchAll();
        }
        return $cats;
    }

    public static function getAllSubcategories(?int $categoryId = null): array {
        $db = Database::getConnection();
        if ($categoryId) {
            $stmt = $db->prepare("SELECT * FROM subcategories WHERE category_id = ? AND status = 1 ORDER BY name ASC");
            $stmt->execute([$categoryId]);
        } else {
            $stmt = $db->query("
                SELECT sc.*, c.name as category_name, c.type as category_type
                FROM subcategories sc
                JOIN categories c ON sc.category_id = c.id
                WHERE sc.status = 1
                ORDER BY c.type ASC, c.name ASC, sc.name ASC
            ");
        }
        return $stmt->fetchAll();
    }

    public static function getSubcategoryById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT sc.*, c.name as category_name, c.slug as category_slug, c.type as category_type
            FROM subcategories sc
            JOIN categories c ON sc.category_id = c.id
            WHERE sc.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function getProducts(array $filters = []): array {
        $db = Database::getConnection();
        $sql = "
            SELECT p.*, 
                   c.name as category_name, c.slug as category_slug, c.type as category_type,
                   sc.name as subcategory_name, sc.slug as subcategory_slug
            FROM products p
            JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
            WHERE p.status = 1
        ";
        $params = [];

        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = :category_id";
            $params[':category_id'] = (int)$filters['category_id'];
        }

        if (!empty($filters['subcategory_id'])) {
            $sql .= " AND p.subcategory_id = :subcategory_id";
            $params[':subcategory_id'] = (int)$filters['subcategory_id'];
        }

        if (!empty($filters['category_slug'])) {
            $sql .= " AND c.slug = :category_slug";
            $params[':category_slug'] = $filters['category_slug'];
        }

        if (!empty($filters['subcategory_slug'])) {
            $sql .= " AND sc.slug = :subcategory_slug";
            $params[':subcategory_slug'] = $filters['subcategory_slug'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND c.type = :type";
            $params[':type'] = $filters['type'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE :search OR p.sku LIKE :search OR p.brand LIKE :search OR p.description LIKE :search OR sc.name LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['brand'])) {
            $sql .= " AND p.brand = :brand";
            $params[':brand'] = $filters['brand'];
        }

        if (!empty($filters['min_price'])) {
            $sql .= " AND p.sale_price >= :min_price";
            $params[':min_price'] = (float)$filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $sql .= " AND p.sale_price <= :max_price";
            $params[':max_price'] = (float)$filters['max_price'];
        }

        if (!empty($filters['featured'])) {
            $sql .= " AND p.is_featured = 1";
        }

        // Sorting
        $sort = $filters['sort'] ?? 'newest';
        switch ($sort) {
            case 'price_asc':
                $sql .= " ORDER BY p.sale_price ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY p.sale_price DESC";
                break;
            case 'name_asc':
                $sql .= " ORDER BY p.name ASC";
                break;
            case 'popular':
                $sql .= " ORDER BY p.is_featured DESC, p.stock_qty DESC";
                break;
            case 'newest':
            default:
                $sql .= " ORDER BY p.id DESC";
                break;
        }

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET " . (int)$filters['offset'];
            }
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getProductById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT p.*, 
                   c.name as category_name, c.slug as category_slug, c.type as category_type,
                   sc.name as subcategory_name, sc.slug as subcategory_slug
            FROM products p
            JOIN categories c ON p.category_id = c.id
            LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();
        return $prod ?: null;
    }

    public static function saveProduct(array $data, ?int $id = null): int {
        $db = Database::getConnection();

        // Calculate Data Integrity Hash based on core product payload
        $rawString = trim($data['name']) . '|' . trim($data['sku']) . '|' . (float)$data['sale_price'] . '|' . (int)$data['stock_qty'];
        $integrityHash = HashEngine::generateIntegrityHash($rawString);

        $galleryJson = null;
        if (isset($data['gallery_images'])) {
            if (is_array($data['gallery_images'])) {
                $galleryJson = json_encode(array_values(array_filter($data['gallery_images'])));
            } elseif (is_string($data['gallery_images'])) {
                $galleryJson = $data['gallery_images'];
            }
        }

        $subcatId = !empty($data['subcategory_id']) ? (int)$data['subcategory_id'] : null;

        if ($id) {
            $stmt = $db->prepare("
                UPDATE products SET
                    category_id = ?, subcategory_id = ?, name = ?, sku = ?, hsn_code = ?, brand = ?,
                    regular_price = ?, sale_price = ?, gst_rate = ?, stock_qty = ?,
                    min_stock_alert = ?, description = ?, specifications = ?,
                    image_url = ?, gallery_images = ?, is_featured = ?, data_integrity_hash = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                (int)$data['category_id'],
                $subcatId,
                trim($data['name']),
                trim($data['sku']),
                trim($data['hsn_code'] ?? '8471'),
                trim($data['brand'] ?? ''),
                (float)$data['regular_price'],
                (float)$data['sale_price'],
                (float)($data['gst_rate'] ?? 18.00),
                (int)$data['stock_qty'],
                (int)($data['min_stock_alert'] ?? 3),
                $data['description'] ?? '',
                $data['specifications'] ?? '',
                $data['image_url'] ?? '',
                $galleryJson,
                isset($data['is_featured']) ? 1 : 0,
                $integrityHash,
                isset($data['status']) ? 1 : 0,
                $id
            ]);
            return $id;
        } else {
            $stmt = $db->prepare("
                INSERT INTO products (
                    category_id, subcategory_id, name, sku, hsn_code, brand, regular_price, sale_price,
                    gst_rate, stock_qty, min_stock_alert, description, specifications,
                    image_url, gallery_images, is_featured, data_integrity_hash, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                (int)$data['category_id'],
                $subcatId,
                trim($data['name']),
                trim($data['sku']),
                trim($data['hsn_code'] ?? '8471'),
                trim($data['brand'] ?? ''),
                (float)$data['regular_price'],
                (float)$data['sale_price'],
                (float)($data['gst_rate'] ?? 18.00),
                (int)$data['stock_qty'],
                (int)($data['min_stock_alert'] ?? 3),
                $data['description'] ?? '',
                $data['specifications'] ?? '',
                $data['image_url'] ?? '',
                $galleryJson,
                isset($data['is_featured']) ? 1 : 0,
                $integrityHash
            ]);
            return (int)$db->lastInsertId();
        }
    }

    public static function getGalleryImages(?array $product): array {
        if (!$product) return [];
        $images = [];
        if (!empty($product['image_url'])) {
            $images[] = $product['image_url'];
        }
        if (!empty($product['gallery_images'])) {
            $decoded = json_decode($product['gallery_images'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $img) {
                    if (!empty($img) && !in_array($img, $images)) {
                        $images[] = $img;
                    }
                }
            }
        }
        return $images;
    }

    public static function deleteProduct(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function getLowStockProducts(): array {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT p.*, c.name as category_name
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.stock_qty <= p.min_stock_alert AND p.status = 1
            ORDER BY p.stock_qty ASC
        ");
        return $stmt->fetchAll();
    }
}
