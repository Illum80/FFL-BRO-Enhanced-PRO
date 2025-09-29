#!/bin/bash

###############################################################################
# Automated Form 4473 Module Installer
# NO MANUAL EDITS - Full Automation
###############################################################################

set -e

echo "🚀 Installing Form 4473 Processing Module..."

# Switch to pi ownership for git operations
sudo chown -R pi:pi .

# Create directory structure
mkdir -p modules
mkdir -p templates/modules
mkdir -p assets/{js,css}/modules

# Create Form 4473 Module File
cat > modules/form-4473-processing.php << 'EOL'
<?php
/**
 * FFL-BRO Form 4473 Processing Module
 * Features: Digital forms, compliance tracking, audit trails
 */

if (!defined('ABSPATH')) exit;

class FFL_BRO_Form_4473_Processing {
    
    private $version = '2.1.0';
    
    public function __construct() {
        $this->init_hooks();
        $this->create_4473_tables();
    }
    
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menus'), 20);
        add_action('wp_ajax_fflbro_create_4473', array($this, 'ajax_create_4473'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    private function create_4473_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $form_4473_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}fflbro_form_4473 (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            form_number varchar(100) NOT NULL UNIQUE,
            customer_id mediumint(9),
            transaction_type enum('sale', 'transfer', 'return') DEFAULT 'sale',
            status enum('draft', 'in_progress', 'pending_approval', 'approved', 'denied', 'cancelled') DEFAULT 'draft',
            
            -- Firearm Information
            firearms_json text NOT NULL,
            
            -- Transferee Information
            transferee_first_name varchar(100) NOT NULL,
            transferee_middle_name varchar(100),
            transferee_last_name varchar(100) NOT NULL,
            transferee_date_of_birth date NOT NULL,
            transferee_gender enum('M', 'F', 'X') NOT NULL,
            transferee_ethnicity enum('hispanic', 'not_hispanic') NOT NULL,
            transferee_race varchar(500) NOT NULL,
            transferee_address varchar(255) NOT NULL,
            transferee_city varchar(100) NOT NULL,
            transferee_state varchar(50) NOT NULL,
            transferee_zip varchar(20) NOT NULL,
            transferee_phone varchar(20),
            transferee_email varchar(255),
            
            -- Background Check Questions
            question_a boolean DEFAULT false,
            question_b boolean DEFAULT false,
            question_c boolean DEFAULT false,
            question_d boolean DEFAULT false,
            question_e boolean DEFAULT false,
            question_f boolean DEFAULT false,
            question_g boolean DEFAULT false,
            question_h boolean DEFAULT false,
            question_i boolean DEFAULT false,
            question_j boolean DEFAULT false,
            question_k boolean DEFAULT false,
            question_l boolean DEFAULT false,
            
            -- Background Check Information
            nics_transaction_number varchar(100),
            background_check_date datetime,
            background_check_result enum('proceed', 'delay', 'deny') DEFAULT 'proceed',
            background_check_notes text,
            
            -- Compliance and Audit
            created_by int(11) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            PRIMARY KEY (id),
            UNIQUE KEY form_number (form_number),
            KEY status_idx (status),
            KEY created_at_idx (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($form_4473_table);
    }
    
    public function ajax_create_4473() {
        check_ajax_referer('fflbro_nonce', 'nonce');
        
        try {
            $form_id = $this->create_4473();
            wp_send_json_success(array(
                'form_id' => $form_id,
                'message' => 'Form 4473 created successfully'
            ));
        } catch (Exception $e) {
            wp_send_json_error('Failed to create Form 4473: ' . $e->getMessage());
        }
    }
    
    private function create_4473() {
        global $wpdb;
        
        $form_number = $this->generate_form_number();
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'fflbro_form_4473',
            array(
                'form_number' => $form_number,
                'status' => 'draft',
                'firearms_json' => wp_json_encode(array()),
                'created_by' => get_current_user_id()
            ),
            array('%s', '%s', '%s', '%d')
        );
        
        if (!$result) {
            throw new Exception('Failed to create Form 4473');
        }
        
        return $wpdb->insert_id;
    }
    
    private function generate_form_number() {
        return '4473' . date('Ymd') . sprintf('%04d', rand(1000, 9999));
    }
    
    public function add_admin_menus() {
        add_submenu_page(
            'ffl-bro',
            'Form 4473 Processing',
            'Form 4473',
            'manage_options',
            'fflbro-4473',
            array($this, 'render_4473_page')
        );
    }
    
    public function render_4473_page() {
        global $wpdb;
        
        $total_forms = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_form_4473");
        $pending_forms = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_form_4473 WHERE status IN ('draft', 'in_progress', 'pending_approval')");
        $approved_forms = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_form_4473 WHERE status = 'approved'");
        
        echo '<div class="wrap">';
        echo '<h1>🎯 Form 4473 Processing</h1>';
        echo '<div class="notice notice-success"><p>✅ Digital ATF Form Processing System Active</p></div>';
        
        echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">';
        
        echo '<div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center;">';
        echo '<h3 style="margin: 0 0 10px 0; color: #1e40af;">Total Forms</h3>';
        echo '<div style="font-size: 32px; font-weight: bold; color: #1e40af;">' . $total_forms . '</div>';
        echo '</div>';
        
        echo '<div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center;">';
        echo '<h3 style="margin: 0 0 10px 0; color: #f59e0b;">Pending</h3>';
        echo '<div style="font-size: 32px; font-weight: bold; color: #f59e0b;">' . $pending_forms . '</div>';
        echo '</div>';
        
        echo '<div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center;">';
        echo '<h3 style="margin: 0 0 10px 0; color: #10b981;">Approved</h3>';
        echo '<div style="font-size: 32px; font-weight: bold; color: #10b981;">' . $approved_forms . '</div>';
        echo '</div>';
        
        echo '</div>';
        
        echo '<div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0;">';
        echo '<h2>🚀 Form 4473 Management</h2>';
        echo '<button id="create-new-4473" class="button button-primary" style="margin-right: 10px;">Create New Form 4473</button>';
        echo '<button id="search-4473s" class="button button-secondary" style="margin-right: 10px;">Search Forms</button>';
        echo '<button id="compliance-report" class="button button-secondary">Compliance Report</button>';
        
        echo '<div id="result-message" style="margin-top: 15px;"></div>';
        echo '</div>';
        
        echo '</div>';
        
        echo '<script>';
        echo 'jQuery(document).ready(function($) {';
        echo '  $("#create-new-4473").click(function() {';
        echo '    var button = $(this);';
        echo '    button.prop("disabled", true).text("Creating...");';
        echo '    $.post(ajaxurl, {';
        echo '      action: "fflbro_create_4473",';
        echo '      nonce: "' . wp_create_nonce('fflbro_nonce') . '"';
        echo '    }, function(response) {';
        echo '      button.prop("disabled", false).text("Create New Form 4473");';
        echo '      if (response.success) {';
        echo '        $("#result-message").html("<div class=\"notice notice-success\"><p>✅ " + response.data.message + "</p></div>");';
        echo '        setTimeout(function() { location.reload(); }, 2000);';
        echo '      } else {';
        echo '        $("#result-message").html("<div class=\"notice notice-error\"><p>❌ " + response.data + "</p></div>");';
        echo '      }';
        echo '    });';
        echo '  });';
        echo '});';
        echo '</script>';
    }
    
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'fflbro-4473') !== false) {
            wp_enqueue_script('jquery');
        }
    }
}

new FFL_BRO_Form_4473_Processing();
EOL

# Update main plugin file to auto-load modules
if ! grep -q "Load modules" ffl-bro-enhanced-pro.php; then
    sed -i '/require_once.*quote-generator-advanced\.php/a\\n// Load modules automatically\nif (is_dir(plugin_dir_path(__FILE__) . "modules/")) {\n    foreach (glob(plugin_dir_path(__FILE__) . "modules/*.php") as $module_file) {\n        require_once $module_file;\n    }\n}' ffl-bro-enhanced-pro.php
fi

# Update STATUS.md
echo "" >> STATUS.md
echo "🎯 AUTOMATED UPDATE - $(date)" >> STATUS.md  
echo "✅ Added Form 4473 Processing Module (AUTOMATED)" >> STATUS.md
echo "📋 Features: Digital ATF forms, compliance tracking, database tables" >> STATUS.md
echo "🔄 Next: Customer Management Module" >> STATUS.md

# Commit to git
git add .
git commit -m "AUTOMATED: Added Form 4473 Processing Module with digital ATF forms"
git push origin main

# Set WordPress ownership back
sudo chown -R www-data:www-data .

echo "✅ Form 4473 Module installed successfully!"
echo "🌐 Go to: http://192.168.2.161:8182/wp-admin?page=ffl-bro"
echo "📋 Look for new 'Form 4473' submenu"
