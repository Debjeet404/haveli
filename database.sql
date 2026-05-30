-- ============================================================
-- HAVELI RESTAURANT MANAGEMENT SYSTEM
-- Complete Database Schema with Sample Data
-- ============================================================

CREATE DATABASE IF NOT EXISTS haveli_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE haveli_db;

-- ============================================================
-- ADMINS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('superadmin','admin','manager') DEFAULT 'admin',
    avatar VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin: admin@haveli.com / admin123
INSERT INTO admins (name, email, password, role) VALUES
('Haveli Admin', 'admin@haveli.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin');
-- Password: password (bcrypt) — change on first login

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    email_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- ADDRESSES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    label VARCHAR(50) DEFAULT 'Home',
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- CATEGORIES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) UNIQUE NOT NULL,
    icon VARCHAR(10) DEFAULT '🍽️',
    image VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO categories (name, slug, icon, sort_order) VALUES
('Starters', 'starters', '🥗', 1),
('Biryani', 'biryani', '🍚', 2),
('Curries', 'curries', '🍛', 3),
('Breads', 'breads', '🫓', 4),
('BBQ & Grill', 'bbq-grill', '🔥', 5),
('Seafood', 'seafood', '🦐', 6),
('Desserts', 'desserts', '🍮', 7),
('Beverages', 'beverages', '🥤', 8);

-- ============================================================
-- FOODS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS foods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(170) UNIQUE NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    discounted_price DECIMAL(10,2) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    gallery TEXT DEFAULT NULL,
    ingredients TEXT DEFAULT NULL,
    spicy_level ENUM('mild','medium','hot','extra_hot') DEFAULT 'mild',
    prep_time INT DEFAULT 20,
    rating DECIMAL(3,2) DEFAULT 4.50,
    rating_count INT DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    is_popular TINYINT(1) DEFAULT 0,
    is_available TINYINT(1) DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    tags VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO foods (category_id, name, slug, description, price, discounted_price, spicy_level, prep_time, rating, rating_count, is_featured, is_popular, tags) VALUES
(1, 'Seekh Kebab Platter', 'seekh-kebab-platter', 'Minced lamb seasoned with aromatic spices, shaped on skewers and grilled to perfection over charcoal flames.', 850.00, 720.00, 'medium', 25, 4.8, 142, 1, 1, 'kebab,starter,grill'),
(1, 'Shammi Kebab', 'shammi-kebab', 'Tender minced lamb patties infused with lentils and spices, shallow fried to golden perfection.', 650.00, NULL, 'mild', 20, 4.6, 89, 0, 1, 'kebab,starter'),
(1, 'Gilafi Seekh', 'gilafi-seekh', 'Seekh kebab wrapped in colorful capsicum and onion, a visual and culinary delight.', 780.00, 650.00, 'hot', 30, 4.7, 76, 1, 0, 'kebab,starter,special'),
(2, 'Dum Gosht Biryani', 'dum-gosht-biryani', 'Slow-cooked mutton biryani sealed with dough, infused with saffron and whole spices. The crown jewel of Haveli.', 1200.00, 980.00, 'medium', 45, 4.9, 312, 1, 1, 'biryani,special,signature'),
(2, 'Chicken Biryani', 'chicken-biryani', 'Fragrant basmati rice layered with spiced chicken, fried onions, and fresh mint leaves.', 850.00, NULL, 'medium', 35, 4.7, 256, 0, 1, 'biryani,chicken'),
(2, 'Vegetable Biryani', 'vegetable-biryani', 'A celebration of seasonal vegetables cooked with premium basmati and whole spices.', 650.00, 550.00, 'mild', 30, 4.5, 98, 0, 0, 'biryani,vegetarian'),
(3, 'Nihari', 'nihari', 'The legendary slow-cooked beef shank stew, simmered overnight with 22 spices. Served with nalli.', 950.00, NULL, 'hot', 15, 4.9, 198, 1, 1, 'curry,special,slow-cook'),
(3, 'Lamb Rogan Josh', 'lamb-rogan-josh', 'Kashmiri-style tender lamb in a rich red sauce of Kashmiri chilies and aromatic spices.', 1050.00, 900.00, 'hot', 20, 4.8, 145, 1, 0, 'curry,lamb,kashmiri'),
(3, 'Dal Makhani', 'dal-makhani', 'Black lentils simmered overnight in a tomato-butter sauce with cream. A vegetarian masterpiece.', 550.00, NULL, 'mild', 15, 4.6, 167, 0, 1, 'curry,vegetarian,dal'),
(4, 'Tandoori Roti', 'tandoori-roti', 'Fresh whole wheat bread baked in the clay tandoor, brushed with pure ghee.', 80.00, NULL, 'mild', 10, 4.5, 230, 0, 1, 'bread,tandoor'),
(4, 'Garlic Naan', 'garlic-naan', 'Fluffy leavened bread topped with roasted garlic and cilantro, baked in tandoor.', 120.00, NULL, 'mild', 12, 4.7, 189, 0, 1, 'bread,naan,tandoor'),
(4, 'Sheermal', 'sheermal', 'Saffron-flavored sweet flatbread, a royal Mughlai tradition. Perfect with kebabs.', 150.00, NULL, 'mild', 20, 4.8, 87, 1, 0, 'bread,special,mughlai'),
(5, 'Mixed BBQ Platter', 'mixed-bbq-platter', 'A grand platter of seekh kebab, chicken tikka, boti kebab, and reshmi kebab. For two.', 1850.00, 1550.00, 'medium', 35, 4.9, 203, 1, 1, 'bbq,platter,special'),
(5, 'Chicken Tikka', 'chicken-tikka', 'Boneless chicken marinated in yogurt and spices, grilled in the tandoor. Classic perfection.', 750.00, NULL, 'medium', 25, 4.7, 278, 0, 1, 'bbq,chicken,tandoor'),
(6, 'Jhinga Masala', 'jhinga-masala', 'Jumbo prawns cooked in a rich tomato-onion masala with coastal spices.', 1350.00, 1150.00, 'hot', 25, 4.8, 134, 1, 1, 'seafood,prawn,special'),
(7, 'Gulab Jamun', 'gulab-jamun', 'Soft milk-solid dumplings soaked in rose-flavored sugar syrup. Served warm.', 280.00, NULL, 'mild', 10, 4.8, 312, 0, 1, 'dessert,sweet'),
(7, 'Kheer', 'kheer', 'Slow-cooked rice pudding with cardamom, saffron, and roasted almonds. Chilled to perfection.', 320.00, 280.00, 'mild', 10, 4.7, 189, 1, 0, 'dessert,sweet,traditional'),
(8, 'Kashmiri Kahwa', 'kashmiri-kahwa', 'Traditional green tea brewed with saffron, cardamom, cinnamon, and almond slivers.', 250.00, NULL, 'mild', 10, 4.9, 156, 1, 1, 'beverage,tea,special'),
(8, 'Rose Sharbat', 'rose-sharbat', 'Chilled rose-flavored drink with basil seeds and a hint of lemon. Refreshing.', 180.00, NULL, 'mild', 5, 4.6, 123, 0, 1, 'beverage,cold,sweet');

-- ============================================================
-- ORDERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT DEFAULT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    delivery_address TEXT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(10,2) DEFAULT 0.00,
    tax DECIMAL(10,2) DEFAULT 0.00,
    discount DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    coupon_code VARCHAR(50) DEFAULT NULL,
    payment_method ENUM('cod','online') DEFAULT 'cod',
    payment_status ENUM('pending','paid','failed') DEFAULT 'pending',
    status ENUM('pending','accepted','preparing','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
    notes TEXT DEFAULT NULL,
    estimated_delivery DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- ORDER ITEMS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    food_id INT NOT NULL,
    food_name VARCHAR(150) NOT NULL,
    food_image VARCHAR(255) DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- COUPONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('percentage','fixed') DEFAULT 'percentage',
    value DECIMAL(10,2) NOT NULL,
    min_order DECIMAL(10,2) DEFAULT 0.00,
    max_discount DECIMAL(10,2) DEFAULT NULL,
    uses_limit INT DEFAULT NULL,
    uses_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    expires_at DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO coupons (code, type, value, min_order, max_discount, uses_limit) VALUES
('HAVELI10', 'percentage', 10.00, 500.00, 150.00, 100),
('WELCOME20', 'percentage', 20.00, 800.00, 300.00, 50),
('FLAT200', 'fixed', 200.00, 1000.00, 200.00, 30),
('NEWUSER50', 'percentage', 50.00, 600.00, 250.00, 1);

-- ============================================================
-- SETTINGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT DEFAULT NULL,
    setting_group VARCHAR(50) DEFAULT 'general',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value, setting_group) VALUES
('site_name', 'Haveli', 'general'),
('site_tagline', 'Royal Flavors. Timeless Traditions.', 'general'),
('site_description', 'Experience the grandeur of Mughlai cuisine at Haveli — where every dish tells a story of royal heritage, slow-cooked perfection, and culinary artistry passed down through generations.', 'general'),
('site_email', 'info@haveli.com', 'general'),
('site_phone', '+92 300 1234567', 'general'),
('site_phone2', '+92 321 7654321', 'general'),
('site_address', '14 Royal Garden Road, DHA Phase 5, Lahore, Pakistan', 'general'),
('site_currency', '₨', 'general'),
('delivery_fee', '150', 'orders'),
('free_delivery_above', '2000', 'orders'),
('tax_percentage', '5', 'orders'),
('min_order_amount', '500', 'orders'),
('estimated_delivery_time', '30-45', 'orders'),
('hero_title', 'Where Royalty Meets', 'homepage'),
('hero_title_highlight', 'Flavor', 'homepage'),
('hero_subtitle', 'Experience the grandeur of Mughlai cuisine — slow-cooked perfection, royal spices, and timeless traditions served in the heart of Lahore.', 'homepage'),
('hero_cta_primary', 'Explore Menu', 'homepage'),
('hero_cta_secondary', 'Our Story', 'homepage'),
('about_title', 'A Legacy of Royal Cuisine', 'about'),
('about_text', 'Founded in 1985, Haveli was born from a dream to bring the grandeur of Mughal royal kitchens to the people of Lahore. Our chefs are custodians of centuries-old recipes, using secret spice blends passed down through generations.', 'about'),
('facebook_url', 'https://facebook.com/haveli', 'social'),
('instagram_url', 'https://instagram.com/haveli', 'social'),
('twitter_url', 'https://twitter.com/haveli', 'social'),
('whatsapp_number', '+923001234567', 'social'),
('footer_text', '© 2024 Haveli. All rights reserved. Crafted with ♥ in Lahore.', 'footer'),
('announcement_text', '🎉 Free delivery on orders above ₨2000! Use code HAVELI10 for 10% off.', 'general'),
('announcement_active', '1', 'general'),
('popup_active', '0', 'general'),
('popup_title', 'Welcome to Haveli!', 'general'),
('popup_text', 'Get 20% off your first order. Use code WELCOME20', 'general'),
('dark_mode_default', '1', 'general'),
('maintenance_mode', '0', 'general'),
('logo_path', '', 'general'),
('favicon_path', '', 'general'),
('primary_color', '#FF6B00', 'theme'),
('secondary_color', '#FFD700', 'theme'),
('restaurant_hours', 'Mon-Sun: 12:00 PM - 12:00 AM', 'general'),
('maps_url', 'https://maps.google.com', 'general'),
('meta_keywords', 'haveli restaurant, mughlai food, biryani lahore, kebabs, nihari', 'seo'),
('google_analytics', '', 'seo');

-- ============================================================
-- BANNERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    subtitle VARCHAR(255) DEFAULT NULL,
    image VARCHAR(255) NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    badge VARCHAR(50) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- NOTIFICATIONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('order','system','promo','info') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- FAVORITES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    food_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_fav (user_id, food_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- REVIEWS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    food_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review TEXT DEFAULT NULL,
    is_approved TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- INDEXES FOR PERFORMANCE
-- ============================================================
CREATE INDEX idx_foods_category ON foods(category_id);
CREATE INDEX idx_foods_featured ON foods(is_featured);
CREATE INDEX idx_foods_popular ON foods(is_popular);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created ON orders(created_at);
CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_favorites_user ON favorites(user_id);
