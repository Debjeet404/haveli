<?php
require_once __DIR__ . '/database.php';

// =============================================
// SECURITY FUNCTIONS
// =============================================

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function generateToken(): string {
    return bin2hex(random_bytes(32));
}

function verifyToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function generateCSRF(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRF() . '">';
}

// =============================================
// AUTHENTICATION FUNCTIONS
// =============================================

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('/login.php');
    }
}

function requireAdmin(): void {
    if (!isAdmin()) {
        redirect('/admin/login.php');
    }
}

function loginUser(array $user): void {
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
    session_regenerate_id(true);
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

// =============================================
// SETTINGS FUNCTIONS
// =============================================

function getSetting(string $key, string $default = ''): string {
    static $settings = null;
    
    if ($settings === null) {
        $stmt = db()->query("SELECT setting_key, setting_value FROM settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    return $settings[$key] ?? $default;
}

function updateSetting(string $key, string $value): bool {
    $stmt = db()->prepare("
        INSERT INTO settings (setting_key, setting_value) 
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    return $stmt->execute([$key, $value, $value]);
}

// =============================================
// FOOD FUNCTIONS
// =============================================

function getAllFoods(array $filters = []): array {
    $where = ['f.is_available = 1'];
    $params = [];

    if (!empty($filters['category'])) {
        $where[] = 'c.slug = ?';
        $params[] = $filters['category'];
    }
    if (!empty($filters['search'])) {
        $where[] = '(f.name LIKE ? OR f.description LIKE ?)';
        $params[] = "%{$filters['search']}%";
        $params[] = "%{$filters['search']}%";
    }
    if (isset($filters['is_veg']) && $filters['is_veg'] !== '') {
        $where[] = 'f.is_veg = ?';
        $params[] = $filters['is_veg'];
    }
    if (!empty($filters['featured'])) {
        $where[] = 'f.is_featured = 1';
    }
    if (!empty($filters['bestseller'])) {
        $where[] = 'f.is_bestseller = 1';
    }

    $whereClause = implode(' AND ', $where);
    $orderBy = match($filters['sort'] ?? '') {
        'price_asc'  => 'f.price ASC',
        'price_desc' => 'f.price DESC',
        'rating'     => 'f.rating DESC',
        'newest'     => 'f.created_at DESC',
        default      => 'f.sort_order ASC, f.name ASC'
    };

    $limit = '';
    if (!empty($filters['limit'])) {
        $limit = 'LIMIT ' . (int)$filters['limit'];
    }

    $sql = "
        SELECT f.*, c.name as category_name, c.slug as category_slug,
               COALESCE(f.discount_price, f.price) as final_price
        FROM foods f
        LEFT JOIN categories c ON f.category_id = c.id
        WHERE {$whereClause}
        ORDER BY {$orderBy}
        {$limit}
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getFoodBySlug(string $slug): ?array {
    $stmt = db()->prepare("
        SELECT f.*, c.name as category_name, c.slug as category_slug,
               COALESCE(f.discount_price, f.price) as final_price
        FROM foods f
        LEFT JOIN categories c ON f.category_id = c.id
        WHERE f.slug = ? AND f.is_available = 1
    ");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function getFoodById(int $id): ?array {
    $stmt = db()->prepare("
        SELECT f.*, c.name as category_name,
               COALESCE(f.discount_price, f.price) as final_price
        FROM foods f
        LEFT JOIN categories c ON f.category_id = c.id
        WHERE f.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getRelatedFoods(int $categoryId, int $excludeId, int $limit = 4): array {
    $stmt = db()->prepare("
        SELECT *, COALESCE(discount_price, price) as final_price
        FROM foods 
        WHERE category_id = ? AND id != ? AND is_available = 1
        ORDER BY RAND()
        LIMIT ?
    ");
    $stmt->execute([$categoryId, $excludeId, $limit]);
    return $stmt->fetchAll();
}

function getFoodAddons(int $foodId): array {
    $stmt = db()->prepare("SELECT * FROM food_addons WHERE food_id = ?");
    $stmt->execute([$foodId]);
    return $stmt->fetchAll();
}

// =============================================
// CATEGORY FUNCTIONS
// =============================================

function getAllCategories(bool $withCount = false): array {
    if ($withCount) {
        $stmt = db()->query("
            SELECT c.*, COUNT(f.id) as food_count
            FROM categories c
            LEFT JOIN foods f ON f.category_id = c.id AND f.is_available = 1
            WHERE c.is_active = 1
            GROUP BY c.id
            ORDER BY c.sort_order ASC
        ");
    } else {
        $stmt = db()->query("
            SELECT * FROM categories 
            WHERE is_active = 1 
            ORDER BY sort_order ASC
        ");
    }
    return $stmt->fetchAll();
}

function getCategoryBySlug(string $slug): ?array {
    $stmt = db()->prepare("SELECT * FROM categories WHERE slug = ? AND is_active = 1");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

// =============================================
// ORDER FUNCTIONS
// =============================================

function generateOrderNumber(): string {
    return 'HAV' . strtoupper(substr(uniqid(), -6)) . rand(10, 99);
}

function createOrder(array $data): int|false {
    try {
        db()->beginTransaction();

        $orderNumber = generateOrderNumber();
        
        $stmt = db()->prepare("
            INSERT INTO orders (
                order_number, user_id, name, email, phone, address, city, notes,
                subtotal, delivery_fee, discount, tax, total,
                payment_method, coupon_code, estimated_time
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $orderNumber,
            $data['user_id'] ?? null,
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['address'],
            $data['city'],
            $data['notes'] ?? null,
            $data['subtotal'],
            $data['delivery_fee'],
            $data['discount'] ?? 0,
            $data['tax'] ?? 0,
            $data['total'],
            $data['payment_method'] ?? 'cod',
            $data['coupon_code'] ?? null,
            $data['estimated_time'] ?? 45
        ]);

        $orderId = db()->lastInsertId();

        // Insert order items
        foreach ($data['items'] as $item) {
            $itemStmt = db()->prepare("
                INSERT INTO order_items (order_id, food_id, food_name, food_image, quantity, price, addons, total)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $itemStmt->execute([
                $orderId,
                $item['food_id'],
                $item['food_name'],
                $item['food_image'] ?? null,
                $item['quantity'],
                $item['price'],
                $item['addons'] ?? null,
                $item['total']
            ]);
        }

        // Add initial tracking
        addOrderTracking($orderId, 'pending', 'Order placed successfully');

        // Update coupon usage
        if (!empty($data['coupon_code'])) {
            db()->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?")
                 ->execute([$data['coupon_code']]);
        }

        db()->commit();
        return (int)$orderId;

    } catch (Exception $e) {
        db()->rollBack();
        error_log("Order creation failed: " . $e->getMessage());
        return false;
    }
}

function getOrderById(int $id): ?array {
    $stmt = db()->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getOrderByNumber(string $number): ?array {
    $stmt = db()->prepare("SELECT * FROM orders WHERE order_number = ?");
    $stmt->execute([$number]);
    return $stmt->fetch() ?: null;
}

function getOrderItems(int $orderId): array {
    $stmt = db()->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

function getUserOrders(int $userId): array {
    $stmt = db()->prepare("
        SELECT * FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function updateOrderStatus(int $orderId, string $status, string $message = ''): bool {
    $stmt = db()->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    $result = $stmt->execute([$status, $orderId]);
    
    if ($result) {
        addOrderTracking($orderId, $status, $message);
    }
    return $result;
}

function addOrderTracking(int $orderId, string $status, string $message): void {
    $stmt = db()->prepare("
        INSERT INTO order_tracking (order_id, status, message) VALUES (?, ?, ?)
    ");
    $stmt->execute([$orderId, $status, $message]);
}

function getOrderTracking(int $orderId): array {
    $stmt = db()->prepare("
        SELECT * FROM order_tracking 
        WHERE order_id = ? 
        ORDER BY updated_at ASC
    ");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

// =============================================
// CART FUNCTIONS
// =============================================

function getCart(): array {
    return $_SESSION['cart'] ?? [];
}

function getCartCount(): int {
    $cart = getCart();
    return array_sum(array_column($cart, 'quantity'));
}

function getCartTotal(): float {
    $cart = getCart();
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

function addToCart(int $foodId, int $quantity = 1): array {
    $food = getFoodById($foodId);
    
    if (!$food) {
        return ['success' => false, 'message' => 'Food item not found'];
    }

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $key = 'food_' . $foodId;
    
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$key] = [
            'food_id'  => $foodId,
            'name'     => $food['name'],
            'price'    => $food['final_price'],
            'image'    => $food['image'],
            'is_veg'   => $food['is_veg'],
            'quantity' => $quantity
        ];
    }

    return [
        'success' => true,
        'message' => $food['name'] . ' added to cart',
        'count'   => getCartCount()
    ];
}

function updateCartItem(int $foodId, int $quantity): array {
    $key = 'food_' . $foodId;
    
    if (!isset($_SESSION['cart'][$key])) {
        return ['success' => false, 'message' => 'Item not in cart'];
    }

    if ($quantity <= 0) {
        unset($_SESSION['cart'][$key]);
        return ['success' => true, 'message' => 'Item removed from cart', 'count' => getCartCount()];
    }

    $_SESSION['cart'][$key]['quantity'] = $quantity;
    return ['success' => true, 'message' => 'Cart updated', 'count' => getCartCount()];
}

function removeFromCart(int $foodId): array {
    $key = 'food_' . $foodId;
    unset($_SESSION['cart'][$key]);
    return ['success' => true, 'message' => 'Item removed', 'count' => getCartCount()];
}

function clearCart(): void {
    $_SESSION['cart'] = [];
}

// =============================================
// COUPON FUNCTIONS
// =============================================

function validateCoupon(string $code, float $orderTotal): array {
    $stmt = db()->prepare("
        SELECT * FROM coupons 
        WHERE code = ? AND is_active = 1
        AND (expires_at IS NULL OR expires_at >= CURDATE())
        AND (usage_limit IS NULL OR used_count < usage_limit)
    ");
    $stmt->execute([strtoupper($code)]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        return ['valid' => false, 'message' => 'Invalid or expired coupon code'];
    }

    if ($orderTotal < $coupon['min_order']) {
        return [
            'valid'   => false,
            'message' => 'Minimum order of ₹' . number_format($coupon['min_order'], 2) . ' required'
        ];
    }

    $discount = 0;
    if ($coupon['type'] === 'percentage') {
        $discount = ($orderTotal * $coupon['value']) / 100;
        if ($coupon['max_discount']) {
            $discount = min($discount, $coupon['max_discount']);
        }
    } else {
        $discount = $coupon['value'];
    }

    return [
        'valid'       => true,
        'coupon'      => $coupon,
        'discount'    => round($discount, 2),
        'message'     => 'Coupon applied! You save ₹' . number_format($discount, 2)
    ];
}

// =============================================
// REVIEW FUNCTIONS
// =============================================

function getFoodReviews(int $foodId): array {
    $stmt = db()->prepare("
        SELECT r.*, u.profile_image
        FROM reviews r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.food_id = ? AND r.is_approved = 1
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$foodId]);
    return $stmt->fetchAll();
}

function addReview(array $data): bool {
    $stmt = db()->prepare("
        INSERT INTO reviews (food_id, user_id, order_id, name, email, rating, review)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $result = $stmt->execute([
        $data['food_id'],
        $data['user_id'] ?? null,
        $data['order_id'] ?? null,
        $data['name'],
        $data['email'] ?? null,
        $data['rating'],
        $data['review'] ?? null
    ]);

    if ($result) {
        updateFoodRating($data['food_id']);
    }
    return $result;
}

function updateFoodRating(int $foodId): void {
    $stmt = db()->prepare("
        UPDATE foods SET 
            rating = (SELECT AVG(rating) FROM reviews WHERE food_id = ? AND is_approved = 1),
            total_reviews = (SELECT COUNT(*) FROM reviews WHERE food_id = ? AND is_approved = 1)
        WHERE id = ?
    ");
    $stmt->execute([$foodId, $foodId, $foodId]);
}

// =============================================
// BANNER FUNCTIONS
// =============================================

function getActiveBanners(): array {
    $stmt = db()->query("
        SELECT * FROM banners 
        WHERE is_active = 1 
        ORDER BY sort_order ASC
    ");
    return $stmt->fetchAll();
}

// =============================================
// WISHLIST FUNCTIONS
// =============================================

function getUserWishlist(int $userId): array {
    $stmt = db()->prepare("
        SELECT f.*, w.created_at as added_at,
               COALESCE(f.discount_price, f.price) as final_price
        FROM wishlist w
        JOIN foods f ON w.food_id = f.id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function toggleWishlist(int $userId, int $foodId): array {
    $stmt = db()->prepare("SELECT id FROM wishlist WHERE user_id = ? AND food_id = ?");
    $stmt->execute([$userId, $foodId]);
    
    if ($stmt->fetch()) {
        db()->prepare("DELETE FROM wishlist WHERE user_id = ? AND food_id = ?")->execute([$userId, $foodId]);
        return ['success' => true, 'action' => 'removed', 'message' => 'Removed from wishlist'];
    } else {
        db()->prepare("INSERT INTO wishlist (user_id, food_id) VALUES (?, ?)")->execute([$userId, $foodId]);
        return ['success' => true, 'action' => 'added', 'message' => 'Added to wishlist'];
    }
}

function isInWishlist(int $userId, int $foodId): bool {
    $stmt = db()->prepare("SELECT id FROM wishlist WHERE user_id = ? AND food_id = ?");
    $stmt->execute([$userId, $foodId]);
    return (bool)$stmt->fetch();
}

// =============================================
// UTILITY FUNCTIONS
// =============================================

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function formatPrice(float $price): string {
    return getSetting('currency_symbol', '₹') . number_format($price, 2);
}

function formatDate(string $date, string $format = 'd M Y, h:i A'): string {
    return date($format, strtotime($date));
}

function timeAgo(string $datetime): string {
    $time = time() - strtotime($datetime);
    
    if ($time < 60)       return 'just now';
    if ($time < 3600)     return floor($time / 60) . ' min ago';
    if ($time < 86400)    return floor($time / 3600) . ' hours ago';
    if ($time < 604800)   return floor($time / 86400) . ' days ago';
    return date('d M Y', strtotime($datetime));
}

function getStatusColor(string $status): string {
    return match($status) {
        'pending'          => 'warning',
        'confirmed'        => 'info',
        'preparing'        => 'primary',
        'out_for_delivery' => 'purple',
        'delivered'        => 'success',
        'cancelled'        => 'danger',
        default            => 'secondary'
    };
}

function getStatusLabel(string $status): string {
    return match($status) {
        'pending'          => 'Order Placed',
        'confirmed'        => 'Confirmed',
        'preparing'        => 'Preparing',
        'out_for_delivery' => 'Out for Delivery',
        'delivered'        => 'Delivered',
        'cancelled'        => 'Cancelled',
        default            => ucfirst($status)
    };
}

function uploadImage(array $file, string $folder = 'foods'): string|false {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }

    if ($file['size'] > $maxSize) {
        return false;
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $uploadDir = __DIR__ . '/../assets/uploads/' . $folder . '/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return $filename;
    }

    return false;
}

function getFoodImage(string $image = '', string $folder = 'foods'): string {
    if (!empty($image) && file_exists(__DIR__ . '/../assets/uploads/' . $folder . '/' . $image)) {
        return '/assets/uploads/' . $folder . '/' . $image;
    }
    return '/assets/img/food-placeholder.jpg';
}

function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

function paginate(int $totalItems, int $perPage, int $currentPage): array {
    $totalPages = (int)ceil($totalItems / $perPage);
    $offset     = ($currentPage - 1) * $perPage;

    return [
        'total_items'  => $totalItems,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'offset'       => $offset,
        'has_prev'     => $currentPage > 1,
        'has_next'     => $currentPage < $totalPages
    ];
}

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function getAdminStats(): array {
    $stats = [];
    
    $stmt = db()->query("SELECT COUNT(*) as total, SUM(total) as revenue FROM orders WHERE order_status != 'cancelled'");
    $orders = $stmt->fetch();
    $stats['total_orders']  = $orders['total'];
    $stats['total_revenue'] = $orders['revenue'] ?? 0;

    $stats['total_customers'] = db()->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
    $stats['total_foods']     = db()->query("SELECT COUNT(*) FROM foods WHERE is_available = 1")->fetchColumn();

    $stmt = db()->query("
        SELECT COUNT(*) as count FROM orders 
        WHERE order_status = 'pending' AND DATE(created_at) = CURDATE()
    ");
    $stats['today_pending'] = $stmt->fetchColumn();

    $stmt = db()->query("
        SELECT DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders
        FROM orders 
        WHERE order_status != 'cancelled' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stats['weekly_data'] = $stmt->fetchAll();

    return $stats;
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}