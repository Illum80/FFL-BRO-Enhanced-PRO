#!/bin/bash

# Find the line number where the fake sync function starts
START_LINE=$(grep -n "public function sync_lipseys_catalog" ffl-bro-enhanced-pro.php | cut -d: -f1)

# Find where it ends (next public function)
END_LINE=$(tail -n +$((START_LINE + 1)) ffl-bro-enhanced-pro.php | grep -n "public function" | head -1 | cut -d: -f1)
END_LINE=$((START_LINE + END_LINE))

echo "Replacing lines $START_LINE to $END_LINE with real sync function..."

# Create the real sync function
cat > real-sync-function.txt << 'EOF'
    public function sync_lipseys_catalog() {
        check_ajax_referer('fflbro_nonce', 'nonce');
        
        global $wpdb;
        
        // Authenticate with Lipseys
        $auth_response = wp_remote_post('https://api.lipseys.com/api/Integration/Authentication/Login', array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array(
                'Username' => 'jrneefe@gmail.com',
                'Password' => 'Rampone1214!'
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($auth_response)) {
            wp_send_json_error('Auth failed: ' . $auth_response->get_error_message());
        }
        
        $auth_data = json_decode(wp_remote_retrieve_body($auth_response), true);
        if (!isset($auth_data['token'])) {
            wp_send_json_error('No token received');
        }
        
        $token = $auth_data['token'];
        
        // Get catalog
        $catalog_response = wp_remote_get('https://api.lipseys.com/api/Integration/Items/CatalogFeed', array(
            'headers' => array('Token' => $token),
            'timeout' => 120
        ));
        
        if (is_wp_error($catalog_response)) {
            wp_send_json_error('Catalog failed: ' . $catalog_response->get_error_message());
        }
        
        $catalog_data = json_decode(wp_remote_retrieve_body($catalog_response), true);
        if (!$catalog_data['success']) {
            wp_send_json_error('Catalog API error');
        }
        
        $products = $catalog_data['data'];
        $total = count($products);
        
        // Clear and insert products
        $wpdb->delete($wpdb->prefix . 'fflbro_products', array('distributor' => 'lipseys'));
        
        $processed = 0;
        foreach ($products as $product) {
            $wpdb->insert($wpdb->prefix . 'fflbro_products', array(
                'distributor' => 'lipseys',
                'item_number' => $product['itemNo'] ?? '',
                'description' => trim(($product['description1'] ?? '') . ' ' . ($product['description2'] ?? '')),
                'manufacturer' => $product['manufacturer'] ?? '',
                'price' => floatval($product['currentPrice'] ?? 0),
                'quantity' => intval($product['quantity'] ?? 0)
            ));
            $processed++;
        }
        
        wp_send_json_success(array('message' => "Sync complete! $processed products imported.", 'total' => $total));
    }
EOF

# Replace the function (this is a simplified approach)
head -n $((START_LINE - 1)) ffl-bro-enhanced-pro.php > temp.php
cat real-sync-function.txt >> temp.php
tail -n +$((END_LINE)) ffl-bro-enhanced-pro.php >> temp.php
mv temp.php ffl-bro-enhanced-pro.php

rm real-sync-function.txt
echo "Real sync function implemented!"
