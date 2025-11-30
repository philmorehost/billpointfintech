<?php

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'billpoint');
define('DB_USER', 'root');
define('DB_PASS', '');

// Global settings array with fallbacks
$GLOBALS['app_settings'] = [
    'site_name' => 'Billpoint',
    'paystack_secret_key' => 'sk_test_placeholder',
    'paystack_public_key' => 'pk_test_placeholder',
    'vtu_api_key' => '',
];

// Attempt to connect to the database to load and merge settings
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT * FROM settings");
    $db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Merge database settings over the fallbacks
    $GLOBALS['app_settings'] = array_merge($GLOBALS['app_settings'], $db_settings);

} catch (PDOException $e) {
    // DB connection failed, the initial fallback settings will be used.
}

// Define constants from the final settings array
define('SITE_NAME', $GLOBALS['app_settings']['site_name']);
define('PAYSTACK_SECRET_KEY', $GLOBALS['app_settings']['paystack_secret_key']);
define('PAYSTACK_PUBLIC_KEY', $GLOBALS['app_settings']['paystack_public_key']);
define('VTU_API_KEY', $GLOBALS['app_settings']['vtu_api_key']);

// Site URL
define('SITE_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . str_replace(['install.php', 'index.php'], '', $_SERVER['PHP_SELF']));

// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
