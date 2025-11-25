<?php
// core/monnify_api.php

class MonnifyAPI {
    private $api_key;
    private $secret_key;
    private $base_url = 'https://api.monnify.com/api/v1'; // Use sandbox for testing
    private $token_cache_file = __DIR__ . '/../cache/monnify_token.json';

    public function __construct($api_key, $secret_key) {
        $this->api_key = $api_key;
        $this->secret_key = $secret_key;

        // Ensure cache directory exists
        if (!file_exists(dirname($this->token_cache_file))) {
            mkdir(dirname($this->token_cache_file), 0755, true);
        }
    }

    private function get_auth_token() {
        // Try to get token from cache first
        if (file_exists($this->token_cache_file)) {
            $cache = json_decode(file_get_contents($this->token_cache_file), true);
            if (isset($cache['token']) && isset($cache['expires_at']) && time() < $cache['expires_at']) {
                return $cache['token'];
            }
        }

        // If cache is invalid or expired, fetch a new token
        $auth_url = $this->base_url . '/auth/login';
        $credentials = base64_encode($this->api_key . ':' . $this->secret_key);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $auth_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Monnify Auth Error: " . $error);
            return false;
        }

        $result = json_decode($response, true);

        if (isset($result['responseBody']['accessToken'])) {
            $token = $result['responseBody']['accessToken'];
            $expires_in = $result['responseBody']['expiresIn']; // in seconds

            // Cache the new token with its expiration time
            $cache_data = [
                'token' => $token,
                'expires_at' => time() + ($expires_in - 120) // Refresh 2 minutes before expiry
            ];
            file_put_contents($this->token_cache_file, json_encode($cache_data));

            return $token;
        }

        error_log("Monnify Auth Failed: " . ($result['responseMessage'] ?? 'Unknown error'));
        return false;
    }

    private function send_request($endpoint, $method = 'POST', $data = []) {
        $token = $this->get_auth_token();
        if (!$token) {
            return ['status' => false, 'message' => 'Failed to authenticate with Monnify. Check admin settings.'];
        }

        $ch = curl_init();
        $url = $this->base_url . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ];

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
        curl_close($ch);

        if ($error) {
            error_log("Monnify API Request Error for endpoint {$endpoint}: " . $error);
            return ['status' => false, 'message' => 'API request failed: ' . $error];
        }

        return json_decode($response, true);
    }

    public function verify_bvn($bvn, $name, $dob, $mobileNo) {
        $endpoint = '/disbursements/bvn/verify';
        $data = [
            "bvn" => $bvn,
            "name" => $name,
            "dateOfBirth" => $dob, // YYYY-MM-DD
            "mobileNo" => $mobileNo
        ];
        return $this->send_request($endpoint, 'POST', $data);
    }

    public function verify_nin($nin) {
        // Placeholder for NIN verification
        $endpoint = '/v1/nin/verify'; // Fictional endpoint
        $data = ["nin" => $nin];
        return $this->send_request($endpoint, 'POST', $data);
    }
}
