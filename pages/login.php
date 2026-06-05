<?php
/**
 * Salim Hırdavat - Giriş Sayfası
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Çıkış
if (isset($_GET['logout'])) {
    logout();
    flashMessage('success', 'Başarıyla çıkış yapıldı.');
    redirect(SITE_URL);
}

// Zaten giriş yapmışsa
if (isLoggedIn()) {
    redirect(SITE_URL . '/hesabim');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Güvenlik hatası. Lütfen tekrar deneyin.';
    } else {
        $result = login(post('email'), $_POST['password'] ?? '');
        
        if ($result['success']) {
            $redirect = $_SESSION['redirect_after_login'] ?? SITE_URL . '/hesabim';
            unset($_SESSION['redirect_after_login']);
            
            if ($result['user']['role'] === 'admin') {
                redirect(SITE_URL . '/admin/');
            }
            
            flashMessage('success', 'Hoş geldiniz, ' . sanitize($result['user']['first_name']) . '!');
            redirect($redirect);
        } else {
            $errors[] = $result['message'];
        }
    }
}

$pageTitle = 'Giriş Yap - ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section" style="min-height: 60vh; display: flex; align-items: center;">
    <div class="container">
        <div style="max-width: 480px; margin: 0 auto;">
            <div style="background: var(--white); border-radius: var(--radius-xl); padding: var(--space-3xl); box-shadow: var(--shadow-lg);">
                <div style="text-align: center; margin-bottom: var(--space-xl);">
                    <div style="width: 64px; height: 64px; background: var(--primary-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-md);">
                        <i class="fas fa-sign-in-alt" style="font-size: 24px; color: var(--primary);"></i>
                    </div>
                    <h1 style="font-size: var(--text-2xl);">Giriş Yap</h1>
                    <p class="text-muted">Hesabınıza giriş yapın</p>
                </div>

                <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?>
                </div>
                <?php endforeach; ?>

                <form method="POST" action="">
                    <?= csrfField() ?>
                    
                    <div class="form-group">
                        <label class="form-label">E-posta Adresi</label>
                        <input type="email" name="email" class="form-control" placeholder="ornek@email.com" value="<?= post('email') ?>" required autofocus>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Şifre</label>
                        <input type="password" name="password" class="form-control" placeholder="Şifrenizi giriniz" required>
                    </div>

                    <div class="flex-between mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="remember"> <span style="font-size: var(--text-sm);">Beni hatırla</span>
                        </label>
                        <a href="#" style="font-size: var(--text-sm); color: var(--primary);">Şifremi unuttum</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        <i class="fas fa-sign-in-alt"></i> Giriş Yap
                    </button>
                </form>

                <div style="text-align: center; margin-top: var(--space-xl); padding-top: var(--space-lg); border-top: 1px solid var(--gray-100);">
                    <p style="font-size: var(--text-sm); color: var(--gray-500);">
                        Hesabınız yok mu? 
                        <a href="<?= SITE_URL ?>/kayit" style="color: var(--primary); font-weight: 600;">Kayıt Ol</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
