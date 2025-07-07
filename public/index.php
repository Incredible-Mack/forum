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
</head>

<body>
    <div class="top-nav">
        <div class="nav-left">
            <div class="logo">GroupChat</div>
            <div class="room-dropdown">
                <button class="dropdown-toggle" id="room-dropdown-toggle">
                    <span id="current-room">Select a room</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="dropdown-menu room-menu" id="room-dropdown-menu">
                    <div class="dropdown-header">Chat Rooms</div>
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
            <div class="user-count">
                <span id="online-count">0</span> online
            </div>
            <div id="user-list" class="d-none"></div>

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

    <input type="hidden" id="user-id" value="<?php echo get_current_user_id(); ?>">
    <input type="hidden" id="username" value="<?php echo htmlspecialchars(get_current_username()); ?>">

    <script src="js/chat.js"></script>
</body>

</html>