<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['error'=>'Invalid']); exit; }

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT status, updated_at FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) { echo json_encode(['error'=>'Not found']); exit; }

echo json_encode(['status'=>$order['status'],'updated_at'=>$order['updated_at']]);
