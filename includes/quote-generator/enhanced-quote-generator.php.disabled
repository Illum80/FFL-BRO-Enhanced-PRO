<?php
/**
 * Enhanced Multi-Distributor Quote Generator Module
 * Version: 2.0.0 - ADMIN ONLY
 * Operator tool for creating quotes, not customer-facing
 */

if (!defined('ABSPATH')) exit;

class FFLBRO_Enhanced_Quote_Generator {
    
    private $supported_distributors = array('lipseys', 'rsr', 'davidsons');
    
    public function __construct() {
        // Add admin menu item
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register AJAX handlers (admin only)
        add_action('wp_ajax_fflbro_search_products', array($this, 'search_products'));
        add_action('wp_ajax_fflbro_save_quote', array($this, 'save_quote'));
        add_action('wp_ajax_fflbro_load_quote', array($this, 'load_quote'));
        
        // Enqueue scripts for admin only
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Add Quote Generator to FFL-BRO admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'fflbro-enhanced-pro',  // Parent menu slug
            'Quote Generator',       // Page title
            'Quote Generator',       // Menu title
            'manage_options',        // Capability
            'fflbro-quote-generator', // Menu slug
            array($this, 'render_admin_page') // Callback
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>📝 Smart Quote Generator</h1>
            <p class="description">Search across Lipseys, RSR, and Davidsons to create professional quotes for customers</p>
            
            <div id="fflbro-quote-generator" class="fflbro-quote-generator-container">
                <div id="quote-generator-app"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our quote generator page
        if ($hook !== 'ffl-bro-enhanced-pro_page_fflbro-quote-generator') {
            return;
        }
        
        // Enqueue React
        wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.production.min.js', array(), '18.0.0', true);
        wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', array('react'), '18.0.0', true);
        
        // Enqueue quote generator script
        wp_enqueue_script(
            'fflbro-quote-generator',
            plugins_url('includes/quote-generator/quote-generator.js', dirname(dirname(__FILE__))),
            array('react', 'react-dom', 'jquery'),
            '2.0.0',
            true
        );
        
        // Localize script
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
        
        // Enqueue styles
        wp_enqueue_style(
            'fflbro-quote-generator',
            plugins_url('includes/quote-generator/quote-generator.css', dirname(dirname(__FILE__))),
            array(),
            '2.0.0'
        );
    }
    
    /**
     * Search products across all distributors
     */
    public function search_products() {
        check_ajax_referer('fflbro_working_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
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
    
    /**
     * Group products by item
     */
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
    
    /**
     * Save quote to database
     */
    public function save_quote() {
        check_ajax_referer('fflbro_working_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
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
    
    /**
     * Load existing quote
     */
    public function load_quote() {
        check_ajax_referer('fflbro_working_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
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
}

// Auto-create quotes table on plugin load
add_action('plugins_loaded', 'fflbro_create_quotes_table_on_load');
function fflbro_create_quotes_table_on_load() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'fflbro_quotes';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quote_number VARCHAR(50) NOT NULL UNIQUE,
            customer_name VARCHAR(255),
            customer_email VARCHAR(255),
            customer_phone VARCHAR(50),
            quote_data LONGTEXT,
            subtotal DECIMAL(10,2),
            tax DECIMAL(10,2),
            total DECIMAL(10,2),
            status VARCHAR(50) DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME,
            INDEX(quote_number),
            INDEX(customer_email),
            INDEX(status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize
new FFLBRO_Enhanced_Quote_Generator();
