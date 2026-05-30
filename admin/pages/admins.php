<?php
$adminTitle = 'Admin Accounts';
require_once '../includes/header.php';

// Only superadmin
if ($_SESSION['admin_role'] !== 'superadmin') {
    echo '<div class="alert alert-error">⛔ Access denied. Only Super Admins can manage admin accounts.</div>';
    require_once '../includes/footer.php'; exit;
}

$pdo = getDB();
$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id    = (int)($_POST['id'] ?? 0);
        $name  = sanitize($_POST['name'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $role  = in_array($_POST['role']??'', ['superadmin','admin','manager']) ? $_POST['role'] : 'admin';
        $pass  = $_POST['password'] ?? '';
        if ($name && $email) {
            if ($id) {
                $sql = "UPDATE admins SET name=?,email=?,role=?"; $p=[$name,$email,$role];
                if ($pass) { $sql.=",password=?"; $p[]=password_hash($pass,PASSWORD_DEFAULT); }
                if (!empty($_FILES['avatar']['name'])) { $up=uploadFile($_FILES['avatar'],'avatars'); if(isset($up['success'])){$sql.=",avatar=?";$p[]=$up['path'];} }
                $sql.=" WHERE id=?"; $p[]=$id;
                $pdo->prepare($sql)->execute($p);
            } else {
                if (!$pass) { $msg='Password required for new admin.'; $msgType='error'; }
                else {
                    $pdo->prepare("INSERT INTO admins (name,email,password,role) VALUES (?,?,?,?)")->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),$role]);
                    $msg='Admin created!'; $msgType='success';
                }
            }
            if (!$msg) { $msg='Admin updated!'; $msgType='success'; }
        } else { $msg='Name and valid email required.'; $msgType='error'; }
    }
    if ($action==='delete') {
        $id=(int)$_POST['id'];
        if ($id==$_SESSION['admin_id']) { $msg='Cannot delete your own account.'; $msgType='error'; }
        else { $pdo->prepare("DELETE FROM admins WHERE id=?")->execute([$id]); $msg='Admin deleted.'; $msgType='success'; }
    }
}

$edit=(int)($_GET['edit']??0); $editAdmin=null;
if ($edit) { $s=$pdo->prepare("SELECT * FROM admins WHERE id=?"); $s->execute([$edit]); $editAdmin=$s->fetch(); }
$admins=$pdo->query("SELECT * FROM admins ORDER BY role,name")->fetchAll();
?>

<div class="page-header">
  <div><div class="page-header-title">🔐 Admin Accounts</div></div>
  <a href="?edit=new" class="btn btn-primary">+ Add Admin</a>
</div>

<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= $msgType==='success'?'✅':'❌' ?> <?= htmlspecialchars($msg) ?></div><?php endif; ?>

<?php if (isset($_GET['edit'])): ?>
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><span class="card-title"><?= $editAdmin?'✏️ Edit Admin':'➕ New Admin' ?></span><a href="?" class="btn btn-ghost btn-sm">← Back</a></div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= $editAdmin['id']??0 ?>">
      <div class="form-row cols-2">
        <div class="form-group"><label class="form-label">Full Name <span class="req">*</span></label><input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editAdmin['name']??'') ?>"></div>
        <div class="form-group"><label class="form-label">Email <span class="req">*</span></label><input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($editAdmin['email']??'') ?>"></div>
        <div class="form-group"><label class="form-label">Role</label>
          <select name="role" class="form-control">
            <option value="admin" <?= ($editAdmin['role']??'admin')==='admin'?'selected':'' ?>>Admin</option>
            <option value="manager" <?= ($editAdmin['role']??'')==='manager'?'selected':'' ?>>Manager</option>
            <option value="superadmin" <?= ($editAdmin['role']??'')==='superadmin'?'selected':'' ?>>Super Admin</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Password <?= $editAdmin?'(leave blank to keep current)':'<span class="req">*</span>' ?></label><input type="password" name="password" class="form-control" placeholder="Min 6 characters" <?= $editAdmin?'':'required' ?>></div>
        <div class="form-group"><label class="form-label">Avatar (optional)</label>
          <div class="upload-box" onclick="this.querySelector('input').click()">
            <input type="file" name="avatar" accept="image/*">
            <?php if ($editAdmin['avatar']??null): ?><img src="<?= BASE_URL.'/'.$editAdmin['avatar'] ?>" class="upload-preview" style="width:50px;height:50px;border-radius:50%" alt=""><?php else: ?><p style="color:var(--text-3);font-size:.82rem">👤 Upload Photo</p><?php endif; ?>
          </div>
        </div>
      </div>
      <div class="form-actions"><button type="submit" class="btn btn-primary">💾 Save Admin</button><a href="?" class="btn btn-ghost">Cancel</a></div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Avatar</th><th>Name</th><th>Email</th><th>Role</th><th>Last Login</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($admins as $a): ?>
        <tr>
          <td><div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--gold));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;overflow:hidden"><?php if($a['avatar']):?><img src="<?= BASE_URL.'/'.$a['avatar'] ?>" style="width:100%;height:100%;object-fit:cover" alt=""><?php else:?>><?= strtoupper(substr($a['name'],0,1)) ?><?php endif;?></div></td>
          <td><div class="td-name"><?= htmlspecialchars($a['name']) ?><?= $a['id']==$_SESSION['admin_id']?' <span style="font-size:.65rem;color:var(--gold)">(You)</span>':'' ?></div></td>
          <td><span style="font-size:.82rem"><?= htmlspecialchars($a['email']) ?></span></td>
          <td><span class="badge <?= $a['role']==='superadmin'?'badge-pending':($a['role']==='admin'?'badge-accepted':'badge-active') ?>"><?= ucfirst($a['role']) ?></span></td>
          <td><span style="font-size:.75rem;color:var(--text-3)"><?= $a['last_login']?date('M j, Y g:i A',strtotime($a['last_login'])):'Never' ?></span></td>
          <td><div style="display:flex;gap:5px">
            <a href="?edit=<?= $a['id'] ?>" class="btn btn-ghost btn-icon btn-sm">✏️</a>
            <?php if ($a['id']!=$_SESSION['admin_id']): ?>
            <button class="btn btn-danger btn-icon btn-sm" onclick="showConfirm('Delete Admin','Delete admin &quot;<?= addslashes($a['name']) ?>&quot;?',()=>delAdmin(<?= $a['id'] ?>))">🗑</button>
            <?php endif; ?>
          </div></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>function delAdmin(id){const f=document.createElement('form');f.method='POST';f.innerHTML=`<input name="action" value="delete"><input name="id" value="${id}">`;document.body.appendChild(f);f.submit();}</script>

<?php require_once '../includes/footer.php'; ?>
