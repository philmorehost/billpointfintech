<?php
// core/beewave_api.php

define('BEEWAVE_BASE_URL', 'https://merchant.beewave.ng/api/v1/');

/**
 * Makes a request to the Beewave API.
 *
 * @param string $endpoint The API endpoint to call.
 * @param array $payload The data to send in the request body.
 * @param string $method The HTTP method (POST or GET).
 * @return array The JSON-decoded response from the API.
 */
function make_beewave_request($endpoint, $payload = [], $method = 'POST') {
    // It's safer to get the key from the settings global, in case it's updated mid-script
    $access_key = $GLOBALS['app_settings']['beewave_access_key'] ?? '';
    if (empty($access_key)) {
        return ['status' => false, 'message' => 'Beewave Access Key is not configured.'];
    }

    $payload['access_key'] = $access_key;
    $url = BEEWAVE_BASE_URL . $endpoint;

    $ch = curl_init();

    if ($method === 'GET') {
        $url .= '?' . http_build_query($payload);
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        error_log("Beewave API cURL Error: " . $error);
        return ['status' => false, 'message' => 'API connection failed. ' . $error];
    }

    if ($http_code >= 400) {
        error_log("Beewave API HTTP Error: " . $response);
        return ['status' => false, 'message' => 'API returned an error. Please check logs.'];
    }

    $decoded_response = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Beewave API JSON Decode Error: " . $response);
        return ['status' => false, 'message' => 'Failed to decode API response.'];
    }

    return $decoded_response;
}

/**
 * Generates a virtual account for a user.
 *
 * @param array $user_details An array containing name, phone, email, etc.
 * @return array The API response.
 */
function generate_virtual_account(array $user_details) {
    // Bank code for Paystack Titan Microfinance Bank as per Beewave docs example
    $payload = [
        'bank_code' => ["100039"],
        'name'      => $user_details['name'],
        'phone'     => $user_details['phone'],
        'email'     => $user_details['email'],
        'bvn'       => $user_details['bvn'] ?? null,
        'nin'       => $user_details['nin'] ?? null,
    ];

    // Remove null values to keep the payload clean
    $payload = array_filter($payload);

    return make_beewave_request('bank-transfer/virtual-account-numbers', $payload, 'POST');
}

/**
 * Fetches existing virtual accounts for a user.
 *
 * @param string $tracking_ref The tracking reference of the virtual account.
 * @return array The API response.
 */
function fetch_virtual_account($tracking_ref) {
    return make_beewave_request('bank-transfer/fetch-account-numbers', ['tracking_ref' => $tracking_ref], 'POST');
}

/**
 * Verifies a Nigerian bank account.
 *
 * @param string $account_number The account number to verify.
 * @param string $bank_code The bank code.
 * @return array The API response.
 */
function verify_bank_account_beewave($account_number, $bank_code) {
    $payload = [
        'secret_key' => $GLOBALS['app_settings']['beewave_secret_key'] ?? '',
        'account_number' => $account_number,
        'bank_code' => $bank_code,
    ];
    return make_beewave_request('bank-transfer/local-bank-verification', $payload, 'POST');
}

/**
 * Initiates a local bank transfer.
 *
 * @param array $transfer_details Details like enquiry_id, amount, etc.
 * @return array The API response.
 */
function transfer_funds_beewave(array $transfer_details) {
    $payload = [
        'secret_key' => $GLOBALS['app_settings']['beewave_secret_key'] ?? '',
        'encrypt_key' => $GLOBALS['app_settings']['beewave_encrypt_key'] ?? '',
        'enquiry_id' => $transfer_details['enquiry_id'],
        'account_number' => $transfer_details['account_number'],
        'bank_code' => $transfer_details['bank_code'],
        'amount' => $transfer_details['amount'],
        'narration' => $transfer_details['narration'],
    ];
    return make_beewave_request('bank-transfer/local-transfer', $payload, 'POST');
}
