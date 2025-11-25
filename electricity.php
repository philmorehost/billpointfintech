<?php
$page_title = 'Electricity Bill Payment';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
$csrf_token = generate_csrf_token();

$stmt = $pdo->query("SELECT * FROM electricity_discos ORDER BY provider_name");
$discos = $stmt->fetchAll();
include 'includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>Electricity Bill Payment</h1>
    </div>

    <div class="content-box">
        <?php display_flash_message(); ?>
        <form id="electricity-form" action="transaction_handler.php" method="POST">
            <input type="hidden" name="action" value="buy_electricity">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-group">
                <label for="provider">Provider (Disco)</label>
                <select id="provider" name="provider" required>
                    <option value="">-- Select Provider --</option>
                    <?php foreach ($discos as $disco): ?>
                        <option value="<?php echo $disco['provider_code']; ?>"><?php echo $disco['provider_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="meter_type">Meter Type</label>
                <select id="meter_type" name="type" required>
                    <option value="prepaid">Prepaid</option>
                    <option value="postpaid">Postpaid</option>
                </select>
            </div>

            <div class="form-group">
                <label for="meter_number">Meter Number</label>
                <input type="text" id="meter_number" name="meter_number" required>
                <button type="button" id="verify-btn" class="btn btn-secondary" style="margin-top: 10px;">Verify</button>
                <div id="verification-result" style="margin-top: 10px;"></div>
            </div>

            <div class="form-group" id="amount-group" style="display: none;">
                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" min="100" required>
            </div>

            <button type="submit" id="buy-btn" class="btn" style="display: none;">Pay Now</button>
        </form>
    </div>
</div>
<script>
    const providerInput_el = document.getElementById('provider');
    const meterNumberInput_el = document.getElementById('meter_number');
    const meterTypeInput_el = document.getElementById('meter_type');
    const verificationResult_el = document.getElementById('verification-result');
    const amountGroup_el = document.getElementById('amount-group');
    const buyBtn_el = document.getElementById('buy-btn');

    function resetVerificationState_el() {
        verificationResult_el.innerHTML = '';
        amountGroup_el.style.display = 'none';
        buyBtn_el.style.display = 'none';
    }

    providerInput_el.addEventListener('change', resetVerificationState_el);
    meterNumberInput_el.addEventListener('input', resetVerificationState_el);
    meterTypeInput_el.addEventListener('change', resetVerificationState_el);

    document.getElementById('verify-btn').addEventListener('click', function() {
        const provider = providerInput_el.value;
        const meter_number = meterNumberInput_el.value;
        const meter_type = meterTypeInput_el.value;

        if (!provider || !meter_number) {
            verificationResult_el.innerHTML = '<p class="text-danger">Please select a provider and enter your meter number.</p>';
            return;
        }

        verificationResult_el.innerHTML = '<p>Verifying...</p>';
        const csrf_token = document.querySelector('input[name="csrf_token"]').value;
        const formData = new FormData();
        formData.append('action', 'verify_meter');
        formData.append('provider', provider);
        formData.append('meter_number', meter_number);
        formData.append('type', meter_type);
        formData.append('csrf_token', csrf_token);

        fetch('ajax_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                verificationResult_el.innerHTML = `<p class="text-success">Customer: <strong>${data.customer_name}</strong></p>`;
                amountGroup_el.style.display = 'block';
                buyBtn_el.style.display = 'block';
            } else {
                verificationResult_el.innerHTML = `<p class="text-danger">${data.message}</p>`;
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
