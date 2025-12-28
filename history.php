<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - Billpoint</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .history-container { padding: 20px; }
        .transaction-item { border-bottom: 1px solid #ddd; padding: 10px 0; }
        .transaction-item:last-child { border-bottom: none; }
        .status-completed { color: green; }
        .status-failed { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Transaction History</h2>
        <div class="history-container">
            <?php if (empty($transactions)): ?>
                <p>You have no transactions yet.</p>
            <?php else: ?>
                <?php foreach ($transactions as $transaction): ?>
                    <div class="transaction-item">
                        <p><strong>Type:</strong> <?php echo htmlspecialchars(ucfirst($transaction['type'])); ?></p>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($transaction['description']); ?></p>
                        <p><strong>Amount:</strong> ₦<?php echo number_format($transaction['amount'], 2); ?></p>
                        <p><strong>Status:</strong> <span class="status-<?php echo $transaction['status']; ?>"><?php echo ucfirst($transaction['status']); ?></span></p>
                        <p><strong>Date:</strong> <?php echo $transaction['created_at']; ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'includes/footer_nav.php'; ?>
</body>
</html>
