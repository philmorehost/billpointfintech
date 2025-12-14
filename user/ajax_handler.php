<?php
// user/ajax_handler.php
require_once '../core/vtu_api.php';
require_once '../core/functions.php'; // Includes session_start() via config.php

header('Content-Type: application/json');

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit;
}

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid action specified.'];

if ($action === 'verify_cable') {
    $provider = $_POST['provider'] ?? '';
    $iuc_number = $_POST['iuc_number'] ?? '';

    if (empty($provider) || empty($iuc_number)) {
        $response['message'] = 'Provider and IUC number are required.';
    } else {
        $api_response = verify_cable_customer($provider, $iuc_number);

        if (isset($api_response['status']) && $api_response['status'] === 'success') {
            $response = [
                'status' => 'success',
                'customer_name' => $api_response['desc']
            ];
        } else {
            $response['message'] = $api_response['desc'] ?? 'Failed to verify customer details.';
        }
    }
}
elseif ($action === 'verify_bank') {
    $bank_code = $_POST['bank_code'] ?? '';
    $account_number = $_POST['account_number'] ?? '';

    if (empty($bank_code) || empty($account_number)) {
        $response['message'] = 'Bank and account number are required.';
    } else {
        $api_response = verify_bank_account($bank_code, $account_number);

        // The API response for success is slightly different here
        if (isset($api_response['status']) && $api_response['status'] === 'success') {
            $response = [
                'status' => 'success',
                'account_name' => $api_response['account_name'],
                'enquiry_id' => $api_response['enquiry_id'] // Pass this back to the client
            ];
        } else {
            $response['message'] = $api_response['desc'] ?? 'Failed to verify account details.';
        }
    }
}

// Future actions (like verify_electricity) can be added here with `elseif ($action === '...')`
elseif ($action === 'verify_electricity') {
    $provider = $_POST['provider'] ?? '';
    $meter_number = $_POST['meter_number'] ?? '';
    $meter_type = $_POST['meter_type'] ?? '';

    if (empty($provider) || empty($meter_number) || empty($meter_type)) {
        $response['message'] = 'Provider, meter number, and meter type are required.';
    } else {
        $api_response = verify_electricity_customer($provider, $meter_number, $meter_type);

        if (isset($api_response['status']) && $api_response['status'] === 'success') {
            $response = [
                'status' => 'success',
                'customer_name' => $api_response['desc']
            ];
        } else {
            $response['message'] = $api_response['desc'] ?? 'Failed to verify customer details.';
        }
    }
}


echo json_encode($response);
exit;
