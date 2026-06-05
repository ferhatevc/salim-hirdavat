<?php
/**
 * Salim Hırdavat - Header
 */
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/functions.php';
}

$categories = getCategoryTree();
$cartCount = getCartCount();
$user = currentUser();
$pageTitle = $pageTitle ?? SITE_NAME . ' - ' . SITE_DESCRIPTION;
$pageDescription = $pageDescription ?? 'Sivas\'ın en büyük hırdavat mağazası. 25.000+ ürün, uygun fiyatlar, hızlı kurye teslimat. Elektrik, tesisat, boya, nalburiye ve daha fazlası.';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= sanitize($pageDescription) ?>">
    <meta name="keywords" content="hırdavat, sivas, nalburiye, elektrik malzemeleri, boya, tesisat, inşaat malzemeleri, salim hırdavat">
    <meta name="author" content="Salim Hırdavat">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= sanitize($pageTitle) ?>">
    <meta property="og:description" content="<?= sanitize($pageDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= currentUrl() ?>">
    <meta property="og:site_name" content="<?= SITE_NAME ?>">
    <meta property="og:locale" content="tr_TR">
    
    <title><?= sanitize($pageTitle) ?></title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/responsive.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/favicon.png">
    <link rel="apple-touch-icon" href="<?= SITE_URL ?>/assets/images/favicon.png">
</head>
<body>
    <!-- Top Bar -->
    <div class="topbar">
        <div class="container">
            <div class="topbar-left">
                <a href="tel:<?= SITE_PHONE ?>">
                    <i class="fas fa-phone-alt"></i> <?= SITE_PHONE ?>
                </a>
                <a href="mailto:<?= SITE_EMAIL ?>">
                    <i class="fas fa-envelope"></i> <?= SITE_EMAIL ?>
                </a>
            </div>
            <div class="topbar-right">
                <span><i class="fas fa-clock"></i> Pazartesi - Cumartesi: 08:00 - 19:00</span>
                <span><i class="fas fa-map-marker-alt"></i> <?= SITE_CITY ?></span>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="header" id="mainHeader">
        <div class="container">
            <div class="header-main">
                <!-- Mobile Menu Button -->
                <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Menü">
                    <i class="fas fa-bars"></i>
                </button>
                
                <!-- Logo -->
                <a href="<?= SITE_URL ?>" class="logo">
                    <img src="<?= SITE_URL ?>/assets/images/logo.png" alt="Salim Hırdavat" class="logo-img" style="height: 52px; width: auto;">
                </a>
                
                <!-- Search Bar -->
                <div class="search-bar" id="searchBar">
                    <form action="<?= SITE_URL ?>/ara" method="GET" id="searchForm">
                        <input type="text" name="q" id="searchInput" placeholder="25.000+ üründe ara... (ürün adı, marka veya kod)" autocomplete="off">
                        <button type="submit" aria-label="Ara"><i class="fas fa-search"></i></button>
                    </form>
                    <div class="search-results" id="searchResults"></div>
                </div>
                
                <!-- Header Actions -->
                <div class="header-actions">
                    <!-- Mobile Search -->
                    <button class="header-action-btn mobile-search-btn d-none" id="mobileSearchBtn" aria-label="Ara">
                        <i class="fas fa-search"></i>
                    </button>
                    
                    <!-- Wishlist -->
                    <?php if ($user): ?>
                    <a href="<?= SITE_URL ?>/hesabim?tab=favoriler" class="header-action-btn" title="Favoriler">
                        <i class="far fa-heart"></i>
                        <span>Favoriler</span>
                    </a>
                    <?php endif; ?>
                    
                    <!-- User Menu -->
                    <div class="user-dropdown" id="userDropdown">
                        <button class="header-action-btn" id="userDropdownBtn">
                            <i class="far fa-user"></i>
                            <span><?= $user ? sanitize($user['first_name']) : 'Hesabım' ?></span>
                        </button>
                        <div class="user-dropdown-menu" id="userDropdownMenu">
                            <?php if ($user): ?>
                                <a href="<?= SITE_URL ?>/hesabim"><i class="fas fa-user"></i> Hesabım</a>
                                <a href="<?= SITE_URL ?>/siparislerim"><i class="fas fa-box"></i> Siparişlerim</a>
                                <a href="<?= SITE_URL ?>/hesabim?tab=adresler"><i class="fas fa-map-marker-alt"></i> Adreslerim</a>
                                <div class="user-dropdown-divider"></div>
                                <?php if (isAdmin()): ?>
                                <a href="<?= SITE_URL ?>/admin/"><i class="fas fa-cog"></i> Admin Panel</a>
                                <div class="user-dropdown-divider"></div>
                                <?php endif; ?>
                                <a href="<?= SITE_URL ?>/pages/login.php?logout=1"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
                            <?php else: ?>
                                <a href="<?= SITE_URL ?>/giris"><i class="fas fa-sign-in-alt"></i> Giriş Yap</a>
                                <a href="<?= SITE_URL ?>/kayit"><i class="fas fa-user-plus"></i> Kayıt Ol</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Cart -->
                    <a href="<?= SITE_URL ?>/sepet" class="header-action-btn cart-btn" id="cartBtn">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Sepet</span>
                        <?php if ($cartCount > 0): ?>
                        <span class="badge" id="cartBadge"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation Bar -->
    <nav class="navbar" id="navbar">
        <div class="container">
            <!-- Categories Button -->
            <button class="nav-categories-btn" id="categoriesBtn">
                <i class="fas fa-bars"></i>
                <span>Tüm Kategoriler</span>
                <i class="fas fa-chevron-down" style="font-size: 10px; margin-left: 4px;"></i>
            </button>
            
            <!-- Nav Links -->
            <div class="nav-links">
                <a href="<?= SITE_URL ?>" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' && !isset($_GET['page']) ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Ana Sayfa
                </a>
                <a href="<?= SITE_URL ?>/urunler">
                    <i class="fas fa-th-large"></i> Tüm Ürünler
                </a>
                <a href="<?= SITE_URL ?>/urunler?featured=1">
                    <i class="fas fa-star"></i> Öne Çıkanlar
                </a>
                <a href="<?= SITE_URL ?>/urunler?sale=1">
                    <i class="fas fa-percent"></i> İndirimli
                </a>
                <a href="<?= SITE_URL ?>/iletisim">
                    <i class="fas fa-headset"></i> İletişim
                </a>
                <a href="<?= SITE_URL ?>/hakkimizda">
                    <i class="fas fa-info-circle"></i> Hakkımızda
                </a>
            </div>
        </div>
        
        <!-- Mega Menu -->
        <div class="mega-menu" id="megaMenu">
            <div class="mega-menu-inner">
                <?php foreach (array_slice($categories, 0, 12) as $cat): ?>
                <div class="mega-menu-category">
                    <h4>
                        <?php if (!empty($cat['icon'])): ?>
                        <i class="fas <?= $cat['icon'] ?>"></i>
                        <?php endif; ?>
                        <a href="<?= SITE_URL ?>/kategori/<?= $cat['slug'] ?>"><?= sanitize($cat['name']) ?></a>
                    </h4>
                    <?php if (!empty($cat['children'])): ?>
                    <ul>
                        <?php foreach (array_slice($cat['children'], 0, 6) as $child): ?>
                        <li>
                            <a href="<?= SITE_URL ?>/kategori/<?= $child['slug'] ?>"><?= sanitize($child['name']) ?></a>
                        </li>
                        <?php endforeach; ?>
                        <?php if (count($cat['children']) > 6): ?>
                        <li>
                            <a href="<?= SITE_URL ?>/kategori/<?= $cat['slug'] ?>" style="color: var(--primary); font-weight: 600;">
                                Tümünü Gör →
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-header">
            <div class="logo-text" style="font-size: 1rem;">Salim <span style="color: var(--primary);">Hırdavat</span></div>
            <button class="mobile-menu-close" id="mobileMenuClose"><i class="fas fa-times"></i></button>
        </div>
        
        <!-- Mobile Search -->
        <div style="padding: 16px;">
            <form action="<?= SITE_URL ?>/ara" method="GET" class="search-bar" style="max-width: none;">
                <div style="display: flex; border: 2px solid var(--gray-200); border-radius: 999px; overflow: hidden;">
                    <input type="text" name="q" placeholder="Ürün ara..." style="flex: 1; padding: 10px 16px; border: none; outline: none;">
                    <button type="submit" style="padding: 10px 16px; background: var(--primary); color: white; border: none;"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
        
        <nav>
            <a href="<?= SITE_URL ?>"><i class="fas fa-home"></i> Ana Sayfa</a>
            <a href="<?= SITE_URL ?>/urunler"><i class="fas fa-th-large"></i> Tüm Ürünler</a>
            <?php foreach (array_slice($categories, 0, 12) as $cat): ?>
            <a href="<?= SITE_URL ?>/kategori/<?= $cat['slug'] ?>">
                <i class="fas <?= $cat['icon'] ?? 'fa-tag' ?>"></i> <?= sanitize($cat['name']) ?>
            </a>
            <?php endforeach; ?>
            <a href="<?= SITE_URL ?>/iletisim"><i class="fas fa-headset"></i> İletişim</a>
            <a href="<?= SITE_URL ?>/hakkimizda"><i class="fas fa-info-circle"></i> Hakkımızda</a>
            <?php if (!$user): ?>
            <a href="<?= SITE_URL ?>/giris"><i class="fas fa-sign-in-alt"></i> Giriş Yap</a>
            <a href="<?= SITE_URL ?>/kayit"><i class="fas fa-user-plus"></i> Kayıt Ol</a>
            <?php else: ?>
            <a href="<?= SITE_URL ?>/hesabim"><i class="fas fa-user"></i> Hesabım</a>
            <a href="<?= SITE_URL ?>/siparislerim"><i class="fas fa-box"></i> Siparişlerim</a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Flash Messages -->
    <div class="container" style="margin-top: 16px;">
        <?= showFlashMessages() ?>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Main Content -->
    <main id="mainContent">
