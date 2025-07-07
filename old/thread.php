<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$thread_id = (int)$_GET['id'];

// Get thread info
$thread_stmt = $conn->prepare("
    SELECT t.*, g.group_name, u.username as creator_name
    FROM threads t
    JOIN groups g ON t.group_id = g.group_id
    JOIN users u ON t.created_by = u.user_id
    WHERE t.thread_id = ? AND t.group_id IN (
        SELECT group_id FROM group_members WHERE user_id = ?
    )
");
$thread_stmt->bind_param("ii", $thread_id, $_SESSION['user_id']);
$thread_stmt->execute();
$thread_result = $thread_stmt->get_result();

if ($thread_result->num_rows === 0) {
    redirect('index.php');
}

$thread = $thread_result->fetch_assoc();

// Get messages
$messages_stmt = $conn->prepare("
    SELECT m.*, u.username, u.user_id
    FROM messages m
    JOIN users u ON m.user_id = u.user_id
    WHERE m.thread_id = ?
    ORDER BY m.created_at ASC
");
$messages_stmt->bind_param("i", $thread_id);
$messages_stmt->execute();
$messages_result = $messages_stmt->get_result();

$page_title = $thread['title'];
require_once 'header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($thread['title']) ?></h1>
        <div>
            <span class="badge bg-secondary"><?= htmlspecialchars($thread['group_name']) ?></span>
            <span class="badge bg-info">Created by <?= htmlspecialchars($thread['creator_name']) ?></span>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-body p-0">
                    <div class="chat-container p-3" data-thread-id="<?= $thread_id ?>">
                        <?php while ($message = $messages_result->fetch_assoc()): ?>
                        <div class="message <?= $message['user_id'] == $_SESSION['user_id'] ? 'sent' : 'received' ?>">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong><?= htmlspecialchars($message['username']) ?></strong>
                                <small
                                    class="message-time"><?= date('M j, g:i a', strtotime($message['created_at'])) ?></small>
                            </div>
                            <p class="mb-1"><?= htmlspecialchars($message['message']) ?></p>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="card-footer">
                    <form id="message-form" data-thread-id="<?= $thread_id ?>">
                        <div class="input-group">
                            <input type="text" name="message" class="form-control" placeholder="Type your message..."
                                required>
                            <button class="btn btn-primary" type="submit">Send</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/chat.js"></script>

<?php require_once 'footer.php'; ?>