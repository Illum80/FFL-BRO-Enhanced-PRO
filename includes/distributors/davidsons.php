<?php
class FFLBRO_Davidsons_Integration {
    
    public function __construct() {
        add_action('wp_ajax_davidsons_upload_csv', array($this, 'upload_csv'));
        add_action('wp_ajax_davidsons_get_inventory', array($this, 'get_inventory'));
    }
    
    public function upload_csv() {
        check_ajax_referer('fflbro_working_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        if (empty($_FILES['csv_file'])) {
            wp_send_json_error('No file uploaded');
            return;
        }
        
        $csv_type = sanitize_text_field($_POST['csv_type'] ?? 'inventory');
        $file = $_FILES['csv_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('File upload error: ' . $file['error']);
            return;
        }
        
        if ($file['type'] !== 'text/csv' && !str_ends_with($file['name'], '.csv')) {
            wp_send_json_error('Invalid file type. Please upload a CSV file.');
            return;
        }
        
        $content = file_get_contents($file['tmp_name']);
        
        if ($csv_type === 'inventory') {
            $result = $this->process_inventory_csv($content);
        } else {
            $result = $this->process_quantity_csv($content);
        }
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => $result['message'],
                'count' => $result['count']
            ));
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    private function process_inventory_csv($content) {
        global $wpdb;
        $table = $wpdb->prefix . 'fflbro_products';
        
        $lines = explode("\n", $content);
        array_shift($lines);
        
        $count = 0;
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $data = str_getcsv($line);
            if (count($data) < 5) continue;
            
            $product_data = array(
                'distributor' => 'davidsons',
                'item_number' => $data[0] ?? '',
                'upc' => $data[1] ?? '',
                'description' => $data[2] ?? '',
                'price' => floatval($data[3] ?? 0),
                'quantity' => intval($data[4] ?? 0),
                'updated_at' => current_time('mysql')
            );
            
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE distributor = 'davidsons' AND item_number = %s",
                $product_data['item_number']
            ));
            
            if ($exists) {
                $wpdb->update($table, $product_data, array('id' => $exists));
            } else {
                $wpdb->insert($table, $product_data);
            }
            $count++;
        }
        
        return array('success' => true, 'message' => "Successfully imported $count products", 'count' => $count);
    }
    
    private function process_quantity_csv($content) {
        global $wpdb;
        $table = $wpdb->prefix . 'fflbro_products';
        
        $lines = explode("\n", $content);
        array_shift($lines);
        
        $count = 0;
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $data = str_getcsv($line);
            if (count($data) < 2) continue;
            
            $updated = $wpdb->update(
                $table,
                array('quantity' => intval($data[1] ?? 0), 'updated_at' => current_time('mysql')),
                array('distributor' => 'davidsons', 'item_number' => $data[0] ?? '')
            );
            if ($updated) $count++;
        }
        
        return array('success' => true, 'message' => "Updated $count products", 'count' => $count);
    }
    
    public function get_inventory() {
        check_ajax_referer('fflbro_working_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'fflbro_products';
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE distributor = 'davidsons'");
        $last_updated = $wpdb->get_var("SELECT MAX(updated_at) FROM $table WHERE distributor = 'davidsons'");
        
        wp_send_json_success(array(
            'count' => intval($count),
            'last_updated' => $last_updated ? date('M j, Y g:i A', strtotime($last_updated)) : 'Never'
        ));
    }
}

new FFLBRO_Davidsons_Integration();
