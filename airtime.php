<?php
session_start();
require_once 'includes/auth_check.php';
require_once 'includes/flash_messages.php';
require_once 'includes/csrf.php';
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Airtime - Billpoint</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h2>Buy Airtime</h2>
            <?php display_flash_message(); ?>
            <form action="transaction_handler.php" method="POST">
                <input type="hidden" name="action" value="buy_airtime">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-group">
                    <label for="network">Network</label>
                    <select id="network" name="network" required>
                        <option value="mtn">MTN</option>
                        <option value="glo">Glo</option>
                        <option value="airtel">Airtel</option>
                        <option value="9mobile">9mobile</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="text" id="phone_number" name="phone_number" required>
                </div>
                <div class="form-group">
                    <label for="amount">Amount</label>
                    <input type="number" id="amount" name="amount" min="50" required>
                </div>
                <button type="submit" class="btn">Buy Now</button>
            </form>
        </div>
    </div>
</body>
</html>
