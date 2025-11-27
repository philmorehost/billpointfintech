<?php
require_once '../core/functions.php';
require_once '../core/vtu_api.php';
require_once '../core/security_functions.php'; // Include the new security functions

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = db_connect();
$stmt = $pdo->query("SELECT * FROM networks ORDER BY name");
$networks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$success_message = '';
$limit_error = null; // Variable to hold a potential limit error
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $network_code = $_POST['network'] ?? '';
    $phone_number = trim($_POST['phone_number'] ?? '');
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    // --- Security & Limit Checks ---
    $limit_check = check_transaction_limit($pdo, $user_id, 'airtime', $amount);
    if (!$limit_check['allowed']) {
        $limit_error = $limit_check['message']; // Store the specific error
    }

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
        $transaction_id = create_transaction($user_id, 'Airtime', $description, $amount);

        if (!$transaction_id) {
            $errors[] = 'Failed to create transaction record.';
        } else {
            if (debit_wallet($user_id, $amount)) {
                $response = buy_airtime($network_code, $phone_number, $amount);
                if (isset($response['status']) && $response['status'] === 'success') {
                    update_transaction_status($transaction_id, 'success', $response['ref'], json_encode($response));
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
    <h2>Buy Airtime</h2>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <div class="success"><p><?php echo htmlspecialchars($success_message); ?></p></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="network">Network</label>
            <select name="network" id="network" required>
                <option value="">-- Select Network --</option>
                <?php foreach ($networks as $net): ?>
                    <option value="<?php echo htmlspecialchars($net['code']); ?>"><?php echo htmlspecialchars($net['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="phone_number">Phone Number</label>
            <input type="tel" name="phone_number" id="phone_number" required>
        </div>
        <div class="form-group">
            <label for="amount">Amount</label>
            <input type="number" name="amount" id="amount" required min="50">
        </div>
        <button type="submit">Buy Now</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>

<?php if ($limit_error): ?>
<script>
    // If a limit error occurred on the server, show the modal on page load
    document.addEventListener('DOMContentLoaded', function() {
        showModal('Transaction Limit Exceeded', <?php echo json_encode($limit_error); ?>);
    });
</script>
<?php endif; ?>
