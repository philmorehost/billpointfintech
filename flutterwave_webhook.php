<?php
// flutterwave_webhook.php
require_once 'includes/bootstrap.php';
require_once 'core/flutterwave_api.php';

// Retrieve the request's body and parse it as JSON
$json = file_get_contents('php://input');
$event = json_decode($json);

if (isset($event->event) && $event->event === 'charge.completed') {
    $transaction_id = $event->data->id;
    $tx_ref = $event->data->tx_ref;

    // Verify the transaction with Flutterwave
    $flutterwave = new FlutterwaveAPI($config['settings']['flutterwave_secret_key'] ?? null);
    $response = $flutterwave->verify_transaction($transaction_id);

    if ($response && $response['status'] === 'success' && $response['data']['tx_ref'] === $tx_ref) {
        $amount = $response['data']['amount'];
        $currency = $response['data']['currency'];
        $status = $response['data']['status'];

        // Extract user_id from tx_ref
        $tx_ref_parts = explode('-', $tx_ref);
        $user_id = $tx_ref_parts[1];

        if ($status === 'successful') {
            try {
                $pdo->beginTransaction();

                // Check if transaction has already been processed
                $stmt = $pdo->prepare("SELECT id FROM transactions WHERE reference = ?");
                $stmt->execute([$tx_ref]);
                if (!$stmt->fetch()) {
                    // Credit user's wallet
                    $wallet_stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = ?");
                    $wallet_stmt->execute([$amount, $user_id, $currency]);

                    // Log the transaction
                    $log_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status, reference, description) VALUES (?, 'deposit', ?, ?, 'completed', ?, ?)");
                    $log_stmt->execute([$user_id, $amount, $currency, $tx_ref, 'Wallet funded via virtual account.']);
                }

                $pdo->commit();
                http_response_code(200);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Flutterwave Webhook Error: " . $e->getMessage());
                http_response_code(500);
            }
        }
    }
}

exit();
