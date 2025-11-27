<?php
require_once '../core/vtu_api.php';
require_once '../core/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = db_connect();
$stmt = $pdo->query("SELECT * FROM networks ORDER BY name");
$networks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $network_code = trim($_POST['network']);
    $phone_number = trim($_POST['phone_number']);
    $amount = trim($_POST['amount']);
    $user_id = $_SESSION['user_id'];

    if (empty($network_code)) {
        $errors[] = 'Please select a network.';
    }

    // ... (rest of the validation and processing logic remains the same)

    if (empty($errors)) {
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
                    credit_wallet($user_id, $amount); // Refund the user
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

include '../includes/header.php';
?>

<div class="container">
    <h2>Buy Airtime</h2>

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

    <form action="airtime.php" method="post">
        <div class="form-group">
            <label for="network">Network</label>
            <select name="network" id="network" required>
                <option value="">-- Select Network --</option>
                <?php foreach ($networks as $net): ?>
                    <option value="<?php echo $net['code']; ?>"><?php echo $net['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="phone_number">Phone Number</label>
            <input type="tel" name="phone_number" id="phone_number" required>
        </div>
        <div class="form-group">
            <label for="amount">Amount</label>
            <input type="number" name="amount" id="amount" required>
        </div>
        <button type="submit">Buy Now</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
