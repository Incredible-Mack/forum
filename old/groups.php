<?php
require_once 'config.php';
$page_title = "Groups";

$errors = [];
$success = '';

// Handle group creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_group'])) {
    $group_name = sanitize($_POST['group_name']);

    if (empty($group_name)) {
        $errors[] = "Group name is required";
    } elseif (strlen($group_name) < 3) {
        $errors[] = "Group name must be at least 3 characters";
    }

    if (empty($errors)) {
        $conn->begin_transaction();

        try {
            // Create the group
            $stmt = $conn->prepare("INSERT INTO groups (group_name, created_by) VALUES (?, ?)");
            $stmt->bind_param("si", $group_name, $_SESSION['user_id']);
            $stmt->execute();
            $group_id = $conn->insert_id;

            // Add creator as member
            $stmt = $conn->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $group_id, $_SESSION['user_id']);
            $stmt->execute();

            $conn->commit();
            $success = "Group created successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Failed to create group: " . $e->getMessage();
        }
    }
}

// Get all groups the user is a member of
$user_groups_stmt = $conn->prepare("
    SELECT g.group_id, g.group_name, g.created_at, 
           (SELECT COUNT(*) FROM group_members WHERE group_id = g.group_id) as member_count
    FROM c_groups g
    JOIN group_members gm ON g.group_id = gm.group_id
    WHERE gm.user_id = ?
    ORDER BY g.created_at DESC
");
$user_groups_stmt->bind_param("i", $_SESSION['user_id']);
$user_groups_stmt->execute();
$user_groups_result = $user_groups_stmt->get_result();

// Get other public groups (for demo purposes, we're showing all groups)
$other_groups_stmt = $conn->prepare("
    SELECT g.group_id, g.group_name, g.created_at, 
           (SELECT COUNT(*) FROM group_members WHERE group_id = g.group_id) as member_count,
           EXISTS(SELECT 1 FROM group_members WHERE group_id = g.group_id AND user_id = ?) as is_member
    FROM c_groups g
    WHERE g.group_id NOT IN (
        SELECT group_id FROM group_members WHERE user_id = ?
    )
    ORDER BY g.created_at DESC
");
$other_groups_stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$other_groups_stmt->execute();
$other_groups_result = $other_groups_stmt->get_result();

require_once 'header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Groups</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
            <i class="bi bi-plus-circle"></i> Create Group
        </button>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
        <p class="mb-0"><?= $error ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success">
        <p class="mb-0"><?= $success ?></p>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Your Groups -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Your Groups</h6>
                </div>
                <div class="card-body">
                    <?php if ($user_groups_result->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while ($group = $user_groups_result->fetch_assoc()): ?>
                        <a href="group.php?id=<?= $group['group_id'] ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?= htmlspecialchars($group['group_name']) ?></h5>
                                <span class="badge bg-primary rounded-pill"><?= $group['member_count'] ?> members</span>
                            </div>
                            <small class="text-muted">Created
                                <?= date('M j, Y', strtotime($group['created_at'])) ?></small>
                        </a>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">You're not a member of any groups yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Other Groups -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Other Groups</h6>
                </div>
                <div class="card-body">
                    <?php if ($other_groups_result->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while ($group = $other_groups_result->fetch_assoc()): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?= htmlspecialchars($group['group_name']) ?></h5>
                                <span class="badge bg-secondary rounded-pill"><?= $group['member_count'] ?>
                                    members</span>
                            </div>
                            <small class="text-muted">Created
                                <?= date('M j, Y', strtotime($group['created_at'])) ?></small>
                            <div class="mt-2">
                                <?php if ($group['is_member']): ?>
                                <span class="badge bg-success">Member</span>
                                <?php else: ?>
                                <form method="POST" action="join_group.php" class="d-inline">
                                    <input type="hidden" name="group_id" value="<?= $group['group_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">Join Group</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">No other groups available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Group Modal -->
<div class="modal fade" id="createGroupModal" tabindex="-1" aria-labelledby="createGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createGroupModalLabel">Create New Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="group_name" class="form-label">Group Name</label>
                        <input type="text" class="form-control" id="group_name" name="group_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="create_group" class="btn btn-primary">Create Group</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>