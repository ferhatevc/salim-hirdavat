<?php
/**
 * Salim Hırdavat - Kayıt Sayfası
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) { redirect(SITE_URL . '/hesabim'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Güvenlik hatası.';
    } else {
        $result = register([
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? ''
        ]);
        
        if ($result['success']) {
            flashMessage('success', 'Hesabınız başarıyla oluşturuldu. Hoş geldiniz!');
            redirect(SITE_URL . '/hesabim');
        } else {
            $errors = $result['errors'];
        }
    }
}

$pageTitle = 'Kayıt Ol - ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section" style="min-height: 60vh; display: flex; align-items: center;">
    <div class="container">
        <div style="max-width: 520px; margin: 0 auto;">
            <div style="background: var(--white); border-radius: var(--radius-xl); padding: var(--space-3xl); box-shadow: var(--shadow-lg);">
                <div style="text-align: center; margin-bottom: var(--space-xl);">
                    <div style="width: 64px; height: 64px; background: var(--primary-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-md);">
                        <i class="fas fa-user-plus" style="font-size: 24px; color: var(--primary);"></i>
                    </div>
                    <h1 style="font-size: var(--text-2xl);">Kayıt Ol</h1>
                    <p class="text-muted">Hesap oluşturun ve alışverişe başlayın</p>
                </div>

                <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?>
                </div>
                <?php endforeach; ?>

                <form method="POST" action="">
                    <?= csrfField() ?>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Ad <span class="required">*</span></label>
                                <input type="text" name="first_name" class="form-control" value="<?= post('first_name') ?>" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Soyad <span class="required">*</span></label>
                                <input type="text" name="last_name" class="form-control" value="<?= post('last_name') ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">E-posta Adresi <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="ornek@email.com" value="<?= post('email') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Telefon <span class="required">*</span></label>
                        <input type="tel" name="phone" class="form-control" placeholder="05XX XXX XX XX" value="<?= post('phone') ?>" required pattern="0[5][0-9]{9}">
                        <div class="form-hint">Örnek: 05321234567</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Şifre <span class="required">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="En az 6 karakter" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Şifre Tekrar <span class="required">*</span></label>
                        <input type="password" name="password_confirm" class="form-control" placeholder="Şifrenizi tekrar giriniz" required>
                    </div>

                    <div class="form-group">
                        <label class="form-check">
                            <input type="checkbox" required>
                            <span style="font-size: var(--text-sm);">
                                <a href="#" style="color: var(--primary);">Kullanım şartlarını</a> ve 
                                <a href="#" style="color: var(--primary);">gizlilik politikasını</a> okudum, kabul ediyorum.
                            </span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        <i class="fas fa-user-plus"></i> Kayıt Ol
                    </button>
                </form>

                <div style="text-align: center; margin-top: var(--space-xl); padding-top: var(--space-lg); border-top: 1px solid var(--gray-100);">
                    <p style="font-size: var(--text-sm); color: var(--gray-500);">
                        Zaten hesabınız var mı? 
                        <a href="<?= SITE_URL ?>/giris" style="color: var(--primary); font-weight: 600;">Giriş Yap</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
