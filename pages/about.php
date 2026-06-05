<?php
/**
 * Salim Hırdavat - Hakkımızda
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Hakkımızda - ' . SITE_NAME;
$productCount = db()->count('products', 'is_active = 1');
$categoryCount = db()->count('categories', 'is_active = 1');

require_once __DIR__ . '/../includes/header.php';
?>

<?= renderBreadcrumb([['name' => 'Hakkımızda']]) ?>

<!-- Hero Section -->
<section style="background: linear-gradient(135deg, var(--dark), var(--dark-light)); color: white; padding: var(--space-4xl) 0; text-align: center;">
    <div class="container">
        <h1 style="color: white; font-size: var(--text-4xl); margin-bottom: var(--space-md);">Sivas'ın Güvenilir <span style="color: var(--primary);">Hırdavat</span> Partneri</h1>
        <p style="max-width: 700px; margin: 0 auto; color: var(--gray-300); font-size: var(--text-lg); line-height: 1.8;">
            Yılların tecrübesiyle Sivas ve çevresine hizmet veren Salim Hırdavat, geniş ürün yelpazesi ve uygun fiyatlarıyla ihtiyaçlarınızı karşılıyor.
        </p>
    </div>
</section>

<!-- Stats -->
<section style="background: var(--white); padding: var(--space-3xl) 0; margin-top: -30px; position: relative; z-index: 2;">
    <div class="container">
        <div class="grid grid-4" style="max-width: 900px; margin: 0 auto;">
            <div style="text-align: center;">
                <div style="font-family: var(--font-heading); font-size: 3rem; font-weight: 800; color: var(--primary);">25.000+</div>
                <div style="color: var(--gray-500); font-weight: 600;">Ürün Çeşidi</div>
            </div>
            <div style="text-align: center;">
                <div style="font-family: var(--font-heading); font-size: 3rem; font-weight: 800; color: var(--primary);"><?= $categoryCount ?></div>
                <div style="color: var(--gray-500); font-weight: 600;">Kategori</div>
            </div>
            <div style="text-align: center;">
                <div style="font-family: var(--font-heading); font-size: 3rem; font-weight: 800; color: var(--primary);">1000+</div>
                <div style="color: var(--gray-500); font-weight: 600;">Mutlu Müşteri</div>
            </div>
            <div style="text-align: center;">
                <div style="font-family: var(--font-heading); font-size: 3rem; font-weight: 800; color: var(--primary);">7/24</div>
                <div style="color: var(--gray-500); font-weight: 600;">Destek</div>
            </div>
        </div>
    </div>
</section>

<!-- Mission -->
<section class="section">
    <div class="container">
        <div class="row" style="align-items: center;">
            <div class="col-6">
                <h2>Misyonumuz</h2>
                <p style="font-size: var(--text-lg); line-height: 1.8; color: var(--gray-600);">
                    Sivas ve çevre illerdeki ev sahipleri, ustalar ve inşaat profesyonellerine en kaliteli hırdavat ve yapı malzemelerini en uygun fiyatlarla sunmak. 
                    Online alışveriş kolaylığı ile hızlı kurye teslimat hizmeti sunarak müşterilerimizin zamandan tasarruf etmesini sağlamak.
                </p>
                <div class="grid grid-2 mt-3" style="gap: var(--space-md);">
                    <div class="flex gap-sm" style="align-items: start;">
                        <i class="fas fa-check-circle" style="color: var(--success); margin-top: 4px;"></i>
                        <span>Geniş ürün yelpazesi</span>
                    </div>
                    <div class="flex gap-sm" style="align-items: start;">
                        <i class="fas fa-check-circle" style="color: var(--success); margin-top: 4px;"></i>
                        <span>Uygun fiyat garantisi</span>
                    </div>
                    <div class="flex gap-sm" style="align-items: start;">
                        <i class="fas fa-check-circle" style="color: var(--success); margin-top: 4px;"></i>
                        <span>Hızlı kurye teslimat</span>
                    </div>
                    <div class="flex gap-sm" style="align-items: start;">
                        <i class="fas fa-check-circle" style="color: var(--success); margin-top: 4px;"></i>
                        <span>Profesyonel destek</span>
                    </div>
                </div>
            </div>
            <div class="col-6" style="text-align: center;">
                <div style="background: linear-gradient(135deg, var(--primary-bg), rgba(255, 107, 0, 0.15)); border-radius: var(--radius-xl); padding: var(--space-4xl); display: inline-block;">
                    <i class="fas fa-tools" style="font-size: 120px; color: var(--primary); opacity: 0.6;"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="section" style="background: var(--white);">
    <div class="container">
        <div class="section-header">
            <h2>Neden Bizi Tercih Etmelisiniz?</h2>
        </div>
        <div class="grid grid-3" style="gap: var(--space-xl);">
            <div class="feature-card">
                <div class="feature-card-icon"><i class="fas fa-truck-fast"></i></div>
                <h4>Hızlı Kurye Teslimat</h4>
                <p>Sivas merkezde aynı gün teslimat imkanı. İlçelere 1-3 iş gününde ulaştırma.</p>
            </div>
            <div class="feature-card">
                <div class="feature-card-icon"><i class="fas fa-tags"></i></div>
                <h4>En Uygun Fiyatlar</h4>
                <p>Piyasanın en uygun fiyatlarıyla hizmet veriyoruz. Toplu alımlarda ekstra indirimler.</p>
            </div>
            <div class="feature-card">
                <div class="feature-card-icon"><i class="fas fa-headset"></i></div>
                <h4>Profesyonel Destek</h4>
                <p>Teknik konularda uzman kadromuzdan danışmanlık hizmeti alabilirsiniz.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; padding: var(--space-3xl) 0; text-align: center;">
    <div class="container">
        <h2 style="color: white;">Hemen Alışverişe Başlayın!</h2>
        <p style="color: rgba(255,255,255,0.9); max-width: 500px; margin: 0 auto var(--space-xl);">
            25.000'den fazla ürünü keşfedin ve kapınıza kadar teslim edelim.
        </p>
        <a href="<?= SITE_URL ?>/urunler" class="btn btn-white btn-xl">
            <i class="fas fa-shopping-bag"></i> Ürünleri İnceleyin
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
