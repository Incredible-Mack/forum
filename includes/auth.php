<?php
require_once __DIR__ . '/../config/database.php';
session_start();

function register_user($username, $password)
{
    $conn = get_db_connection();

    // Check if username exists
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_close($conn);
        return false; // Username exists
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ss", $username, $hashed_password);
    $result = mysqli_stmt_execute($stmt);

    mysqli_close($conn);
    return $result;
}

function login_user($username, $password)
{
    $conn = get_db_connection();
    $_SESSION['username_try'] = $username;

    $stmt = mysqli_prepare($conn, "SELECT id, password FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            mysqli_close($conn);
            return true;
        }
    }
    mysqli_close($conn);
    //return false;
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function get_current_user_id()
{
    return $_SESSION['user_id'] ?? null;
}

function get_current_username()
{
    return $_SESSION['username'] ?? null;
}
