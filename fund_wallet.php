<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
require_once 'core/flutterwave_api.php';

$user_id = $_SESSION['user_id'];
$csrf_token = generate_csrf_token();

// Fetch user details including virtual account info
$stmt = $pdo->prepare("SELECT full_name, email, bvn, virtual_account_number, virtual_bank_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle virtual account generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_virtual_account') {
    if (validate_csrf_token()) {
        if (empty($user['bvn'])) {
            set_flash_message('error', 'You must complete KYC verification before generating a virtual account.');
        } elseif (empty($user['virtual_account_number'])) {
            $flutterwave = new FlutterwaveAPI($config['settings']['flutterwave_secret_key'] ?? null);
            list($firstname, $lastname) = explode(' ', $user['full_name'] . " ");
            $tx_ref = 'v-acct-' . uniqid() . '-' . $user_id;

            $response = $flutterwave->create_virtual_account($user['email'], $user['bvn'], $firstname, $lastname, $tx_ref);

            if (isset($response['status']) && $response['status'] === 'success') {
                $account_data = $response['data'];
                $update_stmt = $pdo->prepare("UPDATE users SET virtual_account_number = ?, virtual_bank_name = ?, virtual_account_ref = ? WHERE id = ?");
                $update_stmt->execute([$account_data['account_number'], $account_data['bank_name'], $tx_ref, $user_id]);
                set_flash_message('success', 'Your virtual account has been generated successfully.');
            } else {
                set_flash_message('error', 'Failed to generate virtual account: ' . ($response['message'] ?? 'Unknown error.'));
            }
        }
    }
    header('Location: fund_wallet.php');
    exit();
}

// Re-fetch user data after potential update
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Fund Your NGN Wallet</h1>
        <p>Deposit funds into your wallet via bank transfer or card payment.</p>
    </div>

    <div class="content-box">
        <h2>Bank Transfer Deposit</h2>
        <?php display_flash_message(); ?>

        <?php if (!empty($user['virtual_account_number'])): ?>
            <p>Transfer funds to the account details below to fund your wallet instantly.</p>
            <div class="account-details">
                <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($user['virtual_bank_name']); ?></p>
                <p><strong>Account Number:</strong> <?php echo htmlspecialchars($user['virtual_account_number']); ?></p>
                <p><strong>Account Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
            </div>
        <?php else: ?>
            <p>Generate a dedicated virtual account to fund your wallet via bank transfer.</p>
            <?php if (empty($user['bvn'])): ?>
                <div class="alert alert-warning">
                    You must <a href="kyc.php">complete KYC verification</a> before you can generate a virtual account.
                </div>
            <?php else: ?>
                <form action="fund_wallet.php" method="POST">
                    <input type="hidden" name="action" value="generate_virtual_account">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <button type="submit" class="btn">Generate My Virtual Account</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="content-box" style="margin-top: 20px;">
        <h2>Card / Other Payment Methods</h2>
        <form action="payment_handler.php" method="POST">
            <input type="hidden" name="action" value="initialize_funding">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group">
                <label for="amount">Amount (NGN)</label>
                <input type="number" id="amount" name="amount" class="form-control" min="100" placeholder="Enter amount (e.g., 5000)" required>
                <small class="form-text text-muted">Use this for card, USSD, or other Paystack payment options.</small>
            </div>
            <button type="submit" class="btn btn-primary">Proceed to Paystack</button>
        </form>
    </div>
</div>
<style>
.account-details {
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 5px;
}
</style>

<?php include 'includes/footer.php'; ?>
