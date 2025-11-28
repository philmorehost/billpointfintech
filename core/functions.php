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

// You can add other global helper functions here in the future.
