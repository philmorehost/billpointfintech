<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../includes/bootstrap.php';

// Log an action to the audit log
function log_admin_action($pdo, $admin_id, $action, $target_user_id = null, $details = '') {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO audit_log (admin_id, action, target_user_id, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$admin_id, $action, $target_user_id, $details, $ip]);
}

// --- Start Impersonation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id_to_impersonate'])) {
    if (validate_csrf_token()) {
        $user_id_to_impersonate = (int)$_POST['user_id_to_impersonate'];

        // Ensure we're not already impersonating
        if (!isset($_SESSION['original_admin_id'])) {
            // Get user details to confirm they are a regular user
            $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id = ? AND role = 'user'");
            $stmt->execute([$user_id_to_impersonate]);
            $user = $stmt->fetch();

            if ($user) {
                // Log the action
                log_admin_action($pdo, $_SESSION['user_id'], 'impersonate_start', $user_id_to_impersonate);

                // Store original admin session
                $_SESSION['original_admin_id'] = $_SESSION['user_id'];
                $_SESSION['original_admin_role'] = $_SESSION['user_role'];

                // Switch to the new user's session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];

                header('Location: ../dashboard.php');
                exit();
            }
        }
    }
}

// --- Stop Impersonation ---
if (isset($_GET['action']) && $_GET['action'] === 'stop') {
    if (isset($_SESSION['original_admin_id'])) {

        log_admin_action($pdo, $_SESSION['original_admin_id'], 'impersonate_stop', $_SESSION['user_id']);

        // Restore original admin session
        $_SESSION['user_id'] = $_SESSION['original_admin_id'];
        $_SESSION['user_role'] = $_SESSION['original_admin_role'];

        unset($_SESSION['original_admin_id']);
        unset($_SESSION['original_admin_role']);
    }
    header('Location: ../admin/index.php');
    exit();
}

// Default redirect if accessed directly
header('Location: users.php');
exit();
