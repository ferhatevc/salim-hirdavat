<?php
/**
 * Admin Sidebar Include
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$pendingOrders = db()->count('orders', "status = 'pending'");
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <a href="<?= SITE_URL ?>/admin/">
            <div class="logo-icon" style="width: 36px; height: 36px; background: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px;">
                <i class="fas fa-tools"></i>
            </div>
            <span class="sidebar-brand">Salim Hırdavat</span>
        </a>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= SITE_URL ?>/admin/" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="<?= SITE_URL ?>/admin/products.php" class="<?= $currentPage === 'products.php' ? 'active' : '' ?>"><i class="fas fa-box"></i> Ürünler</a>
        <a href="<?= SITE_URL ?>/admin/product-add.php" class="<?= in_array($currentPage, ['product-add.php','product-edit.php']) ? 'active' : '' ?>"><i class="fas fa-plus-circle"></i> Ürün Ekle</a>
        <a href="<?= SITE_URL ?>/admin/import-csv.php" class="<?= $currentPage === 'import-csv.php' ? 'active' : '' ?>"><i class="fas fa-file-csv"></i> CSV Import</a>
        <a href="<?= SITE_URL ?>/admin/categories.php" class="<?= $currentPage === 'categories.php' ? 'active' : '' ?>"><i class="fas fa-list"></i> Kategoriler</a>
        <a href="<?= SITE_URL ?>/admin/orders.php" class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>"><i class="fas fa-shopping-bag"></i> Siparişler
            <?php if ($pendingOrders > 0): ?><span class="nav-badge"><?= $pendingOrders ?></span><?php endif; ?>
        </a>
        <a href="<?= SITE_URL ?>/admin/couriers.php" class="<?= $currentPage === 'couriers.php' ? 'active' : '' ?>"><i class="fas fa-motorcycle"></i> Kuryeler</a>
        <a href="<?= SITE_URL ?>/admin/customers.php" class="<?= $currentPage === 'customers.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> Müşteriler</a>
        <a href="<?= SITE_URL ?>/admin/invoices.php" class="<?= $currentPage === 'invoices.php' ? 'active' : '' ?>"><i class="fas fa-file-invoice"></i> E-Fatura</a>
        <a href="<?= SITE_URL ?>/admin/reports.php" class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>"><i class="fas fa-chart-bar"></i> Raporlar</a>
        <a href="<?= SITE_URL ?>/admin/settings.php" class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>"><i class="fas fa-cog"></i> Ayarlar</a>
        <div class="sidebar-divider"></div>
        <a href="<?= SITE_URL ?>" target="_blank"><i class="fas fa-external-link-alt"></i> Siteyi Gör</a>
        <a href="<?= SITE_URL ?>/pages/login.php?logout=1"><i class="fas fa-sign-out-alt"></i> Çıkış</a>
    </nav>
</aside>
