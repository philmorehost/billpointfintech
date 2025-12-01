<?php
$page_title = 'Admin - Loan Management';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../includes/bootstrap.php';
$csrf_token = generate_csrf_token();

// Fetch pending loans with user details
$stmt = $pdo->query("
    SELECT l.*, u.full_name, u.email
    FROM loans l
    JOIN users u ON l.user_id = u.id
    WHERE l.status = 'pending'
    ORDER BY l.created_at DESC
");
$pending_loans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <h1>Admin - Loan Applications</h1>
    <a href="index.php">Dashboard</a> | <a href="../logout.php">Logout</a>

    <div class="admin-container">
        <h2>Pending Loan Requests</h2>
        <?php display_flash_message(); ?>

        <?php if (empty($pending_loans)): ?>
            <p>There are no pending loan applications.</p>
        <?php else: ?>
            <table class="support-table">
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
                                    <input type="hidden" name="action" value="approve_loan">
                                    <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                </form>
                                <form action="loan_handler.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="reject_loan">
                                    <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
