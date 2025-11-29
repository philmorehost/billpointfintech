<?php
// core/functions.php

/**
 * Establishes a database connection using PDO.
 *
 * This function uses the database credentials defined in core/config.php.
 *
 * @return PDO The PDO database connection object.
 */
function db_connect() {
    // These constants (DB_HOST, DB_NAME, DB_USER, DB_PASS) must be defined
    // in core/config.php before this function is called.
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        // For a production environment, you would log this error and
        // display a generic error page instead of die().
        error_log("Database Connection Error: " . $e->getMessage());
        die("A database error occurred. Please try again later.");
    }
}

/**
 * Calculates the final price of an item after applying a service discount.
 *
 * @param PDO $pdo The database connection object.
 * @param string $service_slug The unique slug for the service (e.g., 'airtime').
 * @param float $original_price The original price of the item.
 * @return float The price after the discount has been applied.
 */
function calculate_discounted_price($pdo, $service_slug, $original_price) {
    $stmt = $pdo->prepare("SELECT discount_percentage FROM services WHERE slug = ? AND is_available = 1");
    $stmt->execute([$service_slug]);
    $service = $stmt->fetch();

    if ($service && $service['discount_percentage'] > 0) {
        $discount_factor = (100 - $service['discount_percentage']) / 100;
        return round($original_price * $discount_factor, 2);
    }

    return $original_price;
}

/**
 * A simple database migration utility.
 *
 * This function checks for missing columns and adds them, ensuring the database
 * schema is up-to-date with the application code.
 *
 * @param PDO $pdo The database connection object.
 */
function run_database_migrations($pdo) {
    // Migration 1: Add 'discount_percentage' to 'services' table
    try {
        // Check if the column exists. This is more robust than just running the query.
        $result = $pdo->query("SHOW COLUMNS FROM `services` LIKE 'discount_percentage'");
        if ($result->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `services` ADD `discount_percentage` DECIMAL(5,2) NOT NULL DEFAULT '0.00' AFTER `is_available`");
        }
    } catch (PDOException $e) {
        // If the table doesn't exist yet (e.g., during installation), we can safely ignore this.
        if (strpos($e->getMessage(), "exist") === false) {
            // For other errors, it's better to log or die
            error_log("Migration Error: " . $e->getMessage());
        }
    }
}

/**
 * Creates a new transaction record in the database.
 *
 * @param int $user_id The ID of the user performing the transaction.
 * @param string $service The name of the service (e.g., 'Data', 'Airtime').
 * @param string $description A detailed description of the transaction.
 * @param float $amount The amount of the transaction.
 * @return int|false The ID of the newly created transaction, or false on failure.
 */
function create_transaction($user_id, $service, $description, $amount) {
    $pdo = db_connect();
    try {
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, service, description, amount, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $service, $description, $amount]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Transaction Creation Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Awards bonus points to a user.
 *
 * @param PDO $pdo The database connection object.
 * @param int $user_id The ID of the user.
 * @param string $reason The reason for the bonus (e.g., 'login', 'transaction').
 * @param string $description A detailed description for the bonus transaction log.
 * @return bool True on success, false on failure.
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
    return false; // No points configured for this reason
}

/**
 * Debits (subtracts) a specified amount from a user's wallet.
 *
 * @param int $user_id The ID of the user.
 * @param float $amount The amount to debit.
 * @return bool True on success, false on failure (e.g., insufficient funds).
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
 *
 * @param int $user_id The ID of the user.
 * @param float $amount The amount to credit.
 * @return bool True on success, false on failure.
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
 * @param string|null $reference The external API reference for the transaction.
 * @param string|null $api_response The full API response to log.
 * @return bool True on success, false on failure.
 */
function update_transaction_status($transaction_id, $status, $reference, $api_response) {
    $pdo = db_connect();
    try {
        $stmt = $pdo->prepare("UPDATE transactions SET status = ?, reference = ?, api_response = ? WHERE id = ?");
        return $stmt->execute([$status, $reference, $api_response, $transaction_id]);
    } catch (PDOException $e) {
        error_log("Update Transaction Error: " . $e->getMessage());
        return false;
    }
}
