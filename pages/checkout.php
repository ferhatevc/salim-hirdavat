<?php
/**
 * Salim Hırdavat - Ödeme Sayfası
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$cartItems = getCartItems();
$cartTotal = getCartTotal();

if (empty($cartItems)) {
    flashMessage('warning', 'Sepetiniz boş.');
    redirect(SITE_URL . '/urunler');
}

if ($cartTotal < MIN_ORDER_AMOUNT) {
    flashMessage('warning', 'Minimum sipariş tutarı: ' . formatPrice(MIN_ORDER_AMOUNT));
    redirect(SITE_URL . '/sepet');
}

$deliveryFee = $cartTotal >= FREE_DELIVERY_AMOUNT ? 0 : DEFAULT_DELIVERY_FEE;
$couponDiscount = $_SESSION['coupon']['discount'] ?? 0;
$grandTotal = $cartTotal + $deliveryFee - $couponDiscount;

// Giriş kontrolü - guest checkout yoksa giriş iste
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = SITE_URL . '/odeme';
    flashMessage('info', 'Sipariş vermek için giriş yapmalısınız.');
    redirect(SITE_URL . '/giris');
}

$user = currentUser();
$addresses = db()->fetchAll("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC", [$user['id']]);

// Sipariş oluştur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $addressId = (int)$_POST['address_id'];
    $paymentMethod = sanitize($_POST['payment_method'] ?? 'cash');
    $note = sanitize($_POST['note'] ?? '');
    
    $address = db()->fetch("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?", [$addressId, $user['id']]);
    if (!$address) {
        flashMessage('danger', 'Lütfen teslimat adresi seçin.');
        redirect(SITE_URL . '/odeme');
    }
    
    // Sipariş numarası oluştur
    $orderNumber = 'SH' . date('ymd') . strtoupper(substr(uniqid(), -5));
    
    // Sipariş oluştur
    $orderId = db()->insert('orders', [
        'user_id' => $user['id'],
        'order_number' => $orderNumber,
        'subtotal' => $cartTotal,
        'delivery_fee' => $deliveryFee,
        'discount' => $couponDiscount,
        'total' => $grandTotal,
        'coupon_id' => $_SESSION['coupon']['id'] ?? null,
        'payment_method' => $paymentMethod,
        'payment_status' => $paymentMethod === 'iyzico' ? 'pending' : 'pending',
        'status' => 'pending',
        'delivery_address' => json_encode($address),
        'note' => $note,
    ]);
    
    // Sipariş ürünleri
    foreach ($cartItems as $item) {
        $itemPrice = $item['sale_price'] && $item['sale_price'] < $item['price'] ? $item['sale_price'] : $item['price'];
        db()->insert('order_items', [
            'order_id' => $orderId,
            'product_id' => $item['product_id'] ?? $item['id'],
            'product_name' => $item['name'],
            'quantity' => $item['quantity'],
            'price' => $itemPrice,
            'total' => $itemPrice * $item['quantity'],
        ]);
    }
    
    // Durum geçmişi
    db()->insert('order_status_history', [
        'order_id' => $orderId,
        'status' => 'pending',
        'note' => 'Sipariş oluşturuldu.'
    ]);
    
    // Kupon kullanım sayısı artır
    if (!empty($_SESSION['coupon']['id'])) {
        db()->query("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?", [$_SESSION['coupon']['id']]);
        unset($_SESSION['coupon']);
    }
    
    if ($paymentMethod === 'iyzico') {
        // iyzico ödeme sayfasına yönlendir
        $_SESSION['pending_order_id'] = $orderId;
        redirect(SITE_URL . '/pages/payment-process.php?order_id=' . $orderId);
    } else {
        // Kapıda ödeme - stok düş, sepeti temizle
        foreach ($cartItems as $item) {
            $pid = $item['product_id'] ?? $item['id'];
            db()->query("UPDATE products SET stock = stock - ?, sale_count = sale_count + ? WHERE id = ?", [$item['quantity'], $item['quantity'], $pid]);
        }
        
        // Sepeti temizle
        if (isLoggedIn()) {
            db()->delete('cart', 'user_id = ?', [$user['id']]);
        }
        unset($_SESSION['cart']);
        
        flashMessage('success', 'Siparişiniz başarıyla oluşturuldu! Sipariş No: ' . $orderNumber);
        redirect(SITE_URL . '/siparislerim');
    }
}

$pageTitle = 'Ödeme - ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<?= renderBreadcrumb([['name' => 'Sepet', 'slug' => 'sepet'], ['name' => 'Ödeme']]) ?>

<section class="section" style="padding-top: var(--space-xl);">
    <div class="container">
        <h1 style="margin-bottom: var(--space-xl);">
            <i class="fas fa-lock" style="color: var(--primary);"></i> Güvenli Ödeme
        </h1>

        <form method="POST">
            <?= csrfField() ?>
            <div class="row">
                <div class="col-8">
                    <!-- Teslimat Adresi -->
                    <div style="background: var(--white); border-radius: var(--radius-lg); padding: var(--space-xl); box-shadow: var(--shadow-card); margin-bottom: var(--space-lg);">
                        <h3 style="margin-bottom: var(--space-lg);">
                            <span style="display: inline-flex; width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; align-items: center; justify-content: center; font-size: 14px; margin-right: 8px;">1</span>
                            Teslimat Adresi
                        </h3>

                        <?php if (empty($addresses)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Henüz kayıtlı adresiniz yok. 
                            <a href="<?= SITE_URL ?>/hesabim?tab=adresler" style="color: var(--primary); font-weight: 600;">Adres Ekleyin</a>
                        </div>
                        <?php else: ?>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <?php foreach ($addresses as $addr): ?>
                            <label style="display: block; cursor: pointer; padding: 16px; border: 2px solid var(--gray-200); border-radius: var(--radius-md); transition: all 0.2s;">
                                <input type="radio" name="address_id" value="<?= $addr['id'] ?>" <?= $addr['is_default'] ? 'checked' : '' ?> style="margin-right: 8px;" required>
                                <strong><?= sanitize($addr['title']) ?></strong>
                                <p style="margin: 8px 0 0; font-size: var(--text-sm); color: var(--gray-500); line-height: 1.6;">
                                    <?= sanitize($addr['full_name']) ?><br>
                                    <?= sanitize($addr['address_line']) ?><br>
                                    <?= sanitize($addr['district']) ?> / <?= sanitize($addr['city']) ?><br>
                                    <i class="fas fa-phone"></i> <?= sanitize($addr['phone']) ?>
                                </p>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Ödeme Yöntemi -->
                    <div style="background: var(--white); border-radius: var(--radius-lg); padding: var(--space-xl); box-shadow: var(--shadow-card); margin-bottom: var(--space-lg);">
                        <h3 style="margin-bottom: var(--space-lg);">
                            <span style="display: inline-flex; width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; align-items: center; justify-content: center; font-size: 14px; margin-right: 8px;">2</span>
                            Ödeme Yöntemi
                        </h3>

                        <div style="display: grid; gap: 12px;">
                            <label style="display: flex; align-items: center; gap: 12px; padding: 16px; border: 2px solid var(--gray-200); border-radius: var(--radius-md); cursor: pointer;">
                                <input type="radio" name="payment_method" value="cash" checked>
                                <i class="fas fa-money-bill-wave" style="color: var(--success); font-size: 20px;"></i>
                                <div>
                                    <strong>Kapıda Nakit Ödeme</strong>
                                    <p style="font-size: 13px; color: var(--gray-500); margin: 0;">Teslimat sırasında nakit olarak ödeyin</p>
                                </div>
                            </label>
                            <label style="display: flex; align-items: center; gap: 12px; padding: 16px; border: 2px solid var(--gray-200); border-radius: var(--radius-md); cursor: pointer;">
                                <input type="radio" name="payment_method" value="card_door">
                                <i class="fas fa-credit-card" style="color: var(--info); font-size: 20px;"></i>
                                <div>
                                    <strong>Kapıda Kredi Kartı ile Ödeme</strong>
                                    <p style="font-size: 13px; color: var(--gray-500); margin: 0;">Teslimat sırasında POS cihazı ile ödeyin</p>
                                </div>
                            </label>
                            <label style="display: flex; align-items: center; gap: 12px; padding: 16px; border: 2px solid var(--gray-200); border-radius: var(--radius-md); cursor: pointer;">
                                <input type="radio" name="payment_method" value="iyzico">
                                <i class="fas fa-lock" style="color: var(--primary); font-size: 20px;"></i>
                                <div>
                                    <strong>Online Kredi Kartı (iyzico)</strong>
                                    <p style="font-size: 13px; color: var(--gray-500); margin: 0;">iyzico güvencesi ile güvenli online ödeme</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Sipariş Notu -->
                    <div style="background: var(--white); border-radius: var(--radius-lg); padding: var(--space-xl); box-shadow: var(--shadow-card);">
                        <h3 style="margin-bottom: var(--space-md);">
                            <span style="display: inline-flex; width: 32px; height: 32px; background: var(--primary); color: white; border-radius: 50%; align-items: center; justify-content: center; font-size: 14px; margin-right: 8px;">3</span>
                            Sipariş Notu
                        </h3>
                        <textarea name="note" class="form-control" rows="3" placeholder="Sipariş ile ilgili notunuz (opsiyonel)"></textarea>
                    </div>
                </div>

                <!-- Sipariş Özeti -->
                <div class="col-4">
                    <div class="cart-summary" style="position: sticky; top: 90px;">
                        <h3><i class="fas fa-receipt" style="color: var(--primary);"></i> Sipariş Özeti</h3>
                        
                        <!-- Ürünler -->
                        <div style="max-height: 300px; overflow-y: auto; margin-bottom: var(--space-md);">
                            <?php foreach ($cartItems as $item):
                                $itemPrice = $item['sale_price'] && $item['sale_price'] < $item['price'] ? $item['sale_price'] : $item['price'];
                            ?>
                            <div style="display: flex; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--gray-100); font-size: 13px;">
                                <img src="<?= getProductImageUrl($item) ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600;"><?= sanitize(truncate($item['name'], 40)) ?></div>
                                    <div style="color: var(--gray-400);"><?= $item['quantity'] ?> x <?= formatPrice($itemPrice) ?></div>
                                </div>
                                <div style="font-weight: 700;"><?= formatPrice($itemPrice * $item['quantity']) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="cart-summary-row"><span>Ara Toplam</span><span><?= formatPrice($cartTotal) ?></span></div>
                        <div class="cart-summary-row">
                            <span>Teslimat</span>
                            <span><?= $deliveryFee > 0 ? formatPrice($deliveryFee) : '<span style="color: var(--success);">Ücretsiz</span>' ?></span>
                        </div>
                        <?php if ($couponDiscount > 0): ?>
                        <div class="cart-summary-row"><span>Kupon İndirimi</span><span style="color: var(--success);">-<?= formatPrice($couponDiscount) ?></span></div>
                        <?php endif; ?>
                        <div class="cart-summary-row total"><span>Toplam</span><span class="price-current"><?= formatPrice($grandTotal) ?></span></div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top: var(--space-lg);">
                            <i class="fas fa-check-circle"></i> Siparişi Onayla
                        </button>

                        <div class="flex-center gap-md mt-2" style="font-size: var(--text-xs); color: var(--gray-400);">
                            <span><i class="fas fa-lock"></i> 256-bit SSL</span>
                            <span><i class="fas fa-shield-halved"></i> iyzico</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<style>
input[type="radio"]:checked + i { color: var(--primary) !important; }
label:has(input[type="radio"]:checked) { border-color: var(--primary) !important; background: var(--primary-bg); }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
