<?php
/**
 * Salim Hırdavat - Admin Müşteriler
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$search = get('search');
$page = max(1, (int)get('page', 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$where = ["role = 'customer'"];
$params = [];

if ($search) {
    $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $s = "%{$search}%";
    $params = [$s, $s, $s, $s];
}

$whereStr = implode(' AND ', $where);
$total = db()->count('users', $whereStr, $params);

$customers = db()->fetchAll(
    "SELECT u.*, 
            (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
            (SELECT COALESCE(SUM(total), 0) FROM orders WHERE user_id = u.id AND payment_status = 'paid') as total_spent
     FROM users u 
     WHERE {$whereStr} 
     ORDER BY u.created_at DESC 
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteriler - Admin - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h2 class="page-title"><i class="fas fa-users" style="color: var(--primary);"></i> Müşteriler</h2>
        </header>
        <div class="admin-content">
            <div class="admin-card" style="margin-bottom: 20px;">
                <div class="card-body" style="padding: 12px 20px;">
                    <form method="GET" style="display: flex; gap: 12px;">
                        <input type="text" name="search" class="form-control" placeholder="Ad, e-posta veya telefon..." value="<?= sanitize($search) ?>" style="max-width: 400px;">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>
            <div class="admin-card">
                <div class="card-header"><h3><?= number_format($total) ?> müşteri</h3></div>
                <div class="card-body" style="padding: 0; overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr><th>Müşteri</th><th>E-posta</th><th>Telefon</th><th>Sipariş</th><th>Toplam Harcama</th><th>Kayıt Tarihi</th><th>Durum</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $c): ?>
                            <tr>
                                <td><strong><?= sanitize($c['first_name'] . ' ' . $c['last_name']) ?></strong></td>
                                <td style="font-size: 13px;"><?= sanitize($c['email']) ?></td>
                                <td style="font-size: 13px;"><?= $c['phone'] ?></td>
                                <td><strong><?= $c['order_count'] ?></strong></td>
                                <td><strong style="color: var(--primary);"><?= formatPrice($c['total_spent']) ?></strong></td>
                                <td style="font-size: 13px;"><?= formatDate($c['created_at'], 'd.m.Y') ?></td>
                                <td><span style="color: <?= $c['is_active'] ? 'var(--success)' : 'var(--danger)' ?>; font-size: 12px; font-weight: 600;"><i class="fas fa-circle" style="font-size: 8px;"></i> <?= $c['is_active'] ? 'Aktif' : 'Pasif' ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
