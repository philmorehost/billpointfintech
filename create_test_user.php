<?php
// create_test_user.php
require_once 'includes/bootstrap.php';

$full_name = 'Test User';
$email = 'test@example.com';
$phone = '1234567890';
$password = 'password123';
$pin = '1234';

// Hash the password and PIN
$hashed_password = password_hash($password, PASSWORD_BCRYPT);
$hashed_pin = password_hash($pin, PASSWORD_BCRYPT);

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
        echo "Test user created successfully.\n";
        echo "Email: $email\n";
        echo "Password: $password\n";
    }
} catch (PDOException $e) {
    die("Could not create test user: " . $e->getMessage());
}
