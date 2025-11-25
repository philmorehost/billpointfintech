<?php
$page_title = 'Buy Exam PINs';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';

$stmt = $pdo->query("SELECT * FROM exam_products ORDER BY exam_name");
$products = $stmt->fetchAll();
$csrf_token = generate_csrf_token();
include 'includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>Buy Exam PINs</h1>
    </div>

    <div class="content-box">
        <?php display_flash_message(); ?>
        <form action="transaction_handler.php" method="POST">
            <input type="hidden" name="action" value="buy_exam_pin">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-group">
                <label for="exam_product_id">Select Exam</label>
                <select id="exam_product_id" name="exam_product_id" required>
                    <option value="">-- Select an exam --</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>">
                            <?php echo htmlspecialchars($product['exam_name']) . ' - ₦' . $product['price']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="number" id="quantity" name="quantity" min="1" value="1" required>
            </div>

            <button type="submit" class="btn">Buy Now</button>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
