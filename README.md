# 🔧 Salim Hırdavat - E-Ticaret

Sivas'ın en büyük hırdavat mağazasının e-ticaret platformu.

## 🚀 Özellikler
- 25.000+ ürün desteği (CSV toplu import)
- Sivas kurye teslimat sistemi
- E-fatura (e-arşiv / e-fatura)
- iyzico online ödeme + kapıda ödeme
- Admin paneli (dashboard, raporlar, Chart.js)
- Responsive tasarım (mobil uyumlu)
- SEO dostu URL yapısı

## 🛠️ Teknoloji
- PHP 8+ / MySQL 8+
- Vanilla CSS + JavaScript
- Railway + GitHub deploy

## 📦 Kurulum

### Railway Deploy
1. GitHub repo'ya push et
2. Railway'de "New Project" → "Deploy from GitHub"
3. MySQL eklentisi ekle (Railway dashboard → Add MySQL)
4. Deploy sonrası `sql/schema.sql` dosyasını import et
5. Custom domain bağla

### Ortam Değişkenleri (Railway Variables)
```
SITE_URL=https://yourdomain.com
SITE_EMAIL=info@salimhirdavat.com.tr
SITE_PHONE=0346 218 12 34
```
MySQL değişkenleri Railway tarafından otomatik atanır.

## 📁 Proje Yapısı
```
├── admin/          # Admin panel
├── api/            # REST API endpoints
├── assets/         # CSS, JS, images
├── config/         # Konfigürasyon
├── includes/       # Header, footer, functions
├── pages/          # Müşteri sayfaları
├── sql/            # Veritabanı şeması
├── uploads/        # Kullanıcı yüklemeleri
└── index.php       # Ana sayfa
```

## 📄 Lisans
© 2024 Salim Hırdavat San. Tic. Ltd. Şti.
