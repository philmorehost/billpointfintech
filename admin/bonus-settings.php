<?php
include 'header.php';

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $settings_to_update = [
            'bonus_on_login' => $_POST['bonus_on_login'],
            'bonus_on_transaction' => $_POST['bonus_on_transaction'],
            'bonus_conversion_rate' => $_POST['bonus_conversion_rate']
        ];

        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        foreach ($settings_to_update as $key => $value) {
            $stmt->execute([$value, $key]);
        }

        $pdo->commit();
        $feedback = ['message' => 'Bonus settings updated successfully.', 'type' => 'success'];
    } catch (Exception $e) {
        $pdo->rollBack();
        $feedback = ['message' => 'An error occurred: ' . $e->getMessage(), 'type' => 'errors'];
    }
}

$stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'bonus_%'");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<h2>Bonus System Settings</h2>

<?php if ($feedback['message']): ?>
    <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
<?php endif; ?>

<div class="widget">
    <h3>Configure Bonus Points</h3>
    <form method="post">
        <div class="form-group">
            <label>Points for Daily Login</label>
            <input type="number" name="bonus_on_login" value="<?php echo htmlspecialchars($settings['bonus_on_login'] ?? 0); ?>" required>
        </div>
        <div class="form-group">
            <label>Points per Transaction</label>
            <input type="number" name="bonus_on_transaction" value="<?php echo htmlspecialchars($settings['bonus_on_transaction'] ?? 0); ?>" required>
        </div>
        <div class="form-group">
            <label>Conversion Rate (Points per ₦1)</label>
            <input type="number" name="bonus_conversion_rate" value="<?php echo htmlspecialchars($settings['bonus_conversion_rate'] ?? 100); ?>" required>
        </div>
        <button type="submit">Save Settings</button>
    </form>
</div>

<?php include 'footer.php'; ?>
