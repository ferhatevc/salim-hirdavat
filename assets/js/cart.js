/**
 * Salim Hırdavat - Sepet JavaScript
 */

// Sepete ürün ekle
function addToCart(productId, quantity = 1) {
    quantity = parseInt(quantity) || 1;
    
    fetch(SITE_URL + '/api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId, quantity: quantity })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateCartBadge(data.cart_count);
            showToast(data.message || 'Ürün sepete eklendi!', 'success');
            animateCartIcon();
        } else {
            showToast(data.message || 'Bir hata oluştu.', 'error');
        }
    })
    .catch(() => showToast('Bağlantı hatası.', 'error'));
}

// Sepet miktarını güncelle
function updateCartQuantity(productId, quantity) {
    quantity = parseInt(quantity);
    
    if (quantity <= 0) {
        removeFromCart(productId);
        return;
    }
    
    fetch(SITE_URL + '/api/cart.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId, quantity: quantity })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateCartBadge(data.cart_count);
            // Sepet sayfasındaysa sayfayı yenile
            if (window.location.pathname.includes('sepet') || window.location.pathname.includes('cart')) {
                location.reload();
            }
        } else {
            showToast(data.message || 'Bir hata oluştu.', 'error');
        }
    })
    .catch(() => showToast('Bağlantı hatası.', 'error'));
}

// Sepetten ürün kaldır
function removeFromCart(productId) {
    fetch(SITE_URL + '/api/cart.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateCartBadge(data.cart_count);
            showToast('Ürün sepetten kaldırıldı.', 'info');
            
            // Satırı animasyonla kaldır
            const row = document.getElementById(`cartItem-${productId}`);
            if (row) {
                row.style.opacity = '0';
                row.style.transform = 'translateX(20px)';
                row.style.transition = 'all 300ms ease';
                setTimeout(() => {
                    row.remove();
                    // Sepet boşsa sayfayı yenile
                    const tbody = document.getElementById('cartTableBody');
                    if (tbody && tbody.children.length === 0) {
                        location.reload();
                    } else {
                        location.reload(); // Toplamları güncellemek için
                    }
                }, 300);
            }
        } else {
            showToast(data.message || 'Bir hata oluştu.', 'error');
        }
    })
    .catch(() => showToast('Bağlantı hatası.', 'error'));
}

// Kupon uygula
function applyCoupon() {
    const input = document.getElementById('couponInput');
    if (!input || !input.value.trim()) {
        showToast('Lütfen kupon kodu giriniz.', 'warning');
        return;
    }
    
    fetch(SITE_URL + '/api/cart.php?action=apply_coupon', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ coupon_code: input.value.trim() })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Kupon uygulandı!', 'success');
            location.reload();
        } else {
            showToast(data.message || 'Geçersiz kupon kodu.', 'error');
        }
    })
    .catch(() => showToast('Bağlantı hatası.', 'error'));
}

// Sepet badge güncelle
function updateCartBadge(count) {
    const badge = document.getElementById('cartBadge');
    const btn = document.getElementById('cartBtn');
    
    if (count > 0) {
        if (badge) {
            badge.textContent = count;
            badge.style.animation = 'none';
            badge.offsetHeight; // Reflow
            badge.style.animation = 'bounceIn 300ms ease';
        } else if (btn) {
            const newBadge = document.createElement('span');
            newBadge.className = 'badge';
            newBadge.id = 'cartBadge';
            newBadge.textContent = count;
            btn.appendChild(newBadge);
        }
    } else if (badge) {
        badge.remove();
    }
}

// Sepet ikonu animasyonu
function animateCartIcon() {
    const btn = document.getElementById('cartBtn');
    if (!btn) return;
    
    btn.style.transform = 'scale(1.2)';
    setTimeout(() => {
        btn.style.transform = '';
    }, 300);
}
