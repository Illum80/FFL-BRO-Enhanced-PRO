<?php
/**
 * Form 4473 List Handler
 * REST API endpoint for listing forms
 */

if (!defined('ABSPATH')) exit;

class FFLBRO_Form4473_List_Handler {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        register_rest_route('fflbro/v1', '/form-4473/list', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_forms_list'),
            'permission_callback' => '__return_true'
        ));
    }
    
    public function get_forms_list($request) {
        global $wpdb;
        $table_name = 'main_fflbro_form4473';
        
        // Get all forms from database
        $forms = $wpdb->get_results("
            SELECT 
                id,
                form_number,
                date_created,
                status,
                transferee_info
            FROM {$table_name}
            ORDER BY date_created DESC
            LIMIT 100
        ", ARRAY_A);
        
        // Format the forms data
        $formatted_forms = array_map(function($form) {
            // Parse transferee_info JSON if it exists
            $transferee_name = null;
            if (!empty($form['transferee_info'])) {
                $transferee = json_decode($form['transferee_info'], true);
                if (isset($transferee['first_name']) && isset($transferee['last_name'])) {
                    $transferee_name = trim($transferee['first_name'] . ' ' . $transferee['last_name']);
                }
            }
            
            return array(
                'id' => $form['id'],
                'form_number' => $form['form_number'],
                'created_date' => $form['date_created'],
                'status' => $form['status'] ?: 'in_progress',
                'transferee_name' => $transferee_name
            );
        }, $forms);
        
        return rest_ensure_response(array(
            'status' => 'success',
            'data' => array(
                'forms' => $formatted_forms,
                'count' => count($formatted_forms)
            )
        ));
    }
}

new FFLBRO_Form4473_List_Handler();
