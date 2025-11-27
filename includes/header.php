<?php require_once '../core/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php if (isset($_SESSION['original_admin_id'])): ?>
    <div class="impersonation-banner">
        <p>
            <i class="fas fa-user-secret"></i>
            You are currently viewing the site as a user.
            <a href="../admin/exit-impersonation.php">Return to Admin Panel</a>
        </p>
    </div>
    <style>
    .impersonation-banner { background-color: #ffc107; color: #333; padding: 10px; text-align: center; }
    .impersonation-banner a { color: #007bff; font-weight: bold; }
    </style>
<?php endif; ?>

<header>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
<main>
