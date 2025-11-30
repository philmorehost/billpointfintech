<?php
// core/functions.php

/**
 * Establishes a database connection using PDO.
 */
function db_connect() {
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        die("A database error occurred. Please try again later.");
    }
}


/**
 * Creates a new transaction record in the database.
 *
 * @param int $user_id The ID of the user performing the transaction.
 * @param string $service The name of the service (e.g., 'Data', 'Airtime').
 * @param string $description A detailed description of the transaction.
 * @param float $amount The amount of the transaction.
 * @param string $status The initial status of the transaction.
 * @param string|null $api_ref The external API reference, if available.
 * @param string|null $api_response The full API response, if available.
 * @param string|null $recipient The recipient identifier (e.g., phone number).
 * @return int|false The ID of the newly created transaction, or false on failure.
 */
function create_transaction($user_id, $service, $description, $amount, $status = 'pending', $api_ref = null, $api_response = null, $recipient = null) {
    $pdo = db_connect();
    try {
        // Generate a unique reference number for our system
        $reference = 'BP-' . strtoupper(substr($service, 0, 3)) . '-' . time() . '-' . mt_rand(1000, 9999);

        $sql = "INSERT INTO transactions (user_id, service, description, amount, status, reference, api_response, recipient, api_reference)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $user_id,
            $service,
            $description,
            $amount,
            $status,
            $reference, // Our internal reference
            $api_response,
            $recipient,
            $api_ref // The reference from the external API
        ]);

        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Transaction Creation Error: " . $e->getMessage());
        return false;
    }
}


/**
 * Awards bonus points to a user.
 */
function award_bonus($pdo, $user_id, $reason, $description) {
    $setting_key = "bonus_on_{$reason}";
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$setting_key]);
    $points_to_award = $stmt->fetchColumn();

    if ($points_to_award && $points_to_award > 0) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE users SET bonus_balance = bonus_balance + ? WHERE id = ?");
            $stmt->execute([$points_to_award, $user_id]);

            $stmt = $pdo->prepare("INSERT INTO bonus_transactions (user_id, type, amount, description) VALUES (?, 'earn', ?, ?)");
            $stmt->execute([$user_id, $points_to_award, $description]);
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Award Bonus Error: " . $e->getMessage());
            return false;
        }
    }
    return false;
}

/**
 * Debits (subtracts) a specified amount from a user's wallet.
 */
function debit_wallet($user_id, $amount) {
    $pdo = db_connect();
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && $user['wallet_balance'] >= $amount) {
            $new_balance = $user['wallet_balance'] - $amount;
            $stmt = $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
            $stmt->execute([$new_balance, $user_id]);
            $pdo->commit();
            return true;
        } else {
            $pdo->rollBack();
            return false;
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Debit Wallet Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Credits (adds) a specified amount to a user's wallet.
 */
function credit_wallet($user_id, $amount) {
    $pdo = db_connect();
    try {
        $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
        return $stmt->execute([$amount, $user_id]);
    } catch (PDOException $e) {
        error_log("Credit Wallet Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates the status and details of an existing transaction.
 *
 * @param int $transaction_id The ID of the transaction to update.
 * @param string $status The new status ('success' or 'failed').
 * @param string|null $api_ref The external API reference for the transaction.
 * @param string|null $api_response The full API response to log.
 * @return bool True on success, false on failure.
 */
function update_transaction_status($transaction_id, $status, $api_ref = null, $api_response = null) {
    $pdo = db_connect();
    try {
        // Prepare the SQL statement, only updating fields that are provided
        $sql = "UPDATE transactions SET status = ?";
        $params = [$status];

        if ($api_ref !== null) {
            $sql .= ", api_reference = ?";
            $params[] = $api_ref;
        }
        if ($api_response !== null) {
            $sql .= ", api_response = ?";
            $params[] = $api_response;
        }

        $sql .= " WHERE id = ?";
        $params[] = $transaction_id;

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Update Transaction Error: " . $e->getMessage());
        return false;
    }
}
