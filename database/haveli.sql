-- database/haveli.sql
-- =============================================
-- HAVELI RESTAURANT DATABASE
-- =============================================

CREATE DATABASE IF NOT EXISTS haveli_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE haveli_db;

-- =============================================
-- ADMINS TABLE
-- =============================================
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin','admin','manager') DEFAULT 'admin',
    profile_photo VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- USERS TABLE
-- =============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    email_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- ADDRESSES TABLE
-- =============================================
CREATE TABLE addresses (
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
);

-- =============================================
-- CATEGORIES TABLE
-- =============================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) UNIQUE NOT NULL,
    description TEXT DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    icon VARCHAR(100) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- FOODS TABLE
-- =============================================
CREATE TABLE foods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(220) UNIQUE NOT NULL,
    description TEXT DEFAULT NULL,
    ingredients TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    discounted_price DECIMAL(10,2) DEFAULT NULL,
    main_image VARCHAR(255) DEFAULT NULL,
    spicy_level ENUM('none','mild','medium','hot','extra_hot') DEFAULT 'none',
    prep_time INT DEFAULT 30 COMMENT 'in minutes',
    calories INT DEFAULT NULL,
    rating DECIMAL(3,2) DEFAULT 4.50,
    total_reviews INT DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    is_popular TINYINT(1) DEFAULT 0,
    is_vegetarian TINYINT(1) DEFAULT 0,
    is_available TINYINT(1) DEFAULT 1,
    stock_status ENUM('in_stock','out_of_stock','limited') DEFAULT 'in_stock',
    tags VARCHAR(500) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    total_orders INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- =============================================
-- FOOD IMAGES TABLE
-- =============================================
CREATE TABLE food_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    food_id INT NOT NULL,
    image VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
);

-- =============================================
-- COUPONS TABLE
-- =============================================
CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    discount_type ENUM('percentage','fixed') DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    max_discount DECIMAL(10,2) DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    valid_from DATE NOT NULL,
    valid_until DATE NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- ORDERS TABLE
-- =============================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT DEFAULT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    delivery_address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    delivery_charge DECIMAL(10,2) DEFAULT 0,
    tax DECIMAL(10,2) DEFAULT 0,
    discount DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    coupon_code VARCHAR(50) DEFAULT NULL,
    payment_method ENUM('cod','card','online') DEFAULT 'cod',
    payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    status ENUM('pending','accepted','preparing','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
    notes TEXT DEFAULT NULL,
    estimated_delivery DATETIME DEFAULT NULL,
    delivered_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- ORDER ITEMS TABLE
-- =============================================
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    food_id INT NOT NULL,
    food_name VARCHAR(200) NOT NULL,
    food_image VARCHAR(255) DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
);

-- =============================================
-- SETTINGS TABLE
-- =============================================
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT DEFAULT NULL,
    setting_group VARCHAR(50) DEFAULT 'general',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- BANNERS TABLE
-- =============================================
CREATE TABLE banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    subtitle VARCHAR(300) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    image VARCHAR(255) NOT NULL,
    button_text VARCHAR(100) DEFAULT NULL,
    button_link VARCHAR(255) DEFAULT NULL,
    banner_type ENUM('hero','popup','promotional','slider') DEFAULT 'slider',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- NOTIFICATIONS TABLE
-- =============================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('order','promotion','system','alert') DEFAULT 'system',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- FAVORITES TABLE
-- =============================================
CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    food_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorite (user_id, food_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
);

-- =============================================
-- REVIEWS TABLE
-- =============================================
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    food_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review TEXT DEFAULT NULL,
    is_approved TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
);

-- =============================================
-- INSERT DEFAULT ADMIN
-- Password: Admin@123
-- =============================================
INSERT INTO admins (name, email, password, role) VALUES
('Super Admin', 'admin@haveli.com', '$2y$12$LQv3c1yqBWVHxkd0LQ1Tc.6qNm8eBsn6B0g8L3fBjHZE.P3X1WBJG', 'super_admin');

-- =============================================
-- INSERT DEFAULT SETTINGS
-- =============================================
INSERT INTO settings (setting_key, setting_value, setting_group) VALUES
('site_name', 'Haveli', 'general'),
('site_tagline', 'Authentic Flavors, Royal Experience', 'general'),
('site_description', 'Experience the royal taste of authentic Pakistani & Indian cuisine at Haveli. Every dish tells a story of tradition, culture, and passion.', 'general'),
('site_logo', '', 'general'),
('site_favicon', '', 'general'),
('hero_title', 'Royal Flavors', 'homepage'),
('hero_subtitle', 'Crafted for Kings', 'homepage'),
('hero_description', 'Experience the authentic taste of Pakistan & India. Every bite is a journey through rich cultures and traditions.', 'homepage'),
('hero_image', '', 'homepage'),
('hero_button_text', 'Explore Our Menu', 'homepage'),
('hero_button_link', '/menu.php', 'homepage'),
('about_title', 'Our Story', 'homepage'),
('about_text', 'Haveli is more than a restaurant. It is a celebration of culture, heritage, and the love for authentic cooking. Founded with a passion for bringing royal flavors to every table.', 'homepage'),
('contact_phone', '+92 300 1234567', 'contact'),
('contact_email', 'info@haveli.com', 'contact'),
('contact_address', '123 Food Street, Lahore, Pakistan', 'contact'),
('contact_hours', 'Mon-Sun: 11:00 AM - 11:00 PM', 'contact'),
('facebook_url', 'https://facebook.com/haveli', 'social'),
('instagram_url', 'https://instagram.com/haveli', 'social'),
('twitter_url', 'https://twitter.com/haveli', 'social'),
('whatsapp_number', '+92 300 1234567', 'social'),
('delivery_charge', '150', 'ordering'),
('free_delivery_min', '1000', 'ordering'),
('tax_percentage', '5', 'ordering'),
('currency_symbol', 'Rs', 'ordering'),
('min_order_amount', '300', 'ordering'),
('dark_mode_default', '1', 'appearance'),
('primary_color', '#ff6b00', 'appearance'),
('accent_color', '#ffd700', 'appearance'),
('show_popup', '0', 'homepage'),
('popup_title', 'Special Offer!', 'homepage'),
('popup_text', 'Get 20% off on your first order', 'homepage'),
('announcement_text', '', 'homepage'),
('announcement_active', '0', 'homepage'),
('footer_text', 'Haveli Restaurant - Where every meal is a royal experience.', 'footer'),
('google_maps_embed', '', 'contact'),
('whatsapp_order', '1', 'ordering'),
('max_order_quantity', '20', 'ordering');

-- =============================================
-- INSERT CATEGORIES
-- =============================================
INSERT INTO categories (name, slug, description, icon, sort_order) VALUES
('Starters', 'starters', 'Begin your royal journey with our exquisite appetizers', '🥗', 1),
('Biryani', 'biryani', 'Aromatic rice dishes cooked with premium spices', '🍚', 2),
('Karahi & Curries', 'karahi-curries', 'Rich and flavorful traditional curries', '🍛', 3),
('BBQ & Grills', 'bbq-grills', 'Freshly grilled meats with authentic marinades', '🔥', 4),
('Breads', 'breads', 'Freshly baked traditional breads from the tandoor', '🫓', 5),
('Desserts', 'desserts', 'Sweet endings to your royal feast', '🍮', 6),
('Beverages', 'beverages', 'Refreshing drinks and traditional beverages', '🥤', 7),
('Special Deals', 'special-deals', 'Combo meals and special packages for families', '⭐', 8);

-- =============================================
-- INSERT FOODS
-- =============================================
INSERT INTO foods (category_id, name, slug, description, ingredients, price, discounted_price, spicy_level, prep_time, calories, rating, is_featured, is_popular, is_vegetarian, tags) VALUES
(1, 'Chicken Tikka', 'chicken-tikka', 'Tender chicken marinated in yogurt and aromatic spices, grilled to perfection in our traditional tandoor.', 'Chicken, Yogurt, Ginger-Garlic Paste, Red Chili, Cumin, Coriander, Lemon', 650.00, 550.00, 'medium', 25, 320, 4.80, 1, 1, 0, 'popular,grilled,starter'),
(1, 'Seekh Kebab', 'seekh-kebab', 'Minced meat skewers seasoned with fresh herbs and spices, grilled over charcoal.', 'Minced Beef, Onion, Green Chilies, Coriander, Cumin, Garam Masala', 580.00, NULL, 'hot', 20, 280, 4.70, 0, 1, 0, 'bbq,grilled,starter'),
(1, 'Samosa (4 pcs)', 'samosa', 'Crispy pastry filled with spiced potatoes and peas. A beloved Pakistani classic.', 'Flour, Potatoes, Peas, Cumin, Coriander, Green Chilies', 180.00, NULL, 'mild', 15, 220, 4.60, 0, 1, 1, 'vegetarian,crispy,snack'),
(1, 'Dahi Bhalle', 'dahi-bhalle', 'Soft lentil dumplings in creamy yogurt topped with tangy tamarind chutney.', 'Lentils, Yogurt, Tamarind, Cumin, Red Chili, Mint', 280.00, NULL, 'mild', 10, 180, 4.50, 0, 0, 1, 'vegetarian,chaat,starter'),
(2, 'Chicken Biryani', 'chicken-biryani', 'Aromatic basmati rice layered with succulent chicken, saffron, and fried onions. A timeless classic.', 'Basmati Rice, Chicken, Saffron, Fried Onions, Whole Spices, Yogurt, Ghee', 850.00, 750.00, 'medium', 45, 680, 4.90, 1, 1, 0, 'rice,biryani,popular,featured'),
(2, 'Beef Biryani', 'beef-biryani', 'Premium beef slow-cooked with fragrant basmati rice and traditional spices.', 'Basmati Rice, Beef, Whole Spices, Yogurt, Fried Onions, Ghee', 950.00, NULL, 'medium', 50, 720, 4.80, 0, 1, 0, 'rice,biryani,beef'),
(2, 'Mutton Biryani', 'mutton-biryani', 'Tender mutton cooked low and slow with premium basmati rice. The royal biryani experience.', 'Basmati Rice, Mutton, Saffron, Rose Water, Fried Onions, Premium Spices', 1150.00, 999.00, 'medium', 60, 760, 4.90, 1, 0, 0, 'rice,biryani,mutton,premium'),
(2, 'Vegetable Biryani', 'vegetable-biryani', 'Fragrant rice cooked with seasonal vegetables and aromatic spices.', 'Basmati Rice, Mixed Vegetables, Whole Spices, Saffron, Fried Onions', 650.00, NULL, 'mild', 35, 480, 4.40, 0, 0, 1, 'vegetarian,rice,biryani'),
(3, 'Chicken Karahi', 'chicken-karahi', 'A restaurant signature - chicken cooked in a wok with tomatoes, ginger, and green chilies.', 'Chicken, Tomatoes, Ginger, Green Chilies, Coriander, Spices', 950.00, 850.00, 'hot', 35, 420, 4.85, 1, 1, 0, 'karahi,featured,popular'),
(3, 'Mutton Karahi', 'mutton-karahi', 'Premium mutton cooked in our signature karahi style with robust spices.', 'Mutton, Tomatoes, Ginger, Green Chilies, Black Pepper, Coriander', 1250.00, NULL, 'hot', 45, 520, 4.80, 0, 1, 0, 'karahi,mutton,premium'),
(3, 'Butter Chicken', 'butter-chicken', 'Creamy, rich tomato-based curry with tender chicken. Comfort food at its finest.', 'Chicken, Butter, Cream, Tomatoes, Cashews, Garam Masala, Fenugreek', 880.00, 780.00, 'mild', 30, 480, 4.75, 1, 1, 0, 'curry,creamy,popular'),
(3, 'Dal Makhani', 'dal-makhani', 'Slow-cooked black lentils in a rich buttery tomato sauce. A vegetarian delicacy.', 'Black Lentils, Butter, Cream, Tomatoes, Garlic, Garam Masala', 580.00, NULL, 'mild', 40, 340, 4.70, 0, 1, 1, 'vegetarian,dal,curry'),
(4, 'Mixed BBQ Platter', 'mixed-bbq-platter', 'A royal selection of our finest BBQ items - tikka, kebabs, and chops. Perfect for sharing.', 'Chicken Tikka, Seekh Kebab, Mutton Chops, Naan, Raita, Mint Chutney', 2200.00, 1899.00, 'medium', 40, 1200, 4.90, 1, 1, 0, 'bbq,platter,sharing,featured'),
(4, 'Mutton Chops', 'mutton-chops', 'Tender mutton chops marinated overnight and grilled to smoky perfection.', 'Mutton Chops, Papaya Paste, Yogurt, Spices, Lemon', 1450.00, NULL, 'medium', 35, 580, 4.80, 0, 1, 0, 'bbq,mutton,grilled'),
(4, 'Chicken Reshmi Kebab', 'chicken-reshmi-kebab', 'Silky smooth chicken kebabs with a melt-in-mouth texture. Cooked in the tandoor.', 'Chicken Mince, Cream, Cashews, Green Chilies, Coriander', 780.00, 680.00, 'mild', 25, 380, 4.75, 0, 1, 0, 'kebab,tender,grilled'),
(5, 'Garlic Naan', 'garlic-naan', 'Freshly baked tandoor bread topped with garlic butter and coriander.', 'Flour, Yeast, Garlic, Butter, Coriander, Salt', 120.00, NULL, 'none', 10, 180, 4.70, 0, 1, 1, 'bread,vegetarian,tandoor'),
(5, 'Butter Roti', 'butter-roti', 'Soft whole wheat roti brushed with fresh butter from the tandoor.', 'Whole Wheat Flour, Butter, Salt', 60.00, NULL, 'none', 8, 120, 4.60, 0, 1, 1, 'bread,vegetarian,tandoor'),
(5, 'Peshwari Naan', 'peshwari-naan', 'Sweet naan stuffed with coconut, almonds, and raisins. A Peshawar specialty.', 'Flour, Coconut, Almonds, Raisins, Sugar, Butter', 180.00, NULL, 'none', 12, 280, 4.65, 0, 0, 1, 'bread,sweet,vegetarian'),
(6, 'Gulab Jamun', 'gulab-jamun', 'Soft milk solids dumplings soaked in rose-flavored sugar syrup. A timeless dessert.', 'Milk Powder, Flour, Ghee, Sugar Syrup, Rose Water, Cardamom', 250.00, NULL, 'none', 5, 380, 4.80, 0, 1, 1, 'dessert,sweet,vegetarian'),
(6, 'Kheer', 'kheer', 'Creamy rice pudding slow-cooked with milk, sugar, and aromatic spices.', 'Rice, Milk, Sugar, Cardamom, Saffron, Almonds, Pistachios', 280.00, NULL, 'none', 10, 320, 4.75, 0, 1, 1, 'dessert,creamy,vegetarian'),
(6, 'Shahi Tukra', 'shahi-tukra', 'Royal bread pudding soaked in saffron milk, topped with rabri and dry fruits.', 'Bread, Milk, Saffron, Sugar, Almonds, Pistachios, Rose Water', 350.00, 299.00, 'none', 15, 420, 4.85, 1, 0, 1, 'dessert,royal,premium,vegetarian'),
(7, 'Mango Lassi', 'mango-lassi', 'Refreshing yogurt-based drink blended with fresh Alphonso mangoes.', 'Yogurt, Mango Pulp, Sugar, Milk, Ice', 220.00, NULL, 'none', 5, 180, 4.80, 0, 1, 1, 'beverage,lassi,refreshing'),
(7, 'Rooh Afza Sharbat', 'rooh-afza', 'Traditional rose-flavored cooling drink. Perfect for warm days.', 'Rooh Afza Syrup, Milk, Ice, Rose Petals', 150.00, NULL, 'none', 3, 120, 4.60, 0, 1, 1, 'beverage,traditional,refreshing'),
(7, 'Kashmiri Chai', 'kashmiri-chai', 'Authentic pink salt tea with creamy milk, cardamom, and pistachios.', 'Kashmiri Tea, Milk, Salt, Cardamom, Pistachios, Cream', 180.00, NULL, 'none', 10, 140, 4.90, 1, 1, 1, 'beverage,tea,traditional,featured'),
(8, 'Family Deal 1', 'family-deal-1', 'Perfect for 4-6 people. Includes Chicken Biryani, Chicken Karahi, Naan x4, Raita, and 2 Desserts.', 'Chicken Biryani, Chicken Karahi, Garlic Naan x4, Raita, Gulab Jamun x2', 2999.00, 2499.00, 'medium', 60, 2400, 4.90, 1, 1, 0, 'deal,family,combo,featured'),
(8, 'Couple Deal', 'couple-deal', 'Romantic dinner for 2. Includes Butter Chicken, Biryani, Naan x2, Dessert, and Beverages.', 'Butter Chicken, Chicken Biryani, Garlic Naan x2, Gulab Jamun, Mango Lassi x2', 1899.00, 1499.00, 'mild', 45, 1800, 4.85, 1, 1, 0, 'deal,couple,combo,romantic');

-- =============================================
-- INSERT BANNERS
-- =============================================
INSERT INTO banners (title, subtitle, description, image, button_text, button_link, banner_type, sort_order, is_active) VALUES
('Welcome to Haveli', 'Royal Flavors, Crafted for Kings', 'Experience the authentic taste of Pakistan & India', '', 'Order Now', '/menu.php', 'hero', 1, 1),
('Weekend Special', '30% Off on Family Deals', 'This weekend only - celebrate with your family', '', 'Grab Deal', '/offers.php', 'slider', 1, 1),
('New: Mutton Biryani', 'The Royal Biryani Experience', 'Slow-cooked for 60 minutes with premium spices', '', 'Order Now', '/food-details.php?slug=mutton-biryani', 'slider', 2, 1),
('BBQ Season', 'Fire Up Your Taste Buds', 'Our mixed BBQ platter is back!', '', 'See More', '/menu.php?category=bbq-grills', 'slider', 3, 1);

-- =============================================
-- INSERT SAMPLE COUPON
-- =============================================
INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount, max_discount, usage_limit, valid_from, valid_until, is_active) VALUES
('WELCOME20', 'Get 20% off on your first order', 'percentage', 20.00, 500.00, 300.00, 1000, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 1),
('HAVELI50', 'Flat Rs.50 off on orders above Rs.800', 'fixed', 50.00, 800.00, NULL, 500, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1),
('ROYAL100', 'Flat Rs.100 off on orders above Rs.1500', 'fixed', 100.00, 1500.00, NULL, 300, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 1);

-- =============================================
-- INSERT SAMPLE USER (password: User@123)
-- =============================================
INSERT INTO users (name, email, phone, password, email_verified) VALUES
('Ahmed Khan', 'user@haveli.com', '+92 301 1234567', '$2y$12$LQv3c1yqBWVHxkd0LQ1Tc.6qNm8eBsn6B0g8L3fBjHZE.P3X1WBJG', 1);