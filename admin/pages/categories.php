<?php
$adminTitle = 'Categories';
require_once '../includes/header.php';
$pdo = getDB();

$msg = ''; $msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $slug = generateSlug($name);
        $icon = sanitize($_POST['icon'] ?? '🍽️');
        $desc = sanitize($_POST['description'] ?? '');
        $sort = (int)($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;

        if ($id) {
            $pdo->prepare("UPDATE categories SET name=?,slug=?,icon=?,description=?,sort_order=?,is_active=? WHERE id=?")->execute([$name,$slug,$icon,$desc,$sort,$active,$id]);
        } else {
            $check = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug=?"); $check->execute([$slug]);
            if ($check->fetchColumn()) $slug .= '-'.time();
            $pdo->prepare("INSERT INTO categories (name,slug,icon,description,sort_order,is_active) VALUES (?,?,?,?,?,?)")->execute([$name,$slug,$icon,$desc,$sort,$active]);
        }
        $msg = $id ? 'Category updated!' : 'Category added!'; $msgType = 'success';
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $foodCount = $pdo->prepare("SELECT COUNT(*) FROM foods WHERE category_id=?"); $foodCount->execute([$id]);
        if ($foodCount->fetchColumn() > 0) { $msg = 'Cannot delete: category has foods. Remove or reassign foods first.'; $msgType = 'error'; }
        else { $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]); $msg = 'Category deleted.'; $msgType = 'success'; }
    }
}

$edit = (int)($_GET['edit'] ?? 0);
$editCat = null;
if ($edit) { $s = $pdo->prepare("SELECT * FROM categories WHERE id=?"); $s->execute([$edit]); $editCat = $s->fetch(); }
$categories = $pdo->query("SELECT c.*,(SELECT COUNT(*) FROM foods WHERE category_id=c.id AND is_active=1) as food_count FROM categories c ORDER BY c.sort_order,c.id")->fetchAll();
?>

<div class="page-header">
  <div><div class="page-header-title">📂 Categories</div></div>
  <a href="?edit=new" class="btn btn-primary">+ Add Category</a>
</div>

<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= $msgType==='success'?'✅':'❌' ?> <?= htmlspecialchars($msg) ?></div><?php endif; ?>

<?php if (isset($_GET['edit'])): ?>
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <span class="card-title"><?= $editCat ? '✏️ Edit Category' : '➕ New Category' ?></span>
    <a href="?" class="btn btn-ghost btn-sm">← Back</a>
  </div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= $editCat['id'] ?? 0 ?>">
      <div class="form-row cols-3">
        <div class="form-group">
          <label class="form-label">Category Name <span class="req">*</span></label>
          <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editCat['name'] ?? '') ?>" placeholder="e.g. Biryani">
        </div>
        <div class="form-group">
          <label class="form-label">Emoji Icon</label>
          <input type="text" name="icon" class="form-control" value="<?= htmlspecialchars($editCat['icon'] ?? '🍽️') ?>" placeholder="🍽️" maxlength="5">
        </div>
        <div class="form-group">
          <label class="form-label">Sort Order</label>
          <input type="number" name="sort_order" class="form-control" value="<?= $editCat['sort_order'] ?? 0 ?>">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Description (optional)</label>
          <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($editCat['description'] ?? '') ?>" placeholder="Short description...">
        </div>
      </div>
      <label style="display:flex;align-items:center;gap:8px;margin-bottom:16px;cursor:pointer">
        <input type="checkbox" name="is_active" value="1" <?= ($editCat['is_active'] ?? 1) ? 'checked' : '' ?> style="width:16px;height:16px;accent-color:var(--primary)">
        <span style="font-size:.85rem">Active (visible on website)</span>
      </label>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">💾 Save Category</button>
        <a href="?" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Icon</th><th>Name</th><th>Slug</th><th>Foods</th><th>Status</th><th>Sort</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if (empty($categories)): ?>
        <tr><td colspan="7"><div class="empty-state"><p class="empty-state-text">No categories yet</p></div></td></tr>
        <?php else: foreach ($categories as $c): ?>
        <tr>
          <td style="font-size:1.4rem"><?= htmlspecialchars($c['icon']) ?></td>
          <td><div class="td-name"><?= htmlspecialchars($c['name']) ?></div></td>
          <td><span style="font-size:.78rem;color:var(--text-3)"><?= htmlspecialchars($c['slug']) ?></span></td>
          <td><span class="badge badge-active"><?= $c['food_count'] ?> foods</span></td>
          <td><span class="badge <?= $c['is_active']?'badge-active':'badge-inactive' ?>"><?= $c['is_active']?'Active':'Hidden' ?></span></td>
          <td><?= $c['sort_order'] ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="?edit=<?= $c['id'] ?>" class="btn btn-ghost btn-icon btn-sm">✏️</a>
              <button class="btn btn-danger btn-icon btn-sm" onclick="showConfirm('Delete Category','Delete &quot;<?= addslashes($c['name']) ?>&quot;?',()=>deleteCat(<?= $c['id'] ?>))">🗑</button>
            </div>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function deleteCat(id) {
  const f=document.createElement('form');f.method='POST';
  f.innerHTML=`<input name="action" value="delete"><input name="id" value="${id}">`;
  document.body.appendChild(f);f.submit();
}
</script>

<?php require_once '../includes/footer.php'; ?>
