<?php
require_once 'config.php';

// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['group_id'])) {
//     $group_id = (int)$_POST['group_id'];
    
//     // Check if user is already a member
//     $check_stmt = $conn->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?");
//     $check_stmt->bind_param("ii", $group_id, $_SESSION['user_id']);
//     $check_stmt->execute();
//     $check_stmt->store_result();
    
//     if ($check_stmt->num_rows === 0) {
//         // Join the group
//         $join;
//     }