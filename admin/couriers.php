<?php
/**
 * Salim Hırdavat - Admin Kurye Yönetimi
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// Ekleme / Düzenleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $id = (int)($_POST['id'] ?? 0);
    $data = [
        'first_name' => sanitize($_POST['first_name'] ?? ''),
        'last_name' => sanitize($_POST['last_name'] ?? ''),
        'phone' => sanitize($_POST['phone'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'vehicle_type' => sanitize($_POST['vehicle_type'] ?? 'motorcycle'),
        'vehicle_plate' => sanitize($_POST['vehicle_plate'] ?? ''),
        'zone' => sanitize($_POST['zone'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'is_available' => isset($_POST['is_available']) ? 1 : 0,
    ];
    
    if ($id > 0) {
        db()->update('couriers', $data, 'id = ?', [$id]);
        flashMessage('success', 'Kurye güncellendi.');
    } else {
        db()->insert('couriers', $data);
        flashMessage('success', 'Kurye eklendi.');
    }
    redirect(SITE_URL . '/admin/couriers.php');
}

// Silme
if (isset($_GET['delete']) && validateCSRFToken($_GET['token'] ?? '')) {
    db()->delete('couriers', 'id = ?', [(int)$_GET['delete']]);
    flashMessage('success', 'Kurye silindi.');
    redirect(SITE_URL . '/admin/couriers.php');
}

$couriers = db()->fetchAll(
    "SELECT cr.*, 
            (SELECT COUNT(*) FROM orders WHERE courier_id = cr.id AND status = 'delivered') as delivered_count,
            (SELECT COUNT(*) FROM orders WHERE courier_id = cr.id AND status IN ('courier_assigned','on_delivery')) as active_deliveries
     FROM couriers cr 
     ORDER BY cr.is_active DESC, cr.first_name"
);

$editCourier = isset($_GET['edit']) ? db()->fetch("SELECT * FROM couriers WHERE id = ?", [(int)$_GET['edit']]) : null;
$csrfToken = generateCSRFToken();

$zones = ['Merkez', 'Hafik', 'Yıldızeli', 'Zara', 'Gemerek', 'Şarkışla', 'Kangal', 'Divriği', 'Gürün', 'Suşehri', 'Koyulhisar', 'İmranlı', 'Akıncılar', 'Doğanşar', 'Gölova', 'Ulaş', 'Altınyayla'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuryeler - Admin - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h2 class="page-title"><i class="fas fa-motorcycle" style="color: var(--primary);"></i> Kurye Yönetimi</h2>
        </header>

        <div class="admin-content">
            <?= showFlashMessages() ?>

            <div style="display: grid; grid-template-columns: 1fr 380px; gap: 24px;">
                <!-- Courier List -->
                <div class="admin-card">
                    <div class="card-header">
                        <h3><?= count($couriers) ?> Kurye</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Kurye</th>
                                    <th>Telefon</th>
                                    <th>Araç</th>
                                    <th>Bölge</th>
                                    <th>Teslimat</th>
                                    <th>Aktif İş</th>
                                    <th>Durum</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($couriers as $c): ?>
                                <tr>
                                    <td><strong style="font-size: 13px;"><?= sanitize($c['first_name'] . ' ' . $c['last_name']) ?></strong></td>
                                    <td style="font-size: 13px;"><a href="tel:<?= $c['phone'] ?>"><?= $c['phone'] ?></a></td>
                                    <td style="font-size: 13px;">
                                        <i class="fas <?= $c['vehicle_type'] === 'motorcycle' ? 'fa-motorcycle' : ($c['vehicle_type'] === 'car' ? 'fa-car' : 'fa-bicycle') ?>"></i>
                                        <?= $c['vehicle_plate'] ?>
                                    </td>
                                    <td style="font-size: 13px;"><?= sanitize($c['zone'] ?? '-') ?></td>
                                    <td style="font-size: 13px; font-weight: 600;"><?= $c['delivered_count'] ?></td>
                                    <td>
                                        <?php if ($c['active_deliveries'] > 0): ?>
                                        <span style="color: var(--warning); font-weight: 700;"><?= $c['active_deliveries'] ?></span>
                                        <?php else: ?>
                                        <span style="color: var(--gray-400);">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($c['is_active'] && $c['is_available']): ?>
                                        <span style="color: var(--success); font-size: 12px; font-weight: 600;"><i class="fas fa-circle" style="font-size: 8px;"></i> Müsait</span>
                                        <?php elseif ($c['is_active']): ?>
                                        <span style="color: var(--warning); font-size: 12px; font-weight: 600;"><i class="fas fa-circle" style="font-size: 8px;"></i> Meşgul</span>
                                        <?php else: ?>
                                        <span style="color: var(--gray-400); font-size: 12px; font-weight: 600;"><i class="fas fa-circle" style="font-size: 8px;"></i> Pasif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 6px;">
                                            <a href="couriers.php?edit=<?= $c['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                                            <a href="couriers.php?delete=<?= $c['id'] ?>&token=<?= $csrfToken ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu kuryeyi silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($couriers)): ?>
                                <tr><td colspan="8" style="text-align: center; padding: 40px; color: var(--gray-400);">Henüz kurye eklenmemiş</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add/Edit Form -->
                <div>
                    <div class="admin-card">
                        <div class="card-header">
                            <h3><?= $editCourier ? 'Kurye Düzenle' : 'Yeni Kurye' ?></h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?= csrfField() ?>
                                <?php if ($editCourier): ?>
                                <input type="hidden" name="id" value="<?= $editCourier['id'] ?>">
                                <?php endif; ?>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div class="form-group">
                                        <label class="form-label">Ad *</label>
                                        <input type="text" name="first_name" class="form-control" value="<?= sanitize($editCourier['first_name'] ?? '') ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Soyad *</label>
                                        <input type="text" name="last_name" class="form-control" value="<?= sanitize($editCourier['last_name'] ?? '') ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Telefon *</label>
                                    <input type="tel" name="phone" class="form-control" value="<?= sanitize($editCourier['phone'] ?? '') ?>" required placeholder="05XX XXX XX XX">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">E-posta</label>
                                    <input type="email" name="email" class="form-control" value="<?= sanitize($editCourier['email'] ?? '') ?>">
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div class="form-group">
                                        <label class="form-label">Araç Tipi</label>
                                        <select name="vehicle_type" class="form-control">
                                            <option value="motorcycle" <?= ($editCourier['vehicle_type'] ?? '') === 'motorcycle' ? 'selected' : '' ?>>🏍️ Motorsiklet</option>
                                            <option value="car" <?= ($editCourier['vehicle_type'] ?? '') === 'car' ? 'selected' : '' ?>>🚗 Araba</option>
                                            <option value="bicycle" <?= ($editCourier['vehicle_type'] ?? '') === 'bicycle' ? 'selected' : '' ?>>🚲 Bisiklet</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Plaka</label>
                                        <input type="text" name="vehicle_plate" class="form-control" value="<?= sanitize($editCourier['vehicle_plate'] ?? '') ?>" placeholder="58 XX 000">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Teslimat Bölgesi</label>
                                    <select name="zone" class="form-control">
                                        <option value="">Bölge Seçin</option>
                                        <?php foreach ($zones as $zone): ?>
                                        <option value="<?= $zone ?>" <?= ($editCourier['zone'] ?? '') === $zone ? 'selected' : '' ?>><?= $zone ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; cursor: pointer;">
                                    <input type="checkbox" name="is_active" value="1" <?= ($editCourier['is_active'] ?? 1) ? 'checked' : '' ?>> Aktif
                                </label>
                                <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; cursor: pointer;">
                                    <input type="checkbox" name="is_available" value="1" <?= ($editCourier['is_available'] ?? 1) ? 'checked' : '' ?>> Müsait (Görev alabilir)
                                </label>
                                
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-save"></i> <?= $editCourier ? 'Güncelle' : 'Kurye Ekle' ?>
                                </button>
                                
                                <?php if ($editCourier): ?>
                                <a href="couriers.php" class="btn btn-outline btn-block" style="margin-top: 8px;">İptal</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
