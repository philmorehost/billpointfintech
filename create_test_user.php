<?php
require_once 'includes/bootstrap.php';

$email = 'testuser@example.com';
$password = 'password123';
$pin = '1234';
$full_name = 'Test User';
$phone = '08012345678';

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$hashed_pin = password_hash($pin, PASSWORD_DEFAULT);

try {
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo "Test user already exists.\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, pin, role) VALUES (?, ?, ?, ?, ?, 'user')");
        $stmt->execute([$full_name, $email, $phone, $hashed_password, $hashed_pin]);
        $user_id = $pdo->lastInsertId();

        // Create wallets for the new user
        foreach ($config['wallet_currencies'] as $currency) {
            $wallet_stmt = $pdo->prepare("INSERT INTO wallets (user_id, currency, balance) VALUES (?, ?, ?)");
            // Give some initial balance for testing if needed
            $initial_balance = ($currency === 'NGN') ? 5000 : 100;
            $wallet_stmt->execute([$user_id, $currency, $initial_balance]);
        }

        echo "Test user 'testuser@example.com' with password 'password123' created successfully.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
