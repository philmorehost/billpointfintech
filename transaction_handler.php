<?php
session_start();
require_once 'includes/auth_check.php';
require_once 'includes/database.php';
require_once 'includes/flash_messages.php';
require_once 'core/datagifting_api.php';
require_once 'includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf_token()) {
        die('CSRF validation failed.');
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
}
