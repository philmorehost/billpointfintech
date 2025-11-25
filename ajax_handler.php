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

        case 'add_reply':
            require_once 'includes/auth_check.php';
            $ticket_id = $_POST['ticket_id'] ?? 0;
            $message = trim($_POST['message'] ?? '');
            $user_id = $_SESSION['user_id'];

            if (empty($message) || empty($ticket_id)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
                exit();
            }

            // Verify the user owns the ticket
            $stmt = $pdo->prepare("SELECT id FROM tickets WHERE id = ? AND user_id = ?");
            $stmt->execute([$ticket_id, $user_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Permission denied.']);
                exit();
            }

            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message) VALUES (?, ?, ?)");
                $stmt->execute([$ticket_id, $user_id, $message]);

                // Update ticket status to show user has replied
                $ticket_stmt = $pdo->prepare("UPDATE tickets SET status = 'user_reply' WHERE id = ?");
                $ticket_stmt->execute([$ticket_id]);

                $pdo->commit();
                $new_message = [
                    'message' => htmlspecialchars($message),
                    'created_at' => date('M d, H:i')
                ];
                echo json_encode(['status' => 'success', 'message' => $new_message]);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'Database error.']);
            }
            break;

        case 'add_admin_reply':
            // Admin auth check
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
                 echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
                 exit();
            }
            $ticket_id = $_POST['ticket_id'] ?? 0;
            $message = trim($_POST['message'] ?? '');
            $admin_id = $_SESSION['user_id'];

            if (empty($message) || empty($ticket_id)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
                exit();
            }

            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message, is_admin_reply) VALUES (?, ?, ?, 1)");
                $stmt->execute([$ticket_id, $admin_id, $message]);

                // Set ticket status back to open
                $ticket_stmt = $pdo->prepare("UPDATE tickets SET status = 'open' WHERE id = ?");
                $ticket_stmt->execute([$ticket_id]);

                $pdo->commit();
                $new_message = [
                    'message' => htmlspecialchars($message),
                    'created_at' => date('M d, H:i')
                ];
                 echo json_encode(['status' => 'success', 'message' => $new_message]);
            } catch(Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'Database error.']);
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
