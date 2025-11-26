<?php
require_once '../includes/bootstrap.php';
require_once '../includes/admin_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
        header('Location: gateway_fees.php');
        exit();
    }
    if (isset($_POST['gateway_fees'])) {
        foreach ($_POST['gateway_fees'] as $gateway => $fee) {
            save_setting($pdo, "{$gateway}_fee", (float)$fee);
        }
        set_flash_message('success', 'Gateway fees updated successfully.');
    }
    header("Location: gateway_fees.php");
    exit();
}

$gateways = ['paystack', 'flutterwave']; // Add more gateways as needed
$fees = [];
foreach ($gateways as $gateway) {
    $fees[$gateway] = get_setting($pdo, "{$gateway}_fee") ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gateway Fees - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Gateway Fees</h2>
        <form action="gateway_fees.php" method="post">
            <?php generate_csrf_token(); ?>
            <?php foreach ($gateways as $gateway): ?>
                <div class="form-group">
                    <label for="<?php echo $gateway; ?>_fee"><?php echo ucfirst($gateway); ?> Fee (%)</label>
                    <input type="number" id="<?php echo $gateway; ?>_fee" name="gateway_fees[<?php echo $gateway; ?>]" value="<?php echo htmlspecialchars($fees[$gateway]); ?>" min="0" step="0.01">
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn">Save Fees</button>
        </form>
    </div>
</body>
</html>
