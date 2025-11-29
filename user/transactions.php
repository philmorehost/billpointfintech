<?php
require_once '../core/auth_check.php';
require_once '../core/functions.php';

$pdo = db_connect();

// Handle search
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM transactions WHERE user_id = :user_id";
if (!empty($search)) {
    $sql .= " AND (description LIKE :search OR service LIKE :search)";
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
if (!empty($search)) {
    $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
}
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container app-view">
    <div class="page-header">
        <h1>Transactions</h1>
    </div>

    <form action="transactions.php" method="GET" class="search-form">
        <input type="text" name="search" placeholder="Search transactions..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit"><i class="fas fa-search"></i></button>
    </form>

    <div class="transaction-list">
        <?php if (count($transactions) > 0): ?>
            <?php foreach ($transactions as $transaction): ?>
                <div class="transaction-item">
                    <div class="transaction-icon">
                        <i class="fas fa-arrow-down"></i> <!-- Placeholder Icon -->
                    </div>
                    <div class="transaction-details">
                        <p><?php echo htmlspecialchars(ucfirst($transaction['type'])); ?> - <?php echo htmlspecialchars($transaction['description']); ?></p>
                        <small><?php echo date("d M, Y g:ia", strtotime($transaction['transaction_date'])); ?></small>
                    </div>
                    <div class="transaction-amount <?php echo $transaction['amount'] > 0 ? 'credit' : 'debit'; ?>">
                        &#8358;<?php echo htmlspecialchars(number_format(abs($transaction['amount']), 2)); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No transactions found.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
