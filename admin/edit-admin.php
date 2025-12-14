<?php
include 'header.php';

$admin_id_to_edit = $_GET['id'] ?? null;
if (!$admin_id_to_edit) {
    header('Location: manage-admins.php');
    exit;
}

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($password)) {
        $feedback = ['message' => 'Password cannot be empty.', 'type' => 'errors'];
    } elseif (strlen($password) < 8) {
        $feedback = ['message' => 'Password must be at least 8 characters long.', 'type' => 'errors'];
    } elseif ($password !== $password_confirm) {
        $feedback = ['message' => 'Passwords do not match.', 'type' => 'errors'];
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $admin_id_to_edit]);
            $feedback = ['message' => 'Administrator password has been updated successfully.', 'type' => 'success'];
        } catch (Exception $e) {
            $feedback = ['message' => 'An error occurred: ' . $e->getMessage(), 'type' => 'errors'];
        }
    }
}

// Fetch admin data to show who is being edited
$stmt = $pdo->prepare("SELECT username FROM admins WHERE id = ?");
$stmt->execute([$admin_id_to_edit]);
$admin_username = $stmt->fetchColumn();

if (!$admin_username) {
    header('Location: manage-admins.php');
    exit;
}
?>

<h2>Edit Administrator: <?php echo htmlspecialchars($admin_username); ?></h2>

<?php if ($feedback['message']): ?>
    <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
<?php endif; ?>

<div class="widget">
    <h3>Change Password</h3>
    <form method="post">
        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="password_confirm">Confirm New Password</label>
            <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Update Password</button>
        <a href="manage-admins.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include 'footer.php'; ?>
