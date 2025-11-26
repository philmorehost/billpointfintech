<?php
require_once '../includes/bootstrap.php';
require_once 'includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
        header('Location: services.php');
        exit();
    }
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_service') {
            $stmt = $pdo->prepare("INSERT INTO services (name, url, icon, is_active) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['name'], $_POST['url'], $_POST['icon'], $_POST['is_active']]);
        } elseif ($_POST['action'] === 'update_service') {
            $stmt = $pdo->prepare("UPDATE services SET name = ?, url = ?, icon = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$_POST['name'], $_POST['url'], $_POST['icon'], $_POST['is_active'], $_POST['id']]);
        }
    }
    header("Location: services.php");
    exit();
}

$stmt = $pdo->query("SELECT * FROM services ORDER BY name");
$services = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Manage Services</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>URL</th>
                    <th>Icon</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $service): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($service['name']); ?></td>
                        <td><?php echo htmlspecialchars($service['url']); ?></td>
                        <td><?php echo htmlspecialchars($service['icon']); ?></td>
                        <td><?php echo $service['is_active'] ? 'Active' : 'Inactive'; ?></td>
                        <td>
                            <a href="edit_service.php?id=<?php echo $service['id']; ?>">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Add New Service</h3>
        <form action="services.php" method="post">
            <input type="hidden" name="action" value="add_service">
            <?php generate_csrf_token(); ?>
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="url">URL</label>
                <input type="text" id="url" name="url" required>
            </div>
            <div class="form-group">
                <label for="icon">Icon</label>
                <input type="text" id="icon" name="icon">
            </div>
            <div class="form-group">
                <label for="is_active">Status</label>
                <select id="is_active" name="is_active" required>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn">Add Service</button>
        </form>
    </div>
</body>
</html>
