<?php
// api/favorite.php
require_once '../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['success'=>false,'message'=>'Login required']); exit; }

$input   = json_decode(file_get_contents('php://input'), true);
$foodId  = (int)($input['food_id'] ?? 0);
$action  = sanitize($input['action'] ?? 'add');
$userId  = $_SESSION['user_id'];

if (!$foodId) { echo json_encode(['success'=>false]); exit; }

$pdo = getDB();
if ($action === 'add') {
    $pdo->prepare("INSERT IGNORE INTO favorites (user_id,food_id) VALUES (?,?)")->execute([$userId,$foodId]);
} else {
    $pdo->prepare("DELETE FROM favorites WHERE user_id=? AND food_id=?")->execute([$userId,$foodId]);
}
echo json_encode(['success'=>true]);
