<?php
include 'header.php';

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

// Fetch all settings
$stmt = $pdo->query("SELECT * FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $settings_to_update = [
            'site_name' => $_POST['site_name'] ?? '',
            'manual_bank_name' => $_POST['manual_bank_name'] ?? '',
            'manual_account_number' => $_POST['manual_account_number'] ?? '',
            'manual_account_name' => $_POST['manual_account_name'] ?? ''
        ];

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = :value");

        foreach ($settings_to_update as $key => $value) {
            $stmt->execute([':key' => $key, ':value' => $value]);
        }

        $pdo->commit();
        $feedback = ['message' => 'Settings have been saved successfully.', 'type' => 'success'];
        // Re-fetch settings
        $stmt = $pdo->query("SELECT * FROM settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    } catch (Exception $e) {
        $pdo->rollBack();
        $feedback = ['message' => 'An error occurred: ' . $e->getMessage(), 'type' => 'errors'];
    }
}

// Fetch current admin's details
$admin_id = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<h2>System Settings &amp; Profile</h2>

<?php if ($feedback['message']): ?>
    <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
<?php endif; ?>

<div class="dashboard-widgets">
    <div class="widget">
        <h3>Site Configuration</h3>
        <form method="post">
            <div class="form-group">
                <label for="site_name">Site Name</label>
                <input type="text" name="site_name" id="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'Billpoint'); ?>" required>
            </div>
            <hr>
            <h4>Manual Deposit Account</h4>
            <p>Enter the bank details you want users to see for manual payments.</p>
            <div class="form-group">
                <label for="manual_bank_name">Bank Name</label>
                <input type="text" name="manual_bank_name" id="manual_bank_name" value="<?php echo htmlspecialchars($settings['manual_bank_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="manual_account_number">Account Number</label>
                <input type="text" name="manual_account_number" id="manual_account_number" value="<?php echo htmlspecialchars($settings['manual_account_number'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="manual_account_name">Account Name</label>
                <input type="text" name="manual_account_name" id="manual_account_name" value="<?php echo htmlspecialchars($settings['manual_account_name'] ?? ''); ?>">
            </div>

            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
    <div class="widget">
        <h3>Admin Profile</h3>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($admin['username']); ?></p>
        <p><strong>Member Since:</strong> <?php echo htmlspecialchars(date('F j, Y', strtotime($admin['created_at']))); ?></p>
        <a href="edit-admin.php?id=<?php echo $admin_id; ?>" class="btn btn-secondary">Change Password</a>
    </div>
</div>

<?php include 'footer.php'; ?>
