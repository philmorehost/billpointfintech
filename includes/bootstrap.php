<?php
session_start();

require_once 'database.php';
require_once 'flash_messages.php';
require_once 'functions.php';

// --- Application Configuration ---
$settings_cache_file = __DIR__ . '/../cache/settings.json';
if (file_exists($settings_cache_file) && (time() - filemtime($settings_cache_file) < 3600)) { // 1 hour cache
    $settings = json_decode(file_get_contents($settings_cache_file), true);
} else {
    $settings_stmt = $pdo->query("SELECT name, value FROM settings");
    $settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    file_put_contents($settings_cache_file, json_encode($settings));
}

$config = [
    'wallet_currencies' => ['NGN', 'USD', 'CAD', 'USDT', 'USDC'],
    'settings' => $settings,
    'services' => [
        'airtime_networks' => ['mtn' => 'MTN', 'glo' => 'Glo', 'airtel' => 'Airtel', '9mobile' => '9mobile'],
        'cable_providers' => ['dstv' => 'DSTV', 'gotv' => 'GOTV', 'startimes' => 'Startimes']
    ]
];

require_once 'csrf.php';
