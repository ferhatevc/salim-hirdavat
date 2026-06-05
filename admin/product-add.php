<?php
/**
 * Salim Hırdavat - Admin Ürün Ekleme
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$categories = db()->fetchAll("SELECT id, name, parent_id FROM categories WHERE is_active = 1 ORDER BY name");
$brands = db()->fetchAll("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name");
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Güvenlik hatası.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $slug = !empty($_POST['slug']) ? slugify($_POST['slug']) : slugify($name);
        $price = (float)str_replace(',', '.', $_POST['price'] ?? 0);
        
        if (empty($name)) $errors[] = 'Ürün adı zorunludur.';
        if ($price <= 0) $errors[] = 'Geçerli bir fiyat giriniz.';
        
        // Slug benzersizlik
        $slugExists = db()->count('products', 'slug = ?', [$slug]);
        if ($slugExists) $slug .= '-' . substr(uniqid(), -4);
        
        if (empty($errors)) {
            $salePrice = !empty($_POST['sale_price']) ? (float)str_replace(',', '.', $_POST['sale_price']) : null;
            $costPrice = !empty($_POST['cost_price']) ? (float)str_replace(',', '.', $_POST['cost_price']) : null;
            
            $productId = db()->insert('products', [
                'name' => $name,
                'slug' => $slug,
                'sku' => sanitize($_POST['sku'] ?? '') ?: null,
                'barcode' => sanitize($_POST['barcode'] ?? '') ?: null,
                'category_id' => (int)$_POST['category_id'] ?: null,
                'brand_id' => (int)$_POST['brand_id'] ?: null,
                'price' => $price,
                'sale_price' => $salePrice,
                'cost_price' => $costPrice,
                'stock' => (int)($_POST['stock'] ?? 0),
                'min_stock' => (int)($_POST['min_stock'] ?? 5),
                'unit' => sanitize($_POST['unit'] ?? 'Adet'),
                'weight' => !empty($_POST['weight']) ? (float)$_POST['weight'] : null,
                'short_description' => sanitize($_POST['short_description'] ?? ''),
                'description' => sanitize($_POST['description'] ?? ''),
                'meta_title' => sanitize($_POST['meta_title'] ?? ''),
                'meta_description' => sanitize($_POST['meta_description'] ?? ''),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'is_new' => isset($_POST['is_new']) ? 1 : 0,
            ]);
            
            // Görsel yükleme
            if (!empty($_FILES['images']['name'][0])) {
                $imgPath = PRODUCT_IMAGE_PATH;
                if (!is_dir($imgPath)) mkdir($imgPath, 0755, true);
                
                foreach ($_FILES['images']['name'] as $i => $imgName) {
                    if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($imgName, PATHINFO_EXTENSION);
                        $filename = uniqid('prod_') . '.' . $ext;
                        
                        if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $imgPath . '/' . $filename)) {
                            db()->insert('product_images', [
                                'product_id' => $productId,
                                'image_path' => $filename,
                                'is_primary' => ($i === 0) ? 1 : 0,
                                'sort_order' => $i
                            ]);
                        }
                    }
                }
            }
            
            // Özellikler
            if (!empty($_POST['attr_name'])) {
                foreach ($_POST['attr_name'] as $i => $attrName) {
                    if (!empty($attrName) && !empty($_POST['attr_value'][$i])) {
                        db()->insert('product_attributes', [
                            'product_id' => $productId,
                            'attribute_name' => sanitize($attrName),
                            'attribute_value' => sanitize($_POST['attr_value'][$i]),
                            'sort_order' => $i
                        ]);
                    }
                }
            }
            
            flashMessage('success', 'Ürün başarıyla eklendi!');
            redirect(SITE_URL . '/admin/products.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Ekle - Admin - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h2 class="page-title">Yeni Ürün Ekle</h2>
        </header>

        <div class="admin-content">
            <?php foreach ($errors as $error): ?>
            <div class="admin-alert danger" style="margin-bottom: 16px;">
                <i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?>
            </div>
            <?php endforeach; ?>

            <form method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
                    <!-- Sol: Ana bilgiler -->
                    <div>
                        <div class="admin-card" style="margin-bottom: 20px;">
                            <div class="card-header"><h3>Temel Bilgiler</h3></div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Ürün Adı <span style="color: var(--danger);">*</span></label>
                                    <input type="text" name="name" id="productName" class="form-control" value="<?= post('name') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">URL Slug</label>
                                    <input type="text" name="slug" id="productSlug" class="form-control" value="<?= post('slug') ?>" placeholder="Otomatik oluşturulur">
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                                    <div class="form-group">
                                        <label class="form-label">SKU / Stok Kodu</label>
                                        <input type="text" name="sku" class="form-control" value="<?= post('sku') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Barkod</label>
                                        <input type="text" name="barcode" class="form-control" value="<?= post('barcode') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Birim</label>
                                        <select name="unit" class="form-control">
                                            <option value="Adet">Adet</option>
                                            <option value="Metre">Metre</option>
                                            <option value="Kg">Kg</option>
                                            <option value="Litre">Litre</option>
                                            <option value="Paket">Paket</option>
                                            <option value="Kutu">Kutu</option>
                                            <option value="Set">Set</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Kısa Açıklama</label>
                                    <textarea name="short_description" class="form-control" rows="2"><?= post('short_description') ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Detaylı Açıklama</label>
                                    <textarea name="description" class="form-control" rows="6"><?= post('description') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Görseller -->
                        <div class="admin-card" style="margin-bottom: 20px;">
                            <div class="card-header"><h3>Ürün Görselleri</h3></div>
                            <div class="card-body">
                                <div class="drop-zone" id="dropZone">
                                    <input type="file" name="images[]" id="imageInput" multiple accept="image/*" style="display: none;">
                                    <i class="fas fa-images" style="display: block;"></i>
                                    <h4>Görselleri sürükleyin veya tıklayın</h4>
                                    <p>JPG, PNG, WebP - Max 5MB (İlk görsel ana görsel olur)</p>
                                </div>
                                <div id="imagePreview" style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px;"></div>
                            </div>
                        </div>

                        <!-- Özellikler -->
                        <div class="admin-card" style="margin-bottom: 20px;">
                            <div class="card-header">
                                <h3>Teknik Özellikler</h3>
                                <button type="button" class="btn btn-sm btn-outline" onclick="addAttribute()"><i class="fas fa-plus"></i> Özellik Ekle</button>
                            </div>
                            <div class="card-body" id="attributesContainer">
                                <div class="attr-row" style="display: flex; gap: 12px; margin-bottom: 12px;">
                                    <input type="text" name="attr_name[]" class="form-control" placeholder="Özellik adı (ör: Voltaj)">
                                    <input type="text" name="attr_value[]" class="form-control" placeholder="Değer (ör: 220V)">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                        </div>

                        <!-- SEO -->
                        <div class="admin-card">
                            <div class="card-header"><h3>SEO Ayarları</h3></div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Meta Başlık</label>
                                    <input type="text" name="meta_title" class="form-control" value="<?= post('meta_title') ?>" maxlength="70">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Meta Açıklama</label>
                                    <textarea name="meta_description" class="form-control" rows="2" maxlength="160"><?= post('meta_description') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sağ: Fiyat, kategori, durum -->
                    <div>
                        <div class="admin-card" style="margin-bottom: 20px;">
                            <div class="card-header"><h3>Fiyatlandırma</h3></div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Satış Fiyatı (₺) <span style="color: var(--danger);">*</span></label>
                                    <input type="text" name="price" class="form-control" value="<?= post('price') ?>" required placeholder="0,00">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">İndirimli Fiyat (₺)</label>
                                    <input type="text" name="sale_price" class="form-control" value="<?= post('sale_price') ?>" placeholder="0,00">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Maliyet Fiyatı (₺)</label>
                                    <input type="text" name="cost_price" class="form-control" value="<?= post('cost_price') ?>" placeholder="0,00">
                                </div>
                            </div>
                        </div>

                        <div class="admin-card" style="margin-bottom: 20px;">
                            <div class="card-header"><h3>Stok</h3></div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Stok Miktarı</label>
                                    <input type="number" name="stock" class="form-control" value="<?= post('stock', '0') ?>" min="0">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Minimum Stok Uyarısı</label>
                                    <input type="number" name="min_stock" class="form-control" value="<?= post('min_stock', '5') ?>" min="0">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Ağırlık (kg)</label>
                                    <input type="text" name="weight" class="form-control" value="<?= post('weight') ?>" placeholder="0.00">
                                </div>
                            </div>
                        </div>

                        <div class="admin-card" style="margin-bottom: 20px;">
                            <div class="card-header"><h3>Organizasyon</h3></div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Kategori</label>
                                    <select name="category_id" class="form-control">
                                        <option value="">Kategori Seçin</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Marka</label>
                                    <select name="brand_id" class="form-control">
                                        <option value="">Marka Seçin</option>
                                        <?php foreach ($brands as $brand): ?>
                                        <option value="<?= $brand['id'] ?>"><?= sanitize($brand['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="admin-card" style="margin-bottom: 20px;">
                            <div class="card-header"><h3>Durum</h3></div>
                            <div class="card-body">
                                <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; cursor: pointer;">
                                    <input type="checkbox" name="is_active" value="1" checked> Aktif (Satışta)
                                </label>
                                <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; cursor: pointer;">
                                    <input type="checkbox" name="is_featured" value="1"> Öne Çıkan Ürün
                                </label>
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                    <input type="checkbox" name="is_new" value="1" checked> Yeni Ürün
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" style="padding: 14px;">
                            <i class="fas fa-save"></i> Ürünü Kaydet
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
    <script>
    // Image preview
    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');
    
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            imagePreview.innerHTML = '';
            Array.from(this.files).forEach((file, i) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.style.cssText = 'position: relative; width: 80px; height: 80px; border-radius: 8px; overflow: hidden; border: 2px solid var(--gray-200);';
                    div.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">
                        ${i === 0 ? '<span style="position: absolute; bottom: 0; left: 0; right: 0; background: var(--primary); color: white; font-size: 10px; text-align: center; padding: 2px;">Ana</span>' : ''}`;
                    imagePreview.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // Add attribute row
    function addAttribute() {
        const container = document.getElementById('attributesContainer');
        const row = document.createElement('div');
        row.className = 'attr-row';
        row.style.cssText = 'display: flex; gap: 12px; margin-bottom: 12px;';
        row.innerHTML = `
            <input type="text" name="attr_name[]" class="form-control" placeholder="Özellik adı">
            <input type="text" name="attr_value[]" class="form-control" placeholder="Değer">
            <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(row);
    }
    </script>
</body>
</html>
