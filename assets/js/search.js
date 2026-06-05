/**
 * Salim Hırdavat - Arama JavaScript
 */

let searchTimer = null;
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');
const searchForm = document.getElementById('searchForm');

if (searchInput && searchResults) {
    // Debounced search
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);
        const query = this.value.trim();
        
        if (query.length < 2) {
            searchResults.classList.remove('active');
            searchResults.innerHTML = '';
            return;
        }
        
        searchTimer = setTimeout(() => {
            performSearch(query);
        }, 300);
    });

    // Focus/blur
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2 && searchResults.innerHTML) {
            searchResults.classList.add('active');
        }
    });

    // Click outside to close
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#searchBar')) {
            searchResults.classList.remove('active');
        }
    });

    // Keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        const items = searchResults.querySelectorAll('.search-result-item');
        let activeIdx = -1;
        
        items.forEach((item, i) => {
            if (item.classList.contains('highlighted')) activeIdx = i;
        });

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            items.forEach(i => i.classList.remove('highlighted'));
            activeIdx = (activeIdx + 1) % items.length;
            items[activeIdx]?.classList.add('highlighted');
            items[activeIdx]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            items.forEach(i => i.classList.remove('highlighted'));
            activeIdx = activeIdx <= 0 ? items.length - 1 : activeIdx - 1;
            items[activeIdx]?.classList.add('highlighted');
            items[activeIdx]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter' && activeIdx >= 0) {
            e.preventDefault();
            const link = items[activeIdx].getAttribute('href') || items[activeIdx].querySelector('a')?.href;
            if (link) window.location.href = link;
        } else if (e.key === 'Escape') {
            searchResults.classList.remove('active');
            searchInput.blur();
        }
    });
}

// Arama fonksiyonu
function performSearch(query) {
    fetch(`${SITE_URL}/api/search.php?q=${encodeURIComponent(query)}&limit=8`)
    .then(r => r.json())
    .then(data => {
        if (data.results && data.results.length > 0) {
            let html = '';
            
            data.results.forEach(product => {
                const price = product.sale_price && product.sale_price < product.price 
                    ? `<span class="price">${formatPriceTR(product.sale_price)}</span> <del style="color: var(--gray-400); font-size: 12px;">${formatPriceTR(product.price)}</del>`
                    : `<span class="price">${formatPriceTR(product.price)}</span>`;
                
                const highlighted = highlightMatch(product.name, query);
                
                html += `
                    <a href="${SITE_URL}/urun/${product.slug}" class="search-result-item">
                        <img src="${product.image || SITE_URL + '/assets/images/no-image.png'}" alt="${product.name}">
                        <div class="search-result-info">
                            <h4>${highlighted}</h4>
                            ${product.category_name ? `<div style="font-size: 11px; color: var(--gray-400);">${product.category_name}</div>` : ''}
                            <div>${price}</div>
                        </div>
                    </a>
                `;
            });
            
            html += `
                <div class="search-results-footer">
                    <a href="${SITE_URL}/ara?q=${encodeURIComponent(query)}">
                        Tüm sonuçları gör (${data.total || data.results.length}) <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            `;
            
            searchResults.innerHTML = html;
            searchResults.classList.add('active');
            
            // Save to recent searches
            saveRecentSearch(query);
        } else {
            searchResults.innerHTML = `
                <div style="padding: 24px; text-align: center; color: var(--gray-500);">
                    <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                    "<strong>${escapeHtml(query)}</strong>" için sonuç bulunamadı
                </div>
            `;
            searchResults.classList.add('active');
        }
    })
    .catch(err => {
        console.error('Search error:', err);
    });
}

// Eşleşen metni vurgula
function highlightMatch(text, query) {
    const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
    return text.replace(regex, '<mark style="background: var(--primary-bg); color: var(--primary); padding: 0 2px; border-radius: 2px;">$1</mark>');
}

// Regex escape
function escapeRegex(str) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// HTML escape
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Son aramaları kaydet
function saveRecentSearch(query) {
    let recent = JSON.parse(localStorage.getItem('recentSearches') || '[]');
    recent = recent.filter(q => q !== query);
    recent.unshift(query);
    recent = recent.slice(0, 5);
    localStorage.setItem('recentSearches', JSON.stringify(recent));
}
