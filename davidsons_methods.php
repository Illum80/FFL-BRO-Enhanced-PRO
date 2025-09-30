    /**
     * Upload and process Davidsons CSV file
     */
    public function upload_davidsons_csv() {
        check_ajax_referer('fflbro_working_nonce', 'nonce');
        
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array("message" => "No file uploaded or upload error"));
        }
        
        $file = $_FILES['csv_file'];
        $allowed_types = array('text/csv', 'application/csv', 'text/xml', 'application/xml');
        
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array("message" => "Invalid file type. Please upload CSV or XML files only."));
        }
        
        $file_content = file_get_contents($file['tmp_name']);
        if ($file_content === false) {
            wp_send_json_error(array("message" => "Could not read uploaded file"));
        }
        
        $result = $this->process_davidsons_data($file_content, $file['name']);
        
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
     * Get Davidsons inventory count
     */
    public function get_davidsons_inventory() {
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
