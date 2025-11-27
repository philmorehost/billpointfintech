<?php
require_once '../core/vtu_api.php';
require_once '../core/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = db_connect();
$stmt = $pdo->query("SELECT * FROM data_plans ORDER BY network, price");
$data_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_id = trim($_POST['plan_id']);
    $phone_number = trim($_POST['phone_number']);
    $user_id = $_SESSION['user_id'];

    if (empty($plan_id) || empty($phone_number)) {
        $errors[] = 'All fields are required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM data_plans WHERE id = ?");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            $errors[] = 'Invalid data plan selected.';
        } else {
            $amount = $plan['price'];
            $description = "Data purchase: {$plan['quantity']} of {$plan['type']} for $phone_number on {$plan['network']}";
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
                        $errors[] = isset($response['desc']) ? $response['desc'] : 'An unknown error occurred.';
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

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="success">
            <p><?php echo $success_message; ?></p>
        </div>
    <?php endif; ?>

    <form action="data.php" method="post">
        <div class="form-group">
            <label for="plan_id">Select Plan</label>
            <select name="plan_id" id="plan_id" required>
                <option value="">-- Select a Plan --</option>
                <?php foreach ($data_plans as $plan): ?>
                    <option value="<?php echo $plan['id']; ?>" data-price="<?php echo $plan['price']; ?>">
                        <?php echo strtoupper($plan['network']) . " " . $plan['quantity'] . " (" . strtoupper($plan['type']) . ") - &#8358;" . $plan['price']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="phone_number">Phone Number</label>
            <input type="tel" name="phone_number" id="phone_number" required>
        </div>
        <p><strong>Price:</strong> <span id="price-display">&#8358;0.00</span></p>
        <button type="submit">Buy Now</button>
    </form>
</div>

<script>
document.getElementById('plan_id').addEventListener('change', function() {
    var price = this.options[this.selectedIndex].getAttribute('data-price');
    document.getElementById('price-display').innerHTML = price ? '&#8358;' + price : '&#8358;0.00';
});
</script>

<?php include '../includes/footer.php'; ?>
