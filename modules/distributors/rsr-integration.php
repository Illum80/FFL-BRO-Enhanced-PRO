<?php
/**
 * Enhanced RSR Group FTP Integration for FFL-BRO Enhanced PRO v7.0.0
 * Live implementation with TLS encryption, 77-field format, and Google Drive category mappings
 * Account: 67271 / NEEFECO ARMS
 */

class FFLBRO_RSR_Integration {
    
    // RSR FTP Credentials (Account #67271)
    private $ftp_host = 'ftps.rsrgroup.com';
    private $ftp_port = 2222;
    private $ftp_user = '67271';
    private $ftp_pass = 'h1dtuW5J';
    private $ftp_mode = 'explicit_tls';
    
    // File paths on RSR server
    private $inventory_file = 'fulfillment-inv-new.txt';
    private $attributes_file = 'attributes-all.txt';
    private $restrictions_file = 'rsr-ship-restrictions.txt';
    
    public function __construct() {
        add_action('wp_ajax_fflbro_sync_rsr_catalog', array($this, 'sync_rsr_catalog'));
        add_action('wp_ajax_fflbro_test_rsr_connection', array($this, 'test_rsr_connection'));
        add_action('fflbro_rsr_sync_cron', array($this, 'automated_sync'));
    }
    
    /**
     * Test RSR FTP connection with TLS encryption
     */
    public function test_rsr_connection() {
        check_ajax_referer('fflbro_nonce', 'nonce');
        
        try {
            // Connect with TLS encryption
            $ftp = ftp_ssl_connect($this->ftp_host, $this->ftp_port, 30);
            
            if (!$ftp) {
                throw new Exception('Failed to connect to RSR FTP server with TLS');
            }
            
            // Login
            if (!ftp_login($ftp, $this->ftp_user, $this->ftp_pass)) {
                ftp_close($ftp);
                throw new Exception('RSR FTP authentication failed');
            }
            
            // Enable passive mode
            ftp_pasv($ftp, true);
            
            // Get file list to verify connection
            $files = ftp_nlist($ftp, '.');
            ftp_close($ftp);
            
            if (!$files) {
                throw new Exception('Connected but unable to list files');
            }
            
            $available_files = array_filter($files, function($file) {
                return in_array($file, [
                    'fulfillment-inv-new.txt',
                    'rsrinventory-new.txt', 
                    'attributes-all.txt',
                    'rsr-ship-restrictions.txt'
                ]);
            });
            
            wp_send_json_success([
                'message' => 'RSR FTP connection successful!',
                'files_found' => count($available_files),
                'available_files' => $available_files,
                'connection_type' => 'FTP over TLS (Explicit)',
                'server' => $this->ftp_host . ':' . $this->ftp_port,
                'account' => $this->ftp_user . ' (NEEFECO ARMS)'
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error('RSR connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Main RSR catalog sync function with progress tracking
     */
    public function sync_rsr_catalog() {
        check_ajax_referer('fflbro_nonce', 'nonce');
        
        global $wpdb;
        $stats = [
            'processed' => 0,
            'created' => 0, 
            'updated' => 0,
            'errors' => 0,
            'start_time' => time()
        ];
        
        try {
            // Download inventory file
            $local_file = $this->download_rsr_file($this->inventory_file);
            
            if (!$local_file || !file_exists($local_file)) {
                throw new Exception('Failed to download RSR inventory file');
            }
            
            // Clear existing RSR products
            $deleted = $wpdb->query("DELETE FROM {$wpdb->prefix}fflbro_products WHERE distributor = 'rsr'");
            
            // Process file in chunks with progress updates
            $this->process_rsr_inventory_file($local_file, function($products) use (&$stats, $wpdb) {
                foreach ($products as $product) {
                    try {
                        $result = $this->import_rsr_product($product);
                        if ($result) {
                            $stats['created']++;
                        }
                        $stats['processed']++;
                        
                        // Send progress every 250 products
                        if ($stats['processed'] % 250 == 0) {
                            // Non-blocking progress update
                            echo "data: " . json_encode([
                                'type' => 'progress',
                                'processed' => $stats['processed'],
                                'created' => $stats['created'],
                                'message' => 'Processing RSR products: ' . number_format($stats['processed'])
                            ]) . "\n\n";
                            flush();
                        }
                        
                    } catch (Exception $e) {
                        $stats['errors']++;
                        error_log('RSR Product Import Error: ' . $e->getMessage());
                    }
                }
            });
            
            // Cleanup
            @unlink($local_file);
            
            $stats['duration'] = time() - $stats['start_time'];
            
            wp_send_json_success([
                'message' => sprintf(
                    'RSR sync complete! %s products processed, %s created, %s errors in %s minutes',
                    number_format($stats['processed']),
                    number_format($stats['created']), 
                    number_format($stats['errors']),
                    round($stats['duration'] / 60, 1)
                ),
                'stats' => $stats,
                'deleted_old' => $deleted
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error('RSR sync failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Download file from RSR FTP server with TLS
     */
    private function download_rsr_file($remote_file) {
        $ftp = ftp_ssl_connect($this->ftp_host, $this->ftp_port, 30);
        
        if (!$ftp || !ftp_login($ftp, $this->ftp_user, $this->ftp_pass)) {
            throw new Exception('RSR FTP connection failed');
        }
        
        ftp_pasv($ftp, true);
        
        // Create local temp file
        $upload_dir = wp_upload_dir();
        $local_file = $upload_dir['path'] . '/rsr_' . time() . '.txt';
        
        if (!ftp_get($ftp, $local_file, $remote_file, FTP_BINARY)) {
            ftp_close($ftp);
            throw new Exception("Failed to download $remote_file from RSR");
        }
        
        ftp_close($ftp);
        return $local_file;
    }
    
    /**
     * Process RSR inventory file with streaming (77-field format)
     */
    private function process_rsr_inventory_file($file_path, $callback) {
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            throw new Exception("Cannot open RSR file: $file_path");
        }
        
        $chunk = [];
        $count = 0;
        $chunk_size = 500;
        
        while (($row = fgetcsv($handle, 0, ';')) !== FALSE) {
            // Skip empty rows or invalid data
            if (empty($row[0]) || count($row) < 77) {
                continue;
            }
            
            // Map RSR 77-field format to product array
            $product = [
                'rsr_stock_number' => trim($row[0]),
                'upc' => trim($row[1]),
                'description' => trim($row[2]),
                'department_number' => intval($row[3]),
                'manufacturer_id' => trim($row[4]),
                'retail_price' => floatval($row[5]),
                'rsr_price' => floatval($row[6]),
                'weight_oz' => floatval($row[7]),
                'quantity' => intval($row[8]),
                'model' => trim($row[9]),
                'manufacturer_name' => trim($row[10]),
                'manufacturer_part_number' => trim($row[11]),
                'status' => trim($row[12]),
                'expanded_description' => trim($row[13]),
                'image_name' => trim($row[14]),
                
                // State restrictions (fields 15-65: AK through WY)
                'state_restrictions' => array_slice($row, 15, 51),
                
                // New fields in 77-field format
                'ground_shipments_only' => trim($row[66]) === 'Y',
                'adult_signature_required' => trim($row[67]) === 'Y', 
                'blocked_from_dropship' => trim($row[68]) === 'Y',
                'date_entered' => trim($row[69]),
                'retail_map' => floatval($row[70]),
                'image_disclaimer' => trim($row[71]) === 'Y',
                'shipping_length' => floatval($row[72]),
                'shipping_width' => floatval($row[73]),
                'shipping_height' => floatval($row[74]),
                'prop_65' => trim($row[75]) === 'Y',
                'vendor_approval_required' => trim($row[76])
            ];
            
            // Skip deleted items
            if ($product['status'] === 'Deleted' || $product['quantity'] == 0) {
                continue;
            }
            
            $chunk[] = $product;
            $count++;
            
            if ($count >= $chunk_size) {
                call_user_func($callback, $chunk);
                $chunk = [];
                $count = 0;
                set_time_limit(30); // Reset timeout
            }
        }
        
        // Process remaining items
        if (!empty($chunk)) {
            call_user_func($callback, $chunk);
        }
        
        fclose($handle);
    }
    
    /**
     * Import single RSR product to database
     */
    private function import_rsr_product($product) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'fflbro_products';
        
        // Use Google Drive category mappings
        $category = $this->get_category_from_department($product['department_number']);
        
        // Calculate markup (configurable)
        $markup_percentage = get_option('fflbro_rsr_markup', 15);
        $retail_price = $product['rsr_price'] * (1 + ($markup_percentage / 100));
        
        // Prepare state restrictions as JSON
        $state_codes = [
            'AK', 'AL', 'AR', 'AZ', 'CA', 'CO', 'CT', 'DC', 'DE', 'FL',
            'GA', 'HI', 'IA', 'ID', 'IL', 'IN', 'KS', 'KY', 'LA', 'MA',
            'MD', 'ME', 'MI', 'MN', 'MO', 'MS', 'MT', 'NC', 'ND', 'NE',
            'NH', 'NJ', 'NM', 'NV', 'NY', 'OH', 'OK', 'OR', 'PA', 'RI',
            'SC', 'SD', 'TN', 'TX', 'UT', 'VA', 'VT', 'WA', 'WI', 'WV', 'WY'
        ];
        
        $restrictions = [];
        foreach ($state_codes as $i => $state) {
            if (isset($product['state_restrictions'][$i]) && trim($product['state_restrictions'][$i]) === 'Y') {
                $restrictions[] = $state;
            }
        }
        
        $data = [
            'distributor' => 'rsr',
            'item_number' => $product['rsr_stock_number'],
            'upc' => $product['upc'],
            'description' => $product['description'],
            'manufacturer' => $product['manufacturer_name'],
            'model' => $product['model'],
            'msrp' => $product['retail_price'],
            'dealer_price' => $product['rsr_price'],
            'price' => $retail_price,
            'category' => $category,
            'department' => $product['department_number'],
            'weight' => $product['weight_oz'],
            'quantity' => $product['quantity'],
            'in_stock' => ($product['quantity'] > 0) ? 1 : 0,
            'image_url' => $this->get_rsr_image_url($product['image_name']),
            'status' => $product['status'],
            'state_restrictions' => json_encode($restrictions),
            'shipping_restrictions' => json_encode([
                'ground_only' => $product['ground_shipments_only'],
                'adult_signature' => $product['adult_signature_required'],
                'no_dropship' => $product['blocked_from_dropship'],
                'prop_65' => $product['prop_65']
            ]),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $result = $wpdb->insert($table, $data);
        
        if ($result === false) {
            throw new Exception('Database insert failed: ' . $wpdb->last_error);
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Enhanced category mapping from Google Drive data
     */
    private function get_category_from_department($dept_num) {
        // Google Drive category mappings (from your RSR_Catalog folder)
        $departments = [
            1 => 'Handguns',
            2 => 'Handguns', // Used Handguns -> Handguns
            3 => 'Long Guns', // Used Long Guns -> Long Guns
            4 => 'Tasers',
            5 => 'Long Guns', // Sporting Long Guns -> Long Guns
            6 => 'NFA Products',
            7 => 'Black Powder Firearms',
            8 => 'Optics', // Scopes -> Optics
            9 => 'Optical Accessories', // Scope Mounts -> Optical Accessories
            10 => 'Magazines',
            11 => 'Grips, Pads, Stocks, Bipods',
            12 => 'Soft Gun Cases, Packs, Bags',
            13 => 'Misc. Accessories',
            14 => 'Holsters & Pouches',
            15 => 'Reloading Supplies',
            16 => 'Black Powder Accessories',
            17 => 'Misc. Accessories', // Closeout Accessories -> Misc. Accessories
            18 => 'Ammunition',
            19 => 'Survival & Camping Supplies',
            20 => 'Lights, Lasers, & Batteries',
            21 => 'Cleaning Equipment',
            22 => 'Airguns',
            23 => 'Knives & Tools',
            24 => 'Magazines', // High Capacity Magazines -> Magazines
            25 => 'Safes & Security',
            26 => 'Safety & Protection',
            27 => 'Non-Lethal Defense',
            28 => 'Optics', // Binoculars -> Optics
            29 => 'Optics', // Spotting Scopes -> Optics
            30 => 'Sights',
            31 => 'Optical Accessories',
            32 => 'Barrels, Choke Tubes, & Muzzle Devices',
            33 => 'Clothing',
            34 => 'Parts',
            35 => 'Slings & Swivels',
            36 => 'Electronics',
            38 => 'Books, Software, & DVDs',
            39 => 'Targets',
            40 => 'Hard Gun Cases',
            41 => 'Upper Receivers & Conversion Kits',
            42 => 'SBR Barrels & Upper Receivers',
            43 => 'Upper Receivers & Conversion Kits' // High Cap -> Standard
        ];
        
        return isset($departments[$dept_num]) ? $departments[$dept_num] : 'Uncategorized';
    }
    
    /**
     * Generate RSR image URL
     */
    private function get_rsr_image_url($image_name) {
        if (empty($image_name)) {
            return '';
        }
        
        // For now, return placeholder - implement your CDN strategy
        return "https://via.placeholder.com/300x200/CCCCCC/666666?text=" . urlencode($image_name);
    }
    
    /**
     * Setup automated sync schedule
     */
    public function setup_automation() {
        if (!wp_next_scheduled('fflbro_rsr_sync_cron')) {
            wp_schedule_event(time(), 'daily', 'fflbro_rsr_sync_cron');
        }
    }
    
    /**
     * Remove automated sync schedule
     */
    public function remove_automation() {
        wp_clear_scheduled_hook('fflbro_rsr_sync_cron');
    }
    
    /**
     * Automated sync (called by cron)
     */
    public function automated_sync() {
        if (get_option('fflbro_rsr_auto_sync', false)) {
            // Create minimal context for cron
            $_REQUEST['nonce'] = wp_create_nonce('fflbro_nonce');
            $this->sync_rsr_catalog();
        }
    }
}

// Initialize RSR integration
$GLOBALS['fflbro_rsr'] = new FFLBRO_RSR_Integration();
