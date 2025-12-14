<?php
require_once '../core/config.php';
require_once '../core/functions.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['email'], $_POST['password'])) {
        $errors[] = 'Invalid form submission.';
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $remember_me = isset($_POST['remember_me']);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        }
        if (empty($password)) {
            $errors[] = 'Password is required.';
        }

        if (empty($errors)) {
            $pdo = db_connect();
            $stmt = $pdo->prepare('SELECT id, password, status FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $errors[] = 'Your account is currently inactive. Please contact support.';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['last_activity'] = time(); // Set initial activity time

                    if ($remember_me) {
                        $token = bin2hex(random_bytes(32));
                        $expiry = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30-day expiry

                        $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, remember_token_expiry = ? WHERE id = ?");
                        $stmt->execute([hash('sha256', $token), $expiry, $user['id']]);

                        setcookie('remember_me', $user['id'] . ':' . $token, time() + (86400 * 30), "/");
                    }

                    // Redirect to PIN entry page instead of dashboard
                    header('Location: enter-pin.php');
                    exit;
                }
            } else {
                $errors[] = 'Invalid email or password.';
            }
        }
    }
}

include '../includes/header.php';
?>
<div class="container" style="max-width: 500px; margin-top: 50px;">
    <h2>User Login</h2>
    <?php if (!empty($errors)): ?> <div class="errors"><?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?></div> <?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div class="form-group">
            <input type="checkbox" name="remember_me" id="remember_me">
            <label for="remember_me">Remember Me</label>
        </div>
        <button type="submit">Login</button>
    </form>
    <p style="margin-top: 15px;">Don't have an account? <a href="register.php">Register here</a>.</p>
</div>
<?php include '../includes/footer.php'; ?>
