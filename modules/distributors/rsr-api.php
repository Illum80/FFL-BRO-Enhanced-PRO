<?php
/**
 * RSR Group API Integration Module
 * 
 * Features:
 * - RSR API authentication and token management
 * - Real-time inventory sync
 * - Product search and pricing
 * - Order management integration
 * - Multi-distributor pricing comparison
 */

class FFL_BRO_RSR_API {
    
    private $api_base = 'https://api.rsrgroup.com/';
    private $dealer_id;
    private $api_key;
    private $access_token;
    private $token_expires;
    
    public function __construct() {
        $this->dealer_id = get_option('fflbro_rsr_dealer_id', '');
        $this->api_key = get_option('fflbro_rsr_api_key', '');
        $this->access_token = get_option('fflbro_rsr_access_token', '');
        $this->token_expires = get_option('fflbro_rsr_token_expires', 0);
        
        add_action('wp_ajax_fflbro_rsr_authenticate', array($this, 'ajax_authenticate'));
        add_action('wp_ajax_fflbro_rsr_sync_inventory', array($this, 'ajax_sync_inventory'));
        add_action('wp_ajax_fflbro_rsr_search_products', array($this, 'ajax_search_products'));
    }
    
    /**
     * Authenticate with RSR API
     */
    public function authenticate($dealer_id = null, $api_key = null) {
        if ($dealer_id) $this->dealer_id = $dealer_id;
        if ($api_key) $this->api_key = $api_key;
        
        $auth_data = array(
            'dealer_id' => $this->dealer_id,
            'api_key' => $this->api_key,
            'grant_type' => 'client_credentials'
        );
        
        $response = wp_remote_post($this->api_base . 'oauth/token', array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'body' => json_encode($auth_data)
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('RSR API connection failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['access_token'])) {
            $this->access_token = $data['access_token'];
            $this->token_expires = time() + ($data['expires_in'] ?? 3600);
            
            // Save credentials
            update_option('fflbro_rsr_dealer_id', $this->dealer_id);
            update_option('fflbro_rsr_api_key', $this->api_key);
            update_option('fflbro_rsr_access_token', $this->access_token);
            update_option('fflbro_rsr_token_expires', $this->token_expires);
            
            return array(
                'success' => true,
                'message' => 'RSR authentication successful',
                'token' => $this->access_token
            );
        } else {
            throw new Exception('RSR authentication failed: ' . ($data['error_description'] ?? 'Unknown error'));
        }
    }
    
    /**
     * Make authenticated API call
     */
    private function api_call($endpoint, $method = 'GET', $data = null) {
        // Check if token needs refresh
        if (time() > $this->token_expires - 300) { // Refresh 5 minutes early
            $this->authenticate();
        }
        
        $headers = array(
            'Authorization' => 'Bearer ' . $this->access_token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        );
        
        $args = array(
            'method' => $method,
            'timeout' => 45,
            'headers' => $headers
        );
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($this->api_base . $endpoint, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('RSR API call failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code >= 400) {
            throw new Exception('RSR API error: ' . ($result['message'] ?? 'HTTP ' . $status_code));
        }
        
        return $result;
    }
    
    /**
     * Search products in RSR catalog
     */
    public function search_products($search_params = array()) {
        $defaults = array(
            'keyword' => '',
            'manufacturer' => '',
            'category' => '',
            'limit' => 50,
            'offset' => 0
        );
        
        $params = array_merge($defaults, $search_params);
        $query_string = http_build_query(array_filter($params));
        
        $result = $this->api_call('v1/products?' . $query_string);
        
        $products = array();
        if (isset($result['data']) && is_array($result['data'])) {
            foreach ($result['data'] as $item) {
                $products[] = array(
                    'distributor' => 'RSR Group',
                    'sku' => $item['sku'] ?? '',
                    'upc' => $item['upc'] ?? '',
                    'manufacturer' => $item['manufacturer'] ?? '',
                    'model' => $item['model'] ?? '',
                    'description' => $item['description'] ?? '',
                    'category' => $item['category'] ?? '',
                    'price' => floatval($item['dealer_price'] ?? 0),
                    'msrp' => floatval($item['msrp'] ?? 0),
                    'quantity' => intval($item['quantity_available'] ?? 0),
                    'status' => $item['status'] ?? 'unknown',
                    'image_url' => $item['image_url'] ?? '',
                    'specifications' => $item['specifications'] ?? array()
                );
            }
        }
        
        return array(
            'success' => true,
            'products' => $products,
            'total' => $result['total'] ?? count($products),
            'page_info' => array(
                'limit' => $params['limit'],
                'offset' => $params['offset'],
                'has_more' => ($result['total'] ?? 0) > ($params['offset'] + $params['limit'])
            )
        );
    }
    
    /**
     * AJAX: Authenticate with RSR
     */
    public function ajax_authenticate() {
        check_ajax_referer('fflbro_nonce', 'nonce');
        
        try {
            $dealer_id = sanitize_text_field($_POST['dealer_id'] ?? '');
            $api_key = sanitize_text_field($_POST['api_key'] ?? '');
            
            if (empty($dealer_id) || empty($api_key)) {
                throw new Exception('Dealer ID and API Key are required');
            }
            
            $result = $this->authenticate($dealer_id, $api_key);
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error('RSR Authentication failed: ' . $e->getMessage());
        }
    }
}

// Initialize RSR API
new FFL_BRO_RSR_API();
