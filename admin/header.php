<?php
require_once '../core/config.php';
require_once '../core/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Run database migrations on every admin page load
$pdo = db_connect();
run_database_migrations($pdo);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
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
            <li>
                <a href="index.php"><i class="fas fa-tachometer-alt"></i> <span class="link-text">Dashboard</span></a>
            </li>
            <li>
                <a href="transactions.php"><i class="fas fa-exchange-alt"></i> <span class="link-text">Transactions</span></a>
            </li>
            <li>
                <a href="#userSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-users"></i> <span class="link-text">Users</span></a>
                <ul class="collapse list-unstyled" id="userSubmenu">
                    <li><a href="users.php"><span class="link-text">Manage Users</span></a></li>
                    <li><a href="pending-deposits.php"><span class="link-text">Pending Deposits</span></a></li>
                </ul>
            </li>
            <li>
                <a href="#productSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-box-open"></i> <span class="link-text">Products</span></a>
                <ul class="collapse list-unstyled" id="productSubmenu">
                    <li><a href="products.php"><span class="link-text">Manage Data Plans</span></a></li>
                    <li><a href="exam-pins.php"><span class="link-text">Manage Exam Pins</span></a></li>
                    <li><a href="cable-packages.php"><span class="link-text">Manage Cable TV</span></a></li>
                    <li><a href="electricity-discos.php"><span class="link-text">Manage Electricity</span></a></li>
                    <li><a href="service-manager.php"><span class="link-text">Service Discounts</span></a></li>
                    <!-- Links for Cable TV, etc., can be added here -->
                </ul>
            </li>
             <li>
                <a href="#settingsSubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-cogs"></i> <span class="link-text">Settings</span></a>
                <ul class="collapse list-unstyled" id="settingsSubmenu">
                    <li><a href="settings.php"><span class="link-text">System Settings</span></a></li>
                    <li><a href="bonus-settings.php"><span class="link-text">Bonus Settings</span></a></li>
                    <li><a href="gateways.php"><span class="link-text">Payment Gateways</span></a></li>
                    <li><a href="api-manager.php"><span class="link-text">API Manager</span></a></li>
                </ul>
            </li>
            <li>
                <a href="#securitySubmenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-shield-alt"></i> <span class="link-text">Security</span></a>
                <ul class="collapse list-unstyled" id="securitySubmenu">
                    <li><a href="limits.php"><span class="link-text">Transaction Limits</span></a></li>
                    <li><a href="blacklist.php"><span class="link-text">Blacklist</span></a></li>
                </ul>
            </li>
            <li>
                <a href="manage-admins.php"><i class="fas fa-user-shield"></i> <span class="link-text">Manage Admins</span></a>
            </li>
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
