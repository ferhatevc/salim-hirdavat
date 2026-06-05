<?php
/**
 * Salim Hırdavat - Wishlist API
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Favori eklemek için giriş yapmalısınız.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];

switch ($method) {
    case 'POST':
        $productId = (int)($input['product_id'] ?? 0);
        $exists = db()->count('wishlist', 'user_id = ? AND product_id = ?', [$userId, $productId]);
        
        if ($exists) {
            db()->delete('wishlist', 'user_id = ? AND product_id = ?', [$userId, $productId]);
            echo json_encode(['success' => true, 'message' => 'Favorilerden kaldırıldı.', 'action' => 'removed']);
        } else {
            db()->insert('wishlist', ['user_id' => $userId, 'product_id' => $productId]);
            echo json_encode(['success' => true, 'message' => 'Favorilere eklendi!', 'action' => 'added']);
        }
        break;
        
    case 'DELETE':
        $productId = (int)($input['product_id'] ?? 0);
        db()->delete('wishlist', 'user_id = ? AND product_id = ?', [$userId, $productId]);
        echo json_encode(['success' => true, 'message' => 'Favorilerden kaldırıldı.']);
        break;
        
    case 'GET':
        $items = db()->fetchAll(
            "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
             FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = ? AND p.is_active = 1
             ORDER BY w.created_at DESC",
            [$userId]
        );
        echo json_encode(['success' => true, 'items' => $items]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
}
