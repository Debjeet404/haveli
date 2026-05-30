<?php
/**
 * HAVELI Restaurant - Database Configuration
 * Update these credentials for your server
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'haveli_db');
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL', 'http://localhost/haveli');
define('SITE_ROOT', dirname(__DIR__) . '/');
define('UPLOAD_PATH', SITE_ROOT . 'uploads/');

// Session config
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Database connection (PDO)
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// Get site settings
function getSetting($key, $default = '') {
    static $settings = null;
    if ($settings === null) {
        try {
            $pdo = getDB();
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            return $default;
        }
    }
    return $settings[$key] ?? $default;
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Generate order number
function generateOrderNumber() {
    return 'HVL-' . strtoupper(substr(md5(uniqid()), 0, 8));
}

// Format price
function formatPrice($price) {
    $currency = getSetting('site_currency', '₨');
    return $currency . number_format($price, 0);
}

// Check if user logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if admin logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Redirect
function redirect($url) {
    header("Location: $url");
    exit;
}

// Generate slug
function generateSlug($string) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    return $slug;
}

// Upload file
function uploadFile($file, $folder = 'foods') {
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) return ['error' => 'Invalid file type'];
    if ($file['size'] > 5 * 1024 * 1024) return ['error' => 'File too large (max 5MB)'];
    
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $dest = UPLOAD_PATH . $folder . '/' . $filename;
    
    if (!is_dir(UPLOAD_PATH . $folder)) mkdir(UPLOAD_PATH . $folder, 0755, true);
    
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => true, 'path' => 'uploads/' . $folder . '/' . $filename];
    }
    return ['error' => 'Upload failed'];
}

// CSRF Token
function generateCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
