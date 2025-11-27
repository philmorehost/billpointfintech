<?php
include 'header.php';

$pdo = db_connect();

// Get total users
$total_users = $pdo->query('SELECT count(*) FROM users')->fetchColumn();

// Get total transactions
$total_transactions = $pdo->query('SELECT count(*) FROM transactions')->fetchColumn();
?>

<h2>Admin Dashboard</h2>

<div class="dashboard-stats">
    <div class="stat-card">
        <h3>Total Users</h3>
        <p><?php echo $total_users; ?></p>
    </div>
    <div class="stat-card">
        <h3>Total Transactions</h3>
        <p><?php echo $total_transactions; ?></p>
    </div>
</div>

<?php include 'footer.php'; ?>
