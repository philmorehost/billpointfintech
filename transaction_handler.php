<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';
require_once 'core/datagifting_api.php';

function check_transaction_limit(PDO $pdo, $target_id) {
    $stmt = $pdo->prepare("SELECT * FROM transaction_limits WHERE target_id = ?");
    $stmt->execute([$target_id]);
    $limit = $stmt->fetch();

    if ($limit && !$limit['is_whitelisted']) {
        $count_stmt = $pdo->prepare("
            SELECT COUNT(*) FROM transactions
            WHERE description LIKE ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $count_stmt->execute(["%{$target_id}%", $limit['time_frame_seconds']]);
        $transaction_count = $count_stmt->fetchColumn();

        if ($transaction_count >= $limit['max_count']) {
            send_admin_alert("Transaction limit breached for target ID: {$target_id}", "Security Alert: Transaction Limit");
            return false; // Limit breached
        }
    }
    return true; // OK to proceed
}


function process_transaction(PDO $pdo, $user_id, $amount, $type, $target_id, callable $api_call, $redirect_path) {
    if (!check_transaction_limit($pdo, $target_id)) {
        set_flash_message('error', 'Transaction limit exceeded for this recipient. Please try again later.');
        header("Location: $redirect_path");
        exit();
    }

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

        process_transaction($pdo, $user_id, $amount, 'airtime', $phone_number, function() use ($datagifting, $network, $phone_number, $amount) {
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

        process_transaction($pdo, $user_id, $plan['price'], 'data', $phone_number, function() use ($datagifting, $plan, $phone_number) {
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

        process_transaction($pdo, $user_id, $plan['price'], 'cable', $iuc_number, function() use ($datagifting, $plan, $iuc_number) {
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

        process_transaction($pdo, $user_id, $amount, 'electricity', $meter_number, function() use ($datagifting, $provider, $meter_number, $meter_type, $amount) {
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

        process_transaction($pdo, $user_id, $total_amount, 'exam', $product['product_code'], function() use ($datagifting, $product, $quantity) {
            return $datagifting->purchase_exam_pin($product['product_code'], $quantity);
        }, 'exam.php');
    }

    if ($_POST['action'] === 'p2p_transfer') {
        $recipient_email = $_POST['recipient_email'];
        $amount = (float)$_POST['amount'];
        $pin = $_POST['pin'];

        if (empty($recipient_email) || $amount <= 0 || empty($pin)) {
            set_flash_message('error', 'Invalid input.');
            header('Location: p2p_transfer.php');
            exit();
        }

        try {
            // 1. Verify user's PIN
            $user_stmt = $pdo->prepare("SELECT pin, id FROM users WHERE id = ?");
            $user_stmt->execute([$user_id]);
            $user = $user_stmt->fetch();
            if (!$user || !password_verify($pin, $user['pin'])) {
                set_flash_message('error', 'Incorrect PIN.');
                header('Location: p2p_transfer.php');
                exit();
            }

            // 2. Check balance
            $wallet_stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = 'NGN'");
            $wallet_stmt->execute([$user_id]);
            $balance = $wallet_stmt->fetchColumn();
            if ($balance < $amount) {
                set_flash_message('error', 'Insufficient funds.');
                header('Location: p2p_transfer.php');
                exit();
            }

            // 3. Get recipient ID
            $recipient_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $recipient_stmt->execute([$recipient_email]);
            $recipient = $recipient_stmt->fetch();
            if (!$recipient || $recipient['id'] == $user_id) {
                set_flash_message('error', 'Invalid recipient.');
                header('Location: p2p_transfer.php');
                exit();
            }
            $recipient_id = $recipient['id'];

            // 4. Create pending P2P transfer record
            $stmt = $pdo->prepare("INSERT INTO p2p_transfers (sender_id, recipient_id, amount) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $recipient_id, $amount]);

            set_flash_message('success', 'Transfer request submitted and is pending admin approval.');
            header('Location: history.php');
            exit();

        } catch (Exception $e) {
            error_log("P2P Transfer Error: " . $e->getMessage());
            set_flash_message('error', 'An unexpected error occurred.');
            header('Location: p2p_transfer.php');
            exit();
        }
    }

    if ($_POST['action'] === 'currency_exchange') {
        $from_currency = $_POST['from_currency'];
        $to_currency = $_POST['to_currency'];
        $amount = (float)$_POST['amount'];
        $pin = $_POST['pin'];

        if (empty($from_currency) || empty($to_currency) || $amount <= 0 || empty($pin) || $from_currency === $to_currency) {
            set_flash_message('error', 'Invalid input for currency exchange.');
            header('Location: exchange.php');
            exit();
        }

        try {
            $pdo->beginTransaction();

            // 1. Verify PIN
            $user_stmt = $pdo->prepare("SELECT pin FROM users WHERE id = ?");
            $user_stmt->execute([$user_id]);
            if (!password_verify($pin, $user_stmt->fetchColumn())) {
                throw new Exception("Incorrect PIN.");
            }

            // 2. Lock and check source wallet balance
            $wallet_stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
            $wallet_stmt->execute([$user_id, $from_currency]);
            $balance = $wallet_stmt->fetchColumn();
            if ($balance < $amount) {
                throw new Exception("Insufficient funds in your {$from_currency} wallet.");
            }

            // 3. Get exchange rate from JuicyWay
            require_once 'core/juicyway_api.php';
            $juicyway = new JuicyWayAPI($config['settings']['juicyway_api_key'] ?? null, $config['settings']['juicyway_secret_key'] ?? null);
            $rate_response = $juicyway->get_exchange_rate($from_currency, $to_currency);
            if (!isset($rate_response['data']['rate'])) {
                throw new Exception($rate_response['message'] ?? "Could not fetch exchange rate.");
            }
            $rate = (float)$rate_response['data']['rate'];
            $received_amount = $amount * $rate;

            // 4. Debit source wallet & Credit destination wallet
            $debit_stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency = ?");
            $debit_stmt->execute([$amount, $user_id, $from_currency]);

            $credit_stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency = ?");
            $credit_stmt->execute([$received_amount, $user_id, $to_currency]);

            // 5. Log transaction
            $log_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status, description) VALUES (?, ?, ?, ?, 'completed', ?)");
            $desc_debit = "Converted {$amount} {$from_currency} to {$to_currency}";
            $log_stmt->execute([$user_id, 'exchange_debit', $amount, $from_currency, $desc_debit]);
            $desc_credit = "Received {$received_amount} {$to_currency} from {$from_currency}";
            $log_stmt->execute([$user_id, 'exchange_credit', $received_amount, $to_currency, $desc_credit]);

            $pdo->commit();
            set_flash_message('success', "Successfully converted {$amount} {$from_currency} to {$received_amount} {$to_currency}.");
            header('Location: history.php');
            exit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash_message('error', 'Exchange failed: ' . $e->getMessage());
            header('Location: exchange.php');
            exit();
        }
    }

    if ($_POST['action'] === 'global_transfer') {
        $source_currency = $_POST['source_currency'];
        $amount = (float)$_POST['amount'];
        $pin = $_POST['pin'];
        // In a real app, recipient details would be more complex
        $recipient_details = $_POST['recipient'];

        if (empty($source_currency) || $amount <= 0 || empty($pin)) {
            set_flash_message('error', 'Invalid input for global transfer.');
            header('Location: global_transfer.php');
            exit();
        }

        try {
            $pdo->beginTransaction();

            // 1. Verify PIN and check balance
            $user_stmt = $pdo->prepare("SELECT pin FROM users WHERE id = ?");
            $user_stmt->execute([$user_id]);
            if (!password_verify($pin, $user_stmt->fetchColumn())) {
                throw new Exception("Incorrect PIN.");
            }

            $wallet_stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = ? FOR UPDATE");
            $wallet_stmt->execute([$user_id, $source_currency]);
            $balance = $wallet_stmt->fetchColumn();
            if ($balance < $amount) {
                throw new Exception("Insufficient funds.");
            }

            // 2. Debit user's wallet
            $debit_stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency = ?");
            $debit_stmt->execute([$amount, $user_id, $source_currency]);

            // 3. Call JuicyWay API (simplified)
            require_once 'core/juicyway_api.php';
            $juicyway = new JuicyWayAPI($config['settings']['juicyway_api_key'] ?? null, $config['settings']['juicyway_secret_key'] ?? null);
            $transfer_data = [
                'amount' => $amount,
                'currency' => $_POST['destination_currency'],
                'beneficiary' => $recipient_details
            ];
            $response = $juicyway->create_global_transfer($transfer_data);

            if (!isset($response['status']) || $response['status'] !== true) {
                 throw new Exception($response['message'] ?? "Global transfer initiation failed.");
            }
            $reference = $response['data']['reference'] ?? 'jw_' . uniqid();

            // 4. Log transaction as pending
            $desc = "Global transfer of {$amount} {$source_currency} to {$recipient_details['name']} initiated.";
            $log_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status, description, reference) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
            $log_stmt->execute([$user_id, 'global_transfer', $amount, $source_currency, $desc, $reference]);

            $pdo->commit();
            set_flash_message('success', 'Your global transfer has been initiated.');
            header('Location: history.php');
            exit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash_message('error', 'Transfer failed: ' . $e->getMessage());
            header('Location: global_transfer.php');
            exit();
        }
    }

    if ($_POST['action'] === 'pay_invoice') {
        $invoice_id = (int)$_POST['invoice_id'];

        try {
            $pdo->beginTransaction();

            // 1. Get invoice details and lock the row
            $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ? AND status = 'unpaid' FOR UPDATE");
            $stmt->execute([$invoice_id, $user_id]);
            $invoice = $stmt->fetch();

            if (!$invoice) {
                throw new Exception("Invoice not found, is already paid, or you do not have permission to pay it.");
            }
            $amount = (float)$invoice['total_amount'];

            // 2. Check wallet balance
            $wallet_stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency = 'NGN' FOR UPDATE");
            $wallet_stmt->execute([$user_id]);
            $balance = $wallet_stmt->fetchColumn();

            if ($balance < $amount) {
                throw new Exception("Insufficient funds in your NGN wallet.");
            }

            // 3. Debit wallet
            $debit_stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency = 'NGN'");
            $debit_stmt->execute([$amount, $user_id]);

            // 4. Update invoice status
            $inv_update_stmt = $pdo->prepare("UPDATE invoices SET status = 'paid' WHERE id = ?");
            $inv_update_stmt->execute([$invoice_id]);

            // 5. Log transaction
            $desc = "Payment for Invoice #{$invoice_id}";
            $log_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, currency, status, description) VALUES (?, ?, ?, 'NGN', 'completed', ?)");
            $log_stmt->execute([$user_id, 'invoice_payment', $amount, $desc]);

            $pdo->commit();
            set_flash_message('success', 'Invoice paid successfully.');
            header("Location: view_invoice.php?id={$invoice_id}");
            exit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            set_flash_message('error', 'Payment failed: ' . $e->getMessage());
            header("Location: view_invoice.php?id={$invoice_id}");
            exit();
        }
    }
}
