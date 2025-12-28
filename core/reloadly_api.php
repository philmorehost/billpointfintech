<?php
// core/reloadly_api.php

class ReloadlyAPI {
    private $client_id;
    private $client_secret;
    private $base_url = 'https://topups.reloadly.com'; // Use https://topups-sandbox.reloadly.com for testing
    private $auth_url = 'https://auth.reloadly.com/oauth/token';
    private $token_cache_file = __DIR__ . '/../cache/reloadly_token.json';

    public function __construct($client_id, $client_secret) {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;

        if (!file_exists(dirname($this->token_cache_file))) {
            mkdir(dirname($this->token_cache_file), 0755, true);
        }
    }

    private function get_auth_token() {
        if (file_exists($this->token_cache_file)) {
            $cache = json_decode(file_get_contents($this->token_cache_file), true);
            if (isset($cache['token']) && isset($cache['expires_at']) && time() < $cache['expires_at']) {
                return $cache['token'];
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->auth_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'client_credentials',
            'audience' => $this->base_url
        ]));

        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response, true);

        if (isset($result['access_token'])) {
            $token = $result['access_token'];
            $expires_in = $result['expires_in'];

            $cache_data = [
                'token' => $token,
                'expires_at' => time() + ($expires_in - 120)
            ];
            file_put_contents($this->token_cache_file, json_encode($cache_data));
            return $token;
        }
        return false;
    }

    private function send_request($endpoint, $method = 'GET', $data = []) {
        $token = $this->get_auth_token();
        if (!$token) {
            return ['status' => 'error', 'message' => 'Failed to authenticate with Reloadly.'];
        }

        $ch = curl_init();
        $url = $this->base_url . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/com.reloadly.topups-v1+json'
        ];

        if ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    public function auto_detect_operator($phone, $country_iso) {
        return $this->send_request("/operators/auto-detect/phone/{$phone}/countries/{$country_iso}");
    }

    public function send_topup($operator_id, $amount, $recipient_phone, $country_iso) {
        $data = [
            'operatorId' => $operator_id,
            'amount' => $amount,
            'recipientPhone' => [
                'countryCode' => $country_iso,
                'number' => $recipient_phone
            ],
            'useLocalAmount' => true,
        ];
        return $this->send_request('/topups', 'POST', $data);
    }
}
