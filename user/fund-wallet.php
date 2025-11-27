<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$pdo = db_connect();
$user_id = $_SESSION['user_id'];
$feedback = ['message' => '', 'type' => ''];

// Handle payment notification submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'notify') {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_method = $_POST['payment_method'] ?? '';

    if (empty($amount) || empty($payment_method) || $amount <= 0) {
        $feedback = ['message' => 'Please provide a valid amount and payment method.', 'type' => 'errors'];
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO deposit_notifications (user_id, amount, payment_method) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $amount, $payment_method]);
            $feedback = ['message' => 'Your payment notification has been sent. Your wallet will be credited upon confirmation.', 'type' => 'success'];
        } catch (Exception $e) {
            $feedback = ['message' => 'An error occurred. Please try again.', 'type' => 'errors'];
        }
    }
}


// Fetch manual deposit details
$stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'manual_%'");
$manual_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

include '../includes/header.php';
?>

<div class="container">
    <h2>Fund Wallet</h2>

    <?php if ($feedback['message']): ?>
        <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
    <?php endif; ?>

    <div class="dashboard-widgets">
        <div class="widget">
            <h3>Automated Funding (Paystack)</h3>
            <p>Enter an amount and pay instantly with your card.</p>
            <a href="fund-wallet-card.php" class="btn btn-primary">Pay with Card</a>
        </div>
        <div class="widget">
            <h3>Manual Bank Deposit</h3>
            <?php if (!empty($manual_settings['manual_bank_name'])): ?>
                <div class="account-details">
                    <p>Make a deposit to the account below and submit your payment details for confirmation.</p>
                    <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($manual_settings['manual_bank_name']); ?></p>
                    <p><strong>Account Number:</strong> <?php echo htmlspecialchars($manual_settings['manual_account_number']); ?></p>
                    <p><strong>Account Name:</strong> <?php echo htmlspecialchars($manual_settings['manual_account_name']); ?></p>
                </div>
                <hr>
                <h4>Submit Payment Notification</h4>
                <form method="post">
                    <input type="hidden" name="action" value="notify">
                    <div class="form-group">
                        <label for="amount">Amount Sent (₦)</label>
                        <input type="number" name="amount" id="amount" required step="0.01">
                    </div>
                    <div class="form-group">
                        <label for="payment_method">Payment Method (e.g., Your Name)</label>
                        <input type="text" name="payment_method" id="payment_method" required>
                    </div>
                    <button type="submit">Notify Admin</button>
                </form>
            <?php else: ?>
                <p>Manual deposits are currently not available. Please check back later.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<style>.account-details p { font-size: 1.1rem; margin: 8px 0; }</style>

<?php include '../includes/footer.php'; ?>
