<?php
require_once '../core/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = trim($_POST['amount']);
    $user_id = $_SESSION['user_id'];

    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $errors[] = 'Please enter a valid amount.';
    }

    if (empty($errors)) {
        // This is a placeholder for a real payment gateway integration.
        // For testing purposes, we'll credit the user's wallet directly.
        if (credit_wallet($user_id, $amount)) {
            $success_message = "Successfully funded your wallet with &#8358;" . number_format($amount, 2) . ".";
        } else {
            $errors[] = 'Failed to fund wallet.';
        }
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h2>Fund Wallet</h2>

    <div class="notice">
        <p><strong>Note:</strong> This is a simulation. In a real application, you would be redirected to a payment gateway.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="success">
            <p><?php echo $success_message; ?></p>
        </div>
    <?php endif; ?>

    <form action="fund-wallet.php" method="post">
        <div class="form-group">
            <label for="amount">Amount</label>
            <input type="number" name="amount" id="amount" required>
        </div>
        <button type="submit">Fund Now (Simulation)</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
