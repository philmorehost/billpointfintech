<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';
require_once '../core/vtu_api.php';
require_once '../core/security_functions.php';

$pdo = db_connect();
$user_id = $_SESSION['user_id'];
// ... (rest of the initial setup)
$banks = [
    '044' => 'Access Bank', '023' => 'Citibank Nigeria', /* ... */ '057' => 'Zenith Bank'
];
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
    <form id="transfer-form" action="bank-transfer.php" method="post">
        <!-- ... (form fields) ... -->
        <button type="button" id="verify-btn">Verify Account</button>
        <button type="submit" id="transfer-btn" style="display:none;">Send Money</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
