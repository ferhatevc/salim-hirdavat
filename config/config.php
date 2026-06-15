<?php
/**
 * Salim Hırdavat - Genel Konfigürasyon
 */

// Oturum başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hata raporlama (production'da kapatın)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');

// Site sabitleri - Railway env veya .env'den okur
define('SITE_NAME', getenv('SITE_NAME') ?: 'Salim Hırdavat');
define('SITE_DESCRIPTION', 'Sivas\'ın En Büyük Hırdavat Mağazası');

// Railway otomatik URL veya custom domain
$railwayUrl = getenv('RAILWAY_PUBLIC_DOMAIN') ? 'https://' . getenv('RAILWAY_PUBLIC_DOMAIN') : '';
define('SITE_URL', getenv('SITE_URL') ?: $railwayUrl ?: 'http://localhost:8080');

define('SITE_EMAIL', 'info@salimhirdavat.com.tr');
define('SITE_PHONE', '(346) 222 34 70');
define('SITE_PHONE2', '(346) 222 34 71');
define('SITE_FAX', '(346) 223 10 82');
define('SITE_WHATSAPP', '903462223470');
define('SITE_ADDRESS', 'Gültepe Mah. 4 Eylül San. Böl. 53-2 Sanayi Cad. No:30');
define('SITE_COMPANY', 'SALİM HIRDAVAT SAN.TİC.LTD.ŞTİ.');
define('SITE_CITY', 'Sivas/Merkez');

// Dosya yolları
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('UPLOADS_URL', SITE_URL . '/uploads');

// Ürün görselleri
define('PRODUCT_IMAGE_PATH', UPLOADS_PATH . '/products');
define('PRODUCT_IMAGE_URL', UPLOADS_URL . '/products');
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('THUMBNAIL_WIDTH', 300);
define('THUMBNAIL_HEIGHT', 300);

// Sayfalama
define('PRODUCTS_PER_PAGE', 24);
define('ADMIN_PRODUCTS_PER_PAGE', 50);
define('ORDERS_PER_PAGE', 20);

// Sepet
define('CART_MAX_QUANTITY', 100);
define('MIN_ORDER_AMOUNT', 100); // ₺

// Teslimat
define('FREE_DELIVERY_AMOUNT', 500); // ₺ üzeri ücretsiz teslimat
define('DEFAULT_DELIVERY_FEE', 30); // ₺

// Para birimi
define('CURRENCY', 'TRY');
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', '₺');
define('TAX_RATE', 20); // KDV %

// Güvenlik
define('CSRF_TOKEN_NAME', 'csrf_token');
define('BCRYPT_COST', 12);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 dakika

// CSV Import
define('CSV_BATCH_SIZE', 1000);
define('CSV_MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB

// Veritabanı bağlantısı
require_once CONFIG_PATH . '/database.php';

/**
 * CSRF Token oluştur
 */
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * CSRF Token doğrula
 */
function validateCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * CSRF Token HTML input field
 */
function csrfField() {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . generateCSRFToken() . '">';
}
