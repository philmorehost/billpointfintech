<?php
// cron/query_pending_transactions.php
// This script should be run by a cron job (e.g., every 5 minutes)

// Set a long execution time as this might process many transactions
set_time_limit(300);

// Prevent this script from being run from a web browser
if (php_sapi_name() !== 'cli') {
    die('This script can only be executed from the command line.');
}

// Adjust the path to the core files based on the cron job's execution context
require_once dirname(__DIR__) . '/core/config.php';
require_once dirname(__DIR__) . '/core/functions.php';
require_once dirname(__DIR__) . '/core/vtu_api.php';

$pdo = db_connect();

echo "Starting pending transaction query process...\n";

try {
    // Fetch pending transactions created in the last 24 hours to avoid querying very old, stuck transactions
    $stmt = $pdo->prepare("SELECT id, user_id, amount, reference, api_response FROM transactions WHERE status = 'pending' AND service = 'Airtime' AND created_at >= NOW() - INTERVAL 1 DAY");
    $stmt->execute();
    $pending_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($pending_transactions) === 0) {
        echo "No pending airtime transactions to query.\n";
        exit;
    }

    echo "Found " . count($pending_transactions) . " pending transaction(s) to check.\n";

    foreach ($pending_transactions as $transaction) {
        $transaction_id = $transaction['id'];
        $api_ref = $transaction['reference']; // The API reference from the initial call

        // If for some reason we don't have an API reference, we can't query it.
        if (empty($api_ref)) {
            // Mark as failed to avoid it being stuck in pending forever.
            update_transaction_status($transaction_id, 'failed', null, 'Missing API reference for query.');
            echo " - Transaction #{$transaction_id} failed: Missing API reference.\n";
            continue;
        }

        echo " - Querying status for transaction #{$transaction_id} with ref: {$api_ref}...\n";

        // Call the API function to query the transaction status
        $response = query_transaction_status($api_ref);

        if (is_array($response) && isset($response['status'])) {
            if ($response['status'] === 'success') {
                // The transaction is now confirmed as successful
                update_transaction_status($transaction_id, 'success', $api_ref, json_encode($response));
                echo "   - Status: SUCCESS. Updated record.\n";
            } elseif (in_array($response['status'], ['failed', 'error', 'cancelled'])) {
                // The transaction has definitively failed, refund the user
                update_transaction_status($transaction_id, 'failed', $api_ref, json_encode($response));

                // Important: Credit the user's wallet back
                credit_wallet($transaction['user_id'], $transaction['amount']);

                echo "   - Status: FAILED. Updated record and refunded user.\n";
            } else {
                // The transaction is still pending or has an unknown status from the API
                echo "   - Status: Still Pending or Unknown. No change made.\n";
            }
        } else {
            // The API query itself failed (e.g., network error)
            echo "   - API query failed. Could not get status. Will retry on next run.\n";
        }
    }

    echo "Process finished.\n";

} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage() . "\n";
    // Optionally, send an email to the admin on critical failure
}
?>
