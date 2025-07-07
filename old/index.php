<?php
require_once 'config.php';
$page_title = "Dashboard";

// Get user's groups
$groups_stmt = $conn->prepare("
    SELECT g.group_id, g.group_name, g.created_at 
    FROM c_groups g
    JOIN group_members gm ON g.group_id = gm.group_id
    WHERE gm.user_id = ?
    ORDER BY g.created_at DESC
");
$groups_stmt->bind_param("i", $_SESSION['user_id']);
$groups_stmt->execute();
$groups_result = $groups_stmt->get_result();

// Get recent threads from user's groups
$threads_stmt = $conn->prepare("
    SELECT t.thread_id, t.title, t.created_at, g.group_name, u.username
    FROM threads t
    JOIN c_groups g ON t.group_id = g.group_id
    JOIN users u ON t.created_by = u.user_id
    WHERE t.group_id IN (
        SELECT group_id FROM group_members WHERE user_id = ?
    )
    ORDER BY t.created_at DESC
    LIMIT 5
");
$threads_stmt->bind_param("i", $_SESSION['user_id']);
$threads_stmt->execute();
$threads_result = $threads_stmt->get_result();

// Get recent messages from user's threads
$messages_stmt = $conn->prepare("
    SELECT m.message_id, m.message, m.created_at, t.title as thread_title, 
           g.group_name, u.username, u.user_id as sender_id
    FROM messages m
    JOIN threads t ON m.thread_id = t.thread_id
    JOIN c_groups g ON t.group_id = g.group_id
    JOIN users u ON m.user_id = u.user_id
    WHERE t.thread_id IN (
        SELECT thread_id FROM threads WHERE group_id IN (
            SELECT group_id FROM group_members WHERE user_id = ?
        )
    )
    ORDER BY m.created_at DESC
    LIMIT 5
");
$messages_stmt->bind_param("i", $_SESSION['user_id']);
$messages_stmt->execute();
$messages_result = $messages_stmt->get_result();

require_once 'header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
    </div>

    <div class="row">
        <!-- Groups Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Your Groups</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $groups_result->num_rows ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Threads Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active Threads</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $threads_result->num_rows ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-chat-left-text fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Recent Messages</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $messages_result->num_rows ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-envelope fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Groups -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Your Groups</h6>
                </div>
                <div class="card-body">
                    <?php if ($groups_result->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while ($group = $groups_result->fetch_assoc()): ?>
                        <a href="group.php?id=<?= $group['group_id'] ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?= htmlspecialchars($group['group_name']) ?></h5>
                                <small><?= date('M j, Y', strtotime($group['created_at'])) ?></small>
                            </div>
                        </a>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">You're not a member of any groups yet.</p>
                    <a href="groups.php" class="btn btn-primary">Join or Create a Group</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Threads -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Threads</h6>
                </div>
                <div class="card-body">
                    <?php if ($threads_result->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while ($thread = $threads_result->fetch_assoc()): ?>
                        <a href="thread.php?id=<?= $thread['thread_id'] ?>"
                            class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?= htmlspecialchars($thread['title']) ?></h5>
                                <small><?= date('M j, Y', strtotime($thread['created_at'])) ?></small>
                            </div>
                            <p class="mb-1">In <?= htmlspecialchars($thread['group_name']) ?> by
                                <?= htmlspecialchars($thread['username']) ?></p>
                        </a>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">No recent threads found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Messages -->
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Messages</h6>
                </div>
                <div class="card-body">
                    <?php if ($messages_result->num_rows > 0): ?>
                    <div class="chat-container">
                        <?php while ($message = $messages_result->fetch_assoc()): ?>
                        <div class="message <?= $message['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received' ?>">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong><?= htmlspecialchars($message['username']) ?></strong>
                                <small
                                    class="message-time"><?= date('M j, g:i a', strtotime($message['created_at'])) ?></small>
                            </div>
                            <p class="mb-1"><?= htmlspecialchars($message['message']) ?></p>
                            <small class="d-block">In <?= htmlspecialchars($message['thread_title']) ?>
                                (<?= htmlspecialchars($message['group_name']) ?>)</small>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">No recent messages found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>