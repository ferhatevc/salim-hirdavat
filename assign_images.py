import pymysql

conn = pymysql.connect(
    host='acela.proxy.rlwy.net', port=56000, user='root',
    password='ERqGpkTVsvLWyHYEIQhUvMqvnDddOZzE',
    database='railway', charset='utf8mb4', autocommit=True
)
cursor = conn.cursor()

# Kategori slug -> görsel dosyası eşleştirmesi
cat_images = {
    'mobilya-kulplari': 'assets/images/categories/mobilya-kulplari.png',
    'el-aletleri': 'assets/images/categories/el-aletleri.png',
    'kapi-kulplari': 'assets/images/categories/kapi-kulplari.png',
    'boyalar': 'assets/images/categories/boyalar.png',
    'mobilya-aksesuarlari': 'assets/images/categories/mobilya-aksesuarlari.png',
    'baglanti-elemanlari': 'assets/images/categories/baglanti-elemanlari.png',
    'mobilya-ayaklari': 'assets/images/categories/mobilya-aksesuarlari.png',
    'mobilya-kulplari-01': 'assets/images/categories/mobilya-kulplari.png',
    'kimyasallar': 'assets/images/categories/kimyasallar.png',
    'vidalar': 'assets/images/categories/vidalar.png',
    'banyo-wc-aksesuarlari': 'assets/images/categories/banyo-wc-aksesuarlari.png',
    'elektrik-malzemeleri': 'assets/images/categories/elektrik-malzemeleri.png',
}

# Kategori tablosuna image kolonu ekle (yoksa)
try:
    cursor.execute("ALTER TABLE categories ADD COLUMN image VARCHAR(255) DEFAULT NULL")
    print("✅ categories.image kolonu eklendi")
except:
    print("ℹ️  categories.image kolonu zaten var")

# Kategori görsellerini güncelle
for slug, image in cat_images.items():
    cursor.execute("UPDATE categories SET image = %s WHERE slug = %s", (image, slug))
    rows = cursor.rowcount
    if rows > 0:
        print(f"✅ {slug} → {image}")

# Her kategorideki ürünlere varsayılan görsel ata
print("\n📦 Ürünlere kategori görselleri atanıyor...")

# Önce product_images tablosundaki mevcut kayıtları temizle
cursor.execute("DELETE FROM product_images")

total = 0
for slug, image in cat_images.items():
    # Bu kategorideki tüm ürünleri bul
    cursor.execute("""
        SELECT p.id FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE c.slug = %s
    """, (slug,))
    products = cursor.fetchall()
    
    # Her ürüne kategori görselini ata
    for product in products:
        product_id = product[0]
        cursor.execute("""
            INSERT INTO product_images (product_id, image_path, is_primary, sort_order) 
            VALUES (%s, %s, 1, 1)
        """, (product_id, image))
        total += 1
    
    print(f"  ✅ {slug}: {len(products)} ürüne görsel atandı")

# Görseli olmayan ürünler için genel placeholder
cursor.execute("""
    SELECT p.id FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id 
    WHERE pi.id IS NULL
""")
no_image = cursor.fetchall()
for product in no_image:
    cursor.execute("""
        INSERT INTO product_images (product_id, image_path, is_primary, sort_order) 
        VALUES (%s, 'assets/images/categories/el-aletleri.png', 1, 1)
    """, (product[0],))
    total += 1

print(f"\n  ✅ {len(no_image)} kategorisiz ürüne de görsel atandı")
print(f"\n🎉 TOPLAM: {total} ürüne görsel atandı!")

cursor.close()
conn.close()
