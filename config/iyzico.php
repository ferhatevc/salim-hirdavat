<?php
/**
 * Salim Hırdavat - iyzico Ödeme Konfigürasyonu
 */

// iyzico API Anahtarları
// Sandbox (Test) ortamı - Canlıya geçerken production bilgileri ile değiştirin
define('IYZICO_API_KEY', 'sandbox-api-key');          // iyzico panelinden alın
define('IYZICO_SECRET_KEY', 'sandbox-secret-key');     // iyzico panelinden alın
define('IYZICO_BASE_URL', 'https://sandbox-api.iyzipay.com'); // Canlı: https://api.iyzipay.com

// Ödeme ayarları
define('IYZICO_CALLBACK_URL', SITE_URL . '/pages/payment-callback.php');
define('PAYMENT_CURRENCY', 'TRY');
define('INSTALLMENT_ENABLED', true);
define('MAX_INSTALLMENTS', 12);

// Kapıda ödeme
define('DOOR_PAYMENT_ENABLED', true);
define('DOOR_PAYMENT_CASH', true);     // Kapıda nakit
define('DOOR_PAYMENT_CARD', true);     // Kapıda kredi kartı

/**
 * iyzico Options nesnesi oluştur
 */
function getIyzicoOptions() {
    $options = new \Iyzipay\Options();
    $options->setApiKey(IYZICO_API_KEY);
    $options->setSecretKey(IYZICO_SECRET_KEY);
    $options->setBaseUrl(IYZICO_BASE_URL);
    return $options;
}

/**
 * Benzersiz sipariş conversation ID oluştur
 */
function generateConversationId() {
    return 'SH' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

/**
 * Sipariş numarası oluştur
 */
function generateOrderNumber() {
    return 'SH' . date('ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}
