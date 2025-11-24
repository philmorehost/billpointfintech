<?php
require_once 'includes/bootstrap.php';
require_once 'core/paystack_api.php';

$paystack = new PaystackAPI();
$banks = [];
$bank_list_result = $paystack->getBankList();
if ($bank_list_result && $bank_list_result['status'] === true) {
    $banks = $bank_list_result['data'];
    // Sort banks alphabetically by name
    usort($banks, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Billpoint</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h2>Sign Up</h2>
            <?php display_flash_message(); ?>
            <form action="auth_handler.php" method="POST">
                <input type="hidden" name="action" value="signup">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="pin">4-Digit PIN</label>
                    <input type="password" id="pin" name="pin" maxlength="4" required>
                </div>

                <hr>
                <p><strong>Bank Account Details (for automatic funding)</strong></p>
                 <div class="form-group">
                    <label for="bank_code">Bank Name</label>
                    <select id="bank_code" name="bank_code" required>
                        <option value="">-- Select Bank --</option>
                        <?php foreach ($banks as $bank): ?>
                            <option value="<?php echo htmlspecialchars($bank['code']); ?>">
                                <?php echo htmlspecialchars($bank['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="account_number">Account Number</label>
                    <input type="text" id="account_number" name="account_number" required>
                </div>
                 <div class="form-group">
                    <button type="button" id="verify-account-btn" class="btn btn-secondary">Verify Account</button>
                    <div id="account-name-display" style="margin-top: 10px;"></div>
                </div>


                <button type="submit" class="btn">Sign Up</button>
            </form>
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>
    <script>
    document.getElementById('verify-account-btn').addEventListener('click', function() {
        const bankCode = document.getElementById('bank_code').value;
        const accountNumber = document.getElementById('account_number').value;
        const displayDiv = document.getElementById('account-name-display');
        const fullNameInput = document.getElementById('full_name');

        if (!bankCode || !accountNumber) {
            displayDiv.innerHTML = '<span style="color: red;">Please select a bank and enter an account number.</span>';
            return;
        }

        displayDiv.innerHTML = 'Verifying...';

        const formData = new FormData();
        formData.append('action', 'verify_account');
        formData.append('bank_code', bankCode);
        formData.append('account_number', accountNumber);
        // Add CSRF token for security
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);


        fetch('ajax_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                displayDiv.innerHTML = `<span style="color: green;">Account Name: <strong>${data.account_name}</strong></span>`;
                // Optionally auto-fill the full name field
                if(fullNameInput.value === '') {
                    fullNameInput.value = data.account_name;
                }
            } else {
                displayDiv.innerHTML = `<span style="color: red;">Error: ${data.message}</span>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            displayDiv.innerHTML = '<span style="color: red;">An unexpected error occurred. Please try again.</span>';
        });
    });
    </script>
</body>
</html>
