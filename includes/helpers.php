<?php
require_once __DIR__ . '/../config/database.php';

function get_rooms()
{
    $conn = get_db_connection();
    $result = mysqli_query($conn, "SELECT r.*, u.username as created_by_name 
                                  FROM rooms r 
                                  JOIN users u ON r.created_by = u.id 
                                  ORDER BY r.created_at DESC");

    $rooms = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rooms[] = $row;
    }

    mysqli_close($conn);
    return $rooms;
}

function create_room($name, $user_id, $descriptionName)
{
    $conn = get_db_connection();
    $name = mysqli_real_escape_string($conn, $name);
    $descriptionName = mysqli_real_escape_string($conn, $descriptionName);
    $stmt = mysqli_prepare($conn, "INSERT INTO rooms (name, created_by, description) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sis", $name, $user_id, $descriptionName);
    $result = mysqli_stmt_execute($stmt);
    $room_id = mysqli_insert_id($conn);

    mysqli_close($conn);
    return $result ? $room_id : false;
}

function get_room($room_id)
{
    $conn = get_db_connection();
    $stmt = mysqli_prepare($conn, "SELECT r.*, u.username as created_by_name 
                                   FROM rooms r 
                                   JOIN users u ON r.created_by = u.id 
                                   WHERE r.id = ?");

    mysqli_stmt_bind_param($stmt, "i", $room_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $room = mysqli_fetch_assoc($result);

    mysqli_close($conn);
    return $room;
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
    ];

    return str_replace(array_keys($emojis), array_values($emojis), $text);
}