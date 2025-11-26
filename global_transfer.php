<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
require_once 'core/juicyway_api.php';

$juicyway = new JuicyWayAPI($config['settings']['juicyway_api_key'] ?? null, $config['settings']['juicyway_secret_key'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Transfer - Billpoint</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Global Transfer</h2>
        <form action="transaction_handler.php" method="post">
            <input type="hidden" name="action" value="global_transfer">
            <?php generate_csrf_token(); ?>
            <div class="form-group">
                <label for="source_currency">From</label>
                <select id="source_currency" name="source_currency" required>
                    <option value="NGN">NGN</option>
                    <option value="USD">USD</option>
                    <option value="CAD">CAD</option>
                </select>
            </div>
            <div class="form-group">
                <label for="destination_currency">To</label>
                <select id="destination_currency" name="destination_currency" required>
                    <option value="USD">USD</option>
                    <option value="CAD">CAD</option>
                    <option value="NGN">NGN</option>
                </select>
            </div>
            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" min="0.01" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="recipient_name">Recipient Name</label>
                <input type="text" id="recipient_name" name="recipient[name]" required>
            </div>
            <div class="form-group">
                <label for="recipient_account">Recipient Account Number</label>
                <input type="text" id="recipient_account" name="recipient[account_number]" required>
            </div>
            <div class="form-group">
                <label for="recipient_bank">Recipient Bank</label>
                <input type="text" id="recipient_bank" name="recipient[bank_name]" required>
            </div>
            <div class="form-group">
                <label for="pin">4-Digit PIN</label>
                <input type="password" id="pin" name="pin" maxlength="4" required>
            </div>
            <button type="submit" class="btn">Transfer</button>
        </form>
    </div>
    <?php include 'includes/footer_nav.php'; ?>
</body>
</html>
