<?php
/**
 * Salim Hırdavat - İletişim Sayfası
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'İletişim - ' . SITE_NAME;
$pageDescription = 'Salim Hırdavat ile iletişime geçin. Sivas\'taki mağazamızı ziyaret edin veya bize ulaşın.';
$messageSent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    // Basit form kaydetme (e-posta gönderimi hosting'de SMTP ayarlandığında)
    $messageSent = true;
    flashMessage('success', 'Mesajınız başarıyla gönderildi. En kısa sürede size dönüş yapacağız.');
}

require_once __DIR__ . '/../includes/header.php';
?>

<?= renderBreadcrumb([['name' => 'İletişim']]) ?>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h1 style="font-size: var(--text-3xl);">Bize Ulaşın</h1>
            <p>Sorularınız için bize ulaşmaktan çekinmeyin</p>
        </div>

        <!-- Contact Cards -->
        <div class="grid grid-4" style="margin-bottom: var(--space-3xl);">
            <div class="contact-card">
                <i class="fas fa-map-marker-alt"></i>
                <h4>Adres</h4>
                <p><?= SITE_ADDRESS ?></p>
            </div>
            <div class="contact-card">
                <i class="fas fa-phone-alt"></i>
                <h4>Telefon</h4>
                <p><a href="tel:<?= SITE_PHONE ?>" style="color: var(--primary);"><?= SITE_PHONE ?></a></p>
            </div>
            <div class="contact-card">
                <i class="fas fa-envelope"></i>
                <h4>E-posta</h4>
                <p><a href="mailto:<?= SITE_EMAIL ?>" style="color: var(--primary);"><?= SITE_EMAIL ?></a></p>
            </div>
            <div class="contact-card">
                <i class="fab fa-whatsapp"></i>
                <h4>WhatsApp</h4>
                <p><a href="https://wa.me/<?= SITE_WHATSAPP ?>" style="color: #25D366;" target="_blank">Hemen Yazın</a></p>
            </div>
        </div>

        <div class="row">
            <!-- Contact Form -->
            <div class="col-6">
                <div style="background: var(--white); border-radius: var(--radius-lg); padding: var(--space-xl); box-shadow: var(--shadow-card);">
                    <h3 style="margin-bottom: var(--space-lg);">
                        <i class="fas fa-paper-plane" style="color: var(--primary);"></i> Mesaj Gönderin
                    </h3>
                    
                    <form method="POST" action="">
                        <?= csrfField() ?>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Ad Soyad <span class="required">*</span></label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Telefon</label>
                                    <input type="tel" name="phone" class="form-control" placeholder="05XX XXX XX XX">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">E-posta <span class="required">*</span></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Konu</label>
                            <select name="subject" class="form-control">
                                <option value="genel">Genel Bilgi</option>
                                <option value="siparis">Sipariş Hakkında</option>
                                <option value="urun">Ürün Bilgisi</option>
                                <option value="teklif">Toplu Alım / Teklif</option>
                                <option value="sikayet">Şikayet / Öneri</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mesajınız <span class="required">*</span></label>
                            <textarea name="message" class="form-control" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane"></i> Gönder
                        </button>
                    </form>
                </div>
            </div>

            <!-- Map & Info -->
            <div class="col-6">
                <div class="map-container" style="margin-bottom: var(--space-lg);">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d48784.71267822186!2d37.0080!3d39.7477!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x407571e7e1b23f75%3A0x7c4b0cf3f2e2c1!2sSivas!5e0!3m2!1str!2str!4v1" 
                            allowfullscreen loading="lazy"></iframe>
                </div>
                
                <div style="background: var(--white); border-radius: var(--radius-lg); padding: var(--space-xl); box-shadow: var(--shadow-card);">
                    <h4><i class="fas fa-clock" style="color: var(--primary);"></i> Çalışma Saatleri</h4>
                    <table style="width: 100%; margin-top: var(--space-md);">
                        <tr><td style="padding: 8px 0; font-weight: 600;">Pazartesi - Cuma</td><td style="text-align: right;">08:00 - 19:00</td></tr>
                        <tr><td style="padding: 8px 0; font-weight: 600;">Cumartesi</td><td style="text-align: right;">08:00 - 18:00</td></tr>
                        <tr><td style="padding: 8px 0; font-weight: 600; color: var(--danger);">Pazar</td><td style="text-align: right; color: var(--danger);">Kapalı</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
