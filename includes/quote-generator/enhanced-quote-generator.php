<?php
/**
 * Enhanced Multi-Distributor Quote Generator Module
 * Version: 2.0.0
 * Uses real distributor data from Lipseys, RSR, and Davidsons
 */

if (!defined('ABSPATH')) exit;

class FFLBRO_Enhanced_Quote_Generator {
    
    private $supported_distributors = array('lipseys', 'rsr', 'davidsons');
    
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_fflbro_search_products', array($this, 'search_products'));
        add_action('wp_ajax_fflbro_save_quote', array($this, 'save_quote'));
        add_action('wp_ajax_fflbro_load_quote', array($this, 'load_quote'));
        
        // Register shortcode
        add_shortcode('fflbro_quote_generator', array($this, 'render_quote_generator'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    public function search_products() {
        check_ajax_referer('fflbro_working_nonce', 'nonce');
        
        global $wpdb;
        $search_term = sanitize_text_field($_POST['search'] ?? '');
        
        if (empty($search_term)) {
            wp_send_json_error(array('message' => 'Please provide search criteria'));
        }
        
        $table = $wpdb->prefix . 'fflbro_products';
        
        $query = $wpdb->prepare(
            "SELECT * FROM $table 
             WHERE (description LIKE %s OR manufacturer LIKE %s OR item_number LIKE %s)
             AND quantity > 0
             AND distributor IN ('lipseys', 'rsr', 'davidsons')
             ORDER BY distributor, price ASC 
             LIMIT 100",
            '%' . $wpdb->esc_like($search_term) . '%',
            '%' . $wpdb->esc_like($search_term) . '%',
            '%' . $wpdb->esc_like($search_term) . '%'
        );
        
        $products = $wpdb->get_results($query, ARRAY_A);
        
        if (empty($products)) {
            wp_send_json_error(array('message' => 'No products found'));
        }
        
        $grouped = $this->group_products($products);
        
        wp_send_json_success(array(
            'products' => $grouped,
            'total' => count($grouped)
        ));
    }
    
    private function group_products($products) {
        $grouped = array();
        
        foreach ($products as $product) {
            $key = strtolower(trim($product['manufacturer'] . ' ' . $product['description']));
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = array(
                    'id' => $product['item_number'],
                    'description' => $product['description'],
                    'manufacturer' => $product['manufacturer'],
                    'distributors' => array()
                );
            }
            
            $grouped[$key]['distributors'][$product['distributor']] = array(
                'distributor' => $product['distributor'],
                'item_number' => $product['item_number'],
                'price' => floatval($product['price']),
                'quantity' => intval($product['quantity'])
            );
        }
        
        $result = array();
        foreach ($grouped as $item) {
            $prices = array_column($item['distributors'], 'price');
            $item['best_price'] = !empty($prices) ? min($prices) : 0;
            $result[] = $item;
        }
        
        usort($result, function($a, $b) {
            return $a['best_price'] <=> $b['best_price'];
        });
        
        return $result;
    }
    
    public function save_quote() {
        check_ajax_referer('fflbro_working_nonce', 'nonce');
        
        global $wpdb;
        $quote_data = json_decode(stripslashes($_POST['quote_data'] ?? '{}'), true);
        
        if (empty($quote_data['items'])) {
            wp_send_json_error(array('message' => 'Quote has no items'));
        }
        
        $quote_number = 'Q' . date('Ymd') . '-' . wp_generate_password(6, false);
        
        $subtotal = 0;
        foreach ($quote_data['items'] as $item) {
            $subtotal += floatval($item['retail_price']) * intval($item['quantity']);
        }
        
        $tax_rate = floatval(get_option('fflbro_tax_rate', 0));
        $tax = $subtotal * ($tax_rate / 100);
        $total = $subtotal + $tax;
        
        $table = $wpdb->prefix . 'fflbro_quotes';
        $result = $wpdb->insert(
            $table,
            array(
                'quote_number' => $quote_number,
                'customer_name' => sanitize_text_field($quote_data['customer']['name'] ?? ''),
                'customer_email' => sanitize_email($quote_data['customer']['email'] ?? ''),
                'customer_phone' => sanitize_text_field($quote_data['customer']['phone'] ?? ''),
                'quote_data' => json_encode($quote_data),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
            )
        );
        
        if ($result) {
            wp_send_json_success(array(
                'quote_number' => $quote_number,
                'total' => $total
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save quote'));
        }
    }
    
    public function load_quote() {
        check_ajax_referer('fflbro_working_nonce', 'nonce');
        
        global $wpdb;
        $quote_number = sanitize_text_field($_POST['quote_number'] ?? '');
        
        if (empty($quote_number)) {
            wp_send_json_error(array('message' => 'Quote number required'));
        }
        
        $table = $wpdb->prefix . 'fflbro_quotes';
        $quote = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE quote_number = %s", $quote_number),
            ARRAY_A
        );
        
        if (!$quote) {
            wp_send_json_error(array('message' => 'Quote not found'));
        }
        
        $quote['quote_data'] = json_decode($quote['quote_data'], true);
        wp_send_json_success(array('quote' => $quote));
    }
    
    public function render_quote_generator($atts) {
        ob_start();
        ?>
        <div id="fflbro-quote-generator" class="fflbro-quote-generator-container">
            <div class="quote-generator-header">
                <h2>Smart Quote Generator</h2>
                <p>Search across Lipseys, RSR, and Davidsons for the best prices</p>
            </div>
            <div id="quote-generator-app"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function enqueue_assets() {
        global $post;
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'fflbro_quote_generator')) {
            return;
        }
        
        wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.production.min.js', array(), '18.0.0', true);
        wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', array('react'), '18.0.0', true);
        
        wp_enqueue_script(
            'fflbro-quote-generator',
            plugins_url('includes/quote-generator/quote-generator.js', dirname(dirname(__FILE__))),
            array('react', 'react-dom', 'jquery'),
            '2.0.0',
            true
        );
        
        wp_localize_script('fflbro-quote-generator', 'fflbroQuote', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fflbro_working_nonce'),
            'distributors' => $this->supported_distributors,
            'settings' => array(
                'lipseys_markup' => get_option('fflbro_lipseys_markup', 15),
                'rsr_markup' => get_option('fflbro_rsr_markup', 15),
                'davidsons_markup' => get_option('fflbro_davidsons_markup', 15),
                'tax_rate' => get_option('fflbro_tax_rate', 0)
            )
        ));
        
        wp_enqueue_style(
            'fflbro-quote-generator',
            plugins_url('includes/quote-generator/quote-generator.css', dirname(dirname(__FILE__))),
            array(),
            '2.0.0'
        );
    }
}

new FFLBRO_Enhanced_Quote_Generator();
