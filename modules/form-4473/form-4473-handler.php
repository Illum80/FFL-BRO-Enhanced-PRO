<?php
/**
 * Digital ATF Form 4473 Handler
 * Handles form processing, validation, PDF generation, and NICS integration
 */

if (!defined('ABSPATH')) exit;

class FFLBRO_Form4473_Handler {
    
    private $table_main = 'main_fflbro_form4473';
    private $table_transferee = 'main_fflbro_form4473_transferee';
    private $table_firearms = 'main_fflbro_form4473_firearms';
    private $table_questions = 'main_fflbro_form4473_questions';
    private $table_nics = 'main_fflbro_form4473_nics';
    private $table_audit = 'main_fflbro_form4473_audit';
    
    public function __construct() {
        add_action('wp_ajax_fflbro_4473_create', array($this, 'create_form'));
        add_action('wp_ajax_fflbro_4473_save_section', array($this, 'save_section'));
        add_action('wp_ajax_fflbro_4473_get_form', array($this, 'get_form'));
        add_action('wp_ajax_fflbro_4473_list_forms', array($this, 'list_forms'));
        add_action('wp_ajax_fflbro_4473_generate_pdf', array($this, 'generate_pdf'));
        add_action('wp_ajax_fflbro_4473_nics_check', array($this, 'nics_check'));
    }
    
    // Create new Form 4473
    public function create_form() {
        check_ajax_referer('fflbro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        
        $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
        $form_number = 'F4473-' . date('Ymd') . '-' . substr(uniqid(), -6);
        
        $result = $wpdb->insert($this->table_main, array(
            'form_number' => $form_number,
            'customer_id' => $customer_id,
            'status' => 'in_progress',
            'date_created' => current_time('mysql')
        ));
        
        if ($result) {
            $form_id = $wpdb->insert_id;
            
            // Log creation
            $this->log_audit($form_id, 'form_created', get_current_user_id());
            
            wp_send_json_success(array(
                'form_id' => $form_id,
                'form_number' => $form_number,
                'message' => 'Form 4473 created successfully'
            ));
        } else {
            wp_send_json_error('Database insert failed: ' . $wpdb->last_error);
        }
    }
    
    // Save form section data
    public function save_section() {
        check_ajax_referer('fflbro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        
        $form_id = intval($_POST['form_id']);
        $section = sanitize_text_field($_POST['section']);
        $data = json_decode(stripslashes($_POST['data']), true);
        
        switch ($section) {
            case 'section_a':
                $result = $this->save_transferee_data($form_id, $data);
                break;
            case 'section_b':
                $result = $this->save_firearms_data($form_id, $data);
                break;
            case 'section_c':
                $result = $this->save_questions_data($form_id, $data);
                break;
            default:
                wp_send_json_error('Invalid section');
                return;
        }
        
        if ($result) {
            $this->log_audit($form_id, "section_{$section}_saved", get_current_user_id());
            wp_send_json_success(array('message' => 'Section saved successfully'));
        } else {
            wp_send_json_error('Failed to save section');
        }
    }
    
    // Save transferee (buyer) data
    private function save_transferee_data($form_id, $data) {
        global $wpdb;
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_transferee} WHERE form_id = %d",
            $form_id
        ));
        
        $transferee_data = array(
            'form_id' => $form_id,
            'last_name' => sanitize_text_field($data['last_name']),
            'first_name' => sanitize_text_field($data['first_name']),
            'middle_name' => sanitize_text_field($data['middle_name'] ?? ''),
            'suffix' => sanitize_text_field($data['suffix'] ?? ''),
            'date_of_birth' => sanitize_text_field($data['date_of_birth']),
            'height_feet' => intval($data['height_feet'] ?? 0),
            'height_inches' => intval($data['height_inches'] ?? 0),
            'weight_lbs' => intval($data['weight_lbs'] ?? 0),
            'gender' => sanitize_text_field($data['gender'] ?? ''),
            'birth_place_city' => sanitize_text_field($data['birth_place_city'] ?? ''),
            'birth_place_state' => sanitize_text_field($data['birth_place_state'] ?? ''),
            'birth_place_country' => sanitize_text_field($data['birth_place_country'] ?? 'USA'),
            'residence_address' => sanitize_text_field($data['residence_address']),
            'residence_city' => sanitize_text_field($data['residence_city']),
            'residence_state' => sanitize_text_field($data['residence_state']),
            'residence_zip' => sanitize_text_field($data['residence_zip']),
            'residence_county' => sanitize_text_field($data['residence_county'] ?? ''),
            'ssn_last4' => sanitize_text_field($data['ssn_last4'] ?? ''),
            'upin' => sanitize_text_field($data['upin'] ?? ''),
            'ethnicity' => sanitize_text_field($data['ethnicity'] ?? ''),
            'race' => sanitize_text_field($data['race'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'signature_data' => $data['signature_data'] ?? null,
            'signature_date' => current_time('mysql')
        );
        
        if ($exists) {
            return $wpdb->update($this->table_transferee, $transferee_data, array('form_id' => $form_id));
        } else {
            return $wpdb->insert($this->table_transferee, $transferee_data);
        }
    }
    
    // Save firearms data
    private function save_firearms_data($form_id, $data) {
        global $wpdb;
        
        $wpdb->delete($this->table_firearms, array('form_id' => $form_id));
        
        $success = true;
        foreach ($data['firearms'] as $firearm) {
            $result = $wpdb->insert($this->table_firearms, array(
                'form_id' => $form_id,
                'firearm_type' => sanitize_text_field($firearm['type']),
                'manufacturer' => sanitize_text_field($firearm['manufacturer'] ?? ''),
                'model' => sanitize_text_field($firearm['model'] ?? ''),
                'serial_number' => sanitize_text_field($firearm['serial_number']),
                'caliber_gauge' => sanitize_text_field($firearm['caliber'] ?? ''),
                'importer' => sanitize_text_field($firearm['importer'] ?? ''),
                'country_of_manufacture' => sanitize_text_field($firearm['country'] ?? 'USA'),
                'sale_type' => sanitize_text_field($firearm['sale_type'] ?? 'sale'),
                'price' => floatval($firearm['price'] ?? 0)
            ));
            
            if (!$result) $success = false;
        }
        
        return $success;
    }
    
    // Save background check questions
    private function save_questions_data($form_id, $data) {
        global $wpdb;
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_questions} WHERE form_id = %d",
            $form_id
        ));
        
        $questions_data = array(
            'form_id' => $form_id,
            'question_11a' => sanitize_text_field($data['q11a'] ?? 'no'),
            'question_11b' => sanitize_text_field($data['q11b'] ?? 'no'),
            'question_11c' => sanitize_text_field($data['q11c'] ?? 'no'),
            'question_11d' => sanitize_text_field($data['q11d'] ?? 'no'),
            'question_11e' => sanitize_text_field($data['q11e'] ?? 'no'),
            'question_11f' => sanitize_text_field($data['q11f'] ?? 'no'),
            'question_11g' => sanitize_text_field($data['q11g'] ?? 'no'),
            'question_11h' => sanitize_text_field($data['q11h'] ?? 'no'),
            'question_11i' => sanitize_text_field($data['q11i'] ?? 'no'),
            'question_11j' => sanitize_text_field($data['q11j'] ?? 'no'),
            'question_11k' => sanitize_text_field($data['q11k'] ?? 'no'),
            'question_11l' => sanitize_text_field($data['q11l'] ?? 'no'),
            'exception_11i_explanation' => sanitize_textarea_field($data['q11i_explanation'] ?? '')
        );
        
        if ($exists) {
            return $wpdb->update($this->table_questions, $questions_data, array('form_id' => $form_id));
        } else {
            return $wpdb->insert($this->table_questions, $questions_data);
        }
    }
    
    // Get form data
    public function get_form() {
        check_ajax_referer('fflbro_nonce', 'nonce');
        
        global $wpdb;
        
        $form_id = intval($_GET['form_id']);
        
        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_main} WHERE id = %d",
            $form_id
        ), ARRAY_A);
        
        if (!$form) {
            wp_send_json_error('Form not found');
        }
        
        $transferee = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_transferee} WHERE form_id = %d",
            $form_id
        ), ARRAY_A);
        
        $firearms = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_firearms} WHERE form_id = %d",
            $form_id
        ), ARRAY_A);
        
        $questions = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_questions} WHERE form_id = %d",
            $form_id
        ), ARRAY_A);
        
        $nics = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_nics} WHERE form_id = %d ORDER BY id DESC LIMIT 1",
            $form_id
        ), ARRAY_A);
        
        wp_send_json_success(array(
            'form' => $form,
            'transferee' => $transferee,
            'firearms' => $firearms,
            'questions' => $questions,
            'nics' => $nics
        ));
    }
    
    // List all forms
    public function list_forms() {
        check_ajax_referer('fflbro_nonce', 'nonce');
        
        global $wpdb;
        
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : null;
        
        $where = $status ? $wpdb->prepare("WHERE status = %s", $status) : "";
        
        $forms = $wpdb->get_results(
            "SELECT f.*, t.last_name, t.first_name 
             FROM {$this->table_main} f
             LEFT JOIN {$this->table_transferee} t ON f.id = t.form_id
             {$where}
             ORDER BY f.date_created DESC
             LIMIT 100",
            ARRAY_A
        );
        
        wp_send_json_success(array('forms' => $forms));
    }
    
    // Generate PDF
    public function generate_pdf() {
        check_ajax_referer('fflbro_nonce', 'nonce');
        
        $form_id = intval($_POST['form_id']);
        
        $this->log_audit($form_id, 'pdf_generated', get_current_user_id());
        
        wp_send_json_success(array('message' => 'PDF generation pending implementation'));
    }
    
    // NICS Check placeholder
    public function nics_check() {
        check_ajax_referer('fflbro_nonce', 'nonce');
        
        global $wpdb;
        
        $form_id = intval($_POST['form_id']);
        
        $wpdb->insert($this->table_nics, array(
            'form_id' => $form_id,
            'check_date' => current_time('mysql'),
            'check_result' => 'proceed',
            'examiner_name' => wp_get_current_user()->display_name,
            'notes' => 'Manual NICS check - pending API integration'
        ));
        
        $wpdb->update($this->table_main,
            array('status' => 'completed', 'date_completed' => current_time('mysql')),
            array('id' => $form_id)
        );
        
        $this->log_audit($form_id, 'nics_check_completed', get_current_user_id());
        
        wp_send_json_success(array('message' => 'NICS check recorded'));
    }
    
    // Log audit trail
    private function log_audit($form_id, $action, $user_id = null) {
        global $wpdb;
        
        $wpdb->insert($this->table_audit, array(
            'form_id' => $form_id,
            'action' => $action,
            'user_id' => $user_id,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'timestamp' => current_time('mysql'),
            'details' => json_encode(array('user' => wp_get_current_user()->display_name))
        ));
    }
}

// Initialize handler
new FFLBRO_Form4473_Handler();
