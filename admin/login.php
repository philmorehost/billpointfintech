<?php
$page_title = 'Admin Login';
require_once __DIR__ . '/../includes/bootstrap.php';

// If admin is already logged in, redirect to the dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/new_style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-container { max-width: 400px; width: 100%; }
    </style>
</head>
<body>
    <div class="login-container">
        <div style="background: #fff; padding: 2rem; border-radius: 1rem;">
            <h2>Admin Login</h2>
            <?php display_flash_message(); ?>
            <form action="auth_handler.php" method="POST">
                <?php echo generate_csrf_token_input(); ?>
                <input type="hidden" name="action" value="admin_login">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
