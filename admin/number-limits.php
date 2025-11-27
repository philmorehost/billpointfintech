<?php
include 'header.php';

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service = $_POST['service'] ?? '';
    $recipient = trim($_POST['recipient'] ?? '');
    $limit = filter_input(INPUT_POST, 'limit', FILTER_VALIDATE_FLOAT);

    if (!empty($service) && !empty($recipient) && $limit >= 0) {
        try {
            // This setting is actually a global setting, not per number
            // We'll store it in the 'settings' table for simplicity
            $setting_key = "limit_{$service}_number";
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute([$setting_key, $limit]);
            $feedback = ['message' => 'Per-number daily limit for ' . ucfirst($service) . ' has been set.', 'type' => 'success'];
        } catch (Exception $e) {
            $feedback = ['message' => 'An error occurred: ' . $e->getMessage(), 'type' => 'errors'];
        }
    } else {
        $feedback = ['message' => 'Invalid input. Please check all fields.', 'type' => 'errors'];
    }
}

$stmt = $pdo->query("SELECT slug, name FROM services");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'limit_%_number'");
$limits = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<h2>Set Per-Number Daily Limits</h2>

<?php if ($feedback['message']): ?>
    <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
<?php endif; ?>

<div class="widget">
    <h3>Global Daily Limit Per Recipient</h3>
    <p>Set the maximum total amount that can be sent to a single recipient number (e.g., phone, meter) for a service in one day. Set to 0 for no limit.</p>
    <form method="post">
        <div class="form-group">
            <label for="service">Select Service</label>
            <select name="service" id="service" class="form-control" required>
                <?php foreach ($services as $service): ?>
                    <option value="<?php echo htmlspecialchars($service['slug']); ?>">
                        <?php echo htmlspecialchars($service['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="limit">Daily Limit (₦)</label>
            <input type="number" name="limit" id="limit" class="form-control" step="0.01" required value="0">
        </div>
        <button type="submit" class="btn btn-primary">Set Global Limit</button>
    </form>
    <hr>
    <h4>Current Limits:</h4>
    <ul>
        <?php foreach ($limits as $key => $value): ?>
            <li><strong><?php echo htmlspecialchars(ucfirst(str_replace(['limit_', '_number'], '', $key))); ?>:</strong> ₦<?php echo htmlspecialchars(number_format($value, 2)); ?> / day</li>
        <?php endforeach; ?>
    </ul>
</div>

<?php include 'footer.php'; ?>
