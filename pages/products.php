<?php
/**
 * Salim Hırdavat - Ürün Listesi
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Filtreler
$categorySlug = get('category');
$brandId = (int)get('brand');
$minPrice = (float)get('min_price');
$maxPrice = (float)get('max_price');
$sort = get('sort', 'newest');
$page = max(1, (int)get('page', 1));
$featured = get('featured');
$sale = get('sale');
$search = get('q');

$perPage = PRODUCTS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Sorgu oluştur
$where = ['p.is_active = 1'];
$params = [];

if ($categorySlug) {
    $cat = db()->fetch("SELECT id, name, slug FROM categories WHERE slug = ?", [$categorySlug]);
    if ($cat) {
        $where[] = '(p.category_id = ? OR p.category_id IN (SELECT id FROM categories WHERE parent_id = ?))';
        $params[] = $cat['id'];
        $params[] = $cat['id'];
    }
}

if ($brandId > 0) {
    $where[] = 'p.brand_id = ?';
    $params[] = $brandId;
}

if ($minPrice > 0) {
    $where[] = 'COALESCE(p.sale_price, p.price) >= ?';
    $params[] = $minPrice;
}

if ($maxPrice > 0) {
    $where[] = 'COALESCE(p.sale_price, p.price) <= ?';
    $params[] = $maxPrice;
}

if ($featured) {
    $where[] = 'p.is_featured = 1';
}

if ($sale) {
    $where[] = 'p.sale_price IS NOT NULL AND p.sale_price > 0 AND p.sale_price < p.price';
}

if ($search) {
    $where[] = 'MATCH(p.name, p.description, p.short_description, p.sku, p.barcode) AGAINST(? IN BOOLEAN MODE)';
    $params[] = $search . '*';
}

$whereStr = implode(' AND ', $where);

// Sıralama
$orderBy = match($sort) {
    'price_asc' => 'COALESCE(p.sale_price, p.price) ASC',
    'price_desc' => 'COALESCE(p.sale_price, p.price) DESC',
    'name_asc' => 'p.name ASC',
    'name_desc' => 'p.name DESC',
    'popular' => 'p.sale_count DESC',
    default => 'p.created_at DESC'
};

// Toplam sayı
$total = db()->count('products p', $whereStr, $params);

// Ürünler
$products = db()->fetchAll(
    "SELECT p.*, c.name as category_name, b.name as brand_name,
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     LEFT JOIN brands b ON p.brand_id = b.id
     WHERE {$whereStr} 
     ORDER BY {$orderBy} 
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);

// Sidebar verileri
$allCategories = getCategoryTree();
$brands = db()->fetchAll("SELECT b.*, COUNT(p.id) as product_count FROM brands b JOIN products p ON p.brand_id = b.id WHERE p.is_active = 1 GROUP BY b.id ORDER BY b.name LIMIT 30");

// Sayfa başlığı
$pageTitle = 'Ürünler';
if (isset($cat)) $pageTitle = $cat['name'];
if ($search) $pageTitle = '"' . $search . '" araması';
if ($featured) $pageTitle = 'Öne Çıkan Ürünler';
if ($sale) $pageTitle = 'İndirimli Ürünler';
$pageTitle .= ' - ' . SITE_NAME;

// Breadcrumb
$breadcrumbItems = [];
if (isset($cat)) {
    $breadcrumbItems = getBreadcrumb($cat);
} else {
    $breadcrumbItems[] = ['name' => $featured ? 'Öne Çıkan Ürünler' : ($sale ? 'İndirimli Ürünler' : 'Tüm Ürünler')];
}

require_once __DIR__ . '/../includes/header.php';
?>

<?= renderBreadcrumb($breadcrumbItems) ?>

<section class="section page-with-sidebar" style="padding-top: var(--space-xl);">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-3">
                <button class="filter-toggle-mobile" id="filterToggle">
                    <i class="fas fa-filter"></i> Filtreleri Göster
                </button>
                
                <aside class="sidebar" id="filterSidebar">
                    <!-- Kategoriler -->
                    <div class="filter-card">
                        <h4><i class="fas fa-list" style="color: var(--primary); margin-right: 8px;"></i> Kategoriler</h4>
                        <ul class="filter-list">
                            <li>
                                <a href="<?= SITE_URL ?>/urunler" class="<?= !$categorySlug ? 'active' : '' ?>">
                                    Tüm Ürünler
                                    <span class="count"><?= db()->count('products', 'is_active = 1') ?></span>
                                </a>
                            </li>
                            <?php foreach ($allCategories as $mainCat): ?>
                            <li>
                                <a href="<?= SITE_URL ?>/kategori/<?= $mainCat['slug'] ?>" 
                                   class="<?= $categorySlug == $mainCat['slug'] ? 'active' : '' ?>">
                                    <?= sanitize($mainCat['name']) ?>
                                    <span class="count"><?= $mainCat['product_count'] ?? 0 ?></span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Fiyat Aralığı -->
                    <div class="filter-card">
                        <h4><i class="fas fa-tag" style="color: var(--primary); margin-right: 8px;"></i> Fiyat Aralığı</h4>
                        <form method="GET" action="">
                            <?php if ($categorySlug): ?>
                            <input type="hidden" name="category" value="<?= $categorySlug ?>">
                            <?php endif; ?>
                            <input type="hidden" name="sort" value="<?= $sort ?>">
                            <div class="flex gap-sm mb-2">
                                <input type="number" name="min_price" class="form-control" placeholder="Min ₺" value="<?= $minPrice ?: '' ?>" style="padding: 8px;">
                                <input type="number" name="max_price" class="form-control" placeholder="Max ₺" value="<?= $maxPrice ?: '' ?>" style="padding: 8px;">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm btn-block">Filtrele</button>
                        </form>
                    </div>

                    <!-- Hızlı Filtreler -->
                    <div class="filter-card">
                        <h4><i class="fas fa-filter" style="color: var(--primary); margin-right: 8px;"></i> Hızlı Filtreler</h4>
                        <ul class="filter-list">
                            <li><a href="<?= SITE_URL ?>/urunler?sale=1" class="<?= $sale ? 'active' : '' ?>"><i class="fas fa-percent" style="margin-right: 6px;"></i> İndirimli Ürünler</a></li>
                            <li><a href="<?= SITE_URL ?>/urunler?featured=1" class="<?= $featured ? 'active' : '' ?>"><i class="fas fa-star" style="margin-right: 6px;"></i> Öne Çıkanlar</a></li>
                            <li><a href="<?= SITE_URL ?>/urunler?sort=newest"><i class="fas fa-clock" style="margin-right: 6px;"></i> Yeni Eklenenler</a></li>
                            <li><a href="<?= SITE_URL ?>/urunler?sort=popular"><i class="fas fa-fire" style="margin-right: 6px;"></i> Çok Satanlar</a></li>
                        </ul>
                    </div>
                </aside>
            </div>

            <!-- Products -->
            <div class="col-9">
                <!-- Toolbar -->
                <div class="flex-between mb-3" style="flex-wrap: wrap; gap: 16px;">
                    <div>
                        <h1 style="font-size: var(--text-2xl); margin-bottom: 4px;">
                            <?php if ($search): ?>
                                "<?= sanitize($search) ?>" için sonuçlar
                            <?php elseif (isset($cat)): ?>
                                <?= sanitize($cat['name']) ?>
                            <?php elseif ($featured): ?>
                                Öne Çıkan Ürünler
                            <?php elseif ($sale): ?>
                                İndirimli Ürünler
                            <?php else: ?>
                                Tüm Ürünler
                            <?php endif; ?>
                        </h1>
                        <p class="text-muted mb-0" style="font-size: var(--text-sm);">
                            <?= number_format($total) ?> ürün bulundu
                        </p>
                    </div>
                    
                    <div class="flex gap-md" style="align-items: center;">
                        <select class="form-control" style="width: auto; padding: 8px 40px 8px 12px;" onchange="window.location.href=this.value" id="sortSelect">
                            <option value="?sort=newest<?= $categorySlug ? '&category='.$categorySlug : '' ?>" <?= $sort == 'newest' ? 'selected' : '' ?>>En Yeni</option>
                            <option value="?sort=price_asc<?= $categorySlug ? '&category='.$categorySlug : '' ?>" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Fiyat: Düşükten Yükseğe</option>
                            <option value="?sort=price_desc<?= $categorySlug ? '&category='.$categorySlug : '' ?>" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Fiyat: Yüksekten Düşüğe</option>
                            <option value="?sort=popular<?= $categorySlug ? '&category='.$categorySlug : '' ?>" <?= $sort == 'popular' ? 'selected' : '' ?>>Çok Satanlar</option>
                            <option value="?sort=name_asc<?= $categorySlug ? '&category='.$categorySlug : '' ?>" <?= $sort == 'name_asc' ? 'selected' : '' ?>>A-Z</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($products)): ?>
                <!-- Empty State -->
                <div class="empty-cart">
                    <i class="fas fa-search"></i>
                    <h3>Ürün bulunamadı</h3>
                    <p class="text-muted">Arama kriterlerinize uygun ürün bulunamadı. Filtreleri değiştirmeyi deneyin.</p>
                    <a href="<?= SITE_URL ?>/urunler" class="btn btn-primary">Tüm Ürünleri Gör</a>
                </div>
                <?php else: ?>
                <!-- Product Grid -->
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                    <?php include __DIR__ . '/../includes/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php
                $urlPattern = '?page=%d&sort=' . $sort;
                if ($categorySlug) $urlPattern .= '&category=' . $categorySlug;
                if ($minPrice) $urlPattern .= '&min_price=' . $minPrice;
                if ($maxPrice) $urlPattern .= '&max_price=' . $maxPrice;
                if ($featured) $urlPattern .= '&featured=1';
                if ($sale) $urlPattern .= '&sale=1';
                if ($search) $urlPattern .= '&q=' . urlencode($search);
                echo getPagination($total, $perPage, $page, $urlPattern);
                ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
