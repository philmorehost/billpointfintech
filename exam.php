<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';

$stmt = $pdo->query("SELECT * FROM exam_products ORDER BY exam_name");
$products = $stmt->fetchAll();
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Exam PINs - Billpoint</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h2>Buy Exam PINs</h2>
            <?php display_flash_message(); ?>
            <form action="transaction_handler.php" method="POST">
                <input type="hidden" name="action" value="buy_exam_pin">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="form-group">
                    <label for="exam_product">Select Exam</label>
                    <select id="exam_product" name="exam_product" required>
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
    <?php include 'includes/footer_nav.php'; ?>
</body>
</html>
