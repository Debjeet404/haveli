<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'message'=>'Invalid request']); exit; }
if (!verifyCSRF($_POST['csrf_token'] ?? '')) { echo json_encode(['success'=>false,'message'=>'Security error']); exit; }

$cartData = json_decode($_POST['cart_data'] ?? '{}', true);
if (empty($cartData['items'])) { echo json_encode(['success'=>false,'message'=>'Cart is empty']); exit; }

$pdo = getDB();
$minOrder = (float)getSetting('min_order_amount','500');

$name    = sanitize($_POST['customer_name'] ?? '');
$email   = filter_var($_POST['customer_email'] ?? '', FILTER_VALIDATE_EMAIL);
$phone   = sanitize($_POST['customer_phone'] ?? '');
$address = sanitize($_POST['delivery_address'] ?? '');
$notes   = sanitize($_POST['notes'] ?? '');
$payment = in_array($_POST['payment_method'] ?? '', ['cod','online']) ? $_POST['payment_method'] : 'cod';
$coupon  = sanitize($_POST['coupon_code'] ?? '');

if (!$name || !$email || !$phone || !$address) { echo json_encode(['success'=>false,'message'=>'Please fill in all required fields']); exit; }

$subtotal = (float)($cartData['subtotal'] ?? 0);
$delivery = (float)($cartData['delivery_fee'] ?? 0);
$tax      = (float)($cartData['tax'] ?? 0);
$discount = (float)($cartData['discount'] ?? 0);
$total    = (float)($cartData['total'] ?? 0);

if ($subtotal < $minOrder) { echo json_encode(['success'=>false,'message'=>"Minimum order is " . getSetting('site_currency','₨') . number_format($minOrder)]); exit; }

try {
    $pdo->beginTransaction();

    $orderNum = generateOrderNumber();
    $estDelivery = date('Y-m-d H:i:s', strtotime('+45 minutes'));

    $stmt = $pdo->prepare("
        INSERT INTO orders (order_number,user_id,customer_name,customer_email,customer_phone,delivery_address,subtotal,delivery_fee,tax,discount,total,coupon_code,payment_method,notes,estimated_delivery)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
    $stmt->execute([$orderNum,$userId,$name,$email,$phone,$address,$subtotal,$delivery,$tax,$discount,$total,$coupon ?: null,$payment,$notes,$estDelivery]);
    $orderId = $pdo->lastInsertId();

    // Insert order items
    $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id,food_id,food_name,food_image,price,quantity,subtotal) VALUES (?,?,?,?,?,?,?)");
    foreach ($cartData['items'] as $item) {
        // Verify food exists and get current price
        $food = $pdo->prepare("SELECT * FROM foods WHERE id=? AND is_active=1");
        $food->execute([$item['id']]);
        $foodRow = $food->fetch();
        if (!$foodRow) continue;
        $price = $foodRow['discounted_price'] ?? $foodRow['price'];
        $qty   = max(1, min(20, (int)$item['qty']));
        $itemStmt->execute([$orderId, $item['id'], $foodRow['name'], $foodRow['image'] ?? '', $price, $qty, $price * $qty]);
    }

    // Update coupon usage
    if ($coupon) {
        $pdo->prepare("UPDATE coupons SET uses_count = uses_count + 1 WHERE code = ?")->execute([$coupon]);
    }

    // Create notification for user
    if ($userId) {
        $pdo->prepare("INSERT INTO notifications (user_id,title,message,type) VALUES (?,?,?,?)")
            ->execute([$userId, "Order Placed! 🎉", "Your order $orderNum has been placed successfully. Track it in real-time.", 'order']);
    }

    // Admin notification
    $pdo->prepare("INSERT INTO notifications (admin_id,title,message,type) VALUES (?,?,?,?)")
        ->execute([1, "New Order: $orderNum", "New order from $name — Total: " . getSetting('site_currency','₨') . number_format($total), 'order']);

    $pdo->commit();
    echo json_encode(['success'=>true,'order_number'=>$orderNum,'order_id'=>$orderId]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false,'message'=>'Failed to place order. Please try again.']);
}
