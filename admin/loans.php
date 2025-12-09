<?php
$page_title = 'Admin - Loan Management';
require_once '../includes/bootstrap.php';
require_once '../includes/auth_check.php';

if (!is_admin()) {
    redirect('/dashboard.php');
}

$stmt = $pdo->query("
    SELECT l.*, u.full_name, u.email
    FROM loans l
    JOIN users u ON l.user_id = u.id
    WHERE l.status = 'pending'
    ORDER BY l.created_at DESC
");
$pending_loans = $stmt->fetchAll();

include '../includes/header.php';
?>
<div class="container mt-4">
    <h1>Loan Application Management</h1>
    <p>Review and approve or deny pending loan requests.</p>

    <?php display_flash_message(); ?>

    <div class="content-box">
        <?php if (empty($pending_loans)): ?>
            <div class="alert alert-info">There are no pending loan applications.</div>
        <?php else: ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Amount Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_loans as $loan): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($loan['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($loan['full_name']); ?> (<?php echo htmlspecialchars($loan['email']); ?>)</td>
                            <td>₦<?php echo number_format($loan['amount_requested'], 2); ?></td>
                            <td>
                                <form action="loan_handler.php" method="POST" style="display:inline;">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="approve_loan">
                                    <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                </form>
                                <form action="loan_handler.php" method="POST" style="display:inline;">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="reject_loan">
                                    <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
