<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$rooms = get_rooms();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Group Chat</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
    /* Add these to your existing CSS */

    /* Discussion header */
    .discussion-header {
        padding: 20px 24px;
        background-color: white;
        border-bottom: 1px solid var(--light-gray);
    }

    .discussion-title {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 8px;
        color: var(--dark-color);
    }

    .discussion-description {
        font-size: 15px;
        color: var(--gray-color);
        margin-bottom: 12px;
        line-height: 1.5;
    }

    .discussion-meta {
        display: flex;
        gap: 16px;
        font-size: 14px;
        color: var(--gray-color);
    }

    .discussion-meta i {
        margin-right: 4px;
    }

    /* Enhanced dropdown */
    .search-box {
        position: relative;
        padding: 12px;
        border-bottom: 1px solid var(--light-gray);
    }

    .search-box input {
        width: 100%;
        padding: 8px 12px 8px 32px;
        border: 1px solid var(--light-gray);
        border-radius: var(--border-radius-sm);
        font-size: 14px;
    }

    .search-box i {
        position: absolute;
        left: 24px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray-color);
    }

    /* .room-link {
        display: flex;
        flex-direction: column;
        padding: 12px 16px;
    } */

    .room-name {
        font-weight: 500;
        margin-bottom: 4px;
    }

    .room-meta {
        display: flex;
        gap: 12px;
        font-size: 12px;
        color: var(--gray-color);
    }

    .room-meta i {
        margin-right: 2px;
    }

    .category-link {
        padding-left: 12px;
    }

    .category-link i {
        width: 20px;
        text-align: center;
        margin-right: 8px;
    }

    /* Message input enhancements */
    .input-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .emoji-btn {
        background: none;
        border: none;
        font-size: 18px;
        color: var(--gray-color);
        cursor: pointer;
        padding: 8px;
        border-radius: 50%;
        transition: var(--transition);
    }

    .emoji-btn:hover {
        background-color: var(--light-gray);
        color: var(--dark-color);
    }

    /* Message enhancements */
    .message {
        max-width: 800px;
    }

    .message-content {
        padding: 16px 20px;
        line-height: 1.6;
    }

    .message-user {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .message-user-name {
        text-transform: capitalize;
    }



    .message-user-avatar {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background-color: var(--accent-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 12px;
    }

    /* Thread indicators */
    .message-thread {
        margin-top: 8px;
        padding-left: 16px;
        border-left: 2px solid var(--light-gray);
    }

    .thread-count {
        font-size: 13px;
        color: var(--primary-color);
        cursor: pointer;
        margin-top: 4px;
    }

    .thread-count:hover {
        text-decoration: underline;
    }

    .own-message {
        align-self: flex-end;
        background-color: var(--primary-color);
        border-radius: var(--border-radius);
        border-bottom-left-radius: 4px;
    }

    .other-message {
        align-self: flex-start;
        background-color: var(--secondary-color);
        border-radius: var(--border-radius);
        border-bottom-left-radius: 4px;

    }

    .message-content {
        min-width: 600px;
    }

    /* Textarea Styles */
    .form-control-1 {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        font-size: 15px;
        line-height: 1.5;
        color: #333;
        background-color: #fff;
        resize: vertical;
        /* Allows vertical resizing only */
        min-height: 120px;
        /* Minimum height */
        transition: all 0.3s ease;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    /* Focus state */
    .form-control-1:focus {
        outline: none;
        border-color: #4CAF50;
        /* Green accent color to match your theme */
        box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
    }

    /* Placeholder style */
    .form-control-1::placeholder {
        color: #999;
        opacity: 1;
        /* Fix for Firefox */
    }

    /* Disabled state */
    .form-control-1:disabled {
        background-color: #f9f9f9;
        cursor: not-allowed;
    }

    /* Error state (optional) */
    .form-control-1.error {
        border-color: #e74c3c;
    }

    /* Success state (optional) */
    .form-control-1.success {
        border-color: #2ecc71;
    }
    </style>
</head>

<!-- <body> -->
<?php
$_SESSION['status'] = 'admin';
?>

<body data-is-admin="<?= ($_SESSION['status'] ?? '') === 'admin' ? 'true' : 'false' ?>"></body>
<div class="top-nav">
    <div class="nav-left">
        <div class="logo">GroupChat</div>
        <div class="room-dropdown">
            <button class="dropdown-toggle" id="room-dropdown-toggle">
                <span id="current-room">Select a discussion</span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="dropdown-menu room-menu" id="room-dropdown-menu">
                <div class="search-box">
                    <input type="text" placeholder="Search discussions..." id="room-search">
                    <i class="fas fa-search"></i>
                </div>
                <div class="dropdown-header">Categories</div>
                <div id="category-list">
                    <!-- <a href="#" class="dropdown-item category-link active" data-category="all">
                        <i class="fas fa-globe"></i> All Discussions
                    </a> -->
                    <a href="#" class="dropdown-item category-link" id="announcementBtn" data-category="announcements">
                        <i class="fas fa-bullhorn"></i> Announcements
                    </a>
                    <!-- <a href="#" class="dropdown-item category-link" data-category="general">
                        <i class="fas fa-comments"></i> General
                    </a> -->
                </div>
                <div class="dropdown-header">Popular Discussions</div>
                <div id="room-list">
                    <?php foreach ($rooms as $room): ?>
                    <a href="#"
                        class="dropdown-item room-link <?php echo $room['id'] == $currentRoom ? 'active' : ''; ?>"
                        data-room-id="<?php echo $room['id']; ?>">
                        <?php echo htmlspecialchars($room['name']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="nav-right">
        <div class="online-users">
            <div class="user-count" id="user-count">
                <i class="fas fa-users"></i>
                <span id="online-count">0</span> online
                <i class="fas fa-chevron-down toggle-user-list"></i>
            </div>
            <div class="user-list" id="user-list">
                <div class="user-list-header">
                    <h4>Online Users</h4>
                    <i class="fas fa-times close-user-list"></i>
                </div>
                <ul id="user-list-items"></ul>
            </div>
        </div>

        <div class="user-dropdown">
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr(get_current_username(), 0, 1)); ?></div>
                <span class="user-name"><?php echo htmlspecialchars(get_current_username()); ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="dropdown-menu">
                <button class="dropdown-item" id="create-room-btn">
                    <i class="fas fa-plus"></i> Create Room
                </button>
                <button class="dropdown-item" onclick="location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>

        <button class="mobile-menu-btn" id="mobile-menu-btn">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</div>

<div class="mobile-room-menu" id="mobile-room-menu">
    <div class="room-section">
        <h3>Chat Rooms</h3>
        <div id="room-list">
            <?php foreach ($rooms as $room): ?>
            <a href="#" class="room-link <?php echo $room['id'] == $currentRoom ? 'active' : ''; ?>"
                data-room-id="<?php echo $room['id']; ?>">
                <?php echo htmlspecialchars($room['name']); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="main-content chat-area">
    <div class="messages-container" id="messages-container">
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="far fa-comment-dots"></i>
            </div>
            <div class="empty-state-text">Select a room to start chatting</div>
        </div>
    </div>

    <div class="message-input-area">
        <textarea class="message-input" id="message-input" placeholder="Type your message..."></textarea>
        <label for="file-input" class="file-upload-btn">
            <i class="fas fa-paperclip"></i>
            <input type="file" id="file-input" accept="image/*,.pdf,.txt">
        </label>
        <button class="send-btn" id="send-btn">
            <i class="fas fa-paper-plane"></i> Send
        </button>
    </div>
</div>

<!-- Room Creation Modal (hidden by default) -->
<div id="room-creation-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create New Room</h3>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <input type="text" id="room-name-input" placeholder="Enter room name...">
            <label for="Description">Description</label>
            <textarea name="description" id="description" class="form-control-1"></textarea>
        </div>
        <div class="modal-footer">
            <button class="modal-btn cancel-btn">Cancel</button>
            <button class="modal-btn create-btn">Create</button>
        </div>
    </div>
</div>

<!-- Announcement Modal -->
<div id="announcement-modal">
    <div class="announcement-modal-content">
        <span class="close-announcement-modal">&times;</span>
        <h2>Announcements</h2>
        <div id="announcement-content"></div>

        <!-- Admin-only form -->
        <div id="announcement-controls" style="margin-top: 20px;">
            <form id="announcement-form">
                <textarea id="announcement-input" placeholder="Write your announcement..." rows="3"
                    style="width: 100%; padding: 10px; margin-bottom: 10px;"></textarea>
                <button type="submit" class="btn btn-primary">Post Announcement</button>
            </form>
        </div>
    </div>
</div>

<!-- Admin button in UI -->
<button id="new-announcement-btn" class="btn btn-secondary" style="display: none;">
    <i class="fas fa-bullhorn"></i> New Announcement
</button>

<!-- Hidden field for admin status -->
<input type="hidden" id="is-admin" value="1">



<input type="hidden" id="user-id" value="<?php echo get_current_user_id(); ?>">
<input type="hidden" id="username" value="<?php echo htmlspecialchars(get_current_username()); ?>">

<script src="js/chat.js"></script>
</body>

</html>