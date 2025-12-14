<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$pdo = db_connect();
$stmt = $pdo->query("SELECT * FROM cable_tv_packages ORDER BY provider, price");
$all_packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$packages_by_provider = [];
foreach ($all_packages as $pkg) {
    $packages_by_provider[$pkg['provider']][] = $pkg;
}

include '../includes/header.php';
?>

<div class="app-view">
    <div class="airtime-header">
        <a href="dashboard.php" class="back-btn">&#8592;</a>
        <span class="title">Cable TV</span>
    </div>

    <div class="container">
        <div class="form-card">
            <div id="server-message"></div>

            <!-- Step 1: Verification -->
            <form id="verify-form">
                <div class="form-group">
                    <label for="provider">Provider</label>
                    <select name="provider" id="provider" class="form-control" required>
                        <option value="">-- Select Provider --</option>
                        <option value="DSTV">DSTV</option>
                        <option value="GOTV">GOtv</option>
                        <option value="STARTIMES">StarTimes</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="iuc_number">IUC / Smartcard Number</label>
                    <input type="text" name="iuc_number" id="iuc_number" class="form-control" required>
                </div>
                <button type="submit" id="verify-btn" class="btn-submit">Verify</button>
            </form>

            <!-- Step 2: Payment (hidden) -->
            <form id="pay-form" style="display:none;">
                <div class="notice mb-3"><strong>Customer Name:</strong> <span id="customer-name"></span></div>
                <input type="hidden" name="provider" id="pay_provider">
                <input type="hidden" name="iuc_number" id="pay_iuc_number">

                <div class="form-group">
                    <label for="package_code">Select Package</label>
                    <select name="package_code" id="package_code" class="form-control" required></select>
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
    const packagesByProvider = <?php echo json_encode($packages_by_provider); ?>;

    verifyForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('verify-btn');
        btn.disabled = true;
        btn.textContent = 'Verifying...';

        const formData = new FormData(verifyForm);
        fetch('ajax_cable_handler.php?action=verify', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    document.getElementById('customer-name').textContent = data.customer_name;
                    document.getElementById('pay_provider').value = formData.get('provider');
                    document.getElementById('pay_iuc_number').value = formData.get('iuc_number');

                    const provider = formData.get('provider');
                    const packageSelect = document.getElementById('package_code');
                    packageSelect.innerHTML = '<option value="">-- Select Package --</option>';
                    if (packagesByProvider[provider]) {
                        packagesByProvider[provider].forEach(pkg => {
                            packageSelect.innerHTML += `<option value="${pkg.package_code}">${pkg.package_name} - ₦${pkg.price}</option>`;
                        });
                    }

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
        fetch('ajax_cable_handler.php?action=pay', { method: 'POST', body: formData })
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
