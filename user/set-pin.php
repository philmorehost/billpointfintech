<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$pdo = db_connect();
$user_id = $_SESSION['user_id'];
$feedback = ['message' => '', 'type' => ''];

$stmt = $pdo->prepare("SELECT security_pin FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$has_pin = $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = $_POST['pin'] ?? '';
    $pin_confirm = $_POST['pin_confirm'] ?? '';

    if (!preg_match('/^\d{4}$/', $pin)) {
        $feedback = ['message' => 'Your PIN must be exactly 4 digits.', 'type' => 'errors'];
    } elseif ($pin !== $pin_confirm) {
        $feedback = ['message' => 'The PINs you entered do not match.', 'type' => 'errors'];
    } else {
        try {
            $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET security_pin = ? WHERE id = ?");
            $stmt->execute([$hashed_pin, $user_id]);
            $feedback = ['message' => 'Your Security PIN has been ' . ($has_pin ? 'updated' : 'set') . ' successfully.', 'type' => 'success'];
            $has_pin = true; // Update for current page view
        } catch (Exception $e) {
            $feedback = ['message' => 'An error occurred. Please try again.', 'type' => 'errors'];
        }
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h2><?php echo $has_pin ? 'Update' : 'Set'; ?> Your Security PIN</h2>

    <?php if ($feedback['message']): ?>
        <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
    <?php endif; ?>

    <div class="notice">
        <p>Your 4-digit Security PIN is used to authorize sensitive transactions if you have been inactive for a while. Keep it safe.</p>
    </div>

    <form method="post">
        <div class="form-group">
            <label for="pin">Enter 4-Digit PIN</label>
            <input type="password" name="pin" id="pin" inputmode="numeric" pattern="\d{4}" maxlength="4" required>
        </div>
        <div class="form-group">
            <label for="pin_confirm">Confirm 4-Digit PIN</label>
            <input type="password" name="pin_confirm" id="pin_confirm" inputmode="numeric" pattern="\d{4}" maxlength="4" required>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $has_pin ? 'Update PIN' : 'Set PIN'; ?></button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
