<?php
require_once 'includes/bootstrap.php';

$email = "user@example.com";
$password = "password";
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$hashed_pin = password_hash("1234", PASSWORD_DEFAULT);

// Check if user already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // User exists, just update their KYC status
    $stmt = $pdo->prepare("UPDATE users SET bvn = ?, kyc_verified_at = NOW() WHERE id = ?");
    $stmt->execute(['12345678901', $user['id']]);
    echo "User KYC status updated.";
} else {
    // Create the user
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, pin, bvn, kyc_verified_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute(["Test User", $email, "1234567890", $hashed_password, $hashed_pin, '12345678901']);
    $user_id = $pdo->lastInsertId();

    // Create wallets for the user
    foreach ($config['wallet_currencies'] as $currency) {
        $wallet_stmt = $pdo->prepare("INSERT INTO wallets (user_id, currency) VALUES (?, ?)");
        $wallet_stmt->execute([$user_id, $currency]);
    }
    echo "User 'user@example.com' created and KYC'd successfully.";
}
