<?php
if (!defined('ABSPATH')) exit;

class FFLBRO_Davidsons_Integration {
    
    public function __construct() {
        add_action('wp_ajax_upload_davidsons_csv', array($this, 'upload_csv'));
        add_action('wp_ajax_get_davidsons_inventory', array($this, 'get_inventory'));
    }
    
    public function upload_csv() {
        check_ajax_referer('fflbro_working_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => 'No file uploaded or upload error'));
        }
        
        $file = $_FILES['csv_file'];
        $csv_type = sanitize_text_field($_POST['csv_type'] ?? '1');
        
        // Read file
        $file_content = file_get_contents($file['tmp_name']);
        if ($file_content === false) {
            wp_send_json_error(array('message' => 'Could not read uploaded file'));
        }
        
        // Process based on type
        if ($csv_type === '1') {
            $result = $this->process_inventory_csv($file_content);
        } else {
            $result = $this->process_quantity_csv($file_content);
        }
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => $result['message'],
                'count' => $result['count']
            ));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }
    
    private function process_inventory_csv($content) {
        global $wpdb;
        $table = $wpdb->prefix . 'fflbro_products';
        
        $lines = explode("\n", $content);
        $headers = str_getcsv(array_shift($lines));
        
        $count = 0;
        $markup = floatval(get_option('fflbro_davidsons_markup', 15));
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $data = str_getcsv($line);
            if (count($data) < 3) continue;
            
            // Map CSV columns (adjust based on actual Davidsons format)
            $product = array(
                'distributor' => 'davidsons',
                'item_number' => $data[0] ?? '',
                'description' => $data[1] ?? '',
                'manufacturer' => $data[2] ?? '',
                'price' => floatval($data[3] ?? 0),
                'quantity' => intval($data[4] ?? 0),
                'updated_at' => current_time('mysql')
            );
            
            // Insert or update
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE distributor = 'davidsons' AND item_number = %s",
                $product['item_number']
            ));
            
            if ($exists) {
                $wpdb->update($table, $product, array('id' => $exists));
            } else {
                $wpdb->insert($table, $product);
            }
            
            $count++;
        }
        
        return array(
            'success' => true,
            'message' => "Processed $count products from inventory CSV",
            'count' => $count
        );
    }
    
    private function process_quantity_csv($content) {
        global $wpdb;
        $table = $wpdb->prefix . 'fflbro_products';
        
        $lines = explode("\n", $content);
        array_shift($lines); // Remove header
        
        $count = 0;
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $data = str_getcsv($line);
            if (count($data) < 2) continue;
            
            $item_number = $data[0] ?? '';
            $quantity = intval($data[1] ?? 0);
            
            $updated = $wpdb->update(
                $table,
                array('quantity' => $quantity, 'updated_at' => current_time('mysql')),
                array('distributor' => 'davidsons', 'item_number' => $item_number)
            );
            
            if ($updated) $count++;
        }
        
        return array(
            'success' => true,
            'message' => "Updated quantities for $count products",
            'count' => $count
        );
    }
    
    public function get_inventory() {
        check_ajax_referer('fflbro_working_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'fflbro_products';
        
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table WHERE distributor = 'davidsons'"
        );
        
        $last_updated = $wpdb->get_var(
            "SELECT MAX(updated_at) FROM $table WHERE distributor = 'davidsons'"
        );
        
        wp_send_json_success(array(
            'count' => intval($count),
            'last_updated' => $last_updated
        ));
    }
}

new FFLBRO_Davidsons_Integration();
