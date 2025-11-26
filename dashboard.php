<?php
$page_title = 'Dashboard';
require_once 'includes/header.php';

// Fetch all wallet balances for the user
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT currency, balance, ledger_balance FROM wallets WHERE user_id = ?");
$stmt->execute([$user_id]);
$wallets = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Default to NGN if available, otherwise pick the first wallet
$primary_currency = 'NGN';
$primary_balance = $wallets[$primary_currency]['balance'] ?? 0;
$primary_ledger_balance = $wallets[$primary_currency]['ledger_balance'] ?? 0;

?>

<div class="dashboard-grid">
    <div class="dashboard-main">
        <!-- Balance Card -->
        <div class="balance-card-new">
            <p>Available balance</p>
            <h2><?php echo htmlspecialchars($primary_currency); ?> <?php echo number_format($primary_balance, 2); ?></h2>
            <p>Ledger balance: <?php echo number_format($primary_ledger_balance, 2); ?></p>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="fund_wallet.php" class="action-btn">
                <svg><!-- deposit icon --></svg>
                <span>Deposit</span>
            </a>
            <a href="p2p_transfer.php" class="action-btn">
                <svg><!-- transfer icon --></svg>
                <span>Transfer</span>
            </a>
            <a href="exchange.php" class="action-btn">
                <svg><!-- convert icon --></svg>
                <span>Convert</span>
            </a>
            <a href="support.php" class="action-btn">
                <svg><!-- request icon --></svg>
                <span>Request</span>
            </a>
        </div>

        <!-- Balances / Transactions List -->
        <div class="transactions-card">
            <div class="tabs">
                <div class="tab active" data-target="balances-content">Balances</div>
                <div class="tab" data-target="transactions-content">Transactions</div>
            </div>
            <div id="balances-content" class="tab-content">
                <?php foreach ($wallets as $currency => $wallet): ?>
                <div class="balance-list-item">
                    <img src="assets/img/flags/<?php echo strtolower($currency); ?>.png" alt="<?php echo $currency; ?>" class="currency-icon">
                    <div class="currency-details">
                        <p class="currency-name"><?php echo htmlspecialchars($currency); ?></p>
                        <p class="currency-fullname">
                            <?php
                                $currency_map = ['NGN' => 'Nigerian Naira', 'USD' => 'US Dollar', 'CAD' => 'Canadian Dollar', 'USDT' => 'Tether (USDT)', 'USDC' => 'USD Coin'];
                                echo $currency_map[$currency] ?? '';
                            ?>
                        </p>
                    </div>
                    <div class="balance-amounts">
                        <p class="primary-balance"><?php echo number_format($wallet['balance'], 2); ?></p>
                        <p class="secondary-balance">Ledger: <?php echo number_format($wallet['ledger_balance'], 2); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="transactions-content" class="tab-content" style="display: none;">
                <!-- Transaction history would be loaded here, possibly via AJAX -->
                <p>Transaction history coming soon.</p>
            </div>
        </div>
    </div>

    <div class="dashboard-sidebar">
        <!-- Send Money Form -->
        <div class="send-money-card">
            <h3>Send money</h3>
            <form action="p2p_transfer.php" method="POST">
                <?php echo generate_csrf_token_input(); ?>
                <div class="form-group">
                    <label for="send-amount">You send</label>
                    <div class="input-group">
                        <input type="text" id="send-amount" name="amount" placeholder="0.00">
                        <select class="currency-selector" name="currency">
                            <option>USD</option>
                            <option selected>NGN</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="receive-amount">They receive</label>
                    <div class="input-group">
                        <input type="text" id="receive-amount" placeholder="0.00" disabled>
                         <select class="currency-selector">
                            <option>CAD</option>
                            <option selected>NGN</option>
                        </select>
                    </div>
                </div>
                 <div class="form-group">
                    <label for="recipient">Recipient</label>
                    <input type="text" id="recipient" name="recipient" placeholder="Enter recipient's username or email">
                </div>
                <button type="submit" class="btn-primary">Send money</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
