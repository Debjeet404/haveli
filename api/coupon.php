<?php
// api/coupon.php
require_once '../includes/config.php';
header('Content-Type: application/json');

$input    = json_decode(file_get_contents('php://input'), true);
$code     = strtoupper(sanitize($input['code'] ?? ''));
$subtotal = (float)($input['subtotal'] ?? 0);

if (!$code) { echo json_encode(['success'=>false,'message'=>'Enter a coupon code']); exit; }

$pdo  = getDB();
$stmt = $pdo->prepare("
    SELECT * FROM coupons
    WHERE code = ? AND is_active = 1
    AND (expires_at IS NULL OR expires_at >= CURDATE())
    AND (uses_limit IS NULL OR uses_count < uses_limit)
");
$stmt->execute([$code]);
$coupon = $stmt->fetch();

if (!$coupon) { echo json_encode(['success'=>false,'message'=>'Invalid or expired coupon']); exit; }
if ($subtotal < $coupon['min_order']) {
    echo json_encode(['success'=>false,'message'=>'Minimum order amount: ' . getSetting('site_currency','₨') . number_format($coupon['min_order'])]);
    exit;
}

$discount = $coupon['type'] === 'percentage'
    ? min($subtotal * $coupon['value'] / 100, $coupon['max_discount'] ?? PHP_INT_MAX)
    : min($coupon['value'], $coupon['max_discount'] ?? PHP_INT_MAX);

echo json_encode(['success'=>true,'coupon'=>['code'=>$code,'type'=>$coupon['type'],'value'=>$coupon['value'],'discount'=>round($discount,2)]]);
