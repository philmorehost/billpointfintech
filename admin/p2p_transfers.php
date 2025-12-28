<?php
$page_title = 'Admin - P2P Transfers';
require_once '../includes/admin_header.php'; // Use the new admin header

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

<div class="admin-header">
    <h1>P2P Transfer Requests</h1>
    <p>Approve or reject pending user-to-user transfers.</p>
</div>

<?php display_flash_message(); ?>

<div class="content-box">
    <?php if (empty($pending_transfers)): ?>
        <div class="alert alert-info">There are no pending P2P transfers.</div>
    <?php else: ?>
        <table class="table">
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
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="approve_p2p">
                                <input type="hidden" name="transfer_id" value="<?php echo $transfer['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                            </form>
                            <form action="p2p_handler.php" method="POST" style="display:inline;">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="reject_p2p">
                                <input type="hidden" name="transfer_id" value="<?php echo $transfer['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
