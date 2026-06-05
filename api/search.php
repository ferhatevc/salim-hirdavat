<?php
/**
 * Salim Hırdavat - Arama API
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');
$categoryId = (int)($_GET['category'] ?? 0);
$limit = min(30, max(1, (int)($_GET['limit'] ?? 8)));
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'results' => [], 'total' => 0]);
    exit;
}

$where = ['p.is_active = 1'];
$params = [];

// FULLTEXT arama
$where[] = 'MATCH(p.name, p.description, p.short_description, p.sku, p.barcode) AGAINST(? IN BOOLEAN MODE)';
$params[] = $query . '*';

if ($categoryId > 0) {
    $where[] = '(p.category_id = ? OR p.category_id IN (SELECT id FROM categories WHERE parent_id = ?))';
    $params[] = $categoryId;
    $params[] = $categoryId;
}

$whereStr = implode(' AND ', $where);

// Toplam
$total = db()->count('products p', $whereStr, $params);

// Sonuçlar
$results = db()->fetchAll(
    "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.sku, c.name as category_name,
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image_path
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE {$whereStr}
     ORDER BY MATCH(p.name, p.description, p.short_description, p.sku, p.barcode) AGAINST(? IN BOOLEAN MODE) DESC
     LIMIT {$limit} OFFSET {$offset}",
    array_merge($params, [$query . '*'])
);

// Görsel URL'lerini oluştur
foreach ($results as &$r) {
    $r['image'] = $r['image_path'] ? PRODUCT_IMAGE_URL . '/' . $r['image_path'] : null;
    unset($r['image_path']);
    $r['price'] = (float)$r['price'];
    $r['sale_price'] = $r['sale_price'] ? (float)$r['sale_price'] : null;
}

echo json_encode([
    'success' => true,
    'results' => $results,
    'total' => $total,
    'query' => $query,
    'page' => $page,
    'limit' => $limit
]);
