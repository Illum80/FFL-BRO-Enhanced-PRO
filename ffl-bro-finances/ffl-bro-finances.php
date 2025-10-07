<?php
/**
 * Plugin Name: FFL-BRO Finances (AP)
 * Plugin URI: https://github.com/Illum80/ffl-bro-finances
 * Description: Accounts Payable module for FFL business operations (Vendors → Bills → Payments → Checks)
 * Version: 1.0.0
 * Author: FFL-BRO Development
 * Author URI: https://ffl-bro.com
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * Text Domain: ffl-bro-finances
 */

namespace FFLBRO\Fin;

if (!defined('ABSPATH')) exit;

define('FFLBRO_FIN_VERSION', '1.0.0');
define('FFLBRO_FIN_PATH', plugin_dir_path(__FILE__));
define('FFLBRO_FIN_URL', plugin_dir_url(__FILE__));

// Simple autoloader (PHP 7.4 compatible)
spl_autoload_register(function ($class) {
    $prefix = 'FFLBRO\\Fin\\';
    $len = strlen($prefix);
    if (strpos($class, $prefix) !== 0) return;
    
    $relative = substr($class, $len);
    $file = FFLBRO_FIN_PATH . 'includes/' . str_replace('\\', '/', $relative) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Safety-first activation
register_activation_hook(__FILE__, function() {
    try {
        // Preflight checks
        if (!current_user_can('activate_plugins')) {
            throw new \Exception('Insufficient permissions');
        }
        
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            throw new \Exception('PHP 7.4+ required');
        }
        
        global $wp_version;
        if (version_compare($wp_version, '5.8', '<')) {
            throw new \Exception('WordPress 5.8+ required');
        }
        
        // Grant capabilities
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('ffl_fin_admin');
            $admin->add_cap('ffl_fin_manage');
            $admin->add_cap('ffl_fin_read');
        }
        
        $editor = get_role('editor');
        if ($editor) {
            $editor->add_cap('ffl_fin_manage');
            $editor->add_cap('ffl_fin_read');
        }
        
        // Install schema
        require_once FFLBRO_FIN_PATH . 'includes/Schema.php';
        Schema::install();
        
        // Set version
        update_option('fflbro_fin_version', FFLBRO_FIN_VERSION);
        
        // Ensure toggle exists (default OFF for safety)
        if (get_option('fflbro_fin_enable') === false) {
            add_option('fflbro_fin_enable', 0);
        }
        
    } catch (\Exception $e) {
        // Self-deactivate on failure
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            'FFL-BRO Finances activation failed: ' . $e->getMessage(),
            'Activation Failed',
            ['back_link' => true]
        );
    }
});

// Runtime bootstrap (only if enabled)
add_action('plugins_loaded', function() {
    // Guard: preflight check
    if (!current_user_can('read')) return;
    
    // Guard: toggle check
    if (!get_option('fflbro_fin_enable', 0)) return;
    
    // Bootstrap admin UI
    if (is_admin()) {
        require_once FFLBRO_FIN_PATH . 'includes/Admin.php';
        new Admin();
    }
    
    // Bootstrap REST API
    add_action('rest_api_init', function() {
        require_once FFLBRO_FIN_PATH . 'includes/REST/FinanceRoutes.php';
        REST\FinanceRoutes::register();
    });
});
