<?php
/**
 * HAVELI — Shared Frontend Header
 * Include at top of every page: require_once 'includes/header.php';
 */
require_once __DIR__ . '/config.php';

$siteName     = getSetting('site_name', 'Haveli');
$siteTagline  = getSetting('site_tagline', 'Royal Flavors');
$logoPath     = getSetting('logo_path', '');
$faviconPath  = getSetting('favicon_path', '');
$currency     = getSetting('site_currency', '₨');
$deliveryFee  = getSetting('delivery_fee', '150');
$freeAbove    = getSetting('free_delivery_above', '2000');
$taxRate      = getSetting('tax_percentage', '5');
$annText      = getSetting('announcement_text', '');
$annActive    = getSetting('announcement_active', '0');
$popupActive  = getSetting('popup_active', '0');
$popupTitle   = getSetting('popup_title', '');
$popupText    = getSetting('popup_text', '');
$darkDefault  = getSetting('dark_mode_default', '1');
$primaryColor = getSetting('primary_color', '#FF6B00');
$secondaryColor = getSetting('secondary_color', '#FFD700');
$cartCount    = 0;
$loggedIn     = isLoggedIn();
$currentPage  = basename($_SERVER['PHP_SELF'], '.php');
$metaDesc     = getSetting('site_description', '');
$metaKeys     = getSetting('meta_keywords', '');
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-default-theme="<?= $darkDefault == '1' ? 'dark' : 'light' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?><?= htmlspecialchars($siteName) ?></title>
  <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta name="keywords" content="<?= htmlspecialchars($metaKeys) ?>">
  <meta property="og:title" content="<?= htmlspecialchars($siteName) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta name="theme-color" content="<?= htmlspecialchars($primaryColor) ?>">
  <?php if ($faviconPath): ?>
  <link rel="icon" href="<?= BASE_URL . '/' . $faviconPath ?>">
  <?php else: ?>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🍽️</text></svg>">
  <?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <?php if (isset($extraCSS)): ?>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= $extraCSS ?>">
  <?php endif; ?>
  <style>
    :root {
      --primary: <?= htmlspecialchars($primaryColor) ?>;
      --gold: <?= htmlspecialchars($secondaryColor) ?>;
    }
  </style>
</head>
<body
  data-currency="<?= htmlspecialchars($currency) ?>"
  data-delivery-fee="<?= htmlspecialchars($deliveryFee) ?>"
  data-free-delivery="<?= htmlspecialchars($freeAbove) ?>"
  data-tax-rate="<?= htmlspecialchars($taxRate) ?>"
  <?= $loggedIn ? 'data-logged-in="1"' : '' ?>
>

<!-- Page Loader -->
<div id="pageLoader" class="page-loader">
  <div class="loader-logo"><?= htmlspecialchars($siteName) ?></div>
  <div class="loader-bar"><div class="loader-bar-fill"></div></div>
</div>

<!-- Custom Cursor -->
<div class="cursor"></div>
<div class="cursor-follower"></div>

<!-- Announcement Bar -->
<?php if ($annActive && $annText && !isset($_SESSION['ann_closed'])): ?>
<div id="announcementBar" class="announcement-bar">
  <?= htmlspecialchars($annText) ?>
  <button class="close-ann" id="closeAnnouncement" aria-label="Close">×</button>
</div>
<?php endif; ?>

<!-- NAVBAR -->
<nav class="navbar <?= ($annActive && $annText) ? 'has-announcement' : '' ?>" id="mainNav" role="navigation" aria-label="Main navigation">
  <div class="nav-inner">
    <!-- Logo -->
    <a href="<?= BASE_URL ?>/index.php" class="nav-logo" aria-label="<?= htmlspecialchars($siteName) ?> Home">
      <?php if ($logoPath): ?>
      <img src="<?= BASE_URL . '/' . $logoPath ?>" alt="<?= htmlspecialchars($siteName) ?>" height="44" width="44" style="border-radius:12px;object-fit:cover;">
      <?php else: ?>
      <div class="nav-logo-icon" aria-hidden="true">🍽️</div>
      <?php endif; ?>
      <span class="nav-logo-text"><?= htmlspecialchars($siteName) ?></span>
    </a>

    <!-- Desktop Navigation -->
    <ul class="nav-links" role="list">
      <li><a href="<?= BASE_URL ?>/index.php" class="<?= $currentPage === 'index' ? 'active' : '' ?>">Home</a></li>
      <li><a href="<?= BASE_URL ?>/menu.php" class="<?= $currentPage === 'menu' ? 'active' : '' ?>">Menu</a></li>
      <li><a href="<?= BASE_URL ?>/offers.php" class="<?= $currentPage === 'offers' ? 'active' : '' ?>">Offers</a></li>
      <li><a href="<?= BASE_URL ?>/about.php" class="<?= $currentPage === 'about' ? 'active' : '' ?>">About</a></li>
      <li><a href="<?= BASE_URL ?>/contact.php" class="<?= $currentPage === 'contact' ? 'active' : '' ?>">Contact</a></li>
      <?php if ($loggedIn): ?>
      <li><a href="<?= BASE_URL ?>/profile.php" class="<?= $currentPage === 'profile' ? 'active' : '' ?>">Profile</a></li>
      <li><a href="<?= BASE_URL ?>/orders.php" class="<?= $currentPage === 'orders' ? 'active' : '' ?>">Orders</a></li>
      <?php endif; ?>
    </ul>

    <!-- Nav Actions -->
    <div class="nav-actions">
      <!-- Theme Toggle -->
      <button class="nav-btn theme-btn" aria-label="Toggle theme" title="Toggle dark/light mode">
        <span class="theme-icon">☀️</span>
      </button>

      <!-- Search -->
      <a href="<?= BASE_URL ?>/menu.php" class="nav-btn" aria-label="Search menu">🔍</a>

      <!-- Cart -->
      <button class="nav-btn" data-open-cart aria-label="Open cart">
        🛒
        <span class="badge cart-badge" style="display:none">0</span>
      </button>

      <!-- Auth -->
      <?php if ($loggedIn): ?>
      <a href="<?= BASE_URL ?>/profile.php" class="btn btn-ghost btn-sm">
        👤 <?= htmlspecialchars($_SESSION['user_name'] ?? 'Account') ?>
      </a>
      <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline btn-sm">Logout</a>
      <?php else: ?>
      <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline btn-sm">Login</a>
      <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-sm btn-cta-nav">Sign Up</a>
      <?php endif; ?>

      <!-- Hamburger -->
      <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</nav>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu" role="dialog" aria-label="Mobile navigation">
  <ul class="mobile-menu-links">
    <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
    <li><a href="<?= BASE_URL ?>/menu.php">Menu</a></li>
    <li><a href="<?= BASE_URL ?>/offers.php">Offers & Coupons</a></li>
    <li><a href="<?= BASE_URL ?>/about.php">About</a></li>
    <li><a href="<?= BASE_URL ?>/contact.php">Contact</a></li>
    <?php if ($loggedIn): ?>
    <li><a href="<?= BASE_URL ?>/profile.php">My Profile</a></li>
    <li><a href="<?= BASE_URL ?>/orders.php">My Orders</a></li>
    <li><a href="<?= BASE_URL ?>/logout.php">Logout</a></li>
    <?php else: ?>
    <li><a href="<?= BASE_URL ?>/login.php">Login</a></li>
    <li><a href="<?= BASE_URL ?>/register.php">Sign Up</a></li>
    <?php endif; ?>
  </ul>
</div>

<!-- CART SIDEBAR -->
<div class="cart-overlay" id="cartOverlay" aria-hidden="true"></div>
<aside class="cart-sidebar" id="cartSidebar" role="complementary" aria-label="Shopping cart">
  <div class="cart-header">
    <h2 class="cart-title">🛒 Your Cart</h2>
    <button class="cart-close" id="closeCart" aria-label="Close cart">×</button>
  </div>

  <div id="cartEmpty" class="cart-empty" style="display:none">
    <div class="cart-empty-icon">🛒</div>
    <p class="cart-empty-text">Your cart is empty</p>
    <a href="<?= BASE_URL ?>/menu.php" class="btn btn-primary btn-sm" onclick="closeCart()">Explore Menu</a>
  </div>

  <div class="cart-items" id="cartItems"></div>

  <div class="cart-footer" id="cartFooter" style="display:none">
    <!-- Coupon -->
    <div class="cart-coupon">
      <input type="text" id="couponInput" class="form-input" placeholder="Coupon code" style="border-radius:8px;padding:9px 12px">
      <button id="applyCouponBtn" class="btn btn-ghost btn-sm" onclick="applyCoupon()">Apply</button>
    </div>

    <!-- Summary -->
    <div class="cart-summary-row"><span>Subtotal</span><span id="cartSubtotal">₨0</span></div>
    <div class="cart-summary-row"><span>Delivery</span><span id="cartDelivery">₨0</span></div>
    <div class="cart-summary-row"><span>Tax</span><span id="cartTax">₨0</span></div>
    <div class="cart-summary-row" id="discountRow"><span>Discount</span><span id="cartDiscount">₨0</span></div>
    <div class="cart-total-row"><span>Total</span><span id="cartTotal">₨0</span></div>

    <a href="<?= BASE_URL ?>/checkout.php" class="btn btn-primary btn-full">
      Proceed to Checkout →
    </a>
    <button class="btn btn-ghost btn-sm btn-full" onclick="closeCart()" style="margin-top:8px">Continue Shopping</button>
  </div>
</aside>

<!-- Sticky Cart Button (mobile) -->
<button class="sticky-cart" data-open-cart aria-label="Open cart" id="stickyCart">
  🛒
  <span class="badge cart-badge" style="display:none">0</span>
</button>

<!-- Popup Offer -->
<?php if ($popupActive && $popupTitle): ?>
<div class="offer-popup-overlay" id="offerPopup" role="dialog" aria-modal="true" aria-label="Special offer">
  <div class="offer-popup" style="position:relative">
    <button class="offer-popup-close" onclick="closePopup()" aria-label="Close">×</button>
    <span class="offer-popup-emoji">🎉</span>
    <h2 class="offer-popup-title"><?= htmlspecialchars($popupTitle) ?></h2>
    <p class="offer-popup-text"><?= htmlspecialchars($popupText) ?></p>
    <div class="offer-popup-code" id="popupCode">WELCOME20</div>
    <button class="btn btn-gold btn-full" onclick="copyCoupon('WELCOME20'); closePopup()">Copy & Start Ordering</button>
  </div>
</div>
<?php endif; ?>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Mobile Bottom Navigation -->
<nav class="mobile-nav" role="navigation" aria-label="Mobile navigation">
  <div class="mobile-nav-inner">
    <a href="<?= BASE_URL ?>/index.php" class="mobile-nav-btn" aria-label="Home">
      <span class="nav-icon">🏠</span>Home
    </a>
    <a href="<?= BASE_URL ?>/menu.php" class="mobile-nav-btn" aria-label="Menu">
      <span class="nav-icon">🍽️</span>Menu
    </a>
    <button class="mobile-nav-btn" data-open-cart aria-label="Cart" style="position:relative">
      <span class="nav-icon">🛒</span>Cart
      <span class="badge cart-badge" style="display:none">0</span>
    </button>
    <a href="<?= BASE_URL ?>/offers.php" class="mobile-nav-btn" aria-label="Offers">
      <span class="nav-icon">🏷️</span>Offers
    </a>
    <a href="<?= $loggedIn ? BASE_URL.'/profile.php' : BASE_URL.'/login.php' ?>" class="mobile-nav-btn" aria-label="Account">
      <span class="nav-icon">👤</span><?= $loggedIn ? 'Account' : 'Login' ?>
    </a>
  </div>
</nav>

<!-- Main Content Starts -->
<main class="page-transition" id="mainContent">
