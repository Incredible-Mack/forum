<?php
require_once __DIR__ . '/../config/database.php';

function handle_file_upload($file, $room_id, $user_id)
{
    $upload_dir = __DIR__ . '/../uploads/';

    // Create uploads directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }

    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File too large (max 5MB)'];
    }

    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    $filepath = 'uploads/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
        // Save to database
        $conn = get_db_connection();
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO messages (room_id, user_id, message, is_file, file_path) 
             VALUES (?, ?, ?, 1, ?)"
        );

        $original_name = mysqli_real_escape_string($conn, $file['name']);
        mysqli_stmt_bind_param($stmt, "iiss", $room_id, $user_id, $original_name, $filepath);
        $result = mysqli_stmt_execute($stmt);
        $message_id = mysqli_insert_id($conn);

        mysqli_close($conn);

        if ($result) {
            return [
                'success' => true,
                'message_id' => $message_id,
                'file_path' => $filepath,
                'original_name' => $original_name
            ];
        }
    }

    return ['success' => false, 'error' => 'File upload failed'];
}

function get_file_message($message_id)
{
    $conn = get_db_connection();
    $stmt = mysqli_prepare(
        $conn,
        "SELECT m.*, u.username 
         FROM messages m 
         JOIN users u ON m.user_id = u.id 
         WHERE m.id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $message_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $message = mysqli_fetch_assoc($result);

    mysqli_close($conn);
    return $message;
}