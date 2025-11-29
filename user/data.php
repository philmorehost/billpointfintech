<?php
require_once '../core/functions.php';
require_once '../core/vtu_api.php';
require_once '../core/security_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = db_connect();
// Fetch data plans and group them by network
$stmt = $pdo->query("SELECT * FROM data_plans ORDER BY network, price");
$data_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

$plans_by_network = [];
foreach ($data_plans as $plan) {
    $plans_by_network[$plan['network']][] = $plan;
}

$errors = [];
$success_message = '';
$limit_error = null;
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_id = $_POST['plan_id'] ?? '';
    $phone_number = trim($_POST['phone_number'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM data_plans WHERE id = ?");
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        $errors[] = 'Invalid data plan selected.';
    } else {
        $amount = $plan['price'];

        $limit_check = check_transaction_limit($pdo, $user_id, 'data', $amount);
        if (!$limit_check['allowed']) {
            $limit_error = $limit_check['message'];
        }

        $blacklist_check = is_blacklisted($pdo, 'phone', $phone_number);
        if ($blacklist_check['blacklisted']) {
            $errors[] = $blacklist_check['message'];
        }

        if (empty($phone_number)) $errors[] = "Phone number is required.";

        if (empty($errors) && !$limit_error) {
            $description = "Data purchase: {$plan['quantity']} of {$plan['type']} for $phone_number";
            $transaction_id = create_transaction($user_id, 'Data', $description, $amount);

            if (!$transaction_id) {
                $errors[] = 'Failed to create transaction record.';
            } else {
                if (debit_wallet($user_id, $amount)) {
                    $response = buy_data($plan['network'], $phone_number, $plan['type'], $plan['quantity']);
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
}

include '../includes/header.php';
?>
<div class="container">
    <h2>Buy Data</h2>
    <?php if (!empty($errors)): ?> <div class="errors"><?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?></div> <?php endif; ?>
    <?php if ($success_message): ?> <div class="success"><p><?php echo htmlspecialchars($success_message); ?></p></div> <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="plan_id">Select Plan</label>
            <select name="plan_id" id="plan_id" required>
                <option value="">-- Select a Plan --</option>
                <?php foreach ($plans_by_network as $network => $plans): ?>
                    <optgroup label="<?php echo htmlspecialchars(strtoupper($network)); ?>">
                        <?php foreach ($plans as $p): ?>
                            <option value="<?php echo htmlspecialchars($p['id']); ?>" data-price="<?php echo htmlspecialchars($p['price']); ?>">
                                <?php echo htmlspecialchars($p['quantity'] . " (" . strtoupper($p['type']) . ") - ₦" . $p['price']); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="phone_number">Phone Number</label>
            <input type="tel" name="phone_number" id="phone_number" required>
        </div>
        <p><strong>Price:</strong> <span id="price-display">₦0.00</span></p>
        <button type="submit">Buy Now</button>
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
document.getElementById('plan_id').addEventListener('change', function() {
    var price = this.options[this.selectedIndex].getAttribute('data-price');
    document.getElementById('price-display').innerHTML = price ? '₦' + parseFloat(price).toFixed(2) : '₦0.00';
});
// Trigger change on load if a plan is pre-selected
document.getElementById('plan_id').dispatchEvent(new Event('change'));
</script>
