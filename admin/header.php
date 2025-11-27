<?php
require_once '../core/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="admin-wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><span class="link-text">Billpoint</span></h3>
        </div>

        <ul class="list-unstyled components">
            <p>Admin Menu</p>
            <li>
                <a href="index.php"><i class="fas fa-tachometer-alt"></i> <span class="link-text">Dashboard</span></a>
            </li>
            <li>
                <a href="users.php"><i class="fas fa-users"></i> <span class="link-text">Manage Users</span></a>
            </li>
            <!-- More links will be added here in later steps -->
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-light navbar-admin">
            <div class="container-fluid">
                <button type="button" id="sidebar-toggle" class="btn">
                    <i class="fas fa-align-left"></i>
                </button>
                <div class="ml-auto">
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </nav>
        <main class="container-fluid">
