/**
 * HAVELI Restaurant — Main JavaScript
 * Cart, Cursor, Animations, Theme, Toast, etc.
 */

'use strict';

// ============================================================
// CUSTOM CURSOR
// ============================================================
const cursor = document.querySelector('.cursor');
const cursorFollower = document.querySelector('.cursor-follower');
let mouseX = 0, mouseY = 0, followerX = 0, followerY = 0;

if (cursor && cursorFollower) {
  document.addEventListener('mousemove', e => {
    mouseX = e.clientX; mouseY = e.clientY;
    cursor.style.left = mouseX + 'px';
    cursor.style.top = mouseY + 'px';
  });
  function animateFollower() {
    followerX += (mouseX - followerX) * 0.12;
    followerY += (mouseY - followerY) * 0.12;
    cursorFollower.style.left = followerX + 'px';
    cursorFollower.style.top = followerY + 'px';
    requestAnimationFrame(animateFollower);
  }
  animateFollower();

  document.querySelectorAll('a, button, [role="button"], input, label, select, textarea, .food-card, .glass-card').forEach(el => {
    el.addEventListener('mouseenter', () => { cursor.classList.add('hover'); cursorFollower.classList.add('hover'); });
    el.addEventListener('mouseleave', () => { cursor.classList.remove('hover'); cursorFollower.classList.remove('hover'); });
  });
}

// ============================================================
// PAGE LOADER
// ============================================================
window.addEventListener('load', () => {
  const loader = document.getElementById('pageLoader');
  if (loader) {
    setTimeout(() => {
      loader.classList.add('hidden');
      setTimeout(() => loader.remove(), 500);
    }, 1200);
  }
});

// ============================================================
// NAVBAR SCROLL BEHAVIOR
// ============================================================
const navbar = document.getElementById('mainNav');
if (navbar) {
  const annBar = document.getElementById('announcementBar');
  function handleScroll() {
    if (window.scrollY > 50) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  }
  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll();
}

// ============================================================
// ANNOUNCEMENT BAR
// ============================================================
const closeAnn = document.getElementById('closeAnnouncement');
if (closeAnn) {
  closeAnn.addEventListener('click', () => {
    const bar = document.getElementById('announcementBar');
    if (bar) {
      bar.style.height = bar.offsetHeight + 'px';
      bar.style.transition = 'height 0.3s ease, opacity 0.3s ease';
      requestAnimationFrame(() => { bar.style.height = '0'; bar.style.opacity = '0'; bar.style.overflow = 'hidden'; });
      setTimeout(() => bar.remove(), 300);
      if (navbar) navbar.classList.remove('has-announcement');
    }
    sessionStorage.setItem('ann_closed', '1');
  });
}

// ============================================================
// MOBILE MENU
// ============================================================
const hamburger = document.getElementById('hamburger');
const mobileMenu = document.getElementById('mobileMenu');
if (hamburger && mobileMenu) {
  hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('open');
    mobileMenu.classList.toggle('open');
    document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
  });
  mobileMenu.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => {
      hamburger.classList.remove('open');
      mobileMenu.classList.remove('open');
      document.body.style.overflow = '';
    });
  });
}

// ============================================================
// THEME TOGGLE (Dark / Light)
// ============================================================
function getTheme() {
  return localStorage.getItem('haveli_theme') || (document.documentElement.getAttribute('data-default-theme') || 'dark');
}
function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  localStorage.setItem('haveli_theme', theme);
  document.querySelectorAll('.theme-toggle').forEach(t => t.classList.toggle('on', theme === 'light'));
  document.querySelectorAll('.theme-icon').forEach(i => { i.textContent = theme === 'light' ? '🌙' : '☀️'; });
}
applyTheme(getTheme());
document.querySelectorAll('.theme-toggle, .theme-btn').forEach(btn => {
  btn.addEventListener('click', () => applyTheme(getTheme() === 'dark' ? 'light' : 'dark'));
});

// ============================================================
// CART SYSTEM
// ============================================================
const Cart = (() => {
  let items = JSON.parse(localStorage.getItem('haveli_cart') || '[]');
  let appliedCoupon = null;

  function save() { localStorage.setItem('haveli_cart', JSON.stringify(items)); }

  function add(id, name, price, image, qty = 1) {
    const existing = items.find(i => i.id == id);
    if (existing) {
      existing.qty = Math.min(existing.qty + qty, 20);
    } else {
      items.push({ id, name, price: parseFloat(price), image, qty });
    }
    save(); render(); updateBadges();
    showToast('Added to cart', 'success', '🛒');
  }

  function remove(id) {
    items = items.filter(i => i.id != id);
    save(); render(); updateBadges();
  }

  function setQty(id, qty) {
    const item = items.find(i => i.id == id);
    if (item) {
      item.qty = Math.max(0, Math.min(qty, 20));
      if (item.qty === 0) remove(id);
      else { save(); render(); updateBadges(); }
    }
  }

  function getCount() { return items.reduce((s, i) => s + i.qty, 0); }
  function getSubtotal() { return items.reduce((s, i) => s + i.price * i.qty, 0); }
  function getItems() { return items; }
  function clear() { items = []; appliedCoupon = null; save(); render(); updateBadges(); }

  function getDeliveryFee() {
    const sub = getSubtotal();
    const free = parseFloat(document.body.dataset.freeDelivery || 2000);
    const fee = parseFloat(document.body.dataset.deliveryFee || 150);
    return sub >= free || sub === 0 ? 0 : fee;
  }

  function getTax() {
    const rate = parseFloat(document.body.dataset.taxRate || 5) / 100;
    return getSubtotal() * rate;
  }

  function getDiscount() {
    if (!appliedCoupon) return 0;
    return parseFloat(appliedCoupon.discount || 0);
  }

  function getTotal() {
    return Math.max(0, getSubtotal() + getDeliveryFee() + getTax() - getDiscount());
  }

  function setCoupon(coupon) { appliedCoupon = coupon; render(); }

  function updateBadges() {
    const count = getCount();
    document.querySelectorAll('.cart-badge').forEach(b => {
      b.textContent = count;
      b.style.display = count > 0 ? 'flex' : 'none';
    });
  }

  function getCurrency() { return document.body.dataset.currency || '₨'; }
  function fmt(n) { return getCurrency() + Math.round(n).toLocaleString(); }

  function render() {
    const container = document.getElementById('cartItems');
    const emptyMsg = document.getElementById('cartEmpty');
    const footerEl = document.getElementById('cartFooter');
    if (!container) return;

    if (items.length === 0) {
      container.innerHTML = '';
      if (emptyMsg) emptyMsg.style.display = 'flex';
      if (footerEl) footerEl.style.display = 'none';
      return;
    }
    if (emptyMsg) emptyMsg.style.display = 'none';
    if (footerEl) footerEl.style.display = 'block';

    container.innerHTML = items.map(item => `
      <div class="cart-item" data-id="${item.id}">
        <div class="cart-item-img">
          ${item.image
            ? `<img src="${item.image}" alt="${item.name}" loading="lazy">`
            : `<div class="cart-item-img-placeholder">🍽️</div>`}
        </div>
        <div class="cart-item-info">
          <div class="cart-item-name">${item.name}</div>
          <div class="cart-item-price">${fmt(item.price)}</div>
          <div class="cart-item-actions">
            <div class="qty-control">
              <button class="qty-btn" onclick="Cart.setQty(${item.id}, ${item.qty - 1})">−</button>
              <span class="qty-count">${item.qty}</span>
              <button class="qty-btn" onclick="Cart.setQty(${item.id}, ${item.qty + 1})">+</button>
            </div>
            <button class="cart-item-remove" onclick="Cart.remove(${item.id})">Remove</button>
          </div>
        </div>
      </div>
    `).join('');

    // Summary
    const sub = getSubtotal(), fee = getDeliveryFee(), tax = getTax(), disc = getDiscount(), total = getTotal();
    const setEl = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    setEl('cartSubtotal', fmt(sub));
    setEl('cartDelivery', fee === 0 ? 'FREE' : fmt(fee));
    setEl('cartTax', fmt(tax));
    setEl('cartDiscount', disc > 0 ? '-' + fmt(disc) : fmt(0));
    setEl('cartTotal', fmt(total));
  }

  // Initialize
  setTimeout(() => { render(); updateBadges(); }, 100);

  return { add, remove, setQty, getCount, getSubtotal, getItems, clear, getTotal, getDeliveryFee, getTax, getDiscount, setCoupon, appliedCoupon: () => appliedCoupon, render, updateBadges, fmt };
})();

window.Cart = Cart;

// ============================================================
// CART SIDEBAR TOGGLE
// ============================================================
function openCart() {
  document.getElementById('cartOverlay')?.classList.add('open');
  document.getElementById('cartSidebar')?.classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeCart() {
  document.getElementById('cartOverlay')?.classList.remove('open');
  document.getElementById('cartSidebar')?.classList.remove('open');
  document.body.style.overflow = '';
}
document.querySelectorAll('[data-open-cart]').forEach(btn => btn.addEventListener('click', openCart));
document.getElementById('cartOverlay')?.addEventListener('click', closeCart);
document.getElementById('closeCart')?.addEventListener('click', closeCart);

// ============================================================
// COUPON VALIDATION
// ============================================================
async function applyCoupon() {
  const input = document.getElementById('couponInput');
  const code = input?.value.trim().toUpperCase();
  if (!code) return showToast('Enter a coupon code', 'error', '✗');

  try {
    const res = await fetch('/haveli/api/coupon.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ code, subtotal: Cart.getSubtotal() })
    });
    const data = await res.json();
    if (data.success) {
      Cart.setCoupon(data.coupon);
      showToast(`Coupon applied! You save ${Cart.fmt(data.coupon.discount)}`, 'success', '🎉');
      if (input) input.disabled = true;
      const btn = document.getElementById('applyCouponBtn');
      if (btn) { btn.textContent = 'Applied ✓'; btn.disabled = true; btn.style.opacity = '0.7'; }
    } else {
      showToast(data.message || 'Invalid coupon', 'error', '✗');
    }
  } catch { showToast('Could not validate coupon', 'error', '✗'); }
}
window.applyCoupon = applyCoupon;

// ============================================================
// FAVORITES TOGGLE
// ============================================================
async function toggleFavorite(foodId, btn) {
  if (!document.body.dataset.loggedIn) {
    showToast('Login to save favorites', 'info', '♥');
    return;
  }
  btn.classList.toggle('active');
  const active = btn.classList.contains('active');
  btn.innerHTML = active ? '♥' : '♡';
  try {
    await fetch('/haveli/api/favorite.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ food_id: foodId, action: active ? 'add' : 'remove' })
    });
    showToast(active ? 'Added to favorites' : 'Removed from favorites', 'info', active ? '♥' : '♡');
  } catch {}
}
window.toggleFavorite = toggleFavorite;

// ============================================================
// TOAST NOTIFICATIONS
// ============================================================
function showToast(message, type = 'info', icon = 'ℹ') {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `<span class="toast-icon">${icon}</span><span>${message}</span>`;
  container.appendChild(toast);
  setTimeout(() => {
    toast.classList.add('removing');
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}
window.showToast = showToast;

// ============================================================
// SCROLL REVEAL
// ============================================================
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

// ============================================================
// LAZY LOADING IMAGES
// ============================================================
const lazyObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      const img = entry.target;
      if (img.dataset.src) {
        img.src = img.dataset.src;
        img.removeAttribute('data-src');
      }
      lazyObserver.unobserve(img);
    }
  });
}, { rootMargin: '200px' });
document.querySelectorAll('img[data-src]').forEach(img => lazyObserver.observe(img));

// ============================================================
// CATEGORY FILTER (Menu Page)
// ============================================================
function filterByCategory(slug, btn) {
  document.querySelectorAll('.cat-chip').forEach(c => c.classList.remove('active'));
  btn.classList.add('active');

  const cards = document.querySelectorAll('.food-card[data-category]');
  cards.forEach(card => {
    if (slug === 'all' || card.dataset.category === slug) {
      card.style.display = '';
      card.style.animation = 'fadeIn 0.4s ease both';
    } else {
      card.style.display = 'none';
    }
  });
  const visible = [...cards].filter(c => c.style.display !== 'none').length;
  const noResult = document.getElementById('noFoodsResult');
  if (noResult) noResult.style.display = visible === 0 ? 'block' : 'none';
}
window.filterByCategory = filterByCategory;

// ============================================================
// FOOD SEARCH
// ============================================================
const searchInput = document.getElementById('foodSearch');
if (searchInput) {
  let debounceTimer;
  searchInput.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      const q = searchInput.value.trim().toLowerCase();
      const cards = document.querySelectorAll('.food-card');
      let visible = 0;
      cards.forEach(card => {
        const name = (card.dataset.name || '').toLowerCase();
        const tags = (card.dataset.tags || '').toLowerCase();
        const match = !q || name.includes(q) || tags.includes(q);
        card.style.display = match ? '' : 'none';
        if (match) visible++;
      });
      const noResult = document.getElementById('noFoodsResult');
      if (noResult) noResult.style.display = visible === 0 ? 'block' : 'none';
    }, 250);
  });
}

// ============================================================
// POPUP OFFER
// ============================================================
window.addEventListener('load', () => {
  const popup = document.getElementById('offerPopup');
  if (popup && !sessionStorage.getItem('popup_seen')) {
    setTimeout(() => {
      popup.classList.add('open');
      sessionStorage.setItem('popup_seen', '1');
    }, 2500);
  }
});
function closePopup() {
  document.getElementById('offerPopup')?.classList.remove('open');
}
window.closePopup = closePopup;

// ============================================================
// ORDER TRACKING POLL
// ============================================================
function startOrderTracking(orderId) {
  if (!orderId) return;
  const poll = setInterval(async () => {
    try {
      const res = await fetch(`/haveli/api/order_status.php?id=${orderId}`);
      const data = await res.json();
      if (data.status) updateTrackingUI(data.status);
      if (data.status === 'delivered' || data.status === 'cancelled') clearInterval(poll);
    } catch {}
  }, 15000);
}
window.startOrderTracking = startOrderTracking;

function updateTrackingUI(status) {
  const steps = { pending: 0, accepted: 1, preparing: 2, out_for_delivery: 3, delivered: 4 };
  const currentIdx = steps[status] ?? 0;
  document.querySelectorAll('.order-step').forEach((step, i) => {
    step.classList.remove('completed', 'active');
    if (i < currentIdx) step.classList.add('completed');
    else if (i === currentIdx) step.classList.add('active');
  });
  const badge = document.getElementById('orderStatusBadge');
  if (badge) {
    badge.className = `status-badge status-${status}`;
    badge.innerHTML = `<span class="status-dot"></span>${status.replace(/_/g,' ')}`;
  }
}

// ============================================================
// CHECKOUT FORM VALIDATION
// ============================================================
function validateCheckout(form) {
  let valid = true;
  form.querySelectorAll('[required]').forEach(field => {
    const err = field.nextElementSibling;
    if (!field.value.trim()) {
      field.style.borderColor = '#EF4444';
      if (err?.classList.contains('form-error')) err.style.display = 'block';
      valid = false;
    } else {
      field.style.borderColor = '';
      if (err?.classList.contains('form-error')) err.style.display = 'none';
    }
  });
  if (Cart.getItems().length === 0) {
    showToast('Your cart is empty', 'error', '🛒');
    valid = false;
  }
  return valid;
}
window.validateCheckout = validateCheckout;

// ============================================================
// COPY COUPON CODE
// ============================================================
function copyCoupon(code) {
  navigator.clipboard.writeText(code).then(() => showToast('Coupon copied!', 'success', '📋'));
}
window.copyCoupon = copyCoupon;

// ============================================================
// STAR RATING RENDER
// ============================================================
function renderStars(rating) {
  let html = '<span class="stars-display">';
  for (let i = 1; i <= 5; i++) {
    if (rating >= i) html += '<span class="star filled">★</span>';
    else if (rating >= i - 0.5) html += '<span class="star half">★</span>';
    else html += '<span class="star empty">★</span>';
  }
  return html + '</span>';
}
document.querySelectorAll('.stars-auto').forEach(el => {
  el.innerHTML = renderStars(parseFloat(el.dataset.rating || 0));
});

// ============================================================
// SMOOTH ANCHOR SCROLL
// ============================================================
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const target = document.querySelector(a.getAttribute('href'));
    if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
  });
});

// ============================================================
// ACTIVE NAV LINK
// ============================================================
const currentPath = window.location.pathname;
document.querySelectorAll('.nav-links a, .mobile-menu-links a, .mobile-nav-btn').forEach(link => {
  const href = link.getAttribute('href') || '';
  if (href && currentPath.endsWith(href.split('/').pop())) link.classList.add('active');
});

// ============================================================
// PARALLAX HERO
// ============================================================
const heroBg = document.querySelector('.hero-bg');
if (heroBg) {
  window.addEventListener('scroll', () => {
    const y = window.scrollY;
    heroBg.style.transform = `translateY(${y * 0.3}px)`;
  }, { passive: true });
}

// ============================================================
// QUANTITY INLINE CONTROLS (Food Detail Page)
// ============================================================
window.increaseQty = function(inputId) {
  const inp = document.getElementById(inputId);
  if (inp) inp.value = Math.min(parseInt(inp.value) + 1, 20);
};
window.decreaseQty = function(inputId) {
  const inp = document.getElementById(inputId);
  if (inp) inp.value = Math.max(parseInt(inp.value) - 1, 1);
};

// ============================================================
// ADD TO CART FROM DETAIL PAGE
// ============================================================
window.addToCartDetail = function(id, name, price, image) {
  const qtyEl = document.getElementById('detailQty');
  const qty = qtyEl ? parseInt(qtyEl.value) : 1;
  Cart.add(id, name, price, image, qty);
};

// ============================================================
// IMAGE GALLERY (Food Detail)
// ============================================================
function initGallery() {
  const thumbs = document.querySelectorAll('.gallery-thumb');
  const mainImg = document.getElementById('mainFoodImg');
  thumbs.forEach(thumb => {
    thumb.addEventListener('click', () => {
      thumbs.forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
      if (mainImg) { mainImg.style.opacity = '0'; setTimeout(() => { mainImg.src = thumb.dataset.src; mainImg.style.opacity = '1'; }, 200); }
    });
  });
}
initGallery();

// ============================================================
// MOBILE — Update bottom nav active state
// ============================================================
document.querySelectorAll('.mobile-nav-btn').forEach(btn => {
  const href = btn.getAttribute('href') || '';
  if (href && window.location.pathname.includes(href.replace('.php', ''))) btn.classList.add('active');
});

console.log('%c🍽 HAVELI Restaurant — Premium System Loaded', 'color:#FF6B00;font-size:14px;font-weight:bold;');
