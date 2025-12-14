<?php
include 'header.php';

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO exam_products (name, api_code, price, is_available) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['name'], $_POST['api_code'], $_POST['price'], $_POST['is_available']]);
            $feedback = ['message' => 'Exam product added successfully.', 'type' => 'success'];
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("UPDATE exam_products SET name = ?, api_code = ?, price = ?, is_available = ? WHERE id = ?");
            $stmt->execute([$_POST['name'], $_POST['api_code'], $_POST['price'], $_POST['is_available'], $_POST['id']]);
            $feedback = ['message' => 'Exam product updated successfully.', 'type' => 'success'];
        }
    } catch (Exception $e) {
        $feedback = ['message' => 'An error occurred: ' . $e->getMessage(), 'type' => 'errors'];
    }
}

$stmt = $pdo->query("SELECT * FROM exam_products ORDER BY name");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Manage Exam Products</h2>

<?php if ($feedback['message']): ?>
    <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
<?php endif; ?>

<div class="dashboard-widgets">
    <div class="widget">
        <h3>Add New Exam Product</h3>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
            <div class="form-group"><label>API Code</label><input type="text" name="api_code" required></div>
            <div class="form-group"><label>Price (₦)</label><input type="number" name="price" required step="0.01"></div>
            <div class="form-group">
                <label>Availability</label>
                <select name="is_available"><option value="1">Available</option><option value="0">Unavailable</option></select>
            </div>
            <button type="submit">Add Product</button>
        </form>
    </div>
    <div class="widget">
        <h3>Existing Exam Products</h3>
        <?php foreach ($products as $product): ?>
            <form method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                <div class="form-group"><input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required></div>
                <div class="form-group"><input type="text" name="api_code" value="<?php echo htmlspecialchars($product['api_code']); ?>" required></div>
                <div class="form-group"><input type="number" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required step="0.01"></div>
                <div class="form-group">
                    <select name="is_available">
                        <option value="1" <?php if ($product['is_available']) echo 'selected'; ?>>Available</option>
                        <option value="0" <?php if (!$product['is_available']) echo 'selected'; ?>>Unavailable</option>
                    </select>
                </div>
                <button type="submit">Update</button>
            </form>
            <hr>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
