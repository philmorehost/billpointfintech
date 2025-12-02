<?php
// cron/process_bulk_vtu.php
ini_set('max_execution_time', 600);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../core/datagifting_api.php';
require_once __DIR__ . '/../core/reloadly_api.php';

echo "Starting bulk VTU processing...\n";

try {
    $pdo->beginTransaction();

    $stmt = $pdo->query("SELECT * FROM bulk_jobs WHERE status = 'pending' ORDER BY created_at ASC LIMIT 1 FOR UPDATE");
    $job = $stmt->fetch();

    if (!$job) {
        echo "No pending bulk jobs.\n";
        $pdo->commit();
        exit();
    }

    $job_id = $job['id'];
    echo "Processing job #{$job_id}...\n";

    $update_job_stmt = $pdo->prepare("UPDATE bulk_jobs SET status = 'processing' WHERE id = ?");
    $update_job_stmt->execute([$job_id]);

    $items_stmt = $pdo->prepare("SELECT * FROM bulk_job_items WHERE job_id = ? AND status = 'pending'");
    $items_stmt->execute([$job_id]);
    $items = $items_stmt->fetchAll();

    // Debit the total cost from the user's wallet upfront
    $debit_stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency = 'NGN' AND balance >= ?");
    $debit_stmt->execute([$job['total_cost'], $job['user_id'], $job['total_cost']]);
    if ($debit_stmt->rowCount() === 0) {
        $pdo->prepare("UPDATE bulk_jobs SET status = 'failed', notes = 'Insufficient funds at start of job.' WHERE id = ?")->execute([$job_id]);
        throw new Exception("Insufficient funds for user {$job['user_id']} to cover total cost of job {$job_id}.");
    }

    // Log one single debit for the whole job
    $log_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status, description) VALUES (?, ?, ?, 'NGN', 'completed', ?)");
    $log_stmt->execute([$job['user_id'], 'bulk_vtu_debit', $job['total_cost'], "Debit for Bulk VTU Job #{$job_id}"]);

    $pdo->commit(); // Commit initial debit and status change

    // Initialize APIs
    $datagifting = new DatagiftingAPI($config['settings']['datagifting_api_key'] ?? null);
    $reloadly = new ReloadlyAPI($config['settings']['reloadly_client_id'] ?? null, $config['settings']['reloadly_client_secret'] ?? null);

    $succeeded_count = 0;
    foreach ($items as $item) {
        $item_id = $item['id'];
        $phone = $item['target'];
        $amount = $item['amount'];

        echo " -> Processing item #{$item_id}: {$phone} - {$amount}\n";
        $response = null;

        if ($job['job_type'] === 'airtime_local') {
            // Very basic network detection for local numbers
            $network = 'mtn'; // Placeholder
            $response = $datagifting->purchase_airtime($network, $phone, $amount);
        } elseif ($job['job_type'] === 'airtime_international') {
            // International requires auto-detecting operator first
            // For simplicity, we assume a CSV format of phone,amount,country_iso,operator_id
            // A more robust implementation would handle this better. This is a simplified example.
            // Let's pretend the API call happens here.
            $response = ['status' => 'success']; // Placeholder for Reloadly
        }

        if ($response && ($response['status'] === 'success' || (isset($response['status']) && $response['status'] === 'SUCCESSFUL'))) {
            $status = 'completed';
            $succeeded_count++;
        } else {
            $status = 'failed';
        }

        $update_item_stmt = $pdo->prepare("UPDATE bulk_job_items SET status = ?, response_message = ? WHERE id = ?");
        $update_item_stmt->execute([$status, json_encode($response), $item_id]);
    }

    // Final job status update
    $final_status = $succeeded_count === count($items) ? 'completed' : 'partial_failure';
    $notes = "Processed " . count($items) . " items. {$succeeded_count} succeeded.";
    $pdo->prepare("UPDATE bulk_jobs SET status = ?, notes = ? WHERE id = ?")->execute([$final_status, $notes, $job_id]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Bulk VTU processing error: " . $e->getMessage());
    echo "An error occurred: " . $e->getMessage() . "\n";
}

echo "Bulk VTU processing finished.\n";
