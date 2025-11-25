<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Billpoint'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php if (isset($_SESSION['original_admin_id'])): ?>
    <div class="impersonation-banner">
        You are currently impersonating a user.
        <a href="admin/impersonate.php?action=stop">Return to your Admin session</a>.
    </div>
    <?php endif; ?>
    <header class="main-header">
        <div class="logo">
            <a href="dashboard.php">Billpoint</a>
        </div>
        <nav class="main-nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="signup.php">Sign Up</a>
            <?php endif; ?>
        </nav>
    </header>
    <main class="main-content">
