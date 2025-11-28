<?php
// webhook.php - Handles incoming webhook events from Paystack
require_once 'core/config.php';
require_once 'core/functions.php';

// Only respond to POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Retrieve the request's body and parse it as JSON
$input = @file_get_contents("php://input");

// Validate the event is from Paystack
if (!isset($_SERVER['HTTP_X_PAYSTACK_SIGNATURE']) || $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] !== hash_hmac('sha512', $input, PAYSTACK_SECRET_KEY)) {
    http_response_code(401);
    exit('Unauthorized');
}

$event = json_decode($input, true);

if (isset($event['event']) && $event['event'] === 'charge.success') {
    // Event is a successful charge, now we process it
    $data = $event['data'];
    $customer_code = $data['customer']['customer_code'];
    $amount = $data['amount'] / 100; // Convert from kobo
    $reference = $data['reference'];

    try {
        $pdo = db_connect();

        // Find the user associated with this customer code
        $stmt = $pdo->prepare("SELECT id FROM users WHERE paystack_customer_code = ?");
        $stmt->execute([$customer_code]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $user_id = $user['id'];

            // Prevent re-crediting for the same transaction
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE service = 'Wallet Funding' AND reference = ?");
            $stmt->execute([$reference]);

            if ($stmt->fetchColumn() == 0) {
                // Credit user's wallet and log the transaction
                if (credit_wallet($user_id, $amount)) {
                    $description = "Wallet funding via Virtual Account deposit";
                    create_transaction($user_id, 'Wallet Funding', $description, $amount, 'success', $reference);
                } else {
                    // Log an error if crediting fails, for manual review
                    error_log("Webhook Error: Failed to credit wallet for user ID $user_id and reference $reference");
                }
            }
        } else {
            // Log an error if we can't find the user
            error_log("Webhook Error: Could not find user with customer code $customer_code");
        }
    } catch (Exception $e) {
        // Log any database exceptions
        error_log("Webhook DB Error: " . $e->getMessage());
    }
}

// Acknowledge receipt of the event
http_response_code(200);
exit;
