<?php
require_once 'config.php';

/**
 * Connect to the database
 *
 * @return PDO
 */
function db_connect() {
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: ". $e->getMessage());
    }
}

/**
 * Debit a user's wallet
 *
 * @param int $user_id
 * @param float $amount
 * @return bool
 */
function debit_wallet($user_id, $amount) {
    $pdo = db_connect();

    // Start a transaction
    $pdo->beginTransaction();

    try {
        // Check if the user has sufficient balance
        $stmt = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user || $user['wallet_balance'] < $amount) {
            $pdo->rollBack();
            return false; // Insufficient funds or user not found
        }

        // Debit the wallet
        $new_balance = $user['wallet_balance'] - $amount;
        $stmt = $pdo->prepare('UPDATE users SET wallet_balance = ? WHERE id = ?');
        $stmt->execute([$new_balance, $user_id]);

        // Commit the transaction
        $pdo->commit();

        return true;
    } catch (Exception $e) {
        // An error occurred, rollback the transaction
        $pdo->rollBack();
        // In a real app, you'd log the error
        error_log('Wallet debit failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Credit a user's wallet
 *
 * @param int $user_id
 * @param float $amount
 * @return bool
 */
function credit_wallet($user_id, $amount) {
    $pdo = db_connect();
    try {
        $stmt = $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?');
        return $stmt->execute([$amount, $user_id]);
    } catch (Exception $e) {
        error_log('Wallet credit failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create a new transaction record
 *
 * @param int $user_id
 * @param string $service
 * @param string $description
 * @param float $amount
 * @return int|false The new transaction ID or false on failure
 */
function create_transaction($user_id, $service, $description, $amount) {
    $pdo = db_connect();
    try {
        $stmt = $pdo->prepare('INSERT INTO transactions (user_id, service, description, amount) VALUES (?, ?, ?, ?)');
        $stmt->execute([$user_id, $service, $description, $amount]);
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
        $stmt = $pdo->prepare('UPDATE transactions SET status = ?, reference = ?, api_response = ? WHERE id = ?');
        return $stmt->execute([$status, $reference, $api_response, $transaction_id]);
    } catch (Exception $e) {
        error_log('Transaction update failed: ' . $e->getMessage());
        return false;
    }
}
