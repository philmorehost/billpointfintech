<?php
// This should be at the top of all user-facing pages
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth_check.php';

// Get user's name for the welcome message
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$full_name = $user['full_name'] ?? 'User';

// Define the current page for active nav link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Billpoint'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/new_style.css">
</head>
<body>
    <?php if (isset($_SESSION['original_admin_id'])): ?>
        <div class="impersonation-banner">
            You are impersonating: <strong><?php echo htmlspecialchars($full_name); ?></strong>.
            <a href="stop_impersonating.php">Return to Admin</a>
        </div>
    <?php endif; ?>

    <aside class="sidebar">
        <div class="logo">
            Datagifting
        </div>
        <nav>
            <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <!-- SVG icon for Balances -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m12 3a3 3 0 11-6 0m6 0a3 3 0 00-6 0" /></svg>
                Balances
            </a>
            <a href="history.php" class="<?php echo ($current_page == 'history.php') ? 'active' : ''; ?>">
                 <!-- SVG icon for Payments -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3.375m-3.375 2.25h10.5m-10.5 2.25h10.5m4.5-1.5H15" /></svg>
                Payments
            </a>
            <a href="exchange.php" class="<?php echo ($current_page == 'exchange.php') ? 'active' : ''; ?>">
                 <!-- SVG icon for Exchange -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                Exchange
            </a>
             <a href="p2p_transfer.php" class="<?php echo ($current_page == 'p2p_transfer.php') ? 'active' : ''; ?>">
                 <!-- SVG icon for Refer & Earn -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m-7.5-2.962a3.75 3.75 0 015.962 0zM16.5 9.75a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                Refer & Earn
            </a>
            <a href="global_transfer.php" class="<?php echo ($current_page == 'global_transfer.php') ? 'active' : ''; ?>">
                <!-- SVG icon for Batch Payout -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" /></svg>
                Batch Payout
            </a>
        </nav>
        <a href="logout.php" class="logout">
            <!-- SVG icon for Logout -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
            Logout
        </a>
    </aside>
    <div class="main-content">
        <header class="main-header">
            <h1>Hello, <?php echo htmlspecialchars(explode(' ', $full_name)[0]); ?></h1>
            <div class="header-actions">
                <!-- Header action icons -->
                <button class="icon-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-1.007 1.11-1.007h2.593c.55 0 1.02.465 1.11 1.007l.073.439c.083.495.424.906.873 1.054l.448.15c.5.167.92.598 1.076 1.11l.073.439c.09.542-.257 1.06-.798 1.282l-.54.215c-.45.18-.81.562-1.004 1.006l-.215.54c-.222.54-.742.888-1.282.798l-.439-.073c-.495-.083-.906-.424-1.054-.873l-.15-.448c-.167-.5-.598-.92-1.11-1.076l-.439-.073a1.125 1.125 0 01-1.282.798l-.215.54c-.194.444-.554.826-1.004 1.006l-.54.215c-.54.222-1.06.257-1.282-.798l-.073-.439c-.09-.542.257-1.06.798-1.282l.54-.215c.45-.18.81-.562 1.004-1.006l.215-.54c.222-.54.742-.888 1.282-.798l.439.073c.495.083.906.424 1.054.873l.15.448c.167.5.598.92 1.11 1.076l.439.073c.54.222 1.06.257 1.282-.798l.073-.439z" /></svg>
                </button>
                <a href="profile.php" class="icon-btn">
                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </a>
            </div>
        </header>
        <main>
            <!-- Page-specific content starts here -->
