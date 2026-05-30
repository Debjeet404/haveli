<?php
require_once 'includes/config.php';
$pageTitle = 'Track Order';
$pdo = getDB();

$orderNum = sanitize($_GET['order'] ?? '');
$order = null;
$orderItems = [];

if ($orderNum) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
    $stmt->execute([$orderNum]);
    $order = $stmt->fetch();

    if ($order) {
        $istmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $istmt->execute([$order['id']]);
        $orderItems = $istmt->fetchAll();
    }
}

$currency = getSetting('site_currency','₨');
$estTime  = getSetting('estimated_delivery_time','30-45');

$statusSteps = [
    'pending'          => ['icon'=>'📋','label'=>'Order Placed','desc'=>'Your order has been received.'],
    'accepted'         => ['icon'=>'✅','label'=>'Order Accepted','desc'=>'Restaurant has confirmed your order.'],
    'preparing'        => ['icon'=>'👨‍🍳','label'=>'Preparing','desc'=>'Our chefs are crafting your meal.'],
    'out_for_delivery' => ['icon'=>'🛵','label'=>'Out for Delivery','desc'=>'Your order is on the way!'],
    'delivered'        => ['icon'=>'🎉','label'=>'Delivered','desc'=>'Enjoy your meal! Thank you.'],
];
$statusOrder = array_keys($statusSteps);
$currentIdx = $order ? array_search($order['status'], $statusOrder) : -1;

require_once 'includes/header.php';
?>

<style>
.track-page { padding: calc(var(--nav-height)+40px) clamp(16px,4vw,48px) 80px; }
.track-inner { max-width: 700px; margin: 0 auto; }
.track-search-box { background: var(--glass); border: 1px solid var(--glass-border); border-radius: var(--radius-xl); padding: 32px; backdrop-filter: blur(20px); margin-bottom: 32px; }
.track-result-box { background: var(--glass); border: 1px solid var(--glass-border); border-radius: var(--radius-xl); padding: 32px; backdrop-filter: blur(20px); }
.order-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid var(--border); }
.order-num { font-family: var(--font-display); font-size: 1.6rem; font-weight: 900; color: var(--gold); }
.order-meta { font-size: .85rem; color: var(--text-muted); margin-top: 4px; }
.track-progress { background: var(--glass); border: 1px solid var(--glass-border); border-radius: var(--radius-md); padding: 4px; margin-bottom: 32px; }
.track-progress-bar { height: 6px; border-radius: 6px; background: linear-gradient(90deg, var(--primary), var(--gold)); transition: width 1s ease; }
.order-item-row { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border); }
.order-item-row:last-child { border-bottom: none; }
.order-item-thumb { width: 50px; height: 50px; border-radius: var(--radius-sm); overflow: hidden; background: var(--glass); flex-shrink: 0; }
.order-item-thumb img { width: 100%; height: 100%; object-fit: cover; }
.eta-badge { display: inline-flex; align-items: center; gap: 8px; background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.2); border-radius: var(--radius-md); padding: 10px 18px; color: #22c55e; font-weight: 600; font-size: .88rem; }
</style>

<div class="track-page">
  <div class="track-inner">
    <p class="section-label">Live Tracking</p>
    <h1 class="section-title" style="font-size:clamp(1.8rem,4vw,2.5rem);margin-bottom:32px">
      Track Your <span class="highlight">Order</span>
    </h1>

    <!-- Search Form -->
    <div class="track-search-box">
      <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap">
        <div style="flex:1;min-width:200px">
          <input type="text" name="order" class="form-input"
                 value="<?= htmlspecialchars($orderNum) ?>"
                 placeholder="Enter order number (e.g. HVL-XXXXXXXX)"
                 required aria-label="Order number">
        </div>
        <button type="submit" class="btn btn-primary">🔍 Track</button>
      </form>
      <?php if ($orderNum && !$order): ?>
      <p style="color:#ef4444;margin-top:12px;font-size:.88rem">⚠️ No order found with number <strong><?= htmlspecialchars($orderNum) ?></strong>. Please check and try again.</p>
      <?php endif; ?>
    </div>

    <!-- Order Result -->
    <?php if ($order): ?>
    <div class="track-result-box" id="orderTracker">

      <!-- Order Header -->
      <div class="order-header">
        <div>
          <div class="order-num"><?= htmlspecialchars($order['order_number']) ?></div>
          <div class="order-meta">
            Placed on <?= date('M j, Y \a\t g:i A', strtotime($order['created_at'])) ?>
          </div>
          <div class="order-meta">
            <?= htmlspecialchars($order['customer_name']) ?> · <?= htmlspecialchars($order['customer_phone']) ?>
          </div>
        </div>
        <div>
          <div id="orderStatusBadge" class="status-badge status-<?= $order['status'] ?> status-active">
            <span class="status-dot"></span>
            <?= ucwords(str_replace('_',' ', $order['status'])) ?>
          </div>
          <?php if (!in_array($order['status'], ['delivered','cancelled'])): ?>
          <div class="eta-badge" style="margin-top:10px">
            ⏱ Est. <?= $estTime ?> min
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Progress Bar -->
      <?php
        $progressMap = ['pending'=>10,'accepted'=>35,'preparing'=>60,'out_for_delivery'=>85,'delivered'=>100,'cancelled'=>0];
        $progress = $progressMap[$order['status']] ?? 0;
      ?>
      <?php if ($order['status'] !== 'cancelled'): ?>
      <div class="track-progress" style="margin-bottom:24px">
        <div class="track-progress-bar" style="width:<?= $progress ?>%"></div>
      </div>
      <?php endif; ?>

      <!-- Status Steps -->
      <?php if ($order['status'] !== 'cancelled'): ?>
      <div class="order-track-steps" style="margin-bottom:32px">
        <?php foreach ($statusSteps as $statusKey => $step):
          $idx = array_search($statusKey, $statusOrder);
          $stepClass = '';
          if ($idx < $currentIdx) $stepClass = 'completed';
          elseif ($idx === $currentIdx) $stepClass = 'active';
        ?>
        <div class="order-step <?= $stepClass ?>">
          <div class="step-icon"><?= $step['icon'] ?></div>
          <div class="step-info">
            <div class="step-title"><?= $step['label'] ?></div>
            <div class="step-desc"><?= $step['desc'] ?></div>
            <?php if ($idx === $currentIdx): ?>
            <div class="step-time" style="color:var(--primary);font-weight:600;margin-top:4px">● Current Status</div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div style="text-align:center;padding:24px;background:rgba(239,68,68,.05);border:1px solid rgba(239,68,68,.2);border-radius:var(--radius-md);margin-bottom:24px">
        <div style="font-size:2.5rem;margin-bottom:8px">❌</div>
        <p style="color:#ef4444;font-weight:600">Order Cancelled</p>
        <p style="font-size:.85rem;color:var(--text-muted)">This order has been cancelled.</p>
      </div>
      <?php endif; ?>

      <!-- Delivery Address -->
      <div style="background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius-md);padding:16px;margin-bottom:24px">
        <p style="font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:8px">📍 Delivery Address</p>
        <p style="font-size:.9rem;color:var(--text-secondary)"><?= htmlspecialchars($order['delivery_address']) ?></p>
      </div>

      <!-- Order Items -->
      <?php if (!empty($orderItems)): ?>
      <div>
        <p style="font-family:var(--font-display);font-weight:700;margin-bottom:16px">Order Items</p>
        <?php foreach ($orderItems as $item): ?>
        <div class="order-item-row">
          <div class="order-item-thumb">
            <?php if ($item['food_image']): ?>
            <img src="<?= BASE_URL.'/'.$item['food_image'] ?>" alt="<?= htmlspecialchars($item['food_name']) ?>">
            <?php else: ?>
            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:1.3rem">🍽️</div>
            <?php endif; ?>
          </div>
          <div style="flex:1">
            <p style="font-weight:600;font-size:.9rem"><?= htmlspecialchars($item['food_name']) ?></p>
            <p style="font-size:.78rem;color:var(--text-muted)">Qty: <?= $item['quantity'] ?></p>
          </div>
          <p style="font-weight:700;color:var(--primary)"><?= $currency . number_format($item['subtotal']) ?></p>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Price Breakdown -->
      <div style="background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius-md);padding:20px;margin-top:24px">
        <div style="display:flex;justify-content:space-between;font-size:.88rem;color:var(--text-secondary);margin-bottom:8px">
          <span>Subtotal</span><span><?= $currency . number_format($order['subtotal']) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.88rem;color:var(--text-secondary);margin-bottom:8px">
          <span>Delivery</span><span><?= $currency . number_format($order['delivery_fee']) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.88rem;color:var(--text-secondary);margin-bottom:8px">
          <span>Tax</span><span><?= $currency . number_format($order['tax']) ?></span>
        </div>
        <?php if ($order['discount'] > 0): ?>
        <div style="display:flex;justify-content:space-between;font-size:.88rem;color:#22c55e;margin-bottom:8px">
          <span>Discount</span><span>-<?= $currency . number_format($order['discount']) ?></span>
        </div>
        <?php endif; ?>
        <div style="display:flex;justify-content:space-between;font-family:var(--font-display);font-size:1.1rem;font-weight:800;color:var(--primary);padding-top:12px;border-top:1px solid var(--border)">
          <span>Total</span><span><?= $currency . number_format($order['total']) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.82rem;color:var(--text-muted);margin-top:8px">
          <span>Payment Method</span>
          <span><?= $order['payment_method'] === 'cod' ? '💵 Cash on Delivery' : '💳 Online' ?></span>
        </div>
      </div>

      <?php if (!in_array($order['status'],['delivered','cancelled'])): ?>
      <p style="text-align:center;font-size:.78rem;color:var(--text-muted);margin-top:20px" id="autoRefreshMsg">
        🔄 Auto-refreshing status every 15 seconds
      </p>
      <?php endif; ?>

    </div><!-- /track-result-box -->

    <?php elseif (!$orderNum): ?>
    <!-- Empty State -->
    <div style="text-align:center;padding:60px 20px;background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius-xl)">
      <div style="font-size:4rem;margin-bottom:16px">🛵</div>
      <h2 style="font-family:var(--font-display);font-size:1.3rem;margin-bottom:8px">Track Your Order</h2>
      <p style="color:var(--text-secondary);max-width:360px;margin:0 auto">Enter your order number above to see real-time status updates for your Haveli order.</p>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php if ($order && !in_array($order['status'], ['delivered','cancelled'])): ?>
<script>
// Start live polling
startOrderTracking('<?= $order['id'] ?>');
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
