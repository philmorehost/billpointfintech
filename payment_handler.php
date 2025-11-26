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

    if ($action === 'generate_virtual_account') {
        $user_id = $_SESSION['user_id'];

        $stmt = $pdo->prepare("SELECT full_name, email, phone, kyc_level FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user || $user['kyc_level'] < 1) {
            set_flash_message('error', 'You must complete KYC verification to generate a virtual account.');
            header('Location: virtual_account.php');
            exit();
        }

        list($first_name, $last_name) = explode(' ', $user['full_name'], 2);
        $tx_ref = 'VA-' . $user_id . '-' . time();

        require_once 'core/flutterwave_api.php';
        $flutterwave = new FlutterwaveAPI($config['settings']['flutterwave_secret_key'] ?? null);
        $response = $flutterwave->generate_virtual_account($user['email'], $first_name, $last_name, $user['phone'], $tx_ref);

        if (isset($response['status']) && $response['status'] === 'success' && isset($response['data']['account_number'])) {
            $account_number = $response['data']['account_number'];
            $bank_name = $response['data']['bank_name'];

            $update_stmt = $pdo->prepare("UPDATE users SET virtual_account_number = ?, virtual_bank_name = ?, virtual_account_ref = ? WHERE id = ?");
            $update_stmt->execute([$account_number, $bank_name, $tx_ref, $user_id]);

            set_flash_message('success', 'Your virtual account has been generated successfully.');
        } else {
            $message = $response['message'] ?? 'Failed to generate virtual account. Please try again later.';
            set_flash_message('error', $message);
        }
        header('Location: virtual_account.php');
        exit();
    }

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
            $paystack = new PaystackAPI($config['settings']['paystack_secret_key'] ?? null);
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
            error_log("Payment Handler Error: " . $e->getMessage());
            set_flash_message('error', 'An unexpected error occurred. Please try again or contact support.');
            header('Location: fund_wallet.php');
            exit();
        }
    }
}

// Redirect back if accessed directly or with an unknown action
header('Location: fund_wallet.php');
exit();
