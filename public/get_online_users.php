<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$room_id = $_GET['room_id'] ?? null;

try {
    $conn = get_db_connection();

    // Get users active in the last 5 minutes (adjust as needed)
    $query = "SELECT DISTINCT u.id, u.username 
              FROM users u
              JOIN user_activity ua ON u.id = ua.user_id
              WHERE ua.last_activity > NOW() - INTERVAL 5 MINUTE";

    if ($room_id) {
        $query .= " AND ua.room_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $room_id);
    } else {
        $stmt = mysqli_prepare($conn, $query);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row['username'];
    }

    mysqli_close($conn);

    echo json_encode(['users' => $users]);
} catch (Exception $e) {
    if ($conn) mysqli_close($conn);
    echo json_encode(['error' => $e->getMessage()]);
}