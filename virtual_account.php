<?php
$page_title = 'Virtual Account';
require_once 'includes/header.php';

// Logic to retrieve or generate virtual account
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT virtual_account_number AS account_number, virtual_bank_name AS bank_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$account = $stmt->fetch();

if (!$account) {
    // If no account, try to create one (dummy logic for now)
    // In a real app, this would call the Flutterwave API
    // $flutterwave = new FlutterwaveAPI(...);
    // $new_account = $flutterwave->create_virtual_account(...);
    // and then save it.
}
?>

<div class="virtual-account-container" style="background: #fff; padding: 2rem; border-radius: 1rem; text-align: center;">
    <h2>Your Dedicated Virtual Account</h2>

    <?php if ($account): ?>
        <p>Fund your wallet by transferring to the account below:</p>
        <div class="account-details" style="background: #f9fafb; padding: 1.5rem; border-radius: 0.5rem; margin-top: 1rem;">
            <h3><?php echo htmlspecialchars($account['account_number']); ?></h3>
            <p><strong>Bank:</strong> <?php echo htmlspecialchars($account['bank_name']); ?></p>
            <p><strong>Beneficiary:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
        </div>
    <?php else: ?>
        <p>We were unable to generate a virtual account for you at this time.</p>
        <p>Please <a href="support.php">contact support</a>.</p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
