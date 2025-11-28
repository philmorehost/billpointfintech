<?php
include 'header.php';

$pdo = db_connect();

// Handle search query
$search_term = $_GET['search'] ?? '';
$query = "SELECT t.*, u.email FROM transactions t JOIN users u ON t.user_id = u.id";
$params = [];

if (!empty($search_term)) {
    $query .= " WHERE u.email LIKE ? OR t.service LIKE ? OR t.reference LIKE ?";
    $params = ["%$search_term%", "%$search_term%", "%$search_term%"];
}

$query .= " ORDER BY t.created_at DESC LIMIT 100"; // Limit to 100 for performance

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<h2>Transaction History</h2>

<div class="search-bar">
    <form method="get">
        <input type="text" name="search" placeholder="Search by email, service, or reference..." value="<?php echo htmlspecialchars($search_term); ?>">
        <button type="submit">Search</button>
    </form>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>User</th>
                <th>Service</th>
                <th>Description</th>
                <th>Amount (₦)</th>
                <th>Status</th>
                <th>Reference</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="7">No transactions found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($transactions as $tx): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($tx['created_at']))); ?></td>
                        <td><?php echo htmlspecialchars($tx['email']); ?></td>
                        <td><?php echo htmlspecialchars($tx['service']); ?></td>
                        <td><?php echo htmlspecialchars($tx['description']); ?></td>
                        <td><?php echo htmlspecialchars(number_format($tx['amount'], 2)); ?></td>
                        <td><span class="badge badge-<?php echo htmlspecialchars($tx['status']); ?>"><?php echo htmlspecialchars(ucfirst($tx['status'])); ?></span></td>
                        <td><?php echo htmlspecialchars($tx['reference']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.search-bar { margin-bottom: 20px; }
.search-bar input { padding: 10px; width: 300px; }
.search-bar button { padding: 10px; }
</style>

<?php include 'footer.php'; ?>
