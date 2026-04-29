<?php
require '../includes/config.php';
require '../includes/auth.php';
header('Content-Type: application/json');
auth_start();
if (!is_logged_in()) { echo json_encode(['ok'=>false]); exit; }

$data   = json_decode(file_get_contents('php://input'), true);
$type   = in_array($data['type'] ?? '', ['post','recipe']) ? $data['type'] : null;
$id     = (int)($data['id'] ?? 0);
$rating = (int)($data['rating'] ?? 0);
$uid    = current_user()['id'];

if (!$type || !$id || $rating < 1 || $rating > 5) { echo json_encode(['ok'=>false]); exit; }

$st = $db->prepare("INSERT INTO ratings (user_id, item_type, item_id, rating) VALUES (?,?,?,?)
                    ON DUPLICATE KEY UPDATE rating=VALUES(rating)");
$st->bind_param('isii', $uid, $type, $id, $rating);
$st->execute();
$st->close();
echo json_encode(['ok'=>true]);
