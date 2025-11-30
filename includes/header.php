<?php require_once '../core/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/mobile_app.css">
    <script src="https://merchant.beewave.ng/checkout.min.js"></script>
</head>
<body class="app-view">

<?php if (isset($_SESSION['original_admin_id'])): ?>
    <div class="impersonation-banner">
        <p><i class="fas fa-user-secret"></i> You are browsing as a user. <a href="../admin/exit-impersonation.php">Return to Admin</a></p>
    </div>
    <style>.impersonation-banner { background-color: #ffc107; padding: 5px; text-align: center; position: sticky; top: 0; z-index: 1001; } .impersonation-banner a { font-weight: bold; }</style>
<?php endif; ?>

<main>
    <!-- Page content will be injected here -->
