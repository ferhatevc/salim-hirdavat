<?php
/**
 * Salim Hırdavat - Yardımcı Fonksiyonlar
 */

/**
 * Fiyat formatlama (Türk Lirası)
 */
function formatPrice($price) {
    return number_format((float)$price, 2, ',', '.') . ' ' . CURRENCY_SYMBOL;
}

/**
 * Tarih formatlama (Türkçe)
 */
function formatDate($date, $format = 'd.m.Y H:i') {
    if (empty($date)) return '-';
    $dt = new DateTime($date);
    return $dt->format($format);
}

/**
 * Türkçe karakter destekli slug oluştur
 */
function slugify($text) {
    $tr = ['ç'=>'c','ğ'=>'g','ı'=>'i','ö'=>'o','ş'=>'s','ü'=>'u',
           'Ç'=>'c','Ğ'=>'g','İ'=>'i','Ö'=>'o','Ş'=>'s','Ü'=>'u'];
    $text = strtr($text, $tr);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Metni belirli uzunlukta kes
 */
function truncate($text, $length = 100) {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length, 'UTF-8') . '...';
}

/**
 * XSS koruması
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Yönlendirme
 */
function redirect($url) {
    header("Location: {$url}");
    exit;
}

/**
 * Kullanıcı giriş yapmış mı?
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Mevcut kullanıcı bilgisi
 */
function currentUser() {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $user = db()->fetch("SELECT * FROM users WHERE id = ? AND is_active = 1", [$_SESSION['user_id']]);
    }
    return $user;
}

/**
 * Admin mi?
 */
function isAdmin() {
    $user = currentUser();
    return $user && $user['role'] === 'admin';
}

/**
 * Sepet ürün sayısı
 */
function getCartCount() {
    if (isLoggedIn()) {
        return db()->count('cart', 'user_id = ?', [$_SESSION['user_id']]);
    }
    return isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
}

/**
 * Sepet toplam tutarı
 */
function getCartTotal() {
    if (isLoggedIn()) {
        $result = db()->fetch(
            "SELECT SUM(c.quantity * COALESCE(p.sale_price, p.price)) as total 
             FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?",
            [$_SESSION['user_id']]
        );
        return $result['total'] ?? 0;
    }
    
    $total = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $product = db()->fetch("SELECT price, sale_price FROM products WHERE id = ?", [$item['product_id']]);
            if ($product) {
                $price = $product['sale_price'] ?? $product['price'];
                $total += $price * $item['quantity'];
            }
        }
    }
    return $total;
}

/**
 * Sepet ürünlerini getir
 */
function getCartItems() {
    if (isLoggedIn()) {
        return db()->fetchAll(
            "SELECT c.*, p.name, p.slug, p.price, p.sale_price, p.stock, p.unit, p.sku,
                    (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
             FROM cart c 
             JOIN products p ON c.product_id = p.id 
             WHERE c.user_id = ? 
             ORDER BY c.created_at DESC",
            [$_SESSION['user_id']]
        );
    }
    
    $items = [];
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $product = db()->fetch(
                "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
                 FROM products p WHERE p.id = ?",
                [$item['product_id']]
            );
            if ($product) {
                $product['quantity'] = $item['quantity'];
                $items[] = $product;
            }
        }
    }
    return $items;
}

/**
 * Tüm aktif kategorileri getir
 */
function getCategories() {
    return db()->fetchAll(
        "SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_active = 1) as product_count 
         FROM categories c WHERE c.is_active = 1 ORDER BY c.sort_order, c.name"
    );
}

/**
 * Kategori ağacı oluştur
 */
function getCategoryTree($parentId = null) {
    $categories = getCategories();
    return buildTree($categories, $parentId);
}

function buildTree($categories, $parentId = null) {
    $tree = [];
    foreach ($categories as $cat) {
        if ($cat['parent_id'] == $parentId) {
            $children = buildTree($categories, $cat['id']);
            if ($children) {
                $cat['children'] = $children;
            }
            $tree[] = $cat;
        }
    }
    return $tree;
}

/**
 * Site ayarı getir
 */
function getSettings($key, $default = null) {
    static $settings = null;
    if ($settings === null) {
        $rows = db()->fetchAll("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings[$key] ?? $default;
}

/**
 * Görsel yükleme
 */
function uploadImage($file, $path = null) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Dosya yükleme hatası'];
    }
    
    if ($file['size'] > MAX_IMAGE_SIZE) {
        return ['success' => false, 'message' => 'Dosya boyutu çok büyük (max 5MB)'];
    }
    
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'message' => 'Geçersiz dosya formatı (JPG, PNG, WebP)'];
    }
    
    $path = $path ?? PRODUCT_IMAGE_PATH;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $filepath = $path . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $filepath];
    }
    
    return ['success' => false, 'message' => 'Dosya kaydedilemedi'];
}

/**
 * Thumbnail oluştur
 */
function generateThumbnail($source, $dest, $width = 300, $height = 300) {
    $info = getimagesize($source);
    if (!$info) return false;
    
    switch ($info[2]) {
        case IMAGETYPE_JPEG: $img = imagecreatefromjpeg($source); break;
        case IMAGETYPE_PNG: $img = imagecreatefrompng($source); break;
        case IMAGETYPE_WEBP: $img = imagecreatefromwebp($source); break;
        default: return false;
    }
    
    $srcW = $info[0];
    $srcH = $info[1];
    $ratio = min($width / $srcW, $height / $srcH);
    $newW = (int)($srcW * $ratio);
    $newH = (int)($srcH * $ratio);
    
    $thumb = imagecreatetruecolor($newW, $newH);
    
    if ($info[2] === IMAGETYPE_PNG || $info[2] === IMAGETYPE_WEBP) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }
    
    imagecopyresampled($thumb, $img, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
    
    switch ($info[2]) {
        case IMAGETYPE_JPEG: imagejpeg($thumb, $dest, 85); break;
        case IMAGETYPE_PNG: imagepng($thumb, $dest, 8); break;
        case IMAGETYPE_WEBP: imagewebp($thumb, $dest, 85); break;
    }
    
    imagedestroy($img);
    imagedestroy($thumb);
    return true;
}

/**
 * Flash mesaj oluştur
 */
function flashMessage($type, $message) {
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

/**
 * Flash mesajları göster
 */
function showFlashMessages() {
    if (empty($_SESSION['flash_messages'])) return '';
    
    $html = '';
    foreach ($_SESSION['flash_messages'] as $msg) {
        $icon = match($msg['type']) {
            'success' => 'fa-check-circle',
            'danger' => 'fa-exclamation-circle',
            'warning' => 'fa-exclamation-triangle',
            'info' => 'fa-info-circle',
            default => 'fa-info-circle'
        };
        $html .= '<div class="alert alert-' . $msg['type'] . '">';
        $html .= '<i class="fas ' . $icon . '"></i>';
        $html .= '<span>' . sanitize($msg['message']) . '</span>';
        $html .= '<button class="alert-close" onclick="this.parentElement.remove()">&times;</button>';
        $html .= '</div>';
    }
    
    unset($_SESSION['flash_messages']);
    return $html;
}

/**
 * Sayfalama HTML oluştur
 */
function getPagination($total, $perPage, $currentPage, $urlPattern = '?page=%d') {
    $totalPages = ceil($total / $perPage);
    if ($totalPages <= 1) return '';
    
    $html = '<div class="pagination">';
    
    // Önceki
    if ($currentPage > 1) {
        $html .= '<a href="' . sprintf($urlPattern, $currentPage - 1) . '"><i class="fas fa-chevron-left"></i></a>';
    } else {
        $html .= '<span class="disabled"><i class="fas fa-chevron-left"></i></span>';
    }
    
    // Sayfa numaraları
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $html .= '<a href="' . sprintf($urlPattern, 1) . '">1</a>';
        if ($start > 2) $html .= '<span class="dots">...</span>';
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $currentPage) {
            $html .= '<span class="active">' . $i . '</span>';
        } else {
            $html .= '<a href="' . sprintf($urlPattern, $i) . '">' . $i . '</a>';
        }
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<span class="dots">...</span>';
        $html .= '<a href="' . sprintf($urlPattern, $totalPages) . '">' . $totalPages . '</a>';
    }
    
    // Sonraki
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . sprintf($urlPattern, $currentPage + 1) . '"><i class="fas fa-chevron-right"></i></a>';
    } else {
        $html .= '<span class="disabled"><i class="fas fa-chevron-right"></i></span>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Ürün görsel URL'si
 */
function getProductImageUrl($product) {
    if (!empty($product['image'])) {
        return PRODUCT_IMAGE_URL . '/' . $product['image'];
    }
    return SITE_URL . '/assets/images/no-image.png';
}

/**
 * İndirim yüzdesini hesapla
 */
function calculateDiscount($price, $salePrice) {
    if (!$salePrice || $salePrice >= $price) return 0;
    return round((($price - $salePrice) / $price) * 100);
}

/**
 * Breadcrumb oluştur
 */
function getBreadcrumb($category) {
    $breadcrumb = [];
    $current = $category;
    
    while ($current) {
        array_unshift($breadcrumb, $current);
        if ($current['parent_id']) {
            $current = db()->fetch("SELECT * FROM categories WHERE id = ?", [$current['parent_id']]);
        } else {
            break;
        }
    }
    
    return $breadcrumb;
}

/**
 * Breadcrumb HTML oluştur
 */
function renderBreadcrumb($items) {
    $html = '<div class="breadcrumb"><div class="container"><ul class="breadcrumb-list">';
    $html .= '<li><a href="' . SITE_URL . '"><i class="fas fa-home"></i> Ana Sayfa</a></li>';
    
    foreach ($items as $i => $item) {
        $html .= '<li><span class="separator"><i class="fas fa-chevron-right"></i></span>';
        if ($i < count($items) - 1) {
            $url = isset($item['slug']) ? SITE_URL . '/kategori/' . $item['slug'] : '#';
            $html .= '<a href="' . $url . '">' . sanitize($item['name']) . '</a>';
        } else {
            $html .= '<span class="active">' . sanitize($item['name']) . '</span>';
        }
        $html .= '</li>';
    }
    
    $html .= '</ul></div></div>';
    return $html;
}

/**
 * Sipariş durumu Türkçe metin
 */
function getOrderStatusText($status) {
    return match($status) {
        'pending' => 'Beklemede',
        'confirmed' => 'Onaylandı',
        'preparing' => 'Hazırlanıyor',
        'courier_assigned' => 'Kurye Atandı',
        'on_delivery' => 'Teslimat Yolunda',
        'delivered' => 'Teslim Edildi',
        'cancelled' => 'İptal Edildi',
        'returned' => 'İade Edildi',
        default => $status
    };
}

/**
 * Sipariş durumu CSS sınıfı
 */
function getOrderStatusClass($status) {
    return match($status) {
        'pending' => 'status-pending',
        'confirmed' => 'status-confirmed',
        'preparing' => 'status-preparing',
        'courier_assigned' => 'status-courier',
        'on_delivery' => 'status-delivery',
        'delivered' => 'status-delivered',
        'cancelled', 'returned' => 'status-cancelled',
        default => 'status-pending'
    };
}

/**
 * Ödeme durumu Türkçe metin
 */
function getPaymentStatusText($status) {
    return match($status) {
        'pending' => 'Bekliyor',
        'paid' => 'Ödendi',
        'failed' => 'Başarısız',
        'refunded' => 'İade Edildi',
        default => $status
    };
}

/**
 * Güvenli POST verisi al
 */
function post($key, $default = '') {
    return isset($_POST[$key]) ? sanitize($_POST[$key]) : $default;
}

/**
 * Güvenli GET verisi al
 */
function get($key, $default = '') {
    return isset($_GET[$key]) ? sanitize($_GET[$key]) : $default;
}

/**
 * Geçerli sayfa URL'si
 */
function currentUrl() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
           . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}
