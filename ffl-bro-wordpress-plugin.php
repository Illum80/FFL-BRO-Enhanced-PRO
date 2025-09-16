<?php
/**
 * Plugin Name: FFL-BRO Platform
 * Plugin URI: https://ffl-bro.com
 * Description: Comprehensive FFL business management platform with GunBroker, Lipseys integration, quote generation, and Form 4473 processing
 * Version: 4.0.0
 * Author: FFL-BRO / NEEFECO
 * License: GPL v2 or later
 * Text Domain: ffl-bro
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FFL_BRO_VERSION', '4.0.0');
define('FFL_BRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FFL_BRO_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('FFL_BRO_PLUGIN_FILE', __FILE__);
define('FFL_BRO_DB_VERSION', '4.0.0');

/**
 * Main Plugin Class
 */
class FFL_BRO_Platform {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    public function init() {
        // Check requirements
        if (!$this->check_requirements()) {
            return;
        }
        
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize hooks
        $this->init_hooks();
        
        // Initialize admin
        if (is_admin()) {
            $this->init_admin();
        }
        
        // Initialize frontend
        $this->init_frontend();
    }
    
    private function check_requirements() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p><strong>FFL-BRO Platform:</strong> PHP 7.4 or higher is required. You are running ' . PHP_VERSION . '</p></div>';
            });
            return false;
        }
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p><strong>FFL-BRO Platform:</strong> WordPress 5.0 or higher is required.</p></div>';
            });
            return false;
        }
        
        return true;
    }
    
    private function load_dependencies() {
        require_once FFL_BRO_PLUGIN_PATH . 'includes/class-database.php';
        require_once FFL_BRO_PLUGIN_PATH . 'includes/class-api-manager.php';
        require_once FFL_BRO_PLUGIN_PATH . 'includes/class-settings.php';
        require_once FFL_BRO_PLUGIN_PATH . 'includes/class-ajax-handlers.php';
        require_once FFL_BRO_PLUGIN_PATH . 'includes/class-shortcodes.php';
        
        // API Integrations
        require_once FFL_BRO_PLUGIN_PATH . 'includes/apis/class-gunbroker-api.php';
        require_once FFL_BRO_PLUGIN_PATH . 'includes/apis/class-lipseys-api.php';
        require_once FFL_BRO_PLUGIN_PATH . 'includes/apis/class-rsr-api.php';
        require_once FFL_BRO_PLUGIN_PATH . 'includes/apis/class-davidsons-api.php';
    }
    
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(FFL_BRO_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(FFL_BRO_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_ffl_bro_get_products', array('FFL_BRO_Ajax_Handlers', 'get_products'));
        add_action('wp_ajax_ffl_bro_create_quote', array('FFL_BRO_Ajax_Handlers', 'create_quote'));
        add_action('wp_ajax_ffl_bro_sync_inventory', array('FFL_BRO_Ajax_Handlers', 'sync_inventory'));
        add_action('wp_ajax_ffl_bro_process_4473', array('FFL_BRO_Ajax_Handlers', 'process_4473'));
        
        // Public AJAX (for customer site)
        add_action('wp_ajax_nopriv_ffl_bro_get_products', array('FFL_BRO_Ajax_Handlers', 'get_products'));
        add_action('wp_ajax_nopriv_ffl_bro_create_quote', array('FFL_BRO_Ajax_Handlers', 'create_quote'));
        
        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Shortcodes
        FFL_BRO_Shortcodes::init();
    }
    
    private function init_admin() {
        require_once FFL_BRO_PLUGIN_PATH . 'includes/class-admin.php';
        FFL_BRO_Admin::init();
    }
    
    private function init_frontend() {
        // Frontend functionality
    }
    
    public function activate() {
        // Create database tables
        FFL_BRO_Database::create_tables();
        
        // Set default options
        $default_settings = array(
            'business_name' => 'Your FFL Business',
            'ffl_license' => '',
            'mock_mode' => true,
            'debug_mode' => false,
            'version' => FFL_BRO_VERSION
        );
        
        add_option('ffl_bro_settings', $default_settings);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Cleanup if needed
        flush_rewrite_rules();
    }
    
    public function enqueue_frontend_scripts() {
        // React and ReactDOM
        wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.production.min.js', array(), '18.0.0', true);
        wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', array('react'), '18.0.0', true);
        
        // Babel standalone for JSX transformation
        wp_enqueue_script('babel-standalone', 'https://unpkg.com/@babel/standalone/babel.min.js', array(), '7.0.0', true);
        
        // Main platform script
        wp_enqueue_script(
            'ffl-bro-platform',
            FFL_BRO_PLUGIN_URL . 'assets/js/platform.js',
            array('react', 'react-dom', 'babel-standalone', 'jquery'),
            FFL_BRO_VERSION,
            true
        );
        
        // Tailwind CSS
        wp_enqueue_style('tailwind-css', 'https://cdn.tailwindcss.com', array(), '3.3.0');
        
        // Custom styles
        wp_enqueue_style(
            'ffl-bro-styles',
            FFL_BRO_PLUGIN_URL . 'assets/css/platform.css',
            array(),
            FFL_BRO_VERSION
        );
        
        // Localize script data
        wp_localize_script('ffl-bro-platform', 'fflBroData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ffl_bro_nonce'),
            'apiUrl' => rest_url('ffl-bro/v1/'),
            'pluginUrl' => FFL_BRO_PLUGIN_URL,
            'settings' => get_option('ffl_bro_settings', array()),
            'mockMode' => get_option('ffl_bro_mock_mode', true)
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'ffl-bro') === false) {
            return;
        }
        
        $this->enqueue_frontend_scripts();
        
        // Admin-specific scripts
        wp_enqueue_script(
            'ffl-bro-admin',
            FFL_BRO_PLUGIN_URL . 'assets/js/admin.js',
            array('ffl-bro-platform'),
            FFL_BRO_VERSION,
            true
        );
    }
    
    public function register_rest_routes() {
        register_rest_route('ffl-bro/v1', '/products', array(
            'methods' => 'GET',
            'callback' => array('FFL_BRO_API_Manager', 'get_products'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('ffl-bro/v1', '/quotes', array(
            'methods' => 'POST',
            'callback' => array('FFL_BRO_API_Manager', 'create_quote'),
            'permission_callback' => array($this, 'check_api_permissions')
        ));
        
        register_rest_route('ffl-bro/v1', '/sync/(?P<provider>[\w]+)', array(
            'methods' => 'POST',
            'callback' => array('FFL_BRO_API_Manager', 'sync_provider'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));
    }
    
    public function check_api_permissions() {
        return current_user_can('edit_posts');
    }
    
    public function check_admin_permissions() {
        return current_user_can('manage_options');
    }
}

/**
 * Database Management Class
 */
class FFL_BRO_Database {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Quotes table
        $quotes_table = $wpdb->prefix . 'ffl_bro_quotes';
        $quotes_sql = "CREATE TABLE $quotes_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            quote_number varchar(50) NOT NULL,
            customer_name varchar(255),
            customer_email varchar(255),
            customer_phone varchar(50),
            customer_address text,
            items longtext,
            totals longtext,
            settings longtext,
            notes text,
            status enum('draft','sent','accepted','expired') DEFAULT 'draft',
            valid_until date,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY quote_number (quote_number),
            KEY customer_email (customer_email),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Form 4473 table
        $form4473_table = $wpdb->prefix . 'ffl_bro_form4473';
        $form4473_sql = "CREATE TABLE $form4473_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            form_number varchar(50) NOT NULL,
            transferee_data longtext,
            firearm_data longtext,
            prohibited_answers longtext,
            signature_data longtext,
            nics_status enum('pending','approved','denied','delayed') DEFAULT 'pending',
            status enum('draft','completed','archived') DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY form_number (form_number),
            KEY nics_status (nics_status),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Inventory table
        $inventory_table = $wpdb->prefix . 'ffl_bro_inventory';
        $inventory_sql = "CREATE TABLE $inventory_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            sku varchar(100) NOT NULL,
            upc varchar(50),
            name varchar(255) NOT NULL,
            manufacturer varchar(255),
            model varchar(255),
            category varchar(100),
            caliber varchar(50),
            msrp decimal(10,2),
            cost decimal(10,2),
            stock_local int DEFAULT 0,
            distributor_data longtext,
            gunbroker_data longtext,
            last_synced datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY sku (sku),
            KEY upc (upc),
            KEY manufacturer (manufacturer),
            KEY category (category),
            KEY last_synced (last_synced)
        ) $charset_collate;";
        
        // API Settings table
        $api_settings_table = $wpdb->prefix . 'ffl_bro_api_settings';
        $api_settings_sql = "CREATE TABLE $api_settings_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            provider varchar(50) NOT NULL,
            enabled tinyint(1) DEFAULT 0,
            credentials longtext,
            settings longtext,
            last_sync datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY provider (provider),
            KEY enabled (enabled),
            KEY last_sync (last_sync)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($quotes_sql);
        dbDelta($form4473_sql);
        dbDelta($inventory_sql);
        dbDelta($api_settings_sql);
        
        // Update database version
        update_option('ffl_bro_db_version', FFL_BRO_DB_VERSION);
    }
}

/**
 * Admin Interface Class
 */
class FFL_BRO_Admin {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }
    
    public static function add_admin_menu() {
        // Main menu
        add_menu_page(
            'FFL-BRO Platform',
            'FFL-BRO',
            'manage_options',
            'ffl-bro',
            array(__CLASS__, 'admin_page'),
            'dashicons-store',
            25
        );
        
        // Submenus
        add_submenu_page(
            'ffl-bro',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'ffl-bro',
            array(__CLASS__, 'admin_page')
        );
        
        add_submenu_page(
            'ffl-bro',
            'Quote Generator',
            'Quotes',
            'edit_posts',
            'ffl-bro-quotes',
            array(__CLASS__, 'admin_page')
        );
        
        add_submenu_page(
            'ffl-bro',
            'GunBroker',
            'GunBroker',
            'edit_posts',
            'ffl-bro-gunbroker',
            array(__CLASS__, 'admin_page')
        );
        
        add_submenu_page(
            'ffl-bro',
            'Lipseys',
            'Lipseys',
            'edit_posts',
            'ffl-bro-lipseys',
            array(__CLASS__, 'admin_page')
        );
        
        add_submenu_page(
            'ffl-bro',
            'Form 4473',
            'Form 4473',
            'edit_posts',
            'ffl-bro-form4473',
            array(__CLASS__, 'admin_page')
        );
        
        add_submenu_page(
            'ffl-bro',
            'Settings',
            'Settings',
            'manage_options',
            'ffl-bro-settings',
            array(__CLASS__, 'admin_page')
        );
    }
    
    public static function admin_page() {
        $current_page = $_GET['page'] ?? 'ffl-bro';
        $module = str_replace('ffl-bro-', '', $current_page);
        if ($module === 'ffl-bro') $module = 'dashboard';
        
        echo '<div class="wrap">';
        echo '<div id="ffl-bro-admin-app" data-module="' . esc_attr($module) . '"></div>';
        echo '</div>';
    }
    
    public static function register_settings() {
        register_setting('ffl_bro_settings', 'ffl_bro_business_settings');
        register_setting('ffl_bro_settings', 'ffl_bro_api_settings');
        register_setting('ffl_bro_settings', 'ffl_bro_pricing_settings');
        register_setting('ffl_bro_settings', 'ffl_bro_system_settings');
    }
}

/**
 * Shortcodes Class
 */
class FFL_BRO_Shortcodes {
    
    public static function init() {
        add_shortcode('ffl_bro_platform', array(__CLASS__, 'platform_shortcode'));
        add_shortcode('ffl_bro_quote_generator', array(__CLASS__, 'quote_generator_shortcode'));
        add_shortcode('ffl_bro_form4473', array(__CLASS__, 'form4473_shortcode'));
    }
    
    public static function platform_shortcode($atts) {
        $atts = shortcode_atts(array(
            'module' => 'dashboard',
            'height' => 'auto',
            'customer_mode' => 'false'
        ), $atts);
        
        return '<div id="ffl-bro-platform-app" data-module="' . esc_attr($atts['module']) . '" data-customer-mode="' . esc_attr($atts['customer_mode']) . '" style="height: ' . esc_attr($atts['height']) . '"></div>';
    }
    
    public static function quote_generator_shortcode($atts) {
        $atts = shortcode_atts(array(
            'customer_mode' => 'true'
        ), $atts);
        
        return '<div id="ffl-bro-quote-generator" data-customer-mode="' . esc_attr($atts['customer_mode']) . '"></div>';
    }
    
    public static function form4473_shortcode($atts) {
        return '<div id="ffl-bro-form4473"></div>';
    }
}

/**
 * AJAX Handlers Class
 */
class FFL_BRO_Ajax_Handlers {
    
    public static function get_products() {
        check_ajax_referer('ffl_bro_nonce', 'nonce');
        
        $search = sanitize_text_field($_POST['search'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        
        // Get products from database or API
        $products = self::fetch_products($search, $category);
        
        wp_send_json_success($products);
    }
    
    public static function create_quote() {
        check_ajax_referer('ffl_bro_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $quote_data = $_POST['quote_data'] ?? array();
        
        // Validate and sanitize quote data
        $quote_data = self::sanitize_quote_data($quote_data);
        
        // Save quote to database
        $quote_id = self::save_quote($quote_data);
        
        if ($quote_id) {
            wp_send_json_success(array('quote_id' => $quote_id));
        } else {
            wp_send_json_error('Failed to create quote');
        }
    }
    
    public static function sync_inventory() {
        check_ajax_referer('ffl_bro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        
        // Sync inventory from specified provider
        $result = FFL_BRO_API_Manager::sync_provider_inventory($provider);
        
        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error('Sync failed');
        }
    }
    
    public static function process_4473() {
        check_ajax_referer('ffl_bro_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $form_data = $_POST['form_data'] ?? array();
        
        // Process and save Form 4473 data
        $form_id = self::save_form_4473($form_data);
        
        if ($form_id) {
            wp_send_json_success(array('form_id' => $form_id));
        } else {
            wp_send_json_error('Failed to process form');
        }
    }
    
    private static function fetch_products($search = '', $category = '') {
        // Mock data for now
        return array(
            array(
                'id' => 1,
                'sku' => 'GLK-19-GEN5',
                'name' => 'Glock 19 Gen 5 9mm',
                'manufacturer' => 'Glock',
                'msrp' => 649.99,
                'distributors' => array(
                    array('name' => 'RSR Group', 'price' => 435.25, 'stock' => 12),
                    array('name' => 'Lipseys', 'price' => 438.90, 'stock' => 15)
                )
            )
        );
    }
    
    private static function sanitize_quote_data($data) {
        // Sanitize quote data
        return array(
            'customer_name' => sanitize_text_field($data['customer_name'] ?? ''),
            'customer_email' => sanitize_email($data['customer_email'] ?? ''),
            'items' => array_map('sanitize_text_field', $data['items'] ?? array())
        );
    }
    
    private static function save_quote($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ffl_bro_quotes';
        
        $result = $wpdb->insert(
            $table,
            array(
                'quote_number' => 'Q' . date('Y') . '-' . str_pad(wp_rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'items' => json_encode($data['items']),
                'status' => 'draft'
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    private static function save_form_4473($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ffl_bro_form4473';
        
        $result = $wpdb->insert(
            $table,
            array(
                'form_number' => '4473-' . date('Y') . '-' . str_pad(wp_rand(1, 999999), 6, '0', STR_PAD_LEFT),
                'transferee_data' => json_encode($data['transferee'] ?? array()),
                'firearm_data' => json_encode($data['firearms'] ?? array()),
                'status' => 'draft'
            ),
            array('%s', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
}

/**
 * API Manager Class
 */
class FFL_BRO_API_Manager {
    
    public static function sync_provider_inventory($provider) {
        switch ($provider) {
            case 'gunbroker':
                return self::sync_gunbroker();
            case 'lipseys':
                return self::sync_lipseys();
            case 'rsr':
                return self::sync_rsr();
            case 'davidsons':
                return self::sync_davidsons();
            default:
                return false;
        }
    }
    
    private static function sync_gunbroker() {
        // GunBroker API integration
        return array('synced' => 0, 'message' => 'GunBroker sync not implemented yet');
    }
    
    private static function sync_lipseys() {
        // Lipseys API integration
        return array('synced' => 0, 'message' => 'Lipseys sync not implemented yet');
    }
    
    private static function sync_rsr() {
        // RSR API integration
        return array('synced' => 0, 'message' => 'RSR sync not implemented yet');
    }
    
    private static function sync_davidsons() {
        // Davidsons API integration
        return array('synced' => 0, 'message' => 'Davidsons sync not implemented yet');
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    FFL_BRO_Platform::get_instance();
});

// Activation hook
register_activation_hook(__FILE__, function() {
    FFL_BRO_Platform::get_instance()->activate();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    FFL_BRO_Platform::get_instance()->deactivate();
});
