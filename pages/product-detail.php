<?php
/**
 * Salim Hırdavat - Ürün Detay
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$slug = get('slug');
if (!$slug) { redirect(SITE_URL . '/urunler'); }

$product = db()->fetch(
    "SELECT p.*, c.name as category_name, c.slug as category_slug, b.name as brand_name
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     LEFT JOIN brands b ON p.brand_id = b.id
     WHERE p.slug = ? AND p.is_active = 1",
    [$slug]
);

if (!$product) {
    flashMessage('danger', 'Ürün bulunamadı.');
    redirect(SITE_URL . '/urunler');
}

// Görüntülenme sayısını artır
db()->query("UPDATE products SET view_count = view_count + 1 WHERE id = ?", [$product['id']]);

// Görseller
$images = db()->fetchAll(
    "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order", 
    [$product['id']]
);

// Özellikler
$attributes = db()->fetchAll(
    "SELECT * FROM product_attributes WHERE product_id = ? ORDER BY sort_order",
    [$product['id']]
);

// Benzer ürünler
$relatedProducts = db()->fetchAll(
    "SELECT p.*, c.name as category_name,
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1 
     ORDER BY RAND() LIMIT 4",
    [$product['category_id'], $product['id']]
);

// Fiyat hesaplamaları
$hasDiscount = !empty($product['sale_price']) && $product['sale_price'] < $product['price'];
$currentPrice = $hasDiscount ? $product['sale_price'] : $product['price'];
$discount = $hasDiscount ? calculateDiscount($product['price'], $product['sale_price']) : 0;
$mainImage = !empty($images) ? PRODUCT_IMAGE_URL . '/' . $images[0]['image_path'] : SITE_URL . '/assets/images/no-image.png';

// Breadcrumb
$breadcrumbItems = [];
if ($product['category_id']) {
    $category = db()->fetch("SELECT * FROM categories WHERE id = ?", [$product['category_id']]);
    if ($category) {
        $breadcrumbItems = getBreadcrumb($category);
    }
}
$breadcrumbItems[] = ['name' => $product['name']];

$pageTitle = $product['meta_title'] ?: $product['name'] . ' - ' . SITE_NAME;
$pageDescription = $product['meta_description'] ?: truncate(strip_tags($product['description'] ?: $product['short_description'] ?: $product['name']), 160);

require_once __DIR__ . '/../includes/header.php';
?>

<?= renderBreadcrumb($breadcrumbItems) ?>

<section class="product-detail">
    <div class="container">
        <div class="row">
            <!-- Gallery -->
            <div class="col-6">
                <div class="product-gallery">
                    <div class="product-gallery-main" id="mainImageContainer">
                        <img src="<?= $mainImage ?>" alt="<?= sanitize($product['name']) ?>" id="mainImage">
                        <?php if ($hasDiscount): ?>
                        <div style="position: absolute; top: 16px; left: 16px; z-index: 2;">
                            <span class="badge badge-sale" style="font-size: 14px; padding: 8px 16px;">%<?= $discount ?> İndirim</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($images) > 1): ?>
                    <div class="product-gallery-thumbs">
                        <?php foreach ($images as $i => $img): ?>
                        <div class="product-gallery-thumb <?= $i === 0 ? 'active' : '' ?>" 
                             onclick="changeMainImage(this, '<?= PRODUCT_IMAGE_URL . '/' . $img['image_path'] ?>')">
                            <img src="<?= PRODUCT_IMAGE_URL . '/' . $img['image_path'] ?>" alt="<?= sanitize($product['name']) ?> - <?= $i + 1 ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Info -->
            <div class="col-6">
                <div class="product-info">
                    <div class="product-info-sku">
                        <?php if ($product['sku']): ?>
                        <span>SKU: <strong><?= sanitize($product['sku']) ?></strong></span>
                        <?php endif; ?>
                        <?php if ($product['brand_name']): ?>
                        <span style="margin-left: 16px;">Marka: <strong><?= sanitize($product['brand_name']) ?></strong></span>
                        <?php endif; ?>
                    </div>

                    <h1><?= sanitize($product['name']) ?></h1>

                    <!-- Price -->
                    <div class="product-info-price">
                        <span class="price-current"><?= formatPrice($currentPrice) ?></span>
                        <?php if ($hasDiscount): ?>
                        <span class="price-old"><?= formatPrice($product['price']) ?></span>
                        <span class="price-discount">%<?= $discount ?> İndirim</span>
                        <?php endif; ?>
                    </div>

                    <!-- Stock -->
                    <div class="product-stock <?= $product['stock'] > 10 ? 'in-stock' : ($product['stock'] > 0 ? 'low-stock' : 'out-of-stock') ?>">
                        <i class="fas <?= $product['stock'] > 0 ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                        <?php if ($product['stock'] > 10): ?>
                            Stokta Var
                        <?php elseif ($product['stock'] > 0): ?>
                            Son <?= $product['stock'] ?> <?= $product['unit'] ?> kaldı!
                        <?php else: ?>
                            Stokta Yok
                        <?php endif; ?>
                    </div>

                    <?php if ($product['short_description']): ?>
                    <p style="color: var(--gray-600); margin-bottom: var(--space-lg);">
                        <?= sanitize($product['short_description']) ?>
                    </p>
                    <?php endif; ?>

                    <!-- Quantity & Add to Cart -->
                    <?php if ($product['stock'] > 0): ?>
                    <div class="flex gap-md" style="align-items: end; margin-bottom: var(--space-lg);">
                        <div>
                            <label class="form-label">Miktar (<?= sanitize($product['unit']) ?>)</label>
                            <div class="quantity-selector">
                                <button onclick="updateQty(-1)">−</button>
                                <input type="number" id="productQty" value="1" min="1" max="<?= $product['stock'] ?>">
                                <button onclick="updateQty(1)">+</button>
                            </div>
                        </div>
                    </div>

                    <div class="product-actions">
                        <button class="btn btn-primary btn-lg" onclick="addToCart(<?= $product['id'] ?>, document.getElementById('productQty').value)" style="flex: 1;">
                            <i class="fas fa-cart-plus"></i> Sepete Ekle
                        </button>
                        <button class="btn btn-outline btn-lg btn-icon" onclick="addToWishlist(<?= $product['id'] ?>)" title="Favorilere Ekle">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Bu ürün şu anda stokta bulunmamaktadır. Stok durumu için bizi arayabilirsiniz.
                    </div>
                    <a href="https://wa.me/<?= SITE_WHATSAPP ?>?text=Merhaba, <?= urlencode($product['name']) ?> ürünü hakkında bilgi almak istiyorum." class="btn btn-success btn-lg btn-block" target="_blank">
                        <i class="fab fa-whatsapp"></i> WhatsApp ile Sorun
                    </a>
                    <?php endif; ?>

                    <!-- Product Meta -->
                    <div class="product-meta">
                        <div class="product-meta-item">
                            <i class="fas fa-truck"></i>
                            <strong>Teslimat:</strong>
                            <span>Sivas merkeze aynı gün kurye teslimat</span>
                        </div>
                        <div class="product-meta-item">
                            <i class="fas fa-shield-halved"></i>
                            <strong>Güvenlik:</strong>
                            <span>iyzico güvencesi ile ödeme</span>
                        </div>
                        <div class="product-meta-item">
                            <i class="fas fa-money-bill-wave"></i>
                            <strong>Kapıda Ödeme:</strong>
                            <span>Nakit veya kredi kartı ile kapıda ödeme</span>
                        </div>
                        <?php if ($product['barcode']): ?>
                        <div class="product-meta-item">
                            <i class="fas fa-barcode"></i>
                            <strong>Barkod:</strong>
                            <span><?= sanitize($product['barcode']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Share -->
                    <div class="flex gap-sm" style="align-items: center; margin-top: var(--space-lg);">
                        <span style="font-weight: 600; font-size: var(--text-sm); color: var(--gray-500);">Paylaş:</span>
                        <a href="https://wa.me/?text=<?= urlencode($product['name'] . ' - ' . currentUrl()) ?>" target="_blank" class="btn btn-icon btn-sm" style="background: #25D366; color: white;">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(currentUrl()) ?>" target="_blank" class="btn btn-icon btn-sm" style="background: #1877F2; color: white;">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?text=<?= urlencode($product['name']) ?>&url=<?= urlencode(currentUrl()) ?>" target="_blank" class="btn btn-icon btn-sm" style="background: #1DA1F2; color: white;">
                            <i class="fab fa-twitter"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Tabs -->
        <div class="product-tabs">
            <div class="tab-nav">
                <button class="active" onclick="switchTab(this, 'tabDescription')">Ürün Açıklaması</button>
                <?php if (!empty($attributes)): ?>
                <button onclick="switchTab(this, 'tabSpecs')">Teknik Özellikler</button>
                <?php endif; ?>
                <button onclick="switchTab(this, 'tabDelivery')">Teslimat Bilgisi</button>
            </div>

            <div class="tab-content active" id="tabDescription">
                <?php if ($product['description']): ?>
                    <div class="product-description">
                        <?= nl2br(sanitize($product['description'])) ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Bu ürün için henüz açıklama eklenmemiştir.</p>
                <?php endif; ?>
            </div>

            <?php if (!empty($attributes)): ?>
            <div class="tab-content" id="tabSpecs">
                <table style="width: 100%; max-width: 600px;">
                    <?php foreach ($attributes as $attr): ?>
                    <tr>
                        <td style="padding: 10px 16px; font-weight: 600; color: var(--dark); background: var(--light); width: 40%; border-bottom: 1px solid var(--gray-100);">
                            <?= sanitize($attr['attribute_name']) ?>
                        </td>
                        <td style="padding: 10px 16px; border-bottom: 1px solid var(--gray-100);">
                            <?= sanitize($attr['attribute_value']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endif; ?>

            <div class="tab-content" id="tabDelivery">
                <div class="grid grid-3" style="gap: var(--space-xl);">
                    <div>
                        <h4><i class="fas fa-motorcycle" style="color: var(--primary);"></i> Sivas Merkez</h4>
                        <p>Aynı gün veya 1-3 saat içinde kurye ile teslimat. 200₺ ve üzeri siparişlerde ücretsiz.</p>
                    </div>
                    <div>
                        <h4><i class="fas fa-truck" style="color: var(--primary);"></i> Yakın İlçeler</h4>
                        <p>1-2 iş günü içinde teslimat. Hafik, Yıldızeli, Zara, Gemerek, Şarkışla. 300₺ üzeri ücretsiz.</p>
                    </div>
                    <div>
                        <h4><i class="fas fa-box" style="color: var(--primary);"></i> Uzak İlçeler</h4>
                        <p>2-3 iş günü içinde teslimat. Kangal, Divriği, Gürün, Suşehri ve diğerleri. 500₺ üzeri ücretsiz.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
        <section style="margin-top: var(--space-4xl);">
            <div class="section-header" style="text-align: left;">
                <h2>Benzer Ürünler</h2>
            </div>
            <div class="product-grid">
                <?php foreach ($relatedProducts as $product): ?>
                <?php include __DIR__ . '/../includes/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</section>

<script>
function changeMainImage(thumb, src) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.product-gallery-thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
}

function updateQty(delta) {
    const input = document.getElementById('productQty');
    let val = parseInt(input.value) + delta;
    val = Math.max(1, Math.min(val, parseInt(input.max)));
    input.value = val;
}

function switchTab(btn, tabId) {
    document.querySelectorAll('.tab-nav button').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(tabId).classList.add('active');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
