<?php
$page_title = 'Admin - P2P Transfers';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../includes/bootstrap.php';
$csrf_token = generate_csrf_token();

// Fetch pending transfers with user details
$stmt = $pdo->query("
    SELECT
        p.*,
        sender.full_name as sender_name,
        recipient.full_name as recipient_name
    FROM p2p_transfers p
    JOIN users sender ON p.sender_id = sender.id
    JOIN users recipient ON p.recipient_id = recipient.id
    WHERE p.status = 'pending'
    ORDER BY p.created_at DESC
");
$pending_transfers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <h1>Admin - P2P Transfer Requests</h1>
    <a href="index.php">Dashboard</a> | <a href="../logout.php">Logout</a>

    <div class="admin-container">
        <h2>Pending Transfers</h2>
        <?php display_flash_message(); ?>

        <?php if (empty($pending_transfers)): ?>
            <p>There are no pending P2P transfers.</p>
        <?php else: ?>
            <table class="support-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Sender</th>
                        <th>Recipient</th>
                        <th>Amount (NGN)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_transfers as $transfer): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($transfer['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($transfer['sender_name']); ?></td>
                            <td><?php echo htmlspecialchars($transfer['recipient_name']); ?></td>
                            <td><?php echo number_format($transfer['amount'], 2); ?></td>
                            <td>
                                <form action="p2p_handler.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="approve_p2p">
                                    <input type="hidden" name="transfer_id" value="<?php echo $transfer['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                </form>
                                <form action="p2p_handler.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="reject_p2p">
                                    <input type="hidden" name="transfer_id" value="<?php echo $transfer['id']; ?>">
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
