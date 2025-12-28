<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM wallets WHERE user_id = ? ORDER BY currency");
$stmt->execute([$user_id]);
$wallets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wallets - Billpoint</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .wallets-container { padding: 20px; }
        .wallet-item { border-bottom: 1px solid #ddd; padding: 10px 0; }
        .wallet-item:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>My Wallets</h2>
        <div class="wallets-container">
            <?php if (empty($wallets)): ?>
                <p>You have no wallets yet.</p>
            <?php else: ?>
                <?php foreach ($wallets as $wallet): ?>
                    <div class="wallet-item">
                        <p><strong>Currency:</strong> <?php echo htmlspecialchars($wallet['currency']); ?></p>
                        <p><strong>Balance:</strong> <?php echo number_format($wallet['balance'], 4); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'includes/footer_nav.php'; ?>
</body>
</html>
