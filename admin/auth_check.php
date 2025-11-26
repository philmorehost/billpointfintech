<?php
// admin/auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If no admin is logged in, redirect to the admin login page
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
