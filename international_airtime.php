<?php
$page_title = 'International Airtime';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
$csrf_token = generate_csrf_token();
include 'includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>International Airtime Top-Up</h1>
        <p>Send airtime to mobile numbers worldwide.</p>
    </div>

    <div class="content-box">
        <?php display_flash_message(); ?>
        <form id="topup-form" action="transaction_handler.php" method="POST">
            <input type="hidden" name="action" value="international_airtime">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" id="operator_id" name="operator_id">

            <div class="form-group">
                <label for="country_iso">Recipient Country (2-Letter Code)</label>
                <input type="text" id="country_iso" name="country_iso" maxlength="2" placeholder="e.g., US, GH, KE" required>
            </div>

            <div class="form-group">
                <label for="phone_number">Recipient Phone Number</label>
                <input type="text" id="phone_number" name="phone_number" required>
            </div>

            <button type="button" id="detect-operator-btn" class="btn btn-secondary">Detect Operator</button>
            <div id="operator-info" style="margin-top: 15px;"></div>

            <div id="amount-section" class="form-group" style="display: none;">
                <label for="amount">Select Amount</label>
                <select id="amount" name="amount" required></select>
            </div>

             <div class="form-group" id="pin-section" style="display: none;">
                <label for="pin">Your 4-Digit PIN</label>
                <input type="password" id="pin" name="pin" maxlength="4" required>
            </div>

            <button type="submit" id="submit-btn" class="btn" style="display: none;">Send Top-Up</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const detectBtn = document.getElementById('detect-operator-btn');
    const operatorInfoDiv = document.getElementById('operator-info');
    const amountSection = document.getElementById('amount-section');
    const pinSection = document.getElementById('pin-section');
    const submitBtn = document.getElementById('submit-btn');

    detectBtn.addEventListener('click', function() {
        const phone = document.getElementById('phone_number').value;
        const country = document.getElementById('country_iso').value.toUpperCase();

        if (!phone || !country) {
            operatorInfoDiv.innerHTML = '<p class="text-danger">Please enter a country and phone number.</p>';
            return;
        }

        operatorInfoDiv.innerHTML = '<p>Detecting operator...</p>';
        amountSection.style.display = 'none';
        pinSection.style.display = 'none';
        submitBtn.style.display = 'none';

        const formData = new FormData();
        formData.append('action', 'detect_operator');
        formData.append('phone', phone);
        formData.append('country_iso', country);
        formData.append('csrf_token', '<?php echo $csrf_token; ?>');

        fetch('ajax_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const operator = data.operator;
                document.getElementById('operator_id').value = operator.id;
                operatorInfoDiv.innerHTML = `<p class="text-success"><strong>Operator:</strong> ${operator.name}</p>`;

                const amountSelect = document.getElementById('amount');
                amountSelect.innerHTML = '';
                operator.destinationAmounts.forEach(amount => {
                    const option = new Option(`${amount} ${operator.destinationCurrencyCode}`, amount);
                    amountSelect.add(option);
                });

                amountSection.style.display = 'block';
                pinSection.style.display = 'block';
                submitBtn.style.display = 'block';
            } else {
                operatorInfoDiv.innerHTML = `<p class="text-danger">Error: ${data.message}</p>`;
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
