<?php
/**
 * Salim Hırdavat - Admin Ürün Listesi
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// Filtreler
$search = get('search');
$categoryId = (int)get('category');
$stockFilter = get('stock');
$statusFilter = get('status');
$page = max(1, (int)get('page', 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Silme işlemi
if (isset($_GET['delete']) && validateCSRFToken($_GET['token'] ?? '')) {
    $id = (int)$_GET['delete'];
    db()->delete('products', 'id = ?', [$id]);
    db()->delete('product_images', 'product_id = ?', [$id]);
    db()->delete('product_attributes', 'product_id = ?', [$id]);
    flashMessage('success', 'Ürün silindi.');
    redirect(SITE_URL . '/admin/products.php');
}

// Toplu işlem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $ids = $_POST['selected'] ?? [];
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        switch ($_POST['bulk_action']) {
            case 'delete':
                db()->query("DELETE FROM products WHERE id IN ($placeholders)", $ids);
                flashMessage('success', count($ids) . ' ürün silindi.');
                break;
            case 'activate':
                db()->query("UPDATE products SET is_active = 1 WHERE id IN ($placeholders)", $ids);
                flashMessage('success', count($ids) . ' ürün aktifleştirildi.');
                break;
            case 'deactivate':
                db()->query("UPDATE products SET is_active = 0 WHERE id IN ($placeholders)", $ids);
                flashMessage('success', count($ids) . ' ürün pasifleştirildi.');
                break;
        }
        redirect(SITE_URL . '/admin/products.php');
    }
}

// Sorgu
$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)';
    $s = "%{$search}%";
    $params = array_merge($params, [$s, $s, $s]);
}
if ($categoryId > 0) {
    $where[] = 'p.category_id = ?';
    $params[] = $categoryId;
}
if ($stockFilter === 'low') {
    $where[] = 'p.stock <= p.min_stock AND p.stock > 0';
} elseif ($stockFilter === 'out') {
    $where[] = 'p.stock = 0';
} elseif ($stockFilter === 'in') {
    $where[] = 'p.stock > p.min_stock';
}
if ($statusFilter === 'active') {
    $where[] = 'p.is_active = 1';
} elseif ($statusFilter === 'inactive') {
    $where[] = 'p.is_active = 0';
}

$whereStr = implode(' AND ', $where);
$total = db()->count('products p', $whereStr, $params);

$products = db()->fetchAll(
    "SELECT p.*, c.name as category_name,
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE {$whereStr} 
     ORDER BY p.id DESC 
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);

$categories = db()->fetchAll("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürünler - Admin - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h2 class="page-title">Ürün Yönetimi</h2>
            <div class="topbar-right">
                <a href="<?= SITE_URL ?>/admin/product-add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Yeni Ürün</a>
                <a href="<?= SITE_URL ?>/admin/import-csv.php" class="btn btn-success btn-sm"><i class="fas fa-file-csv"></i> CSV Import</a>
            </div>
        </header>

        <div class="admin-content">
            <!-- Filters -->
            <div class="admin-card" style="margin-bottom: 20px;">
                <div class="card-body" style="padding: 16px;">
                    <form method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end;">
                        <div style="flex: 1; min-width: 200px;">
                            <input type="text" name="search" class="form-control" placeholder="Ürün adı, SKU veya barkod..." value="<?= sanitize($search) ?>">
                        </div>
                        <select name="category" class="form-control" style="width: auto;">
                            <option value="">Tüm Kategoriler</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="stock" class="form-control" style="width: auto;">
                            <option value="">Tüm Stok</option>
                            <option value="in" <?= $stockFilter === 'in' ? 'selected' : '' ?>>Stokta</option>
                            <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Düşük Stok</option>
                            <option value="out" <?= $stockFilter === 'out' ? 'selected' : '' ?>>Stokta Yok</option>
                        </select>
                        <select name="status" class="form-control" style="width: auto;">
                            <option value="">Tüm Durum</option>
                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Aktif</option>
                            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Pasif</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Ara</button>
                        <a href="products.php" class="btn btn-outline btn-sm">Temizle</a>
                    </form>
                </div>
            </div>

            <div class="admin-card">
                <div class="card-header">
                    <h3><?= number_format($total) ?> ürün</h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <form method="POST" id="bulkForm">
                        <?= csrfField() ?>
                        <div style="padding: 12px 20px; background: var(--light); display: flex; align-items: center; gap: 12px; border-bottom: 1px solid var(--gray-100);">
                            <select name="bulk_action" class="form-control" style="width: auto; padding: 6px 12px; font-size: 13px;">
                                <option value="">Toplu İşlem</option>
                                <option value="activate">Aktifleştir</option>
                                <option value="deactivate">Pasifleştir</option>
                                <option value="delete">Sil</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline" onclick="return confirm('Seçili ürünlere bu işlemi uygulamak istediğinize emin misiniz?')">Uygula</button>
                        </div>

                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"><input type="checkbox" onchange="toggleSelectAll(this)"></th>
                                    <th style="width: 60px;">Görsel</th>
                                    <th>Ürün Adı</th>
                                    <th>SKU</th>
                                    <th>Kategori</th>
                                    <th>Fiyat</th>
                                    <th>Stok</th>
                                    <th>Durum</th>
                                    <th style="width: 120px;">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $p): ?>
                                <tr>
                                    <td><input type="checkbox" name="selected[]" value="<?= $p['id'] ?>"></td>
                                    <td>
                                        <img src="<?= getProductImageUrl($p) ?>" alt="" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid var(--gray-100);">
                                    </td>
                                    <td>
                                        <strong style="font-size: 13px;"><?= sanitize(truncate($p['name'], 50)) ?></strong>
                                        <?php if ($p['is_featured']): ?><span style="color: var(--warning); margin-left: 4px;" title="Öne Çıkan"><i class="fas fa-star"></i></span><?php endif; ?>
                                    </td>
                                    <td style="font-size: 13px; color: var(--gray-500);"><?= $p['sku'] ?: '-' ?></td>
                                    <td style="font-size: 13px;"><?= sanitize($p['category_name'] ?? '-') ?></td>
                                    <td>
                                        <?php if ($p['sale_price'] && $p['sale_price'] < $p['price']): ?>
                                        <del style="font-size: 11px; color: var(--gray-400);"><?= formatPrice($p['price']) ?></del><br>
                                        <strong style="color: var(--danger);"><?= formatPrice($p['sale_price']) ?></strong>
                                        <?php else: ?>
                                        <strong><?= formatPrice($p['price']) ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($p['stock'] <= 0): ?>
                                        <span style="color: var(--danger); font-weight: 700;">0</span>
                                        <?php elseif ($p['stock'] <= $p['min_stock']): ?>
                                        <span style="color: var(--warning); font-weight: 700;"><?= $p['stock'] ?></span>
                                        <?php else: ?>
                                        <span style="color: var(--success); font-weight: 600;"><?= $p['stock'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($p['is_active']): ?>
                                        <span style="color: var(--success); font-size: 12px; font-weight: 600;"><i class="fas fa-circle" style="font-size: 8px;"></i> Aktif</span>
                                        <?php else: ?>
                                        <span style="color: var(--gray-400); font-size: 12px; font-weight: 600;"><i class="fas fa-circle" style="font-size: 8px;"></i> Pasif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 6px;">
                                            <a href="product-edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline" title="Düzenle"><i class="fas fa-edit"></i></a>
                                            <a href="<?= SITE_URL ?>/urun/<?= $p['slug'] ?>" target="_blank" class="btn btn-sm btn-outline" title="Görüntüle"><i class="fas fa-eye"></i></a>
                                            <a href="products.php?delete=<?= $p['id'] ?>&token=<?= $csrfToken ?>" class="btn btn-sm btn-danger" title="Sil" onclick="return confirm('Bu ürünü silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($products)): ?>
                                <tr><td colspan="9" style="text-align: center; padding: 40px; color: var(--gray-400);">Ürün bulunamadı</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>

            <!-- Pagination -->
            <?php
            $totalPages = ceil($total / $perPage);
            if ($totalPages > 1):
                $urlBase = '?page=%d';
                if ($search) $urlBase .= '&search=' . urlencode($search);
                if ($categoryId) $urlBase .= '&category=' . $categoryId;
                if ($stockFilter) $urlBase .= '&stock=' . $stockFilter;
                if ($statusFilter) $urlBase .= '&status=' . $statusFilter;
            ?>
            <div class="admin-pagination">
                <?php if ($page > 1): ?>
                <a href="<?= sprintf($urlBase, $page - 1) ?>"><i class="fas fa-chevron-left"></i></a>
                <?php endif; ?>
                <?php
                $start = max(1, $page - 3);
                $end = min($totalPages, $page + 3);
                for ($i = $start; $i <= $end; $i++):
                ?>
                <a href="<?= sprintf($urlBase, $i) ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <a href="<?= sprintf($urlBase, $page + 1) ?>"><i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
