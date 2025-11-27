<?php
include 'header.php';

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vtu_api_key = $_POST['vtu_api_key'] ?? '';

    try {
        if (!empty($vtu_api_key)) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('vtu_api_key', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute(['vtu_api_key', $vtu_api_key]);
            $feedback = ['message' => 'VTU API Key has been saved successfully.', 'type' => 'success'];
        } else {
            $feedback = ['message' => 'API key cannot be empty.', 'type' => 'errors'];
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
            <small>Current Key: ************<?php echo substr($vtu_api_key_current ?? '', -4); ?></small>
        </div>
        <button type="submit" class="btn btn-primary">Save API Key</button>
    </form>
</div>

<?php include 'footer.php'; ?>
