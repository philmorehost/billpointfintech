<?php
// webhook_handler.php
require_once 'includes/bootstrap.php';
require_once 'core/paystack_api.php';

// --- Security Check: Whitelist Paystack IPs in a production environment ---
// This is a crucial step for production. For this example, we'll rely on the signature.

// Get the POST body
$event_payload = @file_get_contents("php://input");

// Get Paystack signature from the header
$paystack_signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

// Get the secret key from settings to validate the signature
$secret_key = $config['settings']['paystack_secret_key'] ?? null;

if (!$secret_key) {
    // If we don't have a secret key, we can't verify the webhook.
    http_response_code(500); // Internal Server Error
    error_log("Paystack Webhook Error: Secret key not configured.");
    exit();
}

// Validate the event signature
$hash = hash_hmac('sha512', $event_payload, $secret_key);

if ($hash !== $paystack_signature) {
    // Invalid signature
    http_response_code(401); // Unauthorized
    error_log("Paystack Webhook Error: Invalid signature.");
    exit();
}

// Decode the event payload
$event_data = json_decode($event_payload, true);

// Check if the event is a successful charge
if (isset($event_data['event']) && $event_data['event'] === 'charge.success') {
    $transaction_data = $event_data['data'];
    $reference = $transaction_data['reference'];
    $status = $transaction_data['status'];
    $amount_kobo = $transaction_data['amount'];
    $customer_email = $transaction_data['customer']['email'];

    // --- Process the Transaction ---
    if ($status === 'success') {
        try {
            $pdo->beginTransaction();

            // 1. Check if this transaction reference has already been processed
            $stmt = $pdo->prepare("SELECT id FROM transactions WHERE reference = ? AND type = 'deposit'");
            $stmt->execute([$reference]);
            if ($stmt->fetch()) {
                // This transaction has already been processed. Acknowledge and exit.
                $pdo->commit();
                http_response_code(200);
                exit();
            }

            // 2. Find the user by email
            $user_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $user_stmt->execute([$customer_email]);
            $user = $user_stmt->fetch();

            if (!$user) {
                throw new Exception("User with email {$customer_email} not found for reference {$reference}.");
            }
            $user_id = $user['id'];

            // 3. Credit the user's NGN wallet
            $amount_ngn = $amount_kobo / 100;
            $wallet_stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = 'NGN'");
            $wallet_stmt->execute([$amount_ngn, $user_id]);

            // 4. Log the transaction
            $log_stmt = $pdo->prepare(
                "INSERT INTO transactions (user_id, type, amount, currency, status, reference, description)
                 VALUES (?, 'deposit', ?, 'NGN', 'completed', ?, ?)"
            );
            $description = "Wallet funded via Paystack.";
            $log_stmt->execute([$user_id, $amount_ngn, $reference, $description]);

            $pdo->commit();

            // Send a 200 OK response to Paystack to acknowledge receipt
            http_response_code(200);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Log the error for debugging
            error_log("Paystack Webhook Processing Error for reference {$reference}: " . $e->getMessage());

            // Respond with a 500 status to signal an error to Paystack, prompting a retry.
            http_response_code(500);
        }
    }
}

// Default response if the event is not 'charge.success'
http_response_code(200);
exit();
