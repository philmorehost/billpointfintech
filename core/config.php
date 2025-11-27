<?php

// Database configuration - these are still needed for the initial connection
define('DB_HOST', 'localhost');
define('DB_NAME', 'billpoint');
define('DB_USER', 'root');
define('DB_PASS', '');

// Attempt to connect to the database to load settings
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT * FROM settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Define constants from settings
    define('SITE_NAME', $settings['site_name'] ?? 'Billpoint');
    define('PAYSTACK_SECRET_KEY', $settings['paystack_secret_key'] ?? '');
    define('PAYSTACK_PUBLIC_KEY', $settings['paystack_public_key'] ?? '');
    define('VTU_API_KEY', $settings['vtu_api_key'] ?? '');

} catch (PDOException $e) {
    // If DB connection fails (e.g., during install), define fallbacks
    define('SITE_NAME', 'Billpoint');
    define('PAYSTACK_SECRET_KEY', '');
    define('PAYSTACK_PUBLIC_KEY', '');
    define('VTU_API_KEY', '');
}

// Site URL
define('SITE_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . str_replace(['install.php', 'index.php'], '', $_SERVER['PHP_SELF']));

// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
