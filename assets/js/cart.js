// =============================================
// HAVELI RESTAURANT - CART JAVASCRIPT
// =============================================

'use strict';

// =============================================
// CART STATE
// =============================================
let cart = {};

// =============================================
// ADD TO CART
// =============================================
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.add-to-cart-btn');
    if (!btn) return;

    const foodId = btn.dataset.foodId;
    if (!foodId) return;

    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    try {
        const response = await fetch('/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', food_id: parseInt(foodId), quantity: 1 })
        });

        const data = await response.json();

        if (data.success) {
            updateCartBadge(data.count);
            showToast(data.message, 'success');
            
            // Bounce animation
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.style.background = 'var(--success)';
            
            setTimeout(() => {
                btn.innerHTML = originalContent;
                btn.style.background = '';
                btn.disabled = false;
            }, 1000);

            // Refresh sidebar if open
            if (document.getElementById('cartSidebar')?.classList.contains('open')) {
                loadCartSidebar();
            }
        } else {
            showToast(data.message || 'Failed to add to cart', 'error');
            btn.innerHTML = originalContent;
            btn.disabled = false;
        }
    } catch (err) {
        showToast('Something went wrong', 'error');
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
});

// =============================================
// CART BADGE
// =============================================
function updateCartBadge(count) {
    const badge = document.getElementById('cartBadge');
    if (!badge) return;
    
    badge.textContent = count;
    badge.style.display = count > 0 ? 'flex' : 'none';
    
    if (count > 0) {
        badge.classList.remove('badgePop');
        void badge.offsetWidth;
        badge.classList.add('badgePop');
    }
}

// =============================================
// CART SIDEBAR
// =============================================
const cartSidebar  = document.getElementById('cartSidebar');
const cartOverlay  = document.getElementById('cartOverlay');
const closeSidebar = document.getElementById('closeSidebar');

// Open via cart badge click (optional shortcut)
document.addEventListener('click', (e) => {
    if (e.target.closest('#openCartSidebar')) {
        openCartSidebar();
    }
});

function openCartSidebar() {
    cartSidebar?.classList.add('open');
    cartOverlay?.classList.add('active');
    document.body.style.overflow = 'hidden';
    loadCartSidebar();
}

function closeCartSidebar() {
    cartSidebar?.classList.remove('open');
    cartOverlay?.classList.remove('active');
    document.body.style.overflow = '';
}

closeSidebar?.addEventListener('click', closeCartSidebar);
cartOverlay?.addEventListener('click', closeCartSidebar);

async function loadCartSidebar() {
    const body   = document.getElementById('cartSidebarBody');
    const footer = document.getElementById('cartSidebarFooter');
    
    if (!body) return;

    body.innerHTML = '<div class="skeleton skeleton-card" style="height:100px;margin:8px 0;"></div>'.repeat(3);

    try {
        const response = await fetch('/api/cart.php?action=get');
        const data = await response.json();

        if (!data.success || !data.items || Object.keys(data.items).length === 0) {
            body.innerHTML = `
                <div class="empty-cart" style="padding: 40px 20px;">
                    <div class="empty-cart-icon"><i class="fas fa-shopping-cart"></i></div>
                    <h3>Your cart is empty</h3>
                    <p>Add some delicious items!</p>
                    <a href="/menu.php" class="btn btn-primary btn-sm" onclick="closeCartSidebar()">
                        Browse Menu
                    </a>
                </div>
            `;
            if (footer) footer.innerHTML = '';
            return;
        }

        body.innerHTML = Object.values(data.items).map(item => `
            <div class="sidebar-cart-item" id="sidebar-item-${item.food_id}">
                <img src="${item.image_url || '/assets/img/food-placeholder.jpg'}" 
                     alt="${escapeHtml(item.name)}" loading="lazy">
                <div class="sidebar-cart-item-info">
                    <div class="sidebar-cart-item-name">${escapeHtml(item.name)}</div>
                    <div class="sidebar-cart-item-price">₹${parseFloat(item.price).toFixed(2)}</div>
                </div>
                <div class="qty-controller">
                    <button class="qty-btn cart-qty-btn" data-food-id="${item.food_id}" data-action="decrease">−</button>
                    