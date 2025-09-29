<?php
if (!defined("ABSPATH")) exit;

class LipseysAPI {
    private $api_base = "https://api.lipseys.com/api/Integration/";
    private $email;
    private $password;
    private $token;
    private $last_error;

    public function __construct($email, $password) {
        $this->email = $email;
        $this->password = $password;
    }

    public function authenticate() {
        $url = $this->api_base . "Authentication/Login";

        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'timeout' => 45,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array(
                'email' => $this->email,
                'password' => $this->password
            ))
        ));

        if (is_wp_error($response)) {
            $this->last_error = "Request failed: " . $response->get_error_message();
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code !== 200) {
            $this->last_error = "HTTP $status_code: $body";
            return false;
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->last_error = "Invalid JSON: " . json_last_error_msg();
            return false;
        }

        if (isset($data["token"])) {
            $this->token = $data["token"];
            return true;
        }

        $this->last_error = "No token found. Response: " . $body;
        return false;
    }

    public function test_connection() {
        if ($this->authenticate()) {
            return array("success" => true, "message" => "Authentication successful! Token received.", "token" => $this->token);
        } else {
            return array("success" => false, "message" => $this->last_error);
        }
    }

    public function get_token() {
        return $this->token;
    }

    public function test_sku_retrieval() {
        // First authenticate to get token
        if (!$this->authenticate()) {
            return array("success" => false, "message" => $this->last_error);
        }
        
        // Test with a known valid SKU
        $test_sku = "RU1022RB"; // Ruger 10/22 - commonly available
        $url = $this->api_base . "Items/PricingQuantityFeed";
        
        $response = wp_remote_get($url, array(
            'timeout' => 45,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->token
            )
        ));
        
        if (is_wp_error($response)) {
            $this->last_error = "SKU request failed: " . $response->get_error_message();
            return array("success" => false, "message" => $this->last_error);
        }
        
        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            $data = json_decode($body, true);
            return array(
                "success" => true, 
                "message" => "SKU retrieval successful! Found: " . $test_sku,
                "skus" => array($data)
            );
        } else {
            $this->last_error = "HTTP $status_code: $body";
            return array("success" => false, "message" => $this->last_error);
        }
    }
}
