<?php
/**
 * Salim Hırdavat - Admin Sipariş Yönetimi
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// Durum güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = sanitize($_POST['new_status']);
    $courierId = (int)($_POST['courier_id'] ?? 0);
    
    db()->update('orders', ['status' => $newStatus], 'id = ?', [$orderId]);
    
    if ($courierId > 0) {
        db()->update('orders', ['courier_id' => $courierId], 'id = ?', [$orderId]);
    }
    
    db()->insert('order_status_history', [
        'order_id' => $orderId,
        'status' => $newStatus,
        'note' => sanitize($_POST['note'] ?? ''),
        'created_by' => $_SESSION['user_id']
    ]);
    
    flashMessage('success', 'Sipariş durumu güncellendi.');
    redirect(SITE_URL . '/admin/orders.php');
}

// Filtreler
$statusFilter = get('status');
$page = max(1, (int)get('page', 1));
$search = get('search');
$perPage = 30;
$offset = ($page - 1) * $perPage;

$where = ['1=1'];
$params = [];

if ($statusFilter) {
    $where[] = 'o.status = ?';
    $params[] = $statusFilter;
}
if ($search) {
    $where[] = "(o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.phone LIKE ?)";
    $s = "%{$search}%";
    $params = array_merge($params, [$s, $s, $s, $s]);
}

$whereStr = implode(' AND ', $where);
$total = db()->fetch("SELECT COUNT(*) as cnt FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE {$whereStr}", $params)['cnt'];

$orders = db()->fetchAll(
    "SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) as customer_name, u.phone as customer_phone, u.email as customer_email,
            CONCAT(cr.first_name, ' ', cr.last_name) as courier_name
     FROM orders o 
     LEFT JOIN users u ON o.user_id = u.id 
     LEFT JOIN couriers cr ON o.courier_id = cr.id
     WHERE {$whereStr}
     ORDER BY o.created_at DESC 
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);

$couriers = db()->fetchAll("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM couriers WHERE is_active = 1 ORDER BY first_name");

$statusCounts = db()->fetchAll("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status");
$statusMap = [];
foreach ($statusCounts as $sc) $statusMap[$sc['status']] = $sc['cnt'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siparişler - Admin - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h2 class="page-title">Sipariş Yönetimi</h2>
        </header>

        <div class="admin-content">
            <?= showFlashMessages() ?>

            <!-- Status Tabs -->
            <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
                <a href="orders.php" class="btn btn-sm <?= !$statusFilter ? 'btn-primary' : 'btn-outline' ?>">
                    Tümü <span style="margin-left: 4px; opacity: 0.7;"><?= $total ?></span>
                </a>
                <?php foreach (['pending'=>'Bekleyen','confirmed'=>'Onaylanan','preparing'=>'Hazırlanan','courier_assigned'=>'Kurye Atanan','on_delivery'=>'Yolda','delivered'=>'Teslim'] as $key => $label): ?>
                <a href="orders.php?status=<?= $key ?>" class="btn btn-sm <?= $statusFilter === $key ? 'btn-primary' : 'btn-outline' ?>">
                    <?= $label ?> <span style="margin-left: 4px; opacity: 0.7;"><?= $statusMap[$key] ?? 0 ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Search -->
            <div class="admin-card" style="margin-bottom: 20px;">
                <div class="card-body" style="padding: 12px 20px;">
                    <form method="GET" style="display: flex; gap: 12px;">
                        <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= $statusFilter ?>"><?php endif; ?>
                        <input type="text" name="search" class="form-control" placeholder="Sipariş no, müşteri adı veya telefon..." value="<?= sanitize($search) ?>" style="max-width: 400px;">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="admin-card">
                <div class="card-body" style="padding: 0; overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Sipariş No</th>
                                <th>Müşteri</th>
                                <th>Tutar</th>
                                <th>Ödeme</th>
                                <th>Durum</th>
                                <th>Kurye</th>
                                <th>Tarih</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?= $order['order_number'] ?></strong></td>
                                <td>
                                    <div style="font-weight: 600; font-size: 13px;"><?= sanitize($order['customer_name'] ?? 'Misafir') ?></div>
                                    <?php if ($order['customer_phone']): ?>
                                    <div style="font-size: 12px; color: var(--gray-400);"><?= $order['customer_phone'] ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= formatPrice($order['total']) ?></strong></td>
                                <td>
                                    <span class="<?= $order['payment_status'] === 'paid' ? 'status-delivered' : 'status-pending' ?>">
                                        <?= getPaymentStatusText($order['payment_status']) ?>
                                    </span>
                                    <div style="font-size: 11px; color: var(--gray-400); margin-top: 2px;">
                                        <?= $order['payment_method'] === 'iyzico' ? 'Online' : ($order['payment_method'] === 'cash' ? 'Nakit' : 'Kart') ?>
                                    </div>
                                </td>
                                <td><span class="<?= getOrderStatusClass($order['status']) ?>"><?= getOrderStatusText($order['status']) ?></span></td>
                                <td style="font-size: 13px;"><?= sanitize($order['courier_name'] ?? '-') ?></td>
                                <td style="font-size: 13px; color: var(--gray-500);"><?= formatDate($order['created_at'], 'd.m.Y H:i') ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline" onclick="showOrderModal(<?= $order['id'] ?>, '<?= $order['order_number'] ?>', '<?= $order['status'] ?>')">
                                        <i class="fas fa-edit"></i> Güncelle
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($orders)): ?>
                            <tr><td colspan="8" style="text-align: center; padding: 40px; color: var(--gray-400);">Sipariş bulunamadı</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal-overlay" id="orderModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; padding: 32px; max-width: 500px; width: 90%; box-shadow: var(--shadow-lg);">
            <h3 style="margin-bottom: 20px;">Sipariş Durumu Güncelle - <span id="modalOrderNo"></span></h3>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="order_id" id="modalOrderId">
                
                <div class="form-group">
                    <label class="form-label">Yeni Durum</label>
                    <select name="new_status" id="modalStatus" class="form-control">
                        <option value="pending">Beklemede</option>
                        <option value="confirmed">Onaylandı</option>
                        <option value="preparing">Hazırlanıyor</option>
                        <option value="courier_assigned">Kurye Atandı</option>
                        <option value="on_delivery">Teslimat Yolunda</option>
                        <option value="delivered">Teslim Edildi</option>
                        <option value="cancelled">İptal Edildi</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Kurye Ata</label>
                    <select name="courier_id" class="form-control">
                        <option value="">Kurye Seçin</option>
                        <?php foreach ($couriers as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Not</label>
                    <textarea name="note" class="form-control" rows="2" placeholder="Opsiyonel not"></textarea>
                </div>
                
                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Güncelle</button>
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('orderModal').style.display='none'">İptal</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
    <script>
    function showOrderModal(id, orderNo, currentStatus) {
        document.getElementById('modalOrderId').value = id;
        document.getElementById('modalOrderNo').textContent = '#' + orderNo;
        document.getElementById('modalStatus').value = currentStatus;
        document.getElementById('orderModal').style.display = 'flex';
    }
    
    document.getElementById('orderModal').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
    </script>
</body>
</html>
