#!/usr/bin/env bash
# Complete FFL-BRO Enhanced PRO Setup and GitHub Automation
set -euo pipefail

echo "🚀 FFL-BRO Enhanced PRO Complete Setup Automation"
echo "=================================================="
echo ""

# Get current directory info
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")/ffl-bro-enhanced-pro-integrated"

echo "📁 Setting up project in: $PROJECT_DIR"

# Create project directory
mkdir -p "$PROJECT_DIR"
cd "$PROJECT_DIR"

echo "[*] Step 1: Initialize Git repository"
if [ ! -d ".git" ]; then
    git init
    git remote add origin https://github.com/Illum80/FFL-BRO-Enhanced-PRO.git 2>/dev/null || true
fi

echo "[*] Step 2: Create main plugin file"
cat > ffl-bro-enhanced-pro.php << 'MAIN_PLUGIN_EOF'
<?php
/**
 * Plugin Name: FFL-BRO Enhanced PRO with Integrated Lipsey's
 * Description: Complete FFL business management system with integrated Lipsey's API, Facebook leads, Form 4473, Training, Compliance, GunBroker & more
 * Version: 4.1.0
 * Author: FFL-BRO Team / Neefeco Arms
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) { exit; }

define('FFLBRO_ENHANCED_VERSION', '4.1.0');
define('FFLBRO_ENHANCED_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FFLBRO_ENHANCED_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include Lipsey's API classes
class FFLBro_Lipseys_Api {
    private $token, $token_expires, $base = 'https://api.lipseys.com', $email, $password;

    public function __construct() {
        $this->email    = get_option('fflbro_lipseys_email', getenv('LIPSEYS_EMAIL') ?: '');
        $this->password = get_option('fflbro_lipseys_password', getenv('LIPSEYS_PASSWORD') ?: '');
        $this->token    = get_option('fflbro_lipseys_token', '');
        $this->token_expires = (int) get_option('fflbro_lipseys_token_expires', 0);
        $opt_base = get_option('fflbro_lipseys_base', getenv('LIPSEYS_BASE') ?: '');
        if ($opt_base) { $this->base = rtrim($opt_base, '/'); }
    }

    private function http($method, $path, $body = null, $auth = true) {
        $ch  = curl_init();
        $url = rtrim($this->base, '/') . '/' . ltrim($path, '/');
        $hdr = ['Content-Type: application/json'];
        if ($auth) { $this->ensure_token(); $hdr[] = 'Authorization: Bearer ' . $this->token; }
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $hdr,
        ]);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        $res  = curl_exec($ch);
        if ($res === false) throw new RuntimeException('Lipsey HTTP error: ' . curl_error($ch));
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        $data = json_decode($res, true);
        if ($code >= 400) throw new RuntimeException("Lipsey API {$code}: " . ($res ?: ''));
        return $data;
    }

    private function try_paths($tries, $method, $payload = null, $query = null) {
        $last = null;
        foreach ($tries as $p) {
            try {
                if ($query) {
                    $p .= (strpos($p,'?')===false?'?':'&') . http_build_query($query);
                }
                return $this->http($method, $p, $payload);
            } catch (\Throwable $e) {
                $last = $e;
                if (strpos($e->getMessage(), 'Lipsey API 404') === false) { throw $e; }
            }
        }
        if ($last) throw $last;
        throw new RuntimeException('No paths attempted.');
    }

    private function ensure_token() {
        if ($this->token && time() < $this->token_expires - 60) return;
        if (!$this->email || !$this->password) throw new RuntimeException('Lipsey credentials not set.');
        $data = $this->http('POST', '/auth', ['Email'=>$this->email,'Password'=>$this->password], $auth=false);
        $this->token = $data['token'] ?? $data['Token'] ?? '';
        $ttl         = isset($data['expiresIn']) ? (int)$data['expiresIn'] : 2700;
        if (!$this->token) throw new RuntimeException('Lipsey auth did not return a token.');
        update_option('fflbro_lipseys_token', $this->token, false);
        $exp = time() + max(600, $ttl);
        $this->token_expires = $exp;
        update_option('fflbro_lipseys_token_expires', (string)$exp, false);
    }

    public function catalog_item($sku) {
        $payload = ['ItemNo'=>(string)$sku];
        $tries = [
            '/items/catalogitem',
            '/Items/CatalogItem',
            '/ApiIntegration/Items/CatalogItem',
        ];
        return $this->try_paths($tries, 'POST', $payload);
    }

    public function pricing_and_quantity() {
        $tries = [
            '/items/pricingandquantity',
            '/Items/PricingAndQuantity',
            '/ApiIntegration/Items/PricingAndQuantity',
        ];
        return $this->try_paths($tries, 'GET');
    }

    public function validate_item($sku, $qty=1) {
        $payload = ['ItemNo'=>(string)$sku,'Quantity'=>(int)$qty];
        $tries = [
            '/items/validateitem',
            '/Items/ValidateItem',
            '/ApiIntegration/Items/ValidateItem',
        ];
        return $this->try_paths($tries, 'POST', $payload);
    }
}

class FFLBro_Lipseys_Importer {
    public static function upsert_product_from_catalog($sku, $cat) {
        if (!function_exists('wc_get_product_id_by_sku')) throw new RuntimeException('WooCommerce not active.');
        require_once ABSPATH.'wp-admin/includes/file.php';
        require_once ABSPATH.'wp-admin/includes/media.php';
        require_once ABSPATH.'wp-admin/includes/image.php';

        $name        = $cat['Description'] ?? ($cat['ItemDescription'] ?? $sku);
        $priceDealer = $cat['DealerPrice'] ?? null;
        $priceRetail = $cat['RetailPrice'] ?? ($cat['Msrp'] ?? null);
        $upc         = $cat['UPC'] ?? ($cat['Upc'] ?? '');
        $brand       = $cat['Manufacturer'] ?? ($cat['Brand'] ?? '');
        $images      = is_array($cat['Images'] ?? null) ? $cat['Images'] : [];

        $pid = wc_get_product_id_by_sku($sku);
        if (!$pid) {
            $pid = wp_insert_post(['post_type'=>'product','post_status'=>'publish','post_title'=>$name]);
            update_post_meta($pid, '_sku', $sku);
        }

        if ($priceRetail) update_post_meta($pid, '_price', wc_format_decimal($priceRetail));
        if ($priceDealer && !get_post_meta($pid, '_regular_price', true))
            update_post_meta($pid, '_regular_price', wc_format_decimal($priceDealer));
        if ($upc) update_post_meta($pid, '_barcode', $upc);

        if ($brand) {
            $tax = 'pa_brand';
            if (!taxonomy_exists($tax)) register_taxonomy($tax, 'product', ['hierarchical'=>false,'label'=>'Brand']);
            $term = term_exists($brand, $tax);
            if (!$term) $term = wp_insert_term($brand, $tax);
            if (!is_wp_error($term)) wp_set_object_terms($pid, [(int)$term['term_id']], $tax, false);
        }

        if ($images && !get_post_thumbnail_id($pid)) {
            $img = is_array($images[0]) ? ($images[0]['Url'] ?? $images[0]['url'] ?? '') : $images[0];
            if ($img && filter_var($img, FILTER_VALIDATE_URL)) media_sideload_image($img, $pid, $name);
        }

        update_post_meta($pid, '_manage_stock', 'yes');
        update_post_meta($pid, '_stock_status', 'instock');
        return $pid;
    }

    public static function apply_qty_prices($map) {
        foreach ((array)$map as $sku => $row) {
            $pid = function_exists('wc_get_product_id_by_sku') ? wc_get_product_id_by_sku($sku) : 0;
            if (!$pid) continue;
            if (isset($row['RetailPrice'])) update_post_meta($pid, '_price', wc_format_decimal($row['RetailPrice']));
            if (isset($row['DealerPrice']) && !get_post_meta($pid, '_regular_price', true))
                update_post_meta($pid, '_regular_price', wc_format_decimal($row['DealerPrice']));
            if (isset($row['Quantity'])) {
                update_post_meta($pid, '_stock', (int)$row['Quantity']);
                update_post_meta($pid, '_stock_status', ((int)$row['Quantity'] > 0) ? 'instock' : 'outofstock');
            }
        }
    }
}

// Main Enhanced PRO Plugin Class
class FFLBroEnhancedPRO {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('admin_init', array($this, 'register_settings'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Add shortcodes
        add_shortcode('ffl_bro_dashboard', array($this, 'dashboard_shortcode'));
        add_shortcode('ffl_bro_lipseys_dashboard', array($this, 'lipseys_dashboard_shortcode'));
        
        // Cart Guard functionality
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_cart_item'), 10, 5);
        add_action('woocommerce_check_cart_items', array($this, 'check_cart_items'));
    }
    
    public function init() {
        $this->create_database_tables();
    }
    
    public function admin_menu() {
        // Main menu
        add_menu_page(
            'FFL-BRO Enhanced PRO',
            'FFL-BRO Enhanced PRO',
            'manage_options',
            'ffl-bro-enhanced-pro',
            array($this, 'dashboard_page'),
            'dashicons-shield-alt',
            3
        );
        
        // Submenus
        add_submenu_page('ffl-bro-enhanced-pro', 'Dashboard', 'Dashboard', 'manage_options', 'ffl-bro-enhanced-pro', array($this, 'dashboard_page'));
        add_submenu_page('ffl-bro-enhanced-pro', 'Lead Management', 'Lead Management', 'manage_options', 'ffl-bro-leads', array($this, 'leads_page'));
        add_submenu_page('ffl-bro-enhanced-pro', 'Digital Form 4473', 'Digital Form 4473', 'manage_options', 'ffl-bro-form4473', array($this, 'form4473_page'));
        add_submenu_page('ffl-bro-enhanced-pro', 'Quote Generator', 'Quote Generator', 'manage_options', 'ffl-bro-quotes', array($this, 'quotes_page'));
        add_submenu_page('ffl-bro-enhanced-pro', 'Training Management', 'Training Management', 'manage_options', 'ffl-bro-training', array($this, 'training_page'));
        add_submenu_page('ffl-bro-enhanced-pro', 'Compliance Monitor', 'Compliance Monitor', 'manage_options', 'ffl-bro-compliance', array($this, 'compliance_page'));
        add_submenu_page('ffl-bro-enhanced-pro', 'GunBroker Integration', 'GunBroker Integration', 'manage_options', 'ffl-bro-gunbroker', array($this, 'gunbroker_page'));
        add_submenu_page('ffl-bro-enhanced-pro', 'Lipseys Integration', 'Lipseys Integration', 'manage_options', 'ffl-bro-lipseys', array($this, 'lipseys_page'));
        add_submenu_page('ffl-bro-enhanced-pro', 'Workflow Automation', 'Workflow Automation', 'manage_options', 'ffl-bro-automation', array($this, 'automation_page'));
        add_submenu_page('ffl-bro-enhanced-pro', 'Multi-Site Management', 'Multi-Site Management', 'manage_options', 'ffl-bro-multisite', array($this, 'multisite_page'));
        add_submenu_page('ffl-bro-enhanced-pro', 'Settings', 'Settings', 'manage_options', 'ffl-bro-settings', array($this, 'settings_page'));
    }
    
    public function admin_scripts($hook) {
        if (strpos($hook, 'ffl-bro') !== false) {
            wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.production.min.js', array(), '18.0.0', true);
            wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', array('react'), '18.0.0', true);
            wp_enqueue_script('babel-standalone', 'https://unpkg.com/@babel/standalone/babel.min.js', array(), '7.0.0', true);
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.0.0', true);
            
            wp_enqueue_script(
                'ffl-bro-enhanced-admin',
                FFLBRO_ENHANCED_PLUGIN_URL . 'assets/js/admin.js',
                array('react', 'react-dom', 'babel-standalone', 'chart-js'),
                FFLBRO_ENHANCED_VERSION,
                true
            );
            
            wp_enqueue_style(
                'ffl-bro-enhanced-admin',
                FFLBRO_ENHANCED_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                FFLBRO_ENHANCED_VERSION
            );
            
            wp_localize_script('ffl-bro-enhanced-admin', 'fflBroAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ffl_bro_nonce'),
                'restUrl' => rest_url('ffl-bro/v1/'),
                'restNonce' => wp_create_nonce('wp_rest'),
                'pluginUrl' => FFLBRO_ENHANCED_PLUGIN_URL,
                'currentPage' => $_GET['page'] ?? ''
            ));
        }
    }
    
    public function register_rest_routes() {
        register_rest_route('ffl-bro/v1', '/dashboard-data', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_dashboard_data'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route('ffl-bro/v1', '/lipseys-data', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_lipseys_data'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route('ffl-bro/v1', '/lipseys-import', array(
            'methods' => 'POST',
            'callback' => array($this, 'lipseys_import_item'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route('ffl-bro/v1', '/lipseys-sync', array(
            'methods' => 'POST',
            'callback' => array($this, 'lipseys_sync_inventory'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route('ffl-bro/v1', '/lipseys-validate', array(
            'methods' => 'POST',
            'callback' => array($this, 'lipseys_validate_item'),
            'permission_callback' => array($this, 'check_permissions')
        ));
    }
    
    public function register_settings() {
        register_setting('ffl_bro_settings', 'fflbro_mock_mode');
        register_setting('ffl_bro_settings', 'fflbro_dealer_name');
        register_setting('ffl_bro_settings', 'fflbro_dealer_license');
        register_setting('ffl_bro_settings', 'fflbro_lipseys_email');
        register_setting('ffl_bro_settings', 'fflbro_lipseys_password');
        register_setting('ffl_bro_settings', 'fflbro_lipseys_base');
    }
    
    // Cart Guard functionality
    public function validate_cart_item($valid, $product_id, $qty, $variation_id = 0, $variations = []) {
        if (!$valid) return $valid;
        
        $sku = get_post_meta($product_id, '_sku', true);
        if (!$sku) return $valid;
        
        try {
            $api = new FFLBro_Lipseys_Api();
            $res = $api->validate_item($sku, (int)$qty);
            $ok = (bool)($res['IsValid'] ?? $res['Success'] ?? $res['Valid'] ?? $res['Allow'] ?? false);
            
            if (!$ok) {
                wc_add_notice(__('This item/quantity is not currently available from our distributor.', 'fflbro'), 'error');
                return false;
            }
        } catch (Throwable $e) {
            error_log('Lipsey validation error: ' . $e->getMessage());
        }
        
        return $valid;
    }
    
    public function check_cart_items() {
        if (!WC()->cart) return;
        
        foreach (WC()->cart->get_cart() as $key => $item) {
            $product = $item['data'] ?? null;
            if (!$product || !method_exists($product, 'get_sku')) continue;
            
            $sku = (string) $product->get_sku();
            $qty = (int) ($item['quantity'] ?? 1);
            if (!$sku) continue;
            
            try {
                $api = new FFLBro_Lipseys_Api();
                $res = $api->validate_item($sku, $qty);
                $ok = (bool)($res['IsValid'] ?? $res['Success'] ?? $res['Valid'] ?? $res['Allow'] ?? false);
                
                if (!$ok) {
                    wc_add_notice(sprintf(__('"%s" is not available in the requested quantity.', 'fflbro'), $product->get_name()), 'error');
                }
            } catch (Throwable $e) {
                error_log('Lipsey checkout validation error: ' . $e->getMessage());
            }
        }
    }
    
    // REST API Callbacks
    public function check_permissions() {
        return current_user_can('manage_options');
    }
    
    public function get_dashboard_data() {
        return new WP_REST_Response(array(
            'revenue' => array('today' => 2340, 'month' => 78920, 'year' => 892430),
            'orders' => array('pending' => 12, 'processing' => 8, 'completed' => 245),
            'inventory' => array('total_items' => 1247, 'low_stock' => 23, 'out_of_stock' => 5),
            'leads' => array('new' => 15, 'contacted' => 32, 'converted' => 8),
            'compliance_score' => 98.5,
            'chart_data' => array(
                'labels' => array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'),
                'sales' => array(12000, 19000, 15000, 25000, 22000, 30000),
                'leads' => array(45, 52, 48, 61, 55, 67)
            )
        ), 200);
    }
    
    public function get_lipseys_data() {
        try {
            $api = new FFLBro_Lipseys_Api();
            $pricing_data = $api->pricing_and_quantity();
            
            return new WP_REST_Response(array(
                'status' => 'connected',
                'inventory_count' => count($pricing_data),
                'last_sync' => date('Y-m-d H:i:s'),
                'sample_items' => array_slice($pricing_data, 0, 10)
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'status' => 'error',
                'message' => $e->getMessage(),
                'mock_data' => array(
                    'inventory_count' => 15742,
                    'last_sync' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
                    'sample_items' => array(
                        array('ItemNo' => 'RU1022RB', 'Description' => 'Ruger 10/22 Carbine', 'Quantity' => 12, 'DealerPrice' => 289.99),
                        array('ItemNo' => 'GL19G5', 'Description' => 'Glock 19 Gen 5', 'Quantity' => 8, 'DealerPrice' => 525.00)
                    )
                )
            ), 200);
        }
    }
    
    public function lipseys_import_item(WP_REST_Request $request) {
        $sku = $request->get_param('sku');
        if (!$sku) {
            return new WP_REST_Response(array('error' => 'SKU required'), 400);
        }
        
        try {
            $api = new FFLBro_Lipseys_Api();
            $cat = $api->catalog_item($sku);
            $pid = FFLBro_Lipseys_Importer::upsert_product_from_catalog($sku, $cat);
            
            return new WP_REST_Response(array(
                'success' => true,
                'product_id' => $pid,
                'message' => "Imported/updated SKU {$sku} → product #{$pid}"
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array('error' => $e->getMessage()), 500);
        }
    }
    
    public function lipseys_sync_inventory() {
        try {
            $api = new FFLBro_Lipseys_Api();
            $pq = $api->pricing_and_quantity();
            $map = array();
            
            foreach ((array)$pq as $row) {
                $sku = $row['ItemNo'] ?? $row['SKU'] ?? $row['Item'] ?? null;
                if (!$sku) continue;
                
                $map[$sku] = array(
                    'Quantity'    => $row['Quantity'] ?? ($row['Qty'] ?? null),
                    'DealerPrice' => $row['DealerPrice'] ?? null,
                    'RetailPrice' => $row['RetailPrice'] ?? ($row['Msrp'] ?? null),
                );
            }
            
            FFLBro_Lipseys_Importer::apply_qty_prices($map);
            
            return new WP_REST_Response(array(
                'success' => true,
                'updated_count' => count($map),
                'message' => 'Applied price/qty updates for ' . count($map) . ' SKUs.'
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array('error' => $e->getMessage()), 500);
        }
    }
    
    public function lipseys_validate_item(WP_REST_Request $request) {
        $sku = $request->get_param('sku');
        $qty = $request->get_param('qty') ?: 1;
        
        if (!$sku) {
            return new WP_REST_Response(array('error' => 'SKU required'), 400);
        }
        
        try {
            $api = new FFLBro_Lipseys_Api();
            $res = $api->validate_item($sku, (int)$qty);
            
            return new WP_REST_Response(array(
                'sku' => $sku,
                'quantity' => $qty,
                'validation_result' => $res
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array('error' => $e->getMessage()), 500);
        }
    }
    
    // Page rendering methods
    public function dashboard_page() {
        echo '<div class="wrap"><h1>FFL-BRO Enhanced PRO Dashboard</h1><div id="ffl-bro-dashboard-mount" data-page="dashboard"></div></div>';
    }
    
    public function leads_page() {
        echo '<div class="wrap"><h1>Lead Management</h1><div id="ffl-bro-leads-mount" data-page="leads"></div></div>';
    }
    
    public function form4473_page() {
        echo '<div class="wrap"><h1>Digital Form 4473 Processing</h1><div class="page-placeholder"><h3>🚧 Module Coming Soon</h3><p>This feature is being developed and will be available in the next update.</p></div></div>';
    }
    
    public function quotes_page() {
        echo '<div class="wrap"><h1>Quote Generator</h1><div class="page-placeholder"><h3>🚧 Module Coming Soon</h3><p>This feature is being developed and will be available in the next update.</p></div></div>';
    }
    
    public function training_page() {
        echo '<div class="wrap"><h1>Training Management</h1><div class="page-placeholder"><h3>🚧 Module Coming Soon</h3><p>This feature is being developed and will be available in the next update.</p></div></div>';
    }
    
    public function compliance_page() {
        echo '<div class="wrap"><h1>Compliance Monitor</h1><div class="page-placeholder"><h3>🚧 Module Coming Soon</h3><p>This feature is being developed and will be available in the next update.</p></div></div>';
    }
    
    public function gunbroker_page() {
        echo '<div class="wrap"><h1>GunBroker Integration</h1><div class="page-placeholder"><h3>🚧 Module Coming Soon</h3><p>This feature is being developed and will be available in the next update.</p></div></div>';
    }
    
    public function lipseys_page() {
        ?>
        <div class="wrap">
            <h1>Lipseys Integration</h1>
            <div id="ffl-bro-lipseys-mount" data-page="lipseys"></div>
            
            <div class="lipseys-tools" style="margin-top: 30px;">
                <h2>Lipseys Management Tools</h2>
                <div class="tool-buttons" style="display: flex; gap: 10px; margin: 20px 0;">
                    <button class="button button-primary" onclick="testLipseysConnection()">Test Connection</button>
                    <button class="button" onclick="importSingleSKU()">Import Single SKU</button>
                    <button class="button" onclick="syncAllInventory()">Sync All Inventory</button>
                    <button class="button" onclick="validateSKU()">Validate SKU/Qty</button>
                </div>
                
                <div class="tool-forms" style="background: #f9f9f9; padding: 20px; border-radius: 8px;">
                    <h3>Quick Actions</h3>
                    
                    <div style="margin-bottom: 20px;">
                        <label><strong>Import Single Product:</strong></label><br>
                        <input type="text" id="import-sku" placeholder="Enter SKU (e.g., RU1022RB)" style="width: 200px; margin-right: 10px;">
                        <button class="button" onclick="doImportSKU()">Import</button>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label><strong>Validate Availability:</strong></label><br>
                        <input type="text" id="validate-sku" placeholder="SKU" style="width: 150px; margin-right: 10px;">
                        <input type="number" id="validate-qty" placeholder="Qty" value="1" style="width: 60px; margin-right: 10px;">
                        <button class="button" onclick="doValidateSKU()">Check</button>
                    </div>
                </div>
                
                <div id="lipseys-results" style="margin-top: 20px; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 5px; display: none;">
                    <h4>Results:</h4>
                    <pre id="lipseys-output"></pre>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function automation_page() {
        echo '<div class="wrap"><h1>Workflow Automation</h1><div class="page-placeholder"><h3>🚧 Module Coming Soon</h3><p>This feature is being developed and will be available in the next update.</p></div></div>';
    }
    
    public function multisite_page() {
        echo '<div class="wrap"><h1>Multi-Site Management</h1><div class="page-placeholder"><h3>🚧 Module Coming Soon</h3><p>This feature is being developed and will be available in the next update.</p></div></div>';
    }
    
    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('fflbro_mock_mode', $_POST['fflbro_mock_mode'] ?? 'true');
            update_option('fflbro_dealer_name', sanitize_text_field($_POST['fflbro_dealer_name'] ?? ''));
            update_option('fflbro_dealer_license', sanitize_text_field($_POST['fflbro_dealer_license'] ?? ''));
            update_option('fflbro_lipseys_email', sanitize_email($_POST['fflbro_lipseys_email'] ?? ''));
            update_option('fflbro_lipseys_password', sanitize_text_field($_POST['fflbro_lipseys_password'] ?? ''));
            update_option('fflbro_lipseys_base', esc_url_raw($_POST['fflbro_lipseys_base'] ?? ''));
            
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>FFL-BRO Enhanced PRO Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field('ffl_bro_settings'); ?>
                
                <h2>General Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Data Mode</th>
                        <td>
                            <label>
                                <input type="radio" name="fflbro_mock_mode" value="true" <?php checked(get_option('fflbro_mock_mode', 'true'), 'true'); ?> />
                                Mock Data (for testing and demos)
                            </label><br>
                            <label>
                                <input type="radio" name="fflbro_mock_mode" value="false" <?php checked(get_option('fflbro_mock_mode'), 'false'); ?> />
                                Live Data (production mode)
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Dealer Name</th>
                        <td><input type="text" name="fflbro_dealer_name" value="<?php echo esc_attr(get_option('fflbro_dealer_name')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">FFL License Number</th>
                        <td><input type="text" name="fflbro_dealer_license" value="<?php echo esc_attr(get_option('fflbro_dealer_license')); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                
                <h2>Lipsey's Integration Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Lipsey's Email</th>
                        <td><input type="email" name="fflbro_lipseys_email" value="<?php echo esc_attr(get_option('fflbro_lipseys_email')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Lipsey's Password</th>
                        <td><input type="password" name="fflbro_lipseys_password" value="<?php echo esc_attr(get_option('fflbro_lipseys_password')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Lipsey's Base URL (optional)</th>
                        <td>
                            <input type="url" name="fflbro_lipseys_base" value="<?php echo esc_attr(get_option('fflbro_lipseys_base')); ?>" class="regular-text" placeholder="https://api.lipseys.com/Integration" />
                            <p class="description">Leave blank to use default. Set to https://api.lipseys.com/Integration if recommended by Lipsey's.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    // Shortcode methods
    public function dashboard_shortcode($atts) {
        return '<div id="ffl-bro-dashboard-mount" data-component="dashboard"></div>';
    }
    
    public function lipseys_dashboard_shortcode($atts) {
        return '<div id="ffl-bro-lipseys-dashboard-mount" data-component="lipseys"></div>';
    }
    
    // Database and activation methods
    public function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_leads = $wpdb->prefix . 'fflbro_leads';
        $sql_leads = "CREATE TABLE $table_leads (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            source varchar(50) NOT NULL,
            campaign varchar(100),
            name varchar(100) NOT NULL,
            email varchar(100),
            phone varchar(20),
            interest varchar(200),
            status varchar(20) DEFAULT 'new',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_leads);
    }
    
    public function activate() {
        $this->create_database_tables();
        add_option('fflbro_mock_mode', 'true');
        add_option('fflbro_dealer_name', '');
        add_option('fflbro_dealer_license', '');
        
        $upload_dir = wp_upload_dir();
        $ffl_dir = $upload_dir['basedir'] . '/ffl-bro-documents';
        if (!file_exists($ffl_dir)) {
            wp_mkdir_p($ffl_dir);
        }
    }
    
    public function deactivate() {
        // Clean up temporary data if needed
    }
}

// WP-CLI Integration for Lipsey's
if (defined('WP_CLI') && WP_CLI) {
    class FFLBro_Lipseys_CLI {
        public function import($args, $assoc) {
            list($sku) = $args;
            $api = new FFLBro_Lipseys_Api();
            $cat = $api->catalog_item($sku);
            $pid = FFLBro_Lipseys_Importer::upsert_product_from_catalog($sku, $cat);
            if (!empty($assoc['featured'])) update_post_meta($pid, '_featured', 'yes');
            \WP_CLI::success("Imported/updated SKU {$sku} → product #{$pid}");
        }
        
        public function sync($args, $assoc) {
            $api = new FFLBro_Lipseys_Api();
            $pq = $api->pricing_and_quantity();
            $map = array();
            
            foreach ((array)$pq as $row) {
                $sku = $row['ItemNo'] ?? $row['SKU'] ?? $row['Item'] ?? null;
                if (!$sku) continue;
                
                $map[$sku] = array(
                    'Quantity'    => $row['Quantity'] ?? ($row['Qty'] ?? null),
                    'DealerPrice' => $row['DealerPrice'] ?? null,
                    'RetailPrice' => $row['RetailPrice'] ?? ($row['Msrp'] ?? null),
                );
            }
            
            FFLBro_Lipseys_Importer::apply_qty_prices($map);
            \WP_CLI::success('Applied price/qty updates for ' . count($map) . ' SKUs.');
        }
        
        public function validate($args, $assoc) {
            list($sku, $qty) = array($args[0], (int)($args[1] ?? 1));
            $api = new FFLBro_Lipseys_Api();
            $res = $api->validate_item($sku, $qty);
            \WP_CLI::log(json_encode($res, JSON_PRETTY_PRINT));
        }
    }
    
    \WP_CLI::add_command('fflbro-lipseys', 'FFLBro_Lipseys_CLI');
}

// Initialize the plugin
new FFLBroEnhancedPRO();
?>
MAIN_PLUGIN_EOF

echo "[*] Step 3: Create asset directories"
mkdir -p assets/js assets/css tools

echo "[*] Step 4: Create React admin dashboard"
cat > assets/js/admin.js << 'ADMIN_JS_EOF'
// FFL-BRO Enhanced PRO Admin Dashboard with React
const { useState, useEffect, useRef } = React;

// Main Dashboard Component
const FFLBroDashboard = () => {
    const [data, setData] = useState({});
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadDashboardData();
    }, []);

    const loadDashboardData = async () => {
        try {
            setLoading(true);
            const response = await fetch(fflBroAjax.restUrl + 'dashboard-data', {
                headers: { 'X-WP-Nonce': fflBroAjax.restNonce }
            });
            const result = await response.json();
            setData(result);
        } catch (error) {
            console.error('Failed to load dashboard data:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return React.createElement('div', { className: 'ffl-loading' },
            React.createElement('h3', null, '⚡ Loading FFL-BRO Enhanced PRO...')
        );
    }

    return React.createElement('div', { className: 'ffl-bro-enhanced-dashboard' },
        React.createElement(DashboardHeader, { data }),
        React.createElement(DashboardStats, { data }),
        React.createElement(DashboardCharts, { data }),
        React.createElement(QuickActions, { onAction: loadDashboardData })
    );
};

// Dashboard Header Component
const DashboardHeader = ({ data }) => {
    return React.createElement('div', { className: 'dashboard-header' },
        React.createElement('h2', null, '🛡️ FFL-BRO Enhanced PRO Dashboard'),
        React.createElement('div', { className: 'status-indicators' },
            React.createElement('span', { className: 'status-item status-success' },
                '✅ System Active'
            ),
            React.createElement('span', { className: 'status-item' },
                `📊 Compliance Score: ${data.compliance_score || 'N/A'}%`
            ),
            React.createElement('span', { className: 'status-item' },
                `📈 Revenue: $${data.revenue?.month || '0'}`
            )
        )
    );
};

// Dashboard Statistics Component  
const DashboardStats = ({ data }) => {
    const stats = [
        { title: 'Today\'s Revenue', value: `$${data.revenue?.today || 0}`, icon: '💰', color: 'green' },
        { title: 'Pending Orders', value: data.orders?.pending || 0, icon: '📋', color: 'orange' },
        { title: 'New Leads', value: data.leads?.new || 0, icon: '👥', color: 'blue' },
        { title: 'Inventory Items', value: data.inventory?.total_items || 0, icon: '📦', color: 'purple' }
    ];

    return React.createElement('div', { className: 'dashboard-stats' },
        stats.map((stat, index) =>
            React.createElement('div', { 
                key: index, 
                className: `stat-card stat-${stat.color}` 
            },
                React.createElement('div', { className: 'stat-icon' }, stat.icon),
                React.createElement('div', { className: 'stat-content' },
                    React.createElement('h3', null, stat.value),
                    React.createElement('p', null, stat.title)
                )
            )
        )
    );
};

// Dashboard Charts Component
const DashboardCharts = ({ data }) => {
    const chartRef = useRef(null);

    useEffect(() => {
        if (data.chart_data && chartRef.current) {
            const ctx = chartRef.current.getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.chart_data.labels || [],
                    datasets: [
                        {
                            label: 'Sales ($)',
                            data: data.chart_data.sales || [],
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.1
                        },
                        {
                            label: 'Leads',
                            data: data.chart_data.leads || [],
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            tension: 0.1,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Business Performance Trends'
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        }
    }, [data]);

    return React.createElement('div', { className: 'dashboard-charts' },
        React.createElement('div', { className: 'chart-container' },
            React.createElement('canvas', { ref: chartRef, id: 'businessChart' })
        )
    );
};

// Quick Actions Component
const QuickActions = ({ onAction }) => {
    const actions = [
        { label: '📋 New Form 4473', url: '/wp-admin/admin.php?page=ffl-bro-form4473', color: 'blue' },
        { label: '💰 Generate Quote', url: '/wp-admin/admin.php?page=ffl-bro-quotes', color: 'green' },
        { label: '👥 Manage Leads', url: '/wp-admin/admin.php?page=ffl-bro-leads', color: 'purple' },
        { label: '🛡️ Compliance Check', url: '/wp-admin/admin.php?page=ffl-bro-compliance', color: 'orange' },
        { label: '🎯 GunBroker', url: '/wp-admin/admin.php?page=ffl-bro-gunbroker', color: 'red' },
        { label: '🚛 Lipseys', url: '/wp-admin/admin.php?page=ffl-bro-lipseys', color: 'teal' }
    ];

    return React.createElement('div', { className: 'quick-actions' },
        React.createElement('h3', null, '⚡ Quick Actions'),
        React.createElement('div', { className: 'actions-grid' },
            actions.map((action, index) =>
                React.createElement('a', {
                    key: index,
                    href: action.url,
                    className: `action-button action-${action.color}`
                }, action.label)
            )
        )
    );
};

// Lipsey's Integration Component
const LipseysIntegration = () => {
    const [data, setData] = useState({});
    const [loading, setLoading] = useState(true);
    const [results, setResults] = useState(null);

    useEffect(() => {
        loadLipseysData();
    }, []);

    const loadLipseysData = async () => {
        try {
            setLoading(true);
            const response = await fetch(fflBroAjax.restUrl + 'lipseys-data', {
                headers: { 'X-WP-Nonce': fflBroAjax.restNonce }
            });
            const result = await response.json();
            setData(result);
        } catch (error) {
            console.error('Failed to load Lipseys data:', error);
            setData({ status: 'error', message: error.message });
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return React.createElement('div', { className: 'ffl-loading' },
            React.createElement('h3', null, '🚛 Loading Lipseys Integration...')
        );
    }

    return React.createElement('div', { className: 'lipseys-integration' },
        React.createElement('div', { className: 'integration-header' },
            React.createElement('h2', null, '🚛 Lipseys Integration Dashboard'),
            React.createElement('div', { className: `status-badge status-${data.status || 'unknown'}` },
                `Status: ${data.status || 'Unknown'}`
            )
        ),
        
        React.createElement('div', { className: 'integration-stats' },
            React.createElement('div', { className: 'stat-item' },
                React.createElement('strong', null, 'Inventory Count: '),
                data.inventory_count || 'N/A'
            ),
            React.createElement('div', { className: 'stat-item' },
                React.createElement('strong', null, 'Last Sync: '),
                data.last_sync || 'Never'
            )
        ),

        results && React.createElement('div', { className: 'integration-results' },
            React.createElement('h4', null, 'Results:'),
            React.createElement('pre', null, JSON.stringify(results, null, 2))
        )
    );
};

// Initialize the app
document.addEventListener('DOMContentLoaded', function() {
    const dashboardMount = document.getElementById('ffl-bro-dashboard-mount');
    const lipseysMount = document.getElementById('ffl-bro-lipseys-mount');
    
    if (dashboardMount) {
        ReactDOM.render(React.createElement(FFLBroDashboard), dashboardMount);
    }
    
    if (lipseysMount) {
        ReactDOM.render(React.createElement(LipseysIntegration), lipseysMount);
    }
});

// Lipsey's Tools JavaScript Functions
function showResults(data) {
    const resultsDiv = document.getElementById('lipseys-results');
    const outputPre = document.getElementById('lipseys-output');
    if (resultsDiv && outputPre) {
        resultsDiv.style.display = 'block';
        outputPre.textContent = JSON.stringify(data, null, 2);
    }
}

function testLipseysConnection() {
    fetch(fflBroAjax.restUrl + 'lipseys-data', {
        headers: { 'X-WP-Nonce': fflBroAjax.restNonce }
    })
    .then(response => response.json())
    .then(data => showResults(data))
    .catch(error => showResults({error: error.message}));
}

function doImportSKU() {
    const sku = document.getElementById('import-sku').value;
    if (!sku) { alert('Please enter a SKU'); return; }
    
    fetch(fflBroAjax.restUrl + 'lipseys-import', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'X-WP-Nonce': fflBroAjax.restNonce 
        },
        body: JSON.stringify({sku: sku})
    })
    .then(response => response.json())
    .then(data => showResults(data))
    .catch(error => showResults({error: error.message}));
}

function syncAllInventory() {
    if (!confirm('This will sync all inventory from Lipseys. Continue?')) return;
    
    fetch(fflBroAjax.restUrl + 'lipseys-sync', {
        method: 'POST',
        headers: { 'X-WP-Nonce': fflBroAjax.restNonce }
    })
    .then(response => response.json())
    .then(data => showResults(data))
    .catch(error => showResults({error: error.message}));
}

function doValidateSKU() {
    const sku = document.getElementById('validate-sku').value;
    const qty = document.getElementById('validate-qty').value;
    if (!sku) { alert('Please enter a SKU'); return; }
    
    fetch(fflBroAjax.restUrl + 'lipseys-validate', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'X-WP-Nonce': fflBroAjax.restNonce 
        },
        body: JSON.stringify({sku: sku, qty: qty})
    })
    .then(response => response.json())
    .then(data => showResults(data))
    .catch(error => showResults({error: error.message}));
}
ADMIN_JS_EOF

echo "[*] Step 5: Create admin CSS styles"
cat > assets/css/admin.css << 'ADMIN_CSS_EOF'
/* FFL-BRO Enhanced PRO Admin Styles */
.ffl-bro-enhanced-dashboard {
    margin: 20px 0;
}

.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.dashboard-header h2 {
    margin: 0 0 10px 0;
    font-size: 24px;
}

.status-indicators {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.status-item {
    background: rgba(255,255,255,0.2);
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
}

.status-success {
    background: rgba(40, 167, 69, 0.3);
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-left: 4px solid #ddd;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.stat-card.stat-green { border-left-color: #28a745; }
.stat-card.stat-blue { border-left-color: #007bff; }
.stat-card.stat-orange { border-left-color: #fd7e14; }
.stat-card.stat-purple { border-left-color: #6f42c1; }

.stat-icon {
    font-size: 2.5em;
    opacity: 0.8;
}

.stat-content h3 {
    margin: 0;
    font-size: 2em;
    font-weight: bold;
    color: #333;
}

.stat-content p {
    margin: 0;
    color: #666;
    font-size: 0.9em;
}

.dashboard-charts {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.chart-container {
    position: relative;
    height: 400px;
}

.quick-actions {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.quick-actions h3 {
    margin-top: 0;
    color: #333;
    font-size: 1.5em;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.action-button {
    display: block;
    padding: 16px 20px;
    text-decoration: none;
    border-radius: 8px;
    text-align: center;
    font-weight: 600;
    transition: all 0.2s;
    color: white;
}

.action-button:hover {
    transform: translateY(-2px);
    text-decoration: none;
    color: white;
}

.action-blue { background: linear-gradient(135deg, #007bff, #0056b3); }
.action-green { background: linear-gradient(135deg, #28a745, #1e7e34); }
.action-purple { background: linear-gradient(135deg, #6f42c1, #5a2d82); }
.action-orange { background: linear-gradient(135deg, #fd7e14, #dc6502); }
.action-red { background: linear-gradient(135deg, #dc3545, #c82333); }
.action-teal { background: linear-gradient(135deg, #20c997, #17a2b8); }

.ffl-loading {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.ffl-loading h3 {
    color: #666;
    font-size: 1.3em;
    margin: 0;
}

/* Lipseys Integration Styles */
.lipseys-integration {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.integration-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f1f1f1;
}

.integration-header h2 {
    margin: 0;
    color: #333;
}

.status-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-connected { background: #d4edda; color: #155724; }
.status-error { background: #f8d7da; color: #721c24; }
.status-unknown { background: #e2e3e5; color: #383d41; }

.integration-stats {
    display: flex;
    gap: 30px;
    margin-bottom: 25px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.integration-results {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
}

.integration-results h4 {
    margin-top: 0;
    color: #333;
}

.integration-results pre {
    background: #343a40;
    color: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    overflow-x: auto;
    font-size: 13px;
    line-height: 1.4;
}

/* Page Placeholder Styles */
.page-placeholder {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.page-placeholder h3 {
    color: #666;
    font-size: 1.5em;
    margin-bottom: 10px;
}

.page-placeholder p {
    color: #888;
    font-size: 1.1em;
    margin: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .dashboard-stats {
        grid-template-columns: 1fr;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
    
    .status-indicators {
        flex-direction: column;
        gap: 8px;
    }
    
    .integration-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .integration-stats {
        flex-direction: column;
        gap: 15px;
    }
}
ADMIN_CSS_EOF

echo "[*] Step 6: Create Lipsey's tools"
cat > tools/import_one.php << 'IMPORT_EOF'
<?php
// tools/import_one.php — import a single SKU via the Lipsey connector
if (!defined('ABSPATH')) { require '/var/www/html/wp-load.php'; }
$sku = $argv[1] ?? 'RU1022RB';
try {
  $api = new FFLBro_Lipseys_Api();
  $cat = $api->catalog_item($sku);
  $pid = FFLBro_Lipseys_Importer::upsert_product_from_catalog($sku, $cat);
  echo "Imported/updated SKU {$sku} → product #{$pid}\n";
} catch (Throwable $e) {
  echo "ERR: ".$e->getMessage()."\n";
  exit(1);
}
IMPORT_EOF

cat > tools/lipseys_probe.php << 'PROBE_EOF'
<?php
// tools/lipseys_probe.php — discover working endpoints/casing for Lipsey's API
if (!defined('ABSPATH')) { require '/var/www/html/wp-load.php'; }
function http_json($method,$url,$token=null,$body=null){
  $ch=curl_init();
  $hdr=['Content-Type: application/json'];
  if($token){ $hdr[]='Authorization: Bearer '.$token; }
  curl_setopt_array($ch,[
    CURLOPT_URL=>$url, CURLOPT_CUSTOMREQUEST=>strtoupper($method),
    CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>20, CURLOPT_HTTPHEADER=>$hdr
  ]);
  if($body!==null){ curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); }
  $res=curl_exec($ch); $code=curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  $err=curl_error($ch); curl_close($ch);
  return [$code,$res,$err];
}
$base = rtrim(get_option('fflbro_lipseys_base', getenv('LIPSEYS_BASE') ?: 'https://api.lipseys.com'),'/');
$email = get_option('fflbro_lipseys_email', getenv('LIPSEYS_EMAIL') ?: '');
$pass  = get_option('fflbro_lipseys_password', getenv('LIPSEYS_PASSWORD') ?: '');
$sku   = $argv[1] ?? 'RU1022RB';

echo "Base: $base\n";
if(!$email || !$pass){ echo "ERR: Lipsey creds missing in options or env.\n"; exit(1); }

list($c,$r,$e)=http_json('POST',$base.'/auth',null,['Email'=>$email,'Password'=>$pass]);
if($c>=400||!$r){ echo "AUTH FAIL code=$c err=$e body=$r\n"; exit(1); }
$j=json_decode($r,true); $token = $j['token'] ?? $j['Token'] ?? null;
if(!$token){ echo "AUTH OK but no token in body: $r\n"; exit(1); }
echo "Auth OK. Token (trunc): ".substr($token,0,12)."...\n";

$candidates = [
  ['POST', '/items/catalogitem', ['ItemNo'=>$sku], null],
  ['POST', '/Items/CatalogItem', ['ItemNo'=>$sku], null],
  ['GET',  '/items/catalogitem', null, ['ItemNo'=>$sku]],
  ['GET',  '/Items/CatalogItem', null, ['ItemNo'=>$sku]],
  ['POST', '/ApiIntegration/Items/CatalogItem', ['ItemNo'=>$sku], null],
  ['GET',  '/ApiIntegration/Items/CatalogItem', null, ['ItemNo'=>$sku]],

  ['GET',  '/items/pricingandquantity', null, null],
  ['GET',  '/Items/PricingAndQuantity', null, null],
  ['GET',  '/ApiIntegration/Items/PricingAndQuantity', null, null],

  ['POST', '/items/validateitem', ['ItemNo'=>$sku,'Quantity'=>1], null],
  ['POST', '/Items/ValidateItem', ['ItemNo'=>$sku,'Quantity'=>1], null],
  ['POST', '/ApiIntegration/Items/ValidateItem', ['ItemNo'=>$sku,'Quantity'=>1], null],
];

foreach($candidates as $row){
  [$m,$p,$body,$q] = $row; $url = $base.$p;
  if($q){ $url .= (strpos($url,'?')===false?'?':'&') . http_build_query($q); }
  [$code,$res,$err]=http_json($m,$url,$token,$body);
  $snippet = $res ? substr($res,0,160) : '';
  echo sprintf("%-5s %-60s  code=%s  err=%s  body=%s\n", $m, $p.($q?('?'.http_build_query($q)):'') , $code, $err?:'-', $snippet?:'-');
}
PROBE_EOF

cat > tools/peek_products.php << 'PEEK_EOF'
<?php
// tools/peek_products.php — lightweight product/stock peek without mysql client
if (!defined('ABSPATH')) { require '/var/www/html/wp-load.php'; }
global $wpdb;

$q = "
SELECT p.ID,
       p.post_title AS name,
       MAX(CASE WHEN pm.meta_key = '_sku'          THEN pm.meta_value END) AS sku,
       MAX(CASE WHEN pm.meta_key = '_price'        THEN pm.meta_value END) AS price,
       MAX(CASE WHEN pm.meta_key = '_stock'        THEN pm.meta_value END) AS stock,
       MAX(CASE WHEN pm.meta_key = '_stock_status' THEN pm.meta_value END) AS stock_status
FROM {$wpdb->posts} p
LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
WHERE p.post_type = 'product' AND p.post_status = 'publish'
GROUP BY p.ID, p.post_title
ORDER BY p.ID DESC
LIMIT 20";
$rows = $wpdb->get_results($q, ARRAY_A);

if (!$rows) { echo "No rows.\n"; exit; }
$cols = array_keys($rows[0]);
echo implode("\t", $cols), "\n";
foreach ($rows as $r) {
  $line = [];
  foreach ($cols as $c) {
    $v = isset($r[$c]) ? preg_replace('/\s+/', ' ', (string)$r[$c]) : '';
    $line[] = $v;
  }