<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/file_upload.php';

if (!is_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$roomId = (int)($_POST['room_id'] ?? 0);
$userId = (int)($_POST['user_id'] ?? 0);

if ($roomId > 0 && $userId > 0 && $userId === get_current_user_id()) {
    $result = handle_file_upload($_FILES['file'], $roomId, $userId);
    echo json_encode($result);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);