<?php
// user/ajax_transfer_handler.php
require_once '../core/auth_check.php';
require_once '../core/functions.php';
require_once '../core/beewave_api.php';
require_once '../core/security_functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? null;
$user_id = $_SESSION['user_id'];
$pdo = db_connect();

if ($action === 'verify') {
    $account_number = $_POST['account_number'] ?? null;
    $bank_code = $_POST['bank_code'] ?? null;

    if (!$account_number || !$bank_code) {
        echo json_encode(['status' => 'error', 'message' => 'Bank and account number are required.']);
        exit;
    }

    $response = verify_bank_account_beewave($account_number, $bank_code);

    if (isset($response['status']) && $response['status'] === true) {
        echo json_encode(['status' => 'success', 'data' => $response['data']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $response['message'] ?? 'Failed to verify account.']);
    }
    exit;
}

if ($action === 'transfer') {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $narration = $_POST['narration'] ?? 'Billpoint Transfer';
    $enquiry_id = $_POST['enquiry_id'] ?? null;
    $bank_code = $_POST['verified_bank_code'] ?? null;
    $account_number = $_POST['verified_account_number'] ?? null;

    // --- Validation ---
    if (!$amount || $amount < 100 || !$enquiry_id || !$bank_code || !$account_number) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid transfer details provided.']);
        exit;
    }

    try {
        // --- Security Checks ---
        $limit_check = check_transaction_limit($pdo, $user_id, 'transfer', $amount);
        if (!$limit_check['allowed']) {
            echo json_encode(['status' => 'error', 'message' => $limit_check['message']]);
            exit;
        }

        $pdo->beginTransaction();

        // --- Debit Wallet First ---
        if (!debit_wallet($user_id, $amount)) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Insufficient wallet balance.']);
            exit;
        }

        $transaction_id = create_transaction($user_id, 'Bank Transfer', "Transfer to $account_number", $amount, 'pending');

        // --- Call API ---
        $transfer_details = [
            'enquiry_id' => $enquiry_id,
            'account_number' => $account_number,
            'bank_code' => $bank_code,
            'amount' => $amount,
            'narration' => $narration,
        ];
        $response = transfer_funds_beewave($transfer_details);

        if (isset($response['status']) && $response['status'] === true) {
            update_transaction_status($transaction_id, 'success', $response['data']['transaction_ref'] ?? null, json_encode($response));
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Transfer successful!']);
        } else {
            // Refund the user if the transfer failed
            credit_wallet($user_id, $amount);
            update_transaction_status($transaction_id, 'failed', null, json_encode($response));
            $pdo->commit(); // Commit the refund and failed transaction
            echo json_encode(['status' => 'error', 'message' => $response['message'] ?? 'Transfer failed.']);
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Transfer Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'An internal server error occurred.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
