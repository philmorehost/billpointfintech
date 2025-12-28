<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
        header('Location: p2p_transfers.php');
        exit();
    }

    $action = $_POST['action'];
    $transfer_id = (int)$_POST['transfer_id'];

    if ($action === 'approve_p2p') {
        try {
            $pdo->beginTransaction();

            // 1. Get transfer details and lock the row
            $stmt = $pdo->prepare("SELECT * FROM p2p_transfers WHERE id = ? AND status = 'pending' FOR UPDATE");
            $stmt->execute([$transfer_id]);
            $transfer = $stmt->fetch();

            if (!$transfer) {
                throw new Exception("Transfer not found or already processed.");
            }

            // 2. Debit sender
            $debit_stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency = 'NGN' AND balance >= ?");
            $debit_stmt->execute([$transfer['amount'], $transfer['sender_id'], $transfer['amount']]);
            if ($debit_stmt->rowCount() === 0) {
                throw new Exception("Sender has insufficient funds.");
            }

            // 3. Credit recipient
            $credit_stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = 'NGN'");
            $credit_stmt->execute([$transfer['amount'], $transfer['recipient_id']]);

            // 4. Update P2P transfer status
            $update_stmt = $pdo->prepare("UPDATE p2p_transfers SET status = 'approved' WHERE id = ?");
            $update_stmt->execute([$transfer_id]);

            // 5. Log transactions
            $log_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status, description) VALUES (?, ?, ?, 'NGN', 'completed', ?)");
            // Log for sender
            $log_stmt->execute([$transfer['sender_id'], 'p2p_debit', $transfer['amount'], "P2P transfer to user ID {$transfer['recipient_id']}"]);
            // Log for recipient
            $log_stmt->execute([$transfer['recipient_id'], 'p2p_credit', $transfer['amount'], "P2P transfer from user ID {$transfer['sender_id']}"]);

            $pdo->commit();
            set_flash_message('success', 'Transfer approved successfully.');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash_message('error', 'Failed to approve transfer: ' . $e->getMessage());
        }

    } elseif ($action === 'reject_p2p') {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT sender_id, recipient_id, amount FROM p2p_transfers WHERE id = ? AND status = 'pending' FOR UPDATE");
            $stmt->execute([$transfer_id]);
            $transfer = $stmt->fetch();

            if (!$transfer) {
                throw new Exception("Transfer not found or already processed.");
            }

            $update_stmt = $pdo->prepare("UPDATE p2p_transfers SET status = 'rejected' WHERE id = ?");
            $update_stmt->execute([$transfer_id]);

            // Log the rejected transaction for the sender
            $log_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status, description) VALUES (?, ?, ?, 'NGN', 'failed', ?)");
            $log_stmt->execute([$transfer['sender_id'], 'p2p_debit', $transfer['amount'], "Rejected P2P transfer to user ID {$transfer['recipient_id']}"]);

            $pdo->commit();
            set_flash_message('success', 'Transfer rejected.');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash_message('error', 'Failed to reject transfer: ' . $e->getMessage());
        }
    }

    header('Location: p2p_transfers.php');
    exit();
}
