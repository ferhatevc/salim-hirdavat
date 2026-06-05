<?php
/**
 * Salim Hırdavat - Admin E-Fatura Yönetimi
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// Fatura oluştur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice']) && validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $orderId = (int)$_POST['order_id'];
    $order = db()->fetch(
        "SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) as customer_name, u.email, u.phone
         FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?", [$orderId]
    );
    
    if ($order) {
        $prefix = getSettings('invoice_prefix', 'SH');
        $nextNo = (int)getSettings('invoice_next_number', 1);
        $invoiceNumber = $prefix . str_pad($nextNo, 6, '0', STR_PAD_LEFT);
        $taxRate = (int)getSettings('tax_rate', 20);
        $subtotal = $order['total'] / (1 + $taxRate / 100);
        $taxAmount = $order['total'] - $subtotal;
        
        $invoiceId = db()->insert('invoices', [
            'order_id' => $orderId,
            'invoice_number' => $invoiceNumber,
            'invoice_type' => sanitize($_POST['invoice_type'] ?? 'e_arsiv'),
            'buyer_name' => sanitize($_POST['buyer_name'] ?? $order['customer_name']),
            'buyer_tax_number' => sanitize($_POST['buyer_tax_number'] ?? ''),
            'buyer_tax_office' => sanitize($_POST['buyer_tax_office'] ?? ''),
            'buyer_address' => sanitize($_POST['buyer_address'] ?? ''),
            'subtotal' => round($subtotal, 2),
            'tax_rate' => $taxRate,
            'tax_amount' => round($taxAmount, 2),
            'total' => $order['total'],
            'status' => 'draft',
            'created_by' => $_SESSION['user_id']
        ]);
        
        // Sonraki fatura numarasını güncelle
        $exists = db()->count('settings', "setting_key = 'invoice_next_number'");
        if ($exists) {
            db()->update('settings', ['setting_value' => $nextNo + 1], "setting_key = 'invoice_next_number'");
        } else {
            db()->insert('settings', ['setting_key' => 'invoice_next_number', 'setting_value' => $nextNo + 1]);
        }
        
        flashMessage('success', 'Fatura ' . $invoiceNumber . ' oluşturuldu.');
    }
    redirect(SITE_URL . '/admin/invoices.php');
}

// Durum güncelle
if (isset($_GET['update_status'])) {
    $id = (int)$_GET['id'];
    $status = sanitize($_GET['update_status']);
    db()->update('invoices', ['status' => $status], 'id = ?', [$id]);
    flashMessage('success', 'Fatura durumu güncellendi.');
    redirect(SITE_URL . '/admin/invoices.php');
}

// Fatura listesi
$invoices = db()->fetchAll(
    "SELECT i.*, o.order_number 
     FROM invoices i 
     LEFT JOIN orders o ON i.order_id = o.id 
     ORDER BY i.created_at DESC 
     LIMIT 100"
);

// Faturasız siparişler
$uninvoicedOrders = db()->fetchAll(
    "SELECT o.id, o.order_number, o.total, o.created_at, CONCAT(u.first_name, ' ', u.last_name) as customer_name
     FROM orders o 
     LEFT JOIN users u ON o.user_id = u.id
     WHERE o.id NOT IN (SELECT COALESCE(order_id, 0) FROM invoices) 
     AND o.payment_status = 'paid'
     ORDER BY o.created_at DESC LIMIT 20"
);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Fatura - Admin - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h2 class="page-title"><i class="fas fa-file-invoice" style="color: var(--primary);"></i> E-Fatura Yönetimi</h2>
        </header>

        <div class="admin-content">
            <?= showFlashMessages() ?>

            <div style="display: grid; grid-template-columns: 1fr 400px; gap: 24px;">
                <!-- Invoice List -->
                <div class="admin-card">
                    <div class="card-header">
                        <h3>Faturalar (<?= count($invoices) ?>)</h3>
                    </div>
                    <div class="card-body" style="padding: 0; overflow-x: auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Fatura No</th>
                                    <th>Sipariş</th>
                                    <th>Müşteri</th>
                                    <th>Toplam</th>
                                    <th>KDV</th>
                                    <th>Tip</th>
                                    <th>Durum</th>
                                    <th>Tarih</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $inv): ?>
                                <tr>
                                    <td><strong><?= $inv['invoice_number'] ?></strong></td>
                                    <td>#<?= $inv['order_number'] ?></td>
                                    <td style="font-size: 13px;"><?= sanitize($inv['buyer_name']) ?></td>
                                    <td><strong><?= formatPrice($inv['total']) ?></strong></td>
                                    <td style="font-size: 13px;"><?= formatPrice($inv['tax_amount']) ?></td>
                                    <td style="font-size: 12px;"><?= $inv['invoice_type'] === 'e_fatura' ? 'E-Fatura' : 'E-Arşiv' ?></td>
                                    <td>
                                        <?php
                                        $statusColors = ['draft' => '#FFC107', 'sent' => '#28A745', 'cancelled' => '#DC3545'];
                                        $statusTexts = ['draft' => 'Taslak', 'sent' => 'Gönderildi', 'cancelled' => 'İptal'];
                                        ?>
                                        <span style="display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; background: <?= $statusColors[$inv['status']] ?? '#6C757D' ?>20; color: <?= $statusColors[$inv['status']] ?? '#6C757D' ?>;">
                                            <?= $statusTexts[$inv['status']] ?? $inv['status'] ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 13px;"><?= formatDate($inv['created_at'], 'd.m.Y') ?></td>
                                    <td>
                                        <div style="display: flex; gap: 4px;">
                                            <?php if ($inv['status'] === 'draft'): ?>
                                            <a href="?id=<?= $inv['id'] ?>&update_status=sent" class="btn btn-sm btn-success" title="Gönderildi olarak işaretle"><i class="fas fa-check"></i></a>
                                            <?php endif; ?>
                                            <a href="?id=<?= $inv['id'] ?>&print=1" class="btn btn-sm btn-outline" title="Yazdır" target="_blank"><i class="fas fa-print"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($invoices)): ?>
                                <tr><td colspan="9" style="text-align: center; padding: 40px; color: var(--gray-400);">Henüz fatura oluşturulmamış</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Create Invoice -->
                <div>
                    <div class="admin-card">
                        <div class="card-header"><h3>Fatura Oluştur</h3></div>
                        <div class="card-body">
                            <?php if (empty($uninvoicedOrders)): ?>
                            <p style="text-align: center; color: var(--gray-400); padding: 20px;">Fatura bekleyen sipariş yok.</p>
                            <?php else: ?>
                            <form method="POST">
                                <?= csrfField() ?>
                                <input type="hidden" name="create_invoice" value="1">
                                
                                <div class="form-group">
                                    <label class="form-label">Sipariş Seçin</label>
                                    <select name="order_id" class="form-control" required>
                                        <option value="">Sipariş Seçin</option>
                                        <?php foreach ($uninvoicedOrders as $o): ?>
                                        <option value="<?= $o['id'] ?>">#<?= $o['order_number'] ?> - <?= sanitize($o['customer_name']) ?> (<?= formatPrice($o['total']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Fatura Tipi</label>
                                    <select name="invoice_type" class="form-control">
                                        <option value="e_arsiv">E-Arşiv Fatura</option>
                                        <option value="e_fatura">E-Fatura</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Alıcı Adı / Firma</label>
                                    <input type="text" name="buyer_name" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">TC / Vergi No</label>
                                    <input type="text" name="buyer_tax_number" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Vergi Dairesi</label>
                                    <input type="text" name="buyer_tax_office" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Adres</label>
                                    <textarea name="buyer_address" class="form-control" rows="2"></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-file-invoice"></i> Fatura Oluştur
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
