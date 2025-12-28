<?php
// flutterwave_webhook.php
require_once 'includes/bootstrap.php';

// 1. Retrieve the request's body
$json = @file_get_contents("php://input");

// 2. Get Flutterwave signature from the header
$flutterwave_signature = $_SERVER['HTTP_VERIF_HASH'] ?? '';

// 3. Get the secret hash from settings to validate the signature
$secret_hash = $config['settings']['flutterwave_secret_key'] ?? null; // Assuming secret key is used as the hash

if (!$secret_hash || $flutterwave_signature !== $secret_hash) {
    // This request isn't from Flutterwave. Stop processing.
    http_response_code(401);
    error_log("Flutterwave Webhook Error: Invalid signature.");
    exit();
}

// 4. Decode the event payload
$event_data = json_decode($json, true);

if (isset($event_data['event']) && $event_data['event'] === 'charge.completed' && isset($event_data['data']['status']) && $event_data['data']['status'] === 'successful') {

    $transaction_data = $event_data['data'];
    $tx_ref = $transaction_data['tx_ref'];
    $amount = (float)$transaction_data['amount'];
    $currency = $transaction_data['currency'];
    $reference = $transaction_data['flw_ref'];

    // We only care about NGN deposits for now
    if ($currency !== 'NGN') {
        http_response_code(200);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. Check if this transaction has already been processed
        $stmt = $pdo->prepare("SELECT id FROM transactions WHERE reference = ? AND type = 'deposit'");
        $stmt->execute([$reference]);
        if ($stmt->fetch()) {
            $pdo->commit();
            http_response_code(200); // Acknowledge and exit
            exit();
        }

        // 2. Find the user via the transaction reference from account creation
        $user_stmt = $pdo->prepare("SELECT id FROM users WHERE virtual_account_ref = ?");
        $user_stmt->execute([$tx_ref]);
        $user = $user_stmt->fetch();

        if (!$user) {
            throw new Exception("User not found for tx_ref {$tx_ref}.");
        }
        $user_id = $user['id'];

        // 3. Credit the user's NGN wallet
        $wallet_stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = 'NGN'");
        $wallet_stmt->execute([$amount, $user_id]);

        // 4. Log the transaction
        $log_stmt = $pdo->prepare(
            "INSERT INTO transactions (user_id, type, amount, currency, status, reference, description)
             VALUES (?, 'deposit', ?, 'NGN', 'completed', ?, ?)"
        );
        $description = "Wallet funded via Flutterwave Virtual Account.";
        $log_stmt->execute([$user_id, $amount, $reference, $description]);

        $pdo->commit();
        http_response_code(200); // Success

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Flutterwave Webhook Processing Error for tx_ref {$tx_ref}: " . $e->getMessage());
        http_response_code(500); // Internal Server Error
    }
} else {
    // Not a successful charge event, so we can ignore it.
    http_response_code(200);
}
exit();
