<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/auth_check.php';

$admin_id = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT full_name FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();
$admin_name = $admin['full_name'] ?? 'Admin';

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo $page_title ?? 'Billpoint'; ?></title>
    <link rel="stylesheet" href="../assets/css/new_style.css">
</head>
<body>
    <aside class="sidebar">
        <div class="logo">Admin Panel</div>
        <nav>
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Dashboard</a>
            <a href="users.php" class="<?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">Manage Users</a>
            <a href="transactions.php" class="<?php echo ($current_page == 'transactions.php') ? 'active' : ''; ?>">Transactions</a>
            <a href="settings.php" class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">Settings</a>
            <a href="gateway_fees.php" class="<?php echo ($current_page == 'gateway_fees.php') ? 'active' : ''; ?>">Gateway Fees</a>
            <a href="fx_rates.php" class="<?php echo ($current_page == 'fx_rates.php') ? 'active' : ''; ?>">FX Rates</a>
            <a href="transaction_limits.php" class="<?php echo ($current_page == 'transaction_limits.php') ? 'active' : ''; ?>">Limits</a>
        </nav>
        <a href="logout.php" class="logout">Logout</a>
    </aside>
    <div class="main-content">
        <header class="main-header">
            <h1><?php echo htmlspecialchars($page_title ?? 'Admin Dashboard'); ?></h1>
            <div class="header-actions">
                Welcome, <?php echo htmlspecialchars($admin_name); ?>
            </div>
        </header>
        <main>
