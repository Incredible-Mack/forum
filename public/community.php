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
    <title>Community Discussions</title>
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

    .room-link {
        display: flex;
        flex-direction: column;
        padding: 12px 16px;
    }

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
    </style>
</head>

<body>
    <div class="top-nav">
        <div class="nav-left">
            <div class="logo">CommunityHub</div>
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
                        <a href="#" class="dropdown-item category-link active" data-category="all">
                            <i class="fas fa-globe"></i> All Discussions
                        </a>
                        <a href="#" class="dropdown-item category-link" data-category="announcements">
                            <i class="fas fa-bullhorn"></i> Announcements
                        </a>
                        <a href="#" class="dropdown-item category-link" data-category="general">
                            <i class="fas fa-comments"></i> General
                        </a>
                    </div>
                    <div class="dropdown-header">Popular Discussions</div>
                    <div id="room-list">
                        <?php foreach ($rooms as $room): ?>
                        <a href="#" class="dropdown-item room-link" data-room-id="<?php echo $room['id']; ?>"
                            data-category="<?php echo htmlspecialchars($room['category']); ?>">
                            <span class="room-name"><?php echo htmlspecialchars($room['name']); ?></span>
                            <span class="room-meta">
                                <span class="member-count"><i class="fas fa-user"></i>
                                    <?php echo $room['member_count']; ?></span>
                                <span class="post-count"><i class="fas fa-comment"></i>
                                    <?php echo $room['post_count']; ?></span>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="nav-right">
            <div class="user-count">
                <span id="online-count">0</span> members online
            </div>

            <div class="user-dropdown">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr(get_current_username(), 0, 1)); ?></div>
                    <span class="user-name"><?php echo htmlspecialchars(get_current_username()); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="dropdown-menu">
                    <button class="dropdown-item" id="create-room-btn">
                        <i class="fas fa-plus"></i> New Discussion
                    </button>
                    <button class="dropdown-item">
                        <i class="fas fa-user"></i> Profile
                    </button>
                    <button class="dropdown-item">
                        <i class="fas fa-cog"></i> Settings
                    </button>
                    <button class="dropdown-item" onclick="location.href='logout.php'">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content chat-area">
        <div class="discussion-header">
            <div class="discussion-title" id="discussion-title">Welcome to CommunityHub</div>
            <div class="discussion-description" id="discussion-description">
                Select a discussion to participate in the conversation
            </div>
            <div class="discussion-meta">
                <span class="discussion-members"><i class="fas fa-users"></i> <span id="room-member-count">0</span>
                    members</span>
                <span class="discussion-posts"><i class="fas fa-comments"></i> <span id="room-post-count">0</span>
                    posts</span>
            </div>
        </div>

        <div class="messages-container" id="messages-container">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="far fa-comment-dots"></i>
                </div>
                <div class="empty-state-text">Select a discussion to start participating</div>
            </div>
        </div>

        <div class="message-input-area">
            <textarea class="message-input" id="message-input" placeholder="Share your thoughts..."></textarea>
            <div class="input-actions">
                <label for="file-input" class="file-upload-btn">
                    <i class="fas fa-paperclip"></i>
                    <input type="file" id="file-input" accept="image/*,.pdf,.txt,.doc,.docx">
                </label>
                <button class="emoji-btn" id="emoji-btn">
                    <i class="far fa-smile"></i>
                </button>
                <button class="send-btn" id="send-btn">
                    <i class="fas fa-paper-plane"></i> Post
                </button>
            </div>
        </div>
    </div>

    <input type="hidden" id="user-id" value="<?php echo get_current_user_id(); ?>">
    <input type="hidden" id="username" value="<?php echo htmlspecialchars(get_current_username()); ?>">

    <script src="js/chat1.js"></script>
</body>

</html>