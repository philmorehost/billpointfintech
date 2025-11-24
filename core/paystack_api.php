<?php
// core/paystack_api.php

class PaystackAPI {
    private $secret_key;
    private $base_url = 'https://api.paystack.co';

    public function __construct() {
        global $pdo;
        $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'paystack_secret_key'");
        $this->secret_key = $stmt->fetchColumn();
    }

    private function sendRequest($url, $method = 'GET', $data = []) {
        if (!$this->secret_key) {
            // Return an error if the key is not set
            return ['status' => false, 'message' => 'Paystack secret key is not configured.'];
        }

        $ch = curl_init();

        $headers = [
            'Authorization: Bearer ' . $this->secret_key,
            'Content-Type: application/json',
        ];

        curl_setopt($ch, CURLOPT_URL, $this->base_url . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 seconds timeout

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            // Handle cURL errors (e.g., connection timeout)
            return ['status' => false, 'message' => 'API request failed: ' . $error];
        }

        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Handle JSON decoding errors
            return ['status' => false, 'message' => 'Failed to decode API response.'];
        }

        return $result;
    }

    /**
     * Initialize a transaction
     * @param string $email User's email
     * @param int $amount Amount in kobo
     * @param string $reference Unique reference for the transaction
     * @return array API response
     */
    public function initializeTransaction($email, $amount, $reference) {
        $data = [
            'email' => $email,
            'amount' => $amount,
            'reference' => $reference,
            'callback_url' => get_base_url() . 'verify_payment.php',
        ];
        return $this->sendRequest('/transaction/initialize', 'POST', $data);
    }

    /**
     * Verify a transaction
     * @param string $reference Transaction reference
     * @return array API response
     */
    public function verifyTransaction($reference) {
        return $this->sendRequest('/transaction/verify/' . rawurlencode($reference));
    }

    /**
     * Fetches the list of supported banks from Paystack
     * @return array API response
     */
    public function getBankList() {
        // NGN only for now
        return $this->sendRequest('/bank?currency=NGN');
    }

    /**
     * Resolves a bank account number to get account holder's name
     * @param string $account_number
     * @param string $bank_code
     * @return array API response
     */
    public function resolveAccountNumber($account_number, $bank_code) {
        $query = http_build_query([
            'account_number' => $account_number,
            'bank_code' => $bank_code
        ]);
        return $this->sendRequest('/bank/resolve?' . $query);
    }
}
