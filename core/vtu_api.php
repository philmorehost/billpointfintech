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
