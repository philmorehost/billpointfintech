<?php
require_once '../core/auth_check.php'; // Protect the page
require_once '../core/functions.php';

$pdo = db_connect();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM services WHERE is_available = 1 ORDER BY name");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define which services are "primary"
$primary_services = ['airtime', 'data', 'electricity'];

include '../includes/header.php'; // Will be a much simpler header now
?>

<div class="top-card">
    <div class="balance-display">
        <p>Total Balance</p>
        <h1>&#8358;<?php echo htmlspecialchars(number_format($user['wallet_balance'], 2)); ?></h1>
    </div>
    <div class="top-actions">
        <a href="fund-wallet.php">
            <div class="action-icon"><i class="fas fa-plus-circle"></i></div>
            <span>Add Fund</span>
        </a>
        <a href="bank-transfer.php">
            <div class="action-icon"><i class="fas fa-paper-plane"></i></div>
            <span>Transfer</span>
        </a>
        <a href="virtual-account.php">
            <div class="action-icon"><i class="fas fa-university"></i></div>
            <span>My Account</span>
        </a>
    </div>
</div>

<div class="container app-view">
    <h3>Services</h3>
    <div class="services-grid-app">
        <?php foreach ($services as $service):
            if (in_array($service['slug'], $primary_services)): ?>
            <div class="service-button">
                <a href="<?php echo htmlspecialchars($service['slug']); ?>.php">
                    <i class="fas <?php echo htmlspecialchars($service['icon_class']); ?>"></i>
                    <p><?php echo htmlspecialchars(str_replace("Buy ", "", $service['name'])); ?></p>
                </a>
            </div>
        <?php endif; endforeach; ?>

        <div class="service-button more" id="more-services-btn">
             <a href="#">
                <i class="fas fa-th-large"></i>
                <p>More</p>
            </a>
        </div>
    </div>
</div>

<!-- Full Screen Modal for "More" Services -->
<div id="more-services-modal" class="full-screen-modal">
    <div class="modal-header">
        <h2>All Services</h2>
        <span class="modal-close" id="modal-close-btn">&times;</span>
    </div>
    <div class="modal-grid">
        <?php foreach ($services as $service): ?>
            <div class="service-button">
                <a href="<?php echo htmlspecialchars($service['slug']); ?>.php">
                    <i class="fas <?php echo htmlspecialchars($service['icon_class']); ?>"></i>
                    <p><?php echo htmlspecialchars($service['name']); ?></p>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Recent Transactions -->
<div class="container app-view">
    <h3>Recent Activity</h3>
    <div class="transaction-list">
        <?php
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC LIMIT 2");
        $stmt->execute([$_SESSION['user_id']]);
        $recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($recent_transactions) > 0):
            foreach ($recent_transactions as $transaction): ?>
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
            <?php endforeach;
        else: ?>
            <p>No recent transactions.</p>
        <?php endif; ?>
    </div>
     <a href="transactions.php" class="view-all-link">View All</a>
</div>


<?php include '../includes/footer.php'; // Will contain the new fixed nav ?>

<script>
document.getElementById('more-services-btn').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('more-services-modal').style.display = 'block';
});
document.getElementById('modal-close-btn').addEventListener('click', function() {
    document.getElementById('more-services-modal').style.display = 'none';
});
</script>
