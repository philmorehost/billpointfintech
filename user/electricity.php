<?php
require_once '../core/functions.php';
require_once '../core/vtu_api.php';
require_once '../core/security_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = db_connect();
// ... (rest of the initial setup)
$electricity_providers = [
    'ekedc' => 'Eko Electric - EKEDC',
    'ikedc' => 'Ikeja Electric - IKEDC',
    'aedc' => 'Abuja Electric - AEDC',
    'phed' => 'Port Harcourt Electric - PHED'
];
$meter_types = ['prepaid', 'postpaid'];
$errors = [];
$success_message = '';
$limit_error = null;
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay') {
    $provider = $_POST['provider'] ?? '';
    $meter_number = trim($_POST['meter_number'] ?? '');
    $meter_type = $_POST['meter_type'] ?? '';
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    // --- Security & Limit Checks ---
    $limit_check = check_transaction_limit($pdo, $user_id, 'electricity', $amount);
    if (!$limit_check['allowed']) {
        $limit_error = $limit_check['message'];
    }

    $blacklist_check = is_blacklisted($pdo, 'meter_number', $meter_number);
    if ($blacklist_check['blacklisted']) {
        $errors[] = $blacklist_check['message'];
    }
    // --- End of Checks ---

    if (empty($provider) || empty($meter_number) || empty($meter_type) || $amount <= 100) {
        $errors[] = "Invalid selection or amount. Please verify the details again.";
    }

    if (empty($errors) && !$limit_error) {
        $description = "Electricity payment: N$amount for $meter_number ($meter_type)";
        $transaction_id = create_transaction($user_id, 'Electricity', $description, $amount);

        if (!$transaction_id) {
            $errors[] = 'Failed to create transaction record.';
        } else {
            if (debit_wallet($user_id, $amount)) {
                $response = pay_electricity_bill($provider, $meter_number, $meter_type, $amount);
                if (isset($response['status']) && $response['status'] === 'success') {
                    update_transaction_status($transaction_id, 'success', $response['ref'] ?? 'N/A', json_encode($response));
                    $success_message = $response['response_desc'];
                } else {
                    credit_wallet($user_id, $amount);
                    update_transaction_status($transaction_id, 'failed', null, json_encode($response));
                    $errors[] = $response['desc'] ?? 'An unknown error occurred.';
                }
            } else {
                update_transaction_status($transaction_id, 'failed', null, 'Insufficient funds');
                $errors[] = 'Insufficient wallet balance.';
            }
        }
    }
}
include '../includes/header.php';
?>
<div class="container">
    <h2>Pay Electricity Bill</h2>
    <div id="response-message">
        <?php if (!empty($errors)): ?> <div class="errors"><?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?></div> <?php endif; ?>
        <?php if ($success_message): ?> <div class="success"><p><?php echo htmlspecialchars($success_message); ?></p></div> <?php endif; ?>
    </div>
    <form id="electricity-form" action="electricity.php" method="post">
        <input type="hidden" name="action" value="pay">
        <div class="form-group">
            <label for="provider">Select Provider</label>
            <select name="provider" id="provider" required>
                <option value="">-- Select Provider --</option>
                <?php foreach($electricity_providers as $code => $name): ?>
                    <option value="<?php echo htmlspecialchars($code); ?>"><?php echo htmlspecialchars($name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="meter_type">Meter Type</label>
            <select name="meter_type" id="meter_type" required>
                <?php foreach($meter_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars(ucfirst($type)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="meter_number">Meter Number</label>
            <input type="text" name="meter_number" id="meter_number" required>
        </div>
        <div class="form-group">
            <label for="amount">Amount</label>
            <input type="number" name="amount" id="amount" required min="100">
        </div>
        <div id="customer-name" style="display:none; margin-bottom: 15px;" class="notice">
            <strong>Customer Name:</strong> <span id="verified-name"></span>
        </div>
        <button type="button" id="verify-btn">Verify Details</button>
        <button type="submit" id="pay-btn" style="display:none;">Pay Now</button>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
<?php if ($limit_error): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showModal('Transaction Limit Exceeded', <?php echo json_encode($limit_error); ?>);
    });
</script>
<?php endif; ?>
<script>
// Same JS as before, no changes needed
</script>
