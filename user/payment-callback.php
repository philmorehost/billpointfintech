<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$message = "Your transaction status is pending. Please wait for confirmation or contact support if your wallet is not credited shortly.";
$type = 'info'; // can be 'success', 'error', 'info'

if (isset($_GET['reference'])) {
    $reference = $_GET['reference'];

    // For enhanced security, you should verify the transaction status with Paystack here
    // before confirming success to the user. This example is simplified.

    // Acknowledge the attempt
    $message = "Your wallet funding request has been received. Your wallet will be credited as soon as the payment is confirmed by Paystack.";
    $type = 'success';

    // In a real application, you would make a cURL request to Paystack's verification endpoint
    // using the reference to get the final status before updating the DB and showing a message.
    // The webhook is the primary method for crediting, this page is for user feedback.
}

include '../includes/header.php';
?>

<div class="container app-view">
    <h2>Payment Status</h2>
    <div class="<?php echo $type; ?>">
        <p><?php echo htmlspecialchars($message); ?></p>
    </div>
    <a href="dashboard.php" class="btn">Back to Dashboard</a>
</div>

<?php include '../includes/footer.php'; ?>
