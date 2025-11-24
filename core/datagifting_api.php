<?php
// core/datagifting_api.php

class DatagiftingAPI {
    private $api_key;
    private $base_url = 'https://v6.datagifting.com.ng/web/api/';

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    private function make_request($endpoint, $data) {
        $url = $this->base_url . $endpoint;
        $post_data = array_merge(['api_key' => $this->api_key], $data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            // cURL error
            $error_msg = curl_error($ch);
            curl_close($ch);
            return ['status' => 'error', 'desc' => "cURL Error: " . $error_msg];
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code != 200) {
            return ['status' => 'error', 'desc' => "HTTP Error: " . $http_code];
        }

        return json_decode($response, true);
    }

    public function purchase_airtime($network, $phone_number, $amount) {
        return $this->make_request('airtime.php', [
            'network' => $network,
            'phone_number' => $phone_number,
            'amount' => $amount
        ]);
    }

    public function purchase_data($network, $phone_number, $type, $quantity) {
        return $this->make_request('data.php', [
            'network' => $network,
            'phone_number' => $phone_number,
            'type' => $type,
            'quantity' => $quantity
        ]);
    }

    public function verify_cable_iuc($provider, $iuc_number) {
        return $this->make_request('verify-cable.php', [
            'type' => $provider,
            'iuc_number' => $iuc_number
        ]);
    }

    public function purchase_cable_plan($provider, $iuc_number, $package_code) {
        return $this->make_request('cable.php', [
            'type' => $provider,
            'iuc_number' => $iuc_number,
            'package' => $package_code
        ]);
    }

    public function verify_meter_number($provider, $meter_number, $type) {
        return $this->make_request('verify-electric.php', [
            'provider' => $provider,
            'meter_number' => $meter_number,
            'type' => $type
        ]);
    }

    public function purchase_electricity($provider, $meter_number, $type, $amount) {
        return $this->make_request('electric.php', [
            'provider' => $provider,
            'meter_number' => $meter_number,
            'type' => $type,
            'amount' => $amount
        ]);
    }

    public function purchase_exam_pin($type, $quantity) {
        return $this->make_request('exam.php', [
            'type' => $type,
            'quantity' => $quantity
        ]);
    }
}
