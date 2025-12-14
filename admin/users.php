<?php
include 'header.php';

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

// Handle actions: suspend, unsuspend, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = $_POST['user_id'] ?? null;
    $action = $_POST['action'];

    if ($user_id) {
        try {
            switch ($action) {
                case 'suspend':
                    $stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $feedback = ['message' => 'User has been suspended.', 'type' => 'success'];
                    break;
                case 'unsuspend':
                    $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $feedback = ['message' => 'User has been reactivated.', 'type' => 'success'];
                    break;
                case 'delete':
                    // A soft delete is often better, but for now, we do a hard delete
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $feedback = ['message' => 'User has been permanently deleted.', 'type' => 'success'];
                    break;
            }
        } catch (Exception $e) {
            $feedback = ['message' => 'An error occurred: ' . $e->getMessage(), 'type' => 'errors'];
        }
    }
}


$stmt = $pdo->query('SELECT id, full_name, email, phone_number, wallet_balance, status, created_at FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Manage Users</h2>

<?php if ($feedback['message']): ?>
    <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
<?php endif; ?>

<div class="table-wrapper">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Wallet Balance</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                    <td>₦<?php echo htmlspecialchars(number_format($user['wallet_balance'], 2)); ?></td>
                    <td><span class="badge badge-<?php echo htmlspecialchars($user['status']); ?>"><?php echo htmlspecialchars(ucfirst($user['status'])); ?></span></td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle">Actions</button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="edit-user.php?id=<?php echo $user['id']; ?>">Edit</a>
                                <form method="post" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <?php if ($user['status'] === 'active'): ?>
                                        <button type="submit" name="action" value="suspend" class="dropdown-item">Suspend</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="unsuspend" class="dropdown-item">Unsuspend</button>
                                    <?php endif; ?>
                                    <button type="submit" name="action" value="delete" class="dropdown-item text-danger">Delete</button>
                                </form>
                                <a class="dropdown-item" href="impersonate.php?id=<?php echo $user['id']; ?>">Login As User</a>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
