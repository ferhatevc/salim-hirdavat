<?php
/**
 * Salim Hırdavat - Admin Kategori Yönetimi
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// Ekleme / Düzenleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $id = (int)($_POST['id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $slug = !empty($_POST['slug']) ? slugify($_POST['slug']) : slugify($name);
    $parentId = (int)($_POST['parent_id'] ?? 0) ?: null;
    $icon = sanitize($_POST['icon'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name)) {
        flashMessage('danger', 'Kategori adı zorunludur.');
    } else {
        $data = [
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $parentId,
            'icon' => $icon,
            'sort_order' => $sortOrder,
            'is_active' => $isActive
        ];
        
        // Görsel
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imgPath = UPLOADS_PATH . '/categories/';
            if (!is_dir($imgPath)) mkdir($imgPath, 0755, true);
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'cat_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $imgPath . $filename);
            $data['image'] = $filename;
        }
        
        if ($id > 0) {
            db()->update('categories', $data, 'id = ?', [$id]);
            flashMessage('success', 'Kategori güncellendi.');
        } else {
            db()->insert('categories', $data);
            flashMessage('success', 'Kategori eklendi.');
        }
    }
    redirect(SITE_URL . '/admin/categories.php');
}

// Silme
if (isset($_GET['delete']) && validateCSRFToken($_GET['token'] ?? '')) {
    $id = (int)$_GET['delete'];
    $productCount = db()->count('products', 'category_id = ?', [$id]);
    if ($productCount > 0) {
        flashMessage('danger', 'Bu kategoride ' . $productCount . ' ürün var. Önce ürünleri taşıyın.');
    } else {
        db()->delete('categories', 'id = ?', [$id]);
        db()->update('categories', ['parent_id' => null], 'parent_id = ?', [$id]);
        flashMessage('success', 'Kategori silindi.');
    }
    redirect(SITE_URL . '/admin/categories.php');
}

$categories = db()->fetchAll(
    "SELECT c.*, p.name as parent_name, 
            (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count
     FROM categories c 
     LEFT JOIN categories p ON c.parent_id = p.id 
     ORDER BY c.parent_id IS NULL DESC, c.sort_order, c.name"
);

$parentCategories = db()->fetchAll("SELECT id, name FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY name");
$editCategory = isset($_GET['edit']) ? db()->fetch("SELECT * FROM categories WHERE id = ?", [(int)$_GET['edit']]) : null;
$csrfToken = generateCSRFToken();

// Popüler icon listesi
$icons = ['fa-bolt','fa-wrench','fa-paint-roller','fa-faucet','fa-lock','fa-lightbulb','fa-hammer','fa-screwdriver-wrench','fa-tape','fa-plug','fa-house-chimney','fa-toolbox','fa-ruler','fa-spray-can','fa-power-off','fa-fan','fa-fire','fa-gear','fa-key','fa-pump-soap','fa-shower','fa-stairs','fa-trowel','fa-trowel-bricks'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategoriler - Admin - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h2 class="page-title">Kategori Yönetimi</h2>
        </header>

        <div class="admin-content">
            <?= showFlashMessages() ?>

            <div style="display: grid; grid-template-columns: 1fr 380px; gap: 24px;">
                <!-- Category List -->
                <div class="admin-card">
                    <div class="card-header">
                        <h3><?= count($categories) ?> Kategori</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Üst Kategori</th>
                                    <th>Ürün</th>
                                    <th>Sıra</th>
                                    <th>Durum</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <?php if ($cat['icon']): ?>
                                            <span style="width: 32px; height: 32px; background: var(--primary-bg); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--primary);"><i class="fas <?= $cat['icon'] ?>"></i></span>
                                            <?php endif; ?>
                                            <strong style="font-size: 13px;"><?= sanitize($cat['name']) ?></strong>
                                        </div>
                                    </td>
                                    <td style="font-size: 13px; color: var(--gray-500);"><?= sanitize($cat['parent_name'] ?? '—') ?></td>
                                    <td style="font-size: 13px;"><?= $cat['product_count'] ?></td>
                                    <td style="font-size: 13px;"><?= $cat['sort_order'] ?></td>
                                    <td>
                                        <span style="color: <?= $cat['is_active'] ? 'var(--success)' : 'var(--gray-400)' ?>; font-size: 12px; font-weight: 600;">
                                            <i class="fas fa-circle" style="font-size: 8px;"></i> <?= $cat['is_active'] ? 'Aktif' : 'Pasif' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 6px;">
                                            <a href="categories.php?edit=<?= $cat['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                                            <a href="categories.php?delete=<?= $cat['id'] ?>&token=<?= $csrfToken ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu kategoriyi silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add/Edit Form -->
                <div>
                    <div class="admin-card">
                        <div class="card-header">
                            <h3><?= $editCategory ? 'Kategori Düzenle' : 'Yeni Kategori' ?></h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <?= csrfField() ?>
                                <?php if ($editCategory): ?>
                                <input type="hidden" name="id" value="<?= $editCategory['id'] ?>">
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label class="form-label">Kategori Adı <span style="color: var(--danger);">*</span></label>
                                    <input type="text" name="name" class="form-control" value="<?= sanitize($editCategory['name'] ?? '') ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">URL Slug</label>
                                    <input type="text" name="slug" class="form-control" value="<?= sanitize($editCategory['slug'] ?? '') ?>" placeholder="Otomatik">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Üst Kategori</label>
                                    <select name="parent_id" class="form-control">
                                        <option value="">Ana Kategori (Üst yok)</option>
                                        <?php foreach ($parentCategories as $pc): ?>
                                        <option value="<?= $pc['id'] ?>" <?= ($editCategory['parent_id'] ?? '') == $pc['id'] ? 'selected' : '' ?>>
                                            <?= sanitize($pc['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">İkon (Font Awesome)</label>
                                    <input type="text" name="icon" class="form-control" value="<?= sanitize($editCategory['icon'] ?? '') ?>" placeholder="fa-wrench">
                                    <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
                                        <?php foreach ($icons as $icon): ?>
                                        <button type="button" onclick="document.querySelector('[name=icon]').value='<?= $icon ?>'" style="width: 36px; height: 36px; border: 1px solid var(--gray-200); border-radius: 6px; background: white; cursor: pointer; display: flex; align-items: center; justify-content: center;" title="<?= $icon ?>">
                                            <i class="fas <?= $icon ?>" style="color: var(--gray-600);"></i>
                                        </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Görsel</label>
                                    <input type="file" name="image" class="form-control" accept="image/*">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Sıralama</label>
                                    <input type="number" name="sort_order" class="form-control" value="<?= $editCategory['sort_order'] ?? 0 ?>" min="0">
                                </div>
                                
                                <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; cursor: pointer;">
                                    <input type="checkbox" name="is_active" value="1" <?= ($editCategory['is_active'] ?? 1) ? 'checked' : '' ?>> Aktif
                                </label>
                                
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-save"></i> <?= $editCategory ? 'Güncelle' : 'Kategori Ekle' ?>
                                </button>
                                
                                <?php if ($editCategory): ?>
                                <a href="categories.php" class="btn btn-outline btn-block" style="margin-top: 8px;">İptal</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
