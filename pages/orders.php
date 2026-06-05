<?php
/**
 * Salim Hırdavat - Sipariş Geçmişi
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = currentUser();
$page = max(1, (int)get('page', 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$total = db()->count('orders', 'user_id = ?', [$user['id']]);
$orders = db()->fetchAll(
    "SELECT o.*, CONCAT(cr.first_name, ' ', cr.last_name) as courier_name, cr.phone as courier_phone
     FROM orders o 
     LEFT JOIN couriers cr ON o.courier_id = cr.id
     WHERE o.user_id = ? 
     ORDER BY o.created_at DESC 
     LIMIT {$perPage} OFFSET {$offset}",
    [$user['id']]
);

// Sipariş detay
$detail = null;
$detailItems = [];
$detailHistory = [];
if (isset($_GET['detail'])) {
    $detail = db()->fetch(
        "SELECT o.*, CONCAT(cr.first_name, ' ', cr.last_name) as courier_name, cr.phone as courier_phone
         FROM orders o LEFT JOIN couriers cr ON o.courier_id = cr.id 
         WHERE o.id = ? AND o.user_id = ?", 
        [(int)$_GET['detail'], $user['id']]
    );
    if ($detail) {
        $detailItems = db()->fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$detail['id']]);
        $detailHistory = db()->fetchAll("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at", [$detail['id']]);
    }
}

$pageTitle = 'Siparişlerim - ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<?= renderBreadcrumb([['name' => 'Siparişlerim']]) ?>

<section class="section" style="padding-top: var(--space-xl);">
    <div class="container">
        <?php if ($detail): ?>
        <!-- Order Detail -->
        <a href="<?= SITE_URL ?>/siparislerim" class="btn btn-outline btn-sm" style="margin-bottom: var(--space-lg);">
            <i class="fas fa-arrow-left"></i> Siparişlerime Dön
        </a>
        
        <div style="background: var(--white); border-radius: var(--radius-lg); padding: var(--space-xl); box-shadow: var(--shadow-card); margin-bottom: var(--space-lg);">
            <div class="flex-between" style="flex-wrap: wrap; gap: 16px; margin-bottom: var(--space-xl);">
                <div>
                    <h2>Sipariş #<?= $detail['order_number'] ?></h2>
                    <p class="text-muted" style="font-size: var(--text-sm);">
                        <?= formatDate($detail['created_at']) ?> tarihinde oluşturuldu
                    </p>
                </div>
                <span class="<?= getOrderStatusClass($detail['status']) ?>" style="padding: 8px 20px; font-size: 14px;">
                    <?= getOrderStatusText($detail['status']) ?>
                </span>
            </div>

            <!-- Timeline -->
            <?php if (!empty($detailHistory)): ?>
            <div style="margin-bottom: var(--space-xl);">
                <h4 style="margin-bottom: var(--space-md);">Sipariş Durumu</h4>
                <?php foreach ($detailHistory as $h): ?>
                <div style="display: flex; gap: 16px; padding: 10px 0; border-left: 2px solid var(--primary); margin-left: 8px; padding-left: 20px; position: relative;">
                    <div style="position: absolute; left: -6px; top: 14px; width: 10px; height: 10px; background: var(--primary); border-radius: 50%;"></div>
                    <div>
                        <strong style="font-size: 14px;"><?= getOrderStatusText($h['status']) ?></strong>
                        <span style="font-size: 12px; color: var(--gray-400); margin-left: 8px;"><?= formatDate($h['created_at']) ?></span>
                        <?php if ($h['note']): ?><p style="font-size: 13px; color: var(--gray-500); margin: 4px 0 0;"><?= sanitize($h['note']) ?></p><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Courier -->
            <?php if ($detail['courier_name']): ?>
            <div style="background: var(--light); padding: var(--space-md); border-radius: var(--radius-md); margin-bottom: var(--space-lg);">
                <strong><i class="fas fa-motorcycle" style="color: var(--primary);"></i> Kurye:</strong>
                <?= sanitize($detail['courier_name']) ?>
                <?php if ($detail['courier_phone']): ?>
                - <a href="tel:<?= $detail['courier_phone'] ?>" style="color: var(--primary);"><?= $detail['courier_phone'] ?></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Items -->
            <h4 style="margin-bottom: var(--space-md);">Ürünler</h4>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--gray-100);">
                        <th style="text-align: left; padding: 10px;">Ürün</th>
                        <th style="padding: 10px;">Miktar</th>
                        <th style="padding: 10px;">Birim Fiyat</th>
                        <th style="text-align: right; padding: 10px;">Toplam</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detailItems as $item): ?>
                    <tr style="border-bottom: 1px solid var(--gray-100);">
                        <td style="padding: 12px;"><?= sanitize($item['product_name']) ?></td>
                        <td style="padding: 12px; text-align: center;"><?= $item['quantity'] ?></td>
                        <td style="padding: 12px; text-align: center;"><?= formatPrice($item['price']) ?></td>
                        <td style="padding: 12px; text-align: right; font-weight: 600;"><?= formatPrice($item['total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="max-width: 300px; margin-left: auto; margin-top: var(--space-lg);">
                <div class="flex-between" style="padding: 6px 0;"><span>Ara Toplam:</span><span><?= formatPrice($detail['subtotal']) ?></span></div>
                <div class="flex-between" style="padding: 6px 0;"><span>Teslimat:</span><span><?= $detail['delivery_fee'] > 0 ? formatPrice($detail['delivery_fee']) : 'Ücretsiz' ?></span></div>
                <?php if ($detail['discount'] > 0): ?>
                <div class="flex-between" style="padding: 6px 0; color: var(--success);"><span>İndirim:</span><span>-<?= formatPrice($detail['discount']) ?></span></div>
                <?php endif; ?>
                <div class="flex-between" style="padding: 10px 0; border-top: 2px solid var(--dark); font-weight: 700; font-size: var(--text-lg);">
                    <span>Toplam:</span><span style="color: var(--primary);"><?= formatPrice($detail['total']) ?></span>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Order List -->
        <h1 style="margin-bottom: var(--space-xl);">
            <i class="fas fa-box" style="color: var(--primary);"></i> Siparişlerim
        </h1>

        <?php if (empty($orders)): ?>
        <div class="empty-cart">
            <i class="fas fa-box-open"></i>
            <h3>Henüz siparişiniz yok</h3>
            <p class="text-muted">İlk siparişinizi verin!</p>
            <a href="<?= SITE_URL ?>/urunler" class="btn btn-primary btn-lg">Alışverişe Başla</a>
        </div>
        <?php else: ?>
        <?php foreach ($orders as $order): ?>
        <div style="background: var(--white); border-radius: var(--radius-lg); padding: var(--space-lg); box-shadow: var(--shadow-card); margin-bottom: var(--space-md);">
            <div class="flex-between" style="flex-wrap: wrap; gap: 12px;">
                <div>
                    <strong style="font-size: var(--text-lg);">Sipariş #<?= $order['order_number'] ?></strong>
                    <span class="<?= getOrderStatusClass($order['status']) ?>" style="margin-left: 12px;">
                        <?= getOrderStatusText($order['status']) ?>
                    </span>
                    <p class="text-muted" style="font-size: var(--text-sm); margin-top: 4px;">
                        <?= formatDate($order['created_at']) ?>
                    </p>
                </div>
                <div style="text-align: right;">
                    <div style="font-family: var(--font-heading); font-size: var(--text-xl); font-weight: 700; color: var(--primary);">
                        <?= formatPrice($order['total']) ?>
                    </div>
                    <a href="?detail=<?= $order['id'] ?>" class="btn btn-sm btn-outline" style="margin-top: 8px;">
                        <i class="fas fa-eye"></i> Detay
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?= getPagination($total, $perPage, $page, '?page=%d') ?>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
