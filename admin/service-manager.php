<?php
include 'header.php';

$pdo = db_connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_id'], $_POST['discount'])) {
    $service_id = $_POST['service_id'];
    $discount = $_POST['discount'];

    if (is_numeric($discount) && $discount >= 0 && $discount <= 100) {
        $stmt = $pdo->prepare("UPDATE services SET discount_percentage = ? WHERE id = ?");
        $stmt->execute([$discount, $service_id]);
        $success_message = "Discount updated successfully!";
    } else {
        $error_message = "Invalid discount percentage. Please enter a value between 0 and 100.";
    }
}

$stmt = $pdo->query("SELECT * FROM services ORDER BY name ASC");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<h2>Manage Service Discounts</h2>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
<?php endif; ?>
<?php if (isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Service Name</th>
                <th>Current Discount (%)</th>
                <th>Set New Discount</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($services as $service): ?>
                <tr>
                    <td><?php echo htmlspecialchars($service['name']); ?></td>
                    <td><?php echo htmlspecialchars($service['discount_percentage'] ?? '0.00'); ?></td>
                    <form method="post">
                        <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                        <td>
                            <input type="number" name="discount" class="form-control" step="0.01" min="0" max="100" placeholder="e.g., 5.5" required>
                        </td>
                        <td>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
