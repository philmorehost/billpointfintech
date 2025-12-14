<?php
// user/ajax_cable_handler.php
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
    $iuc_number = trim($_POST['iuc_number'] ?? '');

    if (empty($provider) || empty($iuc_number)) {
        echo json_encode(['status' => 'error', 'message' => 'Provider and IUC/Smartcard number are required.']);
        exit;
    }

    $response = verify_cable_customer($provider, $iuc_number);

    if (isset($response['status']) && $response['status'] === 'success') {
        echo json_encode(['status' => 'success', 'customer_name' => $response['customer_name'] ?? 'N/A']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $response['desc'] ?? 'Failed to verify customer details.']);
    }
    exit;
}

if ($action === 'pay') {
    $provider = $_POST['provider'] ?? null;
    $iuc_number = trim($_POST['iuc_number'] ?? '');
    $package_code = $_POST['package_code'] ?? null;

    if (empty($provider) || empty($iuc_number) || empty($package_code)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid payment details provided.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT price FROM cable_tv_packages WHERE package_code = ? AND provider = ?");
    $stmt->execute([$package_code, $provider]);
    $amount = $stmt->fetchColumn();

    if (!$amount) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid package selected.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        if (!debit_wallet($user_id, $amount)) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Insufficient wallet balance.']);
            exit;
        }

        $description = "Cable TV: $package_code for $iuc_number";
        $transaction_id = create_transaction($user_id, 'Cable TV', $description, $amount, 'pending');

        $response = pay_cable_bill($provider, $iuc_number, $package_code);

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
        error_log("Cable TV Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'An internal server error occurred.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
