<?php
require_once('../../../../wp-config.php');

$file = 'ffl-bro-enhanced-pro.php';
$content = file_get_contents($file);

// Replace the fake sync function with real API integration
$pattern = '/public function sync_lipseys_catalog\(\) \{.*?wp_send_json_success\(array\(\'message\' => \'Lipseys catalog sync completed! 16,887 items processed\.\'\)\);.*?\}/s';

$real_sync = '
    public function sync_lipseys_catalog() {
        check_ajax_referer(\'fflbro_nonce\', \'nonce\');
        
        global $wpdb;
        
        // Step 1: Authenticate with Lipseys
        $auth_response = wp_remote_post(\'https://api.lipseys.com/api/Integration/Authentication/Login\', array(
            \'headers\' => array(\'Content-Type\' => \'application/json\'),
            \'body\' => json_encode(array(
                \'Username\' => \'jrneefe@gmail.com\',
                \'Password\' => \'Rampone1214!\'
            )),
            \'timeout\' => 30
        ));
        
        if (is_wp_error($auth_response)) {
            wp_send_json_error(\'Authentication failed: \' . $auth_response->get_error_message());
        }
        
        $auth_body = wp_remote_retrieve_body($auth_response);
        $auth_data = json_decode($auth_body, true);
        
        if (!isset($auth_data[\'token\'])) {
            wp_send_json_error(\'Authentication failed - check credentials\');
        }
        
        $token = $auth_data[\'token\'];
        
        // Step 2: Fetch catalog using proven working method  
        $catalog_response = wp_remote_get(\'https://api.lipseys.com/api/Integration/Items/CatalogFeed\', array(
            \'headers\' => array(\'Token\' => $token),
            \'timeout\' => 120
        ));
        
        if (is_wp_error($catalog_response)) {
            wp_send_json_error(\'Catalog fetch failed: \' . $catalog_response->get_error_message());
        }
        
        $catalog_body = wp_remote_retrieve_body($catalog_response);
        $catalog_data = json_decode($catalog_body, true);
        
        if (!isset($catalog_data[\'success\']) || !$catalog_data[\'success\']) {
            wp_send_json_error(\'Catalog API error\');
        }
        
        $products = $catalog_data[\'data\'];
        $total_products = count($products);
        
        // Step 3: Clear existing Lipseys products
        $wpdb->delete($wpdb->prefix . \'fflbro_products\', array(\'distributor\' => \'lipseys\'));
        
        // Step 4: Insert products in batches
        $batch_size = 50;
        $processed = 0;
        
        for ($i = 0; $i < count($products); $i += $batch_size) {
            $batch = array_slice($products, $i, $batch_size);
            
            foreach ($batch as $product) {
                $result = $wpdb->insert(
                    $wpdb->prefix . \'fflbro_products\',
                    array(
                        \'distributor\' => \'lipseys\',
                        \'item_number\' => $product[\'itemNo\'] ?? \'\',
                        \'description\' => trim(($product[\'description1\'] ?? \'\') . \' \' . ($product[\'description2\'] ?? \'\')),
                        \'manufacturer\' => $product[\'manufacturer\'] ?? \'\',
                        \'price\' => floatval($product[\'currentPrice\'] ?? 0),
                        \'quantity\' => intval($product[\'quantity\'] ?? 0),
                        \'category\' => $product[\'category\'] ?? \'\',
                        \'created_at\' => current_time(\'mysql\'),
                        \'updated_at\' => current_time(\'mysql\')
                    ),
                    array(\'%s\', \'%s\', \'%s\', \'%s\', \'%f\', \'%d\', \'%s\', \'%s\', \'%s\')
                );
            }
            
            $processed += count($batch);
        }
        
        wp_send_json_success(array(
            \'message\' => "Lipseys sync completed! " . $processed . " products imported.",
            \'total\' => $total_products,
            \'processed\' => $processed
        ));
    }';

$content = preg_replace($pattern, $real_sync, $content);

file_put_contents($file, $content);
echo "✅ Real Lipseys API sync implemented with your credentials\n";
