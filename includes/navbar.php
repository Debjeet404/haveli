<?php
require_once __DIR__ . '/../config/functions.php';
$currentPage = basename($_SERVER['PHP_SELF']);
$cartCount   = getCartCount();
?>
<header class="site-header" id="siteHeader">
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-left">
                <a href="tel:<?= getSetting('site_phone') ?>">
                    <i class="fas fa-phone-alt"></i> <?= getSetting('site_phone') ?>
                </a>
                <span class="divider">|</span>
                <a href="mailto:<?= getSetting('site_email') ?>">
                    <i class="fas fa-envelope"></i> <?= getSetting('site_email') ?>
                </a>
            </div>
            <div class="top-bar-right">
                <?php
                $isOpen = getSetting('is_open', '1');
                $openTime  = getSetting('restaurant_open', '10:00');
                $closeTime = getSetting('restaurant_close', '23:00');
                ?>
                <span class="restaurant-status <?= $isOpen ? 'open' : 'closed' ?>">
                    <i class="fas fa-circle"></i>
                    <?= $isOpen ? "Open · Till $closeTime" : "Currently Closed" ?>
                </span>
                <div class="social-links">
                    <a href="<?= getSetting('facebook_url') ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <a href="<?= getSetting('instagram_url') ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                    <a href="https://wa.me/<?= getSetting('whatsapp_number') ?>" target="_blank"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navbar -->
    <nav class="navbar" id="mainNav">
        <div class="container">
            <!-- Logo -->
            <a href="/" class="navbar-brand">
                <div class="brand-icon">
                    <i class="fas fa-dharmachakra"></i>
                </div>
                <div class="brand-text">
                    <span class="brand-name"><?= getSetting('site_name', 'Haveli') ?></span>
                    <span class="brand-tagline"><?= getSetting('site_tagline') ?></span>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <ul class="nav-links" id="navLinks">
                <li><a href="/" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a></li>
                <li><a href="/menu.php" class="<?= $currentPage === 'menu.php' ? 'active' : '' ?>">Menu</a></li>
                <li><a href="/offers.php" class="<?= $currentPage === 'offers.php' ? 'active' : '' ?>">
                    <span class="badge-new">Offers</span>
                </a></li>
                <li><a href="/about.php" class="<?= $currentPage === 'about.php' ? 'active' : '' ?>">About</a></li>
                <li><a href="/contact.php" class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>">Contact</a></li>
            </ul>

            <!-- Nav Actions -->
            <div class="nav-actions">
                <!-- Search -->
                <button class="nav-icon-btn" id="searchToggle" aria-label="Search">
                    <i class="fas fa-search"></i>
                </button>

                <!-- Cart -->
                <a href="/cart.php" class="nav-icon-btn cart-btn" aria-label="Cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-badge" id="cartBadge" <?= $cartCount === 0 ? 'style="display:none"' : '' ?>>
                        <?= $cartCount ?>
                    </span>
                </a>

                <!-- User Menu -->
                <?php if (isLoggedIn()): ?>
                    <div class="user-dropdown">
                        <button class="nav-icon-btn user-btn" id="userMenuToggle">
                            <i class="fas fa-user-circle"></i>
                            <span class="user-name"><?= explode(' ', $_SESSION['user_name'])[0] ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu" id="userDropdown">
                            <div class="dropdown-header">
                                <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                                <small><?= htmlspecialchars($_SESSION['user_email']) ?></small>
                            </div>
                            <a href="/profile.php"><i class="fas fa-user"></i> My Profile</a>
                            <a href="/order-history.php"><i class="fas fa-receipt"></i> My Orders</a>
                            <?php if (isAdmin()): ?>
                                <a href="/admin/"><i class="fas fa-cog"></i> Admin Panel</a>
                            <?php endif; ?>
                            <hr>
                            <a href="/api/auth.php?action=logout" class="text-danger">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/login.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                <?php endif; ?>

                <!-- Mobile Menu Toggle -->
                <button class="hamburger" id="hamburger" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Search Bar -->
    <div class="search-bar-overlay" id="searchBar">
        <div class="container">
            <form action="/menu.php" method="GET" class="search-form">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search for dishes, categories..." 
                       autocomplete="off" id="searchInput">
                <div class="search-suggestions" id="searchSuggestions"></div>
                <button type="button" class="search-close" id="searchClose">
                    <i class="fas fa-times"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Mobile Nav Overlay -->
    <div class="mobile-overlay" id="mobileOverlay"></div>
</header>