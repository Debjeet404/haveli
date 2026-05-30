<?php
/**
 * HAVELI Admin — Shared Header & Sidebar
 * Include at top of every admin page
 */
require_once dirname(__DIR__) . '/../includes/config.php';

// Admin auth guard
if (!isAdminLoggedIn()) {
    redirect(BASE_URL . '/admin/login.php');
}

// Load admin data
$pdo_a = getDB();
$adminStmt = $pdo_a->prepare("SELECT * FROM admins WHERE id = ?");
$adminStmt->execute([$_SESSION['admin_id']]);
$adminUser = $adminStmt->fetch();
if (!$adminUser) { session_destroy(); redirect(BASE_URL . '/admin/login.php'); }

$siteName = getSetting('site_name','Haveli');
$adminPage = basename($_SERVER['PHP_SELF'],'.php');

// Unread order notifications
$unreadOrders = $pdo_a->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$totalRevenue = $pdo_a->query("SELECT SUM(total) FROM orders WHERE status='delivered'")->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($adminTitle) ? htmlspecialchars($adminTitle).' — ' : '' ?><?= htmlspecialchars($siteName) ?> Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚙️</text></svg>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin.css">
  <?php if (isset($extraAdminCSS)): ?><link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/<?= $extraAdminCSS ?>"><?php endif; ?>
</head>
<body>
<div class="admin-layout">

<!-- ═══════════════ SIDEBAR ═══════════════ -->
<aside class="sidebar" id="adminSidebar">
  <!-- Logo -->
  <div class="sidebar-logo">
    <div class="sidebar-logo-icon">🍽️</div>
    <div>
      <div class="sidebar-logo-text"><?= htmlspecialchars($siteName) ?></div>
      <div class="sidebar-logo-sub">Admin Panel</div>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav">
    <!-- Main -->
    <div class="nav-group">
      <div class="nav-group-label">Main</div>
      <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-item <?= $adminPage==='dashboard'?'active':'' ?>">
        <span class="nav-icon">📊</span> Dashboard
      </a>
    </div>

    <!-- Catalog -->
    <div class="nav-group">
      <div class="nav-group-label">Catalog</div>
      <a href="<?= BASE_URL ?>/admin/pages/foods.php" class="nav-item <?= $adminPage==='foods'?'active':'' ?>">
        <span class="nav-icon">🍽️</span> Foods
      </a>
      <a href="<?= BASE_URL ?>/admin/pages/categories.php" class="nav-item <?= $adminPage==='categories'?'active':'' ?>">
        <span class="nav-icon">📂</span> Categories
      </a>
      <a href="<?= BASE_URL ?>/admin/pages/banners.php" class="nav-item <?= $adminPage==='banners'?'active':'' ?>">
        <span class="nav-icon">🖼️</span> Banners
      </a>
    </div>

    <!-- Orders -->
    <div class="nav-group">
      <div class="nav-group-label">Orders</div>
      <a href="<?= BASE_URL ?>/admin/pages/orders.php" class="nav-item <?= $adminPage==='orders'?'active':'' ?>">
        <span class="nav-icon">📦</span> All Orders
        <?php if ($unreadOrders > 0): ?>
        <span class="nav-badge"><?= $unreadOrders ?></span>
        <?php endif; ?>
      </a>
    </div>

    <!-- Customers -->
    <div class="nav-group">
      <div class="nav-group-label">Customers</div>
      <a href="<?= BASE_URL ?>/admin/pages/customers.php" class="nav-item <?= $adminPage==='customers'?'active':'' ?>">
        <span class="nav-icon">👥</span> Customers
      </a>
      <a href="<?= BASE_URL ?>/admin/pages/coupons.php" class="nav-item <?= $adminPage==='coupons'?'active':'' ?>">
        <span class="nav-icon">🏷️</span> Coupons
      </a>
    </div>

    <!-- Settings -->
    <div class="nav-group">
      <div class="nav-group-label">Settings</div>
      <a href="<?= BASE_URL ?>/admin/pages/settings.php" class="nav-item <?= $adminPage==='settings'?'active':'' ?>">
        <span class="nav-icon">⚙️</span> Website Settings
      </a>
      <a href="<?= BASE_URL ?>/admin/pages/admins.php" class="nav-item <?= $adminPage==='admins'?'active':'' ?>">
        <span class="nav-icon">🔐</span> Admins
      </a>
    </div>

    <!-- Site Link -->
    <div class="nav-group">
      <a href="<?= BASE_URL ?>/index.php" target="_blank" class="nav-item" style="color:var(--gold)">
        <span class="nav-icon">🌐</span> View Website
      </a>
      <a href="<?= BASE_URL ?>/admin/logout.php" class="nav-item" style="color:#ef4444">
        <span class="nav-icon">🚪</span> Logout
      </a>
    </div>
  </nav>

  <!-- Admin Info -->
  <div class="sidebar-footer">
    <div class="sidebar-admin">
      <div class="sidebar-admin-avatar">
        <?php if ($adminUser['avatar']): ?>
        <img src="<?= BASE_URL.'/'.$adminUser['avatar'] ?>" alt="">
        <?php else: ?>
        <?= strtoupper(substr($adminUser['name'],0,1)) ?>
        <?php endif; ?>
      </div>
      <div>
        <div class="sidebar-admin-name"><?= htmlspecialchars($adminUser['name']) ?></div>
        <div class="sidebar-admin-role"><?= ucfirst($adminUser['role']) ?></div>
      </div>
    </div>
  </div>
</aside>

<!-- Sidebar Overlay (mobile) -->
<div id="sidebarOverlay" onclick="closeSidebar()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);z-index:199"></div>

<!-- ═══════════════ TOP HEADER ═══════════════ -->
<header class="admin-header">
  <div class="header-left">
    <!-- Mobile hamburger -->
    <button class="btn btn-ghost btn-icon hamburger-admin" onclick="toggleSidebar()" aria-label="Toggle sidebar">☰</button>
    <!-- Page title -->
    <div>
      <div class="header-title"><?= isset($adminTitle) ? htmlspecialchars($adminTitle) : 'Dashboard' ?></div>
      <div class="header-breadcrumb">
        <a href="<?= BASE_URL ?>/admin/dashboard.php" style="color:var(--text-3)">Admin</a>
        <?php if (isset($adminTitle)): ?> › <?= htmlspecialchars($adminTitle) ?><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Search -->
  <div class="search-bar">
    <span style="color:var(--text-3)">🔍</span>
    <input type="text" placeholder="Quick search..." onkeydown="if(event.key==='Enter')adminSearch(this.value)">
  </div>

  <div class="header-actions">
    <!-- Theme -->
    <button class="header-btn" onclick="toggleAdminTheme()" title="Toggle theme">🌙</button>

    <!-- Notifications -->
    <a href="<?= BASE_URL ?>/admin/pages/orders.php?status=pending" class="header-btn" title="Pending orders">
      🔔
      <?php if ($unreadOrders > 0): ?><span class="header-notif-dot"></span><?php endif; ?>
    </a>

    <!-- View Site -->
    <a href="<?= BASE_URL ?>/index.php" target="_blank" class="header-btn" title="View website">🌐</a>

    <!-- Admin Avatar -->
    <a href="<?= BASE_URL ?>/admin/pages/admins.php" style="display:flex;align-items:center;gap:8px;background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius);padding:5px 12px 5px 7px;transition:var(--t)" onmouseover="this.style.borderColor='rgba(255,107,0,.3)'" onmouseout="this.style.borderColor='var(--glass-border)'">
      <div class="sidebar-admin-avatar" style="width:26px;height:26px;font-size:.72rem">
        <?php if ($adminUser['avatar']): ?><img src="<?= BASE_URL.'/'.$adminUser['avatar'] ?>" alt=""><?php else: ?><?= strtoupper(substr($adminUser['name'],0,1)) ?><?php endif; ?>
      </div>
      <span style="font-size:.8rem;font-weight:600"><?= htmlspecialchars(explode(' ',$adminUser['name'])[0]) ?></span>
    </a>
  </div>
</header>

<!-- ═══════════════ MAIN CONTENT ═══════════════ -->
<main class="admin-main">
<div class="page-content">
