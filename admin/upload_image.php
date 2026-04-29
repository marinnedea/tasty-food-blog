<?php
require '../includes/config.php';
require '../includes/auth.php';
require '../includes/functions.php';
require_backend();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $filename = save_image($_FILES['file'], dirname(__DIR__) . '/uploads/');
    if ($filename) {
        echo json_encode(['location' => '/uploads/' . $filename]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Upload failed']);
    }
}
