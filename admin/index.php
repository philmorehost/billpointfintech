<?php
$page_title = 'Admin Dashboard';
require_once 'includes/header.php';

// Fetch some stats for the dashboard
$user_count = $pdo->query("SELECT count(*) FROM users")->fetchColumn();
$total_transactions = $pdo->query("SELECT count(*) FROM transactions")->fetchColumn();
$pending_p2p = $pdo->query("SELECT count(*) FROM p2p_transfers WHERE status = 'pending'")->fetchColumn();
?>

<div class="admin-dashboard-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
    <div class="stat-card" style="background: #fff; padding: 1.5rem; border-radius: 0.5rem;">
        <h3>Total Users</h3>
        <p style="font-size: 2rem; font-weight: 600;"><?php echo $user_count; ?></p>
    </div>
    <div class="stat-card" style="background: #fff; padding: 1.5rem; border-radius: 0.5rem;">
        <h3>Total Transactions</h3>
        <p style="font-size: 2rem; font-weight: 600;"><?php echo $total_transactions; ?></p>
    </div>
    <div class="stat-card" style="background: #fff; padding: 1.5rem; border-radius: 0.5rem;">
        <h3>Pending P2P Transfers</h3>
        <p style="font-size: 2rem; font-weight: 600;"><?php echo $pending_p2p; ?></p>
        <a href="p2p_transfers.php">View</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
