<?php
$page_title = 'P2P Transfer';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
$csrf_token = generate_csrf_token();
include 'includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>Peer-to-Peer (P2P) Transfer</h1>
        <p>Send money instantly to another Billpoint user.</p>
    </div>

    <div class="content-box">
        <?php display_flash_message(); ?>
        <form id="p2p-form" action="transaction_handler.php" method="POST">
            <input type="hidden" name="action" value="p2p_transfer">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-group">
                <label for="recipient_email">Recipient's Email Address</label>
                <input type="email" id="recipient_email" name="recipient_email" required>
                <button type="button" id="verify-recipient-btn" class="btn btn-secondary" style="margin-top: 10px;">Verify Recipient</button>
                <div id="recipient-info" style="margin-top: 10px;"></div>
            </div>

            <div class="form-group">
                <label for="amount">Amount (NGN)</label>
                <input type="number" id="amount" name="amount" min="10" required>
            </div>

            <div class="form-group">
                <label for="pin">Your 4-Digit PIN</label>
                <input type="password" id="pin" name="pin" maxlength="4" required>
                <small class="form-text text-muted">Enter your PIN to authorize this transaction.</small>
            </div>

            <button type="submit" id="submit-btn" class="btn" style="display: none;">Request Transfer</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const recipientEmailInput = document.getElementById('recipient_email');
    const verifyBtn = document.getElementById('verify-recipient-btn');
    const recipientInfoDiv = document.getElementById('recipient-info');
    const submitBtn = document.getElementById('submit-btn');

    verifyBtn.addEventListener('click', function() {
        const email = recipientEmailInput.value.trim();
        if (email === '') {
            recipientInfoDiv.innerHTML = '<p class="text-danger">Please enter an email address.</p>';
            return;
        }

        recipientInfoDiv.innerHTML = '<p>Verifying...</p>';
        submitBtn.style.display = 'none';

        const formData = new FormData();
        formData.append('action', 'verify_recipient');
        formData.append('email', email);
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

        fetch('ajax_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                recipientInfoDiv.innerHTML = `<p class="text-success">Recipient Found: <strong>${data.recipient_name}</strong></p>`;
                submitBtn.style.display = 'block';
            } else {
                recipientInfoDiv.innerHTML = `<p class="text-danger">Error: ${data.message}</p>`;
                submitBtn.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            recipientInfoDiv.innerHTML = '<p class="text-danger">An unexpected error occurred.</p>';
        });
    });

    // Reset verification if the email changes
    recipientEmailInput.addEventListener('input', function() {
        recipientInfoDiv.innerHTML = '';
        submitBtn.style.display = 'none';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
