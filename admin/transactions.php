<?php
require_once 'header.php';

$pdo = db_connect();

// Handle search
$search = $_GET['search'] ?? '';
$sql = "SELECT t.*, u.email FROM transactions t JOIN users u ON t.user_id = u.id";
if (!empty($search)) {
    $sql .= " WHERE u.email LIKE :search OR t.description LIKE :search OR t.service LIKE :search OR t.reference LIKE :search";
}
$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
if (!empty($search)) {
    $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
}
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="container-fluid">
    <h1>All Transactions</h1>
    <p>Here you can view and search for all transactions in the system.</p>

    <form action="transactions.php" method="GET" class="form-inline mb-3">
        <input type="text" name="search" class="form-control mr-sm-2" placeholder="Search by email, ref, etc..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <div class="table-wrapper">
        <table class="table table-bordered table-excel">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Service</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Reference</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($transactions) > 0): ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($transaction['id']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['email']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($transaction['service'])); ?></td>
                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                            <td>&#8358;<?php echo htmlspecialchars(number_format($transaction['amount'], 2)); ?></td>
                            <td><span class="badge badge-<?php echo htmlspecialchars($transaction['status']); ?>"><?php echo htmlspecialchars(ucfirst($transaction['status'])); ?></span></td>
                            <td><?php echo htmlspecialchars($transaction['reference']); ?></td>
                            <td><?php echo date("d M, Y g:ia", strtotime($transaction['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No transactions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
require_once 'footer.php';
?>
