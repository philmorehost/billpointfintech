<?php
// user/payment_handler.php
require_once '../core/config.php';
require_once '../core/functions.php';

// Check if the user is authenticated for initialization
if (isset($_POST['action']) && $_POST['action'] === 'initialize_paystack') {
    if (!isset($_SESSION['user_id'])) {
        die('Authentication required.');
    }
    initialize_paystack();
}
// Handle webhook notifications from Paystack
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'])) {
    handle_paystack_webhook();
}
else {
    http_response_code(400);
    echo "Invalid request.";
}

function initialize_paystack() {
    $user_id = $_SESSION['user_id'];
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    if (!$amount || $amount < 100) {
        die('Invalid amount specified. Minimum is NGN 100.');
    }

    $pdo = db_connect();
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_email = $stmt->fetchColumn();

    $transaction_ref = 'BP_' . uniqid() . '_' . $user_id;

    $post_data = [
        'email' => $user_email,
        'amount' => $amount * 100, // Paystack expects amount in kobo
        'reference' => $transaction_ref,
        'callback_url' => 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/payment-callback.php',
        'metadata' => [
            'user_id' => $user_id,
            'description' => "Wallet funding for user #{$user_id}"
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/initialize');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        die("cURL Error: " . $err);
    }

    $result = json_decode($response, true);

    if ($result['status']) {
        header('Location: ' . $result['data']['authorization_url']);
        exit();
    } else {
        die('Paystack API Error: ' . $result['message']);
    }
}

function handle_paystack_webhook() {
    $payload = @file_get_contents('php://input');
    $paystack_signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'];

    // Verify the webhook signature
    if (hash_hmac('sha512', $payload, PAYSTACK_SECRET_KEY) !== $paystack_signature) {
        http_response_code(401);
        die('Invalid signature');
    }

    $event = json_decode($payload, true);

    if ($event['event'] === 'charge.success') {
        $data = $event['data'];
        $user_id = $data['metadata']['user_id'] ?? null;
        $amount_kobo = $data['amount'];
        $amount_ngn = $amount_kobo / 100;
        $reference = $data['reference'];

        if ($user_id && $amount_ngn > 0) {
            $pdo = db_connect();
            $pdo->beginTransaction();
            try {
                // Check if this transaction reference has already been processed
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE reference = ?");
                $stmt->execute([$reference]);
                if ($stmt->fetchColumn() > 0) {
                    // Already processed, acknowledge webhook but do nothing
                    $pdo->commit();
                    http_response_code(200);
                    exit('Already processed.');
                }

                // Credit the user's wallet
                credit_wallet($user_id, $amount_ngn);

                // Create a transaction record
                $description = "Wallet funding via Paystack. Ref: " . $reference;
                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, service, description, amount, status, reference) VALUES (?, 'Wallet Funding', ?, ?, 'success', ?)");
                $stmt->execute([$user_id, $description, $amount_ngn, $reference]);

                $pdo->commit();
                http_response_code(200);
                echo "Webhook processed successfully.";
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Paystack Webhook Error: " . $e->getMessage());
                http_response_code(500);
                die('Internal server error');
            }
        }
    } else {
        http_response_code(200);
        echo "Webhook received, but no action taken for this event.";
    }
}
