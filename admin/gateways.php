<?php
include 'header.php';

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paystack_public_key = $_POST['paystack_public_key'] ?? '';
    $paystack_secret_key = $_POST['paystack_secret_key'] ?? '';

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

        // Only update keys if they are not empty, to avoid overwriting with blanks
        if (!empty($paystack_public_key)) {
            $stmt->execute(['paystack_public_key', $paystack_public_key]);
        }
        if (!empty($paystack_secret_key)) {
            $stmt->execute(['paystack_secret_key', $paystack_secret_key]);
        }

        $pdo->commit();
        $feedback = ['message' => 'Gateway settings have been saved.', 'type' => 'success'];
    } catch (Exception $e) {
        $pdo->rollBack();
        $feedback = ['message' => 'An error occurred: ' . $e->getMessage(), 'type' => 'errors'];
    }
}

// Fetch current settings to display in the form
$stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'paystack_%'");
$gateway_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

?>

<h2>Payment Gateway Settings</h2>

<?php if ($feedback['message']): ?>
    <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
<?php endif; ?>

<div class="widget">
    <h3>Paystack Configuration</h3>
    <form method="post">
        <div class="form-group">
            <label for="paystack_public_key">Paystack Public Key</label>
            <input type="password" name="paystack_public_key" id="paystack_public_key" placeholder="Leave blank to keep current key">
            <small>Current Key: ************<?php echo substr($gateway_settings['paystack_public_key'] ?? '', -4); ?></small>
        </div>
        <div class="form-group">
            <label for="paystack_secret_key">Paystack Secret Key</label>
            <input type="password" name="paystack_secret_key" id="paystack_secret_key" placeholder="Leave blank to keep current key">
             <small>Current Key: ************<?php echo substr($gateway_settings['paystack_secret_key'] ?? '', -4); ?></small>
        </div>
        <button type="submit" class="btn btn-primary">Save Keys</button>
    </form>
</div>

<?php include 'footer.php'; ?>
