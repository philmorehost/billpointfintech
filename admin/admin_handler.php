<?php
require_once '../includes/bootstrap.php';
require_once '../includes/auth_check.php';

if (!is_admin()) {
    redirect('/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf_token()) {
        set_flash_message('error', 'CSRF validation failed.');
        header('Location: ' . basename($_SERVER['HTTP_REFERER']));
        exit();
    }

    $admin_id = $_SESSION['user_id'];

    if ($_POST['action'] === 'approve_api_request') {
        $request_id = (int)$_POST['request_id'];

        try {
            $pdo->beginTransaction();

            // 1. Get user_id from the request
            $stmt = $pdo->prepare("SELECT user_id FROM api_key_requests WHERE id = ? AND status = 'pending'");
            $stmt->execute([$request_id]);
            $user_id = $stmt->fetchColumn();

            if (!$user_id) {
                throw new Exception("Request not found or already processed.");
            }

            // 2. Update the request status
            $update_req_stmt = $pdo->prepare("UPDATE api_key_requests SET status = 'approved', reviewed_by = ? WHERE id = ?");
            $update_req_stmt->execute([$admin_id, $request_id]);

            // 3. Deactivate any old keys for this user
            $deactivate_stmt = $pdo->prepare("UPDATE api_keys SET is_active = 0 WHERE user_id = ?");
            $deactivate_stmt->execute([$user_id]);

            // 4. Generate and insert the new API key
            $new_api_key = 'bp_' . bin2hex(random_bytes(16));
            $insert_key_stmt = $pdo->prepare("INSERT INTO api_keys (user_id, api_key) VALUES (?, ?)");
            $insert_key_stmt->execute([$user_id, $new_api_key]);

            $pdo->commit();
            set_flash_message('success', 'API request approved and key generated.');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            set_flash_message('error', 'Failed to approve request: ' . $e->getMessage());
        }
        header('Location: api_requests.php');
        exit();
    }

    if ($_POST['action'] === 'reject_api_request') {
        $request_id = (int)$_POST['request_id'];

        try {
            $stmt = $pdo->prepare("UPDATE api_key_requests SET status = 'rejected', reviewed_by = ? WHERE id = ? AND status = 'pending'");
            $stmt->execute([$admin_id, $request_id]);

            if ($stmt->rowCount() > 0) {
                set_flash_message('success', 'API request has been rejected.');
            } else {
                set_flash_message('error', 'Request not found or already processed.');
            }
        } catch (Exception $e) {
            set_flash_message('error', 'Failed to reject request: ' . $e->getMessage());
        }
        header('Location: api_requests.php');
        exit();
    }

}

// Fallback redirect for GET requests or unknown actions
redirect('/admin/index.php');
