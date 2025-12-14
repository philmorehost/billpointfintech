<?php
include 'header.php';

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO cable_tv_packages (provider, package_name, api_code, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['provider'], $_POST['package_name'], $_POST['api_code'], $_POST['price']]);
            $feedback = ['message' => 'Cable TV package added successfully.', 'type' => 'success'];
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("UPDATE cable_tv_packages SET provider = ?, package_name = ?, api_code = ?, price = ? WHERE id = ?");
            $stmt->execute([$_POST['provider'], $_POST['package_name'], $_POST['api_code'], $_POST['price'], $_POST['id']]);
            $feedback = ['message' => 'Cable TV package updated successfully.', 'type' => 'success'];
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM cable_tv_packages WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $feedback = ['message' => 'Cable TV package deleted successfully.', 'type' => 'success'];
        }
    } catch (Exception $e) {
        $feedback = ['message' => 'An error occurred: ' . $e->getMessage(), 'type' => 'errors'];
    }
}

$stmt = $pdo->query("SELECT * FROM cable_tv_packages ORDER BY provider, package_name");
$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Manage Cable TV Packages</h2>

<?php if ($feedback['message']): ?>
    <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
<?php endif; ?>

<div class="dashboard-widgets">
    <div class="widget">
        <h3>Add New Package</h3>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="form-group"><label>Provider</label><input type="text" name="provider" required></div>
            <div class="form-group"><label>Package Name</label><input type="text" name="package_name" required></div>
            <div class="form-group"><label>API Code</label><input type="text" name="api_code" required></div>
            <div class="form-group"><label>Price (₦)</label><input type="number" name="price" required step="0.01"></div>
            <button type="submit">Add Package</button>
        </form>
    </div>
    <div class="widget">
        <h3>Existing Packages</h3>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Provider</th><th>Package Name</th><th>API Code</th><th>Price</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($packages as $package): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($package['provider']); ?></td>
                        <td><?php echo htmlspecialchars($package['package_name']); ?></td>
                        <td><?php echo htmlspecialchars($package['api_code']); ?></td>
                        <td><?php echo htmlspecialchars($package['price']); ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('Are you sure you want to delete this package?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $package['id']; ?>">
                                <button type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
