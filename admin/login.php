<?php
require_once dirname(__DIR__) . '/../includes/config.php';
if (isAdminLoggedIn()) redirect(BASE_URL . '/admin/dashboard.php');

$error = ''; $csrf = generateCSRF();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) { $error = 'Security error.'; }
    else {
        $email = sanitize($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        if ($email && $pass) {
            $pdo   = getDB();
            $stmt  = $pdo->prepare("SELECT * FROM admins WHERE email=? AND is_active=1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            if ($admin && password_verify($pass, $admin['password'])) {
                $_SESSION['admin_id']   = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_role'] = $admin['role'];
                $pdo->prepare("UPDATE admins SET last_login=NOW() WHERE id=?")->execute([$admin['id']]);
                redirect(BASE_URL . '/admin/dashboard.php');
            } else { $error = 'Invalid email or password.'; }
        } else { $error = 'Fill in all fields.'; }
    }
}
$siteName = getSetting('site_name','Haveli');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Admin Login — <?= htmlspecialchars($siteName) ?></title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin.css">
  <style>
    body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:radial-gradient(ellipse 80% 60% at 50% 40%,rgba(255,107,0,.06),transparent 60%),#09090B;}
    .login-card{width:100%;max-width:420px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:24px;padding:44px 40px;backdrop-filter:blur(30px);box-shadow:0 30px 80px rgba(0,0,0,.6);animation:pageIn .5s ease both;}
    .login-logo{text-align:center;margin-bottom:32px;}
    .login-logo-icon{width:56px;height:56px;background:linear-gradient(135deg,#FF6B00,#FFD700);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 12px;box-shadow:0 8px 24px rgba(255,107,0,.4);}
    .login-logo-name{font-family:'Cinzel',serif;font-size:1.5rem;font-weight:700;background:linear-gradient(135deg,#fff 40%,#FFD700);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
    .login-sub{font-size:.78rem;color:rgba(244,240,232,.4);text-transform:uppercase;letter-spacing:.1em;margin-top:4px;}
    .pw-wrap{position:relative;}
    .pw-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:rgba(244,240,232,.4);cursor:pointer;font-size:.9rem;}
  </style>
</head>
<body>
<div class="login-card">
  <div class="login-logo">
    <div class="login-logo-icon">🍽️</div>
    <div class="login-logo-name"><?= htmlspecialchars($siteName) ?></div>
    <div class="login-sub">Admin Panel</div>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    <div class="form-group" style="margin-bottom:16px">
      <label class="form-label">Admin Email</label>
      <input type="email" name="email" class="form-control" required placeholder="admin@haveli.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email">
    </div>
    <div class="form-group" style="margin-bottom:24px">
      <label class="form-label">Password</label>
      <div class="pw-wrap">
        <input type="password" id="adminPwd" name="password" class="form-control" required placeholder="Password" autocomplete="current-password">
        <button type="button" class="pw-toggle" onclick="const i=document.getElementById('adminPwd');i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'👁':'🙈'">👁</button>
      </div>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">
      🔐 Sign In to Admin
    </button>
  </form>

  <div style="margin-top:20px;padding:12px;background:rgba(255,215,0,.06);border:1px solid rgba(255,215,0,.15);border-radius:10px;font-size:.75rem;color:rgba(244,240,232,.45);text-align:center">
    Default: admin@haveli.com / <strong style="color:rgba(255,215,0,.6)">password</strong>
    <br><span style="font-size:.68rem;opacity:.6">Change this immediately after first login</span>
  </div>

  <p style="text-align:center;margin-top:16px;font-size:.78rem;color:rgba(244,240,232,.35)">
    <a href="<?= BASE_URL ?>/index.php" style="color:rgba(255,107,0,.7);text-decoration:none">← Back to Website</a>
  </p>
</div>
</body>
</html>
