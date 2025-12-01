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
        $monnify_api_key = $_POST['monnify_api_key'] ?? '';
        $monnify_secret_key = $_POST['monnify_secret_key'] ?? '';
        $juicyway_api_key = $_POST['juicyway_api_key'] ?? '';
        $juicyway_secret_key = $_POST['juicyway_secret_key'] ?? '';
        $flutterwave_api_key = $_POST['flutterwave_api_key'] ?? '';
        $flutterwave_secret_key = $_POST['flutterwave_secret_key'] ?? '';

        $dg_success = save_setting($pdo, 'datagifting_api_key', $datagifting_key);
        $ps_success = save_setting($pdo, 'paystack_secret_key', $paystack_key);
        $mn_api_success = save_setting($pdo, 'monnify_api_key', $monnify_api_key);
        $mn_secret_success = save_setting($pdo, 'monnify_secret_key', $monnify_secret_key);
        $jw_api_success = save_setting($pdo, 'juicyway_api_key', $juicyway_api_key);
        $jw_secret_success = save_setting($pdo, 'juicyway_secret_key', $juicyway_secret_key);
        $fw_api_success = save_setting($pdo, 'flutterwave_api_key', $flutterwave_api_key);
        $fw_secret_success = save_setting($pdo, 'flutterwave_secret_key', $flutterwave_secret_key);

        if ($dg_success && $ps_success && $mn_api_success && $mn_secret_success && $jw_api_success && $jw_secret_success && $fw_api_success && $fw_secret_success) {
            // Clear the settings cache
            $settings_cache_file = __DIR__ . '/../cache/settings.json';
            if (file_exists($settings_cache_file)) {
                unlink($settings_cache_file);
            }
            set_flash_message('success', 'Settings saved successfully.');
        } else {
            set_flash_message('error', 'Failed to save one or more settings.');
        }

        header('Location: index.php');
        exit();
    }
}

// Fetch all settings at once
$settings_stmt = $pdo->query("SELECT name, value FROM settings");
$settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$api_key = $settings['datagifting_api_key'] ?? '';
$paystack_key = $settings['paystack_secret_key'] ?? '';
$monnify_api_key = $settings['monnify_api_key'] ?? '';
$monnify_secret_key = $settings['monnify_secret_key'] ?? '';
$juicyway_api_key = $settings['juicyway_api_key'] ?? '';
$juicyway_secret_key = $settings['juicyway_secret_key'] ?? '';
$flutterwave_api_key = $settings['flutterwave_api_key'] ?? '';
$flutterwave_secret_key = $settings['flutterwave_secret_key'] ?? '';


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
    <a href="../logout.php">Logout</a> |
    <a href="users.php">Users</a> |
    <a href="plans.php">Service Plans</a> |
    <a href="support.php">Support</a> |
    <a href="p2p_transfers.php">P2P Transfers</a> |
    <a href="loans.php">Loan Management</a> |
    <a href="manual_wallet.php">Manual Wallet</a> |
    <a href="risk_management.php">Risk Management</a>

    <div class="admin-container">
        <h2>Settings</h2>
        <?php display_flash_message(); ?>

        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="save_settings">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-group">
                <label for="datagifting_api_key">Datagifting API Key</label>
                <input type="password" id="datagifting_api_key" name="datagifting_api_key" value="<?php echo htmlspecialchars((string)$api_key); ?>">
            </div>

            <div class="form-group">
                <label for="paystack_secret_key">Paystack Secret Key</label>
                <input type="password" id="paystack_secret_key" name="paystack_secret_key" value="<?php echo htmlspecialchars((string)$paystack_key); ?>">
            </div>

            <hr>

            <div class="form-group">
                <label for="monnify_api_key">Monnify API Key</label>
                <input type="password" id="monnify_api_key" name="monnify_api_key" value="<?php echo htmlspecialchars((string)$monnify_api_key); ?>">
            </div>

             <div class="form-group">
                <label for="monnify_secret_key">Monnify Secret Key</label>
                <input type="password" id="monnify_secret_key" name="monnify_secret_key" value="<?php echo htmlspecialchars((string)$monnify_secret_key); ?>">
            </div>

            <hr>

            <div class="form-group">
                <label for="juicyway_api_key">JuicyWay API Key</label>
                <input type="password" id="juicyway_api_key" name="juicyway_api_key" value="<?php echo htmlspecialchars((string)$juicyway_api_key); ?>">
            </div>

             <div class="form-group">
                <label for="juicyway_secret_key">JuicyWay Secret Key</label>
                <input type="password" id="juicyway_secret_key" name="juicyway_secret_key" value="<?php echo htmlspecialchars((string)$juicyway_secret_key); ?>">
            </div>

            <hr>

            <div class="form-group">
                <label for="flutterwave_api_key">Flutterwave API Key</label>
                <input type="password" id="flutterwave_api_key" name="flutterwave_api_key" value="<?php echo htmlspecialchars((string)$flutterwave_api_key); ?>">
            </div>

             <div class="form-group">
                <label for="flutterwave_secret_key">Flutterwave Secret Key</label>
                <input type="password" id="flutterwave_secret_key" name="flutterwave_secret_key" value="<?php echo htmlspecialchars((string)$flutterwave_secret_key); ?>">
            </div>

            <button type="submit" class="btn">Save All Settings</button>
        </form>
    </div>
</body>
</html>
