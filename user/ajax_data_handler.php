<?php
// user/ajax_data_handler.php
require_once '../core/auth_check.php';
require_once '../core/functions.php';
require_once '../core/vtu_api.php';
require_once '../core/security_functions.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$pdo = db_connect();

$plan_id = $_POST['plan_id'] ?? null;
$phone_number = trim($_POST['phone_number'] ?? '');

// --- Validation ---
if (empty($plan_id) || empty($phone_number) || !preg_match('/^\d{11}$/', $phone_number)) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a valid plan and enter an 11-digit phone number.']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM data_plans WHERE id = ?");
$stmt->execute([$plan_id]);
$plan = $stmt->fetch();

if (!$plan) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data plan selected.']);
    exit;
}

$amount = $plan['price'];

try {
    // --- Security & Limit Checks ---
    $limit_check = check_transaction_limit($pdo, $user_id, 'data', $amount);
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

    $description = "Data purchase: {$plan['quantity']} of {$plan['type']} for $phone_number";
    $transaction_id = create_transaction($user_id, 'Data', $description, $amount, 'pending');

    // --- Call API ---
    $response = buy_data($plan['network'], $phone_number, $plan['type'], $plan['quantity']);

    if (is_array($response) && isset($response['status']) && $response['status'] === 'success') {
        update_transaction_status($transaction_id, 'success', $response['ref'] ?? null, json_encode($response));
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => $response['response_desc'] ?? 'Data purchase successful!']);
    } else {
        // Refund and mark as failed
        credit_wallet($user_id, $amount);
        update_transaction_status($transaction_id, 'failed', null, json_encode($response));
        $pdo->commit();
        echo json_encode(['status' => 'error', 'message' => $response['desc'] ?? 'The service is currently unavailable.']);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Data Purchase Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An internal server error occurred.']);
}
?>
