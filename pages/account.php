<?php
/**
 * Salim Hırdavat - Hesabım
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = currentUser();
$tab = get('tab', 'profil');

// Profil güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    db()->update('users', [
        'first_name' => sanitize($_POST['first_name']),
        'last_name' => sanitize($_POST['last_name']),
        'phone' => sanitize($_POST['phone']),
    ], 'id = ?', [$user['id']]);
    flashMessage('success', 'Profil bilgileriniz güncellendi.');
    redirect(SITE_URL . '/hesabim');
}

// Şifre değiştirme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password']) && validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $result = updatePassword($user['id'], $_POST['current_password'], $_POST['new_password']);
    flashMessage($result['success'] ? 'success' : 'danger', $result['message']);
    redirect(SITE_URL . '/hesabim?tab=sifre');
}

// Adres ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address']) && validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    db()->insert('user_addresses', [
        'user_id' => $user['id'],
        'title' => sanitize($_POST['title']),
        'full_name' => sanitize($_POST['full_name']),
        'phone' => sanitize($_POST['phone']),
        'city' => sanitize($_POST['city']),
        'district' => sanitize($_POST['district']),
        'address_line' => sanitize($_POST['address_line']),
        'is_default' => isset($_POST['is_default']) ? 1 : 0,
    ]);
    flashMessage('success', 'Adres eklendi.');
    redirect(SITE_URL . '/hesabim?tab=adresler');
}

// Adres silme
if (isset($_GET['delete_address'])) {
    db()->delete('user_addresses', 'id = ? AND user_id = ?', [(int)$_GET['delete_address'], $user['id']]);
    flashMessage('success', 'Adres silindi.');
    redirect(SITE_URL . '/hesabim?tab=adresler');
}

$addresses = db()->fetchAll("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC", [$user['id']]);
$recentOrders = db()->fetchAll("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$user['id']]);
$wishlistItems = db()->fetchAll(
    "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
     FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = ? ORDER BY w.created_at DESC",
    [$user['id']]
);

$pageTitle = 'Hesabım - ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<?= renderBreadcrumb([['name' => 'Hesabım']]) ?>

<section class="section" style="padding-top: var(--space-xl);">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-3">
                <div style="background: var(--white); border-radius: var(--radius-lg); box-shadow: var(--shadow-card); overflow: hidden;">
                    <div style="padding: var(--space-lg); background: linear-gradient(135deg, var(--dark), var(--dark-light)); color: white; text-align: center;">
                        <div style="width: 64px; height: 64px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-sm); font-size: 24px; font-weight: 700;">
                            <?= mb_strtoupper(mb_substr($user['first_name'], 0, 1, 'UTF-8'), 'UTF-8') ?>
                        </div>
                        <h4 style="color: white; margin-bottom: 4px;"><?= sanitize($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                        <p style="font-size: 13px; color: var(--gray-400);"><?= sanitize($user['email']) ?></p>
                    </div>
                    <nav style="padding: 8px 0;">
                        <a href="?tab=profil" style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: <?= $tab === 'profil' ? 'var(--primary)' : 'var(--gray-600)' ?>; font-weight: <?= $tab === 'profil' ? '600' : '400' ?>; text-decoration: none;">
                            <i class="fas fa-user" style="width: 20px;"></i> Profil Bilgileri
                        </a>
                        <a href="<?= SITE_URL ?>/siparislerim" style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: var(--gray-600); text-decoration: none;">
                            <i class="fas fa-box" style="width: 20px;"></i> Siparişlerim
                        </a>
                        <a href="?tab=adresler" style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: <?= $tab === 'adresler' ? 'var(--primary)' : 'var(--gray-600)' ?>; font-weight: <?= $tab === 'adresler' ? '600' : '400' ?>; text-decoration: none;">
                            <i class="fas fa-map-marker-alt" style="width: 20px;"></i> Adreslerim
                        </a>
                        <a href="?tab=favoriler" style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: <?= $tab === 'favoriler' ? 'var(--primary)' : 'var(--gray-600)' ?>; font-weight: <?= $tab === 'favoriler' ? '600' : '400' ?>; text-decoration: none;">
                            <i class="fas fa-heart" style="width: 20px;"></i> Favorilerim
                        </a>
                        <a href="?tab=sifre" style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: <?= $tab === 'sifre' ? 'var(--primary)' : 'var(--gray-600)' ?>; font-weight: <?= $tab === 'sifre' ? '600' : '400' ?>; text-decoration: none;">
                            <i class="fas fa-lock" style="width: 20px;"></i> Şifre Değiştir
                        </a>
                        <div style="border-top: 1px solid var(--gray-100); margin: 8px 0;"></div>
                        <a href="<?= SITE_URL ?>/pages/login.php?logout=1" style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: var(--danger); text-decoration: none;">
                            <i class="fas fa-sign-out-alt" style="width: 20px;"></i> Çıkış Yap
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Content -->
            <div class="col-9">
                <?php if ($tab === 'profil'): ?>
                <div style="background: var(--white); border-radius: var(--radius-lg); padding: var(--space-xl); box-shadow: var(--shadow-card);">
                    <h2 style="margin-bottom: var(--space-lg);">Profil Bilgileri</h2>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="update_profile" value="1">
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Ad</label>
                                    <input type="text" name="first_name" class="form-control" value="<?= sanitize($user['first_name']) ?>" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Soyad</label>
                                    <input type="text" name="last_name" class="form-control" value="<?= sanitize($user['last_name']) ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">E-posta</label>
                            <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" disabled>
                            <div class="form-hint">E-posta değiştirmek için destek ile iletişime geçin.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Telefon</label>
                            <input type="tel" name="phone" class="form-control" value="<?= sanitize($user['phone']) ?>">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Kaydet</button>
                    </form>
                </div>

                <?php elseif ($tab === 'adresler'): ?>
                <div style="background: var(--white); border-radius: var(--radius-lg); padding: var(--space-xl); box-shadow: var(--shadow-card); margin-bottom: var(--space-lg);">
                    <h2 style="margin-bottom: var(--space-lg);">Adreslerim</h2>
                    
                    <?php if (!empty($addresses)): ?>
                    <div class="grid grid-2" style="gap: var(--space-md); margin-bottom: var(--space-xl);">
                        <?php foreach ($addresses as $addr): ?>
                        <div style="border: 2px solid <?= $addr['is_default'] ? 'var(--primary)' : 'var(--gray-200)' ?>; border-radius: var(--radius-md); padding: var(--space-md);">
                            <div class="flex-between mb-2">
                                <strong><?= sanitize($addr['title']) ?></strong>
                                <?php if ($addr['is_default']): ?>
                                <span style="font-size: 11px; background: var(--primary-bg); color: var(--primary); padding: 2px 8px; border-radius: 20px; font-weight: 600;">Varsayılan</span>
                                <?php endif; ?>
                            </div>
                            <p style="font-size: var(--text-sm); color: var(--gray-500); line-height: 1.6; margin-bottom: var(--space-sm);">
                                <?= sanitize($addr['full_name']) ?><br>
                                <?= sanitize($addr['address_line']) ?><br>
                                <?= sanitize($addr['district']) ?> / <?= sanitize($addr['city']) ?><br>
                                <?= sanitize($addr['phone']) ?>
                            </p>
                            <a href="?tab=adresler&delete_address=<?= $addr['id'] ?>" class="btn btn-sm btn-outline" onclick="return confirm('Bu adresi silmek istediğinize emin misiniz?')">
                                <i class="fas fa-trash"></i> Sil
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <h3 style="margin-bottom: var(--space-md);">Yeni Adres Ekle</h3>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="add_address" value="1">
                        <div class="row">
                            <div class="col-6"><div class="form-group"><label class="form-label">Adres Başlığı *</label><input type="text" name="title" class="form-control" placeholder="Ev, İş vb." required></div></div>
                            <div class="col-6"><div class="form-group"><label class="form-label">Ad Soyad *</label><input type="text" name="full_name" class="form-control" required></div></div>
                        </div>
                        <div class="form-group"><label class="form-label">Telefon *</label><input type="tel" name="phone" class="form-control" required></div>
                        <div class="row">
                            <div class="col-6"><div class="form-group"><label class="form-label">İl *</label><input type="text" name="city" class="form-control" value="Sivas" required></div></div>
                            <div class="col-6"><div class="form-group"><label class="form-label">İlçe *</label><input type="text" name="district" class="form-control" required></div></div>
                        </div>
                        <div class="form-group"><label class="form-label">Adres *</label><textarea name="address_line" class="form-control" rows="2" required></textarea></div>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: var(--space-md); cursor: pointer;"><input type="checkbox" name="is_default" value="1"> Varsayılan adres olarak ayarla</label>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Adres Ekle</button>
                    </form>
                </div>

                <?php elseif ($tab === 'favoriler'): ?>
                <div style="background: var(--white); border-radius: var(--radius-lg); padding: var(--space-xl); box-shadow: var(--shadow-card);">
                    <h2 style="margin-bottom: var(--space-lg);">Favorilerim</h2>
                    <?php if (empty($wishlistItems)): ?>
                    <div class="empty-cart">
                        <i class="far fa-heart"></i>
                        <h3>Favori listeniz boş</h3>
                        <p class="text-muted">Beğendiğiniz ürünleri favorilere ekleyin.</p>
                        <a href="<?= SITE_URL ?>/urunler" class="btn btn-primary">Ürünleri Keşfet</a>
                    </div>
                    <?php else: ?>
                    <div class="product-grid">
                        <?php foreach ($wishlistItems as $product): ?>
                        <?php include __DIR__ . '/../includes/product-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php elseif ($tab === 'sifre'): ?>
                <div style="background: var(--white); border-radius: var(--radius-lg); padding: var(--space-xl); box-shadow: var(--shadow-card); max-width: 500px;">
                    <h2 style="margin-bottom: var(--space-lg);">Şifre Değiştir</h2>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="change_password" value="1">
                        <div class="form-group"><label class="form-label">Mevcut Şifre</label><input type="password" name="current_password" class="form-control" required></div>
                        <div class="form-group"><label class="form-label">Yeni Şifre</label><input type="password" name="new_password" class="form-control" required minlength="6"></div>
                        <div class="form-group"><label class="form-label">Yeni Şifre Tekrar</label><input type="password" name="new_password_confirm" class="form-control" required></div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-lock"></i> Şifreyi Değiştir</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
