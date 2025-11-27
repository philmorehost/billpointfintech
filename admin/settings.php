<?php
include 'header.php';

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

// Fetch all settings
$stmt = $pdo->query("SELECT * FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = $_POST['site_name'] ?? '';
    // Add more settings here as they are created

    try {
        // Use INSERT ... ON DUPLICATE KEY UPDATE to simplify saving
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_name', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$site_name]);

        $feedback = ['message' => 'Settings have been saved successfully.', 'type' => 'success'];
        // Re-fetch settings to display updated values
        $stmt = $pdo->query("SELECT * FROM settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {
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
            <!-- More settings fields can be added here -->
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
