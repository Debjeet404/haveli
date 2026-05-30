<?php
$adminTitle = 'Dashboard';
require_once 'includes/header.php';
$pdo = getDB();

// Stats
$totalOrders    = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders  = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$todayOrders    = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$totalRevenue   = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered'")->fetchColumn();
$todayRevenue   = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered' AND DATE(created_at)=CURDATE()")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$newCustomers   = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$totalFoods     = $pdo->query("SELECT COUNT(*) FROM foods WHERE is_active=1")->fetchColumn();

// Recent orders
$recentOrders = $pdo->query("
    SELECT o.*, COALESCE(u.name, o.customer_name) as cname
    FROM orders o LEFT JOIN users u ON o.user_id=u.id
    ORDER BY o.created_at DESC LIMIT 8
")->fetchAll();

// Monthly revenue (last 6 months)
$monthlyData = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%b') as month,
           COALESCE(SUM(total),0) as revenue,
           COUNT(*) as orders
    FROM orders
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at,'%Y-%m'), DATE_FORMAT(created_at,'%b')
    ORDER BY MIN(created_at)
")->fetchAll();

// Top foods
$topFoods = $pdo->query("
    SELECT f.name, SUM(oi.quantity) as sold, SUM(oi.subtotal) as revenue
    FROM order_items oi JOIN foods f ON oi.food_id=f.id
    GROUP BY oi.food_id ORDER BY sold DESC LIMIT 5
")->fetchAll();

$currency = getSetting('site_currency','₨');
?>

<style>
.activity-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border);}
.activity-item:last-child{border-bottom:none;}
.mini-chart-bar{background:linear-gradient(0deg,var(--primary),rgba(255,107,0,.3));border-radius:4px 4px 0 0;transition:height .5s ease;min-height:4px;}
</style>

<!-- Stats Grid -->
<div class="stats-grid">
  <div class="stat-card orange">
    <div class="stat-icon orange">📦</div>
    <div class="stat-label">Total Orders</div>
    <div class="stat-value"><?= number_format($totalOrders) ?></div>
    <div class="stat-change up">▲ <?= $todayOrders ?> today</div>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon gold">💰</div>
    <div class="stat-label">Total Revenue</div>
    <div class="stat-value"><?= $currency . number_format($totalRevenue) ?></div>
    <div class="stat-change up">▲ <?= $currency . number_format($todayRevenue) ?> today</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon green">👥</div>
    <div class="stat-label">Customers</div>
    <div class="stat-value"><?= number_format($totalCustomers) ?></div>
    <div class="stat-change up">▲ <?= $newCustomers ?> new today</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon blue">🍽️</div>
    <div class="stat-label">Active Foods</div>
    <div class="stat-value"><?= number_format($totalFoods) ?></div>
    <?php if ($pendingOrders > 0): ?>
    <div class="stat-change down">⚠️ <?= $pendingOrders ?> pending orders</div>
    <?php else: ?>
    <div class="stat-change up">✓ All orders handled</div>
    <?php endif; ?>
  </div>
</div>

<!-- Charts + Recent Orders -->
<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;margin-bottom:20px">

  <!-- Revenue Chart (Mini Bar) -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">📈 Revenue — Last 6 Months</span>
      <span style="font-size:.78rem;color:var(--text-3)"><?= $currency . number_format($totalRevenue) ?> total</span>
    </div>
    <div class="card-body" style="padding-bottom:16px">
      <?php if (!empty($monthlyData)):
        $maxRev = max(array_column($monthlyData,'revenue')) ?: 1;
      ?>
      <div style="display:flex;align-items:flex-end;gap:10px;height:160px;padding-bottom:20px;position:relative">
        <?php foreach ($monthlyData as $m):
          $h = max(4, round(($m['revenue'] / $maxRev) * 130));
        ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;height:100%;justify-content:flex-end">
          <div style="font-size:.65rem;color:var(--primary);font-weight:700"><?= $currency . number_format($m['revenue']/1000,1) ?>k</div>
          <div class="mini-chart-bar" style="width:100%;height:<?= $h ?>px" title="<?= $m['month'] ?>: <?= $currency . number_format($m['revenue']) ?>"></div>
          <div style="font-size:.7rem;color:var(--text-3)"><?= $m['month'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="empty-state"><div class="empty-state-icon">📊</div><p class="empty-state-text">No revenue data yet</p></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Top Foods -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">🏆 Top Selling</span>
      <a href="pages/foods.php" class="btn btn-ghost btn-sm">All Foods</a>
    </div>
    <div class="card-body" style="padding:0">
      <?php if (!empty($topFoods)): $maxSold = max(array_column($topFoods,'sold')) ?: 1; ?>
      <?php foreach ($topFoods as $i => $food): ?>
      <div class="activity-item" style="padding:12px 20px">
        <div style="width:22px;height:22px;background:linear-gradient(135deg,var(--primary),var(--gold));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:800;color:#fff;flex-shrink:0"><?= $i+1 ?></div>
        <div style="flex:1;min-width:0">
          <p style="font-size:.83rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($food['name']) ?></p>
          <div style="height:3px;background:var(--glass);border-radius:3px;margin-top:5px;overflow:hidden">
            <div style="height:100%;width:<?= round($food['sold']/$maxSold*100) ?>%;background:linear-gradient(90deg,var(--primary),var(--gold));border-radius:3px"></div>
          </div>
        </div>
        <div style="text-align:right;flex-shrink:0">
          <p style="font-size:.78rem;font-weight:700;color:var(--primary)"><?= $food['sold'] ?> sold</p>
          <p style="font-size:.7rem;color:var(--text-3)"><?= $currency . number_format($food['revenue']) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <div class="empty-state" style="padding:30px 20px"><p class="empty-state-text">No orders yet</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Recent Orders Table -->
<div class="card">
  <div class="card-header">
    <span class="card-title">📋 Recent Orders</span>
    <div style="display:flex;gap:8px;align-items:center">
      <?php if ($pendingOrders > 0): ?>
      <span class="badge badge-pending"><?= $pendingOrders ?> Pending</span>
      <?php endif; ?>
      <a href="pages/orders.php" class="btn btn-ghost btn-sm">View All</a>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Total</th>
          <th>Payment</th>
          <th>Status</th>
          <th>Time</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentOrders)): ?>
        <tr><td colspan="7"><div class="empty-state"><p class="empty-state-text">No orders yet</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($recentOrders as $o): ?>
        <tr>
          <td><span style="font-weight:700;color:var(--gold);font-size:.82rem"><?= htmlspecialchars($o['order_number']) ?></span></td>
          <td>
            <div class="td-name"><?= htmlspecialchars($o['cname']) ?></div>
            <div class="td-muted"><?= htmlspecialchars($o['customer_phone']) ?></div>
          </td>
          <td><strong style="color:var(--primary)"><?= $currency . number_format($o['total']) ?></strong></td>
          <td><span style="font-size:.78rem"><?= $o['payment_method']==='cod' ? '💵 COD' : '💳 Online' ?></span></td>
          <td>
            <select class="filter-input" style="padding:4px 8px;font-size:.75rem;height:28px" onchange="updateStatus('<?= $o['id'] ?>',this.value)" title="Update status">
              <?php foreach (['pending','accepted','preparing','out_for_delivery','delivered','cancelled'] as $s): ?>
              <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><span style="font-size:.75rem;color:var(--text-3)"><?= date('M j, g:i A', strtotime($o['created_at'])) ?></span></td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="pages/orders.php?view=<?= $o['id'] ?>" class="btn btn-ghost btn-icon btn-sm" title="View">👁</a>
              <a href="<?= BASE_URL ?>/track.php?order=<?= urlencode($o['order_number']) ?>" target="_blank" class="btn btn-ghost btn-icon btn-sm" title="Track">🛵</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
async function updateStatus(orderId, status) {
  const res = await fetch('<?= BASE_URL ?>/admin/ajax/update_order_status.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({id: orderId, status: status})
  });
  const data = await res.json();
  if (data.success) adminToast('Order status updated!','success');
  else adminToast('Failed to update status','error');
}
</script>

<?php require_once 'includes/footer.php'; ?>
