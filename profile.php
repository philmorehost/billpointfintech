<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Billpoint</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>My Profile</h2>
        <?php display_flash_message(); ?>

        <div>
            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
        </div>

        <hr>

        <h3>Change Password</h3>
        <form action="profile_handler.php" method="POST">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" name="new_password" required>
            </div>
            <button type="submit" class="btn">Change Password</button>
        </form>

        <hr>

        <h3>Change PIN</h3>
        <form action="profile_handler.php" method="POST">
            <input type="hidden" name="action" value="change_pin">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group">
                <label for="current_password_pin">Current Password</label>
                <input type="password" name="current_password_pin" required>
            </div>
            <div class="form-group">
                <label for="new_pin">New 4-Digit PIN</label>
                <input type="password" name="new_pin" maxlength="4" required>
            </div>
            <button type="submit" class="btn">Change PIN</button>
        </form>
    </div>
    <?php include 'includes/footer_nav.php'; ?>
</body>
</html>
