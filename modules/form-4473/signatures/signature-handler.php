<?php
/**
 * Digital Signature Handler for Form 4473
 * ATF Ruling 2016-2 Compliant
 * Version: 7.3.1
 */

if (!defined('ABSPATH')) exit;

class FFLBRO_Signature_Handler {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        register_rest_route('fflbro/v1', '/form-4473/signature/save', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_signature'),
            'permission_callback' => array($this, 'check_permissions')
        ));
    }
    
    public function save_signature($request) {
        global $wpdb;
        
        $params = $request->get_json_params();
        $form_id = intval($params['form_id']);
        $signature_data = $params['signature_data']; // Base64 PNG
        $signature_type = sanitize_text_field($params['signature_type']); // 'transferee' or 'dealer'
        
        // Validate signature data
        if (!$this->validate_signature($signature_data)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Invalid signature data'
            ), 400);
        }
        
        // Check if signature already exists (prevent reuse)
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}main_fflbro_form4473_signatures 
             WHERE form_id = %d AND signature_type = %s",
            $form_id, $signature_type
        ));
        
        if ($existing) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Signature already exists and cannot be changed'
            ), 400);
        }
        
        // Save signature with timestamp and metadata
        $result = $wpdb->insert(
            $wpdb->prefix . 'main_fflbro_form4473_signatures',
            array(
                'form_id' => $form_id,
                'signature_type' => $signature_type,
                'signature_data' => $signature_data,
                'signed_date' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            // Log audit trail
            $this->log_audit($form_id, 'signature_save', "Signature saved for $signature_type", array(
                'signature_type' => $signature_type,
                'ip' => $_SERVER['REMOTE_ADDR']
            ));
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Signature saved successfully',
                'signature_id' => $wpdb->insert_id
            ), 200);
        }
        
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Failed to save signature'
        ), 500);
    }
    
    private function validate_signature($data) {
        // Check if it's a valid base64 PNG
        if (strpos($data, 'data:image/png;base64,') !== 0) {
            return false;
        }
        
        // Remove header and decode
        $base64 = substr($data, strpos($data, ',') + 1);
        $decoded = base64_decode($base64, true);
        
        if ($decoded === false) {
            return false;
        }
        
        // Verify it's a valid PNG
        if (substr($decoded, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            return false;
        }
        
        return true;
    }
    
    private function log_audit($form_id, $action, $description, $data = array()) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'main_fflbro_form4473_audit',
            array(
                'form_id' => $form_id,
                'action' => $action,
                'description' => $description,
                'data_json' => json_encode($data),
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'created_at' => current_time('mysql')
            )
        );
    }
    
    public function check_permissions() {
        return current_user_can('manage_options') || current_user_can('edit_posts');
    }
}

new FFLBRO_Signature_Handler();
