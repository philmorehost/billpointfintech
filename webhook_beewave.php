<?php
// webhook_beewave.php
require_once 'core/config.php';
require_once 'core/functions.php';
require_once 'core/beewave_api.php'; // For transaction verification

// Set header to indicate JSON response
header('Content-Type: application/json');

// --- 1. Get the incoming payload ---
$payload = file_get_contents('php://input');
$event = json_decode($payload, true);

// Log every incoming webhook for debugging
error_log("Beewave Webhook Received: " . $payload);

// --- 2. Validate the payload ---
if (!$event || !isset($event['status']) || !isset($event['data']['transaction_ref'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => false, 'message' => 'Invalid payload.']);
    exit;
}

// Only process successful transactions
if ($event['status'] !== true || $event['data']['status'] !== 'success') {
    http_response_code(200); // OK, but we're not processing it
    echo json_encode(['status' => true, 'message' => 'Webhook received, but no action taken for this event type.']);
    exit;
}

$transaction_ref = $event['data']['transaction_ref'];
$pdo = db_connect();

try {
    // --- 3. Verify the transaction with Beewave API for security ---
    $verification_response = make_beewave_request('collection/verify', ['transaction_ref' => $transaction_ref], 'GET');

    if (!isset($verification_response['status']) || $verification_response['status'] !== true) {
        error_log("Beewave Webhook: Transaction verification failed for ref: " . $transaction_ref);
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Transaction verification failed.']);
        exit;
    }

    $verified_data = $verification_response['data'];
    $amount_paid = (float) $verified_data['settlement_amount']; // Use settlement amount
    $customer_email = $verified_data['customer']['email'] ?? null;

    if (!$customer_email || $amount_paid <= 0) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Missing required data from verified transaction.']);
        exit;
    }

    $pdo->beginTransaction();

    // --- 4. Check if we have already processed this transaction ---
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE reference = ?");
    $stmt->execute([$transaction_ref]);
    if ($stmt->fetchColumn() > 0) {
        $pdo->commit();
        http_response_code(200);
        echo json_encode(['status' => true, 'message' => 'Transaction already processed.']);
        exit;
    }

    // --- 5. Find the user and credit their wallet ---
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$customer_email]);
    $user_id = $stmt->fetchColumn();

    if ($user_id) {
        // Credit the user's wallet
        credit_wallet($user_id, $amount_paid);

        // Create a transaction record
        $description = "Wallet funding via Beewave Virtual Account. Ref: " . $transaction_ref;
        create_transaction($user_id, 'Wallet Funding', $description, $amount_paid, 'success', $transaction_ref, json_encode($verified_data));

        $pdo->commit();
        http_response_code(200);
        echo json_encode(['status' => true, 'message' => 'Webhook processed successfully.']);
    } else {
        $pdo->rollBack();
        error_log("Beewave Webhook: No user found with email: " . $customer_email);
        http_response_code(404); // Not Found
        echo json_encode(['status' => false, 'message' => 'User not found.']);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Beewave Webhook Error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => false, 'message' => 'An internal server error occurred.']);
}
?>
