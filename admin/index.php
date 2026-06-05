<?php
/**
 * Salim Hırdavat - Admin Dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// İstatistikler
$todayOrders = db()->count('orders', 'DATE(created_at) = CURDATE()');
$todayRevenue = db()->fetch("SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid'")['total'];
$totalProducts = db()->count('products', 'is_active = 1');
$totalCustomers = db()->count('users', "role = 'customer'");
$pendingOrders = db()->count('orders', "status = 'pending'");
$lowStockProducts = db()->count('products', 'stock <= min_stock AND stock > 0 AND is_active = 1');
$outOfStockProducts = db()->count('products', 'stock = 0 AND is_active = 1');

// Son siparişler
$recentOrders = db()->fetchAll(
    "SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) as customer_name, u.phone as customer_phone
     FROM orders o 
     LEFT JOIN users u ON o.user_id = u.id 
     ORDER BY o.created_at DESC LIMIT 10"
);

// Düşük stok ürünler
$lowStockItems = db()->fetchAll(
    "SELECT p.name, p.sku, p.stock, p.min_stock, c.name as category_name
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.stock <= p.min_stock AND p.is_active = 1 
     ORDER BY p.stock ASC LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</head>
<body class="admin-body">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <a href="<?= SITE_URL ?>/admin/">
                <div class="logo-icon" style="width: 36px; height: 36px; background: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                    <i class="fas fa-tools"></i>
                </div>
                <span class="sidebar-brand">Salim Hırdavat</span>
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <a href="<?= SITE_URL ?>/admin/" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="<?= SITE_URL ?>/admin/products.php"><i class="fas fa-box"></i> Ürünler</a>
            <a href="<?= SITE_URL ?>/admin/product-add.php"><i class="fas fa-plus-circle"></i> Ürün Ekle</a>
            <a href="<?= SITE_URL ?>/admin/import-csv.php"><i class="fas fa-file-csv"></i> CSV Import</a>
            <a href="<?= SITE_URL ?>/admin/categories.php"><i class="fas fa-list"></i> Kategoriler</a>
            <a href="<?= SITE_URL ?>/admin/orders.php"><i class="fas fa-shopping-bag"></i> Siparişler 
                <?php if ($pendingOrders > 0): ?><span class="nav-badge"><?= $pendingOrders ?></span><?php endif; ?>
            </a>
            <a href="<?= SITE_URL ?>/admin/couriers.php"><i class="fas fa-motorcycle"></i> Kuryeler</a>
            <a href="<?= SITE_URL ?>/admin/customers.php"><i class="fas fa-users"></i> Müşteriler</a>
            <a href="<?= SITE_URL ?>/admin/invoices.php"><i class="fas fa-file-invoice"></i> E-Fatura</a>
            <a href="<?= SITE_URL ?>/admin/reports.php"><i class="fas fa-chart-bar"></i> Raporlar</a>
            <a href="<?= SITE_URL ?>/admin/settings.php"><i class="fas fa-cog"></i> Ayarlar</a>
            <div class="sidebar-divider"></div>
            <a href="<?= SITE_URL ?>" target="_blank"><i class="fas fa-external-link-alt"></i> Siteyi Gör</a>
            <a href="<?= SITE_URL ?>/pages/login.php?logout=1"><i class="fas fa-sign-out-alt"></i> Çıkış</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="admin-main">
        <!-- Top Bar -->
        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h2 class="page-title">Dashboard</h2>
            <div class="topbar-right">
                <span style="font-size: 14px; color: var(--gray-500);">
                    <i class="fas fa-user"></i> <?= sanitize($_SESSION['user_name'] ?? 'Admin') ?>
                </span>
            </div>
        </header>

        <div class="admin-content">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(255, 107, 0, 0.1); color: #FF6B00;">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $todayOrders ?></span>
                        <span class="stat-label">Bugünkü Siparişler</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28A745;">
                        <i class="fas fa-turkish-lira-sign"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value"><?= formatPrice($todayRevenue) ?></span>
                        <span class="stat-label">Bugünkü Gelir</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(23, 162, 184, 0.1); color: #17A2B8;">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value"><?= number_format($totalProducts) ?></span>
                        <span class="stat-label">Toplam Ürün</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(108, 117, 125, 0.1); color: #6C757D;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value"><?= number_format($totalCustomers) ?></span>
                        <span class="stat-label">Toplam Müşteri</span>
                    </div>
                </div>
            </div>

            <!-- Alert Cards -->
            <?php if ($pendingOrders > 0): ?>
            <div class="admin-alert warning">
                <i class="fas fa-clock"></i>
                <strong><?= $pendingOrders ?> bekleyen sipariş</strong> onayınızı bekliyor.
                <a href="<?= SITE_URL ?>/admin/orders.php?status=pending">Görüntüle →</a>
            </div>
            <?php endif; ?>

            <?php if ($lowStockProducts > 0 || $outOfStockProducts > 0): ?>
            <div class="admin-alert danger">
                <i class="fas fa-exclamation-triangle"></i>
                <strong><?= $lowStockProducts ?> ürün düşük stokta</strong>, <strong><?= $outOfStockProducts ?> ürün stokta yok</strong>.
                <a href="<?= SITE_URL ?>/admin/products.php?stock=low">Görüntüle →</a>
            </div>
            <?php endif; ?>

            <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px;">
                <!-- Recent Orders -->
                <div class="admin-card">
                    <div class="card-header">
                        <h3><i class="fas fa-shopping-bag"></i> Son Siparişler</h3>
                        <a href="<?= SITE_URL ?>/admin/orders.php" class="btn btn-sm btn-outline">Tümü</a>
                    </div>
                    <div class="card-body">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Sipariş No</th>
                                    <th>Müşteri</th>
                                    <th>Tutar</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><strong>#<?= $order['order_number'] ?></strong></td>
                                    <td><?= sanitize($order['customer_name'] ?? 'Misafir') ?></td>
                                    <td><?= formatPrice($order['total']) ?></td>
                                    <td><span class="<?= getOrderStatusClass($order['status']) ?>"><?= getOrderStatusText($order['status']) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentOrders)): ?>
                                <tr><td colspan="4" style="text-align: center; color: var(--gray-400); padding: 24px;">Henüz sipariş yok</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Low Stock -->
                <div class="admin-card">
                    <div class="card-header">
                        <h3><i class="fas fa-exclamation-triangle" style="color: var(--warning);"></i> Düşük Stok Uyarısı</h3>
                    </div>
                    <div class="card-body">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Ürün</th>
                                    <th>Kategori</th>
                                    <th>Stok</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockItems as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= sanitize(truncate($item['name'], 30)) ?></strong>
                                        <?php if ($item['sku']): ?>
                                        <br><small style="color: var(--gray-400);"><?= $item['sku'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= sanitize($item['category_name'] ?? '-') ?></td>
                                    <td>
                                        <span style="color: <?= $item['stock'] == 0 ? 'var(--danger)' : 'var(--warning)' ?>; font-weight: 700;">
                                            <?= $item['stock'] ?> / <?= $item['min_stock'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($lowStockItems)): ?>
                                <tr><td colspan="3" style="text-align: center; color: var(--gray-400); padding: 24px;">Tüm stoklar yeterli 👍</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="admin-card" style="margin-top: 24px;">
                <div class="card-header">
                    <h3><i class="fas fa-bolt"></i> Hızlı İşlemler</h3>
                </div>
                <div class="card-body">
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <a href="<?= SITE_URL ?>/admin/product-add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Ürün Ekle</a>
                        <a href="<?= SITE_URL ?>/admin/import-csv.php" class="btn btn-success"><i class="fas fa-file-csv"></i> CSV Import</a>
                        <a href="<?= SITE_URL ?>/admin/orders.php" class="btn btn-outline-dark"><i class="fas fa-shopping-bag"></i> Siparişler</a>
                        <a href="<?= SITE_URL ?>/admin/categories.php" class="btn btn-outline-dark"><i class="fas fa-list"></i> Kategoriler</a>
                        <a href="<?= SITE_URL ?>/admin/reports.php" class="btn btn-outline-dark"><i class="fas fa-chart-bar"></i> Raporlar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
