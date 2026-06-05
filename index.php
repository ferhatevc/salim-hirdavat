<?php
/**
 * Salim Hırdavat - Ana Sayfa
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = SITE_NAME . ' - Sivas\'ın En Büyük Hırdavat Mağazası';
$pageDescription = 'Salim Hırdavat ile Sivas\'ta 25.000+ ürün, uygun fiyat, hızlı kurye teslimat. Elektrik, hırdavat, boya, tesisat, nalburiye ve daha fazlası.';

// Veriler
$categories = db()->fetchAll("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order LIMIT 12");
$featuredProducts = db()->fetchAll(
    "SELECT p.*, c.name as category_name,
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE p.is_active = 1 AND p.is_featured = 1 
     ORDER BY p.created_at DESC LIMIT 8"
);
$newProducts = db()->fetchAll(
    "SELECT p.*, c.name as category_name,
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE p.is_active = 1 AND p.is_new = 1 
     ORDER BY p.created_at DESC LIMIT 8"
);
$saleProducts = db()->fetchAll(
    "SELECT p.*, c.name as category_name,
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE p.is_active = 1 AND p.sale_price IS NOT NULL AND p.sale_price > 0 AND p.sale_price < p.price 
     ORDER BY ((p.price - p.sale_price) / p.price) DESC LIMIT 8"
);
$sliders = db()->fetchAll("SELECT * FROM sliders WHERE is_active = 1 ORDER BY sort_order");

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Slider -->
<section class="hero" id="heroSlider">
    <div class="hero-slider" id="heroSliderTrack">
        <?php if (empty($sliders)): ?>
        <!-- Varsayılan Slider -->
        <div class="hero-slide" style="background: linear-gradient(135deg, #1A2332, #2D3B4F);">
            <div class="container">
                <div class="hero-content">
                    <span class="badge badge-featured">🔥 Sivas'ın 1 Numarası</span>
                    <h1>Hırdavattan <span>Her Şey</span> Bir Tık Uzağınızda</h1>
                    <p>25.000'den fazla ürün ile Sivas ve çevresine hızlı kurye teslimat. Kaliteli malzeme, uygun fiyat, güvenilir hizmet.</p>
                    <div class="hero-actions">
                        <a href="<?= SITE_URL ?>/urunler" class="btn btn-primary btn-xl">
                            <i class="fas fa-shopping-bag"></i> Alışverişe Başla
                        </a>
                        <a href="<?= SITE_URL ?>/urunler?sale=1" class="btn btn-outline btn-xl" style="border-color: white; color: white;">
                            <i class="fas fa-percent"></i> İndirimli Ürünler
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-slide" style="background: linear-gradient(135deg, #CC0000, #A30000);">
            <div class="container">
                <div class="hero-content">
                    <span class="badge" style="background: #1A2332;">⚡ Hızlı Teslimat</span>
                    <h1>Sivas'ta <span style="color: #1A2332;">Aynı Gün</span> Kurye Teslimat</h1>
                    <p>Siparişinizi verin, kuryemiz kapınıza kadar gelsin. Sivas merkez ve ilçelere hızlı, güvenli teslimat.</p>
                    <div class="hero-actions">
                        <a href="<?= SITE_URL ?>/urunler" class="btn btn-secondary btn-xl">
                            <i class="fas fa-truck"></i> Hemen Sipariş Ver
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-slide" style="background: linear-gradient(135deg, #1A2332, #0D1520);">
            <div class="container">
                <div class="hero-content">
                    <span class="badge badge-sale">💰 Büyük Fırsatlar</span>
                    <h1>Toptan ve <span>Perakende</span> Uygun Fiyatlar</h1>
                    <p>İnşaat, tadilat ve tamirat ihtiyaçlarınız için en uygun fiyatlar. Toplu alımlarda ekstra indirimler.</p>
                    <div class="hero-actions">
                        <a href="<?= SITE_URL ?>/iletisim" class="btn btn-primary btn-xl">
                            <i class="fas fa-headset"></i> Teklif Alın
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
            <?php foreach ($sliders as $slide): ?>
            <div class="hero-slide">
                <?php if ($slide['image']): ?>
                <img src="<?= SITE_URL ?>/uploads/sliders/<?= $slide['image'] ?>" class="hero-slide-bg" alt="<?= sanitize($slide['title']) ?>">
                <?php endif; ?>
                <div class="container">
                    <div class="hero-content">
                        <?php if ($slide['subtitle']): ?>
                        <span class="badge badge-featured"><?= sanitize($slide['subtitle']) ?></span>
                        <?php endif; ?>
                        <h1><?= $slide['title'] ?></h1>
                        <?php if ($slide['link']): ?>
                        <div class="hero-actions">
                            <a href="<?= $slide['link'] ?>" class="btn btn-primary btn-xl">
                                <?= $slide['button_text'] ?? 'İncele' ?> <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="hero-arrow prev" onclick="moveSlider(-1)"><i class="fas fa-chevron-left"></i></div>
    <div class="hero-arrow next" onclick="moveSlider(1)"><i class="fas fa-chevron-right"></i></div>
    
    <div class="hero-dots" id="heroDots"></div>
</section>

<!-- Categories Section -->
<section class="section" style="background: var(--white);">
    <div class="container">
        <div class="section-header">
            <h2>Ürün Kategorileri</h2>
            <p>İhtiyacınız olan her şey tek bir yerde</p>
        </div>
        <div class="grid grid-6 category-grid">
            <?php foreach ($categories as $cat): ?>
            <a href="<?= SITE_URL ?>/kategori/<?= $cat['slug'] ?>" class="category-card">
                <div class="category-card-icon">
                    <i class="fas <?= $cat['icon'] ?? 'fa-tag' ?>"></i>
                </div>
                <h4><?= sanitize($cat['name']) ?></h4>
                <p><?= $cat['product_count'] ?? 0 ?> ürün</p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<?php if (!empty($featuredProducts)): ?>
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Öne Çıkan Ürünler</h2>
            <p>En çok tercih edilen ve popüler ürünlerimiz</p>
        </div>
        <div class="product-grid">
            <?php foreach ($featuredProducts as $product): ?>
            <?php include __DIR__ . '/includes/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?= SITE_URL ?>/urunler?featured=1" class="btn btn-outline btn-lg">
                Tüm Öne Çıkanları Gör <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Why Choose Us -->
<section class="section" style="background: var(--white);">
    <div class="container">
        <div class="section-header">
            <h2>Neden Salim Hırdavat?</h2>
            <p>Sivas'ın güvenilir hırdavat partneri</p>
        </div>
        <div class="grid grid-4 features-grid">
            <div class="feature-card">
                <div class="feature-card-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <h4>Hızlı Kurye Teslimat</h4>
                <p>Sivas merkeze aynı gün, ilçelere 1-3 gün içinde kurye ile kapınıza teslim.</p>
            </div>
            <div class="feature-card">
                <div class="feature-card-icon">
                    <i class="fas fa-boxes-stacked"></i>
                </div>
                <h4>25.000+ Ürün</h4>
                <p>Elektrik, tesisat, boya, nalburiye, makine ve daha fazlası tek çatı altında.</p>
            </div>
            <div class="feature-card">
                <div class="feature-card-icon">
                    <i class="fas fa-hand-holding-dollar"></i>
                </div>
                <h4>Uygun Fiyatlar</h4>
                <p>Toptan ve perakende en uygun fiyat garantisi. Toplu alımlarda ekstra indirim.</p>
            </div>
            <div class="feature-card">
                <div class="feature-card-icon">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <h4>Güvenli Ödeme</h4>
                <p>iyzico güvencesiyle online ödeme veya kapıda nakit/kart ile ödeme imkanı.</p>
            </div>
        </div>
    </div>
</section>

<!-- Sale Products -->
<?php if (!empty($saleProducts)): ?>
<section class="section" style="background: linear-gradient(135deg, var(--dark) 0%, #2D3B4F 100%);">
    <div class="container">
        <div class="section-header">
            <h2 style="color: var(--white);">🔥 İndirimli Ürünler</h2>
            <p style="color: var(--gray-400);">Kaçırılmayacak fırsatlar sizi bekliyor</p>
        </div>
        <div class="product-grid">
            <?php foreach ($saleProducts as $product): ?>
            <?php include __DIR__ . '/includes/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?= SITE_URL ?>/urunler?sale=1" class="btn btn-primary btn-lg">
                Tüm İndirimleri Gör <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- New Products -->
<?php if (!empty($newProducts)): ?>
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Yeni Ürünler</h2>
            <p>Mağazamıza yeni eklenen ürünleri keşfedin</p>
        </div>
        <div class="product-grid">
            <?php foreach ($newProducts as $product): ?>
            <?php include __DIR__ . '/includes/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?= SITE_URL ?>/urunler?sort=newest" class="btn btn-outline btn-lg">
                Tüm Yeni Ürünleri Gör <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA Banner -->
<section class="section" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; text-align: center;">
    <div class="container">
        <h2 style="color: white; font-size: 2.5rem;">Toplu Alım mı Yapacaksınız?</h2>
        <p style="color: rgba(255,255,255,0.9); font-size: 1.125rem; max-width: 600px; margin: 0 auto 2rem;">
            İnşaat, tadilat veya toptan alımlar için özel fiyat teklifi alın. Hemen bizi arayın!
        </p>
        <div class="flex-center gap-md" style="flex-wrap: wrap;">
            <a href="tel:<?= SITE_PHONE ?>" class="btn btn-white btn-xl">
                <i class="fas fa-phone-alt"></i> <?= SITE_PHONE ?>
            </a>
            <a href="https://wa.me/<?= SITE_WHATSAPP ?>" class="btn btn-secondary btn-xl" target="_blank">
                <i class="fab fa-whatsapp"></i> WhatsApp'tan Yazın
            </a>
        </div>
    </div>
</section>

<!-- Hero Slider Script -->
<script>
let currentSlide = 0;
const slider = document.getElementById('heroSliderTrack');
const slides = slider ? slider.children : [];
const dotsContainer = document.getElementById('heroDots');

// Dot'ları oluştur
if (dotsContainer && slides.length > 0) {
    for (let i = 0; i < slides.length; i++) {
        const dot = document.createElement('div');
        dot.className = 'hero-dot' + (i === 0 ? ' active' : '');
        dot.onclick = () => goToSlide(i);
        dotsContainer.appendChild(dot);
    }
}

function goToSlide(index) {
    if (!slider || slides.length === 0) return;
    currentSlide = index;
    slider.style.transform = `translateX(-${currentSlide * 100}%)`;
    document.querySelectorAll('.hero-dot').forEach((d, i) => {
        d.classList.toggle('active', i === currentSlide);
    });
}

function moveSlider(direction) {
    let next = currentSlide + direction;
    if (next < 0) next = slides.length - 1;
    if (next >= slides.length) next = 0;
    goToSlide(next);
}

// Auto-play
if (slides.length > 1) {
    setInterval(() => moveSlider(1), 5000);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
