<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['success'=>false]); exit; }

$input   = json_decode(file_get_contents('php://input'), true);
$orderId = (int)($input['order_id'] ?? 0);
$userId  = $_SESSION['user_id'];

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) { echo json_encode(['success'=>false,'message'=>'Order not found']); exit; }

$items = $pdo->prepare("
    SELECT oi.*, f.image, f.discounted_price, f.is_available
    FROM order_items oi
    JOIN foods f ON oi.food_id = f.id
    WHERE oi.order_id = ? AND f.is_active=1 AND f.is_available=1
");
$items->execute([$orderId]);
$orderItems = $items->fetchAll();

$result = array_map(fn($i) => [
    'food_id'   => $i['food_id'],
    'food_name' => $i['food_name'],
    'price'     => $i['discounted_price'] ?? $i['price'],
    'image'     => $i['image'] ? BASE_URL . '/' . $i['image'] : '',
    'quantity'  => $i['quantity'],
], $orderItems);

echo json_encode(['success'=>true,'items'=>$result]);
