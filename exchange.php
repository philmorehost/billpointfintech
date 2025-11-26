<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
require_once 'core/juicyway_api.php';

$juicyway = new JuicyWayAPI($config['settings']['juicyway_api_key'] ?? null, $config['settings']['juicyway_secret_key'] ?? null);
$supported_pairs = $juicyway->get_supported_pairs();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Currency Exchange - Billpoint</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>Currency Exchange</h2>
        <form action="transaction_handler.php" method="post">
            <input type="hidden" name="action" value="currency_exchange">
            <?php generate_csrf_token(); ?>
            <div class="form-group">
                <label for="from_currency">From</label>
                <select id="from_currency" name="from_currency" required>
                    <?php foreach ($supported_pairs['data'] as $pair): ?>
                        <option value="<?php echo htmlspecialchars($pair['from']); ?>"><?php echo htmlspecialchars($pair['from']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="to_currency">To</label>
                <select id="to_currency" name="to_currency" required>
                    <?php foreach ($supported_pairs['data'] as $pair): ?>
                        <option value="<?php echo htmlspecialchars($pair['to']); ?>"><?php echo htmlspecialchars($pair['to']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" min="0.01" step="0.01" required>
            </div>
            <div id="rate-display"></div>
            <div class="form-group">
                <label for="pin">4-Digit PIN</label>
                <input type="password" id="pin" name="pin" maxlength="4" required>
            </div>
            <button type="submit" class="btn">Exchange</button>
        </form>
    </div>
    <?php include 'includes/footer_nav.php'; ?>
    <script>
        document.getElementById('from_currency').addEventListener('change', getRate);
        document.getElementById('to_currency').addEventListener('change', getRate);
        document.getElementById('amount').addEventListener('input', getRate);

        function getRate() {
            const from = document.getElementById('from_currency').value;
            const to = document.getElementById('to_currency').value;
            const amount = document.getElementById('amount').value;

            if (from && to && amount > 0) {
                const formData = new FormData();
                formData.append('action', 'get_exchange_rate');
                formData.append('from', from);
                formData.append('to', to);
                formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);


                fetch('ajax_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const rate = data.rate;
                        const received_amount = amount * rate;
                        document.getElementById('rate-display').innerHTML = `Rate: 1 ${from} = ${rate} ${to}. You will receive ${received_amount.toFixed(2)} ${to}.`;
                    } else {
                        document.getElementById('rate-display').innerHTML = `<span class="error">${data.message}</span>`;
                    }
                });
            }
        }
    </script>
</body>
</html>
