<?php
require_once dirname(dirname(__DIR__)) . '/includes/config.php';
header('Content-Type: application/json');

if (!isAdminLoggedIn()) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }

$input  = json_decode(file_get_contents('php://input'), true);
$id     = (int)($input['id'] ?? 0);
$status = sanitize($input['status'] ?? '');
$valid  = ['pending','accepted','preparing','out_for_delivery','delivered','cancelled'];

if (!$id || !in_array($status, $valid)) { echo json_encode(['success'=>false,'message'=>'Invalid data']); exit; }

$pdo  = getDB();
$pdo->prepare("UPDATE orders SET status=?, updated_at=NOW() WHERE id=?")->execute([$status, $id]);

// Get order for notification
$order = $pdo->prepare("SELECT * FROM orders WHERE id=?");
$order->execute([$id]);
$o = $order->fetch();
if ($o && $o['user_id']) {
    $labels = [
        'accepted'         => 'confirmed and being processed',
        'preparing'        => 'being prepared in our kitchen',
        'out_for_delivery' => 'out for delivery — on the way to you!',
        'delivered'        => 'delivered. Enjoy your meal! 🎉',
        'cancelled'        => 'cancelled',
    ];
    if (isset($labels[$status])) {
        $pdo->prepare("INSERT INTO notifications (user_id,title,message,type) VALUES (?,?,?,?)")
            ->execute([$o['user_id'], 'Order Update 📦', "Your order {$o['order_number']} is {$labels[$status]}", 'order']);
    }
}

echo json_encode(['success'=>true, 'status'=>$status]);
