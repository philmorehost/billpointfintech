<?php
// user/ajax_exam_handler.php
require_once '../core/auth_check.php';
require_once '../core/functions.php';
require_once '../core/vtu_api.php';
require_once '../core/security_functions.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$pdo = db_connect();

$exam_code = $_POST['exam_code'] ?? null;
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

// --- Validation ---
if (empty($exam_code) || !$quantity || $quantity <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a valid exam type and quantity.']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM exam_products WHERE code = ?");
$stmt->execute([$exam_code]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid exam product selected.']);
    exit;
}

$amount = $product['price'] * $quantity;

try {
    // --- Security & Limit Checks ---
    $limit_check = check_transaction_limit($pdo, $user_id, 'exam', $amount);
    if (!$limit_check['allowed']) {
        echo json_encode(['status' => 'error', 'message' => $limit_check['message']]);
        exit;
    }

    $pdo->beginTransaction();

    if (!debit_wallet($user_id, $amount)) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Insufficient wallet balance.']);
        exit;
    }

    $description = "Exam Pin: $quantity x {$product['name']}";
    $transaction_id = create_transaction($user_id, 'Exam Pin', $description, $amount, 'pending');

    $response = purchase_exam_pin($exam_code, $quantity);

    if (is_array($response) && isset($response['status']) && $response['status'] === 'success') {
        // The response for exam pins often contains the actual pins in a `pins` key.
        // We will update the transaction description to include this.
        $pin_details = $response['pins'] ?? 'Check API response for details.';
        $new_description = $description . " - Pins: " . $pin_details;

        $stmt = $pdo->prepare("UPDATE transactions SET description = ? WHERE id = ?");
        $stmt->execute([$new_description, $transaction_id]);

        update_transaction_status($transaction_id, 'success', $response['ref'] ?? null, json_encode($response));
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => $response['response_desc'] ?? 'Purchase successful! Your pins have been generated.']);
    } else {
        credit_wallet($user_id, $amount);
        update_transaction_status($transaction_id, 'failed', null, json_encode($response));
        $pdo->commit();
        echo json_encode(['status' => 'error', 'message' => $response['desc'] ?? 'Purchase failed.']);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Exam Pin Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An internal server error occurred.']);
}
?>
