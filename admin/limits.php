<?php
include 'header.php';

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        foreach ($_POST['limits'] as $service_name => $daily_limit) {
            $daily_limit = (float)$daily_limit;
            $stmt = $pdo->prepare("INSERT INTO transaction_limits (service_name, daily_limit) VALUES (?, ?) ON DUPLICATE KEY UPDATE daily_limit = VALUES(daily_limit)");
            $stmt->execute([$service_name, $daily_limit]);
        }
        $pdo->commit();
        $feedback = ['message' => 'Transaction limits have been updated successfully.', 'type' => 'success'];
    } catch (Exception $e) {
        $pdo->rollBack();
        $feedback = ['message' => 'An error occurred: ' . $e->getMessage(), 'type' => 'errors'];
    }
}

// Fetch all services and their current limits
$stmt = $pdo->query("SELECT slug, name FROM services");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM transaction_limits");
$limits = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

?>

<h2>Transaction Limits</h2>

<?php if ($feedback['message']): ?>
    <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
<?php endif; ?>

<div class="widget">
    <h3>Daily Limits per Service</h3>
    <p>Set the maximum total amount a single user can transact for each service per day. Set to 0 for no limit.</p>
    <form method="post">
        <?php foreach ($services as $service): ?>
            <div class="form-group">
                <label for="limit-<?php echo htmlspecialchars($service['slug']); ?>"><?php echo htmlspecialchars($service['name']); ?></label>
                <input type="number" name="limits[<?php echo htmlspecialchars($service['slug']); ?>]" id="limit-<?php echo htmlspecialchars($service['slug']); ?>" value="<?php echo htmlspecialchars($limits[$service['slug']] ?? '0'); ?>" step="0.01" class="form-control">
            </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-primary">Save Limits</button>
    </form>
</div>

<?php include 'footer.php'; ?>
