<?php
require_once '../core/config.php';
require_once '../core/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }

    if (empty($phone_number)) {
        $errors[] = 'Phone number is required.';
    }

    if (empty($password) || strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $pdo = db_connect();
        $stmt = $pdo->prepare('SELECT email FROM users WHERE email = ?');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $errors[] = 'Email already exists.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone_number, password) VALUES (?, ?, ?, ?)');

            if ($stmt->execute([$full_name, $email, $phone_number, $hashed_password])) {
                // Log the new user in immediately
                $_SESSION['user_id'] = $pdo->lastInsertId();
                // Redirect to set the security PIN
                header('Location: set-pin.php');
                exit;
            } else {
                $errors[] = 'Something went wrong. Please try again.';
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h2>Register</h2>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="register.php" method="post">
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" name="full_name" id="full_name" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" required>
        </div>
        <div class="form-group">
            <label for="phone_number">Phone Number</label>
            <input type="tel" name="phone_number" id="phone_number" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
        </div>
        <button type="submit">Register</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
