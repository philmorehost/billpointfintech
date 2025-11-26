<?php
$page_title = 'P2P Transfer';
require_once 'includes/header.php';
?>

<div class="p2p-transfer-container" style="max-width: 600px; margin: auto; background: #fff; padding: 2rem; border-radius: 1rem;">
    <h2>Peer-to-Peer Transfer</h2>
    <p>Send funds directly to another Billpoint user.</p>

    <form action="transaction_handler.php" method="POST">
        <?php echo generate_csrf_token_input(); ?>
        <input type="hidden" name="action" value="p2p_transfer">

        <div class="form-group">
            <label for="recipient">Recipient's Username or Email</label>
            <input type="text" id="recipient" name="recipient" required>
        </div>

        <div class="form-group">
            <label for="amount">Amount</label>
            <input type="number" id="amount" name="amount" step="0.01" min="1" required>
        </div>

        <div class="form-group">
            <label for="currency">Currency</label>
            <select id="currency" name="currency" class="currency-selector">
                <option selected>NGN</option>
                <option>USD</option>
                <option>CAD</option>
            </select>
        </div>

        <div class="form-group">
            <label for="pin">Your 4-Digit PIN</label>
            <input type="password" id="pin" name="pin" required maxlength="4" style="text-security: disc; -webkit-text-security: disc;">
        </div>

        <button type="submit" class="btn-primary">Send Funds</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
