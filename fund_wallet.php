<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';

$csrf_token = generate_csrf_token();
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Fund Your NGN Wallet</h1>
        <p>Deposit funds directly into your wallet using our secure payment gateway.</p>
    </div>

    <div class="content-box">
        <?php display_flash_message(); ?>

        <form action="payment_handler.php" method="POST">
            <input type="hidden" name="action" value="initialize_funding">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-group">
                <label for="amount">Amount (NGN)</label>
                <input type="number" id="amount" name="amount" class="form-control" min="100" placeholder="Enter amount (e.g., 5000)" required>
                <small class="form-text text-muted">Minimum deposit is NGN 100.</small>
            </div>

            <button type="submit" class="btn btn-primary">Proceed to Payment</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
