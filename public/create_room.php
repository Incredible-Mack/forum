<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

if (!is_logged_in()) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomName = $_POST['name'] ?? '';
    $userId = get_current_user_id();

    if (!empty($roomName)) {
        $roomId = create_room($roomName, $userId);

        if ($roomId) {
            echo json_encode(['success' => true, 'room_id' => $roomId]);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);