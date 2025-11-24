<?php
// verify_payment.php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
require_once 'core/paystack_api.php';

// Get the transaction reference from the query string
$reference = $_GET['reference'] ?? null;

if (!$reference) {
    // No reference provided, redirect with an error
    set_flash_message('error', 'Invalid transaction reference.');
    header('Location: dashboard.php');
    exit();
}

try {
    // Verify the transaction with Paystack
    $paystack = new PaystackAPI();
    $response = $paystack->verifyTransaction($reference);

    if ($response && isset($response['status']) && $response['status'] === true) {
        $transaction_data = $response['data'];

        // --- Double-check the transaction details ---
        $status = $transaction_data['status'];
        $db_reference = $transaction_data['reference'];
        $amount_kobo = $transaction_data['amount'];

        // Find the corresponding transaction in our database
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE reference = ? AND user_id = ?");
        $stmt->execute([$db_reference, $_SESSION['user_id']]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            set_flash_message('error', 'Transaction not found in our records.');
            header('Location: dashboard.php');
            exit();
        }

        // --- Important Security Check ---
        // This page provides USER FEEDBACK. The actual crediting of the wallet
        // is handled by the WEBHOOK to prevent users from getting value by just
        // visiting this URL. Here, we just confirm the status and update our record
        // if it's still pending (though the webhook should be faster).

        if ($status === 'success') {
            // Check if the transaction was already completed by the webhook
            if ($transaction['status'] === 'pending') {
                // The webhook might be delayed. Let's update the status here
                // but rely on the webhook for the actual crediting logic.
                // This improves user experience by showing "success" immediately.
                $update_stmt = $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE id = ?");
                $update_stmt->execute([$transaction['id']]);
                 // We could also credit the user here if we trust this flow, but webhook is safer.
                 // For now, we assume the webhook will handle it.
            }
            set_flash_message('success', 'Your payment was successful! Your wallet will be credited shortly.');
        } else {
            // Payment was not successful (e.g., failed, abandoned)
             if ($transaction['status'] === 'pending') {
                $update_stmt = $pdo->prepare("UPDATE transactions SET status = 'failed' WHERE id = ?");
                $update_stmt->execute([$transaction['id']]);
            }
            set_flash_message('error', 'Your payment was not successful. Status: ' . htmlspecialchars($status));
        }

    } else {
        // API verification call failed
        $message = $response['message'] ?? 'Could not verify the transaction at this time.';
        set_flash_message('error', 'API Error: ' . htmlspecialchars($message));
    }

} catch (Exception $e) {
    set_flash_message('error', 'An unexpected error occurred: ' . $e->getMessage());
}

header('Location: dashboard.php');
exit();
