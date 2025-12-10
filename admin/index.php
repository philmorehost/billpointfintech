<?php
$page_title = 'Admin Dashboard';
require_once '../includes/admin_header.php'; // Use the new admin header

// --- Data Fetching for Dashboard ---
try {
    // Total Users
    $total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();

    // Pending P2P Transfers
    $pending_p2p = $pdo->query("SELECT COUNT(*) FROM p2p_transfers WHERE status = 'pending'")->fetchColumn();

    // Pending Loan Requests
    $pending_loans = $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'pending'")->fetchColumn();

    // Total Revenue (simplified example: sum of all completed transaction amounts)
    $total_revenue = $pdo->query("SELECT SUM(amount) FROM transactions WHERE status = 'completed' AND type NOT LIKE '%credit%'")->fetchColumn();

} catch (PDOException $e) {
    // Set defaults if tables don't exist yet
    $total_users = 0;
    $pending_p2p = 0;
    $pending_loans = 0;
    $total_revenue = 0;
    set_flash_message('error', "Could not fetch all dashboard stats. A database table might be missing.");
}

// --- Settings Form Handler ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    if (validate_csrf_token()) {
        foreach ($_POST['settings'] as $name => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->execute([$name, $value, $value]);
        }
        // Clear cache
        @unlink(__DIR__ . '/../cache/settings.json');
        set_flash_message('success', 'Settings saved successfully.');
        redirect('index.php');
    } else {
        set_flash_message('error', 'CSRF validation failed.');
    }
}

// Fetch all settings for the form
$settings = $pdo->query("SELECT name, value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="admin-header">
    <h1>Dashboard</h1>
    <p>An overview of your Billpoint application.</p>
</div>

<!-- Statistic Cards -->
<div class="stat-cards">
    <div class="stat-card">
        <div class="title">Total Users</div>
        <div class="value"><?php echo number_format($total_users); ?></div>
    </div>
    <div class="stat-card">
        <div class="title">Pending Approvals</div>
        <div class="value"><?php echo number_format($pending_p2p + $pending_loans); ?></div>
    </div>
    <div class="stat-card">
        <div class="title">Total Revenue (NGN)</div>
        <div class="value small">₦<?php echo number_format($total_revenue, 2); ?></div>
    </div>
</div>

<!-- Settings Box -->
<div class="content-box">
    <h2>Application Settings</h2>
    <?php display_flash_message(); ?>

    <form action="index.php" method="POST">
        <input type="hidden" name="action" value="save_settings">
        <?php csrf_field(); ?>

        <h4>API Keys</h4>
        <div class="form-group">
            <label for="datagifting_api_key">Datagifting API Key</label>
            <input type="password" class="form-control" name="settings[datagifting_api_key]" value="<?php echo htmlspecialchars($settings['datagifting_api_key'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="paystack_secret_key">Paystack Secret Key</label>
            <input type="password" class="form-control" name="settings[paystack_secret_key]" value="<?php echo htmlspecialchars($settings['paystack_secret_key'] ?? ''); ?>">
        </div>
         <div class="form-group">
            <label for="monnify_api_key">Monnify API Key</label>
            <input type="password" class="form-control" name="settings[monnify_api_key]" value="<?php echo htmlspecialchars($settings['monnify_api_key'] ?? ''); ?>">
        </div>
         <div class="form-group">
            <label for="juicyway_api_key">JuicyWay API Key</label>
            <input type="password" class="form-control" name="settings[juicyway_api_key]" value="<?php echo htmlspecialchars($settings['juicyway_api_key'] ?? ''); ?>">
        </div>
         <div class="form-group">
            <label for="flutterwave_api_key">Flutterwave API Key</label>
            <input type="password" class="form-control" name="settings[flutterwave_api_key]" value="<?php echo htmlspecialchars($settings['flutterwave_api_key'] ?? ''); ?>">
        </div>
         <div class="form-group">
            <label for="reloadly_client_id">Reloadly Client ID</label>
            <input type="password" class="form-control" name="settings[reloadly_client_id]" value="<?php echo htmlspecialchars($settings['reloadly_client_id'] ?? ''); ?>">
        </div>

        <h4>System Settings</h4>
        <div class="form-group">
            <label for="loan_duration_days">Loan Duration (Days)</label>
            <input type="number" class="form-control" name="settings[loan_duration_days]" value="<?php echo htmlspecialchars($settings['loan_duration_days'] ?? '30'); ?>">
        </div>

        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
