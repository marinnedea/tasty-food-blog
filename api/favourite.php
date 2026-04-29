<?php
require '../includes/config.php';
require '../includes/auth.php';
header('Content-Type: application/json');
auth_start();
if (!is_logged_in()) { echo json_encode(['ok'=>false]); exit; }

$data = json_decode(file_get_contents('php://input'), true);
$type = in_array($data['type'] ?? '', ['post','recipe']) ? $data['type'] : null;
$id   = (int)($data['id'] ?? 0);
$uid  = current_user()['id'];

if (!$type || !$id) { echo json_encode(['ok'=>false]); exit; }

$chk = $db->prepare("SELECT id FROM user_favorites WHERE user_id=? AND item_type=? AND item_id=?");
$chk->bind_param('isi', $uid, $type, $id);
$chk->execute();
$exists = $chk->get_result()->num_rows > 0;
$chk->close();

if ($exists) {
    $st = $db->prepare("DELETE FROM user_favorites WHERE user_id=? AND item_type=? AND item_id=?");
    $st->bind_param('isi', $uid, $type, $id);
    $st->execute();
    $st->close();
    echo json_encode(['ok'=>true, 'saved'=>false]);
} else {
    $st = $db->prepare("INSERT INTO user_favorites (user_id, item_type, item_id) VALUES (?,?,?)");
    $st->bind_param('isi', $uid, $type, $id);
    $st->execute();
    $st->close();
    echo json_encode(['ok'=>true, 'saved'=>true]);
}
