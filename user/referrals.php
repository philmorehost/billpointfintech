<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$pdo = db_connect();
$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT full_name, email, referral_code FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Generate a referral code if one doesn't exist
if (empty($user['referral_code'])) {
    $referral_code = strtoupper(substr(str_replace(' ', '', $user['full_name']), 0, 4) . substr(md5($user_id), 0, 5));
    $stmt = $pdo->prepare("UPDATE users SET referral_code = ? WHERE id = ?");
    $stmt->execute([$referral_code, $user_id]);
    $user['referral_code'] = $referral_code;
}

include '../includes/header.php';
?>
<style>
.referral-card { text-align: center; padding: 30px; }
.referral-code {
    font-size: 24px;
    font-weight: bold;
    color: #4f46e5;
    background-color: #eef2ff;
    padding: 15px 25px;
    border-radius: 10px;
    display: inline-block;
    border: 2px dashed #c7d2fe;
    margin-top: 15px;
}
</style>

<div class="app-view">
    <div class="airtime-header">
        <a href="profile.php" class="back-btn">&#8592;</a>
        <span class="title">Referrals & Commissions</span>
    </div>

    <div class="container">
        <div class="form-card referral-card">
            <h3>Your Unique Referral Code</h3>
            <p>Share this code with your friends. When they sign up, you'll earn a commission on their transactions!</p>
            <div class="referral-code" id="refCode"><?php echo htmlspecialchars($user['referral_code']); ?></div>
            <button class="btn-submit" style="margin-top: 20px;" onclick="copyCode()">Copy Code</button>
        </div>
    </div>
</div>

<script>
function copyCode() {
    const code = document.getElementById('refCode').innerText;
    navigator.clipboard.writeText(code).then(() => {
        alert('Referral code copied to clipboard!');
    }, () => {
        alert('Failed to copy code.');
    });
}
</script>

<?php include '../includes/footer.php'; ?>
