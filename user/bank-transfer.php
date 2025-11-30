<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$pdo = db_connect();
// Fetch the list of banks from the database
$banks = $pdo->query("SELECT * FROM banks ORDER BY name ASC")->fetchAll();

include 'includes/header.php';
?>
<style>
/* Add styles for the verification step */
#verification-result {
    background-color: #e9f5ff;
    border-left: 5px solid #2994ff;
    padding: 15px;
    margin-top: 20px;
    border-radius: 5px;
}
</style>

<div class="app-view">
    <div class="airtime-header">
        <a href="dashboard.php" class="back-btn">&#8592;</a>
        <span class="title">Bank Transfer</span>
    </div>

    <div class="container">
        <div class="form-card">
            <div id="server-message" class="server-message"></div>

            <!-- Step 1: Account Details Form -->
            <form id="verify-form">
                <div class="form-group">
                    <label for="bank_code">Select Bank</label>
                    <select id="bank_code" name="bank_code" class="form-control" required>
                        <option value="">-- Select Bank --</option>
                        <?php foreach ($banks as $bank): ?>
                            <option value="<?php echo htmlspecialchars($bank['code']); ?>"><?php echo htmlspecialchars($bank['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="account_number">Account Number</label>
                    <input type="text" id="account_number" name="account_number" class="form-control" required pattern="\d{10}">
                </div>
                <button type="submit" id="verify-btn" class="btn-submit">Verify Account</button>
            </form>

            <!-- Step 2: Transfer Confirmation Form (hidden initially) -->
            <form id="transfer-form" style="display: none;">
                <input type="hidden" name="enquiry_id" id="enquiry_id">
                 <input type="hidden" name="verified_bank_code" id="verified_bank_code">
                <input type="hidden" name="verified_account_number" id="verified_account_number">

                <div id="verification-result"></div>

                <div class="form-group">
                    <label for="amount">Amount (₦)</label>
                    <input type="number" id="amount" name="amount" class="form-control" required min="100">
                </div>
                <div class="form-group">
                    <label for="narration">Narration (Optional)</label>
                    <input type="text" id="narration" name="narration" class="form-control">
                </div>
                <button type="submit" id="transfer-btn" class="btn-submit">Complete Transfer</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const verifyForm = document.getElementById('verify-form');
    const transferForm = document.getElementById('transfer-form');
    const verifyBtn = document.getElementById('verify-btn');
    const transferBtn = document.getElementById('transfer-btn');
    const serverMessage = document.getElementById('server-message');

    // Handle Account Verification
    verifyForm.addEventListener('submit', function(e) {
        e.preventDefault();
        verifyBtn.disabled = true;
        verifyBtn.textContent = 'Verifying...';
        serverMessage.style.display = 'none';

        const formData = new FormData(verifyForm);
        fetch('ajax_transfer_handler.php?action=verify', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('verification-result').innerHTML = `
                        <p><strong>Account Name:</strong> ${data.data.acccount_name}</p>
                        <p><strong>Bank:</strong> ${data.data.bank_name}</p>`;
                    document.getElementById('enquiry_id').value = data.data.enquiry_id;
                    document.getElementById('verified_bank_code').value = document.getElementById('bank_code').value;
                    document.getElementById('verified_account_number').value = document.getElementById('account_number').value;

                    verifyForm.style.display = 'none';
                    transferForm.style.display = 'block';
                } else {
                    serverMessage.textContent = data.message || 'Verification failed.';
                    serverMessage.className = 'server-message error';
                    serverMessage.style.display = 'block';
                }
            })
            .finally(() => {
                verifyBtn.disabled = false;
                verifyBtn.textContent = 'Verify Account';
            });
    });

    // Handle Fund Transfer
    transferForm.addEventListener('submit', function(e) {
        e.preventDefault();
        transferBtn.disabled = true;
        transferBtn.textContent = 'Processing...';
        serverMessage.style.display = 'none';

        const formData = new FormData(transferForm);
        fetch('ajax_transfer_handler.php?action=transfer', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                serverMessage.textContent = data.message;
                serverMessage.className = `server-message ${data.status}`;
                serverMessage.style.display = 'block';
                if (data.status === 'success') {
                    transferForm.reset();
                    verifyForm.reset();
                    transferForm.style.display = 'none';
                    verifyForm.style.display = 'block';
                }
            })
            .finally(() => {
                transferBtn.disabled = false;
                transferBtn.textContent = 'Complete Transfer';
            });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
