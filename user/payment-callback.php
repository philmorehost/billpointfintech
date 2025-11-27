<?php
require_once '../core/functions.php';

if (!isset($_GET['reference'])) {
    die('Invalid callback request.');
}

$reference = $_GET['reference'];
$user_id = $_SESSION['user_id'];

// Verify the transaction with Paystack
$url = 'https://api.paystack.co/transaction/verify/' . rawurlencode($reference);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
]);

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

$page_title = "Payment Status";
include '../includes/header.php';

$errors = [];
$success_message = '';

if ($err) {
    $errors[] = 'Could not verify transaction. Please contact support.';
} else {
    $result = json_decode($response, true);
    if (isset($result['status']) && $result['status'] == true && $result['data']['status'] == 'success') {
        // Payment was successful
        $amount = $result['data']['amount'] / 100; // Convert from kobo
        $db_reference = $result['data']['reference'];

        // IMPORTANT: Prevent re-crediting for the same transaction
        $pdo = db_connect();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE service = 'Wallet Funding' AND reference = ?");
        $stmt->execute([$db_reference]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "This transaction has already been processed.";
        } else {
            // Credit the user's wallet
            if (credit_wallet($user_id, $amount)) {
                // Log the transaction
                $description = "Wallet funding via Paystack (Card)";
                create_transaction($user_id, 'Wallet Funding', $description, $amount, 'success', $db_reference);
                $success_message = "Your wallet has been successfully credited with ₦" . number_format($amount, 2);
            } else {
                $errors[] = "Failed to credit wallet. Please contact support immediately.";
                // You should have a robust logging/alerting system here
            }
        }
    } else {
        $errors[] = 'Payment verification failed or payment was not successful.';
    }
}
?>

<div class="container">
    <h2>Payment Confirmation</h2>
    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <div class="success">
            <p><?php echo htmlspecialchars($success_message); ?></p>
        </div>
    <?php endif; ?>
    <a href="dashboard.php"><button>&laquo; Back to Dashboard</button></a>
</div>

<?php include '../includes/footer.php'; ?>
