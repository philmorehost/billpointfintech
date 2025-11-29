<?php
require_once '../core/functions.php';
require_once '../core/vtu_api.php';
require_once '../core/security_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = db_connect();

$stmt = $pdo->query("SELECT * FROM cable_tv_packages ORDER BY provider, package_name");
$all_packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cable_providers = [];
$packages_by_provider = [];
foreach ($all_packages as $pkg) {
    $provider = $pkg['provider'];
    if (!isset($cable_providers[$provider])) {
        $cable_providers[$provider] = ucfirst($provider);
    }
    $packages_by_provider[$provider][] = $pkg;
}

$errors = [];
$success_message = '';
$limit_error = null;
$user_id = $_SESSION['user_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay') {
    $provider = $_POST['provider'] ?? '';
    $iuc_number = trim($_POST['iuc_number'] ?? '');
    $package = $_POST['package'] ?? '';

    $amount = $packages[$provider][ucfirst($package)] ?? 0;

    // --- Security & Limit Checks ---
    $limit_check = check_transaction_limit($pdo, $user_id, 'cable', $amount);
    if (!$limit_check['allowed']) {
        $limit_error = $limit_check['message'];
    }

    $blacklist_check = is_blacklisted($pdo, 'smartcard', $iuc_number);
    if ($blacklist_check['blacklisted']) {
        $errors[] = $blacklist_check['message'];
    }
    // --- End of Checks ---

    if (empty($provider) || empty($iuc_number) || empty($package) || $amount <= 0) {
        $errors[] = "Invalid selection. Please verify the details again.";
    }

    if (empty($errors) && !$limit_error) {
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
    <h2>Pay Cable TV Subscription</h2>
    <div id="response-message">
        <?php if (!empty($errors)): ?> <div class="errors"><?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?></div> <?php endif; ?>
        <?php if ($success_message): ?> <div class="success"><p><?php echo htmlspecialchars($success_message); ?></p></div> <?php endif; ?>
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
<?php include '../includes/footer.php'; ?>
<?php if ($limit_error): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        showModal('Transaction Limit Exceeded', <?php echo json_encode($limit_error); ?>);
    });
</script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const providerSelect = document.getElementById('provider');
    const packageSelect = document.getElementById('package');
    const packagesByProvider = <?php echo json_encode($packages_by_provider); ?>;

    providerSelect.addEventListener('change', function() {
        const provider = this.value;
        packageSelect.innerHTML = '<option value="">-- Select Package --</option>';
        packageSelect.disabled = true;

        if (provider && packagesByProvider[provider]) {
            packagesByProvider[provider].forEach(function(pkg) {
                const option = document.createElement('option');
                option.value = pkg.api_code;
                option.textContent = `${pkg.package_name} - ₦${pkg.price}`;
                packageSelect.appendChild(option);
            });
            packageSelect.disabled = false;
        }
    });

    // ... (rest of the verification and form submission JS remains the same)
});
</script>
