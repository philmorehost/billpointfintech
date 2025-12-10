<?php
// This must be included on all admin pages
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth_check.php';
if (!is_admin()) {
    redirect('/dashboard.php');
}

// Determine the active page for sidebar styling
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - Billpoint Admin' : 'Billpoint Admin'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <a href="index.php" class="logo">Billpoint</a>
        <nav>
            <a href="index.php" class="<?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">Dashboard</a>
            <a href="users.php" class="<?php echo ($current_page === 'users.php') ? 'active' : ''; ?>">Users</a>
            <a href="p2p_transfers.php" class="<?php echo ($current_page === 'p2p_transfers.php') ? 'active' : ''; ?>">P2P Transfers</a>
            <a href="loans.php" class="<?php echo ($current_page === 'loans.php') ? 'active' : ''; ?>">Loan Requests</a>
            <a href="api_requests.php" class="<?php echo ($current_page === 'api_requests.php') ? 'active' : ''; ?>">API Requests</a>
            <a href="support.php" class="<?php echo ($current_page === 'support.php') ? 'active' : ''; ?>">Support Tickets</a>
            <hr>
            <a href="manual_wallet.php" class="<?php echo ($current_page === 'manual_wallet.php') ? 'active' : ''; ?>">Manual Wallet</a>
            <a href="risk_management.php" class="<?php echo ($current_page === 'risk_management.php') ? 'active' : ''; ?>">Risk Management</a>
            <a href="fx_rates.php" class="<?php echo ($current_page === 'fx_rates.php') ? 'active' : ''; ?>">FX Rates</a>
            <a href="plans.php" class="<?php echo ($current_page === 'plans.php') ? 'active' : ''; ?>">Service Plans</a>
            <a href="bulk_jobs.php" class="<?php echo ($current_page === 'bulk_jobs.php') ? 'active' : ''; ?>">Bulk Jobs</a>
             <a href="migrations.php" class="<?php echo ($current_page === 'migrations.php') ? 'active' : ''; ?>">Migrations</a>
        </nav>
        <div class="logout">
            <a href="../logout.php">Logout</a>
        </div>
    </aside>
    <main class="admin-main">
        <!-- Main content starts here -->
