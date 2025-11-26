<?php
$page_title = 'Exchange';
require_once 'includes/header.php';
?>

<div class="exchange-container" style="background: #fff; padding: 2rem; border-radius: 1rem;">
    <h2>Currency Exchange</h2>
    <p>Exchange rates are updated in real-time.</p>

    <form action="exchange_handler.php" method="POST">
        <?php echo generate_csrf_token_input(); ?>
        <div class="form-group">
            <label for="from_currency">From</label>
            <select id="from_currency" name="from_currency" class="currency-selector">
                <option>USD</option>
                <option selected>NGN</option>
                <option>CAD</option>
            </select>
        </div>

        <div class="form-group">
            <label for="to_currency">To</label>
            <select id="to_currency" name="to_currency" class="currency-selector">
                <option selected>USD</option>
                <option>NGN</option>
                <option>CAD</option>
            </select>
        </div>

        <div class="form-group">
            <label for="amount">Amount</label>
            <input type="text" id="amount" name="amount" placeholder="0.00">
        </div>

        <button type="submit" class="btn-primary">Exchange</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
