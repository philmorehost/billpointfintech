<?php
require_once '../core/config.php';
require_once '../core/functions.php';
require_once '../core/auth_check.php';
require_once '../core/vtu_api.php';
require_once '../core/security_functions.php';

$pdo = db_connect();
$user_id = $_SESSION['user_id'];

// Fetch bank transfer fee from settings
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'bank_transfer_fee'");
$bank_transfer_fee = $stmt->fetchColumn() ?: 0.00;

$stmt = $pdo->query("SELECT * FROM banks ORDER BY name");
$banks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$success_message = '';
$limit_error = null;

// Determine if PIN is required before any POST logic
$pin_is_required = is_pin_required($pdo, $user_id);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Re-check PIN requirement in case of session timeout during form fill
    if (is_pin_required($pdo, $user_id)) {
        // This server-side check prevents submitting the form without a fresh PIN verification.
        // The actual PIN check is done via AJAX on the frontend.
        $errors[] = "For your security, PIN verification is required to complete this transaction.";
    } else {
        // ... (existing POST logic for bank transfer)
    }
}

include '../includes/header.php';
?>
<div class="container">
    <h2>Bank Transfer</h2>
    <!-- ... (error/success message display) ... -->
    <div class="info-box">
        <p>A fee of <strong>₦<?php echo htmlspecialchars(number_format($bank_transfer_fee, 2)); ?></strong> will be applied to this transaction.</p>
    </div>

    <form id="transfer-form" action="bank-transfer.php" method="post">
        <div class="form-group">
            <label for="bank_code">Select Bank</label>
            <select name="bank_code" id="bank_code" required>
                <option value="">-- Select a Bank --</option>
                <?php foreach ($banks as $bank): ?>
                    <option value="<?php echo htmlspecialchars($bank['code']); ?>">
                        <?php echo htmlspecialchars($bank['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="account_number">Account Number</label>
            <input type="text" name="account_number" id="account_number" required>
        </div>
        <div class="form-group">
             <label for="amount">Amount (₦)</label>
             <input type="number" id="amount" name="amount" required step="0.01">
             <small>Total to be debited: <strong id="total-debit">₦0.00</strong></small>
        </div>
        <button type="button" id="verify-btn">Verify Account</button>
        <button type="submit" id="transfer-btn" style="display:none;">Send Money</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('amount');
    const totalDebitEl = document.getElementById('total-debit');
    const transferFee = <?php echo json_encode((float)$bank_transfer_fee); ?>;

    amountInput.addEventListener('input', function() {
        const amount = parseFloat(this.value) || 0;
        const total = amount + transferFee;
        totalDebitEl.textContent = `₦${total.toFixed(2)}`;
    });

    const transferForm = document.getElementById('transfer-form');
    const pinIsRequired = <?php echo json_encode($pin_is_required); ?>;

    transferForm.addEventListener('submit', async function(e) {
        if (pinIsRequired) {
            e.preventDefault(); // Stop the form from submitting

            // Request the PIN from the user
            const pinVerified = await requestPin(); // This function is in the footer

            if (pinVerified) {
                // If PIN is correct, programmatically resubmit the form
                transferForm.submit();
            } else {
                // An error message will be shown by the pin modal itself
                // You could add more specific handling here if needed
            }
        }
        // If PIN is not required, the form submits normally
    });

    // ... (rest of the verification AJAX logic for the form) ...
});
</script>
