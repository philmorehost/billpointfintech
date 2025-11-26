<?php
require_once '../includes/bootstrap.php';
require_once '../includes/admin_check.php';

$service_id = $_GET['id'] ?? 0;
if (!$service_id) {
    header("Location: services.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if (!$service) {
    header("Location: services.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Service - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Edit Service</h2>
        <form action="services.php" method="post">
            <input type="hidden" name="action" value="update_service">
            <input type="hidden" name="id" value="<?php echo $service['id']; ?>">
            <?php generate_csrf_token(); ?>
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($service['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="url">URL</label>
                <input type="text" id="url" name="url" value="<?php echo htmlspecialchars($service['url']); ?>" required>
            </div>
            <div class="form-group">
                <label for="icon">Icon</label>
                <input type="text" id="icon" name="icon" value="<?php echo htmlspecialchars($service['icon']); ?>">
            </div>
            <div class="form-group">
                <label for="is_active">Status</label>
                <select id="is_active" name="is_active" required>
                    <option value="1" <?php if ($service['is_active']) echo 'selected'; ?>>Active</option>
                    <option value="0" <?php if (!$service['is_active']) echo 'selected'; ?>>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn">Update Service</button>
        </form>
    </div>
</body>
</html>
