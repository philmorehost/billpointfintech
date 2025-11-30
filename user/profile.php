<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$pdo = db_connect();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
<style>
.profile-header { text-align: center; padding: 20px; }
.profile-avatar { width: 80px; height: 80px; border-radius: 50%; background-color: #4f46e5; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 36px; margin: 0 auto 15px; }
.profile-header h3 { margin: 0; font-size: 22px; }
.profile-header p { color: #777; margin-top: 5px; }

.profile-menu { padding: 0 15px; }
.profile-menu-btn {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 15px;
    margin-bottom: 10px;
    background-color: #fff;
    border: 1px solid #eee;
    border-radius: 10px;
    text-align: left;
    font-size: 16px;
    color: #333;
    text-decoration: none;
    transition: background-color 0.2s, box-shadow 0.2s;
}
.profile-menu-btn:hover { background-color: #f9f9f9; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.profile-menu-btn .icon { font-size: 20px; color: #4f46e5; margin-right: 15px; width: 25px; text-align: center; }
.profile-menu-btn .text { flex-grow: 1; }
.profile-menu-btn .chevron { margin-left: auto; color: #aaa; }
</style>

<div class="app-view">
    <div class="airtime-header">
        <span class="title">My Profile</span>
    </div>

    <div class="profile-header">
        <div class="profile-avatar">
            <?php echo htmlspecialchars(strtoupper(substr($user['full_name'], 0, 1))); ?>
        </div>
        <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
        <p><?php echo htmlspecialchars($user['email']); ?></p>
    </div>

    <div class="profile-menu">
        <a href="edit-information.php" class="profile-menu-btn">
            <i class="fas fa-user-edit icon"></i>
            <span class="text">Edit Information</span>
            <i class="fas fa-chevron-right chevron"></i>
        </a>
        <a href="security.php" class="profile-menu-btn">
            <i class="fas fa-shield-alt icon"></i>
            <span class="text">Security</span>
            <i class="fas fa-chevron-right chevron"></i>
        </a>
        <a href="referrals.php" class="profile-menu-btn">
            <i class="fas fa-users icon"></i>
            <span class="text">Referrals & Commissions</span>
            <i class="fas fa-chevron-right chevron"></i>
        </a>
        <a href="privacy.php" class="profile-menu-btn">
            <i class="fas fa-user-secret icon"></i>
            <span class="text">Privacy Policy</span>
            <i class="fas fa-chevron-right chevron"></i>
        </a>
        <a href="terms.php" class="profile-menu-btn">
            <i class="fas fa-file-contract icon"></i>
            <span class="text">Terms of Use</span>
            <i class="fas fa-chevron-right chevron"></i>
        </a>
        <a href="logout.php" class="profile-menu-btn" style="color: #e54646;">
            <i class="fas fa-sign-out-alt icon" style="color: #e54646;"></i>
            <span class="text">Log Out</span>
            <i class="fas fa-chevron-right chevron"></i>
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
