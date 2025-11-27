<?php
require_once '../core/functions.php';

if (!isset($_SESSION['original_admin_id'])) {
    // If there is no original admin, they don't belong here
    header('Location: ../user/login.php');
    exit;
}

// Restore the original admin's session
$_SESSION['admin_id'] = $_SESSION['original_admin_id'];

// Remove the temporary session variables
unset($_SESSION['original_admin_id']);
unset($_SESSION['user_id']);

// Redirect back to the admin's user management page
header('Location: users.php');
exit;
