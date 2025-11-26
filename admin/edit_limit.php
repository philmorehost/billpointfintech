<?php
require_once '../includes/bootstrap.php';
require_once 'includes/auth_check.php';

$limit_id = $_GET['id'] ?? 0;
if (!$limit_id) {
    header("Location: transaction_limits.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM transaction_limits WHERE id = ?");
$stmt->execute([$limit_id]);
$limit = $stmt->fetch();

if (!$limit) {
    header("Location: transaction_limits.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaction Limit - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Edit Transaction Limit</h2>
        <form action="transaction_limits.php" method="post">
            <?php generate_csrf_token(); ?>
            <input type="hidden" name="action" value="update_limit">
            <input type="hidden" name="id" value="<?php echo $limit['id']; ?>">
            <div class="form-group">
                <label for="target_id">Target ID</label>
                <input type="text" id="target_id" name="target_id" value="<?php echo htmlspecialchars($limit['target_id']); ?>" required>
            </div>
            <div class="form-group">
                <label for="max_count">Max Count</label>
                <input type="number" id="max_count" name="max_count" value="<?php echo htmlspecialchars($limit['max_count']); ?>" required>
            </div>
            <div class="form-group">
                <label for="time_frame_seconds">Time Frame (s)</label>
                <input type="number" id="time_frame_seconds" name="time_frame_seconds" value="<?php echo htmlspecialchars($limit['time_frame_seconds']); ?>" required>
            </div>
            <div class="form-group">
                <label for="is_whitelisted">Whitelisted</label>
                <select id="is_whitelisted" name="is_whitelisted" required>
                    <option value="0" <?php if (!$limit['is_whitelisted']) echo 'selected'; ?>>No</option>
                    <option value="1" <?php if ($limit['is_whitelisted']) echo 'selected'; ?>>Yes</option>
                </select>
            </div>
            <button type="submit" class="btn">Update Limit</button>
        </form>
    </div>
</body>
</html>
