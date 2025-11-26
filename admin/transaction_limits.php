<?php
require_once '../includes/bootstrap.php';
require_once '../includes/admin_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
        header('Location: transaction_limits.php');
        exit();
    }
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_limit') {
            $stmt = $pdo->prepare("INSERT INTO transaction_limits (target_id, max_count, time_frame_seconds, is_whitelisted) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['target_id'], $_POST['max_count'], $_POST['time_frame_seconds'], $_POST['is_whitelisted']]);
        } elseif ($_POST['action'] === 'update_limit') {
            $stmt = $pdo->prepare("UPDATE transaction_limits SET target_id = ?, max_count = ?, time_frame_seconds = ?, is_whitelisted = ? WHERE id = ?");
            $stmt->execute([$_POST['target_id'], $_POST['max_count'], $_POST['time_frame_seconds'], $_POST['is_whitelisted'], $_POST['id']]);
        }
    }
    header("Location: transaction_limits.php");
    exit();
}

$stmt = $pdo->query("SELECT * FROM transaction_limits ORDER BY target_id");
$limits = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Limits - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Transaction Limits</h2>
        <table>
            <thead>
                <tr>
                    <th>Target ID</th>
                    <th>Max Count</th>
                    <th>Time Frame (s)</th>
                    <th>Whitelisted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($limits as $limit): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($limit['target_id']); ?></td>
                        <td><?php echo htmlspecialchars($limit['max_count']); ?></td>
                        <td><?php echo htmlspecialchars($limit['time_frame_seconds']); ?></td>
                        <td><?php echo $limit['is_whitelisted'] ? 'Yes' : 'No'; ?></td>
                        <td>
                            <a href="edit_limit.php?id=<?php echo $limit['id']; ?>">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Add New Limit</h3>
        <form action="transaction_limits.php" method="post">
            <input type="hidden" name="action" value="add_limit">
            <?php generate_csrf_token(); ?>
            <div class="form-group">
                <label for="target_id">Target ID</label>
                <input type="text" id="target_id" name="target_id" required>
            </div>
            <div class="form-group">
                <label for="max_count">Max Count</label>
                <input type="number" id="max_count" name="max_count" required>
            </div>
            <div class="form-group">
                <label for="time_frame_seconds">Time Frame (s)</label>
                <input type="number" id="time_frame_seconds" name="time_frame_seconds" required>
            </div>
            <div class="form-group">
                <label for="is_whitelisted">Whitelisted</label>
                <select id="is_whitelisted" name="is_whitelisted" required>
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <button type="submit" class="btn">Add Limit</button>
        </form>
    </div>
</body>
</html>
