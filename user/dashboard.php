<?php
require_once '../core/auth_check.php'; // Protect the page
require_once '../core/functions.php';

$pdo = db_connect();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM services WHERE is_available = 1 ORDER BY name");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
<style>
    /* Responsive grid for services */
    .services-grid-app {
        display: grid;
        grid-template-columns: repeat(3, 1fr); /* 3 columns for mobile */
        gap: 15px;
    }
    .service-button {
        text-align: center;
    }
    .service-button a {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background-color: #fff;
        padding: 15px 5px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        text-decoration: none;
        color: #333;
        font-size: 12px;
        height: 100%;
    }
    .service-button i {
        font-size: 24px;
        margin-bottom: 8px;
        color: #4f46e5;
    }
    /* Larger screens */
    @media (min-width: 768px) {
        .services-grid-app {
            grid-template-columns: repeat(4, 1fr); /* 4 columns for tablets */
        }
    }
    @media (min-width: 992px) {
        .services-grid-app {
            grid-template-columns: repeat(6, 1fr); /* 6 columns for desktops */
        }
    }
</style>

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
        <?php foreach ($services as $service): ?>
            <div class="service-button">
                <a href="<?php echo htmlspecialchars($service['slug']); ?>.php">
                    <i class="fas <?php echo htmlspecialchars($service['icon_class']); ?>"></i>
                    <p><?php echo htmlspecialchars(str_replace("Buy ", "", $service['name'])); ?></p>
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
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 2");
        $stmt->execute([$_SESSION['user_id']]);
        $recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($recent_transactions) > 0):
            foreach ($recent_transactions as $transaction): ?>
                 <a href="transaction_details.php?id=<?php echo $transaction['id']; ?>" class="transaction-item-link">
                    <div class="transaction-item">
                        <div class="transaction-icon"><i class="fas fa-receipt"></i></div>
                        <div class="transaction-details">
                            <p><?php echo htmlspecialchars(ucfirst($transaction['service'])); ?> - <?php echo htmlspecialchars($transaction['description']); ?></p>
                            <small><?php echo date("d M, Y g:ia", strtotime($transaction['created_at'])); ?></small>
                        </div>
                        <div class="transaction-amount <?php echo $transaction['amount'] > 0 ? 'credit' : 'debit'; ?>">
                            &#8358;<?php echo htmlspecialchars(number_format(abs($transaction['amount']), 2)); ?>
                        </div>
                    </div>
                </a>
            <?php endforeach;
        else: ?>
            <p>No recent transactions.</p>
        <?php endif; ?>
    </div>
     <a href="transactions.php" class="view-all-link">View All</a>
</div>


<?php include 'includes/footer.php'; ?>
