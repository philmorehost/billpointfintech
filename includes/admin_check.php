<?php
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
    set_flash_message('error', 'You do not have permission to access this page.');
    header('Location: login.php');
    exit();
}
