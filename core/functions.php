<?php
// core/functions.php
// ... (db_connect, debit_wallet, credit_wallet functions remain the same)

/**
 * Create a new transaction record
 *
 * @param int $user_id
 * @param string $service
 * @param string $description
 * @param float $amount
 * @param string $status
 * @param string|null $reference
 * @param string|null $api_response
 * @param string|null $recipient
 * @return int|false The new transaction ID or false on failure
 */
function create_transaction($user_id, $service, $description, $amount, $status = 'pending', $reference = null, $api_response = null, $recipient = null) {
    $pdo = db_connect();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO transactions (user_id, service, description, amount, status, reference, api_response, recipient)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$user_id, $service, $description, $amount, $status, $reference, $api_response, $recipient]);
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log('Transaction creation failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update the status of a transaction
 *
 * @param int $transaction_id
 * @param string $status
 * @param string|null $reference
 * @param string|null $api_response
 * @return bool
 */
function update_transaction_status($transaction_id, $status, $reference = null, $api_response = null) {
    $pdo = db_connect();
    try {
        $stmt = $pdo->prepare('UPDATE transactions SET status = ?, reference = ?, api_response = ?, updated_at = NOW() WHERE id = ?');
        return $stmt->execute([$status, $reference, $api_response, $transaction_id]);
    } catch (Exception $e) {
        error_log('Transaction update failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check if a security PIN is required for a sensitive action.
 *
 * @param PDO $pdo The database connection
 * @param int $user_id The ID of the user
 * @return bool True if a PIN is required, false otherwise
 */
function is_pin_required($pdo, $user_id) {
    // 48 hours = 172800 seconds
    $inaction_threshold = 172800;

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) < $inaction_threshold) {
        // User has been active recently
        return false;
    }

    // Check if the user has a PIN set up
    $stmt = $pdo->prepare("SELECT security_pin FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn()) {
        // User has a PIN and has been inactive for too long
        return true;
    }

    // User has no PIN set, so we can't ask for one
    return false;
}
