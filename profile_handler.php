<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
        header('Location: profile.php');
        exit();
    }

    $user_id = $_SESSION['user_id'];

    if ($_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->execute([$hashed_password, $user_id]);
            set_flash_message('success', 'Password changed successfully.');
        } else {
            set_flash_message('error', 'Incorrect current password.');
        }
    }

    if ($_POST['action'] === 'change_pin') {
        $current_password = $_POST['current_password_pin'];
        $new_pin = $_POST['new_pin'];

        if (!preg_match('/^[0-9]{4}$/', $new_pin)) {
            set_flash_message('error', 'PIN must be a 4-digit number.');
            header('Location: profile.php');
            exit();
        }

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            $hashed_pin = password_hash($new_pin, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET pin = ? WHERE id = ?");
            $update_stmt->execute([$hashed_pin, $user_id]);
            set_flash_message('success', 'PIN changed successfully.');
        } else {
            set_flash_message('error', 'Incorrect password.');
        }
    }

    header('Location: profile.php');
    exit();
}
