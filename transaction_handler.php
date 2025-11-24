<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
require_once 'core/datagifting_api.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
        header('Location: ' . basename($_SERVER['HTTP_REFERER']));
        exit();
    }
    if ($_POST['action'] === 'buy_airtime') {
        $user_id = $_SESSION['user_id'];
        $network = $_POST['network'];
        $phone_number = $_POST['phone_number'];
        $amount = (int)$_POST['amount'];

        // --- 1. Validate Input ---
        if (empty($network) || empty($phone_number) || empty($amount) || $amount < 50) {
            set_flash_message('error', 'Invalid input.');
            header('Location: airtime.php');
            exit();
        }

        try {
            $pdo->beginTransaction();

            // --- 2. Check Wallet Balance ---
            $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = 'NGN' FOR UPDATE");
            $stmt->execute([$user_id]);
            $balance = $stmt->fetchColumn();

            if ($balance < $amount) {
                set_flash_message('error', 'Insufficient funds.');
                header('Location: airtime.php');
                exit();
            }

            // --- 3. Debit User's Wallet ---
            $new_balance = $balance - $amount;
            $stmt = $pdo->prepare("UPDATE wallets SET balance = ? WHERE user_id = ? AND currency = 'NGN'");
            $stmt->execute([$new_balance, $user_id]);

            // --- 4. Call Datagifting API ---
            $settings_stmt = $pdo->query("SELECT value FROM settings WHERE name = 'datagifting_api_key'");
            $api_key = $settings_stmt->fetchColumn();
            $datagifting = new DatagiftingAPI($api_key);
            $response = $datagifting->purchase_airtime($network, $phone_number, $amount);

            // --- 5. Log Transaction ---
            $status = ($response && $response['status'] === 'success') ? 'completed' : 'failed';
            $description = $response['desc'] ?? 'Failed to purchase airtime.';

            $log_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, description) VALUES (?, 'airtime', ?, ?, ?)");
            $log_stmt->execute([$user_id, $amount, $status, $description]);

            // --- 6. Commit or Rollback ---
            if ($status === 'completed') {
                $pdo->commit();
                set_flash_message('success', 'Airtime purchase successful.');
            } else {
                $pdo->rollBack();
                set_flash_message('error', 'Airtime purchase failed. Please try again.');
            }

            header('Location: airtime.php');
            exit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash_message('error', 'An error occurred. Please try again.');
            header('Location: airtime.php');
            exit();
        }
    }

    if ($_POST['action'] === 'buy_data') {
        $user_id = $_SESSION['user_id'];
        $phone_number = $_POST['phone_number'];
        $plan_id = (int)$_POST['data_plan'];

        if (empty($phone_number) || empty($plan_id)) {
            set_flash_message('error', 'Invalid input.');
            header('Location: data.php');
            exit();
        }

        try {
            $pdo->beginTransaction();

            $plan_stmt = $pdo->prepare("SELECT * FROM data_plans WHERE id = ?");
            $plan_stmt->execute([$plan_id]);
            $plan = $plan_stmt->fetch();

            if (!$plan) {
                set_flash_message('error', 'Invalid data plan selected.');
                header('Location: data.php');
                exit();
            }

            $amount = $plan['price'];

            $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = 'NGN' FOR UPDATE");
            $stmt->execute([$user_id]);
            $balance = $stmt->fetchColumn();

            if ($balance < $amount) {
                set_flash_message('error', 'Insufficient funds.');
                header('Location: data.php');
                exit();
            }

            $new_balance = $balance - $amount;
            $stmt = $pdo->prepare("UPDATE wallets SET balance = ? WHERE user_id = ? AND currency = 'NGN'");
            $stmt->execute([$new_balance, $user_id]);

            $settings_stmt = $pdo->query("SELECT value FROM settings WHERE name = 'datagifting_api_key'");
            $api_key = $settings_stmt->fetchColumn();
            $datagifting = new DatagiftingAPI($api_key);
            $response = $datagifting->purchase_data($plan['network'], $phone_number, $plan['type'], $plan['quantity']);

            $status = ($response && $response['status'] === 'success') ? 'completed' : 'failed';
            $description = $response['desc'] ?? 'Failed to purchase data.';

            $log_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, description) VALUES (?, 'data', ?, ?, ?)");
            $log_stmt->execute([$user_id, $amount, $status, $description]);

            if ($status === 'completed') {
                $pdo->commit();
                set_flash_message('success', 'Data purchase successful.');
            } else {
                $pdo->rollBack();
                set_flash_message('error', 'Data purchase failed. Please try again.');
            }

            header('Location: data.php');
            exit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash_message('error', 'An error occurred. Please try again.');
            header('Location: data.php');
            exit();
        }
    }

    if ($_POST['action'] === 'buy_cable_plan') {
        $user_id = $_SESSION['user_id'];
        $plan_id = (int)$_POST['cable_plan'];
        $iuc_number = $_POST['iuc_number'];

        if (empty($plan_id) || empty($iuc_number)) {
            set_flash_message('error', 'Invalid input.');
            header('Location: cable.php');
            exit();
        }

        try {
            $pdo->beginTransaction();

            $plan_stmt = $pdo->prepare("SELECT * FROM cable_plans WHERE id = ?");
            $plan_stmt->execute([$plan_id]);
            $plan = $plan_stmt->fetch();

            if (!$plan) {
                set_flash_message('error', 'Invalid cable plan selected.');
                header('Location: cable.php');
                exit();
            }

            $amount = $plan['price'];

            $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = 'NGN' FOR UPDATE");
            $stmt->execute([$user_id]);
            $balance = $stmt->fetchColumn();

            if ($balance < $amount) {
                set_flash_message('error', 'Insufficient funds.');
                header('Location: cable.php');
                exit();
            }

            $new_balance = $balance - $amount;
            $stmt = $pdo->prepare("UPDATE wallets SET balance = ? WHERE user_id = ? AND currency = 'NGN'");
            $stmt->execute([$new_balance, $user_id]);

            $settings_stmt = $pdo->query("SELECT value FROM settings WHERE name = 'datagifting_api_key'");
            $api_key = $settings_stmt->fetchColumn();
            $datagifting = new DatagiftingAPI($api_key);
            $response = $datagifting->purchase_cable_plan($plan['cable_provider'], $iuc_number, $plan['package_code']);

            $status = ($response && $response['status'] === 'success') ? 'completed' : 'failed';
            $description = $response['desc'] ?? 'Failed to purchase cable plan.';

            $log_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, description) VALUES (?, 'cable', ?, ?, ?)");
            $log_stmt->execute([$user_id, $amount, $status, $description]);

            if ($status === 'completed') {
                $pdo->commit();
                set_flash_message('success', 'Cable subscription successful.');
            } else {
                $pdo->rollBack();
                set_flash_message('error', 'Cable subscription failed. Please try again.');
            }

            header('Location: cable.php');
            exit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash_message('error', 'An error occurred. Please try again.');
            header('Location: cable.php');
            exit();
        }
    }

    if ($_POST['action'] === 'buy_electricity') {
        $user_id = $_SESSION['user_id'];
        $provider = $_POST['disco_provider'];
        $meter_type = $_POST['meter_type'];
        $meter_number = $_POST['meter_number'];
        $amount = (int)$_POST['amount'];

        if (empty($provider) || empty($meter_type) || empty($meter_number) || $amount < 100) {
            set_flash_message('error', 'Invalid input.');
            header('Location: electricity.php');
            exit();
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = 'NGN' FOR UPDATE");
            $stmt->execute([$user_id]);
            $balance = $stmt->fetchColumn();

            if ($balance < $amount) {
                set_flash_message('error', 'Insufficient funds.');
                header('Location: electricity.php');
                exit();
            }

            $new_balance = $balance - $amount;
            $stmt = $pdo->prepare("UPDATE wallets SET balance = ? WHERE user_id = ? AND currency = 'NGN'");
            $stmt->execute([$new_balance, $user_id]);

            $settings_stmt = $pdo->query("SELECT value FROM settings WHERE name = 'datagifting_api_key'");
            $api_key = $settings_stmt->fetchColumn();
            $datagifting = new DatagiftingAPI($api_key);
            $response = $datagifting->purchase_electricity($provider, $meter_number, $meter_type, $amount);

            $status = ($response && $response['status'] === 'success') ? 'completed' : 'failed';
            $description = $response['desc'] ?? 'Failed to purchase electricity.';

            $log_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, description) VALUES (?, 'electricity', ?, ?, ?)");
            $log_stmt->execute([$user_id, $amount, $status, $description]);

            if ($status === 'completed') {
                $pdo->commit();
                set_flash_message('success', 'Electricity purchase successful.');
            } else {
                $pdo->rollBack();
                set_flash_message('error', 'Electricity purchase failed. Please try again.');
            }

            header('Location: electricity.php');
            exit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash_message('error', 'An error occurred. Please try again.');
            header('Location: electricity.php');
            exit();
        }
    }
}
