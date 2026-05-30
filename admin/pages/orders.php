<?php
$adminTitle = 'Orders Management';
require_once '../includes/header.php';
$pdo = getDB();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        $id     = (int)($_POST['id'] ?? 0);
        $status = sanitize($_POST['status'] ?? '');
        $valid  = ['pending','accepted','preparing','out_for_delivery','delivered','cancelled'];
        if ($id && in_array($status, $valid)) {
            $pdo->prepare("UPDATE orders SET status=?, updated_at=NOW() WHERE id=?")->execute([$status, $id]);
            // Notify user
            $order = $pdo->prepare("SELECT * FROM orders WHERE id=?");
            $order->execute([$id]);
            $o = $order->fetch();
            if ($o && $o['user_id']) {
                $labels = ['accepted'=>'confirmed','preparing'=>'being prepared','out_for_delivery'=>'on the way','delivered'=>'delivered','cancelled'=>'cancelled'];
                if (isset($labels[$status])) {
                    $pdo->prepare("INSERT INTO notifications (user_id,title,message,type) VALUES (?,?,?,?)")
                        ->execute([$o['user_id'], "Order Update 🍽️", "Your order {$o['order_number']} is now {$labels[$status]}.", 'order']);
                }
            }
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json'); echo json_encode(['success'=>true]); exit;
            }
            header('Location: orders.php?updated=1'); exit;
        }
    }
}

// Filters
$statusF = sanitize($_GET['status'] ?? '');
$q       = sanitize($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$viewId  = (int)($_GET['view'] ?? 0);

$where  = ['1=1']; $params = [];
if ($statusF) { $where[] = 'o.status=?'; $params[] = $statusF; }
if ($q) {
    $where[] = '(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ?)';
    $params = array_merge($params, ["%$q%", "%$q%", "%$q%"]);
}
$whereSQL = implode(' AND ', $where);

$total   = $pdo->prepare("SELECT COUNT(*) FROM orders o WHERE $whereSQL");
$total->execute($params); $totalCount = $total->fetchColumn();
$pages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT o.*, COALESCE(u.name, o.customer_name) as cname
    FROM orders o LEFT JOIN users u ON o.user_id=u.id
    WHERE $whereSQL ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Single order view
$viewOrder = null; $viewItems = [];
if ($viewId) {
    $vs = $pdo->prepare("SELECT o.*, COALESCE(u.name,o.customer_name) as cname, u.email as uemail FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE o.id=?");
    $vs->execute([$viewId]); $viewOrder = $vs->fetch();
    if ($viewOrder) {
        $vis = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
        $vis->execute([$viewId]); $viewItems = $vis->fetchAll();
    }
}

$currency = getSetting('site_currency','₨');
$statuses = ['pending','accepted','preparing','out_for_delivery','delivered','cancelled'];
$statusCounts = [];
foreach ($statuses as $s) {
    $sc = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status=?");
    $sc->execute([$s]); $statusCounts[$s] = $sc->fetchColumn();
}
?>

<div class="page-header">
  <div>
    <div class="page-header-title">📦 Orders Management</div>
    <div class="page-header-sub"><?= number_format($totalCount) ?> orders found</div>
  </div>
  <div style="display:flex;gap:8px">
    <a href="?export=csv" class="btn btn-ghost btn-sm">📥 Export CSV</a>
  </div>
</div>

<?php if (isset($_GET['updated'])): ?>
<div class="alert alert-success">✅ Order status updated successfully.</div>
<?php endif; ?>

<!-- Status Quick Filter Tabs -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
  <a href="?" class="btn btn-sm <?= !$statusF?'btn-primary':'btn-ghost' ?>">All (<?= array_sum($statusCounts) ?>)</a>
  <?php foreach ($statusCounts as $s => $cnt): ?>
  <a href="?status=<?= $s ?><?= $q?"&q=".urlencode($q):'' ?>" class="btn btn-sm <?= $statusF===$s?'btn-primary':'btn-ghost' ?>">
    <?= ucwords(str_replace('_',' ',$s)) ?> (<?= $cnt ?>)
  </a>
  <?php endforeach; ?>
</div>

<!-- Search Bar -->
<form method="GET" class="filter-bar" style="margin-bottom:16px">
  <?php if ($statusF): ?><input type="hidden" name="status" value="<?= htmlspecialchars($statusF) ?>"><?php endif; ?>
  <input type="search" name="q" class="filter-input" placeholder="Search by order#, name, phone..." value="<?= htmlspecialchars($q) ?>" style="flex:1;min-width:220px">
  <button type="submit" class="btn btn-primary btn-sm">Search</button>
  <?php if ($q): ?><a href="?<?= $statusF?"status=$statusF":'' ?>" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
</form>

<!-- ORDER DETAIL VIEW -->
<?php if ($viewOrder): ?>
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <span class="card-title">📋 Order: <span style="color:var(--gold)"><?= htmlspecialchars($viewOrder['order_number']) ?></span></span>
    <a href="orders.php" class="btn btn-ghost btn-sm">← Back to Orders</a>
  </div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:24px">
      <div>
        <p style="font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--text-3);margin-bottom:6px">Customer</p>
        <p style="font-weight:600"><?= htmlspecialchars($viewOrder['cname']) ?></p>
        <p style="font-size:.83rem;color:var(--text-3)"><?= htmlspecialchars($viewOrder['customer_email']) ?></p>
        <p style="font-size:.83rem;color:var(--text-3)"><?= htmlspecialchars($viewOrder['customer_phone']) ?></p>
      </div>
      <div>
        <p style="font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--text-3);margin-bottom:6px">Delivery Address</p>
        <p style="font-size:.88rem;color:var(--text-2)"><?= htmlspecialchars($viewOrder['delivery_address']) ?></p>
      </div>
      <div>
        <p style="font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--text-3);margin-bottom:6px">Order Info</p>
        <p style="font-size:.85rem">Placed: <?= date('M j, Y g:i A', strtotime($viewOrder['created_at'])) ?></p>
        <p style="font-size:.85rem">Payment: <?= $viewOrder['payment_method']==='cod'?'Cash on Delivery':'Online' ?></p>
        <?php if ($viewOrder['coupon_code']): ?><p style="font-size:.85rem">Coupon: <strong style="color:var(--gold)"><?= htmlspecialchars($viewOrder['coupon_code']) ?></strong></p><?php endif; ?>
        <?php if ($viewOrder['notes']): ?><p style="font-size:.83rem;color:var(--text-3)">Note: <?= htmlspecialchars($viewOrder['notes']) ?></p><?php endif; ?>
      </div>
    </div>

    <!-- Items -->
    <div style="background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius);overflow:hidden;margin-bottom:16px">
      <table style="width:100%;border-collapse:collapse;font-size:.85rem">
        <thead><tr style="border-bottom:1px solid var(--border)">
          <th style="padding:10px 14px;text-align:left;font-size:.7rem;text-transform:uppercase;letter-spacing:.1em;color:var(--text-3)">Item</th>
          <th style="padding:10px 14px;text-align:right;color:var(--text-3)">Price</th>
          <th style="padding:10px 14px;text-align:center;color:var(--text-3)">Qty</th>
          <th style="padding:10px 14px;text-align:right;color:var(--text-3)">Subtotal</th>
        </tr></thead>
        <tbody>
        <?php foreach ($viewItems as $item): ?>
        <tr style="border-bottom:1px solid var(--border)">
          <td style="padding:12px 14px">
            <div style="display:flex;align-items:center;gap:10px">
              <?php if ($item['food_image']): ?><img src="<?= BASE_URL.'/'.$item['food_image'] ?>" style="width:36px;height:36px;border-radius:6px;object-fit:cover" alt=""><?php endif; ?>
              <span style="font-weight:600"><?= htmlspecialchars($item['food_name']) ?></span>
            </div>
          </td>
          <td style="padding:12px 14px;text-align:right;color:var(--text-3)"><?= $currency.number_format($item['price']) ?></td>
          <td style="padding:12px 14px;text-align:center"><?= $item['quantity'] ?></td>
          <td style="padding:12px 14px;text-align:right;font-weight:700;color:var(--primary)"><?= $currency.number_format($item['subtotal']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Totals -->
    <div style="max-width:280px;margin-left:auto">
      <div style="display:flex;justify-content:space-between;font-size:.85rem;color:var(--text-3);margin-bottom:6px"><span>Subtotal</span><span><?= $currency.number_format($viewOrder['subtotal']) ?></span></div>
      <div style="display:flex;justify-content:space-between;font-size:.85rem;color:var(--text-3);margin-bottom:6px"><span>Delivery</span><span><?= $currency.number_format($viewOrder['delivery_fee']) ?></span></div>
      <div style="display:flex;justify-content:space-between;font-size:.85rem;color:var(--text-3);margin-bottom:6px"><span>Tax</span><span><?= $currency.number_format($viewOrder['tax']) ?></span></div>
      <?php if ($viewOrder['discount']>0): ?><div style="display:flex;justify-content:space-between;font-size:.85rem;color:#22c55e;margin-bottom:6px"><span>Discount</span><span>-<?= $currency.number_format($viewOrder['discount']) ?></span></div><?php endif; ?>
      <div style="display:flex;justify-content:space-between;font-size:1rem;font-weight:800;color:var(--primary);padding-top:10px;border-top:1px solid var(--border)"><span>Total</span><span><?= $currency.number_format($viewOrder['total']) ?></span></div>
    </div>

    <!-- Update Status -->
    <div style="margin-top:20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
      <form method="POST" style="display:flex;align-items:center;gap:10px">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="id" value="<?= $viewOrder['id'] ?>">
        <label style="font-size:.83rem;color:var(--text-3)">Update Status:</label>
        <select name="status" class="filter-input" style="height:36px">
          <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $viewOrder['status']===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Update</button>
      </form>
      <a href="javascript:window.print()" class="btn btn-ghost btn-sm">🖨 Print Invoice</a>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Orders Table -->
<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Items</th>
          <th>Total</th>
          <th>Payment</th>
          <th>Status</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
        <tr><td colspan="8"><div class="empty-state"><div class="empty-state-icon">📦</div><p class="empty-state-text">No orders found</p></div></td></tr>
        <?php else: foreach ($orders as $o):
          $ic = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id=?");
          $ic->execute([$o['id']]); $itemCount = $ic->fetchColumn();
        ?>
        <tr>
          <td><a href="?view=<?= $o['id'] ?>" style="font-weight:700;color:var(--gold);font-size:.82rem"><?= htmlspecialchars($o['order_number']) ?></a></td>
          <td>
            <div class="td-name"><?= htmlspecialchars($o['cname']) ?></div>
            <div class="td-muted"><?= htmlspecialchars($o['customer_phone']) ?></div>
          </td>
          <td><span style="font-size:.82rem"><?= $itemCount ?> item<?= $itemCount!=1?'s':'' ?></span></td>
          <td><strong style="color:var(--primary)"><?= $currency.number_format($o['total']) ?></strong></td>
          <td><span style="font-size:.78rem"><?= $o['payment_method']==='cod'?'💵 COD':'💳 Online' ?></span></td>
          <td>
            <select class="filter-input" style="padding:4px 8px;font-size:.75rem;height:28px"
                    onchange="quickUpdateStatus(<?= $o['id'] ?>, this.value, this)">
              <?php foreach ($statuses as $s): ?>
              <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><span style="font-size:.75rem;color:var(--text-3)"><?= date('M j, g:i A', strtotime($o['created_at'])) ?></span></td>
          <td>
            <div style="display:flex;gap:5px">
              <a href="?view=<?= $o['id'] ?>" class="btn btn-ghost btn-icon btn-sm" title="View Details">👁</a>
              <a href="<?= BASE_URL ?>/track.php?order=<?= urlencode($o['order_number']) ?>" target="_blank" class="btn btn-ghost btn-icon btn-sm" title="Track">🛵</a>
            </div>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <div class="card-footer" style="display:flex;align-items:center;justify-content:space-between">
    <span style="font-size:.82rem;color:var(--text-3)">Page <?= $page ?> of <?= $pages ?> (<?= $totalCount ?> orders)</span>
    <div style="display:flex;gap:6px">
      <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?><?= $statusF?"&status=$statusF":'' ?><?= $q?"&q=".urlencode($q):'' ?>" class="btn btn-ghost btn-sm">← Prev</a><?php endif; ?>
      <?php for ($i = max(1,$page-2); $i <= min($pages,$page+2); $i++): ?>
      <a href="?page=<?= $i ?><?= $statusF?"&status=$statusF":'' ?><?= $q?"&q=".urlencode($q):'' ?>" class="btn btn-sm <?= $i==$page?'btn-primary':'btn-ghost' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $pages): ?><a href="?page=<?= $page+1 ?><?= $statusF?"&status=$statusF":'' ?><?= $q?"&q=".urlencode($q):'' ?>" class="btn btn-ghost btn-sm">Next →</a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
async function quickUpdateStatus(orderId, status, select) {
  const orig = select.dataset.orig || select.value;
  select.dataset.orig = orig;
  try {
    const res = await fetch('<?= BASE_URL ?>/admin/ajax/update_order_status.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({id: orderId, status})
    });
    const data = await res.json();
    if (data.success) adminToast('Status updated to: '+status.replace(/_/g,' '),'success');
    else { adminToast('Update failed','error'); select.value = orig; }
  } catch { adminToast('Connection error','error'); select.value = orig; }
}
</script>

<?php require_once '../includes/footer.php'; ?>
