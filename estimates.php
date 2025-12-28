<?php
$page_title = 'My Estimates';
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
$csrf_token = generate_csrf_token();
include 'includes/header.php';

// Fetch user's estimates
$stmt = $pdo->prepare("SELECT * FROM estimates WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$estimates = $stmt->fetchAll();
?>
<div class="container">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h1>My Estimates</h1>
        <a href="create_estimate.php" class="btn">Create New Estimate</a>
    </div>

    <div class="content-box">
        <?php display_flash_message(); ?>
        <?php if (empty($estimates)): ?>
            <p>You have not created any estimates yet.</p>
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
                    <?php foreach ($estimates as $estimate): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($estimate['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($estimate['customer_name']); ?></td>
                            <td>₦<?php echo number_format($estimate['total_amount'], 2); ?></td>
                            <td><span class="status-<?php echo strtolower($estimate['status']); ?>"><?php echo ucfirst($estimate['status']); ?></span></td>
                            <td>
                                <!-- Add view/edit links later -->
                                <form action="invoice_handler.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="convert_to_invoice">
                                    <input type="hidden" name="estimate_id" value="<?php echo $estimate['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <button type="submit" class="btn btn-sm">Convert to Invoice</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
