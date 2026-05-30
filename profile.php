<?php
require_once 'includes/config.php';
if (!isLoggedIn()) redirect(BASE_URL . '/login.php');
$pageTitle = 'My Profile';
$pdo = getDB();

// Load user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Recent orders
$orders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$orders->execute([$_SESSION['user_id']]);
$recentOrders = $orders->fetchAll();

// Total orders
$totalOrders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?");
$totalOrders->execute([$_SESSION['user_id']]);
$ordersCount = $totalOrders->fetchColumn();

// Total spent
$totalSpent = $pdo->prepare("SELECT SUM(total) FROM orders WHERE user_id=? AND status='delivered'");
$totalSpent->execute([$_SESSION['user_id']]);
$amountSpent = $totalSpent->fetchColumn() ?: 0;

// Favorites
$favs = $pdo->prepare("
    SELECT f.*, c.name as cat_name FROM favorites fv
    JOIN foods f ON fv.food_id = f.id
    JOIN categories c ON f.category_id = c.id
    WHERE fv.user_id = ? AND f.is_active=1
    ORDER BY fv.created_at DESC LIMIT 6
");
$favs->execute([$_SESSION['user_id']]);
$favorites = $favs->fetchAll();

// Notifications
$notifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$notifs->execute([$_SESSION['user_id']]);
$notifications = $notifs->fetchAll();
$unreadCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
$unreadCount->execute([$_SESSION['user_id']]);
$unread = $unreadCount->fetchColumn();

$currency = getSetting('site_currency','₨');
$error = ''; $success = '';
$csrf = generateCSRF();
$activeTab = sanitize($_GET['tab'] ?? 'overview');
$welcome = isset($_GET['welcome']);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) { $error = 'Security error.'; }
    else {
        $name  = sanitize($_POST['name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $data  = [$name, $phone];
        $sql   = "UPDATE users SET name=?, phone=?";

        // Avatar upload
        if (!empty($_FILES['avatar']['name'])) {
            $up = uploadFile($_FILES['avatar'], 'avatars');
            if (isset($up['success'])) { $sql .= ", avatar=?"; $data[] = $up['path']; }
        }

        // Password change
        $newPwd = $_POST['new_password'] ?? '';
        if ($newPwd) {
            if (strlen($newPwd) < 6) { $error = 'Password must be at least 6 characters.'; }
            elseif (!password_verify($_POST['current_password'] ?? '', $user['password'])) { $error = 'Current password is incorrect.'; }
            else { $sql .= ", password=?"; $data[] = password_hash($newPwd, PASSWORD_DEFAULT); }
        }

        if (!$error) {
            $data[] = $_SESSION['user_id'];
            $pdo->prepare($sql . ", updated_at=NOW() WHERE id=?")->execute($data);
            $_SESSION['user_name'] = $name;
            $success = 'Profile updated successfully!';
            // Reload user
            $stmt2 = $pdo->prepare("SELECT * FROM users WHERE id=?");
            $stmt2->execute([$_SESSION['user_id']]);
            $user = $stmt2->fetch();
        }
    }
}

require_once 'includes/header.php';
?>

<style>
.profile-page { padding: calc(var(--nav-height)+32px) clamp(16px,4vw,48px) 80px; }
.profile-inner { max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: 280px 1fr; gap: 28px; align-items: start; }
.profile-sidebar { background: var(--glass); border: 1px solid var(--glass-border); border-radius: var(--radius-xl); padding: 28px; backdrop-filter: blur(20px); position: sticky; top: calc(var(--nav-height)+16px); }
.profile-avatar-wrap { text-align: center; margin-bottom: 24px; }
.profile-avatar { width: 90px; height: 90px; border-radius: 50%; background: linear-gradient(135deg,var(--primary),var(--gold)); display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800; color: #fff; margin: 0 auto 12px; overflow: hidden; border: 3px solid rgba(255,107,0,.3); box-shadow: 0 0 24px rgba(255,107,0,.2); }
.profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
.profile-name { font-family: var(--font-display); font-size: 1.1rem; font-weight: 700; }
.profile-email { font-size: .8rem; color: var(--text-muted); margin-top: 3px; }
.sidebar-nav { list-style: none; }
.sidebar-nav li { margin-bottom: 4px; }
.sidebar-nav a { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: var(--radius-md); color: var(--text-secondary); text-decoration: none; font-size: .88rem; font-weight: 500; transition: var(--transition-fast); }
.sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,107,0,.08); color: var(--primary); }
.sidebar-nav a.active { border-left: 2px solid var(--primary); }
.profile-content { background: var(--glass); border: 1px solid var(--glass-border); border-radius: var(--radius-xl); padding: 32px; backdrop-filter: blur(20px); min-height: 400px; }
.stat-mini { background: var(--glass); border: 1px solid var(--glass-border); border-radius: var(--radius-md); padding: 16px; text-align: center; }
.stat-mini-val { font-family: var(--font-display); font-size: 1.5rem; font-weight: 800; color: var(--primary); }
.stat-mini-label { font-size: .75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .08em; margin-top: 4px; }
.order-row { display: flex; align-items: center; gap: 12px; padding: 14px 0; border-bottom: 1px solid var(--border); }
.order-row:last-child { border-bottom: none; }
@media(max-width:768px) { .profile-inner { grid-template-columns: 1fr; } .profile-sidebar { position: static; } }
</style>

<?php if ($welcome): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast('Welcome to Haveli! 🎉 Use code WELCOME20 for 20% off.','success','🎉'));</script>
<?php endif; ?>

<div class="profile-page">
  <div class="profile-inner">

    <!-- Sidebar -->
    <div class="profile-sidebar">
      <div class="profile-avatar-wrap">
        <div class="profile-avatar">
          <?php if ($user['avatar']): ?>
          <img src="<?= BASE_URL.'/'.$user['avatar'] ?>" alt="Avatar">
          <?php else: ?>
          <?= strtoupper(substr($user['name'],0,1)) ?>
          <?php endif; ?>
        </div>
        <div class="profile-name"><?= htmlspecialchars($user['name']) ?></div>
        <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
        <?php if ($user['phone']): ?>
        <div class="profile-email" style="margin-top:2px"><?= htmlspecialchars($user['phone']) ?></div>
        <?php endif; ?>
      </div>

      <ul class="sidebar-nav">
        <li><a href="?tab=overview" class="<?= $activeTab==='overview'?'active':'' ?>">🏠 Overview</a></li>
        <li><a href="?tab=orders" class="<?= $activeTab==='orders'?'active':'' ?>">📦 My Orders</a></li>
        <li><a href="?tab=favorites" class="<?= $activeTab==='favorites'?'active':'' ?>">♥ Favorites</a></li>
        <li><a href="?tab=notifications" class="<?= $activeTab==='notifications'?'active':'' ?>">
          🔔 Notifications <?= $unread > 0 ? "<span style='background:var(--primary);color:#fff;border-radius:100px;padding:1px 7px;font-size:.7rem;'>$unread</span>" : '' ?>
        </a></li>
        <li><a href="?tab=settings" class="<?= $activeTab==='settings'?'active':'' ?>">⚙️ Settings</a></li>
        <li><a href="<?= BASE_URL ?>/logout.php" style="color:#ef4444 !important">🚪 Logout</a></li>
      </ul>
    </div>

    <!-- Main Content -->
    <div class="profile-content">

      <?php if ($error): ?><div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:var(--radius-md);padding:12px 16px;color:#ef4444;margin-bottom:20px">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:var(--radius-md);padding:12px 16px;color:#22c55e;margin-bottom:20px">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>

      <!-- OVERVIEW TAB -->
      <?php if ($activeTab === 'overview'): ?>
      <h2 style="font-family:var(--font-display);font-size:1.3rem;margin-bottom:24px">Dashboard Overview</h2>

      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:32px">
        <div class="stat-mini">
          <div class="stat-mini-val"><?= $ordersCount ?></div>
          <div class="stat-mini-label">Total Orders</div>
        </div>
        <div class="stat-mini">
          <div class="stat-mini-val"><?= $currency . number_format($amountSpent) ?></div>
          <div class="stat-mini-label">Total Spent</div>
        </div>
        <div class="stat-mini">
          <div class="stat-mini-val"><?= count($favorites) ?></div>
          <div class="stat-mini-label">Favorites</div>
        </div>
      </div>

      <h3 style="font-family:var(--font-display);font-size:1rem;margin-bottom:16px">Recent Orders</h3>
      <?php if (empty($recentOrders)): ?>
      <div style="text-align:center;padding:32px;color:var(--text-muted)">
        <div style="font-size:3rem;margin-bottom:8px">📦</div>
        <p>No orders yet. <a href="menu.php" style="color:var(--primary);text-decoration:none">Order now!</a></p>
      </div>
      <?php else: ?>
      <?php foreach ($recentOrders as $ord): ?>
      <div class="order-row">
        <div style="flex:1">
          <p style="font-weight:600;font-size:.9rem"><?= htmlspecialchars($ord['order_number']) ?></p>
          <p style="font-size:.78rem;color:var(--text-muted)"><?= date('M j, Y', strtotime($ord['created_at'])) ?></p>
        </div>
        <span class="status-badge status-<?= $ord['status'] ?>"><?= ucwords(str_replace('_',' ',$ord['status'])) ?></span>
        <p style="font-weight:700;color:var(--primary);font-size:.9rem"><?= $currency . number_format($ord['total']) ?></p>
        <a href="track.php?order=<?= urlencode($ord['order_number']) ?>" class="btn btn-ghost btn-sm">Track</a>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>

      <!-- ORDERS TAB -->
      <?php elseif ($activeTab === 'orders'): ?>
      <?php
        $allOrders = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
        $allOrders->execute([$_SESSION['user_id']]);
        $allOrdersList = $allOrders->fetchAll();
      ?>
      <h2 style="font-family:var(--font-display);font-size:1.3rem;margin-bottom:24px">My Orders (<?= count($allOrdersList) ?>)</h2>
      <?php if (empty($allOrdersList)): ?>
      <div style="text-align:center;padding:48px;color:var(--text-muted)">
        <div style="font-size:3.5rem;margin-bottom:12px">🍽️</div>
        <h3 style="margin-bottom:8px">No orders yet</h3>
        <a href="menu.php" class="btn btn-primary btn-sm">Browse Menu</a>
      </div>
      <?php else: ?>
      <?php foreach ($allOrdersList as $ord):
        $oi = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id=?");
        $oi->execute([$ord['id']]);
        $itemCount = $oi->fetchColumn();
      ?>
      <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-md);padding:16px;margin-bottom:12px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:10px">
          <div>
            <span style="font-family:var(--font-display);font-weight:700"><?= htmlspecialchars($ord['order_number']) ?></span>
            <span style="font-size:.78rem;color:var(--text-muted);margin-left:10px"><?= date('M j, Y g:i A', strtotime($ord['created_at'])) ?></span>
          </div>
          <span class="status-badge status-<?= $ord['status'] ?>"><?= ucwords(str_replace('_',' ',$ord['status'])) ?></span>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
          <span style="font-size:.85rem;color:var(--text-muted)"><?= $itemCount ?> item<?= $itemCount!==1?'s':'' ?> · <?= $ord['payment_method']==='cod'?'Cash on Delivery':'Online' ?></span>
          <span style="font-family:var(--font-display);font-size:1rem;font-weight:700;color:var(--primary)"><?= $currency . number_format($ord['total']) ?></span>
        </div>
        <div style="display:flex;gap:8px;margin-top:12px">
          <a href="track.php?order=<?= urlencode($ord['order_number']) ?>" class="btn btn-primary btn-sm">Track Order</a>
          <?php if ($ord['status'] === 'delivered'): ?>
          <button class="btn btn-ghost btn-sm" onclick="reorder(<?= $ord['id'] ?>)">🔁 Reorder</button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>

      <!-- FAVORITES TAB -->
      <?php elseif ($activeTab === 'favorites'): ?>
      <?php
        $allFavs = $pdo->prepare("SELECT f.*,c.name as cat_name FROM favorites fv JOIN foods f ON fv.food_id=f.id JOIN categories c ON f.category_id=c.id WHERE fv.user_id=? ORDER BY fv.created_at DESC");
        $allFavs->execute([$_SESSION['user_id']]);
        $allFavorites = $allFavs->fetchAll();
      ?>
      <h2 style="font-family:var(--font-display);font-size:1.3rem;margin-bottom:24px">My Favorites (<?= count($allFavorites) ?>)</h2>
      <?php if (empty($allFavorites)): ?>
      <div style="text-align:center;padding:48px;color:var(--text-muted)">
        <div style="font-size:3.5rem;margin-bottom:12px">♡</div>
        <h3 style="margin-bottom:8px">No favorites yet</h3>
        <a href="menu.php" class="btn btn-primary btn-sm">Browse Menu</a>
      </div>
      <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px">
        <?php foreach ($allFavorites as $fav):
          $fp = $fav['discounted_price'] && $fav['discounted_price'] < $fav['price'] ? $fav['discounted_price'] : $fav['price'];
        ?>
        <div class="food-card" style="font-size:.9rem">
          <div class="food-card-img" style="height:140px">
            <?php if ($fav['image']): ?><img src="<?= BASE_URL.'/'.$fav['image'] ?>" alt="<?= htmlspecialchars($fav['name']) ?>">
            <?php else: ?><div class="food-card-img-placeholder" style="font-size:2.5rem">🍽️</div><?php endif; ?>
          </div>
          <div class="food-card-body" style="padding:14px">
            <div class="food-cat"><?= htmlspecialchars($fav['cat_name']) ?></div>
            <h3 class="food-name" style="font-size:.95rem"><a href="food.php?slug=<?= urlencode($fav['slug']) ?>" style="color:inherit;text-decoration:none"><?= htmlspecialchars($fav['name']) ?></a></h3>
            <div class="food-card-footer">
              <span class="food-price-main"><?= $currency . number_format($fp) ?></span>
              <button class="btn-add-cart" onclick="Cart.add(<?= $fav['id'] ?>,'<?= addslashes($fav['name']) ?>',<?= $fp ?>,'<?= $fav['image'] ? BASE_URL.'/'.addslashes($fav['image']) : '' ?>')">+</button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- NOTIFICATIONS TAB -->
      <?php elseif ($activeTab === 'notifications'): ?>
      <?php
        $allNotifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC");
        $allNotifs->execute([$_SESSION['user_id']]);
        $allNotifsList = $allNotifs->fetchAll();
        // Mark all as read
        $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$_SESSION['user_id']]);
      ?>
      <h2 style="font-family:var(--font-display);font-size:1.3rem;margin-bottom:24px">Notifications</h2>
      <?php if (empty($allNotifsList)): ?>
      <div style="text-align:center;padding:48px;color:var(--text-muted)">
        <div style="font-size:3.5rem;margin-bottom:12px">🔔</div>
        <h3>No notifications</h3>
      </div>
      <?php else: ?>
      <?php foreach ($allNotifsList as $notif): ?>
      <div style="display:flex;gap:14px;padding:14px 0;border-bottom:1px solid var(--border);<?= !$notif['is_read'] ? 'background:rgba(255,107,0,.03);border-left:3px solid var(--primary);padding-left:12px;border-radius:4px;' : '' ?>">
        <div style="font-size:1.5rem;flex-shrink:0"><?= ['order'=>'📦','promo'=>'🎉','info'=>'ℹ️','system'=>'⚙️'][$notif['type']] ?? 'ℹ️' ?></div>
        <div>
          <p style="font-weight:600;font-size:.9rem;margin-bottom:3px"><?= htmlspecialchars($notif['title']) ?></p>
          <p style="font-size:.83rem;color:var(--text-secondary)"><?= htmlspecialchars($notif['message']) ?></p>
          <p style="font-size:.75rem;color:var(--text-muted);margin-top:5px"><?= date('M j, Y g:i A', strtotime($notif['created_at'])) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>

      <!-- SETTINGS TAB -->
      <?php elseif ($activeTab === 'settings'): ?>
      <h2 style="font-family:var(--font-display);font-size:1.3rem;margin-bottom:24px">Account Settings</h2>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="update_profile" value="1">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group" style="grid-column:1/-1">
            <label class="form-label">Profile Photo</label>
            <div style="display:flex;align-items:center;gap:16px">
              <div class="profile-avatar" style="width:64px;height:64px;flex-shrink:0">
                <?php if ($user['avatar']): ?><img src="<?= BASE_URL.'/'.$user['avatar'] ?>" alt=""><?php else: ?><?= strtoupper(substr($user['name'],0,1)) ?><?php endif; ?>
              </div>
              <input type="file" name="avatar" class="form-input" accept="image/jpeg,image/png,image/webp">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($user['name']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="tel" name="phone" class="form-input" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
          </div>
          <div class="form-group" style="grid-column:1/-1">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-input" value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity:.6">
            <div class="form-hint">Email cannot be changed</div>
          </div>

          <div style="grid-column:1/-1"><div class="divider"></div><p style="font-family:var(--font-display);font-size:.9rem;margin-bottom:16px">Change Password</p></div>

          <div class="form-group" style="grid-column:1/-1">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-input" placeholder="Enter current password">
          </div>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-input" placeholder="Min. 6 characters">
          </div>
          <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_new_password" class="form-input" placeholder="Repeat new password">
          </div>
        </div>

        <button type="submit" class="btn btn-primary">💾 Save Changes</button>
      </form>
      <?php endif; ?>

    </div><!-- /profile-content -->
  </div><!-- /profile-inner -->
</div>

<script>
async function reorder(orderId) {
  try {
    const res = await fetch('/haveli/api/reorder.php', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ order_id: orderId })
    });
    const data = await res.json();
    if (data.items) {
      data.items.forEach(item => Cart.add(item.food_id, item.food_name, item.price, item.image, item.quantity));
      showToast('Items added to cart!', 'success', '🛒');
      openCart();
    }
  } catch { showToast('Could not reorder', 'error', '✗'); }
}
</script>

<?php require_once 'includes/footer.php'; ?>
