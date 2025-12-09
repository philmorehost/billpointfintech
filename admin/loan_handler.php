<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
require_once '../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
        header('Location: loans.php');
        exit();
    }

    $action = $_POST['action'];
    $loan_id = (int)$_POST['loan_id'];

    if ($action === 'approve_loan') {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT * FROM loans WHERE id = ? AND status = 'pending' FOR UPDATE");
            $stmt->execute([$loan_id]);
            $loan = $stmt->fetch();

            if (!$loan) {
                throw new Exception("Loan not found or already processed.");
            }

            $loan_duration_days = $config['settings']['loan_duration_days'] ?? 30;
            $amount_approved = (float)$loan['amount_requested']; // Approve the requested amount for now
            $due_date = date('Y-m-d H:i:s', strtotime("+{$loan_duration_days} days"));

            // 1. Credit user's wallet
            $credit_stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = 'NGN'");
            $credit_stmt->execute([$amount_approved, $loan['user_id']]);

            // 2. Update loan status
            $update_stmt = $pdo->prepare("UPDATE loans SET status = 'active', amount_approved = ?, approved_at = NOW(), repayment_due_date = ? WHERE id = ?");
            $update_stmt->execute([$amount_approved, $due_date, $loan_id]);

            // 3. Log transactions
            $log_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status, description) VALUES (?, ?, ?, 'NGN', 'completed', ?)");
            $log_stmt->execute([$loan['user_id'], 'loan_disbursement', $amount_approved, "Loan #{$loan_id} disbursed"]);

            $pdo->commit();
            set_flash_message('success', 'Loan approved and funds disbursed.');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            set_flash_message('error', 'Failed to approve loan: ' . $e->getMessage());
        }

    } elseif ($action === 'reject_loan') {
        $stmt = $pdo->prepare("UPDATE loans SET status = 'rejected' WHERE id = ? AND status = 'pending'");
        if ($stmt->execute([$loan_id])) {
            set_flash_message('success', 'Loan application rejected.');
        } else {
            set_flash_message('error', 'Failed to reject loan application.');
        }
    }

    header('Location: loans.php');
    exit();
}
