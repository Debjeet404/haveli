<?php
require_once 'includes/config.php';
if (isLoggedIn()) redirect(BASE_URL . '/profile.php');
$pageTitle = 'Create Account';
$errors = [];
$success = false;
$csrf = generateCSRF();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security error. Please try again.';
    } else {
        $name     = sanitize($_POST['name'] ?? '');
        $email    = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $phone    = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!$name)       $errors[] = 'Name is required.';
        if (!$email)      $errors[] = 'Valid email is required.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $pdo = getDB();
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $errors[] = 'This email is already registered. <a href="login.php" style="color:var(--primary)">Login instead</a>';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name,email,phone,password) VALUES (?,?,?,?)");
                $stmt->execute([$name, $email, $phone, $hashed]);
                $userId = $pdo->lastInsertId();

                $_SESSION['user_id']   = $userId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email']= $email;

                // Welcome notification
                $pdo->prepare("INSERT INTO notifications (user_id,title,message,type) VALUES (?,?,?,?)")
                    ->execute([$userId, 'Welcome to Haveli! 🎉', 'Your account is ready. Use code WELCOME20 for 20% off your first order.', 'promo']);

                redirect(BASE_URL . '/profile.php?welcome=1');
            }
        }
    }
}
require_once 'includes/header.php';
?>

<style>
.auth-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: clamp(80px,12vw,120px) clamp(16px,4vw,32px); }
.auth-card { width: 100%; max-width: 480px; background: var(--glass); border: 1px solid var(--glass-border); border-radius: var(--radius-xl); padding: clamp(32px,6vw,52px); backdrop-filter: blur(30px); box-shadow: var(--shadow-hover); animation: fadeInUp .6s ease both; }
.auth-logo { text-align: center; margin-bottom: 28px; }
.auth-logo-icon { font-size: 2.5rem; display: block; margin-bottom: 8px; }
.auth-logo-name { font-family: var(--font-display); font-size: 1.6rem; font-weight: 900; background: linear-gradient(135deg,var(--primary),var(--gold)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.auth-title { font-family: var(--font-display); font-size: 1.4rem; font-weight: 700; text-align: center; margin-bottom: 6px; }
.auth-sub { font-size: .88rem; color: var(--text-muted); text-align: center; margin-bottom: 28px; }
.auth-error { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.2); border-radius: var(--radius-md); padding: 12px 16px; font-size: .88rem; color: #ef4444; margin-bottom: 20px; }
.password-wrap { position: relative; }
.password-toggle { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); font-size: .9rem; }
.password-strength { height: 4px; border-radius: 4px; background: var(--glass); margin-top: 6px; overflow: hidden; }
.password-strength-bar { height: 100%; border-radius: 4px; width: 0; transition: width .3s, background .3s; }
</style>

<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <span class="auth-logo-icon">🍽️</span>
      <span class="auth-logo-name"><?= htmlspecialchars(getSetting('site_name','Haveli')) ?></span>
    </div>

    <h1 class="auth-title">Create Account</h1>
    <p class="auth-sub">Join Haveli for exclusive offers & easy ordering</p>

    <?php if (!empty($errors)): ?>
    <div class="auth-error">
      <?php foreach ($errors as $e): ?>
      <p>⚠️ <?= $e ?></p>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label" for="reg-name">Full Name *</label>
          <input type="text" id="reg-name" name="name" class="form-input" required
                 value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="Your full name" autocomplete="name">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label" for="reg-email">Email Address *</label>
          <input type="email" id="reg-email" name="email" class="form-input" required
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com" autocomplete="email">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label" for="reg-phone">Phone Number</label>
          <input type="tel" id="reg-phone" name="phone" class="form-input"
                 value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+92 300 0000000" autocomplete="tel">
        </div>
        <div class="form-group">
          <label class="form-label" for="reg-password">Password *</label>
          <div class="password-wrap">
            <input type="password" id="reg-password" name="password" class="form-input" required
                   placeholder="Min. 6 characters" autocomplete="new-password" oninput="checkStrength(this.value)">
            <button type="button" class="password-toggle" onclick="togglePwd('reg-password',this)">👁</button>
          </div>
          <div class="password-strength"><div class="password-strength-bar" id="strengthBar"></div></div>
          <div class="form-hint" id="strengthText">Enter a strong password</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="reg-confirm">Confirm Password *</label>
          <div class="password-wrap">
            <input type="password" id="reg-confirm" name="confirm_password" class="form-input" required
                   placeholder="Repeat password" autocomplete="new-password">
            <button type="button" class="password-toggle" onclick="togglePwd('reg-confirm',this)">👁</button>
          </div>
        </div>
      </div>

      <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:20px">
        <input type="checkbox" id="terms" required style="width:16px;height:16px;margin-top:2px;accent-color:var(--primary)">
        <label for="terms" style="font-size:.82rem;color:var(--text-secondary);line-height:1.5;cursor:none">
          I agree to the <a href="#" style="color:var(--primary);text-decoration:none">Terms of Service</a>
          and <a href="#" style="color:var(--primary);text-decoration:none">Privacy Policy</a>
        </label>
      </div>

      <button type="submit" class="btn btn-primary btn-full btn-lg">
        Create Account →
      </button>
    </form>

    <p style="text-align:center;font-size:.88rem;color:var(--text-secondary);margin-top:20px">
      Already have an account?
      <a href="<?= BASE_URL ?>/login.php" style="color:var(--primary);font-weight:600;text-decoration:none">Sign In</a>
    </p>
  </div>
</div>

<script>
function togglePwd(id, btn) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.textContent = inp.type === 'password' ? '👁' : '🙈';
}
function checkStrength(val) {
  const bar = document.getElementById('strengthBar');
  const txt = document.getElementById('strengthText');
  let score = 0;
  if (val.length >= 6) score++;
  if (val.length >= 10) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const levels = [
    {w:'0%',bg:'#ef4444',t:'Too weak'},
    {w:'25%',bg:'#ef4444',t:'Weak'},
    {w:'50%',bg:'#f97316',t:'Fair'},
    {w:'75%',bg:'#eab308',t:'Good'},
    {w:'100%',bg:'#22c55e',t:'Strong 💪'}
  ];
  const l = levels[Math.min(score, 4)];
  bar.style.width = l.w; bar.style.background = l.bg;
  txt.textContent = l.t; txt.style.color = l.bg;
}
</script>

<?php require_once 'includes/footer.php'; ?>
