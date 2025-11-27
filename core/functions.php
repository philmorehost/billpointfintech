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

// You can add other global helper functions here in the future.
