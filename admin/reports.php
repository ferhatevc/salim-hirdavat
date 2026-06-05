<?php
/**
 * Salim Hırdavat - Admin Raporlar
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$dateFrom = get('date_from', date('Y-m-01'));
$dateTo = get('date_to', date('Y-m-d'));

// Toplam gelir
$revenue = db()->fetch(
    "SELECT COALESCE(SUM(total), 0) as total, COUNT(*) as count 
     FROM orders WHERE payment_status = 'paid' AND DATE(created_at) BETWEEN ? AND ?",
    [$dateFrom, $dateTo]
);

// Sipariş durumu dağılımı
$statusBreakdown = db()->fetchAll(
    "SELECT status, COUNT(*) as count FROM orders WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status",
    [$dateFrom, $dateTo]
);

// En çok satan ürünler
$topProducts = db()->fetchAll(
    "SELECT oi.product_name, SUM(oi.quantity) as total_qty, SUM(oi.total) as total_revenue
     FROM order_items oi 
     JOIN orders o ON oi.order_id = o.id 
     WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.payment_status = 'paid'
     GROUP BY oi.product_name 
     ORDER BY total_qty DESC LIMIT 15",
    [$dateFrom, $dateTo]
);

// Kategori bazlı satış
$categoryRevenue = db()->fetchAll(
    "SELECT c.name as category_name, SUM(oi.total) as total_revenue, COUNT(DISTINCT o.id) as order_count
     FROM order_items oi 
     JOIN orders o ON oi.order_id = o.id 
     JOIN products p ON oi.product_id = p.id 
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.payment_status = 'paid'
     GROUP BY c.name 
     ORDER BY total_revenue DESC LIMIT 10",
    [$dateFrom, $dateTo]
);

// Günlük gelir (son 30 gün)
$dailyRevenue = db()->fetchAll(
    "SELECT DATE(created_at) as day, COALESCE(SUM(total), 0) as revenue, COUNT(*) as orders
     FROM orders 
     WHERE payment_status = 'paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY DATE(created_at) 
     ORDER BY day"
);

$avgOrderValue = $revenue['count'] > 0 ? $revenue['total'] / $revenue['count'] : 0;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raporlar - Admin - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</head>
<body class="admin-body">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h2 class="page-title"><i class="fas fa-chart-bar" style="color: var(--primary);"></i> Raporlar</h2>
        </header>
        <div class="admin-content">
            <!-- Date Filter -->
            <div class="admin-card" style="margin-bottom: 20px;">
                <div class="card-body" style="padding: 12px 20px;">
                    <form method="GET" style="display: flex; gap: 12px; align-items: end;">
                        <div><label class="form-label" style="font-size: 12px;">Başlangıç</label><input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>"></div>
                        <div><label class="form-label" style="font-size: 12px;">Bitiş</label><input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>"></div>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filtrele</button>
                    </form>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="stats-grid" style="margin-bottom: 24px;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: #28A745;"><i class="fas fa-turkish-lira-sign"></i></div>
                    <div class="stat-info"><span class="stat-value"><?= formatPrice($revenue['total']) ?></span><span class="stat-label">Toplam Gelir</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(255, 107, 0, 0.1); color: #FF6B00;"><i class="fas fa-shopping-bag"></i></div>
                    <div class="stat-info"><span class="stat-value"><?= $revenue['count'] ?></span><span class="stat-label">Toplam Sipariş</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(23, 162, 184, 0.1); color: #17A2B8;"><i class="fas fa-receipt"></i></div>
                    <div class="stat-info"><span class="stat-value"><?= formatPrice($avgOrderValue) ?></span><span class="stat-label">Ortalama Sepet</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(108, 117, 125, 0.1); color: #6C757D;"><i class="fas fa-users"></i></div>
                    <div class="stat-info"><span class="stat-value"><?= db()->count('users', "role = 'customer' AND DATE(created_at) BETWEEN ? AND ?", [$dateFrom, $dateTo]) ?></span><span class="stat-label">Yeni Müşteri</span></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                <!-- Revenue Chart -->
                <div class="admin-card">
                    <div class="card-header"><h3>Günlük Gelir (Son 30 Gün)</h3></div>
                    <div class="card-body"><canvas id="revenueChart" height="250"></canvas></div>
                </div>

                <!-- Top Products -->
                <div class="admin-card">
                    <div class="card-header"><h3>En Çok Satan Ürünler</h3></div>
                    <div class="card-body" style="padding: 0; max-height: 400px; overflow-y: auto;">
                        <table class="admin-table">
                            <thead><tr><th>Ürün</th><th>Adet</th><th>Gelir</th></tr></thead>
                            <tbody>
                                <?php foreach ($topProducts as $p): ?>
                                <tr>
                                    <td style="font-size: 13px;"><?= sanitize(truncate($p['product_name'], 40)) ?></td>
                                    <td><strong><?= $p['total_qty'] ?></strong></td>
                                    <td><strong style="color: var(--primary);"><?= formatPrice($p['total_revenue']) ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <!-- Category Revenue -->
                <div class="admin-card">
                    <div class="card-header"><h3>Kategorilere Göre Satış</h3></div>
                    <div class="card-body" style="padding: 0;">
                        <table class="admin-table">
                            <thead><tr><th>Kategori</th><th>Sipariş</th><th>Gelir</th></tr></thead>
                            <tbody>
                                <?php foreach ($categoryRevenue as $cr): ?>
                                <tr>
                                    <td><strong><?= sanitize($cr['category_name'] ?? 'Kategorisiz') ?></strong></td>
                                    <td><?= $cr['order_count'] ?></td>
                                    <td><strong style="color: var(--primary);"><?= formatPrice($cr['total_revenue']) ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Status Breakdown -->
                <div class="admin-card">
                    <div class="card-header"><h3>Sipariş Durumu Dağılımı</h3></div>
                    <div class="card-body"><canvas id="statusChart" height="250"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
    <script>
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($dailyRevenue, 'day')) ?>,
                datasets: [{
                    label: 'Gelir (₺)',
                    data: <?= json_encode(array_map('floatval', array_column($dailyRevenue, 'revenue'))) ?>,
                    borderColor: '#CC0000',
                    backgroundColor: 'rgba(204, 0, 0, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#CC0000'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('tr-TR') + ' ₺' } },
                    x: { ticks: { maxTicksLimit: 10 } }
                }
            }
        });
    }

    // Status Chart
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        const statusData = <?= json_encode($statusBreakdown) ?>;
        const statusLabels = { pending: 'Bekleyen', confirmed: 'Onaylanan', preparing: 'Hazırlanan', courier_assigned: 'Kurye Atanan', on_delivery: 'Yolda', delivered: 'Teslim Edilen', cancelled: 'İptal' };
        const statusColors = ['#FFC107', '#17A2B8', '#6C3483', '#2471A3', '#E67E22', '#28A745', '#DC3545'];
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusData.map(s => statusLabels[s.status] || s.status),
                datasets: [{
                    data: statusData.map(s => s.count),
                    backgroundColor: statusColors.slice(0, statusData.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }
    </script>
</body>
</html>
