<?php
require_once 'includes/config.php';
$pdo = getDB();

$slug = sanitize($_GET['slug'] ?? '');
if (!$slug) { header('Location: menu.php'); exit; }

$stmt = $pdo->prepare("
    SELECT f.*, c.name as cat_name, c.slug as cat_slug
    FROM foods f JOIN categories c ON f.category_id = c.id
    WHERE f.slug = ? AND f.is_active = 1
");
$stmt->execute([$slug]);
$food = $stmt->fetch();
if (!$food) { header('Location: 404.php'); exit; }

$pageTitle = $food['name'];
$currency  = getSetting('site_currency','₨');
$hasDiscount = $food['discounted_price'] && $food['discounted_price'] < $food['price'];
$displayPrice = $hasDiscount ? $food['discounted_price'] : $food['price'];
$gallery = $food['gallery'] ? json_decode($food['gallery'], true) : [];
$ingredients = $food['ingredients'] ? array_map('trim', explode(',', $food['ingredients'])) : [];

// Related foods
$related = $pdo->prepare("
    SELECT f.*, c.name as cat_name FROM foods f
    JOIN categories c ON f.category_id = c.id
    WHERE f.category_id = ? AND f.id != ? AND f.is_active=1
    ORDER BY f.rating DESC LIMIT 4
");
$related->execute([$food['category_id'], $food['id']]);
$relatedFoods = $related->fetchAll();

// Reviews
$reviews = $pdo->prepare("
    SELECT r.*, u.name as user_name, u.avatar FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.food_id = ? AND r.is_approved = 1
    ORDER BY r.created_at DESC LIMIT 10
");
$reviews->execute([$food['id']]);
$reviewList = $reviews->fetchAll();

$isFav = false;
if (isLoggedIn()) {
  $fstmt = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id=? AND food_id=?");
  $fstmt->execute([$_SESSION['user_id'], $food['id']]);
  $isFav = (bool)$fstmt->fetchColumn();
}

require_once 'includes/header.php';
?>

<style>
.food-detail { padding: calc(var(--nav-height) + 40px) clamp(16px,4vw,48px) 60px; }
.food-detail-inner { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: start; }
.food-main-img { width: 100%; aspect-ratio: 1; border-radius: var(--radius-xl); overflow: hidden; background: var(--glass); }
.food-main-img img { width: 100%; height: 100%; object-fit: cover; transition: opacity .3s ease; }
.gallery-row { display: flex; gap: 10px; margin-top: 12px; }
.gallery-thumb { width: 72px; height: 72px; border-radius: var(--radius-md); overflow: hidden; border: 2px solid transparent; cursor: none; transition: var(--transition-fast); flex-shrink: 0; }
.gallery-thumb.active { border-color: var(--primary); }
.gallery-thumb img { width: 100%; height: 100%; object-fit: cover; }
.food-info { padding-top: 8px; }
.food-detail-cat { font-size: .75rem; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: var(--primary); margin-bottom: 10px; }
.food-detail-name { font-family: var(--font-display); font-size: clamp(1.8rem,4vw,2.8rem); font-weight: 900; line-height: 1.15; margin-bottom: 16px; }
.food-detail-price { font-family: var(--font-display); font-size: 2rem; font-weight: 900; color: var(--primary); }
.food-detail-old { font-size: 1.1rem; color: var(--text-muted); text-decoration: line-through; margin-left: 8px; }
.ingredient-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
.ingredient-chip { background: var(--glass); border: 1px solid var(--glass-border); border-radius: 100px; padding: 5px 12px; font-size: .78rem; color: var(--text-secondary); }
.meta-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; margin: 24px 0; }
.meta-item { background: var(--glass); border: 1px solid var(--glass-border); border-radius: var(--radius-md); padding: 14px; text-align: center; }
.meta-item-icon { font-size: 1.4rem; margin-bottom: 6px; }
.meta-item-val { font-family: var(--font-display); font-size: .95rem; font-weight: 700; color: var(--gold); }
.meta-item-label { font-size: .72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .08em; margin-top: 2px; }
.qty-row { display: flex; align-items: center; gap: 16px; margin: 24px 0; }
.qty-big { display: flex; align-items: center; gap: 12px; background: var(--glass); border: 1px solid var(--glass-border); border-radius: var(--radius-md); padding: 6px; }
.qty-big-btn { width: 40px; height: 40px; background: var(--glass); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); font-size: 1.2rem; display: flex; align-items: center; justify-content: center; transition: var(--transition-fast); }
.qty-big-btn:hover { background: var(--primary); border-color: var(--primary); color: #fff; }
.qty-big-input { width: 50px; text-align: center; background: none; border: none; font-family: var(--font-display); font-size: 1.2rem; font-weight: 700; color: var(--text-primary); }
.review-card { background: var(--glass); border: 1px solid var(--glass-border); border-radius: var(--radius-md); padding: 20px; }
.reviewer-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff; flex-shrink: 0; overflow: hidden; }
@media(max-width:768px) {
  .food-detail-inner { grid-template-columns: 1fr; gap: 32px; }
  .meta-grid { grid-template-columns: repeat(3,1fr); gap: 8px; }
}
</style>

<!-- Breadcrumb -->
<div style="padding: calc(var(--nav-height) + 20px) clamp(16px,4vw,48px) 0">
  <div style="max-width:1200px;margin:0 auto">
    <nav aria-label="Breadcrumb" style="font-size:.83rem;color:var(--text-muted)">
      <a href="<?= BASE_URL ?>" style="color:var(--text-muted);text-decoration:none" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Home</a>
      <span style="margin:0 8px">›</span>
      <a href="<?= BASE_URL ?>/menu.php" style="color:var(--text-muted);text-decoration:none" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Menu</a>
      <span style="margin:0 8px">›</span>
      <a href="<?= BASE_URL ?>/menu.php?category=<?= urlencode($food['cat_slug']) ?>" style="color:var(--text-muted);text-decoration:none" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">
        <?= htmlspecialchars($food['cat_name']) ?>
      </a>
      <span style="margin:0 8px">›</span>
      <span style="color:var(--text-primary)"><?= htmlspecialchars($food['name']) ?></span>
    </nav>
  </div>
</div>

<div class="food-detail">
  <div class="food-detail-inner">

    <!-- LEFT: Images -->
    <div>
      <div class="food-main-img">
        <?php if ($food['image']): ?>
        <img id="mainFoodImg" src="<?= BASE_URL . '/' . htmlspecialchars($food['image']) ?>" alt="<?= htmlspecialchars($food['name']) ?>">
        <?php else: ?>
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:8rem">🍽️</div>
        <?php endif; ?>
      </div>

      <?php if (!empty($gallery) || $food['image']): ?>
      <div class="gallery-row">
        <?php if ($food['image']): ?>
        <div class="gallery-thumb active" data-src="<?= BASE_URL . '/' . htmlspecialchars($food['image']) ?>" onclick="switchImage(this)">
          <img src="<?= BASE_URL . '/' . htmlspecialchars($food['image']) ?>" alt="Main">
        </div>
        <?php endif; ?>
        <?php foreach ($gallery as $gImg): ?>
        <div class="gallery-thumb" data-src="<?= BASE_URL . '/' . htmlspecialchars($gImg) ?>" onclick="switchImage(this)">
          <img src="<?= BASE_URL . '/' . htmlspecialchars($gImg) ?>" alt="Gallery">
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- RIGHT: Info -->
    <div class="food-info">
      <div class="food-detail-cat"><?= htmlspecialchars($food['cat_name']) ?></div>

      <h1 class="food-detail-name"><?= htmlspecialchars($food['name']) ?></h1>

      <!-- Rating -->
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
        <span class="stars-auto" data-rating="<?= $food['rating'] ?>"></span>
        <span style="font-size:.88rem;color:var(--text-secondary)"><?= number_format($food['rating'],1) ?> (<?= $food['rating_count'] ?> reviews)</span>
        <button class="food-fav-btn <?= $isFav?'active':'' ?>"
                style="position:static;width:36px;height:36px;"
                onclick="toggleFavorite(<?= $food['id'] ?>,this)"
                aria-label="Toggle favorite">
          <?= $isFav ? '♥' : '♡' ?>
        </button>
      </div>

      <!-- Price -->
      <div style="margin-bottom:20px">
        <span class="food-detail-price"><?= $currency . number_format($displayPrice) ?></span>
        <?php if ($hasDiscount): ?>
        <span class="food-detail-old"><?= $currency . number_format($food['price']) ?></span>
        <span class="badge-tag badge-sale" style="margin-left:10px;vertical-align:middle">
          Save <?= round((1 - $food['discounted_price'] / $food['price']) * 100) ?>%
        </span>
        <?php endif; ?>
      </div>

      <?php if ($food['description']): ?>
      <p style="color:var(--text-secondary);line-height:1.8;font-family:var(--font-elegant);font-size:1.05rem;margin-bottom:20px">
        <?= htmlspecialchars($food['description']) ?>
      </p>
      <?php endif; ?>

      <!-- Meta Grid -->
      <div class="meta-grid">
        <div class="meta-item">
          <div class="meta-item-icon">⏱</div>
          <div class="meta-item-val"><?= $food['prep_time'] ?> min</div>
          <div class="meta-item-label">Prep Time</div>
        </div>
        <div class="meta-item">
          <div class="meta-item-icon">
            <?php $spicyIcons = ['mild'=>'🟢','medium'=>'🟡','hot'=>'🔴','extra_hot'=>'🟣']; echo $spicyIcons[$food['spicy_level']] ?? '🟢'; ?>
          </div>
          <div class="meta-item-val"><?= ucfirst(str_replace('_',' ',$food['spicy_level'])) ?></div>
          <div class="meta-item-label">Spice Level</div>
        </div>
        <div class="meta-item">
          <div class="meta-item-icon">✅</div>
          <div class="meta-item-val" style="color:<?= $food['is_available'] ? '#22c55e' : '#ef4444' ?>">
            <?= $food['is_available'] ? 'Available' : 'Sold Out' ?>
          </div>
          <div class="meta-item-label">Status</div>
        </div>
      </div>

      <?php if (!empty($ingredients)): ?>
      <div style="margin-bottom:20px">
        <p style="font-size:.83rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px">Ingredients</p>
        <div class="ingredient-chips">
          <?php foreach ($ingredients as $ing): ?>
          <span class="ingredient-chip"><?= htmlspecialchars(trim($ing)) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Quantity & Add to Cart -->
      <?php if ($food['is_available']): ?>
      <div class="qty-row">
        <div class="qty-big">
          <button class="qty-big-btn" onclick="decreaseQty('detailQty')" aria-label="Decrease quantity">−</button>
          <input type="number" id="detailQty" class="qty-big-input" value="1" min="1" max="20" readonly>
          <button class="qty-big-btn" onclick="increaseQty('detailQty')" aria-label="Increase quantity">+</button>
        </div>
        <button class="btn btn-primary btn-lg"
                style="flex:1"
                onclick="addToCartDetail(<?= $food['id'] ?>,'<?= addslashes($food['name']) ?>',<?= $displayPrice ?>,'<?= $food['image'] ? BASE_URL.'/'.addslashes($food['image']) : '' ?>')">
          🛒 Add to Cart
        </button>
      </div>
      <button class="btn btn-outline btn-full" style="margin-top:10px"
              onclick="addToCartDetail(<?= $food['id'] ?>,'<?= addslashes($food['name']) ?>',<?= $displayPrice ?>,'<?= $food['image'] ? BASE_URL.'/'.addslashes($food['image']) : '' ?>');window.location.href='<?= BASE_URL ?>/checkout.php'">
        ⚡ Order Now
      </button>
      <?php else: ?>
      <div class="btn btn-outline btn-full" style="opacity:.5;justify-content:center;cursor:not-allowed">
        😔 Currently Unavailable
      </div>
      <?php endif; ?>

      <!-- Tags -->
      <?php if ($food['tags']): ?>
      <div style="margin-top:20px;display:flex;flex-wrap:wrap;gap:6px">
        <?php foreach (explode(',', $food['tags']) as $tag): ?>
        <a href="<?= BASE_URL ?>/menu.php?q=<?= urlencode(trim($tag)) ?>"
           style="color:var(--text-muted);text-decoration:none;font-size:.75rem;background:var(--glass);border:1px solid var(--glass-border);border-radius:100px;padding:4px 10px;transition:var(--transition-fast)"
           onmouseover="this.style.color='var(--primary)';this.style.borderColor='rgba(255,107,0,.3)'"
           onmouseout="this.style.color='var(--text-muted)';this.style.borderColor='var(--glass-border)'">
          #<?= htmlspecialchars(trim($tag)) ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- REVIEWS -->
  <?php if (!empty($reviewList)): ?>
  <div style="max-width:1200px;margin:60px auto 0">
    <div class="divider"></div>
    <h2 style="font-family:var(--font-display);font-size:1.5rem;margin:32px 0 24px">
      Customer <span style="color:var(--gold)">Reviews</span>
    </h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px">
      <?php foreach ($reviewList as $rev): ?>
      <div class="review-card">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
          <div class="reviewer-avatar">
            <?php if ($rev['avatar']): ?>
            <img src="<?= BASE_URL.'/'.$rev['avatar'] ?>" alt="" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
            <?= strtoupper(substr($rev['user_name'],0,1)) ?>
            <?php endif; ?>
          </div>
          <div>
            <p style="font-weight:600;font-size:.9rem"><?= htmlspecialchars($rev['user_name']) ?></p>
            <p style="font-size:.72rem;color:var(--text-muted)"><?= date('M j, Y', strtotime($rev['created_at'])) ?></p>
          </div>
          <div style="margin-left:auto;color:var(--gold);font-size:.85rem">
            <?= str_repeat('★', $rev['rating']) ?><?= str_repeat('☆', 5 - $rev['rating']) ?>
          </div>
        </div>
        <?php if ($rev['review']): ?>
        <p style="font-size:.88rem;color:var(--text-secondary);line-height:1.7"><?= htmlspecialchars($rev['review']) ?></p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- RELATED FOODS -->
  <?php if (!empty($relatedFoods)): ?>
  <div style="max-width:1200px;margin:60px auto 0">
    <div class="divider"></div>
    <h2 style="font-family:var(--font-display);font-size:1.5rem;margin:32px 0 24px">
      You Might Also <span style="color:var(--primary)">Like</span>
    </h2>
    <div class="food-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
      <?php foreach ($relatedFoods as $rf):
        $rfDiscount = $rf['discounted_price'] && $rf['discounted_price'] < $rf['price'];
        $rfPrice = $rfDiscount ? $rf['discounted_price'] : $rf['price'];
      ?>
      <article class="food-card">
        <div class="food-card-img">
          <?php if ($rf['image']): ?>
          <img data-src="<?= BASE_URL.'/'.$rf['image'] ?>"
               src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 300'%3E%3Crect fill='%23111' width='400' height='300'/%3E%3C/svg%3E"
               alt="<?= htmlspecialchars($rf['name']) ?>" loading="lazy">
          <?php else: ?>
          <div class="food-card-img-placeholder">🍽️</div>
          <?php endif; ?>
        </div>
        <div class="food-card-body">
          <div class="food-cat"><?= htmlspecialchars($rf['cat_name']) ?></div>
          <h3 class="food-name">
            <a href="<?= BASE_URL ?>/food.php?slug=<?= urlencode($rf['slug']) ?>" style="color:inherit;text-decoration:none">
              <?= htmlspecialchars($rf['name']) ?>
            </a>
          </h3>
          <div class="food-card-footer">
            <div class="food-price">
              <span class="food-price-main"><?= $currency . number_format($rfPrice) ?></span>
            </div>
            <button class="btn-add-cart" onclick="Cart.add(<?= $rf['id'] ?>,'<?= addslashes($rf['name']) ?>',<?= $rfPrice ?>,'<?= $rf['image'] ? BASE_URL.'/'.addslashes($rf['image']) : '' ?>')">+</button>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
function switchImage(thumb) {
  document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
  thumb.classList.add('active');
  const mainImg = document.getElementById('mainFoodImg');
  if (mainImg) {
    mainImg.style.opacity = '0';
    setTimeout(() => { mainImg.src = thumb.dataset.src; mainImg.style.opacity = '1'; }, 200);
  }
}
</script>

<?php require_once 'includes/footer.php'; ?>
