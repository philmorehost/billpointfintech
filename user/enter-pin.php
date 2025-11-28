<?php
require_once '../core/config.php';
require_once '../core/functions.php';
require_once '../core/auth_check.php'; // Ensures user is logged in (session exists)

$pdo = db_connect();
$user_id = $_SESSION['user_id'];

// Check if the user has a PIN set. If not, they need to set one.
$stmt = $pdo->prepare("SELECT security_pin FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_pin_hash = $stmt->fetchColumn();
if (!$user_pin_hash) {
    header('Location: set-pin.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = $_POST['pin'] ?? '';

    if (!preg_match('/^[0-9]{4}$/', $pin)) {
        $errors[] = 'Invalid PIN format. Please enter 4 digits.';
    } elseif (password_verify($pin, $user_pin_hash)) {
        // PIN is correct. Mark as verified and redirect to dashboard.
        $_SESSION['pin_verified_at'] = time();
        header('Location: dashboard.php');
        exit;
    } else {
        $errors[] = 'The PIN you entered is incorrect.';
    }
}

include '../includes/header.php';
?>

<div class="container" style="max-width: 500px; margin-top: 50px;">
    <h2>Enter Security PIN</h2>
    <p>Please enter your 4-digit PIN to continue.</p>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="pin">4-Digit PIN</label>
            <input type="password" name="pin" id="pin" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" required>
        </div>
        <button type="submit">Unlock</button>
    </form>
    <p style="margin-top: 15px;"><a href="logout.php">Not you? Log out.</a></p>
</div>

<?php include '../includes/footer.php'; ?>
