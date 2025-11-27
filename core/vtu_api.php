<?php
require_once 'config.php';

/**
 * Make a request to the VTU API
 *
 * @param string $endpoint The API endpoint to call (e.g., 'airtime.php')
 * @param array $data The data to send in the POST request
 * @return array The JSON-decoded response from the API
 */
function make_api_request($endpoint, $data) {
    $url = 'https://v6.datagifting.com.ng/web/api/' . $endpoint;

    // Add the API key to the request data
    $data['api_key'] = VTU_API_KEY;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Add timeout to prevent the script from hanging
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        // In a real app, you'd log this error
        error_log("API request failed: " . $error);
        return ['status' => 'error', 'desc' => 'API request failed: ' . $error];
    }

    $decoded_response = json_decode($response, true);

    // Check if json_decode failed
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Failed to decode API response: " . $response);
        return ['status' => 'error', 'desc' => 'Failed to decode API response.'];
    }

    return $decoded_response;
}

/**
 * Buy airtime
 *
 * @param string $network
 * @param string $phone_number
 * @param int $amount
 * @return array
 */
function buy_airtime($network, $phone_number, $amount) {
    return make_api_request('airtime.php', [
        'network' => $network,
        'phone_number' => $phone_number,
        'amount' => $amount
    ]);
}

/**
 * Buy data
 *
 * @param string $network
 * @param string $phone_number
 * @param string $type
 * @param string $quantity
 * @return array
 */
function buy_data($network, $phone_number, $type, $quantity) {
    return make_api_request('data.php', [
        'network' => $network,
        'phone_number' => $phone_number,
        'type' => $type,
        'quantity' => $quantity
    ]);
}

/**
 * Verify a Cable TV customer's details
 *
 * @param string $type The cable provider (e.g., 'dstv', 'gotv')
 * @param string $iuc_number The customer's IUC or smartcard number
 * @return array The API response
 */
function verify_cable_customer($type, $iuc_number) {
    return make_api_request('verify-cable.php', [
        'type' => $type,
        'iuc_number' => $iuc_number
    ]);
}

/**
 * Pay for a Cable TV subscription
 *
 * @param string $type The cable provider
 * @param string $iuc_number The customer's IUC number
 * @param string $package The selected package
 * @return array The API response
 */
function pay_cable_bill($type, $iuc_number, $package) {
    return make_api_request('cable.php', [
        'type' => $type,
        'iuc_number' => $iuc_number,
        'package' => $package
    ]);
}

/**
 * Verify an Electricity customer's details
 *
 * @param string $provider The electricity provider (e.g., 'ekedc')
 * @param string $meter_number The customer's meter number
 * @param string $type The meter type ('prepaid' or 'postpaid')
 * @return array The API response
 */
function verify_electricity_customer($provider, $meter_number, $type) {
    return make_api_request('verify-electric.php', [
        'provider' => $provider,
        'meter_number' => $meter_number,
        'type' => $type
    ]);
}

/**
 * Pay for an Electricity bill
 *
 * @param string $provider The electricity provider
 * @param string $meter_number The customer's meter number
 * @param string $type The meter type
 * @param int $amount The amount to pay
 * @return array The API response
 */
function pay_electricity_bill($provider, $meter_number, $type, $amount) {
    return make_api_request('electric.php', [
        'provider' => $provider,
        'meter_number' => $meter_number,
        'type' => $type,
        'amount' => $amount
    ]);
}

/**
 * Purchase an Exam Pin
 *
 * @param string $type The exam type (e.g., 'waec')
 * @param int $quantity The number of pins to purchase
 * @return array The API response
 */
function purchase_exam_pin($type, $quantity) {
    return make_api_request('exam.php', [
        'type' => $type,
        'quantity' => $quantity
    ]);
}

/**
 * Verify a Bank Account's details
 *
 * @param string $bank_code The bank code
 * @param string $account_number The customer's account number
 * @return array The API response, including an 'enquiry_id' on success
 */
function verify_bank_account($bank_code, $account_number) {
    return make_api_request('verify-bank.php', [
        'bank_code' => $bank_code,
        'account_number' => $account_number
    ]);
}

/**
 * Perform a Bank Transfer
 *
 * @param string $enquiry_id The ID from the verification step
 * @param string $bank_code
 * @param string $account_number
 * @param int $amount
 * @param string $narration
 * @return array The API response
 */
function transfer_funds($enquiry_id, $bank_code, $account_number, $amount, $narration) {
    return make_api_request('bank-transfer.php', [
        'enquiry_id' => $enquiry_id,
        'bank_code' => $bank_code,
        'account_number' => $account_number,
        'amount' => $amount,
        'narration' => $narration
    ]);
}
