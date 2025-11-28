<?php
require_once '../core/config.php';
require_once '../core/functions.php';
require_once '../core/auth_check.php'; // Ensures user is logged in

$pdo = db_connect();
$user_id = $_SESSION['user_id'];

// Check if user already has a PIN
$stmt = $pdo->prepare("SELECT security_pin FROM users WHERE id = ?");
$stmt->execute([$user_id]);
if ($stmt->fetchColumn()) {
    // If they already have a pin, they shouldn't be here.
    header('Location: dashboard.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = $_POST['pin'] ?? '';
    $confirm_pin = $_POST['confirm_pin'] ?? '';

    if (!preg_match('/^[0-9]{4}$/', $pin)) {
        $errors[] = 'Your PIN must be exactly 4 digits.';
    } elseif ($pin !== $confirm_pin) {
        $errors[] = 'The PINs you entered do not match.';
    }

    if (empty($errors)) {
        $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET security_pin = ? WHERE id = ?");
        if ($stmt->execute([$hashed_pin, $user_id])) {
            // Pin is set, now they can proceed.
            // We'll also mark the pin as "verified" for this session.
            $_SESSION['pin_verified_at'] = time();
            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = 'An error occurred while setting your PIN. Please try again.';
        }
    }
}

include '../includes/header.php';
?>

<div class="container" style="max-width: 500px; margin-top: 50px;">
    <h2>Set Your Security PIN</h2>
    <p>Please set a 4-digit PIN for transaction security.</p>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="pin">New 4-Digit PIN</label>
            <input type="password" name="pin" id="pin" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" required>
        </div>
        <div class="form-group">
            <label for="confirm_pin">Confirm PIN</label>
            <input type="password" name="confirm_pin" id="confirm_pin" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" required>
        </div>
        <button type="submit">Set PIN and Continue</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
