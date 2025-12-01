<?php
// cron/process_repayments.php
// This script should be run by a cron job (e.g., once daily)

ini_set('max_execution_time', 300);
require_once __DIR__ . '/../includes/bootstrap.php';

echo "Starting loan repayment processing...\n";

try {
    // Select all active loans where the repayment date is in the past
    $stmt = $pdo->query("
        SELECT l.*, w.balance as wallet_balance
        FROM loans l
        JOIN wallets w ON l.user_id = w.user_id
        WHERE l.status = 'active'
        AND w.currency = 'NGN'
        AND l.repayment_due_date <= NOW()
    ");
    $loans_to_process = $stmt->fetchAll();

    if (empty($loans_to_process)) {
        echo "No loans are due for repayment.\n";
        exit();
    }

    foreach ($loans_to_process as $loan) {
        echo "Processing repayment for loan #{$loan['id']} for user #{$loan['user_id']}...\n";
        $loan_id = $loan['id'];
        $user_id = $loan['user_id'];
        $total_due = (float)$loan['amount_approved'] * (1 + ((float)$loan['interest_rate'] / 100));
        $amount_to_repay = $total_due - (float)$loan['amount_repaid'];

        try {
            $pdo->beginTransaction();

            if ($loan['wallet_balance'] >= $amount_to_repay) {
                // Sufficient funds for full repayment
                // 1. Debit wallet
                $debit_stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency = 'NGN'");
                $debit_stmt->execute([$amount_to_repay, $user_id]);

                // 2. Update loan record
                $loan_update_stmt = $pdo->prepare("UPDATE loans SET amount_repaid = amount_repaid + ?, status = 'repaid' WHERE id = ?");
                $loan_update_stmt->execute([$amount_to_repay, $loan_id]);

                // 3. Log repayment
                $log_repayment_stmt = $pdo->prepare("INSERT INTO loan_repayments (loan_id, user_id, amount, status) VALUES (?, ?, ?, 'successful')");
                $log_repayment_stmt->execute([$loan_id, $user_id, $amount_to_repay]);

                // 4. Log in main transactions
                $log_main_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status, description) VALUES (?, ?, ?, 'NGN', 'completed', ?)");
                $log_main_stmt->execute([$user_id, 'loan_repayment', $amount_to_repay, "Full repayment for Loan #{$loan_id}"]);

                echo " -> Success: Full repayment of {$amount_to_repay} processed.\n";

            } else {
                // Insufficient funds for full repayment
                // Log failed attempt and mark as defaulted
                $loan_update_stmt = $pdo->prepare("UPDATE loans SET status = 'defaulted' WHERE id = ?");
                $loan_update_stmt->execute([$loan_id]);

                $log_repayment_stmt = $pdo->prepare("INSERT INTO loan_repayments (loan_id, user_id, amount, status) VALUES (?, ?, ?, 'failed')");
                $log_repayment_stmt->execute([$loan_id, $user_id, $amount_to_repay]);

                echo " -> Failed: Insufficient funds. Loan marked as defaulted.\n";
            }

            $pdo->commit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("Failed to process repayment for loan ID {$loan_id}: " . $e->getMessage());
            echo " -> Error: " . $e->getMessage() . "\n";
        }
    }

} catch (Exception $e) {
    error_log("General loan repayment processing error: " . $e->getMessage());
    echo "An unexpected error occurred: " . $e->getMessage() . "\n";
}

echo "Loan repayment processing finished.\n";
