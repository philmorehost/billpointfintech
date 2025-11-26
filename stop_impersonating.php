<?php
require_once 'includes/bootstrap.php';

if (isset($_SESSION['original_admin_id']) && isset($_SESSION['original_admin_role'])) {
    $admin_id = $_SESSION['original_admin_id'];
    $admin_role = $_SESSION['original_admin_role'];

    // Log the end of impersonation
    $log_stmt = $pdo->prepare("INSERT INTO audit_log (admin_id, action, target_user_id, ip_address) VALUES (?, 'impersonate_stop', ?, ?)");
    $log_stmt->execute([$admin_id, $_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);

    // Restore admin's session
    session_destroy();
    session_start(); // We need to start a new session to set the flash message
    $_SESSION['admin_id'] = $admin_id;
    $_SESSION['admin_role'] = $admin_role;

    set_flash_message('success', 'You have stopped impersonating and are back to your admin account.');
    header('Location: admin/index.php');
    exit();
}

header('Location: index.php');
exit();
