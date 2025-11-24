<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
$csrf_token = generate_csrf_token();

$stmt = $pdo->query("SELECT * FROM electricity_discos ORDER BY provider_name");
$discos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electricity Bill Payment - Billpoint</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h2>Electricity Bill Payment</h2>
            <?php display_flash_message(); ?>
            <form id="electricity-form" action="transaction_handler.php" method="POST">
                <input type="hidden" name="action" value="buy_electricity">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="form-group">
                    <label for="disco_provider">Provider (Disco)</label>
                    <select id="disco_provider" name="disco_provider" required>
                        <option value="">-- Select Provider --</option>
                        <?php foreach ($discos as $disco): ?>
                            <option value="<?php echo $disco['provider_code']; ?>"><?php echo $disco['provider_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="meter_type">Meter Type</label>
                    <select id="meter_type" name="meter_type" required>
                        <option value="prepaid">Prepaid</option>
                        <option value="postpaid">Postpaid</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="meter_number">Meter Number</label>
                    <input type="text" id="meter_number" name="meter_number" required>
                    <button type="button" id="verify-btn" class="btn" style="margin-top: 10px;">Verify</button>
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
        const providerInput = document.getElementById('disco_provider');
        const meterNumberInput = document.getElementById('meter_number');
        const meterTypeInput = document.getElementById('meter_type');
        const verificationResult = document.getElementById('verification-result');
        const amountGroup = document.getElementById('amount-group');
        const buyBtn = document.getElementById('buy-btn');

        function resetVerification() {
            verificationResult.innerHTML = '';
            amountGroup.style.display = 'none';
            buyBtn.style.display = 'none';
        }

        providerInput.addEventListener('change', resetVerification);
        meterNumberInput.addEventListener('input', resetVerification);
        meterTypeInput.addEventListener('change', resetVerification);

        document.getElementById('verify-btn').addEventListener('click', function() {
            const provider = providerInput.value;
            const meter_number = document.getElementById('meter_number').value;
            const meter_type = document.getElementById('meter_type').value;
            const verificationResult = document.getElementById('verification-result');

            if (!provider || !meter_number) {
                verificationResult.innerHTML = '<p style="color: red;">Please select a provider and enter your meter number.</p>';
                return;
            }

            // AJAX call to verify meter
            const csrf_token = document.querySelector('input[name="csrf_token"]').value;
            fetch('ajax_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=verify_meter&provider=${provider}&meter_number=${meter_number}&type=${meter_type}&csrf_token=${csrf_token}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    verificationResult.innerHTML = `<p style="color: green;">Customer: ${data.customer_name}</p>`;
                    document.getElementById('amount-group').style.display = 'block';
                    document.getElementById('buy-btn').style.display = 'block';
                } else {
                    verificationResult.innerHTML = `<p style="color: red;">${data.message}</p>`;
                }
            });
        });
    </script>
</body>
</html>
