<?php
/**
 * Product Card Component (reusable)
 * Kullanım: $product değişkeni tanımlı olmalı
 */
$imgUrl = getProductImageUrl($product);
$hasDiscount = !empty($product['sale_price']) && $product['sale_price'] < $product['price'];
$discount = $hasDiscount ? calculateDiscount($product['price'], $product['sale_price']) : 0;
$currentPrice = $hasDiscount ? $product['sale_price'] : $product['price'];
$productUrl = SITE_URL . '/urun/' . $product['slug'];
?>
<div class="product-card" data-product-id="<?= $product['id'] ?>">
    <a href="<?= $productUrl ?>" class="product-card-image">
        <img src="<?= $imgUrl ?>" alt="<?= sanitize($product['name']) ?>" loading="lazy">
        
        <div class="product-card-badges">
            <?php if ($hasDiscount): ?>
            <span class="badge badge-sale">%<?= $discount ?></span>
            <?php endif; ?>
            <?php if (!empty($product['is_new'])): ?>
            <span class="badge badge-new">Yeni</span>
            <?php endif; ?>
            <?php if ($product['stock'] <= 0): ?>
            <span class="badge badge-outofstock">Tükendi</span>
            <?php endif; ?>
        </div>
    </a>
    
    <div class="product-card-actions">
        <button class="product-card-action" onclick="addToWishlist(<?= $product['id'] ?>)" title="Favorilere Ekle">
            <i class="far fa-heart"></i>
        </button>
        <a href="<?= $productUrl ?>" class="product-card-action" title="Detay">
            <i class="fas fa-eye"></i>
        </a>
    </div>
    
    <div class="product-card-body">
        <?php if (!empty($product['category_name'])): ?>
        <div class="product-card-category"><?= sanitize($product['category_name']) ?></div>
        <?php endif; ?>
        
        <a href="<?= $productUrl ?>">
            <h3 class="product-card-title"><?= sanitize($product['name']) ?></h3>
        </a>
        
        <div class="product-card-price">
            <span class="price-current"><?= formatPrice($currentPrice) ?></span>
            <?php if ($hasDiscount): ?>
            <span class="price-old"><?= formatPrice($product['price']) ?></span>
            <span class="price-discount">%<?= $discount ?></span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="product-card-footer">
        <?php if ($product['stock'] > 0): ?>
        <button class="btn btn-primary btn-block" onclick="addToCart(<?= $product['id'] ?>, 1)">
            <i class="fas fa-cart-plus"></i> Sepete Ekle
        </button>
        <?php else: ?>
        <button class="btn btn-secondary btn-block" disabled>
            <i class="fas fa-ban"></i> Stokta Yok
        </button>
        <?php endif; ?>
    </div>
</div>
