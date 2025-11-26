<?php
$page_title = 'My Profile';
require_once 'includes/header.php';

// Fetch user profile data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<div class="profile-container" style="max-width: 600px; margin: auto; background: #fff; padding: 2rem; border-radius: 1rem;">
    <h2>Update Profile</h2>
    <form action="profile_handler.php" method="POST">
        <?php echo generate_csrf_token_input(); ?>
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
        </div>
        <button type="submit" class="btn-primary">Update Profile</button>
    </form>

    <h2 style="margin-top: 2rem;">Change Password</h2>
    <form action="profile_handler.php" method="POST">
         <?php echo generate_csrf_token_input(); ?>
         <input type="hidden" name="action" value="change_password">
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>
        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn-primary">Change Password</button>
    </form>
</div>


<?php require_once 'includes/footer.php'; ?>
