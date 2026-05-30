<?php
require_once 'includes/config.php';
if (isLoggedIn()) redirect(BASE_URL . '/profile.php');
$pageTitle = 'Login';
$error = '';
$csrf = generateCSRF();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Security error. Please try again.';
    } else {
        $email    = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($email && $password) {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email']= $user['email'];
                $pdo->prepare("UPDATE users SET updated_at=NOW() WHERE id=?")->execute([$user['id']]);
                redirect(BASE_URL . '/profile.php');
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Please fill in all fields.';
        }
    }
}
require_once 'includes/header.php';
?>

<style>
.auth-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: clamp(80px,12vw,120px) clamp(16px,4vw,32px); }
.auth-card { width: 100%; max-width: 460px; background: var(--glass); border: 1px solid var(--glass-border); border-radius: var(--radius-xl); padding: clamp(32px,6vw,52px); backdrop-filter: blur(30px); box-shadow: var(--shadow-hover); animation: fadeInUp .6s ease both; }
.auth-logo { text-align: center; margin-bottom: 32px; }
.auth-logo-icon { font-size: 2.5rem; display: block; margin-bottom: 8px; }
.auth-logo-name { font-family: var(--font-display); font-size: 1.6rem; font-weight: 900; background: linear-gradient(135deg,var(--primary),var(--gold)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.auth-title { font-family: var(--font-display); font-size: 1.4rem; font-weight: 700; text-align: center; margin-bottom: 6px; }
.auth-sub { font-size: .88rem; color: var(--text-muted); text-align: center; margin-bottom: 28px; }
.auth-divider { display: flex; align-items: center; gap: 12px; margin: 20px 0; }
.auth-divider::before, .auth-divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }
.auth-divider span { font-size: .78rem; color: var(--text-muted); }
.auth-error { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.2); border-radius: var(--radius-md); padding: 12px 16px; font-size: .88rem; color: #ef4444; margin-bottom: 20px; }
.password-wrap { position: relative; }
.password-toggle { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); font-size: .9rem; transition: color .2s; }
.password-toggle:hover { color: var(--primary); }
</style>

<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <span class="auth-logo-icon">🍽️</span>
      <span class="auth-logo-name"><?= htmlspecialchars(getSetting('site_name','Haveli')) ?></span>
    </div>

    <h1 class="auth-title">Welcome Back</h1>
    <p class="auth-sub">Sign in to your account to continue</p>

    <?php if ($error): ?>
    <div class="auth-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" id="email" name="email" class="form-input" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="you@example.com" autocomplete="email">
      </div>

      <div class="form-group">
        <label class="form-label" for="password">
          Password
          <a href="#" style="float:right;color:var(--primary);font-size:.78rem;text-decoration:none">Forgot password?</a>
        </label>
        <div class="password-wrap">
          <input type="password" id="password" name="password" class="form-input" required
                 placeholder="Your password" autocomplete="current-password">
          <button type="button" class="password-toggle" onclick="togglePwd('password',this)">👁</button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:8px">
        Sign In →
      </button>
    </form>

    <div class="auth-divider"><span>or</span></div>

    <p style="text-align:center;font-size:.88rem;color:var(--text-secondary)">
      Don't have an account?
      <a href="<?= BASE_URL ?>/register.php" style="color:var(--primary);font-weight:600;text-decoration:none">Create Account</a>
    </p>
  </div>
</div>

<script>
function togglePwd(id, btn) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.textContent = inp.type === 'password' ? '👁' : '🙈';
}
</script>

<?php require_once 'includes/footer.php'; ?>
