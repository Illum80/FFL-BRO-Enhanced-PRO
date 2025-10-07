<?php
/**
 * Form 4473 List Handler
 * REST API endpoints for listing and creating forms
 */

if (!defined('ABSPATH')) exit;

class FFLBRO_Form4473_List_Handler {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        // List forms
        register_rest_route('fflbro/v1', '/form-4473/list', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_forms_list'),
            'permission_callback' => '__return_true'
        ));
        
        // Create form
        register_rest_route('fflbro/v1', '/form-4473/create', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_form'),
            'permission_callback' => '__return_true'
        ));
        
        // Get single form
        register_rest_route('fflbro/v1', '/form-4473/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_form'),
            'permission_callback' => '__return_true'
        ));
        
        // Update form
        register_rest_route('fflbro/v1', '/form-4473/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_form'),
            'permission_callback' => '__return_true'
        ));
    }
    
    public function get_forms_list($request) {
        global $wpdb;
        $table_name = 'main_fflbro_form4473';
        
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
        
        $formatted_forms = array_map(function($form) {
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
    
    public function create_form($request) {
        global $wpdb;
        $table_name = 'main_fflbro_form4473';
        
        // Generate form number
        $form_number = 'ATF-4473-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        
        // Insert new form
        $result = $wpdb->insert($table_name, array(
            'form_number' => $form_number,
            'status' => 'in_progress',
            'date_created' => current_time('mysql')
        ));
        
        if ($result === false) {
            return new WP_Error('create_failed', 'Failed to create form', array('status' => 500));
        }
        
        $form_id = $wpdb->insert_id;
        
        return rest_ensure_response(array(
            'status' => 'success',
            'data' => array(
                'form_id' => $form_id,
                'form_number' => $form_number
            )
        ));
    }
    
    public function get_form($request) {
        global $wpdb;
        $table_name = 'main_fflbro_form4473';
        $form_id = $request['id'];
        
        $form = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$table_name} WHERE id = %d
        ", $form_id), ARRAY_A);
        
        if (!$form) {
            return new WP_Error('not_found', 'Form not found', array('status' => 404));
        }
        
        // Parse JSON fields
        $form['transferee_info'] = !empty($form['transferee_info']) ? json_decode($form['transferee_info'], true) : null;
        $form['firearm_info'] = !empty($form['firearm_info']) ? json_decode($form['firearm_info'], true) : null;
        $form['background_check'] = !empty($form['background_check']) ? json_decode($form['background_check'], true) : null;
        
        return rest_ensure_response(array(
            'status' => 'success',
            'data' => array('form' => $form)
        ));
    }
    
    public function update_form($request) {
        global $wpdb;
        $table_name = 'main_fflbro_form4473';
        $form_id = $request['id'];
        
        $body = json_decode($request->get_body(), true);
        
        $update_data = array();
        
        if (isset($body['transferee_info'])) {
            $update_data['transferee_info'] = json_encode($body['transferee_info']);
        }
        if (isset($body['firearm_info'])) {
            $update_data['firearm_info'] = json_encode($body['firearm_info']);
        }
        if (isset($body['background_check'])) {
            $update_data['background_check'] = json_encode($body['background_check']);
        }
        if (isset($body['status'])) {
            $update_data['status'] = $body['status'];
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_data', 'No data to update', array('status' => 400));
        }
        
        $result = $wpdb->update($table_name, $update_data, array('id' => $form_id));
        
        if ($result === false) {
            return new WP_Error('update_failed', 'Failed to update form', array('status' => 500));
        }
        
        return rest_ensure_response(array(
            'status' => 'success',
            'data' => array('form_id' => $form_id)
        ));
    }
}

new FFLBRO_Form4473_List_Handler();
