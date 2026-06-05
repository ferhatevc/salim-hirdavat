/**
 * Salim Hırdavat - Admin Panel JavaScript
 */

// Sidebar toggle
const sidebarToggle = document.getElementById('sidebarToggle');
const adminSidebar = document.getElementById('adminSidebar');

if (sidebarToggle && adminSidebar) {
    sidebarToggle.addEventListener('click', () => {
        adminSidebar.classList.toggle('active');
    });
}

// Bulk select
function toggleSelectAll(source) {
    const checkboxes = document.querySelectorAll('input[name="selected[]"]');
    checkboxes.forEach(cb => cb.checked = source.checked);
}

// Confirm delete
function confirmDelete(message = 'Bu öğeyi silmek istediğinize emin misiniz?') {
    return confirm(message);
}

// Toast notification
function showAdminToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.style.cssText = `position: fixed; top: 20px; right: 20px; padding: 14px 24px; border-radius: 8px; color: white; font-weight: 600; font-size: 14px; z-index: 9999; animation: slideIn 0.3s ease; box-shadow: 0 4px 16px rgba(0,0,0,0.15);`;
    toast.style.background = type === 'success' ? '#28A745' : type === 'error' ? '#DC3545' : '#FFC107';
    if (type === 'warning') toast.style.color = '#333';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Image preview
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Drag & drop file
document.querySelectorAll('.drop-zone').forEach(zone => {
    const input = zone.querySelector('input[type="file"]');
    
    zone.addEventListener('click', () => input?.click());
    
    zone.addEventListener('dragover', (e) => {
        e.preventDefault();
        zone.classList.add('dragover');
    });
    
    zone.addEventListener('dragleave', () => {
        zone.classList.remove('dragover');
    });
    
    zone.addEventListener('drop', (e) => {
        e.preventDefault();
        zone.classList.remove('dragover');
        if (input && e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            input.dispatchEvent(new Event('change'));
        }
    });
});

// Slug generator
function generateSlug(text) {
    const tr = {'ç':'c','ğ':'g','ı':'i','ö':'o','ş':'s','ü':'u','Ç':'c','Ğ':'g','İ':'i','Ö':'o','Ş':'s','Ü':'u'};
    let slug = text.toLowerCase();
    for (let [k, v] of Object.entries(tr)) slug = slug.replace(new RegExp(k, 'g'), v);
    return slug.replace(/[^a-z0-9\s-]/g, '').replace(/[\s-]+/g, '-').trim();
}

// Auto-slug
const nameInput = document.getElementById('productName');
const slugInput = document.getElementById('productSlug');
if (nameInput && slugInput) {
    nameInput.addEventListener('input', () => {
        if (!slugInput.dataset.manual) {
            slugInput.value = generateSlug(nameInput.value);
        }
    });
    slugInput.addEventListener('input', () => {
        slugInput.dataset.manual = 'true';
    });
}
