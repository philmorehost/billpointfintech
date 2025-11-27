<?php
require_once '../core/functions.php';
require_once '../core/vtu_api.php';

// ... (PHP logic from previous turn)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// In a real app, this would come from a database table
$electricity_providers = [
    'ekedc' => 'Eko Electric - EKEDC',
    'ikedc' => 'Ikeja Electric - IKEDC',
    'aedc' => 'Abuja Electric - AEDC',
    'phed' => 'Port Harcourt Electric - PHED'
];

$meter_types = ['prepaid', 'postpaid'];

$errors = [];
$success_message = '';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay') {
    $provider = $_POST['provider'] ?? '';
    $meter_number = $_POST['meter_number'] ?? '';
    $meter_type = $_POST['meter_type'] ?? '';
    $amount = $_POST['amount'] ?? 0;

    if (empty($provider) || empty($meter_number) || empty($meter_type) || $amount <= 100) {
        $errors[] = "Invalid selection or amount. Please verify the details again.";
    } else {
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
                    credit_wallet($user_id, $amount); // Refund
                    update_transaction_status($transaction_id, 'failed', null, json_encode($response));
                    $errors[] = $response['desc'] ?? 'An unknown error occurred during payment.';
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
        <?php if (!empty($errors)): ?>
            <div class="errors"><?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="success"><p><?php echo htmlspecialchars($success_message); ?></p></div>
        <?php endif; ?>
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

<script>
// Logic is safe as it uses textContent and secured server responses.
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('electricity-form');
    const verifyBtn = document.getElementById('verify-btn');
    const payBtn = document.getElementById('pay-btn');
    const responseDiv = document.getElementById('response-message');
    const customerNameDiv = document.getElementById('customer-name');
    const verifiedNameSpan = document.getElementById('verified-name');

    function resetFormState() {
        payBtn.style.display = 'none';
        verifyBtn.style.display = 'inline-block';
        customerNameDiv.style.display = 'none';
    }

    form.addEventListener('change', resetFormState);

    verifyBtn.addEventListener('click', async function() {
        responseDiv.innerHTML = '';
        const formData = new FormData(form);
        formData.append('action', 'verify_electricity');

        if (!formData.get('provider') || !formData.get('meter_number') || !formData.get('meter_type')) {
            responseDiv.innerHTML = '<div class="errors"><p>Please fill in all required fields.</p></div>';
            return;
        }

        verifyBtn.disabled = true;
        verifyBtn.textContent = 'Verifying...';

        try {
            const response = await fetch('ajax_handler.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.status === 'success') {
                verifiedNameSpan.textContent = data.customer_name;
                customerNameDiv.style.display = 'block';
                verifyBtn.style.display = 'none';
                payBtn.style.display = 'inline-block';
                responseDiv.innerHTML = '<div class="success"><p>Customer verified successfully.</p></div>';
            } else {
                const errorMessage = document.createElement('p');
                errorMessage.textContent = data.message || 'Verification failed.';
                responseDiv.innerHTML = '<div class="errors"></div>';
                responseDiv.firstChild.appendChild(errorMessage);
            }
        } catch (error) {
            responseDiv.innerHTML = '<div class="errors"><p>An error occurred. Please try again.</p></div>';
        } finally {
            verifyBtn.disabled = false;
            verifyBtn.textContent = 'Verify Details';
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
