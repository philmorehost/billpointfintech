<?php
$page_title = 'Admin - Risk Management';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../includes/bootstrap.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (validate_csrf_token()) {
        $action = $_POST['action'];
        if ($action === 'add_limit') {
            $target_id = trim($_POST['target_id']);
            $max_count = (int)$_POST['max_count'];
            $time_frame = (int)$_POST['time_frame']; // in hours

            if (!empty($target_id) && $max_count > 0 && $time_frame > 0) {
                $time_frame_seconds = $time_frame * 3600;
                $stmt = $pdo->prepare("INSERT INTO transaction_limits (target_id, max_count, time_frame_seconds) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE max_count = ?, time_frame_seconds = ?");
                $stmt->execute([$target_id, $max_count, $time_frame_seconds, $max_count, $time_frame_seconds]);
                set_flash_message('success', 'Transaction limit rule saved.');
            } else {
                set_flash_message('error', 'Invalid input for limit rule.');
            }
        } elseif ($action === 'delete_limit') {
            $limit_id = (int)$_POST['limit_id'];
            $stmt = $pdo->prepare("DELETE FROM transaction_limits WHERE id = ?");
            $stmt->execute([$limit_id]);
            set_flash_message('success', 'Limit rule deleted.');
        } elseif ($action === 'toggle_whitelist') {
            $limit_id = (int)$_POST['limit_id'];
            $is_whitelisted = (int)$_POST['is_whitelisted'];
            $stmt = $pdo->prepare("UPDATE transaction_limits SET is_whitelisted = ? WHERE id = ?");
            $stmt->execute([$is_whitelisted, $limit_id]);
            set_flash_message('success', 'Whitelist status updated.');
        }
    }
    header('Location: risk_management.php');
    exit();
}

$limits = $pdo->query("SELECT * FROM transaction_limits ORDER BY created_at DESC")->fetchAll();
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <h1>Admin - Transaction Risk Management</h1>
    <a href="index.php">Dashboard</a> | <a href="../logout.php">Logout</a>

    <div class="admin-container">
        <h2>Add/Update Limit Rule</h2>
        <?php display_flash_message(); ?>
        <form action="risk_management.php" method="POST">
            <input type="hidden" name="action" value="add_limit">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group">
                <label for="target_id">Target ID (Phone Number, Account, etc.)</label>
                <input type="text" name="target_id" required>
            </div>
            <div class="form-group">
                <label for="max_count">Max Transactions</label>
                <input type="number" name="max_count" min="1" required>
            </div>
            <div class="form-group">
                <label for="time_frame">Within Time Frame (Hours)</label>
                <input type="number" name="time_frame" min="1" required>
            </div>
            <button type="submit" class="btn">Save Rule</button>
        </form>
    </div>

    <div class="admin-container" style="margin-top: 20px;">
        <h2>Existing Limit Rules</h2>
        <table class="support-table">
            <thead>
                <tr>
                    <th>Target ID</th>
                    <th>Rule</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($limits as $limit): ?>
                <tr>
                    <td><?php echo htmlspecialchars($limit['target_id']); ?></td>
                    <td><?php echo "Max {$limit['max_count']} times per " . ($limit['time_frame_seconds'] / 3600) . " hour(s)"; ?></td>
                    <td><?php echo $limit['is_whitelisted'] ? '<span class="status-open">Whitelisted</span>' : '<span class="status-closed">Monitored</span>'; ?></td>
                    <td>
                        <form action="risk_management.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="toggle_whitelist">
                            <input type="hidden" name="limit_id" value="<?php echo $limit['id']; ?>">
                            <input type="hidden" name="is_whitelisted" value="<?php echo $limit['is_whitelisted'] ? '0' : '1'; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <button type="submit" class="btn btn-sm"><?php echo $limit['is_whitelisted'] ? 'Disable Whitelist' : 'Whitelist'; ?></button>
                        </form>
                        <form action="risk_management.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete_limit">
                            <input type="hidden" name="limit_id" value="<?php echo $limit['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
