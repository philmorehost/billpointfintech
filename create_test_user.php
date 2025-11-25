<?php
require_once 'includes/bootstrap.php';

$email = "user@example.com";
$password = "password";
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$hashed_pin = password_hash("1234", PASSWORD_DEFAULT);

// Check if user already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo "User already exists.";
    exit();
}

// Create the user
$stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, pin) VALUES (?, ?, ?, ?, ?)");
$stmt->execute(["Test User", $email, "1234567890", $hashed_password, $hashed_pin]);
$user_id = $pdo->lastInsertId();

// Create wallets for the user
foreach ($config['wallet_currencies'] as $currency) {
    $wallet_stmt = $pdo->prepare("INSERT INTO wallets (user_id, currency) VALUES (?, ?)");
    $wallet_stmt->execute([$user_id, $currency]);
}

echo "User 'user@example.com' created successfully.";
