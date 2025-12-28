<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$csrf_token = generate_csrf_token();

// Fetch user's current KYC status
$stmt = $pdo->prepare("SELECT kyc_level, kyc_verified_at, full_name, phone FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$is_verified = $user['kyc_verified_at'] !== null;
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>KYC Verification</h1>
        <p>Verify your identity to increase your transaction limits.</p>
    </div>

    <div class="content-box">
        <h2>Your Verification Status</h2>
        <?php if ($is_verified): ?>
            <div class="alert alert-success">
                <p><strong>Status: Verified</strong></p>
                <p>Your identity was successfully verified on <?php echo date('M d, Y', strtotime($user['kyc_verified_at'])); ?>.</p>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <p><strong>Status: Not Verified</strong></p>
                <p>Please complete the form below to verify your identity.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!$is_verified): ?>
    <div class="content-box" style="margin-top: 20px;">
        <h2>BVN Verification Form</h2>
        <p>Please provide your details exactly as they appear on your bank records.</p>

        <div id="kyc-result" style="margin-top: 15px;"></div>
        <?php display_flash_message(); ?>

        <form id="kyc-form">
            <input type="hidden" name="action" value="verify_kyc">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="form-group">
                <label for="bvn">Bank Verification Number (BVN)</label>
                <input type="text" id="bvn" name="bvn" maxlength="11" required>
            </div>

            <div class="form-group">
                <label for="name">Full Name (as on BVN)</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="dob">Date of Birth (YYYY-MM-DD)</label>
                <input type="date" id="dob" name="dob" required>
            </div>

            <div class="form-group">
                <label for="mobileNo">Phone Number (as on BVN)</label>
                <input type="text" id="mobileNo" name="mobileNo" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
            </div>

            <button type="submit" class="btn">Verify My Identity</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const kycForm = document.getElementById('kyc-form');
    if (kycForm) {
        kycForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const resultDiv = document.getElementById('kyc-result');
            resultDiv.innerHTML = '<p>Verifying... Please wait.</p>';

            const formData = new FormData(kycForm);

            fetch('ajax_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    resultDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    // Reload the page after a short delay to show the updated status
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-danger">Error: ${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                resultDiv.innerHTML = '<div class="alert alert-danger">An unexpected error occurred.</div>';
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
