<?php
require_once '../includes/bootstrap.php';
require_once 'includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
        header('Location: users.php');
        exit();
    }
    $user_id_to_impersonate = (int)$_POST['user_id'];

    $stmt = $pdo->prepare("SELECT id, role, full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id_to_impersonate]);
    $user = $stmt->fetch();

    if ($user) {
        // Log the impersonation action
        $log_stmt = $pdo->prepare("INSERT INTO audit_log (admin_id, action, target_user_id, ip_address) VALUES (?, 'impersonate_start', ?, ?)");
        $log_stmt->execute([$_SESSION['admin_id'], $user_id_to_impersonate, $_SERVER['REMOTE_ADDR']]);

        // Store original admin ID and role, then switch to the user's session
        $_SESSION['original_admin_id'] = $_SESSION['admin_id'];
        $_SESSION['original_admin_role'] = $_SESSION['admin_role'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = 'user';

        set_flash_message('success', 'You are now impersonating ' . htmlspecialchars($user['full_name']));
        header('Location: ../dashboard.php');
        exit();
    }
}

set_flash_message('error', 'Invalid user selected for impersonation.');
header('Location: users.php');
exit();
