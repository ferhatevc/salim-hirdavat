<?php
/**
 * Salim Hırdavat - Router
 * PHP Built-in Server için URL yönlendirme
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

// Statik dosyalar (CSS, JS, resim vs.)
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|webp|svg|ico|woff|woff2|ttf|eot|map)$/i', $uri)) {
    return false; // PHP built-in server statik dosyayı kendisi serve eder
}

// Route tanımları
$routes = [
    // Ana Sayfa
    '' => 'index.php',
    '/' => 'index.php',
    
    // Ürünler
    '/urunler' => 'pages/products.php',
    '/products' => 'pages/products.php',
    
    // Arama
    '/ara' => 'pages/products.php',
    '/search' => 'pages/products.php',
    
    // İletişim
    '/iletisim' => 'pages/contact.php',
    '/contact' => 'pages/contact.php',
    
    // Hakkımızda
    '/hakkimizda' => 'pages/about.php',
    '/about' => 'pages/about.php',
    
    // Sepet
    '/sepet' => 'pages/cart.php',
    '/cart' => 'pages/cart.php',
    
    // Giriş / Kayıt
    '/giris' => 'pages/login.php',
    '/login' => 'pages/login.php',
    '/kayit' => 'pages/register.php',
    '/register' => 'pages/register.php',
    
    // Hesap
    '/hesabim' => 'pages/account.php',
    '/account' => 'pages/account.php',
    
    // Siparişler
    '/siparislerim' => 'pages/orders.php',
    '/orders' => 'pages/orders.php',
    
    // Ödeme
    '/odeme' => 'pages/checkout.php',
    '/checkout' => 'pages/checkout.php',
];

// Direkt eşleşme
if (isset($routes[$uri])) {
    require __DIR__ . '/' . $routes[$uri];
    exit;
}

// Kategori: /kategori/{slug}
if (preg_match('#^/kategori/([a-zA-Z0-9\-]+)$#', $uri, $matches)) {
    $_GET['category'] = $matches[1];
    require __DIR__ . '/pages/products.php';
    exit;
}

// Kategori sayfalama: /kategori/{slug}/sayfa/{page}
if (preg_match('#^/kategori/([a-zA-Z0-9\-]+)/sayfa/(\d+)$#', $uri, $matches)) {
    $_GET['category'] = $matches[1];
    $_GET['page'] = $matches[2];
    require __DIR__ . '/pages/products.php';
    exit;
}

// Ürün detay: /urun/{slug}
if (preg_match('#^/urun/([a-zA-Z0-9\-]+)$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/pages/product-detail.php';
    exit;
}

// Marka: /marka/{slug}
if (preg_match('#^/marka/([a-zA-Z0-9\-]+)$#', $uri, $matches)) {
    $_GET['brand'] = $matches[1];
    require __DIR__ . '/pages/products.php';
    exit;
}

// API endpoints
if (preg_match('#^/api/#', $uri)) {
    $apiFile = __DIR__ . $uri . '.php';
    if (file_exists($apiFile)) {
        require $apiFile;
        exit;
    }
}

// Admin panel
if (preg_match('#^/admin#', $uri)) {
    $adminPath = $uri;
    if ($adminPath === '/admin' || $adminPath === '/admin/') {
        require __DIR__ . '/admin/index.php';
        exit;
    }
    
    // /admin/xxx -> admin/xxx.php
    $file = __DIR__ . $adminPath . '.php';
    if (file_exists($file)) {
        require $file;
        exit;
    }
    
    // /admin/xxx -> admin/xxx/index.php
    $file = __DIR__ . $adminPath . '/index.php';
    if (file_exists($file)) {
        require $file;
        exit;
    }
}

// PHP dosyası direkt erişim (fallback)
$directFile = __DIR__ . $uri;
if (file_exists($directFile) && pathinfo($directFile, PATHINFO_EXTENSION) === 'php') {
    require $directFile;
    exit;
}

$directFile = __DIR__ . $uri . '.php';
if (file_exists($directFile)) {
    require $directFile;
    exit;
}

// 404
http_response_code(404);
echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><title>404 - Sayfa Bulunamadı</title>';
echo '<style>body{font-family:Inter,sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:#f8f9fa;color:#1A2332}';
echo '.container{text-align:center}.code{font-size:120px;font-weight:800;color:#FF6B00;line-height:1}.msg{font-size:24px;margin:20px 0}';
echo '.btn{display:inline-block;padding:12px 32px;background:#FF6B00;color:white;text-decoration:none;border-radius:8px;font-weight:600;margin-top:10px}</style></head>';
echo '<body><div class="container"><div class="code">404</div><p class="msg">Sayfa Bulunamadı</p>';
echo '<p>Aradığınız sayfa mevcut değil veya taşınmış olabilir.</p>';
echo '<a href="/" class="btn">Ana Sayfaya Dön</a></div></body></html>';
