<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        if (register_user($username, $password)) {
            // Automatically log in after registration
            if (login_user($username, $password)) {
                header('Location: index.php');
                exit;
            }
        } else {
            $error = 'Username already exists';
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Register - Group Chat</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
    .auth-container {
        max-width: 400px;
        margin: 50px auto;
        padding: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .auth-container h2 {
        text-align: center;
        color: #2c3e50;
    }

    .auth-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .auth-form input {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }

    .auth-form button {
        padding: 10px;
        background-color: #3498db;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }

    .auth-form button:hover {
        background-color: #2980b9;
    }

    .error {
        color: #e74c3c;
        text-align: center;
    }

    .auth-links {
        text-align: center;
        margin-top: 15px;
    }

    .auth-links a {
        color: #3498db;
        text-decoration: none;
    }
    </style>
</head>

<body>
    <div class="auth-container">
        <h2>Register</h2>

        <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form class="auth-form" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required minlength="6">
            <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="6">
            <button type="submit">Register</button>
        </form>

        <div class="auth-links">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>

</html>