<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

if (!is_logged_in()) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

$message_id = $_POST['message_id'] ?? null;
$user_id = get_current_user_id();

if (!$message_id) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

$conn = get_db_connection();

try {
    // Verify the message belongs to the user
    $stmt = mysqli_prepare($conn, "SELECT user_id, file_path FROM messages WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $message_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $message = mysqli_fetch_assoc($result);

    if (!$message || $message['user_id'] != $user_id) {
        header('HTTP/1.1 403 Forbidden');
        mysqli_close($conn);
        exit;
    }

    // Delete the message
    $stmt = mysqli_prepare($conn, "DELETE FROM messages WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $message_id);
    $result = mysqli_stmt_execute($stmt);

    // If it was a file message, delete the file too
    if (!empty($message['file_path'])) {
        @unlink(__DIR__ . '/../' . $message['file_path']);
    }

    mysqli_close($conn);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database error']);
    }
} catch (Exception $e) {
    mysqli_close($conn);
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}