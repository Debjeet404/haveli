# 🍽️ HAVELI — Premium Restaurant Management System

A complete, production-ready restaurant management website built with pure PHP, MySQL, HTML, CSS & JavaScript. No frameworks, no dependencies.

---

## ✨ Features

### Frontend
- 🏠 Home Page with animated hero, featured foods, category strips
- 🍽️ Menu Page with category filter, search, sort
- 📄 Food Detail Page with gallery, ingredients, reviews
- 🛒 Cart (glass sidebar, coupon, real-time total)
- 💳 Checkout with address management
- 🛵 Live Order Tracking with status steps
- 📦 Order History & Reorder
- 👤 User Profile with tabs (overview, orders, favorites, notifications, settings)
- 🏷️ Offers & Coupons Page
- 📞 Contact Page
- 🏰 About Restaurant Page
- 🔐 Login & Register
- 404 Page

### Admin Panel (`/admin`)
- 📊 Dashboard with live stats & charts
- 🍽️ Foods CRUD (images, gallery, pricing, toggles)
- 📂 Categories management
- 📦 Orders management (status update, detail view)
- 👥 Customers management
- 🏷️ Coupons management
- 🖼️ Banners management
- ⚙️ Full Website Settings (7 sections: general, homepage, orders, social, theme, SEO)
- 🔐 Admin accounts (role-based)

### Premium UI
- 🎨 Glassmorphism design, neon glow, animated gradients
- 🌙 Dark / Light mode toggle
- 🖱️ Custom cursor effects
- ✨ Scroll reveal animations
- 📱 Mobile-first responsive + bottom navigation
- 🔔 Toast notifications
- 🎉 Popup offer system

---

## 🚀 Installation

### Requirements
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Apache with `mod_rewrite`

### Step-by-Step Setup

#### 1. Clone / Download
```bash
# Place the haveli/ folder in your web server root
# e.g. /var/www/html/haveli  OR  C:/xampp/htdocs/haveli
```

#### 2. Create Database
```sql
-- In phpMyAdmin or MySQL CLI:
CREATE DATABASE haveli_db;
-- Then import the SQL file:
SOURCE /path/to/haveli/database.sql;
```

#### 3. Configure Database
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'haveli_db');
define('BASE_URL', 'http://localhost/haveli');  // ← update this!
```

#### 4. Set Permissions
```bash
chmod 755 uploads/
chmod 755 uploads/foods/
chmod 755 uploads/banners/
chmod 755 uploads/avatars/
chmod 755 uploads/logos/
```

#### 5. Enable mod_rewrite (Apache)
Make sure `AllowOverride All` is set in your Apache config.

#### 6. Done! Access:
- **Website:** `http://localhost/haveli/`
- **Admin Panel:** `http://localhost/haveli/admin/`

---

## 🔑 Default Credentials

### Admin Login
| Field    | Value               |
|----------|---------------------|
| Email    | admin@haveli.com    |
| Password | password            |
| Role     | Super Admin         |

> ⚠️ **Change these immediately after first login!**

### Sample Coupons
| Code       | Discount | Min Order |
|------------|----------|-----------|
| HAVELI10   | 10% off  | ₨500      |
| WELCOME20  | 20% off  | ₨800      |
| FLAT200    | ₨200 off | ₨1000     |
| NEWUSER50  | 50% off  | ₨600      |

---

## 📁 Folder Structure

```
haveli/
├── index.php              # Homepage
├── menu.php               # Menu listing
├── food.php               # Food detail
├── checkout.php           # Checkout
├── track.php              # Order tracking
├── offers.php             # Coupons page
├── about.php              # About page
├── contact.php            # Contact page
├── login.php              # User login
├── register.php           # User registration
├── profile.php            # User profile
├── logout.php             # Logout
├── 404.php                # Error page
├── database.sql           # Full database schema + sample data
├── .htaccess              # Apache config
│
├── includes/
│   ├── config.php         # DB config, helpers
│   ├── header.php         # Shared header + navbar + cart
│   └── footer.php         # Shared footer + scripts
│
├── assets/
│   ├── css/main.css       # All frontend styles
│   └── js/main.js         # Cart, cursor, animations, etc.
│
├── api/
│   ├── place_order.php    # Order placement
│   ├── coupon.php         # Coupon validation
│   ├── order_status.php   # Live order status
│   ├── favorite.php       # Favorites toggle
│   └── reorder.php        # Reorder past orders
│
├── uploads/               # User uploads (protected)
│   ├── foods/
│   ├── banners/
│   ├── avatars/
│   └── logos/
│
└── admin/
    ├── login.php          # Admin login
    ├── logout.php         # Admin logout
    ├── dashboard.php      # Main dashboard
    ├── includes/
    │   ├── header.php     # Admin header + sidebar
    │   └── footer.php     # Admin footer + JS
    ├── pages/
    │   ├── foods.php      # Food CRUD
    │   ├── categories.php # Category management
    │   ├── orders.php     # Order management
    │   ├── customers.php  # Customer management
    │   ├── coupons.php    # Coupon management
    │   ├── banners.php    # Banner management
    │   ├── settings.php   # Website settings
    │   └── admins.php     # Admin accounts
    ├── ajax/
    │   └── update_order_status.php
    └── assets/
        └── css/admin.css  # Admin dashboard styles
```

---

## 🛡️ Security Features

- ✅ Prepared statements (PDO) — SQL injection prevention
- ✅ `password_hash()` / `password_verify()` — Secure passwords
- ✅ CSRF tokens on all POST forms
- ✅ Session-based authentication (admin + user separate)
- ✅ File upload validation (type + size)
- ✅ PHP execution blocked in uploads directory
- ✅ `htmlspecialchars()` on all output — XSS prevention
- ✅ Directory listing disabled
- ✅ Security headers via `.htaccess`

---

## 🎨 Customization

### Change Colors
Go to **Admin → Settings → Theme** and update:
- Primary Color (default: `#FF6B00` orange)
- Secondary Color (default: `#FFD700` gold)

### Add Food Images
Upload food images via **Admin → Foods → Edit Food**
- Supported: JPG, PNG, WebP
- Max size: 5MB
- Recommended: 800×600px or square

### Add Real Food Photos
Replace placeholder emoji displays by uploading images in the admin panel for each food item.

---

## 📱 Mobile Support

- Fully responsive mobile-first design
- Bottom navigation bar on mobile
- Touch-friendly cart sidebar
- Optimized images with lazy loading

---

## 🔧 Production Checklist

- [ ] Change default admin credentials
- [ ] Set correct `BASE_URL` in `config.php`
- [ ] Enable HTTPS and uncomment redirect in `.htaccess`
- [ ] Set strong database password
- [ ] Configure Google Analytics ID in Settings → SEO
- [ ] Upload restaurant logo and favicon in Settings → General
- [ ] Add real food images via Admin → Foods
- [ ] Update social media links in Settings → Social Media
- [ ] Set delivery fee and tax in Settings → Orders
- [ ] Test all coupon codes
- [ ] Disable `display_errors` in PHP production config

---

## 💡 Tech Stack

| Layer      | Technology              |
|------------|-------------------------|
| Backend    | PHP 8.0+ (Pure)         |
| Database   | MySQL / MariaDB         |
| Frontend   | HTML5, CSS3, JavaScript |
| Design     | Glassmorphism, CSS Grid |
| Fonts      | Cinzel, Cormorant, Inter|
| No         | React, Vue, Laravel, Bootstrap, Tailwind |

---

Built with ♥ for Haveli Restaurant — Lahore, Pakistan
