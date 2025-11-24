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
        $response = curl_exec($ch);
        curl_close($ch);

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
        // Placeholder for data purchase
        return $this->make_request('data.php', [
            'network' => $network,
            'phone_number' => $phone_number,
            'type' => $type,
            'quantity' => $quantity
        ]);
    }
}
