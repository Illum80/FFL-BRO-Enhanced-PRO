<?php
/**
 * Sports South API Integration Module
 * 
 * Features:
 * - Sports South API authentication and session management
 * - Real-time inventory sync and pricing
 * - Product search with regional pricing
 * - Southern region distributor specialization
 */

class FFL_BRO_Sports_South_API {
    
    private $api_base = 'https://api.sports-south.com/';
    private $dealer_id;
    private $api_key;
    private $session_token;
    private $token_expires;
    
    public function __construct() {
        $this->dealer_id = get_option('fflbro_sports_south_dealer_id', '');
        $this->api_key = get_option('fflbro_sports_south_api_key', '');
        $this->session_token = get_option('fflbro_sports_south_session_token', '');
        $this->token_expires = get_option('fflbro_sports_south_token_expires', 0);
        
        add_action('wp_ajax_fflbro_sports_south_authenticate', array($this, 'ajax_authenticate'));
        add_action('wp_ajax_fflbro_sports_south_sync_inventory', array($this, 'ajax_sync_inventory'));
        add_action('wp_ajax_fflbro_sports_south_search_products', array($this, 'ajax_search_products'));
    }
    
    /**
     * Authenticate with Sports South API
     */
    public function authenticate($dealer_id = null, $api_key = null) {
        if ($dealer_id) $this->dealer_id = $dealer_id;
        if ($api_key) $this->api_key = $api_key;
        
        $auth_data = array(
            'dealer_number' => $this->dealer_id,
            'api_key' => $this->api_key,
            'auth_type' => 'api_key'
        );
        
        $response = wp_remote_post($this->api_base . 'auth/login', array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'FFL-BRO-Enhanced-Pro/1.0'
            ),
            'body' => json_encode($auth_data)
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('Sports South API connection failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['session_token'])) {
            $this->session_token = $data['session_token'];
            $this->token_expires = time() + ($data['expires_in'] ?? 7200);
            
            update_option('fflbro_sports_south_dealer_id', $this->dealer_id);
            update_option('fflbro_sports_south_api_key', $this->api_key);
            update_option('fflbro_sports_south_session_token', $this->session_token);
            update_option('fflbro_sports_south_token_expires', $this->token_expires);
            
            return array(
                'success' => true,
                'message' => 'Sports South authentication successful',
                'token' => $this->session_token
            );
        } else {
            throw new Exception('Sports South authentication failed: ' . ($data['error_message'] ?? 'Unknown error'));
        }
    }
    
    /**
     * AJAX: Authenticate with Sports South
     */
    public function ajax_authenticate() {
        check_ajax_referer('fflbro_nonce', 'nonce');
        
        try {
            $dealer_id = sanitize_text_field($_POST['dealer_id'] ?? '');
            $api_key = sanitize_text_field($_POST['api_key'] ?? '');
            
            if (empty($dealer_id) || empty($api_key)) {
                throw new Exception('Dealer Number and API Key are required');
            }
            
            $result = $this->authenticate($dealer_id, $api_key);
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error('Sports South Authentication failed: ' . $e->getMessage());
        }
    }
}

// Initialize Sports South API
new FFL_BRO_Sports_South_API();
