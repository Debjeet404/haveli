<?php
$adminTitle = 'Customers';
require_once '../includes/header.php';
$pdo = getDB();

$q    = sanitize($_GET['q'] ?? '');
$page = max(1,(int)($_GET['page'] ?? 1));
$per  = 20;

$where = ['1=1']; $params = [];
if ($q) { $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)'; $params = array_merge($params,["%$q%","%$q%","%$q%"]); }
$whereSQL = implode(' AND ',$where);

$total = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $whereSQL");
$total->execute($params); $totalCount = $total->fetchColumn();
$pages = ceil($totalCount / $per);
$offset = ($page-1)*$per;

$stmt = $pdo->prepare("
    SELECT u.*,
           COUNT(o.id) as order_count,
           COALESCE(SUM(CASE WHEN o.status='delivered' THEN o.total ELSE 0 END),0) as total_spent
    FROM users u LEFT JOIN orders o ON u.id=o.user_id
    WHERE $whereSQL GROUP BY u.id ORDER BY u.created_at DESC LIMIT $per OFFSET $offset
");
$stmt->execute($params);
$customers = $stmt->fetchAll();
$currency = getSetting('site_currency','₨');

// Handle toggle active
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_active') {
    $uid = (int)$_POST['id']; $val = (int)$_POST['value'];
    $pdo->prepare("UPDATE users SET is_active=? WHERE id=?")->execute([$val,$uid]);
    header('Content-Type: application/json'); echo json_encode(['success'=>true]); exit;
}
?>

<div class="page-header">
  <div><div class="page-header-title">👥 Customers</div><div class="page-header-sub"><?= number_format($totalCount) ?> registered users</div></div>
</div>

<form method="GET" class="filter-bar" style="margin-bottom:16px">
  <input type="search" name="q" class="filter-input" placeholder="Search by name, email or phone..." value="<?= htmlspecialchars($q) ?>" style="flex:1">
  <button type="submit" class="btn btn-primary btn-sm">Search</button>
  <?php if ($q): ?><a href="?" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
</form>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Avatar</th><th>Name</th><th>Email / Phone</th><th>Orders</th><th>Spent</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if (empty($customers)): ?>
        <tr><td colspan="8"><div class="empty-state"><p class="empty-state-text">No customers found</p></div></td></tr>
        <?php else: foreach ($customers as $c): ?>
        <tr>
          <td>
            <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--gold));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.82rem;overflow:hidden">
              <?php if ($c['avatar']): ?><img src="<?= BASE_URL.'/'.$c['avatar'] ?>" style="width:100%;height:100%;object-fit:cover" alt=""><?php else: ?><?= strtoupper(substr($c['name'],0,1)) ?><?php endif; ?>
            </div>
          </td>
          <td><div class="td-name"><?= htmlspecialchars($c['name']) ?></div></td>
          <td><div style="font-size:.82rem"><?= htmlspecialchars($c['email']) ?></div><div class="td-muted"><?= htmlspecialchars($c['phone'] ?? '—') ?></div></td>
          <td><span class="badge badge-active"><?= $c['order_count'] ?> orders</span></td>
          <td><strong style="color:var(--primary)"><?= $currency.number_format($c['total_spent']) ?></strong></td>
          <td><span style="font-size:.75rem;color:var(--text-3)"><?= date('M j, Y',strtotime($c['created_at'])) ?></span></td>
          <td>
            <button class="badge <?= $c['is_active']?'badge-active':'badge-inactive' ?>"
                    onclick="toggleCustomer(<?= $c['id'] ?>,<?= $c['is_active']?0:1 ?>,this)"
                    style="border:none;cursor:pointer">
              <?= $c['is_active']?'Active':'Suspended' ?>
            </button>
          </td>
          <td>
            <a href="<?= BASE_URL ?>/admin/pages/orders.php?q=<?= urlencode($c['email']) ?>" class="btn btn-ghost btn-icon btn-sm" title="View Orders">📦</a>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <div class="card-footer" style="display:flex;align-items:center;justify-content:space-between">
    <span style="font-size:.82rem;color:var(--text-3)">Page <?= $page ?> of <?= $pages ?></span>
    <div style="display:flex;gap:6px">
      <?php if ($page>1): ?><a href="?page=<?= $page-1 ?><?= $q?"&q=".urlencode($q):'' ?>" class="btn btn-ghost btn-sm">← Prev</a><?php endif; ?>
      <?php if ($page<$pages): ?><a href="?page=<?= $page+1 ?><?= $q?"&q=".urlencode($q):'' ?>" class="btn btn-ghost btn-sm">Next →</a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
async function toggleCustomer(id, val, btn) {
  const ok = await ajaxAction('<?= BASE_URL ?>/admin/pages/customers.php',
    {action:'toggle_active',id,value:val}, val?'Customer activated':'Customer suspended');
  if (ok) {
    btn.className = val ? 'badge badge-active' : 'badge badge-inactive';
    btn.textContent = val ? 'Active' : 'Suspended';
    btn.setAttribute('onclick',`toggleCustomer(${id},${val?0:1},this)`);
  }
}
</script>

<?php require_once '../includes/footer.php'; ?>
