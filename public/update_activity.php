<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';



if (!is_logged_in()) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

$room_id = $_POST['room_id'] ?? null;
$user_id = get_current_user_id();

$conn = get_db_connection();

try {
    // First check if the user is already in the room
    $check_stmt = mysqli_prepare($conn, "SELECT 1 FROM user_activity WHERE user_id = ? AND room_id = ?");
    mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $room_id);
    mysqli_stmt_execute($check_stmt);
    $exists = mysqli_stmt_fetch($check_stmt);
    mysqli_stmt_close($check_stmt);

    if ($exists) {
        // Update existing activity record
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE user_activity 
             SET last_activity = NOW() 
             WHERE user_id = ? AND room_id = ?"
        );
    } else {
        // Create new activity record
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO user_activity (user_id, room_id, last_activity) 
             VALUES (?, ?, NOW())"
        );
    }

    mysqli_stmt_bind_param($stmt, "ii", $user_id, $room_id);
    mysqli_stmt_execute($stmt);

    // Clean up inactive users (older than 5 minutes)
    $cleanup_stmt = mysqli_prepare(
        $conn,
        "DELETE FROM user_activity 
         WHERE last_activity < NOW() - INTERVAL 5 MINUTE"
    );
    mysqli_stmt_execute($cleanup_stmt);
    mysqli_stmt_close($cleanup_stmt);

    mysqli_close($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Activity updated successfully'
    ]);
} catch (Exception $e) {
    if ($conn) {
        mysqli_close($conn);
    }
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTrace()
    ]);
}