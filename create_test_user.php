<?php
// create_test_user.php
require_once 'includes/bootstrap.php';

$email = 'testuser@example.com';
$password = 'password123';
$full_name = 'Test User';
$phone = '1234567890';
$pin = '1234';

// Hash the password and PIN
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$hashed_pin = password_hash($pin, PASSWORD_DEFAULT);


try {
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo "Test user already exists.\n";
    } else {
        // Insert the new user
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, pin) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $phone, $hashed_password, $hashed_pin]);
        $user_id = $pdo->lastInsertId();
        echo "Test user created successfully with ID: $user_id\n";
    }

    // Create NGN wallet for the test user
    $stmt = $pdo->prepare("SELECT id FROM wallets WHERE user_id = ? AND currency = 'NGN'");
    $stmt->execute([$user_id]);
    if(!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO wallets (user_id, currency, balance, ledger_balance) VALUES (?, 'NGN', 10000.00, 10000.00)");
        $stmt->execute([$user_id]);
        echo "NGN wallet created for test user.\n";
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
