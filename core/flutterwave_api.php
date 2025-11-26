<?php
// core/flutterwave_api.php

class FlutterwaveAPI {
    private $secret_key;
    private $base_url = 'https://api.flutterwave.com/v3';

    public function __construct($secret_key) {
        $this->secret_key = $secret_key;
    }

    private function send_request($endpoint, $method = 'POST', $data = []) {
        $ch = curl_init();
        $url = $this->base_url . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $this->secret_key,
            'Content-Type: application/json',
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
            error_log("Flutterwave API Request Error: " . $error);
            return ['status' => 'error', 'message' => 'API request failed: ' . $error];
        }

        $decoded_response = json_decode($response, true);
        if ($http_code >= 400) {
            error_log("Flutterwave API HTTP Error: " . $http_code . " | Response: " . $response);
            return $decoded_response;
        }

        return $decoded_response;
    }

    public function create_virtual_account($email, $bvn, $firstname, $lastname, $tx_ref) {
        $endpoint = '/virtual-account-numbers';
        $data = [
            'email' => $email,
            'bvn' => $bvn,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'tx_ref' => $tx_ref,
            'is_permanent' => true,
        ];
        return $this->send_request($endpoint, 'POST', $data);
    }
}
