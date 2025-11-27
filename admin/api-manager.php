<?php
include 'header.php';

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vtu_api_key = $_POST['vtu_api_key'] ?? '';

    try {
        if (!empty($vtu_api_key)) {
            // Corrected SQL execution: Only one parameter is needed for the VALUES() clause.
            $stmt = $pdo->prepare(
                "INSERT INTO settings (setting_key, setting_value) VALUES ('vtu_api_key', ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            );
            $stmt->execute([$vtu_api_key]);
            $feedback = ['message' => 'VTU API Key has been saved successfully.', 'type' => 'success'];
        } else {
             // We allow submitting a blank form without an error, as the placeholder suggests.
             // Only provide an error if the user intended to save but left it blank.
             // For simplicity, we'll just show a success message that nothing changed.
             $feedback = ['message' => 'No new API key was entered. The existing key remains unchanged.', 'type' => 'success'];
        }
    } catch (Exception $e) {
        $feedback = ['message' => 'An error occurred: ' . $e->getMessage(), 'type' => 'errors'];
    }
}

// Fetch current setting to display in the form
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'vtu_api_key'");
$vtu_api_key_current = $stmt->fetchColumn();

?>

<h2>VTU API Manager</h2>

<?php if ($feedback['message']): ?>
    <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
<?php endif; ?>

<div class="widget">
    <h3>Datagifting.com.ng API Key</h3>
    <form method="post">
        <div class="form-group">
            <label for="vtu_api_key">API Key</label>
            <input type="password" name="vtu_api_key" id="vtu_api_key" placeholder="Leave blank to keep current key">
            <?php if ($vtu_api_key_current): ?>
                <small>Current Key: ************<?php echo htmlspecialchars(substr($vtu_api_key_current, -4)); ?></small>
            <?php else: ?>
                <small>No API key is currently set.</small>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary">Save API Key</button>
    </form>
</div>

<?php include 'footer.php'; ?>
