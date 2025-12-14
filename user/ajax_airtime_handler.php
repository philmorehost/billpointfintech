<?php
// user/ajax_airtime_handler.php
require_once '../core/config.php';
require_once '../core/functions.php';
require_once '../core/vtu_api.php';
require_once '../core/security_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$pdo = db_connect();

$network_code = $_POST['network'] ?? '';
$phone_number = trim($_POST['phone_number'] ?? '');
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

// --- Form Validation ---
if (empty($network_code) || !in_array($network_code, ['MTN', 'GLO', 'AIRTEL', '9MOBILE'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a valid network.']);
    exit;
}
if (empty($phone_number) || !preg_match('/^\d{11}$/', $phone_number)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid 11-digit phone number.']);
    exit;
}
if ($amount === false || $amount < 50) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid amount (minimum ₦50).']);
    exit;
}

try {
    // --- Security & Limit Checks ---
    $limit_check = check_transaction_limit($pdo, $user_id, 'airtime', $amount);
    if (!$limit_check['allowed']) {
        echo json_encode(['status' => 'error', 'message' => $limit_check['message']]);
        exit;
    }

    $blacklist_check = is_blacklisted($pdo, 'phone', $phone_number);
    if ($blacklist_check['blacklisted']) {
        echo json_encode(['status' => 'error', 'message' => $blacklist_check['message']]);
        exit;
    }

    // --- Transaction Processing ---
    $pdo->beginTransaction();

    $description = "Airtime purchase: ₦$amount for $phone_number";

    // 1. Debit user's wallet
    if (!debit_wallet($user_id, $amount)) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Insufficient wallet balance.']);
        exit;
    }

    // 2. Create initial transaction record
    $transaction_id = create_transaction($user_id, 'Airtime', $description, $amount, 'pending', null, null, $phone_number);
    if (!$transaction_id) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Failed to create transaction record.']);
        exit;
    }

    // 3. Call external API
    $response = buy_airtime($network_code, $phone_number, $amount);

    // 4. Process API response
    if (is_array($response) && isset($response['status']) && $response['status'] === 'success') {
        // API call was successful
        update_transaction_status($transaction_id, 'success', $response['ref'] ?? null, json_encode($response));
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => $response['response_desc'] ?? 'Airtime purchase successful!']);
    } else {
        // API call failed or returned an error, treat as pending for query
        // The transaction status is already 'pending', so we just commit the debit and record.
        // A cron job will later verify the final status.
        $pdo->commit();
        $fail_message = $response['desc'] ?? 'Your request has been submitted and is being processed.';
        echo json_encode(['status' => 'success', 'message' => $fail_message . ' The final status will be updated shortly.']);
    }

} catch (Exception $e) {
    // Rollback on any unexpected error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Airtime Purchase Error: " . $e->getMessage()); // Log error for admin
    echo json_encode(['status' => 'error', 'message' => 'An internal server error occurred. Please try again later.']);
}
?>
