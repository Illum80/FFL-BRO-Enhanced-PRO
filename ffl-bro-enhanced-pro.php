<?php
/*
Plugin Name: FFL-BRO Enhanced PRO
Description: Complete FFL business management platform - WORKING VERSION WITH TOKEN
Version: 5.1.3
*/

if (!defined("ABSPATH")) exit;

// Include Lipseys API
require_once plugin_dir_path(__FILE__) . "includes/distributors/lipseys.php";
require_once plugin_dir_path(__FILE__) . "includes/quote-generator-advanced.php";

class FFLBroEnhancedPRO {
    public function __construct() {
        add_action("admin_menu", array($this, "add_admin_menu"));
        add_action("admin_init", array($this, "admin_init"));
        add_action("admin_enqueue_scripts", array($this, "enqueue_scripts"));
    }
    
    public function add_admin_menu() {
        add_menu_page("FFL-BRO", "FFL-BRO", "manage_options", "ffl-bro", array($this, "settings_page"));
    }
    
    public function admin_init() {
        register_setting("fflbro_settings", "fflbro_lipseys_email");
        register_setting("fflbro_settings", "fflbro_lipseys_password");
    }
    
    public function enqueue_scripts($hook) {
        if (strpos($hook, "ffl-bro") !== false) {
            wp_enqueue_script("jquery");
            wp_localize_script("jquery", "fflbro_ajax", array(
                "ajax_url" => admin_url("admin-ajax.php"),
                "nonce" => wp_create_nonce("fflbro_nonce")
            ));
        }
    }
    
    public function settings_page() {
        if (isset($_POST["submit"])) {
            update_option("fflbro_lipseys_email", sanitize_email($_POST["email"]));
            update_option("fflbro_lipseys_password", sanitize_text_field($_POST["password"]));
            echo "<div class=\"notice notice-success\"><p>Settings saved!</p></div>";
        }
        
        $email = get_option("fflbro_lipseys_email", "");
        $password = get_option("fflbro_lipseys_password", "");
        ?>
        <div class="wrap">
            <h1>FFL-BRO Enhanced PRO Settings - v5.1.0 WORKING WITH TOKEN</h1>
            <h2>Lipseys Configuration</h2>
            
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="email">Email Address</label></th>
                        <td><input type="email" name="email" id="email" value="<?php echo esc_attr($email); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="password">Password</label></th>
                        <td><input type="password" name="password" id="password" value="<?php echo esc_attr($password); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                
                <p>
                    <button type="button" id="test-connection" class="button">Test Connection</button>
                    <button type="button" id="test-skus" class="button">Test SKU Retrieval</button>
                    <input type="submit" name="submit" class="button-primary" value="Save Changes" />
                </p>
                
                <div id="connection-result"></div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $("#test-connection").click(function() {
                var email = $("#email").val();
                var password = $("#password").val();
                var button = $(this);
                var result = $("#connection-result");
                
                if (!email || !password) {
                    result.html("<div class=\"notice notice-error\"><p>Please enter both email and password</p></div>");
                    return;
                }
                
                button.prop("disabled", true).text("Testing...");
                result.html("<div class=\"notice notice-info\"><p>Testing connection...</p></div>");
                
                $.post(fflbro_ajax.ajax_url, {
                    action: "fflbro_test_lipseys_connection",
                    email: email,
                    password: password,
                    nonce: fflbro_ajax.nonce
                }, function(response) {
                    button.prop("disabled", false).text("Test Connection");
                    
                    console.log("Full response:", response); // Debug log
                    
                    if (response.success) {
                        var token = response.data.token || "Token not provided";
                        result.html("<div class=\"notice notice-success\"><p>✅ " + response.data.message + "<br><strong>Token:</strong> " + token + "</p></div>");
                    } else {
                        result.html("<div class=\"notice notice-error\"><p>❌ Authentication failed: " + response.data + "</p></div>");
                    }
                }).fail(function(xhr, status, error) {
                    button.prop("disabled", false).text("Test Connection");
                    console.log("AJAX Error:", xhr.responseText); // Debug log
                    result.html("<div class=\"notice notice-error\"><p>❌ Connection failed: " + error + "</p></div>");
                });
            });
            
            $("#test-skus").click(function() {
                var email = $("#email").val();
                var password = $("#password").val();
                var button = $(this);
                var result = $("#connection-result");
                
                if (!email || !password) {
                    result.html("<div class=\"notice notice-error\"><p>Please enter both email and password</p></div>");
                    return;
                }
                
                button.prop("disabled", true).text("Testing SKUs...");
                result.html("<div class=\"notice notice-info\"><p>Retrieving sample SKUs...</p></div>");
                
                $.post(fflbro_ajax.ajax_url, {
                    action: "fflbro_test_skus",
                    email: email,
                    password: password,
                    nonce: fflbro_ajax.nonce
                }, function(response) {
                    button.prop("disabled", false).text("Test SKU Retrieval");
                    
                    if (response.success) {
                        var html = "<div class=\"notice notice-success\"><p>✅ " + response.data.message + "</p>";
                        if (response.data.skus) {
                            html += "<ul>";
                            response.data.skus.forEach(function(sku) {
                                html += "<li><strong>" + sku.sku + ":</strong> " + sku.description + " - $" + sku.price + "</li>";
                            });
                            html += "</ul>";
                        }
                        html += "</div>";
                        result.html(html);
                    } else {
                        result.html("<div class=\"notice notice-error\"><p>❌ SKU test failed: " + response.data + "</p></div>");
                    }
                }).fail(function() {
                    button.prop("disabled", false).text("Test SKU Retrieval");
                    result.html("<div class=\"notice notice-error\"><p>❌ SKU request failed</p></div>");
                });
            });
        });
        </script>
        <?php
    }
}

new FFLBroEnhancedPRO();

// Add missing AJAX handlers
add_action('wp_ajax_fflbro_test_lipseys_connection', 'handle_lipseys_connection');
add_action('wp_ajax_nopriv_fflbro_test_lipseys_connection', 'handle_lipseys_connection');

function handle_lipseys_connection() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'fflbro_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    // Get credentials
    $email = get_option('fflbro_lipseys_email', '');
    $password = get_option('fflbro_lipseys_password', '');
    
    if (empty($email) || empty($password)) {
        wp_send_json_error('Lipseys credentials not configured');
    }
    
    // Test connection using your Lipseys API class
    require_once plugin_dir_path(__FILE__) . 'includes/distributors/lipseys.php';
require_once plugin_dir_path(__FILE__) . "includes/quote-generator-advanced.php";
    $lipseys = new LipseysAPI($email, $password);
    $result = $lipseys->test_connection();
    
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result['message']);
    }
}

// Add missing AJAX handlers

// AJAX handler for SKU testing
add_action('wp_ajax_fflbro_test_skus', 'handle_lipseys_sku_test');
add_action('wp_ajax_nopriv_fflbro_test_skus', 'handle_lipseys_sku_test');

function handle_lipseys_sku_test() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'fflbro_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    // Get credentials
    $email = get_option('fflbro_lipseys_email', '');
    $password = get_option('fflbro_lipseys_password', '');
    
    if (empty($email) || empty($password)) {
        wp_send_json_error('Lipseys credentials not configured');
    }
    
    // Test SKU retrieval using Lipseys API class
    require_once plugin_dir_path(__FILE__) . 'includes/distributors/lipseys.php';
require_once plugin_dir_path(__FILE__) . "includes/quote-generator-advanced.php";
    $lipseys = new LipseysAPI($email, $password);
    $result = $lipseys->test_sku_retrieval();
    
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result['message'] ?? 'Unknown SKU retrieval error');
    }
}
