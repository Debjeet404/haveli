<?php
$adminTitle = 'Foods Management';
require_once '../includes/header.php';
$pdo = getDB();

// Handle actions
$msg = ''; $msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id       = (int)($_POST['id'] ?? 0);
        $catId    = (int)($_POST['category_id'] ?? 0);
        $name     = sanitize($_POST['name'] ?? '');
        $desc     = sanitize($_POST['description'] ?? '');
        $price    = (float)($_POST['price'] ?? 0);
        $discP    = $_POST['discounted_price'] !== '' ? (float)$_POST['discounted_price'] : null;
        $ings     = sanitize($_POST['ingredients'] ?? '');
        $spicy    = sanitize($_POST['spicy_level'] ?? 'mild');
        $prep     = (int)($_POST['prep_time'] ?? 20);
        $rating   = (float)($_POST['rating'] ?? 4.5);
        $isFeat   = isset($_POST['is_featured']) ? 1 : 0;
        $isPop    = isset($_POST['is_popular']) ? 1 : 0;
        $isAvail  = isset($_POST['is_available']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $tags     = sanitize($_POST['tags'] ?? '');
        $slug     = generateSlug($name);

        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $up = uploadFile($_FILES['image'], 'foods');
            if (isset($up['success'])) $imagePath = $up['path'];
            elseif (isset($up['error'])) { $msg = $up['error']; $msgType = 'error'; }
        }

        if (!$msg) {
            if ($id) {
                $sql = "UPDATE foods SET category_id=?,name=?,description=?,price=?,discounted_price=?,ingredients=?,spicy_level=?,prep_time=?,rating=?,is_featured=?,is_popular=?,is_available=?,is_active=?,tags=?,slug=?";
                $params = [$catId,$name,$desc,$price,$discP,$ings,$spicy,$prep,$rating,$isFeat,$isPop,$isAvail,$isActive,$tags,$slug];
                if ($imagePath) { $sql .= ",image=?"; $params[] = $imagePath; }
                $sql .= " WHERE id=?"; $params[] = $id;
            } else {
                // Unique slug
                $checkSlug = $pdo->prepare("SELECT COUNT(*) FROM foods WHERE slug=?");
                $checkSlug->execute([$slug]);
                if ($checkSlug->fetchColumn()) $slug .= '-' . time();

                $sql = "INSERT INTO foods (category_id,name,slug,description,price,discounted_price,ingredients,spicy_level,prep_time,rating,is_featured,is_popular,is_available,is_active,tags,image) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                $params = [$catId,$name,$slug,$desc,$price,$discP,$ings,$spicy,$prep,$rating,$isFeat,$isPop,$isAvail,$isActive,$tags,$imagePath];
            }
            $pdo->prepare($sql)->execute($params);
            $msg = $id ? 'Food item updated!' : 'Food item added!';
            $msgType = 'success';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE foods SET is_active=0 WHERE id=?")->execute([$id]);
        $msg = 'Food deleted.'; $msgType = 'success';
    }

    if ($action === 'toggle') {
        $id  = (int)($_POST['id'] ?? 0);
        $val = (int)($_POST['value'] ?? 0);
        $col = sanitize($_POST['col'] ?? 'is_active');
        if (in_array($col, ['is_active','is_available','is_featured','is_popular'])) {
            $pdo->prepare("UPDATE foods SET $col=? WHERE id=?")->execute([$val,$id]);
        }
        header('Content-Type: application/json');
        echo json_encode(['success'=>true]); exit;
    }
}

// Filter & search
$q      = sanitize($_GET['q'] ?? '');
$catF   = (int)($_GET['cat'] ?? 0);
$edit   = (int)($_GET['edit'] ?? 0);
$editFood = null;
if ($edit) { $s = $pdo->prepare("SELECT * FROM foods WHERE id=?"); $s->execute([$edit]); $editFood = $s->fetch(); }

$where = ["1=1"]; $params = [];
if ($q) { $where[] = "(f.name LIKE ? OR f.tags LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($catF) { $where[] = "f.category_id=?"; $params[] = $catF; }
$whereSQL = implode(' AND ',$where);

$foods = $pdo->prepare("SELECT f.*,c.name as cat_name FROM foods f JOIN categories c ON f.category_id=c.id WHERE $whereSQL ORDER BY f.sort_order ASC,f.id DESC");
$foods->execute($params);
$foodList = $foods->fetchAll();

$categories = $pdo->query("SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order")->fetchAll();
$currency   = getSetting('site_currency','₨');
?>

<div class="page-header">
  <div>
    <div class="page-header-title">🍽️ Foods Management</div>
    <div class="page-header-sub"><?= count($foodList) ?> items found</div>
  </div>
  <div style="display:flex;gap:8px">
    <a href="?edit=new" class="btn btn-primary">+ Add New Food</a>
  </div>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?>"><?= $msgType==='success'?'✅':'❌' ?> <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- ADD/EDIT FORM -->
<?php if ($edit === -1 || isset($_GET['edit'])): ?>
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <span class="card-title"><?= $editFood ? '✏️ Edit: '.htmlspecialchars($editFood['name']) : '➕ Add New Food' ?></span>
    <a href="?" class="btn btn-ghost btn-sm">← Back to List</a>
  </div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= $editFood['id'] ?? 0 ?>">

      <div class="form-row cols-3">
        <div class="form-group">
          <label class="form-label">Food Name <span class="req">*</span></label>
          <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editFood['name'] ?? '') ?>" placeholder="e.g. Dum Gosht Biryani">
        </div>
        <div class="form-group">
          <label class="form-label">Category <span class="req">*</span></label>
          <select name="category_id" class="form-control" required>
            <option value="">Select category</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($editFood['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Spicy Level</label>
          <select name="spicy_level" class="form-control">
            <?php foreach (['mild','medium','hot','extra_hot'] as $s): ?>
            <option value="<?= $s ?>" <?= ($editFood['spicy_level'] ?? 'mild') === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Detailed description..."><?= htmlspecialchars($editFood['description'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="form-row cols-3">
        <div class="form-group">
          <label class="form-label">Price (<?= $currency ?>) <span class="req">*</span></label>
          <input type="number" name="price" class="form-control" required step="0.01" value="<?= $editFood['price'] ?? '' ?>" placeholder="0.00">
        </div>
        <div class="form-group">
          <label class="form-label">Discounted Price (leave blank for none)</label>
          <input type="number" name="discounted_price" class="form-control" step="0.01" value="<?= $editFood['discounted_price'] ?? '' ?>" placeholder="0.00">
        </div>
        <div class="form-group">
          <label class="form-label">Preparation Time (minutes)</label>
          <input type="number" name="prep_time" class="form-control" value="<?= $editFood['prep_time'] ?? 20 ?>" min="1">
        </div>
      </div>

      <div class="form-row cols-2">
        <div class="form-group">
          <label class="form-label">Ingredients (comma-separated)</label>
          <input type="text" name="ingredients" class="form-control" value="<?= htmlspecialchars($editFood['ingredients'] ?? '') ?>" placeholder="Lamb, Basmati Rice, Saffron, ...">
        </div>
        <div class="form-group">
          <label class="form-label">Tags (comma-separated)</label>
          <input type="text" name="tags" class="form-control" value="<?= htmlspecialchars($editFood['tags'] ?? '') ?>" placeholder="biryani,special,signature">
        </div>
      </div>

      <div class="form-row cols-2">
        <div class="form-group">
          <label class="form-label">Rating (1-5)</label>
          <input type="number" name="rating" class="form-control" step="0.1" min="1" max="5" value="<?= $editFood['rating'] ?? 4.5 ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Food Image</label>
          <div class="upload-box" onclick="this.querySelector('input').click()">
            <input type="file" name="image" accept="image/*">
            <?php if ($editFood['image'] ?? null): ?>
            <img src="<?= BASE_URL.'/'.$editFood['image'] ?>" class="upload-preview" alt="">
            <?php else: ?>
            <p style="color:var(--text-3);font-size:.82rem">📷 Click to upload image</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Toggles -->
      <div style="display:flex;flex-wrap:wrap;gap:24px;margin:12px 0 20px">
        <?php
        $toggles = [
          ['is_featured','⭐ Featured'],
          ['is_popular','🔥 Popular'],
          ['is_available','✅ Available'],
          ['is_active','👁 Active'],
        ];
        foreach ($toggles as [$name,$label]):
          $checked = $editFood ? (bool)$editFood[$name] : true;
        ?>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
          <input type="checkbox" name="<?= $name ?>" value="1" <?= $checked?'checked':'' ?> style="width:16px;height:16px;accent-color:var(--primary)">
          <span style="font-size:.85rem"><?= $label ?></span>
        </label>
        <?php endforeach; ?>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">💾 Save Food</button>
        <a href="?" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- FILTER BAR -->
<div class="filter-bar">
  <form method="GET" style="display:flex;gap:8px;flex:1;flex-wrap:wrap">
    <input type="search" name="q" class="filter-input" placeholder="Search foods..." value="<?= htmlspecialchars($q) ?>" style="flex:1;min-width:180px">
    <select name="cat" class="filter-input" onchange="this.form.submit()">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
      <option value="<?= $cat['id'] ?>" <?= $catF==$cat['id']?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <?php if ($q || $catF): ?><a href="?" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
  </form>
</div>

<!-- FOODS TABLE -->
<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Image</th>
          <th>Name</th>
          <th>Category</th>
          <th>Price</th>
          <th>Rating</th>
          <th>Featured</th>
          <th>Available</th>
          <th>Active</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($foodList)): ?>
        <tr><td colspan="9"><div class="empty-state"><div class="empty-state-icon">🍽️</div><p class="empty-state-text">No foods found. <a href="?edit=new" style="color:var(--primary)">Add one!</a></p></div></td></tr>
        <?php else: ?>
        <?php foreach ($foodList as $f):
          $dispPrice = $f['discounted_price'] && $f['discounted_price'] < $f['price'] ? $f['discounted_price'] : $f['price'];
        ?>
        <tr id="food-row-<?= $f['id'] ?>">
          <td>
            <?php if ($f['image']): ?>
            <img src="<?= BASE_URL.'/'.$f['image'] ?>" class="td-img" alt="<?= htmlspecialchars($f['name']) ?>">
            <?php else: ?>
            <div class="td-img-placeholder">🍽️</div>
            <?php endif; ?>
          </td>
          <td>
            <div class="td-name"><?= htmlspecialchars($f['name']) ?></div>
            <div class="td-muted">⏱ <?= $f['prep_time'] ?>min · <?= ucfirst(str_replace('_',' ',$f['spicy_level'])) ?></div>
          </td>
          <td><span style="font-size:.8rem"><?= htmlspecialchars($f['cat_name']) ?></span></td>
          <td>
            <strong style="color:var(--primary)"><?= $currency . number_format($dispPrice) ?></strong>
            <?php if ($f['discounted_price'] && $f['discounted_price'] < $f['price']): ?>
            <div class="td-muted" style="text-decoration:line-through"><?= $currency . number_format($f['price']) ?></div>
            <?php endif; ?>
          </td>
          <td><span style="color:var(--gold)">★ <?= number_format($f['rating'],1) ?></span></td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="id" value="<?= $f['id'] ?>">
              <input type="hidden" name="col" value="is_featured">
              <input type="hidden" name="value" value="<?= $f['is_featured'] ? 0 : 1 ?>">
              <button type="submit" class="btn btn-ghost btn-icon btn-sm" title="Toggle featured">
                <?= $f['is_featured'] ? '⭐' : '☆' ?>
              </button>
            </form>
          </td>
          <td>
            <span class="badge <?= $f['is_available'] ? 'badge-active' : 'badge-inactive' ?>">
              <?= $f['is_available'] ? 'Yes' : 'No' ?>
            </span>
          </td>
          <td>
            <span class="badge <?= $f['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
              <?= $f['is_active'] ? 'Active' : 'Hidden' ?>
            </span>
          </td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="?edit=<?= $f['id'] ?>" class="btn btn-ghost btn-icon btn-sm" title="Edit">✏️</a>
              <a href="<?= BASE_URL ?>/food.php?slug=<?= urlencode($f['slug']) ?>" target="_blank" class="btn btn-ghost btn-icon btn-sm" title="View">👁</a>
              <button class="btn btn-danger btn-icon btn-sm" title="Delete"
                      onclick="showConfirm('Delete Food','Delete &quot;<?= addslashes($f['name']) ?>&quot;? This cannot be undone.',()=>deleteFood(<?= $f['id'] ?>))">🗑</button>
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
function deleteFood(id) {
  const form = document.createElement('form');
  form.method = 'POST';
  form.innerHTML = `<input name="action" value="delete"><input name="id" value="${id}">`;
  document.body.appendChild(form);
  form.submit();
}
</script>

<?php require_once '../includes/footer.php'; ?>
