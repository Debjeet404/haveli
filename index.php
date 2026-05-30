<?php
require_once 'includes/config.php';
$pageTitle = getSetting('site_tagline','Royal Flavors. Timeless Traditions.');

// Fetch featured foods
$pdo = getDB();
$featuredFoods = $pdo->query("
    SELECT f.*, c.name as cat_name, c.slug as cat_slug
    FROM foods f
    JOIN categories c ON f.category_id = c.id
    WHERE f.is_featured = 1 AND f.is_active = 1
    ORDER BY f.sort_order ASC, f.id DESC
    LIMIT 8
")->fetchAll();

// Fetch categories
$categories = $pdo->query("
    SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC
")->fetchAll();

// Stats
$totalOrders   = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='delivered'")->fetchColumn();
$totalCustomers= $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Hero settings
$heroTitle     = getSetting('hero_title','Where Royalty Meets');
$heroHighlight = getSetting('hero_title_highlight','Flavor');
$heroSubtitle  = getSetting('hero_subtitle','Experience the grandeur of Mughlai cuisine.');
$heroCta1      = getSetting('hero_cta_primary','Explore Menu');
$heroCta2      = getSetting('hero_cta_secondary','Our Story');
$currency      = getSetting('site_currency','₨');
$deliveryAbove = getSetting('free_delivery_above','2000');

require_once 'includes/header.php';
?>

<!-- ==================== HERO ==================== -->
<section class="hero" aria-label="Welcome to <?= htmlspecialchars(getSetting('site_name','Haveli')) ?>">
  <div class="hero-bg" aria-hidden="true"></div>

  <div class="hero-inner">
    <!-- Left: Text -->
    <div>
      <div class="hero-badge">Est. 1985 &nbsp;·&nbsp; Lahore's Finest</div>

      <h1 class="hero-title">
        <?= htmlspecialchars($heroTitle) ?><br>
        <span class="line2"><?= htmlspecialchars($heroHighlight) ?></span>
      </h1>

      <p class="hero-desc"><?= htmlspecialchars($heroSubtitle) ?></p>

      <div class="hero-actions">
        <a href="<?= BASE_URL ?>/menu.php" class="btn btn-primary btn-lg">
          🍽️ <?= htmlspecialchars($heroCta1) ?>
        </a>
        <a href="<?= BASE_URL ?>/about.php" class="btn btn-outline btn-lg">
          <?= htmlspecialchars($heroCta2) ?> →
        </a>
      </div>

      <div class="hero-stats">
        <div>
          <div class="hero-stat-num"><?= number_format($totalOrders > 0 ? $totalOrders : 12000) ?>+</div>
          <div class="hero-stat-label">Orders Served</div>
        </div>
        <div style="width:1px;height:40px;background:var(--border)"></div>
        <div>
          <div class="hero-stat-num"><?= number_format($totalCustomers > 0 ? $totalCustomers : 4500) ?>+</div>
          <div class="hero-stat-label">Happy Guests</div>
        </div>
        <div style="width:1px;height:40px;background:var(--border)"></div>
        <div>
          <div class="hero-stat-num">38+</div>
          <div class="hero-stat-label">Years Legacy</div>
        </div>
      </div>
    </div>

    <!-- Right: Visual -->
    <div class="hero-visual" aria-hidden="true">
      <div class="hero-img-wrap">
        <div class="hero-img-circle">
          <div class="hero-img-circle-inner">🍽️</div>
        </div>
      </div>

      <!-- Floating Cards -->
      <div class="hero-floating-card card-1">
        <div class="hero-fc-label">Today's Special</div>
        <div class="hero-fc-value">Dum Gosht Biryani</div>
        <div class="hero-fc-stars">★★★★★</div>
      </div>
      <div class="hero-floating-card card-2">
        <div class="hero-fc-label">Free Delivery</div>
        <div class="hero-fc-value">Above <?= $currency . number_format((float)$deliveryAbove) ?></div>
        <div class="hero-fc-sub">Orders within 45 min</div>
      </div>
      <div class="hero-floating-card card-3">
        <div class="hero-fc-label">Rating</div>
        <div class="hero-fc-value">4.9 / 5.0</div>
        <div class="hero-fc-sub">2000+ Reviews</div>
      </div>
    </div>
  </div>

  <!-- Scroll Indicator -->
  <div class="scroll-indicator" aria-hidden="true">
    <div class="scroll-mouse"></div>
    <span>Scroll</span>
  </div>
</section>

<!-- ==================== CATEGORIES STRIP ==================== -->
<?php if (!empty($categories)): ?>
<section class="section" style="padding-top:40px;padding-bottom:40px" aria-label="Food categories">
  <div class="section-inner">
    <div class="categories-strip" role="list">
      <button class="cat-chip active" onclick="filterByCategory('all',this)" role="listitem">
        🌟 All Items
      </button>
      <?php foreach ($categories as $cat): ?>
      <button class="cat-chip" onclick="window.location.href='<?= BASE_URL ?>/menu.php?category=<?= urlencode($cat['slug']) ?>'" role="listitem">
        <?= htmlspecialchars($cat['icon']) ?> <?= htmlspecialchars($cat['name']) ?>
      </button>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ==================== FEATURED FOODS ==================== -->
<?php if (!empty($featuredFoods)): ?>
<section class="section" id="featured" aria-label="Featured dishes">
  <div class="section-inner">
    <div class="reveal" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:40px">
      <div>
        <p class="section-label">Chef's Selection</p>
        <h2 class="section-title">Featured <span class="highlight">Dishes</span></h2>
        <p class="section-subtitle">Handpicked masterpieces from our royal kitchen — each dish a timeless tradition.</p>
      </div>
      <a href="<?= BASE_URL ?>/menu.php" class="btn btn-outline">View Full Menu →</a>
    </div>

    <div class="food-grid">
      <?php foreach ($featuredFoods as $i => $food): ?>
      <?php
        $hasDiscount = $food['discounted_price'] && $food['discounted_price'] < $food['price'];
        $displayPrice = $hasDiscount ? $food['discounted_price'] : $food['price'];
        $isFav = false;
        if (isLoggedIn()) {
          $stmt = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id=? AND food_id=?");
          $stmt->execute([$_SESSION['user_id'], $food['id']]);
          $isFav = (bool)$stmt->fetchColumn();
        }
      ?>
      <article class="food-card reveal reveal-delay-<?= ($i % 3) + 1 ?>"
               data-category="<?= htmlspecialchars($food['cat_slug']) ?>"
               data-name="<?= htmlspecialchars($food['name']) ?>"
               data-tags="<?= htmlspecialchars($food['tags'] ?? '') ?>"
               aria-label="<?= htmlspecialchars($food['name']) ?>">

        <!-- Image -->
        <div class="food-card-img">
          <?php if ($food['image']): ?>
          <img data-src="<?= BASE_URL . '/' . htmlspecialchars($food['image']) ?>"
               src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 300'%3E%3Crect fill='%23111' width='400' height='300'/%3E%3C/svg%3E"
               alt="<?= htmlspecialchars($food['name']) ?>" loading="lazy">
          <?php else: ?>
          <div class="food-card-img-placeholder">🍽️</div>
          <?php endif; ?>

          <!-- Badges -->
          <div class="food-badges">
            <?php if ($food['is_featured']): ?><span class="badge-tag badge-featured">⭐ Featured</span><?php endif; ?>
            <?php if ($food['is_popular']): ?><span class="badge-tag badge-popular">🔥 Popular</span><?php endif; ?>
            <?php if ($hasDiscount): ?><span class="badge-tag badge-sale">Sale</span><?php endif; ?>
          </div>

          <!-- Favorite -->
          <button class="food-fav-btn <?= $isFav ? 'active' : '' ?>"
                  onclick="toggleFavorite(<?= $food['id'] ?>, this)"
                  aria-label="<?= $isFav ? 'Remove from' : 'Add to' ?> favorites">
            <?= $isFav ? '♥' : '♡' ?>
          </button>
        </div>

        <!-- Body -->
        <div class="food-card-body">
          <div class="food-cat"><?= htmlspecialchars($food['cat_name']) ?></div>
          <h3 class="food-name">
            <a href="<?= BASE_URL ?>/food.php?slug=<?= urlencode($food['slug']) ?>" style="color:inherit;text-decoration:none">
              <?= htmlspecialchars($food['name']) ?>
            </a>
          </h3>
          <?php if ($food['description']): ?>
          <p class="food-desc"><?= htmlspecialchars($food['description']) ?></p>
          <?php endif; ?>

          <!-- Meta -->
          <div class="food-meta">
            <span class="rating">★ <?= number_format($food['rating'],1) ?></span>
            <span class="dot"></span>
            <span><?= $food['rating_count'] ?> reviews</span>
            <span class="dot"></span>
            <span>⏱ <?= $food['prep_time'] ?>min</span>
            <span class="dot"></span>
            <span class="spicy-dot spicy-<?= htmlspecialchars($food['spicy_level']) ?>" title="<?= htmlspecialchars($food['spicy_level']) ?>"></span>
          </div>

          <!-- Footer -->
          <div class="food-card-footer">
            <div class="food-price">
              <span class="food-price-main"><?= $currency . number_format($displayPrice) ?></span>
              <?php if ($hasDiscount): ?>
              <span class="food-price-old"><?= $currency . number_format($food['price']) ?></span>
              <?php endif; ?>
            </div>
            <button class="btn-add-cart"
                    onclick="Cart.add(<?= $food['id'] ?>, '<?= addslashes($food['name']) ?>', <?= $displayPrice ?>, '<?= $food['image'] ? BASE_URL . '/' . addslashes($food['image']) : '' ?>')"
                    aria-label="Add <?= htmlspecialchars($food['name']) ?> to cart">
              +
            </button>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <div style="text-align:center;margin-top:48px">
      <a href="<?= BASE_URL ?>/menu.php" class="btn btn-gold btn-lg">
        🍴 Explore Complete Menu
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ==================== WHY HAVELI ==================== -->
<section class="section" style="background:linear-gradient(135deg,rgba(255,107,0,0.03),rgba(255,215,0,0.02))" aria-label="Why choose Haveli">
  <div class="section-inner">
    <div class="reveal" style="text-align:center;margin-bottom:56px">
      <p class="section-label" style="justify-content:center">Our Promise</p>
      <h2 class="section-title">Why <span class="highlight">Haveli</span>?</h2>
      <p class="section-subtitle" style="margin:0 auto;text-align:center">Every dish carries a story of heritage, passion, and culinary mastery.</p>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px">
      <?php
      $whys = [
        ['🧑‍🍳','Master Chefs','Custodians of 200-year-old Mughal recipes, our chefs train for years before mastering our signature dishes.'],
        ['🌶️','Authentic Spices','We source whole spices directly from Khari Baoli, blending proprietary masalas for each dish.'],
        ['🔥','Dum Cooking','The ancient art of slow-sealing food — our biryanis and curries are sealed with dough and cooked over charcoal.'],
        ['🚀','Fast Delivery','From our kitchen to your door in 30–45 minutes. Hot, fresh, and exactly as you ordered.'],
        ['🏆','Award Winning','Lahore Food Awards Best Mughlai 2019, 2021, 2023. Consistency is our hallmark.'],
        ['💚','Fresh Daily','Zero freezing policy. Every ingredient is sourced fresh each morning from trusted local suppliers.'],
      ];
      foreach ($whys as $i => $why): ?>
      <div class="glass-card reveal reveal-delay-<?= ($i % 3) + 1 ?>" style="padding:32px 28px;text-align:center">
        <div style="font-size:2.5rem;margin-bottom:16px"><?= $why[0] ?></div>
        <h3 style="font-family:var(--font-display);font-size:1.05rem;font-weight:700;margin-bottom:10px;color:var(--gold)"><?= $why[1] ?></h3>
        <p style="font-size:.88rem;color:var(--text-secondary);line-height:1.7"><?= $why[2] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ==================== CTA BANNER ==================== -->
<section class="section" aria-label="Order now call to action">
  <div class="section-inner">
    <div class="reveal" style="
      background: linear-gradient(135deg, rgba(255,107,0,0.12), rgba(255,215,0,0.08));
      border: 1px solid rgba(255,107,0,0.2);
      border-radius: var(--radius-xl);
      padding: clamp(40px,6vw,80px) clamp(24px,5vw,80px);
      text-align: center;
      position: relative; overflow: hidden;
    ">
      <div aria-hidden="true" style="position:absolute;inset:0;background:radial-gradient(ellipse at 50% 50%,rgba(255,107,0,0.06),transparent 70%);pointer-events:none"></div>
      <p class="section-label" style="justify-content:center">Ready to Order?</p>
      <h2 class="section-title" style="margin-bottom:16px">Royal Flavors <span class="highlight">Delivered</span><br>To Your Door</h2>
      <p style="color:var(--text-secondary);max-width:480px;margin:0 auto 36px;font-family:var(--font-elegant);font-size:1.1rem;line-height:1.8">
        Order now and experience the grandeur of Haveli from the comfort of your home.
        Free delivery on orders above <?= $currency . number_format((float)$deliveryAbove) ?>.
      </p>
      <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/menu.php" class="btn btn-primary btn-lg">🍽️ Order Now</a>
        <?php $waNum = getSetting('whatsapp_number',''); if ($waNum): ?>
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$waNum) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-lg">
          💬 WhatsApp Order
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
