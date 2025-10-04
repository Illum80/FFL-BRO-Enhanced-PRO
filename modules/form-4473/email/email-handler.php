<?php
/**
 * Email Delivery Handler for Form 4473
 * Version: 7.3.1
 */

if (!defined('ABSPATH')) exit;

class FFLBRO_Email_Handler {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        register_rest_route('fflbro/v1', '/form-4473/(?P<id>\d+)/email', array(
            'methods' => 'POST',
            'callback' => array($this, 'send_email'),
            'permission_callback' => array($this, 'check_permissions')
        ));
    }
    
    public function send_email($request) {
        $form_id = $request['id'];
        
        // Get form data
        global $wpdb;
        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}main_fflbro_form4473 WHERE id = %d",
            $form_id
        ), ARRAY_A);
        
        if (!$form) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Form not found'
            ), 404);
        }
        
        // Get transferee email
        $transferee = $wpdb->get_row($wpdb->prepare(
            "SELECT email FROM {$wpdb->prefix}main_fflbro_form4473_transferee WHERE form_id = %d",
            $form_id
        ), ARRAY_A);
        
        if (!$transferee || empty($transferee['email'])) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'No email address on file'
            ), 400);
        }
        
        $to = $transferee['email'];
        $subject = 'Your ATF Form 4473 - ' . $form['form_number'];
        
        // Build email content
        $message = $this->build_email_content($form);
        
        // Get PDF attachment if exists
        $attachments = array();
        if (!empty($form['pdf_path']) && file_exists($form['pdf_path'])) {
            $attachments[] = $form['pdf_path'];
        }
        
        // Send email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($to, $subject, $message, $headers, $attachments);
        
        if ($sent) {
            // Log audit
            $this->log_audit($form_id, 'email_sent', 'Form emailed to transferee', array(
                'email' => $to,
                'has_attachment' => !empty($attachments)
            ));
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Email sent successfully',
                'recipient' => $to
            ), 200);
        }
        
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Failed to send email'
        ), 500);
    }
    
    private function build_email_content($form) {
        $html = '<html><body>';
        $html .= '<h2>ATF Form 4473 - Firearms Transaction Record</h2>';
        $html .= '<p><strong>Form Number:</strong> ' . htmlspecialchars($form['form_number']) . '</p>';
        $html .= '<p><strong>Date:</strong> ' . htmlspecialchars($form['created_at']) . '</p>';
        $html .= '<hr>';
        $html .= '<p>Your completed ATF Form 4473 is attached to this email for your records.</p>';
        $html .= '<p>Please keep this document for your files.</p>';
        $html .= '<hr>';
        $html .= '<p><em>This is an automated message from FFL-BRO Enhanced PRO v7.3.1</em></p>';
        $html .= '</body></html>';
        
        return $html;
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

new FFLBRO_Email_Handler();
