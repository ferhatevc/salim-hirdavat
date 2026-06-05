<?php
/**
 * Salim Hırdavat - Sepet API
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? null;

try {
    switch ($method) {
        case 'POST':
            if ($action === 'apply_coupon') {
                handleApplyCoupon($input);
            } else {
                handleAddToCart($input);
            }
            break;
        case 'PUT':
            handleUpdateCart($input);
            break;
        case 'DELETE':
            handleRemoveFromCart($input);
            break;
        case 'GET':
            handleGetCart();
            break;
        default:
            jsonResponse(false, 'Geçersiz istek.');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Bir hata oluştu: ' . $e->getMessage());
}

function handleAddToCart($input) {
    $productId = (int)($input['product_id'] ?? 0);
    $quantity = max(1, (int)($input['quantity'] ?? 1));
    
    if ($productId <= 0) {
        jsonResponse(false, 'Geçersiz ürün.');
        return;
    }
    
    $product = db()->fetch("SELECT id, name, stock, price FROM products WHERE id = ? AND is_active = 1", [$productId]);
    if (!$product) {
        jsonResponse(false, 'Ürün bulunamadı.');
        return;
    }
    
    if ($product['stock'] < $quantity) {
        jsonResponse(false, 'Yeterli stok bulunmuyor. Stok: ' . $product['stock']);
        return;
    }
    
    if (isLoggedIn()) {
        $existing = db()->fetch("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?", [$_SESSION['user_id'], $productId]);
        
        if ($existing) {
            $newQty = min($existing['quantity'] + $quantity, $product['stock'], CART_MAX_QUANTITY);
            db()->update('cart', ['quantity' => $newQty], 'id = ?', [$existing['id']]);
        } else {
            db()->insert('cart', [
                'user_id' => $_SESSION['user_id'],
                'product_id' => $productId,
                'quantity' => min($quantity, $product['stock'], CART_MAX_QUANTITY)
            ]);
        }
    } else {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $productId) {
                $item['quantity'] = min($item['quantity'] + $quantity, $product['stock'], CART_MAX_QUANTITY);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id' => $productId,
                'quantity' => min($quantity, $product['stock'], CART_MAX_QUANTITY)
            ];
        }
    }
    
    jsonResponse(true, sanitize($product['name']) . ' sepete eklendi!', ['cart_count' => getCartCount()]);
}

function handleUpdateCart($input) {
    $productId = (int)($input['product_id'] ?? 0);
    $quantity = (int)($input['quantity'] ?? 0);
    
    if ($quantity <= 0) {
        handleRemoveFromCart($input);
        return;
    }
    
    $product = db()->fetch("SELECT stock FROM products WHERE id = ?", [$productId]);
    if (!$product) {
        jsonResponse(false, 'Ürün bulunamadı.');
        return;
    }
    
    $quantity = min($quantity, $product['stock'], CART_MAX_QUANTITY);
    
    if (isLoggedIn()) {
        db()->update('cart', ['quantity' => $quantity], 'user_id = ? AND product_id = ?', [$_SESSION['user_id'], $productId]);
    } else {
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['product_id'] == $productId) {
                    $item['quantity'] = $quantity;
                    break;
                }
            }
        }
    }
    
    jsonResponse(true, 'Miktar güncellendi.', ['cart_count' => getCartCount()]);
}

function handleRemoveFromCart($input) {
    $productId = (int)($input['product_id'] ?? 0);
    
    if (isLoggedIn()) {
        db()->delete('cart', 'user_id = ? AND product_id = ?', [$_SESSION['user_id'], $productId]);
    } else {
        if (isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], fn($item) => $item['product_id'] != $productId));
        }
    }
    
    jsonResponse(true, 'Ürün sepetten kaldırıldı.', ['cart_count' => getCartCount()]);
}

function handleGetCart() {
    $items = getCartItems();
    $total = getCartTotal();
    $deliveryFee = $total >= FREE_DELIVERY_AMOUNT ? 0 : DEFAULT_DELIVERY_FEE;
    
    jsonResponse(true, '', [
        'items' => $items,
        'subtotal' => $total,
        'delivery_fee' => $deliveryFee,
        'grand_total' => $total + $deliveryFee,
        'cart_count' => getCartCount(),
        'free_delivery_remaining' => max(0, FREE_DELIVERY_AMOUNT - $total)
    ]);
}

function handleApplyCoupon($input) {
    $code = trim($input['coupon_code'] ?? '');
    
    if (empty($code)) {
        jsonResponse(false, 'Kupon kodu giriniz.');
        return;
    }
    
    $coupon = db()->fetch(
        "SELECT * FROM coupons WHERE code = ? AND is_active = 1 
         AND (starts_at IS NULL OR starts_at <= NOW()) 
         AND (expires_at IS NULL OR expires_at >= NOW())
         AND (usage_limit IS NULL OR used_count < usage_limit)",
        [$code]
    );
    
    if (!$coupon) {
        jsonResponse(false, 'Geçersiz veya süresi dolmuş kupon kodu.');
        return;
    }
    
    $cartTotal = getCartTotal();
    if ($cartTotal < $coupon['min_order_amount']) {
        jsonResponse(false, 'Bu kupon için minimum sipariş tutarı: ' . formatPrice($coupon['min_order_amount']));
        return;
    }
    
    $discount = $coupon['type'] === 'percentage' 
        ? ($cartTotal * $coupon['value'] / 100) 
        : $coupon['value'];
    
    if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
        $discount = $coupon['max_discount'];
    }
    
    $_SESSION['coupon'] = [
        'id' => $coupon['id'],
        'code' => $coupon['code'],
        'discount' => $discount,
        'type' => $coupon['type'],
        'value' => $coupon['value']
    ];
    
    jsonResponse(true, 'Kupon uygulandı! İndirim: ' . formatPrice($discount), ['discount' => $discount]);
}

function jsonResponse($success, $message = '', $data = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}
