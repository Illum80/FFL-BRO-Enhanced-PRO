<?php
/**
 * NICS Background Check Handler for Form 4473
 * FBI E-Check Integration Framework
 * Version: 7.3.1
 */

if (!defined('ABSPATH')) exit;

class FFLBRO_NICS_Handler {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        register_rest_route('fflbro/v1', '/form-4473/nics/check', array(
            'methods' => 'POST',
            'callback' => array($this, 'initiate_check'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route('fflbro/v1', '/form-4473/nics/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'check_status'),
            'permission_callback' => array($this, 'check_permissions')
        ));
    }
    
    public function initiate_check($request) {
        $params = $request->get_json_params();
        $form_id = intval($params['form_id']);
        
        if (!$form_id) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Form ID required'
            ), 400);
        }
        
        // Get form data
        global $wpdb;
        $transferee = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}main_fflbro_form4473_transferee WHERE form_id = %d",
            $form_id
        ), ARRAY_A);
        
        if (!$transferee) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Transferee data not found'
            ), 404);
        }
        
        // TODO: Integrate with FBI E-Check API when available
        // For now, create placeholder NICS record
        $transaction_number = 'NICS-' . time() . '-' . rand(1000, 9999);
        
        $wpdb->insert(
            $wpdb->prefix . 'main_fflbro_form4473_nics',
            array(
                'form_id' => $form_id,
                'transaction_number' => $transaction_number,
                'check_date' => current_time('mysql'),
                'result' => 'pending',
                'created_at' => current_time('mysql')
            )
        );
        
        // Log audit
        $this->log_audit($form_id, 'nics_initiated', 'NICS background check initiated', array(
            'transaction_number' => $transaction_number
        ));
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'NICS check initiated',
            'transaction_number' => $transaction_number,
            'status' => 'pending'
        ), 200);
    }
    
    public function check_status($request) {
        $form_id = $request->get_param('form_id');
        
        if (!$form_id) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Form ID required'
            ), 400);
        }
        
        global $wpdb;
        $nics = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}main_fflbro_form4473_nics WHERE form_id = %d ORDER BY id DESC LIMIT 1",
            $form_id
        ), ARRAY_A);
        
        if (!$nics) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'No NICS check found for this form'
            ), 404);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'nics' => $nics
        ), 200);
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

new FFLBRO_NICS_Handler();
