<?php
$adminTitle = 'Banners';
require_once '../includes/header.php';
$pdo = getDB();

$msg = ''; $msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id    = (int)($_POST['id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $sub   = sanitize($_POST['subtitle'] ?? '');
        $link  = sanitize($_POST['link'] ?? '');
        $badge = sanitize($_POST['badge'] ?? '');
        $sort  = (int)($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        $imgPath = null;
        if (!empty($_FILES['image']['name'])) {
            $up = uploadFile($_FILES['image'], 'banners');
            if (isset($up['success'])) $imgPath = $up['path'];
        }
        if ($id) {
            $sql = "UPDATE banners SET title=?,subtitle=?,link=?,badge=?,sort_order=?,is_active=?";
            $p = [$title,$sub,$link,$badge,$sort,$active];
            if ($imgPath) { $sql.=",image=?"; $p[]=$imgPath; }
            $sql.=" WHERE id=?"; $p[]=$id;
            $pdo->prepare($sql)->execute($p);
        } else {
            if (!$imgPath) { $msg='Image required for new banner.'; $msgType='error'; }
            else { $pdo->prepare("INSERT INTO banners (title,subtitle,image,link,badge,sort_order,is_active) VALUES (?,?,?,?,?,?,?)")->execute([$title,$sub,$imgPath,$link,$badge,$sort,$active]); $msg='Banner added!'; $msgType='success'; }
        }
        if (!$msg) { $msg='Banner updated!'; $msgType='success'; }
    }
    if ($action==='delete') { $pdo->prepare("DELETE FROM banners WHERE id=?")->execute([(int)$_POST['id']]); $msg='Banner deleted.'; $msgType='success'; }
}
$edit=(int)($_GET['edit']??0); $editBanner=null;
if ($edit) { $s=$pdo->prepare("SELECT * FROM banners WHERE id=?"); $s->execute([$edit]); $editBanner=$s->fetch(); }
$banners=$pdo->query("SELECT * FROM banners ORDER BY sort_order,id DESC")->fetchAll();
?>
<div class="page-header">
  <div><div class="page-header-title">🖼️ Banners</div></div>
  <a href="?edit=new" class="btn btn-primary">+ Add Banner</a>
</div>
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= $msgType==='success'?'✅':'❌' ?> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if (isset($_GET['edit'])): ?>
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><span class="card-title"><?= $editBanner?'✏️ Edit Banner':'➕ New Banner' ?></span><a href="?" class="btn btn-ghost btn-sm">← Back</a></div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= $editBanner['id']??0 ?>">
      <div class="form-row cols-2">
        <div class="form-group"><label class="form-label">Title <span class="req">*</span></label><input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($editBanner['title']??'') ?>"></div>
        <div class="form-group"><label class="form-label">Subtitle</label><input type="text" name="subtitle" class="form-control" value="<?= htmlspecialchars($editBanner['subtitle']??'') ?>"></div>
        <div class="form-group"><label class="form-label">Link URL (optional)</label><input type="text" name="link" class="form-control" value="<?= htmlspecialchars($editBanner['link']??'') ?>" placeholder="/menu.php"></div>
        <div class="form-group"><label class="form-label">Badge Text (optional)</label><input type="text" name="badge" class="form-control" value="<?= htmlspecialchars($editBanner['badge']??'') ?>" placeholder="New! / Hot!"></div>
        <div class="form-group"><label class="form-label">Sort Order</label><input type="number" name="sort_order" class="form-control" value="<?= $editBanner['sort_order']??0 ?>"></div>
        <div class="form-group">
          <label class="form-label">Banner Image <?= $editBanner?'(leave blank to keep current)':'<span class="req">*</span>' ?></label>
          <div class="upload-box" onclick="this.querySelector('input').click()">
            <input type="file" name="image" accept="image/*">
            <?php if ($editBanner['image']??null): ?><img src="<?= BASE_URL.'/'.$editBanner['image'] ?>" class="upload-preview" alt="" style="max-height:80px;width:auto"><?php else: ?><p style="color:var(--text-3);font-size:.82rem">🖼️ Upload banner image</p><?php endif; ?>
          </div>
        </div>
      </div>
      <label style="display:flex;align-items:center;gap:8px;margin-bottom:16px;cursor:pointer">
        <input type="checkbox" name="is_active" value="1" <?= ($editBanner['is_active']??1)?'checked':'' ?> style="width:16px;height:16px;accent-color:var(--primary)">
        <span style="font-size:.85rem">Active (visible on website)</span>
      </label>
      <div class="form-actions"><button type="submit" class="btn btn-primary">💾 Save Banner</button><a href="?" class="btn btn-ghost">Cancel</a></div>
    </form>
  </div>
</div>
<?php endif; ?>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Image</th><th>Title</th><th>Badge</th><th>Link</th><th>Sort</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if (empty($banners)): ?><tr><td colspan="7"><div class="empty-state"><p class="empty-state-text">No banners yet</p></div></td></tr>
        <?php else: foreach ($banners as $b): ?>
        <tr>
          <td><img src="<?= BASE_URL.'/'.$b['image'] ?>" style="height:40px;width:80px;object-fit:cover;border-radius:6px" alt=""></td>
          <td><div class="td-name"><?= htmlspecialchars($b['title']) ?></div><div class="td-muted"><?= htmlspecialchars($b['subtitle']??'') ?></div></td>
          <td><?= $b['badge']?'<span class="badge badge-active">'.htmlspecialchars($b['badge']).'</span>':'—' ?></td>
          <td><span style="font-size:.78rem;color:var(--text-3)"><?= htmlspecialchars($b['link']??'—') ?></span></td>
          <td><?= $b['sort_order'] ?></td>
          <td><span class="badge <?= $b['is_active']?'badge-active':'badge-inactive' ?>"><?= $b['is_active']?'Active':'Hidden' ?></span></td>
          <td><div style="display:flex;gap:5px"><a href="?edit=<?= $b['id'] ?>" class="btn btn-ghost btn-icon btn-sm">✏️</a><button class="btn btn-danger btn-icon btn-sm" onclick="showConfirm('Delete Banner','Delete this banner?',()=>delBanner(<?= $b['id'] ?>))">🗑</button></div></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script>function delBanner(id){const f=document.createElement('form');f.method='POST';f.innerHTML=`<input name="action" value="delete"><input name="id" value="${id}">`;document.body.appendChild(f);f.submit();}</script>
<?php require_once '../includes/footer.php'; ?>
