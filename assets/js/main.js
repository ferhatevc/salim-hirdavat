/**
 * Salim Hırdavat - Ana JavaScript
 */
const SITE_URL = document.querySelector('meta[property="og:url"]')?.content?.split('/').slice(0, 3).join('/') || '';

// ── DOM Ready ──
document.addEventListener('DOMContentLoaded', function() {
    initStickyHeader();
    initMobileMenu();
    initUserDropdown();
    initScrollTop();
    initCookieConsent();
    initAlertClose();
    initLazyLoading();
});

// ── Sticky Header ──
function initStickyHeader() {
    const header = document.getElementById('mainHeader');
    if (!header) return;
    
    let lastScroll = 0;
    window.addEventListener('scroll', () => {
        const scroll = window.pageYOffset;
        header.classList.toggle('scrolled', scroll > 50);
        lastScroll = scroll;
    });
}

// ── Mobile Menu ──
function initMobileMenu() {
    const btn = document.getElementById('mobileMenuBtn');
    const menu = document.getElementById('mobileMenu');
    const overlay = document.getElementById('mobileMenuOverlay');
    const close = document.getElementById('mobileMenuClose');
    
    if (!btn || !menu) return;
    
    const toggle = (open) => {
        menu.classList.toggle('active', open);
        overlay.classList.toggle('active', open);
        document.body.style.overflow = open ? 'hidden' : '';
    };
    
    btn.addEventListener('click', () => toggle(true));
    close?.addEventListener('click', () => toggle(false));
    overlay?.addEventListener('click', () => toggle(false));
}

// ── User Dropdown ──
function initUserDropdown() {
    const btn = document.getElementById('userDropdownBtn');
    const menu = document.getElementById('userDropdownMenu');
    if (!btn || !menu) return;
    
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.classList.toggle('active');
    });
    
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#userDropdown')) {
            menu.classList.remove('active');
        }
    });
}

// ── Mega Menu ──
const categoriesBtn = document.getElementById('categoriesBtn');
const megaMenu = document.getElementById('megaMenu');

if (categoriesBtn && megaMenu) {
    categoriesBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        megaMenu.classList.toggle('active');
    });
    
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#megaMenu') && !e.target.closest('#categoriesBtn')) {
            megaMenu.classList.remove('active');
        }
    });
}

// ── Scroll to Top ──
function initScrollTop() {
    const btn = document.getElementById('scrollTopBtn');
    if (!btn) return;
    
    window.addEventListener('scroll', () => {
        btn.classList.toggle('visible', window.pageYOffset > 400);
    });
    
    btn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

// ── Toast Notification System ──
function showToast(message, type = 'success', duration = 3000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="fas ${icons[type] || icons.info}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" style="margin-left: auto; background: none; border: none; cursor: pointer; font-size: 18px; color: var(--gray-400);">&times;</button>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(30px)';
        toast.style.transition = 'all 300ms ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ── Cookie Consent ──
function initCookieConsent() {
    const consent = document.getElementById('cookieConsent');
    const accept = document.getElementById('cookieAccept');
    if (!consent || !accept) return;
    
    if (!localStorage.getItem('cookieAccepted')) {
        setTimeout(() => consent.classList.add('show'), 2000);
    }
    
    accept.addEventListener('click', () => {
        localStorage.setItem('cookieAccepted', 'true');
        consent.classList.remove('show');
    });
}

// ── Alert Close ──
function initAlertClose() {
    document.querySelectorAll('.alert-close').forEach(btn => {
        btn.addEventListener('click', () => {
            const alert = btn.closest('.alert');
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        });
    });
}

// ── Lazy Loading ──
function initLazyLoading() {
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    observer.unobserve(img);
                }
            });
        }, { rootMargin: '100px' });
        
        document.querySelectorAll('img[data-src]').forEach(img => observer.observe(img));
    }
}

// ── Modal System ──
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal on overlay click
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Close modal on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => {
            m.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
});

// ── Wishlist ──
function addToWishlist(productId) {
    fetch(SITE_URL + '/api/wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Favorilere eklendi!', 'success');
        } else {
            showToast(data.message || 'Giriş yapmalısınız.', 'warning');
        }
    })
    .catch(() => showToast('Bir hata oluştu.', 'error'));
}

// ── Newsletter Form ──
const newsletterForm = document.getElementById('newsletterForm');
if (newsletterForm) {
    newsletterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        showToast('Bülten aboneliğiniz başarıyla oluşturuldu!', 'success');
        newsletterForm.reset();
    });
}

// ── Number Format Helper ──
function formatPriceTR(price) {
    return new Intl.NumberFormat('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price) + ' ₺';
}
