<?php
include 'header.php';

$pdo = db_connect();
$feedback = ['message' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $notification_id = $_POST['notification_id'] ?? null;
    $user_id = $_POST['user_id'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $action = $_POST['action'];

    if ($notification_id && $user_id && $amount) {
        try {
            $pdo->beginTransaction();

            if ($action === 'approve') {
                // Credit the user's wallet
                credit_wallet($user_id, $amount);

                // Update the notification status
                $stmt = $pdo->prepare("UPDATE deposit_notifications SET status = 'approved', reviewed_at = NOW() WHERE id = ?");
                $stmt->execute([$notification_id]);

                // Find and update the corresponding transaction
                $description_like = "Manual deposit notification. Ref: " . $notification_id;
                $stmt = $pdo->prepare("UPDATE transactions SET status = 'success' WHERE user_id = ? AND description = ? AND status = 'pending'");
                $stmt->execute([$user_id, $description_like]);

                $feedback = ['message' => 'Deposit approved and user wallet has been credited.', 'type' => 'success'];

            } elseif ($action === 'reject') {
                // Update the notification status
                $stmt = $pdo->prepare("UPDATE deposit_notifications SET status = 'rejected', reviewed_at = NOW() WHERE id = ?");
                $stmt->execute([$notification_id]);

                // Find and update the corresponding transaction to 'failed'
                $description_like = "Manual deposit notification. Ref: " . $notification_id;
                $stmt = $pdo->prepare("UPDATE transactions SET status = 'failed' WHERE user_id = ? AND description = ? AND status = 'pending'");
                $stmt->execute([$user_id, $description_like]);

                $feedback = ['message' => 'Deposit notification has been rejected.', 'type' => 'success'];
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $feedback = ['message' => 'An error occurred: ' . $e->getMessage(), 'type' => 'errors'];
        }
    }
}

$stmt = $pdo->query(
    "SELECT dn.*, u.full_name, u.email
     FROM deposit_notifications dn
     JOIN users u ON dn.user_id = u.id
     WHERE dn.status = 'pending'
     ORDER BY dn.created_at ASC"
);
$pending_deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<h2>Pending Manual Deposits</h2>

<?php if ($feedback['message']): ?>
    <div class="<?php echo htmlspecialchars($feedback['type']); ?>"><p><?php echo htmlspecialchars($feedback['message']); ?></p></div>
<?php endif; ?>

<div class="widget">
    <h3>Notifications Awaiting Review</h3>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_deposits as $deposit): ?>
                <tr>
                    <td><?php echo htmlspecialchars($deposit['full_name']); ?><br><small><?php echo htmlspecialchars($deposit['email']); ?></small></td>
                    <td>₦<?php echo htmlspecialchars(number_format($deposit['amount'], 2)); ?></td>
                    <td><?php echo htmlspecialchars($deposit['payment_method']); ?></td>
                    <td><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($deposit['created_at']))); ?></td>
                    <td>
                        <form method="post" style="display:inline-block;" onsubmit="return confirm('Approve this deposit? The user will be credited.');">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="notification_id" value="<?php echo $deposit['id']; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $deposit['user_id']; ?>">
                            <input type="hidden" name="amount" value="<?php echo $deposit['amount']; ?>">
                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                        </form>
                        <form method="post" style="display:inline-block;" onsubmit="return confirm('Reject this deposit?');">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="notification_id" value="<?php echo $deposit['id']; ?>">
                             <input type="hidden" name="user_id" value="<?php echo $deposit['user_id']; ?>">
                            <input type="hidden" name="amount" value="<?php echo $deposit['amount']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
