<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
$csrf_token = generate_csrf_token();

$stmt = $pdo->query("SELECT * FROM data_plans ORDER BY network, price");
$plans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Data - Billpoint</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h2>Buy Data</h2>
            <?php display_flash_message(); ?>
            <form action="transaction_handler.php" method="POST">
                <input type="hidden" name="action" value="buy_data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="text" id="phone_number" name="phone_number" required>
                </div>
                <div class="form-group">
                    <label for="data_plan">Select Plan</label>
                    <select id="data_plan" name="data_plan" required>
                        <option value="">-- Select a plan --</option>
                        <?php foreach ($plans as $plan): ?>
                            <option value="<?php echo $plan['id']; ?>">
                                <?php echo strtoupper($plan['network']) . ' ' . $plan['quantity'] . ' (' . $plan['type'] . ') - ₦' . $plan['price']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn">Buy Now</button>
            </form>
        </div>
    </div>
</body>
</html>
