<?php
// cron/process_savings.php
// This script should be run by a cron job (e.g., once every hour or day)

// Set a long execution time
ini_set('max_execution_time', 300);

// bootstrap.php in the parent directory
require_once __DIR__ . '/../includes/bootstrap.php';

echo "Starting savings processing...\n";

try {
    // Select all active savings goals where the next deduction date is in the past
    $stmt = $pdo->query("
        SELECT sg.*, w.balance as wallet_balance
        FROM savings_goals sg
        JOIN wallets w ON sg.user_id = w.user_id
        WHERE sg.status = 'active'
        AND w.currency = 'NGN'
        AND sg.next_deduction_at <= NOW()
    ");

    $goals_to_process = $stmt->fetchAll();

    if (empty($goals_to_process)) {
        echo "No savings goals are due for processing.\n";
        exit();
    }

    foreach ($goals_to_process as $goal) {
        echo "Processing goal #{$goal['id']} for user #{$goal['user_id']}...\n";

        try {
            $pdo->beginTransaction();

            $user_id = $goal['user_id'];
            $saving_amount = (float)$goal['saving_amount'];
            $goal_id = $goal['id'];

            if ($goal['wallet_balance'] >= $saving_amount) {
                // Sufficient funds, proceed with deduction

                // 1. Debit main wallet
                $debit_stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency = 'NGN'");
                $debit_stmt->execute([$saving_amount, $user_id]);

                // 2. Credit savings goal
                $credit_stmt = $pdo->prepare("UPDATE savings_goals SET current_amount = current_amount + ? WHERE id = ?");
                $credit_stmt->execute([$saving_amount, $goal_id]);

                // 3. Log in savings_transactions
                $log_savings_stmt = $pdo->prepare("INSERT INTO savings_transactions (savings_goal_id, user_id, amount, type) VALUES (?, ?, ?, 'deposit')");
                $log_savings_stmt->execute([$goal_id, $user_id, $saving_amount]);

                // 4. Log in main transactions
                $log_main_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status, description) VALUES (?, ?, ?, 'NGN', 'completed', ?)");
                $desc = "Automatic savings deposit to goal: " . $goal['goal_name'];
                $log_main_stmt->execute([$user_id, 'savings_debit', $saving_amount, $desc]);

                echo " -> Success: Debited {$saving_amount} from main wallet.\n";

            } else {
                // Insufficient funds
                echo " -> Skipped: Insufficient funds in main wallet.\n";
                // Optional: Notify user about the missed payment
            }

            // 5. Update next deduction date, regardless of success
            $interval = $goal['saving_frequency'] === 'daily' ? 'P1D' : 'P1W';
            $next_deduction = (new DateTime())->add(new DateInterval($interval))->format('Y-m-d H:i:s');
            $update_next_date_stmt = $pdo->prepare("UPDATE savings_goals SET next_deduction_at = ? WHERE id = ?");
            $update_next_date_stmt->execute([$next_deduction, $goal_id]);

            $pdo->commit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Failed to process savings for goal ID {$goal['id']}: " . $e->getMessage());
            echo " -> Error: " . $e->getMessage() . "\n";
        }
    }

} catch (Exception $e) {
    error_log("General savings processing error: " . $e->getMessage());
    echo "An unexpected error occurred: " . $e->getMessage() . "\n";
}

echo "Savings processing finished.\n";
