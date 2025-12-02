<?php
require_once 'includes/bootstrap.php';
require_once 'core/datagifting_api.php';
// We don't require auth_check.php globally because some actions (like account verification on signup) don't need a logged-in user.

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // CSRF check for all POST actions
    if (!validate_csrf_token()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'CSRF validation failed. Please refresh the page.']);
        exit();
    }

    $action = $_POST['action'];
    header('Content-Type: application/json');

    switch ($action) {
        case 'verify_iuc':
            require_once 'includes/auth_check.php';
            $provider = $_POST['cable_provider'];
            $iuc = $_POST['iuc_number'];

            $datagifting = new DatagiftingAPI($config['settings']['datagifting_api_key'] ?? null);
            $response = $datagifting->verify_cable_iuc($provider, $iuc);

            if ($response && $response['status'] === 'success') {
                echo json_encode(['status' => 'success', 'customer_name' => $response['desc']]);
            } else {
                $message = $response['desc'] ?? 'Verification failed.';
                echo json_encode(['status' => 'error', 'message' => $message]);
            }
            break;

        case 'verify_meter':
            require_once 'includes/auth_check.php';
            $provider = $_POST['provider'];
            $meter_number = $_POST['meter_number'];
            $type = $_POST['type'];

            $datagifting = new DatagiftingAPI($config['settings']['datagifting_api_key'] ?? null);
            $response = $datagifting->verify_meter_number($provider, $meter_number, $type);

            if ($response && $response['status'] === 'success') {
                echo json_encode(['status' => 'success', 'customer_name' => $response['desc']]);
            } else {
                $message = $response['desc'] ?? 'Verification failed.';
                echo json_encode(['status' => 'error', 'message' => $message]);
            }
            break;

        case 'verify_kyc':
            require_once 'includes/auth_check.php';
            require_once 'core/monnify_api.php';

            $user_id = $_SESSION['user_id'];
            $bvn = $_POST['bvn'] ?? '';
            $name = $_POST['name'] ?? '';
            $dob = $_POST['dob'] ?? '';
            $mobileNo = $_POST['mobileNo'] ?? '';

            if (empty($bvn) || empty($name) || empty($dob) || empty($mobileNo)) {
                echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
                exit();
            }

            $monnify = new MonnifyAPI($config['settings']['monnify_api_key'] ?? null, $config['settings']['monnify_secret_key'] ?? null);
            $response = $monnify->verify_bvn($bvn, $name, $dob, $mobileNo);

            if (isset($response['responseBody']['matchStatus']) && $response['responseBody']['matchStatus'] === 'MATCH') {
                // Verification successful, update user's record
                try {
                    $stmt = $pdo->prepare("UPDATE users SET bvn = ?, kyc_level = 1, kyc_verified_at = NOW() WHERE id = ?");
                    $stmt->execute([$bvn, $user_id]);
                    echo json_encode(['status' => 'success', 'message' => 'BVN verification successful! Your account is now verified.']);
                } catch (Exception $e) {
                    error_log("KYC DB Update Error: " . $e->getMessage());
                    echo json_encode(['status' => 'error', 'message' => 'Verification was successful, but we could not update your profile. Please contact support.']);
                }
            } else {
                $message = $response['responseMessage'] ?? 'Verification failed. Please check your details and try again.';
                echo json_encode(['status' => 'error', 'message' => $message]);
            }
            break;

        case 'verify_recipient':
            require_once 'includes/auth_check.php';
            $email = $_POST['email'] ?? '';

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
                exit();
            }

            // Check if the recipient is the sender themselves
            $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $sender_email = $stmt->fetchColumn();
            if (strtolower($email) === strtolower($sender_email)) {
                echo json_encode(['status' => 'error', 'message' => 'You cannot send money to yourself.']);
                exit();
            }

            $stmt = $pdo->prepare("SELECT full_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $recipient = $stmt->fetch();

            if ($recipient) {
                echo json_encode(['status' => 'success', 'recipient_name' => $recipient['full_name']]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Recipient not found.']);
            }
            break;

        case 'get_exchange_rate':
            require_once 'includes/auth_check.php';
            require_once 'core/juicyway_api.php';

            $from = $_POST['from'] ?? '';
            $to = $_POST['to'] ?? '';

            if (empty($from) || empty($to)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid currency pair.']);
                exit();
            }

            $juicyway = new JuicyWayAPI($config['settings']['juicyway_api_key'] ?? null, $config['settings']['juicyway_secret_key'] ?? null);
            $response = $juicyway->get_exchange_rate($from, $to);

            // Assuming a successful response structure like: { "status": true, "data": { "rate": 1.23 } }
            if (isset($response['data']['rate'])) {
                echo json_encode(['status' => 'success', 'rate' => (float)$response['data']['rate']]);
            } else {
                $message = $response['message'] ?? 'Could not retrieve exchange rate.';
                echo json_encode(['status' => 'error', 'message' => $message]);
            }
            break;

        case 'verify_account':
            // No auth check needed for this action
            require_once 'core/paystack_api.php';

            $bank_code = $_POST['bank_code'] ?? '';
            $account_number = $_POST['account_number'] ?? '';

            if (empty($bank_code) || empty($account_number)) {
                echo json_encode(['status' => 'error', 'message' => 'Bank code and account number are required.']);
                exit();
            }

            $paystack = new PaystackAPI($config['settings']['paystack_secret_key'] ?? null);
            $response = $paystack->resolveAccountNumber($account_number, $bank_code);

            if ($response && isset($response['status']) && $response['status'] === true) {
                 echo json_encode(['status' => 'success', 'account_name' => $response['data']['account_name']]);
            } else {
                 $message = $response['message'] ?? 'Could not verify account details.';
                 echo json_encode(['status' => 'error', 'message' => $message]);
            }
            break;

        case 'reply_ticket_ajax':
            require_once 'includes/auth_check.php';
            $ticket_id = $_POST['ticket_id'] ?? 0;
            $message = trim($_POST['message'] ?? '');
            $user_id = $_SESSION['user_id'];
            $is_admin = is_admin();

            if (empty($message) || empty($ticket_id)) {
                echo json_encode(['status' => 'error', 'message' => 'Message cannot be empty.']);
                exit();
            }

            // Security: Verify the user owns the ticket OR is an admin
            $stmt = $pdo->prepare("SELECT id, status FROM tickets WHERE id = ? AND (user_id = ? OR ?)");
            $stmt->execute([$ticket_id, $user_id, $is_admin]);
            $ticket = $stmt->fetch();

            if (!$ticket) {
                echo json_encode(['status' => 'error', 'message' => 'Permission denied.']);
                exit();
            }

            if ($ticket['status'] === 'closed') {
                echo json_encode(['status' => 'error', 'message' => 'This ticket is closed.']);
                exit();
            }

            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message, is_admin_reply) VALUES (?, ?, ?, ?)");
                $stmt->execute([$ticket_id, $user_id, $message, $is_admin ? 1 : 0]);

                // Update ticket status
                $new_status = $is_admin ? 'open' : 'user_reply';
                $ticket_stmt = $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?");
                $ticket_stmt->execute([$new_status, $ticket_id]);

                $pdo->commit();
                echo json_encode(['status' => 'success', 'message' => 'Reply sent successfully.']);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log("AJAX Ticket Reply Error: " . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'An error occurred while sending your reply.']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Unknown action specified.']);
            break;
    }
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
    header('Content-Type: application/json');

    switch ($action) {
        case 'get_cable_plans':
            require_once 'includes/auth_check.php';
            $provider = $_GET['provider'];
            $stmt = $pdo->prepare("SELECT * FROM cable_plans WHERE cable_provider = ? ORDER BY price");
            $stmt->execute([$provider]);
            echo json_encode($stmt->fetchAll());
            break;
        case 'get_ticket_messages':
            require_once 'includes/auth_check.php';
            $ticket_id = $_GET['ticket_id'] ?? 0;
            $user_id = $_SESSION['user_id'];

            // Security: Ensure the user owns the ticket or is an admin
            $stmt = $pdo->prepare("SELECT id FROM tickets WHERE id = ? AND (user_id = ? OR ?)");
            $stmt->execute([$ticket_id, $user_id, is_admin()]);
            if (!$stmt->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Permission denied.']);
                exit();
            }

            $stmt = $pdo->prepare("SELECT message, is_admin_reply, created_at FROM ticket_messages WHERE ticket_id = ? ORDER BY created_at ASC");
            $stmt->execute([$ticket_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'messages' => $messages]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Unknown action specified.']);
            break;
    }
    exit();
}

// Fallback for any request that doesn't match
header('Content-Type: application/json');
http_response_code(400); // Bad Request
echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
exit();
