<?php
require_once '../core/functions.php';
require_once '../core/vtu_api.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// In a real app, this would come from a database table
$cable_providers = [
    'dstv' => 'DSTV',
    'gotv' => 'GOTV',
    'startimes' => 'Startimes'
];

// Simplified list of packages. A real app would fetch this dynamically with prices.
$packages = [
    'dstv' => ['Padi' => 4400, 'Yanga' => 6000, 'Confam' => 11000],
    'gotv' => ['Smallie' => 1900, 'Jinja' => 3900, 'Jolli' => 5800],
    'startimes' => ['Nova' => 1900, 'Basic' => 3700, 'Smart' => 4700]
];

$errors = [];
$success_message = '';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay') {
    $provider = $_POST['provider'] ?? '';
    $iuc_number = $_POST['iuc_number'] ?? '';
    $package = $_POST['package'] ?? '';

    // Find the price from our packages array
    $amount = $packages[$provider][ucfirst($package)] ?? 0;

    if (empty($provider) || empty($iuc_number) || empty($package) || $amount <= 0) {
        $errors[] = "Invalid selection. Please verify the details again.";
    } else {
        $description = "Cable TV payment: $package for $iuc_number on $provider";
        $transaction_id = create_transaction($user_id, 'Cable TV', $description, $amount);

        if (!$transaction_id) {
            $errors[] = 'Failed to create transaction record.';
        } else {
            if (debit_wallet($user_id, $amount)) {
                $response = pay_cable_bill($provider, $iuc_number, $package);
                if (isset($response['status']) && $response['status'] === 'success') {
                    update_transaction_status($transaction_id, 'success', $response['ref'], json_encode($response));
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
    <h2>Pay Cable TV Subscription</h2>

    <div id="response-message">
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="success"><p><?php echo htmlspecialchars($success_message); ?></p></div>
        <?php endif; ?>
    </div>

    <form id="cable-form" action="cable.php" method="post">
        <input type="hidden" name="action" value="pay">
        <div class="form-group">
            <label for="provider">Select Provider</label>
            <select name="provider" id="provider" required>
                <option value="">-- Select Provider --</option>
                <?php foreach($cable_providers as $code => $name): ?>
                    <option value="<?php echo htmlspecialchars($code); ?>"><?php echo htmlspecialchars($name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="iuc_number">IUC / Smartcard Number</label>
            <input type="text" name="iuc_number" id="iuc_number" required>
        </div>
        <div class="form-group">
            <label for="package">Select Package</label>
            <select name="package" id="package" required disabled>
                <option value="">-- Select provider first --</option>
            </select>
        </div>

        <div id="customer-name" style="display:none; margin-bottom: 15px;" class="notice">
            <strong>Customer Name:</strong> <span id="verified-name"></span>
        </div>

        <button type="button" id="verify-btn">Verify Details</button>
        <button type="submit" id="pay-btn" style="display:none;">Pay Now</button>
    </form>
</div>

<script>
// The script is now safe because the AJAX handler and the PHP error display are secured
// All dynamic content is handled by textContent, which prevents XSS.
// No changes are needed here.
document.addEventListener('DOMContentLoaded', function() {
    const providerSelect = document.getElementById('provider');
    const packageSelect = document.getElementById('package');
    const verifyBtn = document.getElementById('verify-btn');
    const payBtn = document.getElementById('pay-btn');
    const iucInput = document.getElementById('iuc_number');
    const customerNameDiv = document.getElementById('customer-name');
    const verifiedNameSpan = document.getElementById('verified-name');
    const responseDiv = document.getElementById('response-message');

    const packages = <?php echo json_encode($packages); ?>;

    function resetFormState() {
        payBtn.style.display = 'none';
        verifyBtn.style.display = 'inline-block';
        customerNameDiv.style.display = 'none';
    }

    providerSelect.addEventListener('change', function() {
        resetFormState();
        const provider = this.value;
        packageSelect.innerHTML = '<option value="">-- Select Package --</option>';
        if (provider && packages[provider]) {
            packageSelect.disabled = false;
            for (const pkg in packages[provider]) {
                const option = document.createElement('option');
                option.value = pkg.toLowerCase();
                option.textContent = `${pkg} - ₦${packages[provider][pkg]}`;
                packageSelect.appendChild(option);
            }
        } else {
            packageSelect.disabled = true;
        }
    });

    iucInput.addEventListener('input', resetFormState);
    packageSelect.addEventListener('change', resetFormState);

    verifyBtn.addEventListener('click', async function() {
        responseDiv.innerHTML = '';
        const provider = providerSelect.value;
        const iuc = iucInput.value;

        if (!provider || !iuc) {
            responseDiv.innerHTML = '<div class="errors"><p>Please select a provider and enter an IUC number.</p></div>';
            return;
        }

        verifyBtn.disabled = true;
        verifyBtn.textContent = 'Verifying...';

        try {
            const formData = new FormData();
            formData.append('action', 'verify_cable');
            formData.append('provider', provider);
            formData.append('iuc_number', iuc);

            const response = await fetch('ajax_handler.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.status === 'success') {
                verifiedNameSpan.textContent = data.customer_name;
                customerNameDiv.style.display = 'block';
                verifyBtn.style.display = 'none';
                payBtn.style.display = 'inline-block';
                responseDiv.innerHTML = '<div class="success"><p>Customer details verified successfully.</p></div>';
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
