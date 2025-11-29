<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$pdo = db_connect();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container app-view">
    <div class="profile-header">
        <div class="profile-avatar">
            <?php echo htmlspecialchars(strtoupper(substr($user['full_name'], 0, 1))); ?>
        </div>
        <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
        <p>@<?php echo htmlspecialchars($user['email']); // Assuming email is the username substitute ?></p>
    </div>

    <div class="settings-list">
        <h4>General settings</h4>
        <a href="personal-info.php" class="settings-item">
            <div class="settings-icon"><i class="fas fa-user-circle"></i></div>
            <div class="settings-text">
                <h5>Personal Information</h5>
                <p>Edit your information</p>
            </div>
            <i class="fas fa-chevron-right"></i>
        </a>
        <a href="settings.php" class="settings-item">
            <div class="settings-icon"><i class="fas fa-cog"></i></div>
            <div class="settings-text">
                <h5>Settings</h5>
                <p>Account, notifications</p>
            </div>
            <i class="fas fa-chevron-right"></i>
        </a>
        <a href="account-limits.php" class="settings-item">
            <div class="settings-icon"><i class="fas fa-shield-alt"></i></div>
            <div class="settings-text">
                <h5>Account Limits</h5>
                <p>Upgrade your Billpoint account</p>
            </div>
            <i class="fas fa-chevron-right"></i>
        </a>
        <a href="referrals.php" class="settings-item">
            <div class="settings-icon"><i class="fas fa-users"></i></div>
            <div class="settings-text">
                <h5>My Referral</h5>
                <p>Referrals, commissions</p>
            </div>
            <i class="fas fa-chevron-right"></i>
        </a>
        <div class="settings-item">
            <div class="settings-icon"><i class="fas fa-moon"></i></div>
            <div class="settings-text">
                <h5>Dark Mode</h5>
                <p>Switch app display mode</p>
            </div>
            <div class="toggle-switch">
                <input type="checkbox" id="dark-mode-toggle" />
                <label for="dark-mode-toggle"></label>
            </div>
        </div>
        <a href="support.php" class="settings-item">
            <div class="settings-icon"><i class="fas fa-headset"></i></div>
            <div class="settings-text">
                <h5>Help & Support</h5>
                <p>Help or contact our customer service</p>
            </div>
            <i class="fas fa-chevron-right"></i>
        </a>
        <a href="legal.php" class="settings-item">
            <div class="settings-icon"><i class="fas fa-info-circle"></i></div>
            <div class="settings-text">
                <h5>Legal</h5>
                <p>Privacy, Security & Terms of use</p>
            </div>
            <i class="fas fa-chevron-right"></i>
        </a>
        <a href="logout.php" class="settings-item">
            <div class="settings-icon"><i class="fas fa-sign-out-alt"></i></div>
            <div class="settings-text">
                <h5>Log Out</h5>
                <p>Sign Out of your account</p>
            </div>
            <i class="fas fa-chevron-right"></i>
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
