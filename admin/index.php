<?php
include 'header.php';

$pdo = db_connect();

// Get stats
$total_users = $pdo->query('SELECT count(*) FROM users')->fetchColumn();
$total_transactions = $pdo->query('SELECT count(*) FROM transactions')->fetchColumn();
$total_revenue = $pdo->query('SELECT SUM(amount) FROM transactions WHERE status = "success"')->fetchColumn();

// Get latest 5 transactions
$stmt = $pdo->query("SELECT t.*, u.full_name FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 5");
$latest_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php
// Check for database updates
$latest_db_version = 2; // This should match the version in update_database.php
$stmt_db = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'db_version'");
$current_db_version = $stmt_db->fetchColumn() ?? 0;

if ($current_db_version < $latest_db_version):
?>
<div class="alert alert-warning">
    <h4><i class="fas fa-exclamation-triangle"></i> Database Update Required</h4>
    <p>Your database schema is out of date. This can cause errors and unpredictable behavior. Please update your database to the latest version.</p>
    <a href="../update_database.php?source=admin" class="btn btn-success">Update Database Now</a>
</div>
<?php endif; ?>


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
    <div class="stat-card">
        <h3>Total Revenue</h3>
        <p>₦<?php echo number_format($total_revenue ?? 0, 2); ?></p>
    </div>
</div>

<div class="dashboard-widgets">
    <div class="widget">
        <h3>Latest Transactions</h3>
        <ul class="transaction-list">
            <?php if ($latest_transactions): ?>
                <?php foreach ($latest_transactions as $tx): ?>
                    <li>
                        <span class="tx-user"><?php echo htmlspecialchars($tx['full_name']); ?></span>
                        <span class="tx-desc"><?php echo htmlspecialchars($tx['description']); ?></span>
                        <span class="tx-amount">₦<?php echo htmlspecialchars(number_format($tx['amount'], 2)); ?></span>
                        <span class="badge badge-<?php echo htmlspecialchars($tx['status']); ?>"><?php echo htmlspecialchars(ucfirst($tx['status'])); ?></span>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>No transactions yet.</li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="widget">
        <h3>Quick Actions</h3>
        <div class="quick-actions">
            <a href="users.php" class="btn btn-primary">Manage Users</a>
            <a href="settings.php" class="btn btn-secondary">System Settings</a>
            <a href="api-manager.php" class="btn btn-info">API Manager</a>
        </div>
    </div>
</div>


<?php include 'footer.php'; ?>
