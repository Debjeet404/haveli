<?php
$adminTitle = 'Coupons';
require_once '../includes/header.php';
$pdo = getDB();

$msg = ''; $msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id      = (int)($_POST['id'] ?? 0);
        $code    = strtoupper(sanitize($_POST['code'] ?? ''));
        $type    = in_array($_POST['type'] ?? '', ['percentage','fixed']) ? $_POST['type'] : 'percentage';
        $value   = (float)($_POST['value'] ?? 0);
        $minOrd  = (float)($_POST['min_order'] ?? 0);
        $maxDisc = $_POST['max_discount'] !== '' ? (float)$_POST['max_discount'] : null;
        $usesLim = $_POST['uses_limit'] !== '' ? (int)$_POST['uses_limit'] : null;
        $active  = isset($_POST['is_active']) ? 1 : 0;
        $expires = $_POST['expires_at'] ?: null;
        if ($code && $value > 0) {
            if ($id) {
                $pdo->prepare("UPDATE coupons SET code=?,type=?,value=?,min_order=?,max_discount=?,uses_limit=?,is_active=?,expires_at=? WHERE id=?")->execute([$code,$type,$value,$minOrd,$maxDisc,$usesLim,$active,$expires,$id]);
            } else {
                $pdo->prepare("INSERT INTO coupons (code,type,value,min_order,max_discount,uses_limit,is_active,expires_at) VALUES (?,?,?,?,?,?,?,?)")->execute([$code,$type,$value,$minOrd,$maxDisc,$usesLim,$active,$expires]);
            }
            $msg = $id ? 'Coupon updated!' : 'Coupon created!'; $msgType = 'success';
        } else { $msg = 'Code and value are required.'; $msgType = 'error'; }
    }
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM coupons WHERE id=?")->execute([(int)$_POST['id']]);
        $msg = 'Coupon deleted.'; $msgType = 'success';
    }
    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0); $val = (int)($_POST['value'] ?? 0);
        $pdo->prepare("UPDATE coupons SET is_active=? WHERE id=?")->execute([$val,$id]);
        header('Content-Type: application/json'); echo json_encode(['success'=>true]); exit;
    }
}

$edit = (int)($_GET['edit'] ?? 0);
$editCoupon = null;
if ($edit) { $s = $pdo->prepare("SELECT * FROM coupons WHERE id=?"); $s->execute([$edit]); $editCoupon = $s->fetch(); }
$coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
$currency = getSetting('site_currency','₨');
?>

<div class="page-header">
  <div><div class="page-header-title">🏷️ Coupons</div></div>
  <a href="?edit=new" class="btn btn-primary">+ Add Coupon</a>
</div>

<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= $msgType==='success'?'✅':'❌' ?> <?= htmlspecialchars($msg) ?></div><?php endif; ?>

<?php if (isset($_GET['edit'])): ?>
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <span class="card-title"><?= $editCoupon ? '✏️ Edit Coupon' : '➕ New Coupon' ?></span>
    <a href="?" class="btn btn-ghost btn-sm">← Back</a>
  </div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= $editCoupon['id'] ?? 0 ?>">
      <div class="form-row cols-3">
        <div class="form-group">
          <label class="form-label">Coupon Code <span class="req">*</span></label>
          <input type="text" name="code" class="form-control" required value="<?= htmlspecialchars($editCoupon['code'] ?? '') ?>" placeholder="e.g. SAVE20" style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
        </div>
        <div class="form-group">
          <label class="form-label">Discount Type</label>
          <select name="type" class="form-control">
            <option value="percentage" <?= ($editCoupon['type'] ?? 'percentage')==='percentage'?'selected':'' ?>>Percentage (%)</option>
            <option value="fixed" <?= ($editCoupon['type'] ?? '')==='fixed'?'selected':'' ?>>Fixed Amount (<?= $currency ?>)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Discount Value <span class="req">*</span></label>
          <input type="number" name="value" class="form-control" required step="0.01" value="<?= $editCoupon['value'] ?? '' ?>" placeholder="10 (% or fixed amount)">
        </div>
        <div class="form-group">
          <label class="form-label">Minimum Order Amount</label>
          <input type="number" name="min_order" class="form-control" step="0.01" value="<?= $editCoupon['min_order'] ?? 0 ?>" placeholder="0 = no minimum">
        </div>
        <div class="form-group">
          <label class="form-label">Maximum Discount (leave blank for no limit)</label>
          <input type="number" name="max_discount" class="form-control" step="0.01" value="<?= $editCoupon['max_discount'] ?? '' ?>" placeholder="Optional cap">
        </div>
        <div class="form-group">
          <label class="form-label">Total Uses Limit (blank = unlimited)</label>
          <input type="number" name="uses_limit" class="form-control" value="<?= $editCoupon['uses_limit'] ?? '' ?>" placeholder="e.g. 100">
        </div>
        <div class="form-group">
          <label class="form-label">Expiry Date (optional)</label>
          <input type="date" name="expires_at" class="form-control" value="<?= $editCoupon['expires_at'] ?? '' ?>">
        </div>
      </div>
      <label style="display:flex;align-items:center;gap:8px;margin-bottom:16px;cursor:pointer">
        <input type="checkbox" name="is_active" value="1" <?= ($editCoupon['is_active'] ?? 1) ? 'checked' : '' ?> style="width:16px;height:16px;accent-color:var(--primary)">
        <span style="font-size:.85rem">Active (usable by customers)</span>
      </label>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">💾 Save Coupon</button>
        <a href="?" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Min Order</th><th>Uses</th><th>Expires</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if (empty($coupons)): ?>
        <tr><td colspan="8"><div class="empty-state"><p class="empty-state-text">No coupons yet</p></div></td></tr>
        <?php else: foreach ($coupons as $c): $expired = $c['expires_at'] && $c['expires_at'] < date('Y-m-d'); ?>
        <tr>
          <td><strong style="font-family:var(--font-display);color:var(--gold);font-size:.9rem"><?= htmlspecialchars($c['code']) ?></strong></td>
          <td><span class="badge badge-active"><?= $c['type']==='percentage'?'%':'Fixed' ?></span></td>
          <td><strong style="color:var(--primary)"><?= $c['type']==='percentage' ? $c['value'].'%' : $currency.number_format($c['value']) ?></strong></td>
          <td><?= $c['min_order'] > 0 ? $currency.number_format($c['min_order']) : '<span style="color:var(--text-3)">None</span>' ?></td>
          <td><span style="font-size:.82rem"><?= $c['uses_count'] ?><?= $c['uses_limit'] ? ' / '.$c['uses_limit'] : '' ?></span></td>
          <td><span style="font-size:.78rem;color:<?= $expired?'#ef4444':'var(--text-3)' ?>"><?= $c['expires_at'] ? date('M j, Y',strtotime($c['expires_at'])) : '—' ?><?= $expired?' (Expired)':'' ?></span></td>
          <td><span class="badge <?= $c['is_active']&&!$expired?'badge-active':'badge-inactive' ?>"><?= $c['is_active']&&!$expired?'Active':'Inactive' ?></span></td>
          <td>
            <div style="display:flex;gap:5px">
              <a href="?edit=<?= $c['id'] ?>" class="btn btn-ghost btn-icon btn-sm">✏️</a>
              <button class="btn btn-danger btn-icon btn-sm" onclick="showConfirm('Delete Coupon','Delete code &quot;<?= addslashes($c['code']) ?>&quot;?',()=>deleteCoupon(<?= $c['id'] ?>))">🗑</button>
            </div>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function deleteCoupon(id) {
  const f=document.createElement('form');f.method='POST';
  f.innerHTML=`<input name="action" value="delete"><input name="id" value="${id}">`;
  document.body.appendChild(f);f.submit();
}
</script>

<?php require_once '../includes/footer.php'; ?>
