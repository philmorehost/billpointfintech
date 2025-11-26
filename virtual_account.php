<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT kyc_level, virtual_account_number, virtual_bank_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$kyc_verified = ($user && $user['kyc_level'] >= 1);
$has_virtual_account = ($user && !empty($user['virtual_account_number']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Account - Billpoint</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Your Virtual Account</h2>
        <?php display_flash_message(); ?>

        <?php if (!$kyc_verified): ?>
            <div class="alert alert-warning">
                <p>You must complete your KYC verification before you can generate a virtual bank account.</p>
                <a href="kyc.php" class="btn">Complete KYC Now</a>
            </div>
        <?php elseif ($has_virtual_account): ?>
            <div class="virtual-account-details">
                <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($user['virtual_bank_name']); ?></p>
                <p><strong>Account Number:</strong> <?php echo htmlspecialchars($user['virtual_account_number']); ?></p>
                <p class="small-text">Fund your wallet by transferring money to this account. Your wallet will be credited automatically.</p>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <p>You are eligible to generate a dedicated virtual account for easy wallet funding.</p>
                <form action="payment_handler.php" method="post">
                    <input type="hidden" name="action" value="generate_virtual_account">
                    <?php generate_csrf_token(); ?>
                    <button type="submit" class="btn">Generate My Virtual Account</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <?php include 'includes/footer_nav.php'; ?>
</body>
</html>
