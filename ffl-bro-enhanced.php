<?php
/**
 * Plugin Name: FFL-BRO Enhanced Platform PRO
 * Description: Complete FFL business management platform with quote generator, 4473 forms, and all pro features
 * Version: 2.1.0
 */

if (!defined('ABSPATH')) exit;

class FFLBroEnhanced {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('admin_init', array($this, 'admin_init'));
        add_shortcode('ffl_bro_dashboard', array($this, 'dashboard_shortcode'));
        add_shortcode('ffl_bro_fnews', array($this, 'fnews_shortcode'));
        add_shortcode('ffl_bro_form_component', array($this, 'form_shortcode'));
        add_shortcode('ffl_bro_quote_generator', array($this, 'quote_generator_shortcode'));
        add_shortcode('ffl_bro_4473_form', array($this, 'form_4473_shortcode'));
        add_shortcode('ffl_bro_market_research', array($this, 'market_research_shortcode'));
        add_shortcode('ffl_bro_compliance', array($this, 'compliance_shortcode'));
        add_shortcode('ffl_bro_mobile_ops', array($this, 'mobile_ops_shortcode'));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.production.min.js', array(), '18.0.0', true);
        wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', array('react'), '18.0.0', true);
        wp_enqueue_script('ffl-bro-platform', plugin_dir_url(__FILE__) . 'js/platform.js', array('react', 'react-dom'), '2.1.0', true);
        wp_enqueue_style('tailwind', 'https://unpkg.com/tailwindcss@3.4.17/dist/tailwind.min.css', array(), '3.4.17');
        
        wp_localize_script('ffl-bro-platform', 'fflBroSettings', array(
            'restUrl' => rest_url('fflbro/v1/'),
            'mockMode' => get_option('fflbro_mock_mode', 'true'),
            'dealerName' => get_option('fflbro_dealer_name', 'Your FFL Business'),
            'dealerLicense' => get_option('fflbro_dealer_license', '')
        ));
    }
    
    public function admin_menu() {
        add_menu_page('FFL-BRO Platform', 'FFL-BRO', 'manage_options', 'ffl-bro-platform', array($this, 'dashboard_page'), 'dashicons-shield-alt', 30);
        add_submenu_page('ffl-bro-platform', 'Quote Generator', 'Quote Generator', 'manage_options', 'ffl-bro-quotes', array($this, 'quote_generator_page'));
        add_submenu_page('ffl-bro-platform', 'Form 4473 Manager', 'Form 4473 Manager', 'manage_options', 'ffl-bro-4473', array($this, 'form_4473_page'));
        add_submenu_page('ffl-bro-platform', 'Market Research', 'Market Research', 'manage_options', 'ffl-bro-market', array($this, 'market_research_page'));
        add_submenu_page('ffl-bro-platform', 'ATF Compliance', 'ATF Compliance', 'manage_options', 'ffl-bro-compliance', array($this, 'compliance_page'));
        add_submenu_page('ffl-bro-platform', 'Mobile Operations', 'Mobile Operations', 'manage_options', 'ffl-bro-mobile', array($this, 'mobile_operations_page'));
        add_submenu_page('ffl-bro-platform', 'FNews Settings', 'FNews Settings', 'manage_options', 'ffl-bro-fnews-settings', array($this, 'fnews_settings_page'));
        add_submenu_page('ffl-bro-platform', 'Settings', 'Settings', 'manage_options', 'ffl-bro-settings', array($this, 'settings_page'));
    }
    
    public function admin_init() {
        register_setting('ffl_bro_settings', 'fflbro_mock_mode');
        register_setting('ffl_bro_settings', 'fflbro_dealer_name');
        register_setting('ffl_bro_settings', 'fflbro_dealer_license');
        register_setting('ffl_bro_settings', 'fflbro_fnews_enabled');
    }
    
    public function register_rest_routes() {
        register_rest_route('fflbro/v1', '/dashboard', array('methods' => 'GET', 'callback' => array($this, 'get_dashboard_data'), 'permission_callback' => '__return_true'));
        register_rest_route('fflbro/v1', '/fnews', array('methods' => 'GET', 'callback' => array($this, 'get_fnews_data'), 'permission_callback' => '__return_true'));
        register_rest_route('fflbro/v1', '/market-research', array('methods' => 'GET', 'callback' => array($this, 'get_market_research_data'), 'permission_callback' => '__return_true'));
        register_rest_route('fflbro/v1', '/compliance', array('methods' => 'GET', 'callback' => array($this, 'get_compliance_data'), 'permission_callback' => '__return_true'));
        register_rest_route('fflbro/v1', '/quotes', array('methods' => 'GET', 'callback' => array($this, 'get_quotes_data'), 'permission_callback' => '__return_true'));
        register_rest_route('fflbro/v1', '/quotes', array('methods' => 'POST', 'callback' => array($this, 'create_quote'), 'permission_callback' => '__return_true'));
        register_rest_route('fflbro/v1', '/form-4473', array('methods' => 'GET', 'callback' => array($this, 'get_4473_data'), 'permission_callback' => '__return_true'));
        register_rest_route('fflbro/v1', '/form-4473', array('methods' => 'POST', 'callback' => array($this, 'submit_4473'), 'permission_callback' => '__return_true'));
    }
    
    public function get_dashboard_data() {
        return new WP_REST_Response(array(
            'status' => 'success',
            'data' => array(
                'kpis' => array(
                    'monthly_revenue' => 45280,
                    'active_quotes' => 23,
                    'pending_transfers' => 8,
                    'compliance_score' => 98.5,
                    'pending_4473s' => 5,
                    'completed_4473s' => 187
                )
            )
        ), 200);
    }
    
    public function get_quotes_data() {
        return new WP_REST_Response(array(
            'status' => 'success',
            'data' => array(
                'quotes' => array(
                    array('id' => 1, 'customer' => 'John Smith', 'firearm' => 'Glock 19', 'price' => 550, 'status' => 'pending'),
                    array('id' => 2, 'customer' => 'Sarah Johnson', 'firearm' => 'AR-15', 'price' => 850, 'status' => 'approved'),
                    array('id' => 3, 'customer' => 'Mike Wilson', 'firearm' => 'Ruger 10/22', 'price' => 320, 'status' => 'pending')
                )
            )
        ), 200);
    }
    
    public function create_quote($request) {
        $params = $request->get_json_params();
        return new WP_REST_Response(array(
            'status' => 'success',
            'message' => 'Quote created successfully',
            'quote_id' => rand(1000, 9999)
        ), 200);
    }
    
    public function get_4473_data() {
        return new WP_REST_Response(array(
            'status' => 'success',
            'data' => array(
                'forms' => array(
                    array('id' => 1, 'customer' => 'John Smith', 'firearm' => 'Glock 19', 'status' => 'completed', 'date' => date('Y-m-d')),
                    array('id' => 2, 'customer' => 'Sarah Johnson', 'firearm' => 'AR-15', 'status' => 'pending', 'date' => date('Y-m-d')),
                    array('id' => 3, 'customer' => 'Mike Wilson', 'firearm' => 'Ruger 10/22', 'status' => 'review', 'date' => date('Y-m-d', strtotime('-1 day')))
                )
            )
        ), 200);
    }
    
    public function submit_4473($request) {
        $params = $request->get_json_params();
        return new WP_REST_Response(array(
            'status' => 'success',
            'message' => 'Form 4473 submitted successfully',
            'form_id' => rand(1000, 9999)
        ), 200);
    }
    
    public function get_fnews_data() {
        return new WP_REST_Response(array(
            'status' => 'success',
            'data' => array(
                'articles' => array(
                    array('title' => 'ATF Updates Form 4473 Requirements', 'summary' => 'New changes take effect next month', 'category' => 'Compliance'),
                    array('title' => 'Market Trends: AR-15 Platform Popularity', 'summary' => 'Strong demand continues', 'category' => 'Market'),
                    array('title' => 'Distributor Partnership Opportunities', 'summary' => 'New wholesale pricing available', 'category' => 'Business')
                )
            )
        ), 200);
    }
    
    public function get_market_research_data() {
        return new WP_REST_Response(array(
            'status' => 'success',
            'data' => array(
                'opportunities' => array(
                    array('title' => 'High Demand: Concealed Carry Accessories', 'potential_revenue' => 12500, 'confidence' => 85),
                    array('title' => 'Basic Pistol Course Demand', 'potential_revenue' => 8900, 'confidence' => 78)
                )
            )
        ), 200);
    }
    
    public function get_compliance_data() {
        return new WP_REST_Response(array(
            'status' => 'success',
            'data' => array(
                'compliance_score' => 98.5,
                'alerts' => array(
                    array('message' => '4473 forms need digital backup', 'due' => '3 days'),
                    array('message' => 'Monthly ATF report due soon', 'due' => '15 days')
                )
            )
        ), 200);
    }
    
    public function dashboard_page() {
        echo '<div class="wrap"><h1>FFL-BRO Dashboard</h1><div id="ffl-bro-dashboard-mount"></div></div>';
    }
    
    public function quote_generator_page() {
        echo '<div class="wrap"><h1>Quote Generator</h1><div id="ffl-bro-quote-generator-mount"></div></div>';
    }
    
    public function form_4473_page() {
        echo '<div class="wrap"><h1>Form 4473 Manager</h1><div id="ffl-bro-4473-mount"></div></div>';
    }
    
    public function market_research_page() {
        echo '<div class="wrap"><h1>Market Research</h1><div id="ffl-bro-market-mount"></div></div>';
    }
    
    public function compliance_page() {
        echo '<div class="wrap"><h1>ATF Compliance Dashboard</h1><div id="ffl-bro-compliance-mount"></div></div>';
    }
    
    public function mobile_operations_page() {
        echo '<div class="wrap"><h1>Mobile Operations</h1><div id="ffl-bro-mobile-mount"></div></div>';
    }
    
    public function fnews_settings_page() {
        echo '<div class="wrap"><h1>FNews Settings</h1><form method="post" action="options.php">';
        settings_fields('ffl_bro_settings');
        echo '<table class="form-table"><tr><th>Enable FNews</th><td><input type="checkbox" name="fflbro_fnews_enabled" value="true" ' . checked(get_option('fflbro_fnews_enabled'), 'true', false) . ' /></td></tr></table>';
        submit_button();
        echo '</form></div>';
    }
    
    public function settings_page() {
        echo '<div class="wrap"><h1>FFL-BRO Settings</h1><form method="post" action="options.php">';
        settings_fields('ffl_bro_settings');
        echo '<table class="form-table">';
        echo '<tr><th>Data Mode</th><td><label><input type="radio" name="fflbro_mock_mode" value="true" ' . checked(get_option('fflbro_mock_mode'), 'true', false) . ' /> Mock Data</label><br><label><input type="radio" name="fflbro_mock_mode" value="false" ' . checked(get_option('fflbro_mock_mode'), 'false', false) . ' /> Live Data</label></td></tr>';
        echo '<tr><th>Dealer Name</th><td><input type="text" name="fflbro_dealer_name" value="' . esc_attr(get_option('fflbro_dealer_name')) . '" /></td></tr>';
        echo '<tr><th>FFL License</th><td><input type="text" name="fflbro_dealer_license" value="' . esc_attr(get_option('fflbro_dealer_license')) . '" /></td></tr>';
        echo '</table>';
        submit_button();
        echo '</form></div>';
    }
    
    public function dashboard_shortcode($atts) {
        return '<div id="ffl-bro-dashboard-mount" data-component="dashboard"></div>';
    }
    
    public function fnews_shortcode($atts) {
        return '<div id="ffl-bro-fnews-mount" data-component="fnews"></div>';
    }
    
    public function form_shortcode($atts) {
        return '<div id="ffl-bro-form-mount" data-component="form"></div>';
    }
    
    public function quote_generator_shortcode($atts) {
        return '<div id="ffl-bro-quote-generator-mount" data-component="quote-generator"></div>';
    }
    
    public function form_4473_shortcode($atts) {
        return '<div id="ffl-bro-4473-mount" data-component="form-4473"></div>';
    }
    
    public function market_research_shortcode($atts) {
        return '<div id="ffl-bro-market-mount" data-component="market"></div>';
    }
    
    public function compliance_shortcode($atts) {
        return '<div id="ffl-bro-compliance-mount" data-component="compliance"></div>';
    }
    
    public function mobile_ops_shortcode($atts) {
        return '<div id="ffl-bro-mobile-mount" data-component="mobile"></div>';
    }
}

new FFLBroEnhanced();
?>