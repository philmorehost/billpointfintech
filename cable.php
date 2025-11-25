<?php
$page_title = 'Cable TV Subscription';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
$csrf_token = generate_csrf_token();
include 'includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>Cable TV Subscription</h1>
    </div>

    <div class="content-box">
        <?php display_flash_message(); ?>
        <form id="cable-form" action="transaction_handler.php" method="POST">
            <input type="hidden" name="action" value="buy_cable_plan">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-group">
                <label for="cable_provider">Provider</label>
                <select id="cable_provider" name="cable_provider" required>
                    <option value="">-- Select Provider --</option>
                    <?php foreach ($config['services']['cable_providers'] as $code => $name): ?>
                        <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="iuc_number">IUC/Smartcard Number</label>
                <input type="text" id="iuc_number" name="iuc_number" required>
                <button type="button" id="verify-btn" class="btn btn-secondary" style="margin-top: 10px;">Verify</button>
                <div id="verification-result" style="margin-top: 10px;"></div>
            </div>

            <div class="form-group" id="package-group" style="display: none;">
                <label for="cable_plan_id">Select Package</label>
                <select id="cable_plan_id" name="cable_plan_id" required></select>
            </div>

            <button type="submit" id="buy-btn" class="btn" style="display: none;">Buy Now</button>
        </form>
    </div>
</div>

<script>
    const providerInput = document.getElementById('cable_provider');
    const iucInput = document.getElementById('iuc_number');
    const verificationResult = document.getElementById('verification-result');
    const packageGroup = document.getElementById('package-group');
    const packageSelect = document.getElementById('cable_plan_id');
    const buyBtn = document.getElementById('buy-btn');

    function resetVerificationState() {
        verificationResult.innerHTML = '';
        packageGroup.style.display = 'none';
        buyBtn.style.display = 'none';
        packageSelect.innerHTML = '';
    }

    providerInput.addEventListener('change', resetVerificationState);
    iucInput.addEventListener('input', resetVerificationState);

    document.getElementById('verify-btn').addEventListener('click', function() {
        const provider = providerInput.value;
        const iuc = iucInput.value;

        if (!provider || !iuc) {
            verificationResult.innerHTML = '<p class="text-danger">Please select a provider and enter your IUC number.</p>';
            return;
        }

        verificationResult.innerHTML = '<p>Verifying...</p>';

        const csrf_token = document.querySelector('input[name="csrf_token"]').value;
        const formData = new FormData();
        formData.append('action', 'verify_iuc');
        formData.append('cable_provider', provider);
        formData.append('iuc_number', iuc);
        formData.append('csrf_token', csrf_token);

        fetch('ajax_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                verificationResult.innerHTML = `<p class="text-success">Customer: <strong>${data.customer_name}</strong></p>`;
                populatePackages(provider);
                packageGroup.style.display = 'block';
                buyBtn.style.display = 'block';
            } else {
                verificationResult.innerHTML = `<p class="text-danger">${data.message}</p>`;
            }
        });
    });

    function populatePackages(provider) {
        packageSelect.innerHTML = '<option value="">-- Loading Packages --</option>';

        fetch(`ajax_handler.php?action=get_cable_plans&provider=${provider}`)
        .then(response => response.json())
        .then(plans => {
            packageSelect.innerHTML = '<option value="">-- Select Package --</option>';
            plans.forEach(plan => {
                const option = new Option(`${plan.package_name} - ₦${plan.price}`, plan.id);
                packageSelect.add(option);
            });
        });
    }
</script>

<?php include 'includes/footer.php'; ?>
