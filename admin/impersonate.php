<?php
require_once '../core/functions.php';

if (!isset($_SESSION['admin_id'])) {
    // Only admins can impersonate
    header('Location: ../user/login.php');
    exit;
}

$user_id_to_impersonate = $_GET['id'] ?? null;

if (!$user_id_to_impersonate) {
    header('Location: users.php');
    exit;
}

// Store the original admin ID in a separate session variable
$_SESSION['original_admin_id'] = $_SESSION['admin_id'];

// Log in as the new user
$_SESSION['user_id'] = $user_id_to_impersonate;
unset($_SESSION['admin_id']); // Temporarily log out as admin

// Redirect to the user's dashboard
header('Location: ../user/dashboard.php');
exit;
