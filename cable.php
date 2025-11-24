<?php
session_start();
require_once 'includes/auth_check.php';
require_once 'includes/database.php';
require_once 'includes/flash_messages.php';
require_once 'includes/csrf.php';
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cable TV Subscription - Billpoint</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h2>Cable TV Subscription</h2>
            <?php display_flash_message(); ?>
            <form id="cable-form" action="transaction_handler.php" method="POST">
                <input type="hidden" name="action" value="buy_cable_plan">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="form-group">
                    <label for="cable_provider">Provider</label>
                    <select id="cable_provider" name="cable_provider" required>
                        <option value="">-- Select Provider --</option>
                        <option value="dstv">DSTV</option>
                        <option value="gotv">GOTV</option>
                        <option value="startimes">Startimes</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="iuc_number">IUC/Smartcard Number</label>
                    <input type="text" id="iuc_number" name="iuc_number" required>
                    <button type="button" id="verify-btn" class="btn" style="margin-top: 10px;">Verify</button>
                    <div id="verification-result" style="margin-top: 10px;"></div>
                </div>

                <div class="form-group" id="package-group" style="display: none;">
                    <label for="cable_plan">Select Package</label>
                    <select id="cable_plan" name="cable_plan" required></select>
                </div>

                <button type="submit" id="buy-btn" class="btn" style="display: none;">Buy Now</button>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('verify-btn').addEventListener('click', function() {
            const provider = document.getElementById('cable_provider').value;
            const iuc = document.getElementById('iuc_number').value;
            const verificationResult = document.getElementById('verification-result');

            if (!provider || !iuc) {
                verificationResult.innerHTML = '<p style="color: red;">Please select a provider and enter your IUC number.</p>';
                return;
            }

            // AJAX call to verify IUC
            fetch('transaction_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=verify_iuc&cable_provider=${provider}&iuc_number=${iuc}&csrf_token=<?php echo $csrf_token; ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    verificationResult.innerHTML = `<p style="color: green;">Customer: ${data.customer_name}</p>`;
                    populatePackages(provider);
                    document.getElementById('package-group').style.display = 'block';
                    document.getElementById('buy-btn').style.display = 'block';
                } else {
                    verificationResult.innerHTML = `<p style="color: red;">${data.message}</p>`;
                }
            });
        });

        function populatePackages(provider) {
            const packageSelect = document.getElementById('cable_plan');
            packageSelect.innerHTML = '<option value="">-- Select Package --</option>';

            // AJAX call to get packages
            fetch(`transaction_handler.php?action=get_cable_plans&provider=${provider}`)
            .then(response => response.json())
            .then(plans => {
                plans.forEach(plan => {
                    const option = new Option(`${plan.package_name} - ₦${plan.price}`, plan.id);
                    packageSelect.add(option);
                });
            });
        }
    </script>
</body>
</html>
