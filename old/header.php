<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title : 'Group Chat' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
    :root {
        --primary-color: #4e73df;
        --secondary-color: #f8f9fc;
        --sidebar-width: 250px;
    }

    body {
        background-color: #f8f9fa;
    }

    .sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        background: white;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        z-index: 1000;
        transition: all 0.3s;
    }

    .main-content {
        margin-left: var(--sidebar-width);
        padding: 20px;
        transition: all 0.3s;
    }

    .sidebar-brand {
        height: 4.375rem;
        text-decoration: none;
        font-size: 1.2rem;
        font-weight: 800;
        padding: 1.5rem 1rem;
        text-align: center;
        letter-spacing: 0.05rem;
        z-index: 1;
    }

    .sidebar-divider {
        border-top: 1px solid rgba(0, 0, 0, 0.1);
        margin: 1rem 0;
    }

    .nav-item {
        position: relative;
    }

    .nav-link {
        padding: 0.75rem 1rem;
        color: #d1d3e2;
    }

    .nav-link:hover {
        color: #b7b9cc;
    }

    .nav-link.active {
        color: var(--primary-color);
    }

    .nav-link i {
        margin-right: 0.25rem;
    }

    .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .chat-container {
        height: calc(100vh - 150px);
        overflow-y: auto;
    }

    .message {
        max-width: 70%;
        margin-bottom: 10px;
        padding: 10px 15px;
        border-radius: 15px;
        position: relative;
    }

    .received {
        background-color: #f1f1f1;
        margin-right: auto;
    }

    .sent {
        background-color: var(--primary-color);
        color: white;
        margin-left: auto;
    }

    .message-time {
        font-size: 0.75rem;
        opacity: 0.8;
        margin-top: 5px;
    }

    .thread-card {
        transition: all 0.2s;
    }

    .thread-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    @media (max-width: 768px) {
        .sidebar {
            margin-left: -var(--sidebar-width);
        }

        .main-content {
            margin-left: 0;
        }

        .sidebar.toggled {
            margin-left: 0;
        }

        .main-content.toggled {
            margin-left: var(--sidebar-width);
        }
    }
    </style>
</head>

<body data-user-id="<?= $_SESSION['user_id'] ?>" data-username="<?= htmlspecialchars($_SESSION['username']) ?>">
    <div class="sidebar" id="sidebar">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
            <div class="sidebar-brand-text mx-3">Group Chat</div>
        </a>
        <hr class="sidebar-divider">
        <div class="nav-item">
            <a class="nav-link" href="index.php">
                <i class="bi bi-house-door"></i>
                <span>Dashboard</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="groups.php">
                <i class="bi bi-people"></i>
                <span>Groups</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="profile.php">
                <i class="bi bi-person"></i>
                <span>Profile</span>
            </a>
        </div>
        <hr class="sidebar-divider">
        <div class="nav-item">
            <a class="nav-link" href="logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="main-content" id="main-content">
        <nav class="navbar navbar-expand navbar-light bg-white shadow mb-4">
            <div class="container-fluid">
                <button class="btn btn-link d-md-none rounded-circle me-3" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <div class="d-flex align-items-center">
                    <img src="assets/default.png" class="avatar me-2" alt="User Avatar">
                    <span class="fw-bold"><?= $current_username ?></span>
                </div>
            </div>
        </nav>