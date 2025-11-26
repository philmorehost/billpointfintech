<?php
$page_title = 'Application Settings';
require_once 'includes/header.php';

// Fetch current settings
$stmt = $pdo->query("SELECT * FROM settings");
$settings_array = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<div class="settings-container" style="max-width: 800px; margin: auto; background: #fff; padding: 2rem; border-radius: 1rem;">
    <form action="settings_handler.php" method="POST">
        <?php echo generate_csrf_token_input(); ?>
        <div class="form-group">
            <label for="site_title">Site Title</label>
            <input type="text" id="site_title" name="settings[site_title]" value="<?php echo htmlspecialchars($settings_array['site_title'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="datagifting_api_key">Datagifting API Key</label>
            <input type="password" id="datagifting_api_key" name="settings[datagifting_api_key]" value="<?php echo htmlspecialchars($settings_array['datagifting_api_key'] ?? ''); ?>">
        </div>
         <div class="form-group">
            <label for="flutterwave_secret_key">Flutterwave Secret Key</label>
            <input type="password" id="flutterwave_secret_key" name="settings[flutterwave_secret_key]" value="<?php echo htmlspecialchars($settings_array['flutterwave_secret_key'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="flutterwave_secret_hash">Flutterwave Secret Hash</label>
            <input type="password" id="flutterwave_secret_hash" name="settings[flutterwave_secret_hash]" value="<?php echo htmlspecialchars($settings_array['flutterwave_secret_hash'] ?? ''); ?>">
        </div>
        <button type="submit" class="btn-primary">Save Settings</button>
    </form>
</div>
<?php require_once 'includes/footer.php'; ?>
