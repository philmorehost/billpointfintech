<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$pdo = db_connect();
$user_id = $_SESSION['user_id'];
$feedback = ['message' => '', 'type' => ''];

// Fetch user data
$stmt = $pdo->prepare("SELECT full_name, phone_number FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');

    if (empty($full_name) || !preg_match('/^\d{11}$/', $phone_number)) {
        $feedback = ['message' => 'Please provide a valid full name and 11-digit phone number.', 'type' => 'errors'];
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone_number = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone_number, $user_id]);
            $feedback = ['message' => 'Your information has been updated successfully.', 'type' => 'success'];
            // Re-fetch user data to display updated info
            $stmt = $pdo->prepare("SELECT full_name, phone_number FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } catch (Exception $e) {
            $feedback = ['message' => 'An error occurred while updating your information.', 'type' => 'errors'];
        }
    }
}

include '../includes/header.php';
?>

<div class="app-view">
    <div class="airtime-header">
        <a href="profile.php" class="back-btn">&#8592;</a>
        <span class="title">Edit Information</span>
    </div>

    <div class="container">
        <div class="form-card">
            <?php if ($feedback['message']): ?>
                <div class="<?php echo htmlspecialchars($feedback['type']); ?> mb-3"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" name="full_name" id="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="tel" name="phone_number" id="phone_number" class="form-control" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                </div>
                <button type="submit" class="btn-submit">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
