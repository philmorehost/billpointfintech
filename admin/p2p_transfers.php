<?php
require_once '../includes/bootstrap.php';
require_once 'includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
        header('Location: p2p_transfers.php');
        exit();
    }
    $transfer_id = (int)$_POST['transfer_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT * FROM p2p_transfers WHERE id = ? AND status = 'pending' FOR UPDATE");
            $stmt->execute([$transfer_id]);
            $transfer = $stmt->fetch();

            if ($transfer) {
                // Debit sender
                $debit_stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency = 'NGN'");
                $debit_stmt->execute([$transfer['amount'], $transfer['sender_id']]);

                // Credit recipient
                $credit_stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = 'NGN'");
                $credit_stmt->execute([$transfer['amount'], $transfer['recipient_id']]);

                // Update transfer status
                $update_stmt = $pdo->prepare("UPDATE p2p_transfers SET status = 'approved' WHERE id = ?");
                $update_stmt->execute([$transfer_id]);

                // Log transactions
                $log_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status, description) VALUES (?, ?, ?, 'NGN', 'completed', ?)");
                $log_stmt->execute([$transfer['sender_id'], 'p2p_debit', $transfer['amount'], 'P2P Transfer to ' . $transfer['recipient_id']]);
                $log_stmt->execute([$transfer['recipient_id'], 'p2p_credit', $transfer['amount'], 'P2P Transfer from ' . $transfer['sender_id']]);

                $pdo->commit();
                set_flash_message('success', 'Transfer approved.');
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Failed to approve transfer: ' . $e->getMessage());
        }
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE p2p_transfers SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$transfer_id]);
        set_flash_message('success', 'Transfer rejected.');
    }
    header("Location: p2p_transfers.php");
    exit();
}

$stmt = $pdo->query("
    SELECT t.id, s.full_name AS sender, r.full_name AS recipient, t.amount, t.status, t.created_at
    FROM p2p_transfers t
    JOIN users s ON t.sender_id = s.id
    JOIN users r ON t.recipient_id = r.id
    ORDER BY t.created_at DESC
");
$transfers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P2P Transfers - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h2>P2P Transfers</h2>
        <table>
            <thead>
                <tr>
                    <th>Sender</th>
                    <th>Recipient</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transfers as $transfer): ?>
                <tr>
                    <td><?php echo htmlspecialchars($transfer['sender']); ?></td>
                    <td><?php echo htmlspecialchars($transfer['recipient']); ?></td>
                    <td><?php echo number_format($transfer['amount'], 2); ?></td>
                    <td><?php echo htmlspecialchars($transfer['status']); ?></td>
                    <td><?php echo $transfer['created_at']; ?></td>
                    <td>
                        <?php if ($transfer['status'] === 'pending'): ?>
                        <form action="p2p_transfers.php" method="post" style="display:inline;">
                            <?php generate_csrf_token(); ?>
                            <input type="hidden" name="transfer_id" value="<?php echo $transfer['id']; ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-sm">Approve</button>
                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
