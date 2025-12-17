<?php
if (!isset($_SESSION['user_id'])) {
    // Use an absolute path to ensure the redirect works from any directory
    $login_url = get_base_url() . 'login.php';
    header('Location: ' . $login_url);
    exit();
}

/**
 * Checks if the current logged-in user is an admin.
 *
 * @return bool
 */
function is_admin() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}
