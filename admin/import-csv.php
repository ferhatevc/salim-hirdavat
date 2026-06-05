<?php
/**
 * Salim Hırdavat - CSV Toplu Ürün Import
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$step = $_GET['step'] ?? 'upload';
$importId = $_GET['import_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV Import - Admin - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h2 class="page-title"><i class="fas fa-file-csv" style="color: var(--primary);"></i> CSV Ürün Import</h2>
        </header>

        <div class="admin-content">
            <!-- Steps -->
            <div style="display: flex; gap: 0; margin-bottom: 32px; background: white; border-radius: 10px; overflow: hidden; box-shadow: var(--shadow);">
                <div style="flex: 1; padding: 16px; text-align: center; font-size: 14px; font-weight: 600; <?= $step === 'upload' ? 'background: var(--primary); color: white;' : 'color: var(--gray-400);' ?>">
                    <i class="fas fa-upload"></i> 1. Dosya Yükle
                </div>
                <div style="flex: 1; padding: 16px; text-align: center; font-size: 14px; font-weight: 600; <?= $step === 'mapping' ? 'background: var(--primary); color: white;' : 'color: var(--gray-400);' ?>">
                    <i class="fas fa-columns"></i> 2. Sütun Eşleştir
                </div>
                <div style="flex: 1; padding: 16px; text-align: center; font-size: 14px; font-weight: 600; <?= $step === 'import' ? 'background: var(--primary); color: white;' : 'color: var(--gray-400);' ?>">
                    <i class="fas fa-cog"></i> 3. Import
                </div>
                <div style="flex: 1; padding: 16px; text-align: center; font-size: 14px; font-weight: 600; <?= $step === 'result' ? 'background: var(--success); color: white;' : 'color: var(--gray-400);' ?>">
                    <i class="fas fa-check"></i> 4. Sonuç
                </div>
            </div>

            <?php if ($step === 'upload'): ?>
            <!-- Step 1: Upload -->
            <div class="admin-card">
                <div class="card-header">
                    <h3>CSV Dosyası Yükle</h3>
                    <a href="<?= SITE_URL ?>/admin/import-csv.php?action=sample" class="btn btn-sm btn-outline">
                        <i class="fas fa-download"></i> Örnek CSV İndir
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" action="?step=mapping" id="uploadForm">
                        <?= csrfField() ?>
                        
                        <div class="drop-zone" id="dropZone">
                            <input type="file" name="csv_file" id="csvFile" accept=".csv,.txt" style="display: none;" required>
                            <i class="fas fa-file-csv" style="display: block;"></i>
                            <h4>CSV dosyasını buraya sürükleyin</h4>
                            <p>veya tıklayarak dosya seçin (Max: 50MB)</p>
                            <div id="fileName" style="margin-top: 12px; color: var(--primary); font-weight: 600; display: none;"></div>
                        </div>

                        <div style="margin-top: 20px;">
                            <label class="form-label">Ayarlar</label>
                            <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                                <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer;">
                                    <input type="checkbox" name="has_header" value="1" checked>
                                    İlk satır başlık satırı
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer;">
                                    <input type="checkbox" name="update_existing" value="1" checked>
                                    Mevcut ürünleri güncelle (SKU'ya göre)
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer;">
                                    <input type="checkbox" name="skip_duplicates" value="1">
                                    Tekrarlanan ürünleri atla
                                </label>
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px; margin-top: 24px;">
                            <select name="delimiter" class="form-control" style="width: auto;">
                                <option value=",">Ayırıcı: Virgül (,)</option>
                                <option value=";">Ayırıcı: Noktalı Virgül (;)</option>
                                <option value="\t">Ayırıcı: Tab</option>
                            </select>
                            <select name="encoding" class="form-control" style="width: auto;">
                                <option value="UTF-8">Encoding: UTF-8</option>
                                <option value="ISO-8859-9">Encoding: ISO-8859-9 (Türkçe)</option>
                                <option value="Windows-1254">Encoding: Windows-1254</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top: 24px;" id="uploadBtn">
                            <i class="fas fa-arrow-right"></i> Devam Et - Sütun Eşleştirme
                        </button>
                    </form>

                    <div style="margin-top: 32px; padding: 20px; background: var(--light); border-radius: 10px;">
                        <h4 style="margin-bottom: 12px;"><i class="fas fa-info-circle" style="color: var(--info);"></i> CSV Format Bilgisi</h4>
                        <p style="font-size: 13px; color: var(--gray-600); line-height: 1.8;">
                            CSV dosyanız aşağıdaki sütunları içerebilir (hepsi zorunlu değil):<br>
                            <strong>Zorunlu:</strong> ürün_adı, fiyat<br>
                            <strong>Önerilen:</strong> sku, kategori, stok, açıklama, birim<br>
                            <strong>Opsiyonel:</strong> barkod, marka, indirimli_fiyat, ağırlık, görsel_url
                        </p>
                        <p style="font-size: 13px; color: var(--gray-500); margin-top: 8px;">
                            <i class="fas fa-lightbulb" style="color: var(--warning);"></i>
                            25.000 ürün yaklaşık 2-5 dakikada import edilecektir (1000'er gruplar halinde).
                        </p>
                    </div>
                </div>
            </div>

            <?php elseif ($step === 'mapping'): ?>
            <!-- Step 2: Column Mapping -->
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['csv_file']['tmp_name'])) {
                if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) { die('CSRF Error'); }
                
                $file = $_FILES['csv_file'];
                $delimiter = $_POST['delimiter'] === '\t' ? "\t" : $_POST['delimiter'];
                $encoding = $_POST['encoding'];
                $hasHeader = isset($_POST['has_header']);
                
                // Dosyayı kaydet
                $uploadPath = UPLOADS_PATH . '/imports/';
                if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
                $savedFile = $uploadPath . uniqid('csv_') . '.csv';
                move_uploaded_file($file['tmp_name'], $savedFile);
                
                // Encoding dönüşümü
                if ($encoding !== 'UTF-8') {
                    $content = file_get_contents($savedFile);
                    $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                    file_put_contents($savedFile, $content);
                }
                
                // İlk 10 satırı oku
                $handle = fopen($savedFile, 'r');
                $previewRows = [];
                $headers = [];
                $lineCount = 0;
                
                while (($row = fgetcsv($handle, 0, $delimiter)) !== false && $lineCount < 11) {
                    if ($lineCount === 0 && $hasHeader) {
                        $headers = $row;
                    } else {
                        $previewRows[] = $row;
                    }
                    $lineCount++;
                }
                
                // Toplam satır sayısı
                $totalLines = 0;
                rewind($handle);
                while (fgetcsv($handle, 0, $delimiter) !== false) $totalLines++;
                fclose($handle);
                if ($hasHeader) $totalLines--;
                
                // Session'a kaydet
                $_SESSION['csv_import'] = [
                    'file' => $savedFile,
                    'delimiter' => $delimiter,
                    'has_header' => $hasHeader,
                    'headers' => $headers,
                    'total_lines' => $totalLines,
                    'update_existing' => isset($_POST['update_existing']),
                    'skip_duplicates' => isset($_POST['skip_duplicates']),
                ];
                
                $dbFields = [
                    '' => '-- Atla --',
                    'name' => 'Ürün Adı *',
                    'sku' => 'SKU / Stok Kodu',
                    'barcode' => 'Barkod',
                    'price' => 'Fiyat *',
                    'sale_price' => 'İndirimli Fiyat',
                    'cost_price' => 'Maliyet Fiyatı',
                    'stock' => 'Stok Miktarı',
                    'category' => 'Kategori',
                    'brand' => 'Marka',
                    'description' => 'Açıklama',
                    'short_description' => 'Kısa Açıklama',
                    'unit' => 'Birim (Adet/Metre/Kg)',
                    'weight' => 'Ağırlık (kg)',
                    'image_url' => 'Görsel URL',
                ];
            }
            ?>
            
            <div class="admin-card">
                <div class="card-header">
                    <h3>Sütun Eşleştirme</h3>
                    <span style="font-size: 14px; color: var(--gray-500);">
                        Toplam: <strong><?= number_format($totalLines) ?></strong> ürün
                    </span>
                </div>
                <div class="card-body">
                    <p style="margin-bottom: 20px; font-size: 14px; color: var(--gray-600);">
                        CSV dosyanızdaki sütunları veritabanı alanları ile eşleştirin. <strong>Ürün Adı</strong> ve <strong>Fiyat</strong> alanları zorunludur.
                    </p>
                    
                    <form method="POST" action="?step=import">
                        <?= csrfField() ?>
                        
                        <table class="admin-table mapping-table" style="margin-bottom: 24px;">
                            <thead>
                                <tr>
                                    <th>CSV Sütunu</th>
                                    <th>Önizleme (1. satır)</th>
                                    <th>→ Veritabanı Alanı</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $colCount = !empty($headers) ? count($headers) : (isset($previewRows[0]) ? count($previewRows[0]) : 0);
                                for ($i = 0; $i < $colCount; $i++): 
                                    $header = $headers[$i] ?? 'Sütun ' . ($i + 1);
                                    $preview = $previewRows[0][$i] ?? '';
                                    $autoMap = autoMapColumn($header, $dbFields);
                                ?>
                                <tr>
                                    <td><strong><?= sanitize($header) ?></strong></td>
                                    <td style="color: var(--gray-500); font-size: 13px;"><?= sanitize(truncate($preview, 50)) ?></td>
                                    <td>
                                        <select name="mapping[<?= $i ?>]" class="form-control" style="width: 100%;">
                                            <?php foreach ($dbFields as $key => $label): ?>
                                            <option value="<?= $key ?>" <?= $autoMap === $key ? 'selected' : '' ?>><?= $label ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>

                        <!-- Preview -->
                        <h4 style="margin-bottom: 12px;">Veri Önizleme (İlk 5 satır)</h4>
                        <div style="overflow-x: auto; margin-bottom: 24px;">
                            <table class="admin-table" style="font-size: 12px;">
                                <thead>
                                    <tr>
                                        <?php for ($i = 0; $i < $colCount; $i++): ?>
                                        <th><?= sanitize($headers[$i] ?? 'Sütun ' . ($i+1)) ?></th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($previewRows, 0, 5) as $row): ?>
                                    <tr>
                                        <?php for ($i = 0; $i < $colCount; $i++): ?>
                                        <td><?= sanitize(truncate($row[$i] ?? '', 40)) ?></td>
                                        <?php endfor; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div style="display: flex; gap: 12px;">
                            <a href="?step=upload" class="btn btn-outline">← Geri</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-play"></i> Import'u Başlat (<?= number_format($totalLines) ?> ürün)
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php elseif ($step === 'import'): ?>
            <!-- Step 3: Processing -->
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) { die('CSRF Error'); }
                
                $importData = $_SESSION['csv_import'] ?? null;
                if (!$importData) { redirect('?step=upload'); }
                
                $mapping = $_POST['mapping'] ?? [];
                $file = $importData['file'];
                $delimiter = $importData['delimiter'];
                $hasHeader = $importData['has_header'];
                $updateExisting = $importData['update_existing'];
                
                // Import log oluştur
                $logId = db()->insert('import_logs', [
                    'filename' => basename($file),
                    'total_rows' => $importData['total_lines'],
                    'status' => 'processing',
                    'imported_by' => $_SESSION['user_id']
                ]);
                
                // Import işlemi
                $handle = fopen($file, 'r');
                if ($hasHeader) fgetcsv($handle, 0, $delimiter); // Skip header
                
                $imported = 0;
                $skipped = 0;
                $errors = [];
                $batch = [];
                $batchCount = 0;
                
                db()->beginTransaction();
                
                try {
                    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                        $data = [];
                        foreach ($mapping as $colIdx => $field) {
                            if (!empty($field) && isset($row[$colIdx])) {
                                $data[$field] = trim($row[$colIdx]);
                            }
                        }
                        
                        // Gerekli alan kontrolü
                        if (empty($data['name'])) {
                            $skipped++;
                            continue;
                        }
                        
                        // Fiyat temizle
                        $price = isset($data['price']) ? (float)str_replace(['.', ',', '₺', ' '], ['', '.', '', ''], $data['price']) : 0;
                        if ($price <= 0) {
                            $skipped++;
                            continue;
                        }
                        
                        // SKU ile mevcut kontrol
                        $existingProduct = null;
                        if (!empty($data['sku'])) {
                            $existingProduct = db()->fetch("SELECT id FROM products WHERE sku = ?", [$data['sku']]);
                        }
                        
                        // Kategori bul veya oluştur
                        $categoryId = null;
                        if (!empty($data['category'])) {
                            $cat = db()->fetch("SELECT id FROM categories WHERE name = ?", [$data['category']]);
                            if ($cat) {
                                $categoryId = $cat['id'];
                            } else {
                                $categoryId = db()->insert('categories', [
                                    'name' => sanitize($data['category']),
                                    'slug' => slugify($data['category']),
                                    'is_active' => 1
                                ]);
                            }
                        }
                        
                        // Marka bul veya oluştur
                        $brandId = null;
                        if (!empty($data['brand'])) {
                            $brand = db()->fetch("SELECT id FROM brands WHERE name = ?", [$data['brand']]);
                            if ($brand) {
                                $brandId = $brand['id'];
                            } else {
                                $brandId = db()->insert('brands', [
                                    'name' => sanitize($data['brand']),
                                    'slug' => slugify($data['brand']),
                                    'is_active' => 1
                                ]);
                            }
                        }
                        
                        $salePrice = isset($data['sale_price']) ? (float)str_replace(['.', ',', '₺', ' '], ['', '.', '', ''], $data['sale_price']) : null;
                        $costPrice = isset($data['cost_price']) ? (float)str_replace(['.', ',', '₺', ' '], ['', '.', '', ''], $data['cost_price']) : null;
                        
                        $productData = [
                            'name' => sanitize($data['name']),
                            'slug' => slugify($data['name']) . '-' . substr(md5($data['sku'] ?? uniqid()), 0, 4),
                            'sku' => !empty($data['sku']) ? sanitize($data['sku']) : null,
                            'barcode' => !empty($data['barcode']) ? sanitize($data['barcode']) : null,
                            'price' => $price,
                            'sale_price' => $salePrice > 0 ? $salePrice : null,
                            'cost_price' => $costPrice > 0 ? $costPrice : null,
                            'stock' => isset($data['stock']) ? (int)$data['stock'] : 0,
                            'category_id' => $categoryId,
                            'brand_id' => $brandId,
                            'description' => !empty($data['description']) ? sanitize($data['description']) : null,
                            'short_description' => !empty($data['short_description']) ? sanitize($data['short_description']) : null,
                            'unit' => !empty($data['unit']) ? sanitize($data['unit']) : 'Adet',
                            'weight' => !empty($data['weight']) ? (float)$data['weight'] : null,
                            'is_active' => 1,
                        ];
                        
                        if ($existingProduct && $updateExisting) {
                            unset($productData['slug']);
                            db()->update('products', $productData, 'id = ?', [$existingProduct['id']]);
                            $imported++;
                        } elseif (!$existingProduct) {
                            db()->insert('products', $productData);
                            $imported++;
                        } else {
                            $skipped++;
                        }
                        
                        $batchCount++;
                    }
                    
                    db()->commit();
                    
                    // Log güncelle
                    db()->update('import_logs', [
                        'imported_rows' => $imported,
                        'skipped_rows' => $skipped,
                        'error_rows' => count($errors),
                        'errors' => !empty($errors) ? json_encode(array_slice($errors, 0, 100)) : null,
                        'status' => 'completed',
                        'completed_at' => date('Y-m-d H:i:s')
                    ], 'id = ?', [$logId]);
                    
                } catch (Exception $e) {
                    db()->rollBack();
                    db()->update('import_logs', [
                        'status' => 'failed',
                        'errors' => json_encode(['Genel hata: ' . $e->getMessage()])
                    ], 'id = ?', [$logId]);
                    $errors[] = $e->getMessage();
                }
                
                fclose($handle);
                unset($_SESSION['csv_import']);
                
                // Sonuç sayfasına yönlendir
                $_SESSION['import_result'] = [
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'errors' => count($errors),
                    'total' => $importData['total_lines'],
                    'log_id' => $logId
                ];
                redirect('?step=result');
            }
            ?>

            <?php elseif ($step === 'result'): ?>
            <!-- Step 4: Results -->
            <?php $result = $_SESSION['import_result'] ?? ['imported' => 0, 'skipped' => 0, 'errors' => 0, 'total' => 0]; unset($_SESSION['import_result']); ?>
            
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-check-circle" style="color: var(--success);"></i> Import Tamamlandı!</h3>
                </div>
                <div class="card-body" style="text-align: center; padding: 48px;">
                    <div style="font-size: 64px; color: var(--success); margin-bottom: 16px;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 style="margin-bottom: 24px;">Import İşlemi Başarıyla Tamamlandı</h2>
                    
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; max-width: 600px; margin: 0 auto 32px;">
                        <div style="background: #D4EDDA; padding: 20px; border-radius: 10px;">
                            <div style="font-size: 32px; font-weight: 800; color: #155724;"><?= number_format($result['imported']) ?></div>
                            <div style="font-size: 14px; color: #155724;">Başarılı Import</div>
                        </div>
                        <div style="background: #FFF3CD; padding: 20px; border-radius: 10px;">
                            <div style="font-size: 32px; font-weight: 800; color: #856404;"><?= number_format($result['skipped']) ?></div>
                            <div style="font-size: 14px; color: #856404;">Atlanan</div>
                        </div>
                        <div style="background: #F8D7DA; padding: 20px; border-radius: 10px;">
                            <div style="font-size: 32px; font-weight: 800; color: #721C24;"><?= number_format($result['errors']) ?></div>
                            <div style="font-size: 14px; color: #721C24;">Hata</div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; justify-content: center;">
                        <a href="<?= SITE_URL ?>/admin/products.php" class="btn btn-primary"><i class="fas fa-box"></i> Ürünleri Gör</a>
                        <a href="?step=upload" class="btn btn-outline"><i class="fas fa-upload"></i> Yeni Import</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
    <script>
    // File input display
    const csvFile = document.getElementById('csvFile');
    const fileName = document.getElementById('fileName');
    if (csvFile) {
        csvFile.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileName.textContent = '📄 ' + this.files[0].name + ' (' + (this.files[0].size / 1024 / 1024).toFixed(1) + ' MB)';
                fileName.style.display = 'block';
            }
        });
    }
    </script>
</body>
</html>

<?php
// Auto-map CSV column names to DB fields
function autoMapColumn($header, $dbFields) {
    $header = mb_strtolower(trim($header), 'UTF-8');
    $map = [
        'name' => ['ürün adı', 'ürün_adı', 'urun_adi', 'product_name', 'name', 'ad', 'ürün', 'urun', 'mal_adi'],
        'sku' => ['sku', 'stok_kodu', 'stok kodu', 'kod', 'product_code', 'code', 'urun_kodu'],
        'barcode' => ['barkod', 'barcode', 'barkot', 'ean'],
        'price' => ['fiyat', 'price', 'satis_fiyati', 'satış fiyatı', 'birim_fiyat'],
        'sale_price' => ['indirimli_fiyat', 'indirimli fiyat', 'sale_price', 'kampanya_fiyat'],
        'cost_price' => ['maliyet', 'cost', 'alis_fiyati', 'alış fiyatı'],
        'stock' => ['stok', 'stock', 'miktar', 'adet', 'quantity', 'stok_miktari'],
        'category' => ['kategori', 'category', 'grup', 'group', 'kat'],
        'brand' => ['marka', 'brand', 'üretici', 'manufacturer'],
        'description' => ['açıklama', 'aciklama', 'description', 'detay'],
        'short_description' => ['kısa_açıklama', 'kisa_aciklama', 'short_description'],
        'unit' => ['birim', 'unit', 'ölçü'],
        'weight' => ['ağırlık', 'agirlik', 'weight', 'kg'],
    ];
    
    foreach ($map as $field => $aliases) {
        foreach ($aliases as $alias) {
            if ($header === $alias || strpos($header, $alias) !== false) {
                return $field;
            }
        }
    }
    return '';
}
?>
