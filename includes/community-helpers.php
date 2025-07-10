<?php
require_once __DIR__ . '/../config/db.php';

function get_rooms()
{
    $conn = get_db_connection();

    $query = "SELECT r.*, u.username as created_by_name, 
              (SELECT COUNT(*) FROM room_members rm WHERE rm.room_id = r.id) as member_count,
              (SELECT COUNT(*) FROM messages m WHERE m.room_id = r.id) as post_count
              FROM rooms r 
              JOIN users u ON r.created_by = u.id 
              ORDER BY r.created_at DESC";

    $result = mysqli_query($conn, $query);
    $rooms = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rooms[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'category' => $row['category'] ?? 'general',
            'created_by' => $row['created_by'],
            'created_by_name' => $row['created_by_name'],
            'created_at' => $row['created_at'],
            'member_count' => $row['member_count'] ?? 0,
            'post_count' => $row['post_count'] ?? 0
        ];
    }

    mysqli_close($conn);
    return $rooms;
}

function get_room($room_id)
{
    $conn = get_db_connection();

    $stmt = mysqli_prepare($conn, "SELECT r.*, u.username as created_by_name,
                                  (SELECT COUNT(*) FROM room_members rm WHERE rm.room_id = r.id) as member_count,
                                  (SELECT COUNT(*) FROM messages m WHERE m.room_id = r.id) as post_count
                                  FROM rooms r 
                                  JOIN users u ON r.created_by = u.id 
                                  WHERE r.id = ?");
    mysqli_stmt_bind_param($stmt, "i", $room_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $room = mysqli_fetch_assoc($result);

    mysqli_close($conn);
    return $room ?: null;
}

function create_room($name, $user_id, $description = '', $category = 'general')
{
    $conn = get_db_connection();

    $stmt = mysqli_prepare($conn, "INSERT INTO rooms (name, description, category, created_by) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssi", $name, $description, $category, $user_id);
    $success = mysqli_stmt_execute($stmt);
    $room_id = mysqli_insert_id($conn);

    if ($success) {
        // Add creator as first member
        $stmt2 = mysqli_prepare($conn, "INSERT INTO room_members (room_id, user_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt2, "ii", $room_id, $user_id);
        mysqli_stmt_execute($stmt2);
    }

    mysqli_close($conn);
    return $success ? $room_id : false;
}

function get_room_messages($room_id, $limit = 50)
{
    $conn = get_db_connection();

    $stmt = mysqli_prepare($conn, "SELECT m.*, u.username 
                                  FROM messages m
                                  JOIN users u ON m.user_id = u.id
                                  WHERE m.room_id = ?
                                  ORDER BY m.created_at DESC
                                  LIMIT ?");
    mysqli_stmt_bind_param($stmt, "ii", $room_id, $limit);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $messages = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = $row;
    }

    mysqli_close($conn);
    return array_reverse($messages); // Return oldest first
}

function send_message($room_id, $user_id, $message, $is_file = false, $file_path = null)
{
    $conn = get_db_connection();

    $stmt = mysqli_prepare($conn, "INSERT INTO messages (room_id, user_id, message, is_file, file_path) 
                                  VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iisis", $room_id, $user_id, $message, $is_file, $file_path);
    $success = mysqli_stmt_execute($stmt);

    mysqli_close($conn);
    return $success;
}

function join_room($room_id, $user_id)
{
    $conn = get_db_connection();

    $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO room_members (room_id, user_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ii", $room_id, $user_id);
    $success = mysqli_stmt_execute($stmt);

    mysqli_close($conn);
    return $success;
}

function get_online_users($room_id)
{
    $conn = get_db_connection();

    // In a real app, you'd track active connections
    // This is a simplified version
    $stmt = mysqli_prepare($conn, "SELECT u.id, u.username 
                                  FROM room_members rm
                                  JOIN users u ON rm.user_id = u.id
                                  WHERE rm.room_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $room_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $users = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }

    mysqli_close($conn);
    return $users;
}

function emojify($text)
{
    $emojis = [
        ':) ' => '😊 ',
        ':-) ' => '😊 ',
        ':D ' => '😃 ',
        ':-D ' => '😃 ',
        ':(' => '😞',
        ':-(' => '😞',
        ';) ' => '😉 ',
        ';-) ' => '😉 ',
        ':P ' => '😛 ',
        ':-P ' => '😛 ',
        ':O ' => '😮 ',
        ':-O ' => '😮 ',
        ':* ' => '😘 ',
        ':-* ' => '😘 ',
        '<3' => '❤️',
        '</3' => '💔'
    ];

    return str_replace(array_keys($emojis), array_values($emojis), $text . ' ') . '';
}