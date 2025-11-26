<?php
$page_title = 'Transaction History';
require_once 'includes/header.php';

// Fetch user's transaction history
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC LIMIT 50");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll();
?>

<div class="history-container" style="background: #fff; padding: 2rem; border-radius: 1rem;">
    <h2>Transaction History</h2>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align: left; border-bottom: 1px solid #e5e7eb;">
                <th style="padding: 1rem;">Date</th>
                <th style="padding: 1rem;">Type</th>
                <th style="padding: 1rem;">Amount</th>
                <th style="padding: 1rem;">Description</th>
                <th style="padding: 1rem;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $tx): ?>
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 1rem;"><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($tx['transaction_date']))); ?></td>
                <td style="padding: 1rem;"><?php echo htmlspecialchars(ucfirst($tx['type'])); ?></td>
                <td style="padding: 1rem;"><?php echo htmlspecialchars($tx['currency']); ?> <?php echo number_format($tx['amount'], 2); ?></td>
                <td style="padding: 1rem;"><?php echo htmlspecialchars($tx['description']); ?></td>
                <td style="padding: 1rem;"><?php echo htmlspecialchars(ucfirst($tx['status'])); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($transactions)): ?>
            <tr>
                <td colspan="5" style="padding: 1rem; text-align: center;">No transactions found.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
