<?php
/**
 * Salim Hırdavat - Admin Ayarlar
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $settings = $_POST['settings'] ?? [];
    foreach ($settings as $key => $value) {
        $exists = db()->count('settings', 'setting_key = ?', [$key]);
        if ($exists) {
            db()->update('settings', ['setting_value' => sanitize($value)], 'setting_key = ?', [$key]);
        } else {
            db()->insert('settings', ['setting_key' => $key, 'setting_value' => sanitize($value)]);
        }
    }
    flashMessage('success', 'Ayarlar kaydedildi.');
    redirect(SITE_URL . '/admin/settings.php');
}

$s = function($key, $default = '') { return getSettings($key, $default); };
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayarlar - Admin - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h2 class="page-title"><i class="fas fa-cog" style="color: var(--primary);"></i> Site Ayarları</h2>
        </header>

        <div class="admin-content">
            <?= showFlashMessages() ?>

            <form method="POST">
                <?= csrfField() ?>

                <!-- Genel Ayarlar -->
                <div class="admin-card" style="margin-bottom: 24px;">
                    <div class="card-header"><h3><i class="fas fa-globe"></i> Genel Ayarlar</h3></div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label class="form-label">Site Adı</label>
                                <input type="text" name="settings[site_name]" class="form-control" value="<?= $s('site_name', 'Salim Hırdavat') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Site Açıklaması</label>
                                <input type="text" name="settings[site_description]" class="form-control" value="<?= $s('site_description', 'Sivas Hırdavat Mağazası') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Telefon</label>
                                <input type="tel" name="settings[site_phone]" class="form-control" value="<?= $s('site_phone', SITE_PHONE) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">E-posta</label>
                                <input type="email" name="settings[site_email]" class="form-control" value="<?= $s('site_email', SITE_EMAIL) ?>">
                            </div>
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label">Adres</label>
                                <input type="text" name="settings[site_address]" class="form-control" value="<?= $s('site_address', SITE_ADDRESS) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">WhatsApp Numarası</label>
                                <input type="text" name="settings[site_whatsapp]" class="form-control" value="<?= $s('site_whatsapp', SITE_WHATSAPP) ?>" placeholder="905XXXXXXXXX">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ödeme Ayarları -->
                <div class="admin-card" style="margin-bottom: 24px;">
                    <div class="card-header"><h3><i class="fas fa-credit-card"></i> Ödeme Ayarları</h3></div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label class="form-label">iyzico API Key</label>
                                <input type="text" name="settings[iyzico_api_key]" class="form-control" value="<?= $s('iyzico_api_key') ?>" placeholder="sandbox veya live API key">
                            </div>
                            <div class="form-group">
                                <label class="form-label">iyzico Secret Key</label>
                                <input type="password" name="settings[iyzico_secret_key]" class="form-control" value="<?= $s('iyzico_secret_key') ?>">
                            </div>
                        </div>
                        <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; cursor: pointer;">
                            <input type="checkbox" name="settings[iyzico_sandbox]" value="1" <?= $s('iyzico_sandbox', '1') ? 'checked' : '' ?>> Sandbox (Test) Modu
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; cursor: pointer;">
                            <input type="checkbox" name="settings[payment_cash]" value="1" <?= $s('payment_cash', '1') ? 'checked' : '' ?>> Kapıda Nakit Ödeme
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="settings[payment_card_door]" value="1" <?= $s('payment_card_door', '1') ? 'checked' : '' ?>> Kapıda Kart ile Ödeme
                        </label>
                    </div>
                </div>

                <!-- Teslimat Ayarları -->
                <div class="admin-card" style="margin-bottom: 24px;">
                    <div class="card-header"><h3><i class="fas fa-truck"></i> Teslimat Ayarları</h3></div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label class="form-label">Varsayılan Teslimat Ücreti (₺)</label>
                                <input type="number" name="settings[default_delivery_fee]" class="form-control" value="<?= $s('default_delivery_fee', '25') ?>" step="0.01">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Ücretsiz Teslimat Limiti (₺)</label>
                                <input type="number" name="settings[free_delivery_amount]" class="form-control" value="<?= $s('free_delivery_amount', '500') ?>" step="0.01">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Minimum Sipariş Tutarı (₺)</label>
                                <input type="number" name="settings[min_order_amount]" class="form-control" value="<?= $s('min_order_amount', '100') ?>" step="0.01">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fatura Ayarları -->
                <div class="admin-card" style="margin-bottom: 24px;">
                    <div class="card-header"><h3><i class="fas fa-file-invoice"></i> E-Fatura Ayarları</h3></div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label class="form-label">Fatura Öneki</label>
                                <input type="text" name="settings[invoice_prefix]" class="form-control" value="<?= $s('invoice_prefix', 'SH') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Sonraki Fatura No</label>
                                <input type="number" name="settings[invoice_next_number]" class="form-control" value="<?= $s('invoice_next_number', '1') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">KDV Oranı (%)</label>
                                <input type="number" name="settings[tax_rate]" class="form-control" value="<?= $s('tax_rate', '20') ?>" step="1">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label class="form-label">Vergi Dairesi</label>
                                <input type="text" name="settings[tax_office]" class="form-control" value="<?= $s('tax_office') ?>" placeholder="Sivas Vergi Dairesi">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Vergi Numarası</label>
                                <input type="text" name="settings[tax_number]" class="form-control" value="<?= $s('tax_number') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sosyal Medya -->
                <div class="admin-card" style="margin-bottom: 24px;">
                    <div class="card-header"><h3><i class="fas fa-share-alt"></i> Sosyal Medya</h3></div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label class="form-label"><i class="fab fa-facebook" style="color: #1877F2;"></i> Facebook</label>
                                <input type="url" name="settings[social_facebook]" class="form-control" value="<?= $s('social_facebook') ?>" placeholder="https://facebook.com/...">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fab fa-instagram" style="color: #E4405F;"></i> Instagram</label>
                                <input type="url" name="settings[social_instagram]" class="form-control" value="<?= $s('social_instagram') ?>" placeholder="https://instagram.com/...">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fab fa-twitter" style="color: #1DA1F2;"></i> Twitter</label>
                                <input type="url" name="settings[social_twitter]" class="form-control" value="<?= $s('social_twitter') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fab fa-youtube" style="color: #FF0000;"></i> YouTube</label>
                                <input type="url" name="settings[social_youtube]" class="form-control" value="<?= $s('social_youtube') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="padding: 14px 40px;">
                    <i class="fas fa-save"></i> Tüm Ayarları Kaydet
                </button>
            </form>
        </div>
    </div>
    <script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
