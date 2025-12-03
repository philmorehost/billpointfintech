<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$pdo = db_connect();
$discos = $pdo->query("SELECT * FROM electricity_discos ORDER BY name ASC")->fetchAll();

include '../includes/header.php';
?>

<div class="app-view">
    <div class="airtime-header">
        <a href="dashboard.php" class="back-btn">&#8592;</a>
        <span class="title">Electricity Bill</span>
    </div>

    <div class="container">
        <div class="form-card">
            <div id="server-message"></div>

            <!-- Step 1: Verification -->
            <form id="verify-form">
                <div class="form-group">
                    <label for="provider">Disco</label>
                    <select name="provider" id="provider" class="form-control" required>
                        <option value="">-- Select Disco --</option>
                        <?php foreach($discos as $disco): ?>
                            <option value="<?php echo htmlspecialchars($disco['code']); ?>"><?php echo htmlspecialchars($disco['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="form-group">
                    <label for="meter_type">Meter Type</label>
                    <select name="meter_type" id="meter_type" class="form-control" required>
                        <option value="prepaid">Prepaid</option>
                        <option value="postpaid">Postpaid</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="meter_number">Meter Number</label>
                    <input type="text" name="meter_number" id="meter_number" class="form-control" required>
                </div>
                <button type="submit" id="verify-btn" class="btn-submit">Verify</button>
            </form>

            <!-- Step 2: Payment (hidden) -->
            <form id="pay-form" style="display:none;">
                <div class="notice mb-3"><strong>Customer Name:</strong> <span id="customer-name"></span></div>
                <input type="hidden" name="provider" id="pay_provider">
                <input type="hidden" name="meter_number" id="pay_meter_number">
                <input type="hidden" name="meter_type" id="pay_meter_type">

                <div class="form-group">
                    <label for="amount">Amount</label>
                    <input type="number" name="amount" id="amount" class="form-control" required min="100">
                </div>
                <button type="submit" id="pay-btn" class="btn-submit">Pay Now</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const verifyForm = document.getElementById('verify-form');
    const payForm = document.getElementById('pay-form');

    verifyForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('verify-btn');
        btn.disabled = true;
        btn.textContent = 'Verifying...';

        const formData = new FormData(verifyForm);
        fetch('ajax_electricity_handler.php?action=verify', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    document.getElementById('customer-name').textContent = data.customer_name;
                    document.getElementById('pay_provider').value = formData.get('provider');
                    document.getElementById('pay_meter_number').value = formData.get('meter_number');
                    document.getElementById('pay_meter_type').value = formData.get('meter_type');

                    verifyForm.style.display = 'none';
                    payForm.style.display = 'block';
                } else {
                    document.getElementById('server-message').innerHTML = `<div class="errors"><p>${data.message}</p></div>`;
                }
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = 'Verify';
            });
    });

    payForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('pay-btn');
        btn.disabled = true;
        btn.textContent = 'Processing...';

        const formData = new FormData(payForm);
        fetch('ajax_electricity_handler.php?action=pay', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                 document.getElementById('server-message').innerHTML = `<div class="${data.status === 'success' ? 'success' : 'errors'}"><p>${data.message}</p></div>`;
                 if(data.status === 'success') {
                     payForm.style.display = 'none';
                     verifyForm.reset();
                     verifyForm.style.display = 'block';
                 }
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = 'Pay Now';
            });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
