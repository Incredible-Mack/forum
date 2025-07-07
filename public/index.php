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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <div class="chat-container">
        <div class="sidebar">
            <div class="user-info">
                <h3>Welcome, <?php echo htmlspecialchars(get_current_username()); ?></h3>
                <a href="logout.php">Logout</a>
            </div>

            <div class="room-section">
                <h3>Chat Rooms</h3>
                <button id="create-room-btn">Create Room</button>
                <ul id="room-list">
                    <?php foreach ($rooms as $room): ?>
                    <li>
                        <a href="#" class="room-link" data-room-id="<?php echo $room['id']; ?>">
                            <?php echo htmlspecialchars($room['name']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="chat-area">
            <div class="chat-header">
                <h2 id="current-room">Select a room</h2>
                <div id="user-list"></div>
            </div>

            <div id="messages-container" class="messages"></div>

            <div class="message-input">
                <input type="text" id="message-input" placeholder="Type your message...">
                <button id="send-btn">Send</button>
                <label for="file-input" class="file-upload-btn">
                    <input type="file" id="file-input" accept="image/*,.pdf,.txt">
                    📎
                </label>
            </div>
        </div>
    </div>

    <input type="hidden" id="user-id" value="<?php echo get_current_user_id(); ?>">
    <input type="hidden" id="username" value="<?php echo htmlspecialchars(get_current_username()); ?>">

    <script src="js/chat.js"></script>
</body>

</html>