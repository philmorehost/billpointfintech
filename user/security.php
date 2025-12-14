<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$pdo = db_connect();
$user_id = $_SESSION['user_id'];
$feedback = ['message' => '', 'type' => ''];
$form_type = $_POST['form_type'] ?? '';

// Handle Password Change
if ($form_type === 'password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!password_verify($current_password, $user['password'])) {
        $feedback = ['message' => 'Your current password is not correct.', 'type' => 'errors'];
    } elseif ($new_password !== $confirm_password) {
        $feedback = ['message' => 'New password and confirmation do not match.', 'type' => 'errors'];
    } elseif (strlen($new_password) < 6) {
        $feedback = ['message' => 'Password must be at least 6 characters long.', 'type' => 'errors'];
    } else {
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_password_hash, $user_id]);
        $feedback = ['message' => 'Your password has been changed successfully.', 'type' => 'success'];
    }
}

// Handle PIN Change
if ($form_type === 'pin') {
    $current_pin = $_POST['current_pin'];
    $new_pin = $_POST['new_pin'];
    $confirm_pin = $_POST['confirm_pin'];

    $stmt = $pdo->prepare("SELECT pin FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_pin = $stmt->fetchColumn();

    if (!password_verify($current_pin, $user_pin)) {
        $feedback = ['message' => 'Your current PIN is incorrect.', 'type' => 'errors'];
    } elseif ($new_pin !== $confirm_pin) {
        $feedback = ['message' => 'New PIN and confirmation do not match.', 'type' => 'errors'];
    } elseif (!preg_match('/^\d{4}$/', $new_pin)) {
        $feedback = ['message' => 'PIN must be exactly 4 digits.', 'type' => 'errors'];
    } else {
        $new_pin_hash = password_hash($new_pin, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET pin = ? WHERE id = ?");
        $stmt->execute([$new_pin_hash, $user_id]);
        $feedback = ['message' => 'Your security PIN has been updated successfully.', 'type' => 'success'];
    }
}

include '../includes/header.php';
?>

<div class="app-view">
    <div class="airtime-header">
        <a href="profile.php" class="back-btn">&#8592;</a>
        <span class="title">Security Settings</span>
    </div>

    <div class="container">
        <?php if ($feedback['message']): ?>
            <div class="<?php echo htmlspecialchars($feedback['type']); ?> mb-3"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
        <?php endif; ?>

        <div class="form-card">
            <h3>Change Password</h3>
            <form method="post">
                <input type="hidden" name="form_type" value="password">
                <div class="form-group"><label for="current_password">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
                <div class="form-group"><label for="new_password">New Password</label><input type="password" name="new_password" class="form-control" required></div>
                <div class="form-group"><label for="confirm_password">Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required></div>
                <button type="submit" class="btn-submit">Update Password</button>
            </form>
        </div>

        <div class="form-card">
            <h3>Change Security PIN</h3>
            <form method="post">
                <input type="hidden" name="form_type" value="pin">
                <div class="form-group"><label for="current_pin">Current PIN</label><input type="password" name="current_pin" inputmode="numeric" maxlength="4" class="form-control" required></div>
                <div class="form-group"><label for="new_pin">New PIN</label><input type="password" name="new_pin" inputmode="numeric" maxlength="4" class="form-control" required></div>
                <div class="form-group"><label for="confirm_pin">Confirm New PIN</label><input type="password" name="confirm_pin" inputmode="numeric" maxlength="4" class="form-control" required></div>
                <button type="submit" class="btn-submit">Update PIN</button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
