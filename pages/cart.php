<?php
/**
 * Salim Hırdavat - Sepet Sayfası
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Sepetim - ' . SITE_NAME;
$cartItems = getCartItems();
$cartTotal = getCartTotal();
$deliveryFee = $cartTotal >= FREE_DELIVERY_AMOUNT ? 0 : DEFAULT_DELIVERY_FEE;
$grandTotal = $cartTotal + $deliveryFee;

require_once __DIR__ . '/../includes/header.php';
?>

<?= renderBreadcrumb([['name' => 'Sepetim']]) ?>

<section class="section" style="padding-top: var(--space-xl);">
    <div class="container">
        <h1 style="margin-bottom: var(--space-xl);">
            <i class="fas fa-shopping-cart" style="color: var(--primary);"></i> Sepetim
            <?php if (!empty($cartItems)): ?>
            <span class="text-muted" style="font-size: var(--text-lg); font-weight: 400;">(<?= count($cartItems) ?> ürün)</span>
            <?php endif; ?>
        </h1>

        <?php if (empty($cartItems)): ?>
        <!-- Empty Cart -->
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h3>Sepetiniz Boş</h3>
            <p class="text-muted">Henüz sepetinize ürün eklemediniz. Hemen alışverişe başlayın!</p>
            <a href="<?= SITE_URL ?>/urunler" class="btn btn-primary btn-lg">
                <i class="fas fa-shopping-bag"></i> Alışverişe Başla
            </a>
        </div>
        <?php else: ?>
        <div class="row">
            <!-- Cart Items -->
            <div class="col-8">
                <div class="cart-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Ürün</th>
                                <th>Birim Fiyat</th>
                                <th>Miktar</th>
                                <th>Toplam</th>
                                <th style="width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="cartTableBody">
                            <?php foreach ($cartItems as $item): 
                                $itemPrice = $item['sale_price'] && $item['sale_price'] < $item['price'] ? $item['sale_price'] : $item['price'];
                                $itemTotal = $itemPrice * $item['quantity'];
                            ?>
                            <tr id="cartItem-<?= $item['product_id'] ?? $item['id'] ?>">
                                <td>
                                    <div class="cart-item-info">
                                        <div class="cart-item-image">
                                            <img src="<?= getProductImageUrl($item) ?>" alt="<?= sanitize($item['name']) ?>">
                                        </div>
                                        <div>
                                            <a href="<?= SITE_URL ?>/urun/<?= $item['slug'] ?>" class="cart-item-name">
                                                <?= sanitize($item['name']) ?>
                                            </a>
                                            <?php if (!empty($item['sku'])): ?>
                                            <div style="font-size: 12px; color: var(--gray-400); margin-top: 4px;">SKU: <?= $item['sku'] ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight: 600; color: var(--dark);"><?= formatPrice($itemPrice) ?></span>
                                </td>
                                <td>
                                    <div class="quantity-selector" style="margin: 0;">
                                        <button onclick="updateCartQuantity(<?= $item['product_id'] ?? $item['id'] ?>, <?= $item['quantity'] - 1 ?>)">−</button>
                                        <input type="number" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" 
                                               onchange="updateCartQuantity(<?= $item['product_id'] ?? $item['id'] ?>, this.value)" style="width: 50px;">
                                        <button onclick="updateCartQuantity(<?= $item['product_id'] ?? $item['id'] ?>, <?= $item['quantity'] + 1 ?>)">+</button>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: var(--primary); font-size: var(--text-lg);">
                                        <?= formatPrice($itemTotal) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="cart-item-remove" onclick="removeFromCart(<?= $item['product_id'] ?? $item['id'] ?>)" title="Kaldır">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="flex-between mt-3">
                    <a href="<?= SITE_URL ?>/urunler" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Alışverişe Devam Et
                    </a>
                </div>
            </div>

            <!-- Cart Summary -->
            <div class="col-4">
                <div class="cart-summary" id="cartSummary">
                    <h3><i class="fas fa-receipt" style="color: var(--primary);"></i> Sipariş Özeti</h3>
                    
                    <div class="cart-summary-row">
                        <span>Ara Toplam</span>
                        <span id="subtotal"><?= formatPrice($cartTotal) ?></span>
                    </div>
                    <div class="cart-summary-row">
                        <span>Teslimat Ücreti</span>
                        <span id="deliveryFee">
                            <?php if ($deliveryFee > 0): ?>
                                <?= formatPrice($deliveryFee) ?>
                            <?php else: ?>
                                <span style="color: var(--success); font-weight: 600;">Ücretsiz</span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <?php if ($cartTotal < FREE_DELIVERY_AMOUNT): ?>
                    <div class="free-delivery-msg" style="background: var(--info-light); color: var(--info);">
                        <i class="fas fa-info-circle"></i> 
                        <?= formatPrice(FREE_DELIVERY_AMOUNT - $cartTotal) ?> daha ekleyin, teslimat ücretsiz olsun!
                    </div>
                    <?php else: ?>
                    <div class="free-delivery-msg">
                        <i class="fas fa-check-circle"></i> Ücretsiz teslimat hakkı kazandınız!
                    </div>
                    <?php endif; ?>

                    <!-- Coupon -->
                    <div class="cart-coupon">
                        <label class="form-label">İndirim Kuponu</label>
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Kupon kodu" id="couponInput">
                            <button class="btn btn-primary" onclick="applyCoupon()">Uygula</button>
                        </div>
                    </div>

                    <div class="cart-summary-row total">
                        <span>Genel Toplam</span>
                        <span class="price-current" id="grandTotal"><?= formatPrice($grandTotal) ?></span>
                    </div>

                    <div style="margin-top: var(--space-lg);">
                        <?php if ($cartTotal >= MIN_ORDER_AMOUNT): ?>
                        <a href="<?= SITE_URL ?>/odeme" class="btn btn-primary btn-lg btn-block">
                            <i class="fas fa-lock"></i> Güvenli Ödemeye Geç
                        </a>
                        <?php else: ?>
                        <button class="btn btn-primary btn-lg btn-block" disabled>
                            Minimum sipariş: <?= formatPrice(MIN_ORDER_AMOUNT) ?>
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="flex-center gap-md mt-3" style="font-size: var(--text-xs); color: var(--gray-400);">
                        <span><i class="fas fa-lock"></i> 256-bit SSL</span>
                        <span><i class="fas fa-shield-halved"></i> iyzico Güvencesi</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
