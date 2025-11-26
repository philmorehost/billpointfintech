<?php
$page_title = 'My Invoices';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
include 'includes/header.php';

// Fetch user's invoices
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$invoices = $stmt->fetchAll();
?>
<div class="container">
    <div class="page-header">
        <h1>My Invoices</h1>
    </div>

    <div class="content-box">
        <?php display_flash_message(); ?>
        <?php if (empty($invoices)): ?>
            <p>You have not created any invoices yet. You can <a href="estimates.php">create an estimate</a> and convert it.</p>
        <?php else: ?>
            <table class="support-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($invoice['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                            <td>₦<?php echo number_format($invoice['total_amount'], 2); ?></td>
                            <td><span class="status-<?php echo strtolower($invoice['status']); ?>"><?php echo ucfirst($invoice['status']); ?></span></td>
                            <td>
                                <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<style>
.status-unpaid { background-color: #ffc107; color: black; }
.status-paid { background-color: #28a745; color: white; }
.status-cancelled { background-color: #6c757d; color: white; }
</style>
