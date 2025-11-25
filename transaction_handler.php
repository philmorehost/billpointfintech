<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
require_once 'core/datagifting_api.php';

function process_transaction(PDO $pdo, $user_id, $amount, $type, callable $api_call, $redirect_path) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = 'NGN' FOR UPDATE");
        $stmt->execute([$user_id]);
        $balance = $stmt->fetchColumn();

        if ($balance < $amount) {
            set_flash_message('error', 'Insufficient funds.');
            header("Location: $redirect_path");
            exit();
        }

        $new_balance = $balance - $amount;
        $stmt = $pdo->prepare("UPDATE wallets SET balance = ? WHERE user_id = ? AND currency = 'NGN'");
        $stmt->execute([$new_balance, $user_id]);

        $response = $api_call();

        $status = ($response && $response['status'] === 'success') ? 'completed' : 'failed';

        $description = $response['response_desc'] ?? "Failed to purchase $type.";
        if ($status === 'completed' && isset($response['response_desc'])) {
            $description = $response['response_desc'];
        } elseif ($status === 'failed' && isset($response['desc'])) {
            $description = $response['desc'];
        }

        $log_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status, description) VALUES (?, ?, ?, 'NGN', ?, ?)");
        $log_stmt->execute([$user_id, $type, $amount, $status, $description]);

        if ($status === 'completed') {
            $pdo->commit();
            set_flash_message('success', ucfirst($type) . ' purchase successful.');
        } else {
            $pdo->rollBack();
            set_flash_message('error', ucfirst($type) . ' purchase failed. Please try again.');
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_flash_message('error', 'An error occurred. Please try again.');
    }

    if ($status === 'completed') {
        header("Location: history.php");
    } else {
        header("Location: $redirect_path");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
        header('Location: ' . basename($_SERVER['HTTP_REFERER']));
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $datagifting = new DatagiftingAPI($config['settings']['datagifting_api_key'] ?? null);

    if ($_POST['action'] === 'buy_airtime') {
        $network = $_POST['network'];
        $phone_number = $_POST['phone_number'];
        $amount = (int)$_POST['amount'];

        if (empty($network) || empty($phone_number) || $amount < 50) {
            set_flash_message('error', 'Invalid input.');
            header('Location: airtime.php');
            exit();
        }

        process_transaction($pdo, $user_id, $amount, 'airtime', function() use ($datagifting, $network, $phone_number, $amount) {
            return $datagifting->purchase_airtime($network, $phone_number, $amount);
        }, 'airtime.php');
    }

    if ($_POST['action'] === 'buy_data') {
        $phone_number = $_POST['phone_number'];
        $plan_id = (int)$_POST['data_plan_id'];

        if (empty($phone_number) || empty($plan_id)) {
            set_flash_message('error', 'Invalid input.');
            header('Location: data.php');
            exit();
        }

        $plan_stmt = $pdo->prepare("SELECT * FROM data_plans WHERE id = ?");
        $plan_stmt->execute([$plan_id]);
        $plan = $plan_stmt->fetch();

        if (!$plan) {
            set_flash_message('error', 'Invalid data plan selected.');
            header('Location: data.php');
            exit();
        }

        process_transaction($pdo, $user_id, $plan['price'], 'data', function() use ($datagifting, $plan, $phone_number) {
            return $datagifting->purchase_data($plan['network'], $phone_number, $plan['type'], $plan['quantity']);
        }, 'data.php');
    }

    if ($_POST['action'] === 'buy_cable_plan') {
        $plan_id = (int)$_POST['cable_plan_id'];
        $iuc_number = $_POST['iuc_number'];

        if (empty($plan_id) || empty($iuc_number)) {
            set_flash_message('error', 'Invalid input.');
            header('Location: cable.php');
            exit();
        }

        $plan_stmt = $pdo->prepare("SELECT * FROM cable_plans WHERE id = ?");
        $plan_stmt->execute([$plan_id]);
        $plan = $plan_stmt->fetch();

        if (!$plan) {
            set_flash_message('error', 'Invalid cable plan selected.');
            header('Location: cable.php');
            exit();
        }

        process_transaction($pdo, $user_id, $plan['price'], 'cable', function() use ($datagifting, $plan, $iuc_number) {
            return $datagifting->purchase_cable_plan($plan['cable_provider'], $iuc_number, $plan['package_code']);
        }, 'cable.php');
    }

    if ($_POST['action'] === 'buy_electricity') {
        $provider = $_POST['provider'];
        $meter_type = $_POST['type'];
        $meter_number = $_POST['meter_number'];
        $amount = (int)$_POST['amount'];

        if (empty($provider) || empty($meter_type) || empty($meter_number) || $amount < 100) {
            set_flash_message('error', 'Invalid input.');
            header('Location: electricity.php');
            exit();
        }

        process_transaction($pdo, $user_id, $amount, 'electricity', function() use ($datagifting, $provider, $meter_number, $meter_type, $amount) {
            return $datagifting->purchase_electricity($provider, $meter_number, $meter_type, $amount);
        }, 'electricity.php');
    }

    if ($_POST['action'] === 'buy_exam_pin') {
        $product_id = (int)$_POST['exam_product_id'];
        $quantity = (int)$_POST['quantity'];

        if (empty($product_id) || $quantity < 1) {
            set_flash_message('error', 'Invalid input.');
            header('Location: exam.php');
            exit();
        }

        $product_stmt = $pdo->prepare("SELECT * FROM exam_products WHERE id = ?");
        $product_stmt->execute([$product_id]);
        $product = $product_stmt->fetch();

        if (!$product) {
            set_flash_message('error', 'Invalid exam product selected.');
            header('Location: exam.php');
            exit();
        }

        $total_amount = $product['price'] * $quantity;

        process_transaction($pdo, $user_id, $total_amount, 'exam', function() use ($datagifting, $product, $quantity) {
            return $datagifting->purchase_exam_pin($product['product_code'], $quantity);
        }, 'exam.php');
    }
}
