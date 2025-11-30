<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$pdo = db_connect();
$user_id = $_SESSION['user_id'];
$feedback = ['message' => '', 'type' => ''];

// Fetch user details for the checkout
$stmt = $pdo->prepare("SELECT email, full_name, phone_number FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();


// Handle payment notification submission for manual deposit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'notify') {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_method = $_POST['payment_method'] ?? '';

    if (empty($amount) || empty($payment_method) || $amount <= 0) {
        $feedback = ['message' => 'Please provide a valid amount and payment method.', 'type' => 'errors'];
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO deposit_notifications (user_id, amount, payment_method) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $amount, $payment_method]);
            $notification_id = $pdo->lastInsertId();

            $description = "Manual deposit notification. Ref: " . $notification_id;
            create_transaction($user_id, 'Wallet Funding', $description, $amount, 'pending');

            $pdo->commit();
            $feedback = ['message' => 'Your payment notification has been sent. Your wallet will be credited upon confirmation.', 'type' => 'success'];
        } catch (Exception $e) {
            $pdo->rollBack();
            $feedback = ['message' => 'An error occurred. Please try again.', 'type' => 'errors'];
        }
    }
}


// Fetch manual deposit details
$stmt = $pdo->query("SELECT * FROM settings WHERE setting_key LIKE 'manual_%'");
$manual_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

include '../includes/header.php';
?>

<div class="app-view">
     <div class="airtime-header">
        <a href="dashboard.php" class="back-btn">&#8592;</a>
        <span class="title">Fund Wallet</span>
    </div>

    <div class="container">
        <?php if ($feedback['message']): ?>
            <div class="<?php echo htmlspecialchars($feedback['type']); ?> mb-3"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
        <?php endif; ?>

        <div class="dashboard-widgets">
            <div class="widget">
                <h3>Automated Funding (Card / Transfer)</h3>
                <p>Enter an amount and pay instantly with your card via our secure gateway.</p>

                <div class="form-group">
                    <label for="beewave_amount">Amount (₦)</label>
                    <input type="number" id="beewave_amount" name="amount" required min="100" step="0.01" class="form-control">
                </div>
                <button type="button" id="pay-btn" class="btn btn-primary">Pay Now</button>
            </div>

            <div class="widget">
                <h3>Manual Bank Deposit</h3>
                <?php if (!empty($manual_settings['manual_bank_name'])): ?>
                    <div class="account-details">
                        <p><strong>Bank:</strong> <?php echo htmlspecialchars($manual_settings['manual_bank_name']); ?></p>
                        <p><strong>Account Number:</strong> <?php echo htmlspecialchars($manual_settings['manual_account_number']); ?></p>
                        <p><strong>Account Name:</strong> <?php echo htmlspecialchars($manual_settings['manual_account_name']); ?></p>
                    </div>
                    <hr>
                    <h4>Submit Payment Notification</h4>
                    <form method="post">
                        <input type="hidden" name="action" value="notify">
                        <div class="form-group">
                            <label for="amount">Amount Sent (₦)</label>
                            <input type="number" name="amount" id="amount" required step="0.01" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="payment_method">Payment Method</label>
                            <input type="text" name="payment_method" id="payment_method" required class="form-control">
                        </div>
                        <button type="submit" class="btn btn-secondary">Notify Admin</button>
                    </form>
                <?php else: ?>
                    <p>Manual deposits are currently not available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('pay-btn').addEventListener('click', function() {
    const amount = document.getElementById('beewave_amount').value;
    if (!amount || amount < 100) {
        alert('Please enter an amount of at least ₦100.');
        return;
    }

    BeefinanceCheckout.open({
        accessKey: "<?php echo htmlspecialchars($GLOBALS['app_settings']['beewave_access_key']); ?>",
        name: "<?php echo htmlspecialchars($user['full_name']); ?>",
        email: "<?php echo htmlspecialchars($user['email']); ?>",
        phone: "<?php echo htmlspecialchars($user['phone_number']); ?>",
        amount: amount,
        onclose: function () {
            // This function is called when the checkout is closed
            // You can optionally reload or check transaction status here
            window.location.reload();
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
