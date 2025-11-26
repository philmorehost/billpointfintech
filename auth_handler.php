<?php
require_once 'includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
        header('Location: ' . basename($_SERVER['HTTP_REFERER']));
        exit();
    }
    $action = $_POST['action'];

    if ($action === 'signup') {
        $full_name = htmlspecialchars($_POST['full_name']);
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = $_POST['password'];
        $pin = $_POST['pin'];
        $bank_code = $_POST['bank_code'];
        $account_number = $_POST['account_number'];

        if (empty($full_name) || empty($email) || empty($phone) || empty($password) || empty($pin) || empty($bank_code) || empty($account_number)) {
            set_flash_message('error', 'All fields are required.');
            header('Location: signup.php');
            exit();
        }

        if (!preg_match('/^[0-9]{10,14}$/', $phone)) {
            set_flash_message('error', 'Invalid phone number format.');
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
            $pdo->beginTransaction();

            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                set_flash_message('error', 'Email already exists.');
                header('Location: signup.php');
                exit();
            }

            // First, let's re-verify the account details on the server-side to ensure consistency and prevent manipulation.
            require_once 'core/paystack_api.php';
            $paystack = new PaystackAPI($config['settings']['paystack_secret_key'] ?? null);
            $verify_response = $paystack->resolveAccountNumber($account_number, $bank_code);

            if (!$verify_response || $verify_response['status'] !== true) {
                set_flash_message('error', 'Could not verify bank account details. Please check and try again.');
                header('Location: signup.php');
                exit();
            }

            $verified_account_name = $verify_response['data']['account_name'];
            $bank_name = $verify_response['data']['bank_name']; // Get bank name from verification

            // Optional: You might want to check if the verified name closely matches the user's full name.
            // This is a business logic decision. For now, we'll proceed if verification is successful.


            $stmt = $pdo->prepare(
                "INSERT INTO users (full_name, email, phone, password, pin, bank_name, bank_code, account_number)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$full_name, $email, $phone, $hashed_password, $hashed_pin, $bank_name, $bank_code, $account_number]);

            $user_id = $pdo->lastInsertId();
            $wallet_stmt = $pdo->prepare("INSERT INTO wallets (user_id, currency) VALUES (?, ?)");
            foreach ($config['wallet_currencies'] as $currency) {
                $wallet_stmt->execute([$user_id, $currency]);
            }

            $pdo->commit();
            set_flash_message('success', 'Signup successful. Please login.');
            header('Location: login.php');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
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
                $_SESSION['user_role'] = 'user';
                header('Location: dashboard.php');
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
