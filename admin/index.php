<?php
session_start();

// A simple check to ensure the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/bootstrap.php';

// --- Helper Function for Saving Settings ---
function save_setting($pdo, $name, $value) {
    $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
    return $stmt->execute([$name, $value, $value]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
        header('Location: index.php');
        exit();
    }

    $action = $_POST['action'];

    if ($action === 'save_settings') {
        $datagifting_key = $_POST['datagifting_api_key'] ?? '';
        $paystack_key = $_POST['paystack_secret_key'] ?? '';

        $dg_success = save_setting($pdo, 'datagifting_api_key', $datagifting_key);
        $ps_success = save_setting($pdo, 'paystack_secret_key', $paystack_key);

        if ($dg_success && $ps_success) {
            set_flash_message('success', 'Settings saved successfully.');
        } else {
            set_flash_message('error', 'Failed to save one or more settings.');
        }

        header('Location: index.php');
        exit();
    }
}

// Fetch current settings
$stmt_dg = $pdo->query("SELECT value FROM settings WHERE name = 'datagifting_api_key'");
$api_key = $stmt_dg->fetchColumn();
$stmt_ps = $pdo->query("SELECT value FROM settings WHERE name = 'paystack_secret_key'");
$paystack_key = $stmt_ps->fetchColumn();

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Settings</title>
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Assuming a shared stylesheet -->
</head>
<body>
    <h1>Welcome to the Admin Dashboard</h1>
    <a href="../logout.php">Logout</a> | <a href="plans.php">Manage Plans</a>

    <div class="admin-container">
        <h2>Settings</h2>
        <?php display_flash_message(); ?>

        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="save_settings">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-group">
                <label for="datagifting_api_key">Datagifting API Key</label>
                <input type="text" id="datagifting_api_key" name="datagifting_api_key" value="<?php echo htmlspecialchars((string)$api_key); ?>">
            </div>

            <div class="form-group">
                <label for="paystack_secret_key">Paystack Secret Key</label>
                <input type="text" id="paystack_secret_key" name="paystack_secret_key" value="<?php echo htmlspecialchars((string)$paystack_key); ?>">
            </div>

            <button type="submit" class="btn">Save All Settings</button>
        </form>
    </div>
</body>
</html>
