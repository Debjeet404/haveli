<?php
require_once 'includes/config.php';
$pageTitle = 'About Us';
$siteName  = getSetting('site_name','Haveli');
$aboutTitle= getSetting('about_title','A Legacy of Royal Cuisine');
$aboutText = getSetting('about_text','Founded in 1985...');
$address   = getSetting('site_address','');
$phone     = getSetting('site_phone','');
$hours     = getSetting('restaurant_hours','Mon-Sun: 12PM – 12AM');
require_once 'includes/header.php';
?>

<style>
.about-hero { padding: calc(var(--nav-height)+60px) clamp(16px,4vw,48px) 80px; position:relative; overflow:hidden; }
.about-hero-bg { position:absolute;inset:0;background:radial-gradient(ellipse 70% 60% at 30% 50%,rgba(255,107,0,.07),transparent 60%),radial-gradient(ellipse 50% 70% at 70% 30%,rgba(255,215,0,.04),transparent 50%);pointer-events:none; }
.about-inner { max-width:1200px;margin:0 auto; }
.about-grid { display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center; }
.about-visual { position:relative; }
.about-img-main { width:100%;aspect-ratio:4/5;border-radius:var(--radius-xl);background:linear-gradient(135deg,rgba(255,107,0,.08),rgba(255,215,0,.05));display:flex;align-items:center;justify-content:center;font-size:8rem;overflow:hidden;border:1px solid var(--glass-border); }
.about-stat-card { position:absolute;background:rgba(10,10,11,.9);backdrop-filter:blur(20px);border:1px solid var(--glass-border);border-radius:var(--radius-md);padding:14px 20px;box-shadow:var(--shadow-card); }
.about-stat-card.card-a { bottom:10%;left:-8%;animation:float1 4s ease-in-out infinite; }
.about-stat-card.card-b { top:12%;right:-6%;animation:float2 5s ease-in-out infinite; }
.timeline { position:relative;padding-left:28px; }
.timeline::before { content:'';position:absolute;left:6px;top:0;bottom:0;width:2px;background:linear-gradient(180deg,var(--primary),var(--gold),transparent); }
.timeline-item { position:relative;margin-bottom:32px; }
.timeline-item::before { content:'';position:absolute;left:-25px;top:5px;width:12px;height:12px;border-radius:50%;background:var(--primary);box-shadow:0 0 12px rgba(255,107,0,.4); }
.timeline-year { font-family:var(--font-display);font-size:.85rem;font-weight:700;color:var(--primary);margin-bottom:4px;letter-spacing:.06em; }
.timeline-text { font-size:.88rem;color:var(--text-secondary);line-height:1.7; }
.value-card { background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius-lg);padding:28px;backdrop-filter:blur(20px);transition:var(--transition);text-align:center; }
.value-card:hover { transform:translateY(-4px);border-color:rgba(255,107,0,.2); }
@media(max-width:900px){.about-grid{grid-template-columns:1fr;gap:40px;}.about-visual{display:none;}}
</style>

<!-- Hero -->
<div class="about-hero">
  <div class="about-hero-bg" aria-hidden="true"></div>
  <div class="about-inner">
    <div class="about-grid">
      <!-- Text -->
      <div>
        <p class="section-label reveal">Our Heritage</p>
        <h1 class="section-title reveal" style="font-size:clamp(2.2rem,5vw,3.8rem)">
          <?= htmlspecialchars($aboutTitle) ?>
        </h1>
        <p style="font-family:var(--font-elegant);font-size:1.15rem;color:var(--text-secondary);line-height:1.85;margin-bottom:28px" class="reveal">
          <?= htmlspecialchars($aboutText) ?>
        </p>
        <p style="font-family:var(--font-elegant);font-size:1.05rem;color:var(--text-secondary);line-height:1.8;margin-bottom:36px" class="reveal">
          Every dish at <?= htmlspecialchars($siteName) ?> is a masterpiece — crafted with hand-selected spices, 
          slow-cooked over charcoal flames, and served with the warmth and grandeur of Mughal hospitality. 
          We don't just serve food; we serve memories.
        </p>
        <div style="display:flex;gap:16px;flex-wrap:wrap" class="reveal">
          <a href="<?= BASE_URL ?>/menu.php" class="btn btn-primary btn-lg">🍽️ Explore Menu</a>
          <a href="<?= BASE_URL ?>/contact.php" class="btn btn-outline btn-lg">📞 Contact Us</a>
        </div>
      </div>

      <!-- Visual -->
      <div class="about-visual">
        <div class="about-img-main">🏰</div>
        <div class="about-stat-card card-a">
          <div style="font-size:.72rem;color:var(--text-muted);margin-bottom:2px">YEARS OF LEGACY</div>
          <div style="font-family:var(--font-display);font-size:2rem;font-weight:900;color:var(--gold)">38+</div>
        </div>
        <div class="about-stat-card card-b">
          <div style="font-size:.72rem;color:var(--text-muted);margin-bottom:2px">DISHES SERVED</div>
          <div style="font-family:var(--font-display);font-size:2rem;font-weight:900;color:var(--primary)">2M+</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Stats Bar -->
<section style="background:linear-gradient(90deg,rgba(255,107,0,.06),rgba(255,215,0,.04),rgba(255,107,0,.06));border-top:1px solid var(--border);border-bottom:1px solid var(--border);padding:40px clamp(16px,4vw,48px)">
  <div style="max-width:1000px;margin:0 auto;display:grid;grid-template-columns:repeat(4,1fr);gap:24px;text-align:center">
    <?php $stats = [['38+','Years of Legacy'],['12,000+','Happy Customers'],['50+','Signature Dishes'],['4.9/5','Average Rating']]; foreach($stats as $s): ?>
    <div class="reveal">
      <div style="font-family:var(--font-display);font-size:2rem;font-weight:900;color:var(--primary);margin-bottom:4px"><?= $s[0] ?></div>
      <div style="font-size:.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em"><?= $s[1] ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Our Story Timeline -->
<section class="section">
  <div class="section-inner">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:start">
      <div class="reveal">
        <p class="section-label">Our Journey</p>
        <h2 class="section-title">The <?= htmlspecialchars($siteName) ?><br><span class="highlight">Story</span></h2>
        <p style="color:var(--text-secondary);line-height:1.8;margin-top:8px;font-family:var(--font-elegant);font-size:1.05rem">
          From a humble tandoor in the heart of Lahore to a culinary institution — 
          our journey has been shaped by passion, perseverance, and a relentless 
          pursuit of culinary perfection.
        </p>
      </div>
      <div class="timeline reveal">
        <?php
        $timeline = [
          ['1985','The Beginning','Chef Ustad Ghulam Nabi opens a small dhaba on Mcleod Road with three signature dishes.'],
          ['1992','First Restaurant','The first proper Haveli restaurant opens in Gulberg, bringing Mughal cuisine to the masses.'],
          ['2001','Award Recognition','Haveli wins Lahore\'s Best Mughlai Restaurant award for the first time. A tradition begins.'],
          ['2010','Expansion','Three new branches open across Lahore, DHA, and Johar Town.'],
          ['2019','Digital Era','Haveli launches online ordering, bringing royal flavors to every doorstep.'],
          ['2024','Legacy Continues','5 locations, 50+ dishes, 12,000+ loyal customers. The journey goes on.'],
        ];
        foreach ($timeline as $t): ?>
        <div class="timeline-item">
          <div class="timeline-year"><?= $t[0] ?></div>
          <p style="font-weight:600;font-size:.92rem;margin-bottom:3px"><?= $t[1] ?></p>
          <p class="timeline-text"><?= $t[2] ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- Our Values -->
<section class="section" style="background:linear-gradient(135deg,rgba(255,107,0,.03),rgba(255,215,0,.02))">
  <div class="section-inner">
    <div style="text-align:center;margin-bottom:48px" class="reveal">
      <p class="section-label" style="justify-content:center">What We Stand For</p>
      <h2 class="section-title">Our <span class="highlight">Values</span></h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px">
      <?php
      $values = [
        ['🏛️','Heritage First','Every recipe is a time capsule from the Mughal era.'],
        ['🧄','No Shortcuts','Real spice blends ground fresh daily. No shortcuts ever.'],
        ['❤️','With Love','Every dish prepared with the care of a family meal.'],
        ['🌱','Sustainability','Local sourcing, seasonal menus, zero waste kitchen.'],
        ['👑','Premium Quality','Only the finest ingredients make it to your plate.'],
        ['🤝','Community','Supporting local farmers and artisan spice producers.'],
      ];
      foreach ($values as $i => $v): ?>
      <div class="value-card reveal reveal-delay-<?= ($i%3)+1 ?>">
        <div style="font-size:2.2rem;margin-bottom:14px"><?= $v[0] ?></div>
        <h3 style="font-family:var(--font-display);font-size:.95rem;font-weight:700;color:var(--gold);margin-bottom:8px"><?= $v[1] ?></h3>
        <p style="font-size:.84rem;color:var(--text-secondary);line-height:1.7"><?= $v[2] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Visit Us -->
<?php if ($address || $phone || $hours): ?>
<section class="section">
  <div class="section-inner">
    <div style="background:linear-gradient(135deg,rgba(255,107,0,.08),rgba(255,215,0,.05));border:1px solid rgba(255,107,0,.15);border-radius:var(--radius-xl);padding:clamp(32px,5vw,60px);text-align:center" class="reveal">
      <h2 style="font-family:var(--font-display);font-size:1.8rem;font-weight:900;margin-bottom:8px">
        Visit <span style="background:linear-gradient(135deg,var(--primary),var(--gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text"><?= htmlspecialchars($siteName) ?></span>
      </h2>
      <p style="color:var(--text-secondary);margin-bottom:32px;font-family:var(--font-elegant);font-size:1.05rem">
        Experience the grandeur in person — you're always welcome.
      </p>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;max-width:700px;margin:0 auto">
        <?php if ($address): ?><div style="background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius-md);padding:20px"><div style="font-size:1.5rem;margin-bottom:8px">📍</div><p style="font-size:.88rem;color:var(--text-secondary)"><?= htmlspecialchars($address) ?></p></div><?php endif; ?>
        <?php if ($phone): ?><div style="background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius-md);padding:20px"><div style="font-size:1.5rem;margin-bottom:8px">📞</div><a href="tel:<?= preg_replace('/\s+/','',$phone) ?>" style="font-size:.88rem;color:var(--primary);text-decoration:none"><?= htmlspecialchars($phone) ?></a></div><?php endif; ?>
        <?php if ($hours): ?><div style="background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius-md);padding:20px"><div style="font-size:1.5rem;margin-bottom:8px">🕐</div><p style="font-size:.88rem;color:var(--text-secondary)"><?= htmlspecialchars($hours) ?></p></div><?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
