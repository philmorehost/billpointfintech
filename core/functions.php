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

// You can add other global helper functions here in the future.
