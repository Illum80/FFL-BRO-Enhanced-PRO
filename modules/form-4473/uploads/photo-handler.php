<?php
/**
 * Photo ID Upload Handler for Form 4473
 * Version: 7.3.1
 */

if (!defined('ABSPATH')) exit;

class FFLBRO_Photo_Handler {
    
    private $upload_dir;
    
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->upload_dir = $upload_dir['basedir'] . '/form-4473-photos/';
        
        // Create upload directory
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
            // Protect directory
            file_put_contents($this->upload_dir . '.htaccess', 'Deny from all');
            file_put_contents($this->upload_dir . 'index.php', '<?php // Silence is golden');
        }
        
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        register_rest_route('fflbro/v1', '/form-4473/upload-id', array(
            'methods' => 'POST',
            'callback' => array($this, 'upload_photo'),
            'permission_callback' => array($this, 'check_permissions')
        ));
    }
    
    public function upload_photo($request) {
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        if (!$form_id) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Form ID required'
            ), 400);
        }
        
        if (!isset($_FILES['id_photo'])) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'No photo uploaded'
            ), 400);
        }
        
        $file = $_FILES['id_photo'];
        
        // Validate file
        $validation = $this->validate_upload($file);
        if ($validation !== true) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $validation
            ), 400);
        }
        
        // Generate secure filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'form-' . $form_id . '-id-' . time() . '.' . $extension;
        $filepath = $this->upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Update database
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'main_fflbro_form4473_transferee',
                array('photo_id_path' => $filepath),
                array('form_id' => $form_id)
            );
            
            // Log audit
            $this->log_audit($form_id, 'photo_uploaded', 'Government ID photo uploaded', array(
                'filename' => $filename,
                'filesize' => filesize($filepath)
            ));
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Photo uploaded successfully',
                'filename' => $filename,
                'filepath' => $filepath
            ), 200);
        }
        
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Failed to save photo'
        ), 500);
    }
    
    private function validate_upload($file) {
        // Check for errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'Upload error: ' . $file['error'];
        }
        
        // Check file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            return 'File too large. Maximum 5MB allowed.';
        }
        
        // Check file type
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png');
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            return 'Invalid file type. Only JPG and PNG allowed.';
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

new FFLBRO_Photo_Handler();
