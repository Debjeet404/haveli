<?php
require_once 'includes/config.php';
$pageTitle = 'Checkout';
$pdo = getDB();
$currency    = getSetting('site_currency','₨');
$deliveryFee = getSetting('delivery_fee','150');
$freeAbove   = getSetting('free_delivery_above','2000');
$taxRate     = getSetting('tax_percentage','5');
$minOrder    = getSetting('min_order_amount','500');

// Pre-fill user data if logged in
$userData = [];
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch();

    // Default address
    $astmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id=? AND is_default=1 LIMIT 1");
    $astmt->execute([$_SESSION['user_id']]);
    $defaultAddr = $astmt->fetch();
}

$csrf = generateCSRF();
require_once 'includes/header.php';
?>

<style>
.checkout-page { padding: calc(var(--nav-height)+40px) clamp(16px,4vw,48px) 80px; }
.checkout-grid { max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: 1fr 380px; gap: 32px; align-items: start; }
.checkout-box { background: var(--glass); border: 1px solid var(--glass-border); border-radius: var(--radius-xl); padding: 32px; backdrop-filter: blur(20px); }
.checkout-section-title { font-family: var(--font-display); font-size: 1.15rem; font-weight: 700; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }
.checkout-section-title span { width: 28px; height: 28px; background: linear-gradient(135deg,var(--primary),var(--gold)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .8rem; color: #fff; font-weight: 700; }
.order-summary-item { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border); }
.order-summary-item:last-of-type { border-bottom: none; }
.summary-item-img { width: 52px; height: 52px; border-radius: var(--radius-sm); overflow: hidden; background: var(--glass); flex-shrink: 0; }
.summary-item-img img, .summary-item-img div { width: 100%; height: 100%; object-fit: cover; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
.summary-row { display: flex; justify-content: space-between; font-size: .88rem; color: var(--text-secondary); margin-bottom: 8px; }
.summary-total { display: flex; justify-content: space-between; font-family: var(--font-display); font-size: 1.15rem; font-weight: 800; color: var(--primary); padding-top: 12px; border-top: 1px solid var(--border); margin-top: 4px; }
.payment-option { display: flex; align-items: center; gap: 14px; background: var(--glass); border: 2px solid var(--glass-border); border-radius: var(--radius-md); padding: 14px 18px; cursor: none; transition: var(--transition-fast); margin-bottom: 10px; }
.payment-option.selected { border-color: var(--primary); background: rgba(255,107,0,0.06); }
.payment-option input[type=radio] { display: none; }
.payment-radio { width: 18px; height: 18px; border: 2px solid var(--glass-border); border-radius: 50%; flex-shrink: 0; transition: var(--transition-fast); position: relative; }
.payment-option.selected .payment-radio { border-color: var(--primary); background: var(--primary); }
.payment-option.selected .payment-radio::after { content: ''; width: 6px; height: 6px; background: #fff; border-radius: 50%; position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); }
.addr-option { background: var(--glass); border: 2px solid var(--glass-border); border-radius: var(--radius-md); padding: 14px; cursor: none; transition: var(--transition-fast); margin-bottom: 8px; }
.addr-option.selected { border-color: var(--primary); }
#orderCartEmpty { display: none; text-align: center; padding: 40px 20px; }
@media(max-width:900px) { .checkout-grid { grid-template-columns: 1fr; } }
</style>

<div class="checkout-page">
  <div style="max-width:1100px;margin:0 auto;margin-bottom:32px">
    <p class="section-label">Almost There</p>
    <h1 class="section-title">Complete Your <span class="highlight">Order</span></h1>
  </div>

  <form id="checkoutForm" method="POST" action="<?= BASE_URL ?>/api/place_order.php" onsubmit="return handleCheckout(event)">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    <input type="hidden" name="cart_data" id="cartDataInput">
    <input type="hidden" name="coupon_code" id="checkoutCoupon">
    <input type="hidden" name="payment_method" id="selectedPayment" value="cod">

    <div class="checkout-grid">

      <!-- LEFT: Delivery + Payment -->
      <div>
        <!-- Delivery Details -->
        <div class="checkout-box" style="margin-bottom:24px">
          <div class="checkout-section-title"><span>1</span> Delivery Details</div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div class="form-group" style="grid-column:1/-1">
              <label class="form-label">Full Name *</label>
              <input type="text" name="customer_name" class="form-input" required
                     value="<?= htmlspecialchars($userData['name'] ?? '') ?>" placeholder="Your full name">
              <div class="form-error" style="display:none">Name is required</div>
            </div>
            <div class="form-group">
              <label class="form-label">Email Address *</label>
              <input type="email" name="customer_email" class="form-input" required
                     value="<?= htmlspecialchars($userData['email'] ?? '') ?>" placeholder="your@email.com">
              <div class="form-error" style="display:none">Valid email required</div>
            </div>
            <div class="form-group">
              <label class="form-label">Phone Number *</label>
              <input type="tel" name="customer_phone" class="form-input" required
                     value="<?= htmlspecialchars($userData['phone'] ?? '') ?>" placeholder="+92 300 0000000">
              <div class="form-error" style="display:none">Phone required</div>
            </div>
            <div class="form-group" style="grid-column:1/-1">
              <label class="form-label">Delivery Address *</label>
              <textarea name="delivery_address" class="form-textarea" required rows="3" placeholder="Full delivery address including area and landmark..."><?php
                if (!empty($defaultAddr)) {
                    echo htmlspecialchars($defaultAddr['address_line1'] . ', ' . $defaultAddr['city'] . ', ' . $defaultAddr['state']);
                }
              ?></textarea>
              <div class="form-error" style="display:none">Address is required</div>
            </div>
            <div class="form-group" style="grid-column:1/-1">
              <label class="form-label">Special Instructions (optional)</label>
              <textarea name="notes" class="form-textarea" rows="2" placeholder="Any allergies, preferences or instructions..."></textarea>
            </div>
          </div>

          <?php if (isLoggedIn()):
            $allAddr = $pdo->prepare("SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC");
            $allAddr->execute([$_SESSION['user_id']]);
            $addresses = $allAddr->fetchAll();
            if (!empty($addresses)): ?>
          <div style="margin-top:8px">
            <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:10px">Or select saved address:</p>
            <?php foreach ($addresses as $addr): ?>
            <div class="addr-option <?= $addr['is_default'] ? 'selected' : '' ?>"
                 onclick="selectAddress(this, '<?= addslashes($addr['address_line1'].', '.$addr['city'].', '.$addr['state']) ?>')">
              <strong style="font-size:.88rem"><?= htmlspecialchars($addr['label']) ?></strong> —
              <span style="font-size:.83rem;color:var(--text-secondary)"><?= htmlspecialchars($addr['address_line1'] . ', ' . $addr['city']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; endif; ?>
        </div>

        <!-- Payment Method -->
        <div class="checkout-box">
          <div class="checkout-section-title"><span>2</span> Payment Method</div>

          <div class="payment-option selected" onclick="selectPayment(this,'cod')">
            <div class="payment-radio"></div>
            <div>
              <p style="font-weight:600;font-size:.95rem">💵 Cash on Delivery</p>
              <p style="font-size:.78rem;color:var(--text-muted)">Pay when your order arrives</p>
            </div>
          </div>

          <div class="payment-option" onclick="selectPayment(this,'online')" style="opacity:.6" title="Coming soon">
            <div class="payment-radio"></div>
            <div>
              <p style="font-weight:600;font-size:.95rem">💳 Online Payment <span style="font-size:.7rem;color:var(--gold)">(Coming Soon)</span></p>
              <p style="font-size:.78rem;color:var(--text-muted)">JazzCash, EasyPaisa, Card</p>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT: Order Summary -->
      <div>
        <div class="checkout-box" style="position:sticky;top:calc(var(--nav-height)+20px)">
          <div class="checkout-section-title"><span>3</span> Order Summary</div>

          <!-- Cart Items (rendered by JS) -->
          <div id="checkoutItems">
            <p style="text-align:center;color:var(--text-muted);padding:24px 0" id="loadingCart">Loading cart...</p>
          </div>

          <!-- Coupon -->
          <div style="display:flex;gap:8px;margin:16px 0">
            <input type="text" id="checkoutCouponInput" class="form-input" placeholder="Coupon code" style="font-size:.85rem;padding:9px 12px">
            <button type="button" class="btn btn-ghost btn-sm" onclick="applyCheckoutCoupon()">Apply</button>
          </div>
          <div id="couponMsg" style="font-size:.8rem;margin-bottom:12px;display:none"></div>

          <!-- Summary Rows -->
          <div class="summary-row"><span>Subtotal</span><span id="co-subtotal">₨0</span></div>
          <div class="summary-row"><span>Delivery Fee</span><span id="co-delivery">₨0</span></div>
          <div class="summary-row"><span>Tax (<?= $taxRate ?>%)</span><span id="co-tax">₨0</span></div>
          <div class="summary-row" id="co-discount-row" style="display:none"><span>Discount</span><span id="co-discount" style="color:#22c55e">-₨0</span></div>
          <div class="summary-total">
            <span>Total</span>
            <span id="co-total">₨0</span>
          </div>

          <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:20px" id="placeOrderBtn">
            🛍️ Place Order
          </button>

          <p style="font-size:.75rem;color:var(--text-muted);text-align:center;margin-top:12px;line-height:1.6">
            By placing your order you agree to our terms of service. Free delivery above <?= $currency . number_format((float)$freeAbove) ?>.
          </p>
        </div>
      </div>

    </div>
  </form>
</div>

<script>
const CUR = '<?= $currency ?>';
const DELIVERY_FEE = <?= $deliveryFee ?>;
const FREE_ABOVE = <?= $freeAbove ?>;
const TAX_RATE = <?= $taxRate ?> / 100;
let checkoutCoupon = null;

function fmt(n) { return CUR + Math.round(n).toLocaleString(); }

function renderCheckoutCart() {
  const items = Cart.getItems();
  const container = document.getElementById('checkoutItems');
  if (!items.length) {
    container.innerHTML = `<div style="text-align:center;padding:24px"><p style="color:var(--text-muted)">Your cart is empty</p><a href="menu.php" class="btn btn-primary btn-sm" style="margin-top:12px">Browse Menu</a></div>`;
    document.getElementById('placeOrderBtn').disabled = true;
    return;
  }
  container.innerHTML = items.map(item => `
    <div class="order-summary-item">
      <div class="summary-item-img">
        ${item.image ? `<img src="${item.image}" alt="${item.name}">` : `<div>🍽️</div>`}
      </div>
      <div style="flex:1">
        <p style="font-weight:600;font-size:.88rem">${item.name}</p>
        <p style="font-size:.78rem;color:var(--text-muted)">Qty: ${item.qty}</p>
      </div>
      <p style="font-weight:700;color:var(--primary);font-size:.9rem">${fmt(item.price * item.qty)}</p>
    </div>
  `).join('');
  updateSummary();
}

function updateSummary() {
  const sub = Cart.getSubtotal();
  const fee = sub >= FREE_ABOVE || sub === 0 ? 0 : DELIVERY_FEE;
  const tax = sub * TAX_RATE;
  const disc = checkoutCoupon ? parseFloat(checkoutCoupon.discount) : 0;
  const total = Math.max(0, sub + fee + tax - disc);

  document.getElementById('co-subtotal').textContent = fmt(sub);
  document.getElementById('co-delivery').textContent = fee === 0 ? 'FREE' : fmt(fee);
  document.getElementById('co-tax').textContent = fmt(tax);
  document.getElementById('co-total').textContent = fmt(total);

  const discRow = document.getElementById('co-discount-row');
  if (disc > 0) {
    discRow.style.display = 'flex';
    document.getElementById('co-discount').textContent = '-' + fmt(disc);
  } else discRow.style.display = 'none';
}

async function applyCheckoutCoupon() {
  const code = document.getElementById('checkoutCouponInput').value.trim().toUpperCase();
  const msgEl = document.getElementById('couponMsg');
  if (!code) return;

  try {
    const res = await fetch('/haveli/api/coupon.php', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ code, subtotal: Cart.getSubtotal() })
    });
    const data = await res.json();
    if (data.success) {
      checkoutCoupon = data.coupon;
      document.getElementById('checkoutCoupon').value = code;
      msgEl.style.display = 'block';
      msgEl.style.color = '#22c55e';
      msgEl.textContent = `✓ Coupon applied! You save ${fmt(data.coupon.discount)}`;
      updateSummary();
      showToast('Coupon applied!', 'success', '🎉');
    } else {
      msgEl.style.display = 'block';
      msgEl.style.color = '#ef4444';
      msgEl.textContent = data.message || 'Invalid coupon';
    }
  } catch { showToast('Could not validate coupon','error','✗'); }
}

function selectPayment(el, method) {
  document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('selectedPayment').value = method;
}

function selectAddress(el, addr) {
  document.querySelectorAll('.addr-option').forEach(o => o.classList.remove('selected'));
  el.classList.add('selected');
  document.querySelector('textarea[name="delivery_address"]').value = addr;
}

function handleCheckout(e) {
  e.preventDefault();
  const form = document.getElementById('checkoutForm');
  if (!validateCheckout(form)) return false;

  const items = Cart.getItems();
  const sub = Cart.getSubtotal();
  const fee = sub >= FREE_ABOVE ? 0 : DELIVERY_FEE;
  const tax = sub * TAX_RATE;
  const disc = checkoutCoupon ? parseFloat(checkoutCoupon.discount) : 0;
  const total = Math.max(0, sub + fee + tax - disc);

  document.getElementById('cartDataInput').value = JSON.stringify({ items, subtotal: sub, delivery_fee: fee, tax, discount: disc, total });

  const btn = document.getElementById('placeOrderBtn');
  btn.disabled = true;
  btn.innerHTML = '⏳ Placing Order...';

  fetch(form.action, {
    method: 'POST',
    body: new FormData(form)
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      Cart.clear();
      showToast('Order placed successfully! 🎉', 'success', '✓');
      setTimeout(() => { window.location.href = `/haveli/track.php?order=${data.order_number}`; }, 1000);
    } else {
      btn.disabled = false;
      btn.innerHTML = '🛍️ Place Order';
      showToast(data.message || 'Order failed. Try again.', 'error', '✗');
    }
  })
  .catch(() => {
    btn.disabled = false;
    btn.innerHTML = '🛍️ Place Order';
    showToast('Connection error. Please try again.', 'error', '✗');
  });
  return false;
}

// Init
document.addEventListener('DOMContentLoaded', () => {
  renderCheckoutCart();
  // Redirect if cart is empty after 1 second
  setTimeout(() => {
    if (Cart.getItems().length === 0) {
      showToast('Your cart is empty!','info','🛒');
    }
  }, 500);
});
</script>

<?php require_once 'includes/footer.php'; ?>
