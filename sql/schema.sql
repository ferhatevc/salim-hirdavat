-- =====================================================
-- Salim Hırdavat - E-Ticaret Veritabanı Şeması
-- Sivas - Hırdavat & Yapı Malzemeleri
-- =====================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET COLLATION_CONNECTION = utf8mb4_unicode_ci;

CREATE DATABASE IF NOT EXISTS salim_hirdavat
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE salim_hirdavat;

-- =====================================================
-- 1. KATEGORİLER
-- =====================================================
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id INT UNSIGNED DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    image VARCHAR(500) DEFAULT NULL,
    icon VARCHAR(100) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    meta_title VARCHAR(255) DEFAULT NULL,
    meta_description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_parent (parent_id),
    INDEX idx_slug (slug),
    INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. MARKALAR
-- =====================================================
CREATE TABLE brands (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    logo VARCHAR(500) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. ÜRÜNLER
-- =====================================================
CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED DEFAULT NULL,
    brand_id INT UNSIGNED DEFAULT NULL,
    name VARCHAR(500) NOT NULL,
    slug VARCHAR(500) NOT NULL UNIQUE,
    sku VARCHAR(100) DEFAULT NULL,
    barcode VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    short_description VARCHAR(500) DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    sale_price DECIMAL(10,2) DEFAULT NULL,
    cost_price DECIMAL(10,2) DEFAULT NULL,
    stock INT DEFAULT 0,
    min_stock INT DEFAULT 5,
    unit VARCHAR(50) DEFAULT 'Adet',
    weight DECIMAL(8,3) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    is_new TINYINT(1) DEFAULT 0,
    view_count INT UNSIGNED DEFAULT 0,
    sale_count INT UNSIGNED DEFAULT 0,
    meta_title VARCHAR(255) DEFAULT NULL,
    meta_description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_brand (brand_id),
    INDEX idx_sku (sku),
    INDEX idx_barcode (barcode),
    INDEX idx_active (is_active),
    INDEX idx_featured (is_featured, is_active),
    INDEX idx_price (price),
    INDEX idx_sale_price (sale_price),
    INDEX idx_created (created_at),
    INDEX idx_sale_count (sale_count),
    FULLTEXT idx_search (name, description, short_description, sku, barcode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. ÜRÜN GÖRSELLERİ
-- =====================================================
CREATE TABLE product_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255) DEFAULT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_primary (product_id, is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. ÜRÜN ÖZELLİKLERİ
-- =====================================================
CREATE TABLE product_attributes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    attribute_name VARCHAR(255) NOT NULL,
    attribute_value VARCHAR(500) NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. KULLANICILAR
-- =====================================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role ENUM('customer', 'admin', 'courier') DEFAULT 'customer',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    tc_no VARCHAR(11) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    email_verified TINYINT(1) DEFAULT 0,
    last_login TIMESTAMP NULL DEFAULT NULL,
    remember_token VARCHAR(255) DEFAULT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expires TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. ADRESLER
-- =====================================================
CREATE TABLE addresses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(100) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    city VARCHAR(100) NOT NULL DEFAULT 'Sivas',
    district VARCHAR(100) NOT NULL,
    neighborhood VARCHAR(200) DEFAULT NULL,
    full_address TEXT NOT NULL,
    postal_code VARCHAR(10) DEFAULT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. TESLİMAT BÖLGELERİ (Sivas İlçeleri)
-- =====================================================
CREATE TABLE delivery_zones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    districts TEXT NOT NULL COMMENT 'Virgülle ayrılmış ilçe/mahalle isimleri',
    delivery_fee DECIMAL(10,2) DEFAULT 0.00,
    min_order_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Ücretsiz teslimat için minimum tutar',
    estimated_delivery_time VARCHAR(100) DEFAULT NULL COMMENT 'Örn: 1-2 saat',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. KURYELER
-- =====================================================
CREATE TABLE couriers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    zone_id INT UNSIGNED DEFAULT NULL,
    vehicle_type ENUM('motor', 'araba', 'minibus') DEFAULT 'motor',
    vehicle_plate VARCHAR(20) DEFAULT NULL,
    is_available TINYINT(1) DEFAULT 1,
    current_lat DECIMAL(10,8) DEFAULT NULL,
    current_lng DECIMAL(11,8) DEFAULT NULL,
    total_deliveries INT UNSIGNED DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 5.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (zone_id) REFERENCES delivery_zones(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_zone (zone_id),
    INDEX idx_available (is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. SİPARİŞLER
-- =====================================================
CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    address_id INT UNSIGNED DEFAULT NULL,
    courier_id INT UNSIGNED DEFAULT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending', 'confirmed', 'preparing', 'courier_assigned', 'on_delivery', 'delivered', 'cancelled', 'returned') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method ENUM('online', 'door_cash', 'door_card') DEFAULT 'online',
    iyzico_payment_id VARCHAR(255) DEFAULT NULL,
    iyzico_conversation_id VARCHAR(255) DEFAULT NULL,
    coupon_code VARCHAR(50) DEFAULT NULL,
    delivery_address TEXT DEFAULT NULL COMMENT 'Anlık kopya',
    delivery_notes TEXT DEFAULT NULL,
    estimated_delivery TIMESTAMP NULL DEFAULT NULL,
    delivered_at TIMESTAMP NULL DEFAULT NULL,
    cancelled_at TIMESTAMP NULL DEFAULT NULL,
    cancel_reason TEXT DEFAULT NULL,
    invoice_number VARCHAR(50) DEFAULT NULL,
    invoice_created TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (address_id) REFERENCES addresses(id) ON DELETE SET NULL,
    FOREIGN KEY (courier_id) REFERENCES couriers(id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_courier (courier_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11. SİPARİŞ KALEMLERİ
-- =====================================================
CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    product_name VARCHAR(500) NOT NULL COMMENT 'Anlık kopya',
    product_sku VARCHAR(100) DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 12. SEPET
-- =====================================================
CREATE TABLE cart (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    session_id VARCHAR(255) DEFAULT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id),
    UNIQUE KEY unique_cart_item (user_id, product_id),
    UNIQUE KEY unique_cart_session (session_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 13. FAVORİLER
-- =====================================================
CREATE TABLE wishlist (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 14. İNDİRİM KUPONLARI
-- =====================================================
CREATE TABLE coupons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0.00,
    max_discount DECIMAL(10,2) DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    starts_at TIMESTAMP NULL DEFAULT NULL,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 15. SİPARİŞ DURUM GEÇMİŞİ
-- =====================================================
CREATE TABLE order_status_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    status VARCHAR(50) NOT NULL,
    note TEXT DEFAULT NULL,
    changed_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 16. E-FATURA
-- =====================================================
CREATE TABLE invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    invoice_type ENUM('e_arsiv', 'e_fatura') DEFAULT 'e_arsiv',
    buyer_name VARCHAR(255) NOT NULL,
    buyer_tax_number VARCHAR(11) DEFAULT NULL,
    buyer_tax_office VARCHAR(100) DEFAULT NULL,
    buyer_address TEXT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 20.00,
    tax_amount DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    pdf_path VARCHAR(500) DEFAULT NULL,
    external_id VARCHAR(255) DEFAULT NULL COMMENT 'Harici e-fatura servis ID',
    status ENUM('draft', 'sent', 'cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    INDEX idx_order (order_id),
    INDEX idx_invoice_number (invoice_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 17. SİTE AYARLARI
-- =====================================================
CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    setting_group VARCHAR(50) DEFAULT 'general',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 18. SLIDERS (Ana Sayfa)
-- =====================================================
CREATE TABLE sliders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) DEFAULT NULL,
    subtitle VARCHAR(500) DEFAULT NULL,
    image VARCHAR(500) NOT NULL,
    link VARCHAR(500) DEFAULT NULL,
    button_text VARCHAR(100) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 19. STATİK SAYFALAR
-- =====================================================
CREATE TABLE pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content LONGTEXT DEFAULT NULL,
    meta_title VARCHAR(255) DEFAULT NULL,
    meta_description TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 20. CSV IMPORT LOG
-- =====================================================
CREATE TABLE import_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    total_rows INT DEFAULT 0,
    imported_rows INT DEFAULT 0,
    skipped_rows INT DEFAULT 0,
    error_rows INT DEFAULT 0,
    errors TEXT DEFAULT NULL,
    status ENUM('processing', 'completed', 'failed') DEFAULT 'processing',
    imported_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (imported_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VARSAYILAN VERİLER
-- =====================================================

-- Admin kullanıcı (şifre: admin123 - DEĞİŞTİRİLMELİ!)
INSERT INTO users (role, first_name, last_name, email, phone, password_hash, is_active, email_verified) VALUES
('admin', 'Salim', 'Admin', 'admin@salimhirdavat.com', '05001234567', '$2y$10$placeholder_change_this_hash', 1, 1);

-- Site ayarları
INSERT INTO settings (setting_key, setting_value, setting_group) VALUES
('site_name', 'Salim Hırdavat', 'general'),
('site_description', 'Sivas\'ın En Büyük Hırdavat Mağazası - 25.000+ Ürün', 'general'),
('site_phone', '0346 XXX XX XX', 'general'),
('site_whatsapp', '905XXXXXXXXX', 'general'),
('site_email', 'info@salimhirdavat.com', 'general'),
('site_address', 'Sivas Merkez, Sivas', 'general'),
('site_city', 'Sivas', 'general'),
('currency', 'TRY', 'general'),
('currency_symbol', '₺', 'general'),
('tax_rate', '20', 'general'),
('min_order_amount', '100', 'order'),
('free_delivery_amount', '500', 'order'),
('default_delivery_fee', '30', 'order'),
('working_hours', '08:00 - 19:00', 'general'),
('working_days', 'Pazartesi - Cumartesi', 'general'),
('iyzico_api_key', '', 'payment'),
('iyzico_secret_key', '', 'payment'),
('iyzico_base_url', 'https://sandbox-api.iyzipay.com', 'payment'),
('door_payment_enabled', '1', 'payment'),
('invoice_prefix', 'SH', 'invoice'),
('invoice_next_number', '1001', 'invoice');

-- Teslimat bölgeleri (Sivas)
INSERT INTO delivery_zones (name, districts, delivery_fee, min_order_amount, estimated_delivery_time) VALUES
('Sivas Merkez', 'Merkez, Kaleardı, Paşabey, Esenyurt, Karşıyaka, Yenişehir', 0.00, 200.00, '1-3 saat'),
('Sivas Yakın İlçeler', 'Hafik, Yıldızeli, Zara, Gemerek, Şarkışla', 30.00, 300.00, '1-2 gün'),
('Sivas Uzak İlçeler', 'Kangal, Divriği, Gürün, Suşehri, Koyulhisar, İmranlı, Doğanşar, Akıncılar, Gölova, Ulaş', 50.00, 500.00, '2-3 gün');

-- Ana kategoriler (Hırdavat sektörü)
INSERT INTO categories (name, slug, icon, sort_order) VALUES
('Elektrik Malzemeleri', 'elektrik-malzemeleri', 'fa-bolt', 1),
('Hırdavat & El Aletleri', 'hirdavat-el-aletleri', 'fa-wrench', 2),
('Boya & Vernik', 'boya-vernik', 'fa-paint-roller', 3),
('Tesisat & Banyo', 'tesisat-banyo', 'fa-faucet', 4),
('Nalburiye', 'nalburiye', 'fa-screwdriver', 5),
('İnşaat Malzemeleri', 'insaat-malzemeleri', 'fa-hard-hat', 6),
('Makine & Ekipman', 'makine-ekipman', 'fa-cogs', 7),
('Bahçe & Tarım', 'bahce-tarim', 'fa-leaf', 8),
('Isıtma & Soğutma', 'isitma-sogutma', 'fa-temperature-high', 9),
('Güvenlik & Kilit', 'guvenlik-kilit', 'fa-lock', 10),
('Aydınlatma', 'aydinlatma', 'fa-lightbulb', 11),
('Yapı Kimyasalları', 'yapi-kimyasallari', 'fa-flask', 12);

-- Alt kategoriler örnekleri
INSERT INTO categories (parent_id, name, slug, sort_order) VALUES
(1, 'Kablolar', 'kablolar', 1),
(1, 'Prizler & Anahtarlar', 'prizler-anahtarlar', 2),
(1, 'Sigortalar', 'sigortalar', 3),
(1, 'Buat & Boru', 'buat-boru', 4),
(2, 'Çekiçler', 'cekicler', 1),
(2, 'Tornavidalar', 'tornavidalar', 2),
(2, 'Penseler', 'penseler', 3),
(2, 'Anahtarlar', 'anahtarlar', 4),
(2, 'Testere & Kesiciler', 'testere-kesiciler', 5),
(3, 'İç Cephe Boya', 'ic-cephe-boya', 1),
(3, 'Dış Cephe Boya', 'dis-cephe-boya', 2),
(3, 'Ahşap Boyalar', 'ahsap-boyalar', 3),
(3, 'Boya Fırça & Rulo', 'boya-firca-rulo', 4),
(4, 'Musluklar', 'musluklar', 1),
(4, 'Borular & Ek Parçalar', 'borular-ek-parcalar', 2),
(4, 'Klozet & Lavabo', 'klozet-lavabo', 3),
(4, 'Duş Sistemleri', 'dus-sistemleri', 4);
