<?php
session_start();

// A simple check to ensure the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
</head>
<?php
require_once '../includes/database.php';
require_once '../includes/flash_messages.php';
require_once '../includes/csrf.php';
$csrf_token = generate_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf_token()) {
        die('CSRF validation failed.');
    }
    if ($_POST['action'] === 'save_api_key') {
        $api_key = $_POST['datagifting_api_key'];

        $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES ('datagifting_api_key', ?) ON DUPLICATE KEY UPDATE value = ?");
        if ($stmt->execute([$api_key, $api_key])) {
            set_flash_message('success', 'API Key saved successfully.');
        } else {
            set_flash_message('error', 'Failed to save API Key.');
        }
        header('Location: index.php');
        exit();
    }
}

$stmt = $pdo->query("SELECT value FROM settings WHERE name = 'datagifting_api_key'");
$api_key = $stmt->fetchColumn();
?>
<body>
    <h1>Welcome to the Admin Dashboard</h1>
    <a href="../logout.php">Logout</a>

    <div class="admin-container">
        <h2>Settings</h2>
        <?php display_flash_message(); ?>
        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="save_api_key">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group">
                <label for="datagifting_api_key">Datagifting API Key</label>
                <input type="text" id="datagifting_api_key" name="datagifting_api_key" value="<?php echo htmlspecialchars($api_key); ?>" style="width: 400px;">
            </div>
            <button type="submit" class="btn">Save</button>
        </form>
    </div>
</body>
</html>
