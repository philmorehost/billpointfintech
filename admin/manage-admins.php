<?php
include 'header.php';

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];
$current_admin_id = $_SESSION['admin_id'];

// Handle form submissions for add, edit, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_admin') {
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            if (empty($username) || empty($password)) {
                throw new Exception("Username and password are required.");
            }
            if (strlen($password) < 8) {
                throw new Exception("Password must be at least 8 characters long.");
            }

            $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            $feedback = ['message' => 'New administrator created successfully.', 'type' => 'success'];

        } elseif ($action === 'delete_admin') {
            $admin_id_to_delete = $_POST['admin_id'];
            // Prevent admin from deleting themselves
            if ($admin_id_to_delete == $current_admin_id) {
                throw new Exception("You cannot delete your own account.");
            }
            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->execute([$admin_id_to_delete]);
            $feedback = ['message' => 'Administrator account has been deleted.', 'type' => 'success'];
        }
    } catch (Exception $e) {
        $feedback = ['message' => 'An error occurred: ' . $e->getMessage(), 'type' => 'errors'];
    }
}


$stmt = $pdo->query("SELECT id, username, created_at FROM admins ORDER BY created_at DESC");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Manage Administrators</h2>

<?php if ($feedback['message']): ?>
    <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
<?php endif; ?>

<div class="dashboard-widgets">
    <div class="widget">
        <h3>Add New Administrator</h3>
        <form method="post">
            <input type="hidden" name="action" value="add_admin">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Create Administrator</button>
        </form>
    </div>
    <div class="widget">
        <h3>Current Administrators</h3>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Username</th><th>Created</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                        <td><?php echo htmlspecialchars(date('M j, Y', strtotime($admin['created_at']))); ?></td>
                        <td>
                            <a href="edit-admin.php?id=<?php echo $admin['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                            <?php if ($admin['id'] != $current_admin_id): // Can't delete self ?>
                            <form method="post" onsubmit="return confirm('Are you sure you want to delete this admin?');" style="display:inline;">
                                <input type="hidden" name="action" value="delete_admin">
                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
