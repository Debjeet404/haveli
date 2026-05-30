<?php
require_once 'includes/config.php';
$pageTitle = 'Offers & Coupons';
$pdo = getDB();

$coupons = $pdo->query("
    SELECT * FROM coupons
    WHERE is_active = 1
    AND (expires_at IS NULL OR expires_at >= CURDATE())
    AND (uses_limit IS NULL OR uses_count < uses_limit)
    ORDER BY value DESC
")->fetchAll();

$currency = getSetting('site_currency','₨');
require_once 'includes/header.php';
?>

<style>
.offers-hero { padding: calc(var(--nav-height)+60px) clamp(16px,4vw,48px) 60px; text-align:center; background: linear-gradient(135deg,rgba(255,215,0,.05),rgba(255,107,0,.04)); border-bottom:1px solid var(--border); }
.coupon-card { background: var(--glass); border: 1px solid var(--glass-border); border-radius: var(--radius-xl); overflow: hidden; backdrop-filter: blur(20px); transition: var(--transition); position:relative; }
.coupon-card:hover { transform: translateY(-4px); box-shadow: 0 24px 60px rgba(0,0,0,.4), 0 0 30px rgba(255,215,0,.06); border-color: rgba(255,215,0,.25); }
.coupon-top { padding: 32px 28px; background: linear-gradient(135deg, rgba(255,107,0,.08), rgba(255,215,0,.05)); border-bottom: 2px dashed var(--border); position: relative; }
.coupon-bottom { padding: 20px 28px; }
.coupon-value { font-family: var(--font-display); font-size: 3rem; font-weight: 900; background: linear-gradient(135deg,var(--primary),var(--gold)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; line-height:1; }
.coupon-type-label { font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:var(--text-muted); }
.coupon-code-box { background: rgba(255,215,0,.08); border: 2px dashed rgba(255,215,0,.3); border-radius: var(--radius-md); padding: 12px 20px; font-family: var(--font-display); font-size: 1.4rem; font-weight: 900; color: var(--gold); letter-spacing: .15em; text-align:center; cursor:none; transition: var(--transition-fast); }
.coupon-code-box:hover { background: rgba(255,215,0,.15); transform: scale(1.02); }
.coupon-notch-left, .coupon-notch-right { position:absolute; top:50%; width:24px; height:24px; background:var(--bg-dark); border-radius:50%; transform:translateY(-50%); border:1px solid var(--border); }
.coupon-notch-left { left:-12px; } .coupon-notch-right { right:-12px; }
.coupon-badge { position:absolute; top:12px; right:16px; background:linear-gradient(135deg,var(--primary),var(--gold)); color:#fff; font-size:.68rem; font-weight:700; padding:4px 10px; border-radius:100px; letter-spacing:.06em; }
</style>

<!-- Hero -->
<div class="offers-hero">
  <p class="section-label" style="justify-content:center">Save More</p>
  <h1 class="section-title" style="font-size:clamp(2rem,5vw,3rem)">
    Exclusive <span class="highlight">Offers</span>
  </h1>
  <p style="color:var(--text-secondary);font-family:var(--font-elegant);font-size:1.1rem;max-width:480px;margin:0 auto">
    Click any coupon to copy the code and apply it at checkout.
  </p>
</div>

<section class="section">
  <div class="section-inner">

    <?php if (empty($coupons)): ?>
    <div style="text-align:center;padding:80px 20px;background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius-xl)">
      <div style="font-size:4rem;margin-bottom:16px">🏷️</div>
      <h2 style="font-family:var(--font-display);margin-bottom:8px">No Active Offers</h2>
      <p style="color:var(--text-secondary)">Check back soon for exclusive deals!</p>
    </div>
    <?php else: ?>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px">
      <?php foreach ($coupons as $i => $coupon): ?>
      <div class="coupon-card reveal reveal-delay-<?= ($i%3)+1 ?>" onclick="copyCoupon('<?= htmlspecialchars($coupon['code']) ?>')">

        <?php if ($coupon['min_order'] > 0): ?>
        <div class="coupon-badge">Min <?= $currency . number_format($coupon['min_order']) ?></div>
        <?php endif; ?>

        <div class="coupon-top">
          <div class="coupon-notch-left"></div>
          <div class="coupon-notch-right"></div>

          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
            <div>
              <div class="coupon-type-label"><?= $coupon['type'] === 'percentage' ? 'Discount' : 'Flat Off' ?></div>
              <div class="coupon-value">
                <?= $coupon['type'] === 'percentage'
                    ? $coupon['value'] . '%'
                    : $currency . number_format($coupon['value']) ?>
              </div>
            </div>
            <div style="font-size:3rem">
              <?= $coupon['type'] === 'percentage' ? '💫' : '💰' ?>
            </div>
          </div>

          <?php if ($coupon['max_discount']): ?>
          <p style="font-size:.8rem;color:var(--text-muted)">
            Max discount: <?= $currency . number_format($coupon['max_discount']) ?>
          </p>
          <?php endif; ?>
        </div>

        <div class="coupon-bottom">
          <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:10px">
            <?php
              $details = [];
              if ($coupon['min_order'] > 0) $details[] = "On orders above " . $currency . number_format($coupon['min_order']);
              if ($coupon['expires_at']) $details[] = "Expires " . date('M j, Y', strtotime($coupon['expires_at']));
              if ($coupon['uses_limit']) {
                $remaining = $coupon['uses_limit'] - $coupon['uses_count'];
                $details[] = "$remaining uses left";
              }
              echo implode(' · ', $details) ?: 'No minimum order required';
            ?>
          </p>

          <div class="coupon-code-box">
            <?= htmlspecialchars($coupon['code']) ?>
          </div>

          <button class="btn btn-primary btn-full btn-sm" style="margin-top:14px"
                  onclick="event.stopPropagation();copyCoupon('<?= htmlspecialchars($coupon['code']) ?>')">
            📋 Copy Code
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Info Box -->
    <div style="margin-top:48px;background:rgba(255,107,0,.05);border:1px solid rgba(255,107,0,.15);border-radius:var(--radius-xl);padding:28px 32px">
      <h3 style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;margin-bottom:12px;color:var(--gold)">
        🛍️ How to Use Coupons
      </h3>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
        <?php $steps = [['1','Browse Menu','Pick your favourite dishes and add them to cart.'],['2','Copy Coupon','Click any coupon above to copy the code.'],['3','Apply at Checkout','Paste the code in the coupon field at checkout.'],['4','Save!','Your discount is applied automatically to the total.']]; foreach($steps as $s): ?>
        <div style="display:flex;gap:12px;align-items:flex-start">
          <div style="width:28px;height:28px;background:linear-gradient(135deg,var(--primary),var(--gold));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:800;color:#fff;flex-shrink:0"><?= $s[0] ?></div>
          <div>
            <p style="font-weight:600;font-size:.9rem;margin-bottom:2px"><?= $s[1] ?></p>
            <p style="font-size:.8rem;color:var(--text-muted)"><?= $s[2] ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
