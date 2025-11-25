<?php
$page_title = 'Global Transfer';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
$csrf_token = generate_csrf_token();
include 'includes/header.php';

// Fetch user's wallets to determine source currencies
$stmt = $pdo->prepare("SELECT currency, balance FROM wallets WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$wallets = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<div class="container">
    <div class="page-header">
        <h1>Global Transfer</h1>
        <p>Send money to bank accounts and mobile wallets worldwide.</p>
    </div>

    <div class="content-box">
        <?php display_flash_message(); ?>
        <form id="global-transfer-form" action="transaction_handler.php" method="POST">
            <input type="hidden" name="action" value="global_transfer">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <p><strong>Note:</strong> This is a simplified form. A real-world implementation would require a multi-step process to select payout methods, fetch beneficiary requirements dynamically, and get quotes.</p>

            <div class="form-group">
                <label for="source_currency">Send From</label>
                <select id="source_currency" name="source_currency" required>
                    <?php foreach ($wallets as $currency => $balance): ?>
                        <option value="<?php echo $currency; ?>">
                            <?php echo $currency; ?> (Balance: <?php echo number_format($balance, 2); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="destination_currency">Recipient Currency</label>
                <input type="text" id="destination_currency" name="destination_currency" placeholder="e.g., USD, KES, GHS" required>
            </div>

            <div class="form-group">
                <label for="amount">Amount to Send</label>
                <input type="number" id="amount" name="amount" step="0.01" min="1" required>
            </div>

            <hr>
            <h3>Recipient Details</h3>
            <div class="form-group">
                <label for="recipient_name">Recipient Full Name</label>
                <input type="text" name="recipient[name]" required>
            </div>
             <div class="form-group">
                <label for="recipient_bank_name">Recipient Bank Name</label>
                <input type="text" name="recipient[bank_name]" required>
            </div>
            <div class="form-group">
                <label for="recipient_account_number">Recipient Account Number</label>
                <input type="text" name="recipient[account_number]" required>
            </div>
             <div class="form-group">
                <label for="recipient_country">Recipient Country (2-letter code)</label>
                <input type="text" name="recipient[country]" maxlength="2" placeholder="e.g., US, KE, GH" required>
            </div>

            <hr>

            <div class="form-group">
                <label for="pin">Your 4-Digit PIN</label>
                <input type="password" id="pin" name="pin" maxlength="4" required>
            </div>

            <button type="submit" class="btn">Initiate Transfer</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
