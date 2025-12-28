<?php
$page_title = 'Currency Exchange';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
$csrf_token = generate_csrf_token();
include 'includes/header.php';

// Fetch user's wallets
$stmt = $pdo->prepare("SELECT currency, balance FROM wallets WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$wallets = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<div class="container">
    <div class="page-header">
        <h1>Currency Exchange</h1>
        <p>Convert funds between your wallets.</p>
    </div>

    <div class="content-box">
        <?php display_flash_message(); ?>
        <form id="exchange-form" action="transaction_handler.php" method="POST">
            <input type="hidden" name="action" value="currency_exchange">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-group">
                <label for="from_currency">From</label>
                <select id="from_currency" name="from_currency" required>
                    <?php foreach ($wallets as $currency => $balance): ?>
                        <option value="<?php echo $currency; ?>">
                            <?php echo $currency; ?> (Balance: <?php echo number_format($balance, 2); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="to_currency">To</label>
                <select id="to_currency" name="to_currency" required>
                     <?php foreach ($wallets as $currency => $balance): ?>
                        <option value="<?php echo $currency; ?>"><?php echo $currency; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="amount">Amount to Convert</label>
                <input type="number" id="amount" name="amount" step="0.01" min="1" required>
            </div>

            <div id="rate-info" style="margin: 15px 0;"></div>

            <div class="form-group">
                <label for="pin">Your 4-Digit PIN</label>
                <input type="password" id="pin" name="pin" maxlength="4" required>
            </div>

            <button type="submit" id="submit-btn" class="btn">Convert</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fromSelect = document.getElementById('from_currency');
    const toSelect = document.getElementById('to_currency');
    const amountInput = document.getElementById('amount');
    const rateInfoDiv = document.getElementById('rate-info');
    let debounceTimer;

    function fetchRate() {
        const from = fromSelect.value;
        const to = toSelect.value;
        const amount = parseFloat(amountInput.value);

        if (from === to || !amount || amount <= 0) {
            rateInfoDiv.innerHTML = '';
            return;
        }

        rateInfoDiv.innerHTML = '<p>Fetching rate...</p>';

        const formData = new FormData();
        formData.append('action', 'get_exchange_rate');
        formData.append('from', from);
        formData.append('to', to);
        formData.append('csrf_token', '<?php echo $csrf_token; ?>'); // Assuming CSRF is needed for GET-like AJAX

        fetch('ajax_handler.php', {
            method: 'POST', // Using POST to send CSRF token
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const rate = data.rate;
                const received_amount = amount * rate;
                rateInfoDiv.innerHTML = `<p class="text-success"><strong>Exchange Rate:</strong> 1 ${from} ≈ ${rate.toFixed(4)} ${to}</p><p>You will receive approximately: <strong>${received_amount.toFixed(2)} ${to}</strong></p>`;
            } else {
                rateInfoDiv.innerHTML = `<p class="text-danger">Error: ${data.message}</p>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            rateInfoDiv.innerHTML = '<p class="text-danger">Could not fetch exchange rate.</p>';
        });
    }

    fromSelect.addEventListener('change', fetchRate);
    toSelect.addEventListener('change', fetchRate);
    amountInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchRate, 500); // Debounce to avoid too many requests
    });
});
</script>

<?php include 'includes/footer.php'; ?>
