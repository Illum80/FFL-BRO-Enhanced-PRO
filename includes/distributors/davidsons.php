<?php
if (!defined('ABSPATH')) exit;

class FFLBRO_Davidsons_Integration {
    
    public function __construct() {
        add_action('wp_ajax_upload_davidsons_csv', array($this, 'upload_csv'));
        add_action('wp_ajax_get_davidsons_inventory', array($this, 'get_inventory'));
    }
    
    /**
     * Handle CSV file upload and processing
     */
    public function upload_csv() {
        check_ajax_referer('fflbro_working_nonce', 'nonce');
        
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array("message" => "No file uploaded or upload error"));
        }
        
        $file = $_FILES['csv_file'];
        $allowed_types = array('text/csv', 'application/csv', 'text/xml', 'application/xml');
        
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array("message" => "Invalid file type. Please upload CSV or XML files only."));
        }
        
        // Read and process file
        $file_content = file_get_contents($file['tmp_name']);
        if ($file_content === false) {
            wp_send_json_error(array("message" => "Could not read uploaded file"));
        }
        
        $result = $this->process_csv_data($file_content, $file['name']);
        
        if ($result['success']) {
            wp_send_json_success(array(
                "message" => "Processed " . $result['count'] . " products from " . $file['name'],
                "count" => $result['count']
            ));
        } else {
            wp_send_json_error(array("message" => $result['message']));
        }
    }
    
    /**
     * Process CSV data and import to database
     */
    private function process_csv_data($csv_content, $filename) {
        global $wpdb;
        
        $lines = str_getcsv($csv_content, "\n");
        if (empty($lines)) {
            return array('success' => false, 'message' => 'No data found in CSV file');
        }
        
        $headers = str_getcsv(array_shift($lines));
        $processed_count = 0;
        $markup_percent = floatval(get_option('fflbro_davidsons_markup', 15));
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $data = str_getcsv($line);
            if (count($data) < count($headers)) continue;
            
            $product = array_combine($headers, $data);
            
            // Extract product data (flexible field mapping)
            $item_number = $product['SKU'] ?? $product['Item'] ?? $product['ItemNumber'] ?? '';
            $description = $product['Description'] ?? $product['Name'] ?? '';
            $manufacturer = $product['Manufacturer'] ?? $product['Brand'] ?? '';
            $dealer_cost = floatval($product['DealerCost'] ?? $product['Cost'] ?? $product['Price'] ?? 0);
            $retail_price = $dealer_cost * (1 + ($markup_percent / 100));
            
            if (empty($item_number) || empty($description)) {
                continue;
            }
            
            // Insert/update in database
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}fflbro_products WHERE distributor = 'davidsons' AND item_number = %s",
                $item_number
            ));
            
            $product_data = array(
                'distributor' => 'davidsons',
                'item_number' => $item_number,
                'description' => $description,
                'manufacturer' => $manufacturer,
                'dealer_cost' => $dealer_cost,
                'retail_price' => $retail_price,
                'quantity' => intval($product['Quantity'] ?? $product['QtyOnHand'] ?? 1),
                'last_updated' => current_time('mysql'),
                'source_file' => $filename
            );
            
            if ($existing) {
                $wpdb->update($wpdb->prefix . 'fflbro_products', $product_data, array('id' => $existing->id));
            } else {
                $wpdb->insert($wpdb->prefix . 'fflbro_products', $product_data);
            }
            
            $processed_count++;
        }
        
        return array('success' => true, 'count' => $processed_count);
    }
    
    /**
     * Get Davidsons inventory count
     */
    public function get_inventory() {
        check_ajax_referer('fflbro_working_nonce', 'nonce');
        
        global $wpdb;
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_products WHERE distributor = 'davidsons'"
        );
        
        wp_send_json_success(array(
            "count" => intval($count),
            "message" => "Found " . number_format($count) . " Davidsons products"
        ));
    }
}

// Initialize the Davidsons integration
new FFLBRO_Davidsons_Integration();
