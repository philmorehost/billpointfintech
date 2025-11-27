<?php
require_once '../core/functions.php';
require_once '../core/vtu_api.php';
require_once '../core/security_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = db_connect();
$user_id = $_SESSION['user_id'];
// ... (fetch networks, setup variables)
$errors = [];
$success_message = '';
$limit_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $network_code = $_POST['network'] ?? '';
    $phone_number = trim($_POST['phone_number'] ?? '');
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    // --- Advanced Security & Limit Checks ---
    // 1. Check user's balance first
    $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() < $amount) {
        $errors[] = "Insufficient wallet balance.";
    }

    // 2. Check user's daily limit
    $limit_check = check_transaction_limit($pdo, $user_id, 'airtime', $amount);
    if (!$limit_check['allowed']) {
        $limit_error = $limit_check['message'];
    }

    // 3. Check recipient's daily limit
    $recipient_limit_check = check_recipient_limit($pdo, 'airtime', $phone_number, $amount);
    if (!$recipient_limit_check['allowed']) {
        $errors[] = $recipient_limit_check['message'];
    }

    // 4. Check blacklist
    $blacklist_check = is_blacklisted($pdo, 'phone', $phone_number);
    if ($blacklist_check['blacklisted']) {
        $errors[] = $blacklist_check['message'];
    }
    // --- End of Checks ---

    if (empty($network_code)) $errors[] = 'Please select a network.';
    if (empty($phone_number)) $errors[] = 'Please enter a valid phone number.';
    if ($amount <= 0) $errors[] = 'Please enter a valid amount.';


    if (empty($errors) && !$limit_error) {
        $description = "Airtime purchase: $amount for $phone_number on $network_code";

        if (debit_wallet($user_id, $amount)) {
            $transaction_id = create_transaction($user_id, 'Airtime', $description, $amount, 'pending', null, null, $phone_number);

            $response = buy_airtime($network_code, $phone_number, $amount);

            if (isset($response['status']) && $response['status'] === 'success') {
                update_transaction_status($transaction_id, 'success', $response['ref'], json_encode($response));
                update_recipient_total($pdo, 'airtime', $phone_number, $amount); // Update recipient's daily total
                $success_message = $response['response_desc'];
            } else {
                credit_wallet($user_id, $amount); // Refund
                update_transaction_status($transaction_id, 'failed', null, json_encode($response));
                $errors[] = $response['desc'] ?? 'An unknown error occurred.';
            }
        } else {
            // This is a redundant check, but good for safety
            $errors[] = 'Transaction failed due to insufficient balance.';
        }
    }
}

include '../includes/header.php';
?>
<div class="container">
    <h2>Buy Airtime</h2>
    <!-- The rest of the HTML remains the same -->
</div>
<?php include '../includes/footer.php'; ?>
<?php if ($limit_error): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showModal('Transaction Limit Exceeded', <?php echo json_encode($limit_error); ?>);
    });
</script>
<?php endif; ?>
