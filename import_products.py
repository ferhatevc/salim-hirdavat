import pymysql
import xlrd
import re
import sys

# Railway MySQL bağlantısı
conn = pymysql.connect(
    host='acela.proxy.rlwy.net',
    port=56000,
    user='root',
    password='ERqGpkTVsvLWyHYEIQhUvMqvnDddOZzE',
    database='railway',
    charset='utf8mb4',
    autocommit=True
)
cursor = conn.cursor()

print("🚀 DIA3 → Salim Hırdavat Site Import Başlıyor...\n")

# ============================
# SLUG FONKSİYONU (Türkçe destekli)
# ============================
def slugify(text):
    if not text:
        return ''
    text = text.lower().strip()
    tr_map = {'ç':'c', 'ğ':'g', 'ı':'i', 'ö':'o', 'ş':'s', 'ü':'u',
              'Ç':'c', 'Ğ':'g', 'İ':'i', 'Ö':'o', 'Ş':'s', 'Ü':'u'}
    for tr, en in tr_map.items():
        text = text.replace(tr, en)
    text = re.sub(r'[^a-z0-9\s-]', '', text)
    text = re.sub(r'[\s]+', '-', text)
    text = re.sub(r'-+', '-', text)
    text = text.strip('-')
    return text[:250] if text else 'urun'

# ============================
# EXCEL DOSYASINI OKU
# ============================
print("📂 Excel dosyası okunuyor...")
wb = xlrd.open_workbook('/Users/ferhatevci/Desktop/salim hırdavat.xls')
sheet = wb.sheet_by_index(0)
print(f"   Toplam satır: {sheet.nrows}\n")

# ============================
# 1. ÖNCEKİ VERİLERİ TEMİZLE
# ============================
print("🗑️  Eski veriler temizleniyor...")
cursor.execute("DELETE FROM product_attributes")
cursor.execute("DELETE FROM product_images")
cursor.execute("DELETE FROM products")
cursor.execute("DELETE FROM categories WHERE id > 0")
cursor.execute("DELETE FROM brands WHERE id > 0")
cursor.execute("ALTER TABLE categories AUTO_INCREMENT = 1")
cursor.execute("ALTER TABLE brands AUTO_INCREMENT = 1")
cursor.execute("ALTER TABLE products AUTO_INCREMENT = 1")
print("   ✅ Temizlendi\n")

# ============================
# 2. KATEGORİLERİ OLUŞTUR
# ============================
print("📁 Kategoriler oluşturuluyor...")

# Kategori ikonları eşleştirmesi
cat_icons = {
    'EL ALETLERİ': 'fa-wrench',
    'ELEKTRİK MALZEMELERİ': 'fa-bolt',
    'BOYALAR': 'fa-paint-roller',
    'VERNİKLER': 'fa-fill-drip',
    'KİMYASALLAR': 'fa-flask',
    'BAĞLANTI ELEMANLARI': 'fa-link',
    'VİDALAR': 'fa-screwdriver',
    'MOBİLYA KULPLARI': 'fa-hand-pointer',
    'MOBİLYA KULPLARI (01)': 'fa-hand-pointer',
    'MOBİLYA AKSESUARLARI': 'fa-couch',
    'MOBİLYA AYAKLARI': 'fa-chair',
    'MOBİLYA MENTEŞESİ': 'fa-door-open',
    'MOBİLYA KİLİTLERİ': 'fa-lock',
    'KAPI KULPLARI': 'fa-door-closed',
    'KAPI KİLİTLERİ': 'fa-key',
    'KAPI MENTEŞESİ': 'fa-door-open',
    'KAPI AKSESUARLARI': 'fa-door-closed',
    'KAPI SÜRGÜLERİ': 'fa-arrows-alt-h',
    'BANYO WC AKSESUARLARI': 'fa-bath',
    'BATARYALAR': 'fa-faucet',
    'SU TESİSATI': 'fa-water',
    'MATKAP UÇLARI': 'fa-cog',
    'RAYLAR': 'fa-grip-lines',
    'KENAR BANTLARI': 'fa-tape',
    'OTO BAKIM & AKSESUAR': 'fa-car',
    'ZIMPARALAR': 'fa-square',
    'İŞ GÜVENLİĞİ': 'fa-hard-hat',
    'PROFİL': 'fa-columns',
    'BAHÇE ALETLERİ': 'fa-leaf',
    'İNŞAAT MALZEMELERİ': 'fa-hammer',
    'KESKİ TAŞLARI': 'fa-gem',
    'DÜBEL': 'fa-thumbtack',
    'FIRÇALAR': 'fa-paint-brush',
    'MERDİVEN': 'fa-stairs',
    'ÇİVİLER': 'fa-thumbtack',
    'ASKILIKLAR': 'fa-tshirt',
    'ELDİVENLER': 'fa-mitten',
    'KAYNAK MALZEMELERİ': 'fa-fire',
    'MAKİNELER': 'fa-cogs',
    'HALATLAR': 'fa-rope',
    'PENCERE MANDALLARI': 'fa-window-maximize',
    'EVYE': 'fa-sink',
    'ANKASTRE': 'fa-kitchen-set',
    'ASPİRATÖRLER': 'fa-fan',
}

# Kategorileri topla
categories = {}
for i in range(5, sheet.nrows - 3):
    name = str(sheet.cell_value(i, 0)).strip()
    cat = str(sheet.cell_value(i, 6)).strip()
    if name and cat:
        categories[cat] = categories.get(cat, 0) + 1

# Kategorileri ekle
cat_id_map = {}
sort_order = 1
for cat_name, count in sorted(categories.items(), key=lambda x: -x[1]):
    if not cat_name:
        continue
    slug = slugify(cat_name)
    if not slug:
        slug = f'kategori-{sort_order}'
    
    # Slug benzersizliği kontrol
    cursor.execute("SELECT id FROM categories WHERE slug = %s", (slug,))
    if cursor.fetchone():
        slug = f'{slug}-{sort_order}'
    
    icon = cat_icons.get(cat_name, 'fa-box')
    
    cursor.execute(
        "INSERT INTO categories (name, slug, icon, sort_order, is_active) VALUES (%s, %s, %s, %s, 1)",
        (cat_name, slug, icon, sort_order)
    )
    cat_id_map[cat_name] = cursor.lastrowid
    print(f"   ✅ {cat_name} ({count} ürün)")
    sort_order += 1

# Kategorisiz ürünler için
cursor.execute(
    "INSERT INTO categories (name, slug, icon, sort_order, is_active) VALUES (%s, %s, %s, %s, 1)",
    ('Diğer', 'diger', 'fa-box-open', sort_order)
)
cat_id_map[''] = cursor.lastrowid
print(f"   ✅ Diğer (kategorisiz ürünler)")
print(f"\n   📁 Toplam {len(cat_id_map)} kategori oluşturuldu\n")

# ============================
# 3. MARKALARI OLUŞTUR
# ============================
print("🏷️  Markalar oluşturuluyor...")
brands = set()
for i in range(5, sheet.nrows - 3):
    brand = str(sheet.cell_value(i, 1)).strip()
    if brand and brand != '.' and len(brand) > 1:
        brands.add(brand)

brand_id_map = {}
for brand_name in sorted(brands):
    slug = slugify(brand_name)
    if not slug:
        continue
    cursor.execute("SELECT id FROM brands WHERE slug = %s", (slug,))
    if cursor.fetchone():
        slug = f'{slug}-marka'
    
    cursor.execute(
        "INSERT INTO brands (name, slug, is_active) VALUES (%s, %s, 1)",
        (brand_name, slug)
    )
    brand_id_map[brand_name] = cursor.lastrowid

print(f"   ✅ {len(brand_id_map)} marka oluşturuldu\n")

# ============================
# 4. ÜRÜNLERİ YÜKLE
# ============================
print("📦 Ürünler yükleniyor...")
total = 0
errors = 0
slug_counter = {}

for i in range(5, sheet.nrows - 3):
    name = str(sheet.cell_value(i, 0)).strip()
    if not name:
        continue
    
    brand = str(sheet.cell_value(i, 1)).strip()
    
    try:
        price = float(sheet.cell_value(i, 2)) if sheet.cell_value(i, 2) else 0
    except:
        price = 0
    
    try:
        stock = int(float(sheet.cell_value(i, 3))) if sheet.cell_value(i, 3) else 0
    except:
        stock = 0
    
    unit = str(sheet.cell_value(i, 4)).strip() or 'AD'
    cat_code = str(sheet.cell_value(i, 5)).strip()
    cat_name = str(sheet.cell_value(i, 6)).strip()
    
    # Negatif stok = 0
    if stock < 0:
        stock = 0
    
    # Slug oluştur
    slug = slugify(name)
    if not slug:
        slug = f'urun-{total}'
    
    # Slug benzersizliği
    if slug in slug_counter:
        slug_counter[slug] += 1
        slug = f'{slug}-{slug_counter[slug]}'
    else:
        slug_counter[slug] = 0
    
    # Kategori ID
    category_id = cat_id_map.get(cat_name, cat_id_map.get('', None))
    
    # Marka ID
    brand_id = brand_id_map.get(brand, None)
    
    # Birim çevirisi
    unit_map = {'AD': 'Adet', 'MT': 'Metre', 'KG': 'Kg', 'LT': 'Litre', 'PK': 'Paket', 'TK': 'Takım', 'KL': 'Koli', 'ŞŞ': 'Şişe', 'TB': 'Tüp', 'M2': 'm²', 'ST': 'Set'}
    unit_display = unit_map.get(unit, unit)
    
    try:
        cursor.execute("""
            INSERT INTO products (category_id, brand_id, name, slug, price, stock, unit, is_active, is_new, created_at) 
            VALUES (%s, %s, %s, %s, %s, %s, %s, 1, 0, NOW())
        """, (category_id, brand_id, name, slug, price, stock, unit_display))
        total += 1
        
        if total % 1000 == 0:
            print(f"   📦 {total} ürün yüklendi...")
    except Exception as e:
        errors += 1
        if errors <= 5:
            print(f"   ❌ Hata: {name[:30]} → {str(e)[:80]}")

print(f"\n{'='*50}")
print(f"🎉 TAMAMLANDI!")
print(f"{'='*50}")
print(f"   ✅ Yüklenen ürün: {total}")
print(f"   ❌ Hata: {errors}")
print(f"   📁 Kategori: {len(cat_id_map)}")
print(f"   🏷️  Marka: {len(brand_id_map)}")
print(f"{'='*50}")

cursor.close()
conn.close()
