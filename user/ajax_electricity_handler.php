<?php
// user/ajax_electricity_handler.php
require_once '../core/auth_check.php';
require_once '../core/functions.php';
require_once '../core/vtu_api.php';
require_once '../core/security_functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? null;
$user_id = $_SESSION['user_id'];
$pdo = db_connect();

if ($action === 'verify') {
    $provider = $_POST['provider'] ?? null;
    $meter_number = trim($_POST['meter_number'] ?? '');
    $meter_type = $_POST['meter_type'] ?? null;

    if (empty($provider) || empty($meter_number) || empty($meter_type)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required for verification.']);
        exit;
    }

    $response = verify_electricity_customer($provider, $meter_number, $meter_type);

    if (isset($response['status']) && $response['status'] === 'success') {
        echo json_encode(['status' => 'success', 'customer_name' => $response['customer_name'] ?? 'N/A']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $response['desc'] ?? 'Failed to verify meter details.']);
    }
    exit;
}

if ($action === 'pay') {
    $provider = $_POST['provider'] ?? null;
    $meter_number = trim($_POST['meter_number'] ?? '');
    $meter_type = $_POST['meter_type'] ?? null;
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    if (empty($provider) || empty($meter_number) || empty($meter_type) || !$amount || $amount < 100) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid payment details. Amount must be at least ₦100.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        if (!debit_wallet($user_id, $amount)) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Insufficient wallet balance.']);
            exit;
        }

        $description = "Electricity: ₦$amount for meter $meter_number";
        $transaction_id = create_transaction($user_id, 'Electricity', $description, $amount, 'pending');

        $response = pay_electricity_bill($provider, $meter_number, $meter_type, $amount);

        if (is_array($response) && isset($response['status']) && $response['status'] === 'success') {
            update_transaction_status($transaction_id, 'success', $response['ref'] ?? null, json_encode($response));
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => $response['response_desc'] ?? 'Payment successful!']);
        } else {
            credit_wallet($user_id, $amount);
            update_transaction_status($transaction_id, 'failed', null, json_encode($response));
            $pdo->commit();
            echo json_encode(['status' => 'error', 'message' => $response['desc'] ?? 'Payment failed.']);
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Electricity Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'An internal server error occurred.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
