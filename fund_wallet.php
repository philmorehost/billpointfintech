<?php
$page_title = 'Fund Wallet';
require_once 'includes/header.php';
?>

<div class="fund-wallet-container" style="background: #fff; padding: 2rem; border-radius: 1rem;">
    <h2>Fund Your Wallet</h2>
    <p>Choose your preferred method to add funds.</p>

    <div class="funding-options">
        <div class="funding-option">
            <h3>Pay with Paystack</h3>
            <form action="payment_handler.php" method="POST">
                <?php echo generate_csrf_token_input(); ?>
                <input type="hidden" name="gateway" value="paystack">
                <div class="form-group">
                    <label for="paystack_amount">Amount (NGN)</label>
                    <input type="number" id="paystack_amount" name="amount" min="100" required>
                </div>
                <button type="submit" class="btn-primary">Pay Now</button>
            </form>
        </div>

        <div class="funding-option" style="margin-top: 2rem;">
            <h3>Bank Transfer (Virtual Account)</h3>
            <p>To fund your wallet via bank transfer, please use your dedicated virtual account.</p>
            <a href="virtual_account.php" class="btn-primary" style="display: inline-block; text-align: center;">View Virtual Account</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
