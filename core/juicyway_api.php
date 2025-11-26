<?php
// core/juicyway_api.php

class JuicyWayAPI {
    private $api_key;
    private $secret_key;
    private $base_url = 'https://api.juicyway.com/v1'; // Example URL, adjust as needed

    public function __construct($api_key, $secret_key) {
        $this->api_key = $api_key;
        $this->secret_key = $secret_key;
    }

    private function send_request($endpoint, $method = 'GET', $data = []) {
        $ch = curl_init();
        $url = $this->base_url . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $this->secret_key, // Assuming Bearer token auth
            'Content-Type: application/json',
            'X-API-KEY: ' . $this->api_key
        ];

        if ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            error_log("JuicyWay API Request Error: " . $error);
            return ['status' => false, 'message' => 'API request failed: ' . $error];
        }

        if ($http_code >= 400) {
            error_log("JuicyWay API HTTP Error: " . $http_code . " | Response: " . $response);
             return json_decode($response, true); // Return error response from API
        }

        return json_decode($response, true);
    }

    public function get_supported_pairs() {
        return $this->send_request('/exchange/rates/list-supported-pairs');
    }

    public function get_exchange_rate($from, $to) {
        return $this->send_request('/exchange/rates/convert', 'GET', ['from' => $from, 'to' => $to]);
    }

    public function create_conversion($from_currency, $to_currency, $amount) {
        $data = [
            'fromCurrency' => $from_currency,
            'toCurrency' => $to_currency,
            'amount' => $amount
        ];
        return $this->send_request('/exchange/conversions', 'POST', $data);
    }

    public function create_global_transfer($data) {
        // Data should be an associative array matching JuicyWay's requirements
        // e.g., ['beneficiary' => [...], 'amount' => 100, 'currency' => 'USD', ...]
        return $this->send_request('/transfers', 'POST', $data);
    }

    public function create_exchange_rate($pair, $rate) {
        $data = [
            'pair' => $pair,
            'rate' => $rate,
        ];
        return $this->send_request('/exchange/rates', 'POST', $data);
    }

    public function delete_exchange_rate($rate_id) {
        return $this->send_request('/exchange/rates/' . $rate_id, 'DELETE');
    }

    public function fetch_exchange_rate($rate_id) {
        return $this->send_request('/exchange/rates/' . $rate_id);
    }

    public function list_exchange_rates() {
        return $this->send_request('/exchange/rates');
    }

    public function update_exchange_rate($rate_id, $rate) {
        $data = [
            'rate' => $rate,
        ];
        return $this->send_request('/exchange/rates/' . $rate_id, 'PATCH', $data);
    }
}
