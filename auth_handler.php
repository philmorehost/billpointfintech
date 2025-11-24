<?php
session_start();

require_once 'includes/database.php';
require_once 'includes/flash_messages.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'signup') {
        $full_name = htmlspecialchars($_POST['full_name']);
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = $_POST['password'];
        $pin = $_POST['pin'];

        if (empty($full_name) || empty($email) || empty($phone) || empty($password) || empty($pin)) {
            set_flash_message('error', 'All fields are required.');
            header('Location: signup.php');
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash_message('error', 'Invalid email format.');
            header('Location: signup.php');
            exit();
        }

        if (!preg_match('/^[0-9]{4}$/', $pin)) {
            set_flash_message('error', 'PIN must be a 4-digit number.');
            header('Location: signup.php');
            exit();
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);

        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                set_flash_message('error', 'Email already exists.');
                header('Location: signup.php');
                exit();
            }

            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, pin) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $phone, $hashed_password, $hashed_pin]);

            $user_id = $pdo->lastInsertId();
            $currencies = ['NGN', 'USD', 'CAD', 'USDT', 'USDC'];
            $wallet_stmt = $pdo->prepare("INSERT INTO wallets (user_id, currency) VALUES (?, ?)");
            foreach ($currencies as $currency) {
                $wallet_stmt->execute([$user_id, $currency]);
            }

            set_flash_message('success', 'Signup successful. Please login.');
            header('Location: login.php');
        } catch (PDOException $e) {
            set_flash_message('error', 'Database error. Please try again.');
            header('Location: signup.php');
        }
        exit();
    }

    if ($action === 'login') {
        $email = $_POST['email'];
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            set_flash_message('error', 'All fields are required.');
            header('Location: login.php');
            exit();
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: dashboard.php');
                }
            } else {
                set_flash_message('error', 'Invalid credentials.');
                header('Location: login.php');
            }
        } catch (PDOException $e) {
            set_flash_message('error', 'Database error. Please try again.');
            header('Location: login.php');
        }
        exit();
    }
}
