<?php
require_once '../core/functions.php';
require_once '../core/vtu_api.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// In a real app, this would be a comprehensive list from a database
$banks = [
    '044' => 'Access Bank', '023' => 'Citibank Nigeria', '063' => 'Diamond Bank', '050' => 'Ecobank Nigeria', '011' => 'First Bank of Nigeria', '214' => 'First City Monument Bank', '058' => 'Guaranty Trust Bank', '030' => 'Heritage Bank', '301' => 'Jaiz Bank', '082' => 'Keystone Bank', '101' => 'Providus Bank', '076' => 'Skye Bank', '221' => 'Stanbic IBTC Bank', '068' => 'Standard Chartered Bank', '232' => 'Sterling Bank', '100' => 'Suntrust Bank', '032' => 'Union Bank of Nigeria', '033' => 'United Bank for Africa', '215' => 'Unity Bank', '035' => 'Wema Bank', '057' => 'Zenith Bank'
];

$errors = [];
$success_message = '';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bank_code = $_POST['bank_code'] ?? '';
    $account_number = $_POST['account_number'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $narration = $_POST['narration'] ?? '';
    $enquiry_id = $_POST['enquiry_id'] ?? '';

    // The platform fee for transfers
    $fee = 35;
    $total_amount = $amount + $fee;

    if (empty($bank_code) || empty($account_number) || $amount < 100 || empty($enquiry_id)) {
        $errors[] = "Invalid transfer details. Please re-verify the account.";
    } else {
        $description = "Bank Transfer: N$amount to $account_number";
        $transaction_id = create_transaction($user_id, 'Bank Transfer', $description, $total_amount);

        if (!$transaction_id) {
            $errors[] = 'Failed to create transaction record.';
        } else {
            if (debit_wallet($user_id, $total_amount)) {
                $response = transfer_funds($enquiry_id, $bank_code, $account_number, $amount, $narration);
                if (isset($response['status']) && $response['status'] === 'success') {
                    update_transaction_status($transaction_id, 'success', $response['ref'] ?? 'N/A', json_encode($response));
                    $success_message = $response['desc'] ?? 'Transfer successful.';
                } else {
                    credit_wallet($user_id, $total_amount); // Refund
                    update_transaction_status($transaction_id, 'failed', null, json_encode($response));
                    $errors[] = $response['desc'] ?? 'An unknown error occurred during the transfer.';
                }
            } else {
                update_transaction_status($transaction_id, 'failed', null, 'Insufficient funds');
                $errors[] = 'Insufficient wallet balance for the amount plus ₦' . $fee . ' fee.';
            }
        }
    }
}


include '../includes/header.php';
?>

<div class="container">
    <h2>Bank Transfer</h2>
    <div id="response-message">
        <?php if (!empty($errors)): ?> <div class="errors"><?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?></div> <?php endif; ?>
        <?php if ($success_message): ?> <div class="success"><p><?php echo htmlspecialchars($success_message); ?></p></div> <?php endif; ?>
    </div>
    <form id="transfer-form" action="bank-transfer.php" method="post">
        <input type="hidden" name="enquiry_id" id="enquiry_id">
        <div class="form-group">
            <label for="bank_code">Select Bank</label>
            <select name="bank_code" id="bank_code" required>
                <option value="">-- Select Bank --</option>
                <?php foreach($banks as $code => $name): ?> <option value="<?php echo htmlspecialchars($code); ?>"><?php echo htmlspecialchars($name); ?></option> <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="account_number">Account Number</label>
            <input type="text" name="account_number" id="account_number" required pattern="\d{10}" title="Please enter a 10-digit account number">
        </div>
        <div class="form-group">
            <label for="amount">Amount (Fee: ₦35)</label>
            <input type="number" name="amount" id="amount" required min="100">
        </div>
        <div class="form-group">
            <label for="narration">Narration (Optional)</label>
            <input type="text" name="narration" id="narration">
        </div>
        <div id="customer-name" style="display:none; margin-bottom: 15px;" class="notice">
            <strong>Account Name:</strong> <span id="verified-name"></span>
        </div>
        <button type="button" id="verify-btn">Verify Account</button>
        <button type="submit" id="transfer-btn" style="display:none;">Send Money</button>
    </form>
</div>

<script>
// Script is safe, uses textContent and secured server responses.
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('transfer-form');
    const verifyBtn = document.getElementById('verify-btn');
    const transferBtn = document.getElementById('transfer-btn');
    const responseDiv = document.getElementById('response-message');
    const customerNameDiv = document.getElementById('customer-name');
    const verifiedNameSpan = document.getElementById('verified-name');
    const enquiryIdInput = document.getElementById('enquiry_id');

    function resetFormState() {
        transferBtn.style.display = 'none';
        verifyBtn.style.display = 'inline-block';
        customerNameDiv.style.display = 'none';
        enquiryIdInput.value = '';
    }

    form.addEventListener('change', resetFormState);

    verifyBtn.addEventListener('click', async function() {
        responseDiv.innerHTML = '';
        const formData = new FormData();
        formData.append('action', 'verify_bank');
        formData.append('bank_code', document.getElementById('bank_code').value);
        formData.append('account_number', document.getElementById('account_number').value);

        if (!formData.get('bank_code') || !formData.get('account_number')) {
            responseDiv.innerHTML = '<div class="errors"><p>Please select a bank and enter an account number.</p></div>';
            return;
        }

        verifyBtn.disabled = true;
        verifyBtn.textContent = 'Verifying...';

        try {
            const response = await fetch('ajax_handler.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.status === 'success') {
                verifiedNameSpan.textContent = data.account_name;
                enquiryIdInput.value = data.enquiry_id;
                customerNameDiv.style.display = 'block';
                verifyBtn.style.display = 'none';
                transferBtn.style.display = 'inline-block';
                responseDiv.innerHTML = '<div class="success"><p>Account verified successfully.</p></div>';
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
            verifyBtn.textContent = 'Verify Account';
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
