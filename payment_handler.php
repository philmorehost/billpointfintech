<?php
// payment_handler.php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
require_once 'core/paystack_api.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed. Please try again.');
        header('Location: fund_wallet.php');
        exit();
    }

    $action = $_POST['action'];

    if ($action === 'initialize_funding') {
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

        // Basic validation
        if (!$amount || $amount < 100) {
            set_flash_message('error', 'Invalid amount. Minimum deposit is NGN 100.');
            header('Location: fund_wallet.php');
            exit();
        }

        $user_id = $_SESSION['user_id'];

        // Get user's email from the database
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user) {
            // This should not happen for a logged-in user
            set_flash_message('error', 'User not found.');
            header('Location: fund_wallet.php');
            exit();
        }
        $user_email = $user['email'];

        // Generate a unique transaction reference
        $reference = 'blp_' . uniqid() . time();

        // Convert amount to kobo for Paystack
        $amount_kobo = $amount * 100;

        try {
             // Log the pending transaction in our database
            $stmt = $pdo->prepare(
                "INSERT INTO transactions (user_id, type, amount, currency, status, reference, description)
                 VALUES (?, 'deposit', ?, 'NGN', 'pending', ?, ?)"
            );
            $stmt->execute([$user_id, $amount, $reference, 'Wallet funding initiated.']);

            // Initialize Paystack transaction
            $paystack = new PaystackAPI();
            $response = $paystack->initializeTransaction($user_email, $amount_kobo, $reference);

            if ($response && $response['status'] === true) {
                // Redirect user to Paystack's payment page
                header('Location: ' . $response['data']['authorization_url']);
                exit();
            } else {
                // Paystack API call failed
                $message = $response['message'] ?? 'Failed to initialize payment. Please try again.';
                set_flash_message('error', 'API Error: ' . $message);
                header('Location: fund_wallet.php');
                exit();
            }

        } catch (Exception $e) {
            set_flash_message('error', 'An unexpected error occurred. ' . $e->getMessage());
            header('Location: fund_wallet.php');
            exit();
        }
    }
}

// Redirect back if accessed directly or with an unknown action
header('Location: fund_wallet.php');
exit();
