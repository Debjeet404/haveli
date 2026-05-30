<?php
require_once 'includes/config.php';
$pageTitle = '404 — Page Not Found';
http_response_code(404);
require_once 'includes/header.php';
?>
<style>
.error-page { min-height:70vh;display:flex;align-items:center;justify-content:center;padding:clamp(80px,12vw,120px) clamp(16px,4vw,32px);text-align:center; }
.error-code { font-family:var(--font-display);font-size:clamp(6rem,15vw,12rem);font-weight:900;line-height:1;background:linear-gradient(135deg,var(--primary),var(--gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;opacity:.3;margin-bottom:-20px;display:block; }
.error-emoji { font-size:clamp(3rem,7vw,5rem);display:block;margin-bottom:16px;animation:float2 4s ease-in-out infinite; }
</style>
<div class="error-page">
  <div>
    <span class="error-code">404</span>
    <span class="error-emoji">🍽️</span>
    <h1 style="font-family:var(--font-display);font-size:clamp(1.6rem,4vw,2.5rem);font-weight:900;margin-bottom:12px">Oops! This dish isn't on the menu.</h1>
    <p style="color:var(--text-secondary);font-family:var(--font-elegant);font-size:1.1rem;max-width:440px;margin:0 auto 36px;line-height:1.8">The page you're looking for has gone missing — much like the last piece of Nihari at dinner. Let's get you back home.</p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
      <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary btn-lg">🏠 Back to Home</a>
      <a href="<?= BASE_URL ?>/menu.php" class="btn btn-outline btn-lg">🍴 View Menu</a>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
