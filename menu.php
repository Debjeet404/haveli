<?php
require_once 'includes/config.php';
$pageTitle = 'Menu';
$pdo = getDB();

$catSlug = sanitize($_GET['category'] ?? 'all');
$search  = sanitize($_GET['q'] ?? '');
$sort    = sanitize($_GET['sort'] ?? 'popular');

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order ASC")->fetchAll();

// Build food query
$where = ["f.is_active = 1"];
$params = [];

if ($catSlug !== 'all' && $catSlug !== '') {
    $where[] = "c.slug = ?";
    $params[] = $catSlug;
}
if ($search) {
    $where[] = "(f.name LIKE ? OR f.description LIKE ? OR f.tags LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s]);
}

$orderBy = match($sort) {
    'price_asc'  => 'f.price ASC',
    'price_desc' => 'f.price DESC',
    'rating'     => 'f.rating DESC',
    'newest'     => 'f.id DESC',
    default      => 'f.is_popular DESC, f.rating DESC'
};

$whereSQL = implode(' AND ', $where);
$stmt = $pdo->prepare("
    SELECT f.*, c.name as cat_name, c.slug as cat_slug
    FROM foods f JOIN categories c ON f.category_id = c.id
    WHERE $whereSQL ORDER BY $orderBy
");
$stmt->execute($params);
$foods = $stmt->fetchAll();

$currency = getSetting('site_currency','₨');
require_once 'includes/header.php';
?>

<style>
.menu-hero {
  padding: calc(var(--nav-height) + 60px) clamp(16px,4vw,48px) 60px;
  background: linear-gradient(135deg,rgba(255,107,0,0.06),rgba(255,215,0,0.03));
  border-bottom: 1px solid var(--border);
  text-align: center;
}
.menu-controls {
  display: flex; align-items: center; gap: 12px;
  flex-wrap: wrap; justify-content: space-between;
  margin-bottom: 32px;
}
.sort-select {
  background: var(--glass); border: 1px solid var(--glass-border);
  border-radius: var(--radius-md); padding: 10px 14px;
  color: var(--text-primary); font-family: var(--font-body);
  font-size: .85rem; backdrop-filter: blur(10px);
  min-width: 160px;
}
.search-wrap { position: relative; flex: 1; min-width: 200px; max-width: 360px; }
.search-wrap input { padding-left: 40px !important; }
.search-wrap .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; }
.no-results { text-align: center; padding: 80px 20px; }
.no-results-icon { font-size: 4rem; margin-bottom: 16px; opacity: .5; }
</style>

<!-- Menu Hero -->
<div class="menu-hero">
  <p class="section-label" style="justify-content:center">Our Kitchen</p>
  <h1 class="section-title" style="font-size:clamp(2rem,5vw,3rem)">
    The <span class="highlight">Menu</span>
  </h1>
  <p style="color:var(--text-secondary);font-family:var(--font-elegant);font-size:1.1rem;max-width:500px;margin:0 auto">
    <?= count($foods) ?> dishes crafted with centuries-old recipes and the finest ingredients.
  </p>
</div>

<section class="section" style="padding-top:40px">
  <div class="section-inner">

    <!-- Category Filter -->
    <div class="categories-strip" style="margin-bottom:32px" role="list" aria-label="Filter by category">
      <a href="<?= BASE_URL ?>/menu.php" class="cat-chip <?= ($catSlug==='all'||$catSlug==='') ? 'active':'' ?>" role="listitem">
        🌟 All
      </a>
      <?php foreach ($categories as $cat): ?>
      <a href="<?= BASE_URL ?>/menu.php?category=<?= urlencode($cat['slug']) ?><?= $search ? '&q='.urlencode($search):'' ?>"
         class="cat-chip <?= $catSlug===$cat['slug'] ? 'active':'' ?>"
         role="listitem">
        <?= htmlspecialchars($cat['icon']) ?> <?= htmlspecialchars($cat['name']) ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Controls -->
    <div class="menu-controls">
      <!-- Search -->
      <form method="GET" style="display:flex;gap:8px;flex:1;max-width:400px" role="search">
        <?php if ($catSlug !== 'all'): ?><input type="hidden" name="category" value="<?= htmlspecialchars($catSlug) ?>"><?php endif; ?>
        <div class="search-wrap" style="flex:1">
          <span class="search-icon" aria-hidden="true">🔍</span>
          <input type="search" name="q" id="foodSearch"
                 class="form-input" placeholder="Search dishes..."
                 value="<?= htmlspecialchars($search) ?>" autocomplete="off" aria-label="Search menu">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
      </form>

      <!-- Sort -->
      <form method="GET" style="display:flex;align-items:center;gap:8px">
        <?php if ($catSlug !== 'all'): ?><input type="hidden" name="category" value="<?= htmlspecialchars($catSlug) ?>"><?php endif; ?>
        <?php if ($search): ?><input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
        <label for="sortSelect" style="font-size:.83rem;color:var(--text-muted);white-space:nowrap">Sort by:</label>
        <select name="sort" id="sortSelect" class="sort-select" onchange="this.form.submit()" aria-label="Sort foods">
          <option value="popular" <?= $sort==='popular'?'selected':'' ?>>Most Popular</option>
          <option value="rating"  <?= $sort==='rating'?'selected':'' ?>>Highest Rated</option>
          <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Price: Low to High</option>
          <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price: High to Low</option>
          <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest First</option>
        </select>
      </form>

      <p style="font-size:.85rem;color:var(--text-muted);white-space:nowrap">
        <?= count($foods) ?> item<?= count($foods)!==1?'s':'' ?>
      </p>
    </div>

    <!-- Food Grid -->
    <?php if (empty($foods)): ?>
    <div class="no-results" id="noFoodsResult">
      <div class="no-results-icon">🍽️</div>
      <h3 style="font-family:var(--font-display);margin-bottom:8px">No dishes found</h3>
      <p style="color:var(--text-secondary);margin-bottom:24px">Try a different category or search term.</p>
      <a href="<?= BASE_URL ?>/menu.php" class="btn btn-primary">View All Menu</a>
    </div>
    <?php else: ?>
    <div id="noFoodsResult" style="display:none" class="no-results">
      <div class="no-results-icon">🔍</div>
      <h3 style="font-family:var(--font-display);margin-bottom:8px">No results found</h3>
      <p style="color:var(--text-secondary)">Try a different search term.</p>
    </div>

    <div class="food-grid" id="menuGrid">
      <?php foreach ($foods as $i => $food):
        $hasDiscount = $food['discounted_price'] && $food['discounted_price'] < $food['price'];
        $displayPrice = $hasDiscount ? $food['discounted_price'] : $food['price'];
        $isFav = false;
        if (isLoggedIn()) {
          $stmt2 = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id=? AND food_id=?");
          $stmt2->execute([$_SESSION['user_id'], $food['id']]);
          $isFav = (bool)$stmt2->fetchColumn();
        }
      ?>
      <article class="food-card reveal"
               data-category="<?= htmlspecialchars($food['cat_slug']) ?>"
               data-name="<?= htmlspecialchars($food['name']) ?>"
               data-tags="<?= htmlspecialchars($food['tags'] ?? '') ?>"
               style="animation-delay:<?= ($i % 8) * 0.06 ?>s"
               aria-label="<?= htmlspecialchars($food['name']) ?>">

        <div class="food-card-img">
          <?php if ($food['image']): ?>
          <img data-src="<?= BASE_URL . '/' . htmlspecialchars($food['image']) ?>"
               src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 300'%3E%3Crect fill='%23111' width='400' height='300'/%3E%3C/svg%3E"
               alt="<?= htmlspecialchars($food['name']) ?>" loading="lazy">
          <?php else: ?>
          <div class="food-card-img-placeholder">🍽️</div>
          <?php endif; ?>

          <div class="food-badges">
            <?php if ($food['is_featured']): ?><span class="badge-tag badge-featured">⭐</span><?php endif; ?>
            <?php if ($food['is_popular']): ?><span class="badge-tag badge-popular">🔥 Hot</span><?php endif; ?>
            <?php if ($hasDiscount): ?>
            <span class="badge-tag badge-sale">
              -<?= round((1 - $food['discounted_price'] / $food['price']) * 100) ?>%
            </span>
            <?php endif; ?>
          </div>

          <button class="food-fav-btn <?= $isFav ? 'active' : '' ?>"
                  onclick="toggleFavorite(<?= $food['id'] ?>, this)"
                  aria-label="Toggle favorite">
            <?= $isFav ? '♥' : '♡' ?>
          </button>
        </div>

        <div class="food-card-body">
          <div class="food-cat"><?= htmlspecialchars($food['cat_name']) ?></div>
          <h2 class="food-name">
            <a href="<?= BASE_URL ?>/food.php?slug=<?= urlencode($food['slug']) ?>"
               style="color:inherit;text-decoration:none">
              <?= htmlspecialchars($food['name']) ?>
            </a>
          </h2>
          <?php if ($food['description']): ?>
          <p class="food-desc"><?= htmlspecialchars($food['description']) ?></p>
          <?php endif; ?>

          <div class="food-meta">
            <span class="rating">★ <?= number_format($food['rating'],1) ?></span>
            <span class="dot"></span>
            <span><?= $food['rating_count'] ?> reviews</span>
            <span class="dot"></span>
            <span>⏱ <?= $food['prep_time'] ?>min</span>
            <span class="dot"></span>
            <span class="spicy-dot spicy-<?= $food['spicy_level'] ?>" title="Spicy level: <?= $food['spicy_level'] ?>"></span>
          </div>

          <div class="food-card-footer">
            <div class="food-price">
              <span class="food-price-main"><?= $currency . number_format($displayPrice) ?></span>
              <?php if ($hasDiscount): ?>
              <span class="food-price-old"><?= $currency . number_format($food['price']) ?></span>
              <?php endif; ?>
            </div>
            <button class="btn-add-cart"
                    onclick="Cart.add(<?= $food['id'] ?>,'<?= addslashes($food['name']) ?>',<?= $displayPrice ?>,'<?= $food['image'] ? BASE_URL.'/'.addslashes($food['image']) : '' ?>')"
                    aria-label="Add to cart">+</button>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
