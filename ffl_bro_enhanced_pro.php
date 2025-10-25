<?php
/**
 * Plugin Name: FFL-BRO Enhanced PRO
 * Plugin URI: https://github.com/Illum80/FFL-BRO-Enhanced-PRO
 * Description: Complete FFL business management system with live data integration
 * Version: 4.1.0
 * Author: FFL-BRO Team
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FFLBRO_ENHANCED_VERSION', '4.1.0');
define('FFLBRO_ENHANCED_PATH', plugin_dir_path(__FILE__));
define('FFLBRO_ENHANCED_URL', plugin_dir_url(__FILE__));

class FFLBROEnhancedPRO {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_assets'));
        add_action('wp_ajax_fflbro_api', array($this, 'ajax_handler'));
        add_action('wp_ajax_nopriv_fflbro_api', array($this, 'ajax_handler'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Database setup
        register_activation_hook(__FILE__, array($this, 'create_tables'));
        
        // Shortcodes for frontend
        add_shortcode('fflbro_dashboard', array($this, 'dashboard_shortcode'));
        add_shortcode('fflbro_quote_form', array($this, 'quote_form_shortcode'));
        add_shortcode('fflbro_training', array($this, 'training_shortcode'));
    }
    
    public function init() {
        // Initialize session for forms
        if (!session_id()) {
            session_start();
        }
    }
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Leads table
        $table_leads = $wpdb->prefix . 'fflbro_leads';
        $sql_leads = "CREATE TABLE $table_leads (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            source varchar(50) NOT NULL,
            campaign_id varchar(100),
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20),
            interest varchar(255),
            status varchar(20) DEFAULT 'new',
            score int(3) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Form 4473 table
        $table_4473 = $wpdb->prefix . 'fflbro_form_4473';
        $sql_4473 = "CREATE TABLE $table_4473 (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            customer_id int(11),
            section_1 longtext,
            section_2 longtext,
            section_3 longtext,
            section_4 longtext,
            status varchar(20) DEFAULT 'draft',
            compliance_score int(3) DEFAULT 0,
            audit_log longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Training table
        $table_training = $wpdb->prefix . 'fflbro_training';
        $sql_training = "CREATE TABLE $table_training (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            course_name varchar(255) NOT NULL,
            instructor varchar(100),
            capacity int(3) DEFAULT 20,
            enrolled int(3) DEFAULT 0,
            start_date datetime,
            end_date datetime,
            status varchar(20) DEFAULT 'scheduled',
            price decimal(10,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Quotes table
        $table_quotes = $wpdb->prefix . 'fflbro_quotes';
        $sql_quotes = "CREATE TABLE $table_quotes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            quote_number varchar(50) NOT NULL,
            customer_name varchar(100) NOT NULL,
            customer_email varchar(100),
            items longtext,
            subtotal decimal(10,2) DEFAULT 0.00,
            fees decimal(10,2) DEFAULT 0.00,
            total decimal(10,2) DEFAULT 0.00,
            status varchar(20) DEFAULT 'pending',
            valid_until datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Inventory table for GunBroker/Lipseys
        $table_inventory = $wpdb->prefix . 'fflbro_inventory';
        $sql_inventory = "CREATE TABLE $table_inventory (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            sku varchar(100) NOT NULL,
            upc varchar(50),
            title varchar(255) NOT NULL,
            manufacturer varchar(100),
            category varchar(100),
            price decimal(10,2) DEFAULT 0.00,
            cost decimal(10,2) DEFAULT 0.00,
            quantity int(5) DEFAULT 0,
            source varchar(50) DEFAULT 'manual',
            source_id varchar(100),
            gunbroker_listed tinyint(1) DEFAULT 0,
            lipseys_sync tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Automation workflows table
        $table_workflows = $wpdb->prefix . 'fflbro_workflows';
        $sql_workflows = "CREATE TABLE $table_workflows (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            trigger_type varchar(100) NOT NULL,
            trigger_conditions longtext,
            actions longtext,
            status varchar(20) DEFAULT 'active',
            executions int(10) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_leads);
        dbDelta($sql_4473);
        dbDelta($sql_training);
        dbDelta($sql_quotes);
        dbDelta($sql_inventory);
        dbDelta($sql_workflows);
        
        // Insert sample data for testing
        $this->insert_sample_data();
    }
    
    private function insert_sample_data() {
        global $wpdb;
        
        // Sample leads
        $wpdb->insert(
            $wpdb->prefix . 'fflbro_leads',
            array(
                'source' => 'facebook',
                'campaign_id' => 'FB_CAMP_001',
                'name' => 'John Smith',
                'email' => 'john.smith@email.com',
                'phone' => '555-0123',
                'interest' => 'CCW Training',
                'status' => 'hot',
                'score' => 85
            )
        );
        
        // Sample training courses
        $wpdb->insert(
            $wpdb->prefix . 'fflbro_training',
            array(
                'course_name' => 'Basic Firearms Safety',
                'instructor' => 'Mike Johnson',
                'capacity' => 15,
                'enrolled' => 8,
                'start_date' => date('Y-m-d H:i:s', strtotime('+1 week')),
                'end_date' => date('Y-m-d H:i:s', strtotime('+1 week +4 hours')),
                'price' => 125.00
            )
        );
        
        // Sample inventory
        $wpdb->insert(
            $wpdb->prefix . 'fflbro_inventory',
            array(
                'sku' => 'GLOCK-17-GEN5',
                'upc' => '764503026775',
                'title' => 'Glock 17 Gen 5 9mm',
                'manufacturer' => 'Glock',
                'category' => 'Handguns',
                'price' => 599.99,
                'cost' => 475.00,
                'quantity' => 3,
                'source' => 'lipseys'
            )
        );
        
        // Sample workflow
        $wpdb->insert(
            $wpdb->prefix . 'fflbro_workflows',
            array(
                'name' => 'New Lead Follow-up',
                'trigger_type' => 'lead_created',
                'trigger_conditions' => json_encode(array('source' => 'facebook')),
                'actions' => json_encode(array(
                    array('type' => 'email', 'template' => 'welcome_email'),
                    array('type' => 'assign_score', 'value' => 50)
                ))
            )
        );
    }
    
    public function admin_menu() {
        add_menu_page(
            'FFL-BRO Enhanced PRO',
            'FFL-BRO PRO',
            'manage_options',
            'fflbro-enhanced',
            array($this, 'admin_dashboard'),
            'dashicons-store',
            30
        );
        
        add_submenu_page(
            'fflbro-enhanced',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'fflbro-enhanced',
            array($this, 'admin_dashboard')
        );
        
        add_submenu_page(
            'fflbro-enhanced',
            'Leads Management',
            'Leads',
            'manage_options',
            'fflbro-leads',
            array($this, 'leads_page')
        );
        
        add_submenu_page(
            'fflbro-enhanced',
            'Form 4473',
            'Form 4473',
            'manage_options',
            'fflbro-4473',
            array($this, 'form_4473_page')
        );
        
        add_submenu_page(
            'fflbro-enhanced',
            'Training Management',
            'Training',
            'manage_options',
            'fflbro-training',
            array($this, 'training_page')
        );
        
        add_submenu_page(
            'fflbro-enhanced',
            'Quote Generator',
            'Quotes',
            'manage_options',
            'fflbro-quotes',
            array($this, 'quotes_page')
        );
        
        add_submenu_page(
            'fflbro-enhanced',
            'GunBroker Integration',
            'GunBroker',
            'manage_options',
            'fflbro-gunbroker',
            array($this, 'gunbroker_page')
        );
        
        add_submenu_page(
            'fflbro-enhanced',
            'Lipseys Integration',
            'Lipseys',
            'manage_options',
            'fflbro-lipseys',
            array($this, 'lipseys_page')
        );
        
        add_submenu_page(
            'fflbro-enhanced',
            'Automation',
            'Automation',
            'manage_options',
            'fflbro-automation',
            array($this, 'automation_page')
        );
        
        add_submenu_page(
            'fflbro-enhanced',
            'Settings',
            'Settings',
            'manage_options',
            'fflbro-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_assets($hook) {
        if (strpos($hook, 'fflbro') === false) {
            return;
        }
        
        wp_enqueue_script('fflbro-admin-js', FFLBRO_ENHANCED_URL . 'assets/admin.js', array(), FFLBRO_ENHANCED_VERSION, true);
        wp_enqueue_style('fflbro-admin-css', FFLBRO_ENHANCED_URL . 'assets/admin.css', array(), FFLBRO_ENHANCED_VERSION);
        
        // Localize script for AJAX
        wp_localize_script('fflbro-admin-js', 'fflbro_ajax', array(
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fflbro_nonce'),
            'rest_url' => rest_url('fflbro/v1/'),
            'rest_nonce' => wp_create_nonce('wp_rest')
        ));
    }
    
    public function frontend_assets() {
        wp_enqueue_script('fflbro-frontend-js', FFLBRO_ENHANCED_URL . 'assets/frontend.js', array('jquery'), FFLBRO_ENHANCED_VERSION, true);
        wp_enqueue_style('fflbro-frontend-css', FFLBRO_ENHANCED_URL . 'assets/frontend.css', array(), FFLBRO_ENHANCED_VERSION);
    }
    
    public function register_rest_routes() {
        register_rest_route('fflbro/v1', '/leads', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_leads'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route('fflbro/v1', '/leads', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_lead'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('fflbro/v1', '/inventory', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_inventory'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route('fflbro/v1', '/gunbroker/sync', array(
            'methods' => 'POST',
            'callback' => array($this, 'sync_gunbroker'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route('fflbro/v1', '/lipseys/sync', array(
            'methods' => 'POST',
            'callback' => array($this, 'sync_lipseys'),
            'permission_callback' => array($this, 'check_permissions')
        ));
    }
    
    public function check_permissions() {
        return current_user_can('manage_options');
    }
    
    public function ajax_handler() {
        check_ajax_referer('fflbro_nonce', 'nonce');
        
        $action = sanitize_text_field($_POST['fflbro_action']);
        
        switch ($action) {
            case 'get_dashboard_stats':
                $this->get_dashboard_stats();
                break;
            case 'save_form_4473':
                $this->save_form_4473();
                break;
            case 'generate_quote':
                $this->generate_quote();
                break;
            case 'sync_facebook_leads':
                $this->sync_facebook_leads();
                break;
            default:
                wp_die('Invalid action');
        }
    }
    
    private function get_dashboard_stats() {
        global $wpdb;
        
        $stats = array(
            'leads_today' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_leads WHERE DATE(created_at) = CURDATE()"),
            'leads_total' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_leads"),
            'forms_pending' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_form_4473 WHERE status = 'pending'"),
            'training_upcoming' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_training WHERE start_date > NOW()"),
            'quotes_pending' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_quotes WHERE status = 'pending'"),
            'inventory_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_inventory"),
            'revenue_month' => $wpdb->get_var("SELECT SUM(total) FROM {$wpdb->prefix}fflbro_quotes WHERE status = 'accepted' AND MONTH(created_at) = MONTH(NOW())")
        );
        
        wp_send_json_success($stats);
    }
    
    public function admin_dashboard() {
        ?>
        <div class="wrap">
            <h1>FFL-BRO Enhanced PRO Dashboard</h1>
            <div id="fflbro-dashboard-root"></div>
        </div>
        
        <script>
        // Initialize React Dashboard
        document.addEventListener('DOMContentLoaded', function() {
            const dashboardRoot = document.getElementById('fflbro-dashboard-root');
            if (dashboardRoot && typeof FFLBRODashboard !== 'undefined') {
                ReactDOM.render(React.createElement(FFLBRODashboard), dashboardRoot);
            }
        });
        </script>
        
        <style>
        .fflbro-dashboard {
            background: #f0f2f5;
            padding: 20px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #007cba;
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #23282d;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #007cba;
            margin-bottom: 5px;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .feature-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s;
        }
        
        .feature-card:hover {
            transform: translateY(-2px);
        }
        
        .feature-card .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .feature-card h4 {
            margin: 0 0 10px 0;
            color: #23282d;
        }
        
        .feature-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .btn {
            background: #007cba;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .btn:hover {
            background: #005a87;
            color: white;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-online { background: #46b450; }
        .status-offline { background: #dc3232; }
        .status-warning { background: #ffb900; }
        </style>
        
        <?php
        // Dashboard HTML structure
        echo '<div class="fflbro-dashboard">';
        echo '<div class="dashboard-grid">';
        
        // Stats cards will be populated by JavaScript
        $stats = array(
            array('title' => 'New Leads Today', 'id' => 'leads-today', 'icon' => '👥'),
            array('title' => 'Total Leads', 'id' => 'leads-total', 'icon' => '📊'),
            array('title' => 'Pending Forms', 'id' => 'forms-pending', 'icon' => '📋'),
            array('title' => 'Upcoming Training', 'id' => 'training-upcoming', 'icon' => '🎓'),
            array('title' => 'Pending Quotes', 'id' => 'quotes-pending', 'icon' => '💰'),
            array('title' => 'Inventory Items', 'id' => 'inventory-count', 'icon' => '📦'),
        );
        
        foreach ($stats as $stat) {
            echo "<div class='stat-card'>";
            echo "<h3>{$stat['icon']} {$stat['title']}</h3>";
            echo "<div class='number' id='{$stat['id']}'>-</div>";
            echo "<div class='trend'>Loading...</div>";
            echo "</div>";
        }
        
        echo '</div>'; // End dashboard-grid
        
        // Feature cards
        echo '<div class="feature-grid">';
        
        $features = array(
            array('title' => 'Facebook Leads', 'desc' => 'Import and manage Facebook ad leads', 'url' => 'admin.php?page=fflbro-leads', 'icon' => '📘', 'status' => 'online'),
            array('title' => 'Form 4473', 'desc' => 'Digital ATF compliance forms', 'url' => 'admin.php?page=fflbro-4473', 'icon' => '📋', 'status' => 'online'),
            array('title' => 'Training', 'desc' => 'Course management system', 'url' => 'admin.php?page=fflbro-training', 'icon' => '🎓', 'status' => 'online'),
            array('title' => 'Quote Generator', 'desc' => 'Professional quote creation', 'url' => 'admin.php?page=fflbro-quotes', 'icon' => '💰', 'status' => 'online'),
            array('title' => 'GunBroker', 'desc' => 'Marketplace integration', 'url' => 'admin.php?page=fflbro-gunbroker', 'icon' => '🎯', 'status' => 'warning'),
            array('title' => 'Lipseys', 'desc' => 'Distributor integration', 'url' => 'admin.php?page=fflbro-lipseys', 'icon' => '🚛', 'status' => 'warning'),
            array('title' => 'Automation', 'desc' => 'Workflow automation', 'url' => 'admin.php?page=fflbro-automation', 'icon' => '⚡', 'status' => 'online'),
            array('title' => 'Compliance', 'desc' => 'Monitor compliance score', 'url' => 'admin.php?page=fflbro-enhanced', 'icon' => '🛡️', 'status' => 'online'),
        );
        
        foreach ($features as $feature) {
            echo "<div class='feature-card'>";
            echo "<div class='icon'>{$feature['icon']}</div>";
            echo "<h4>{$feature['title']}</h4>";
            echo "<p>{$feature['desc']}</p>";
            echo "<div><span class='status-indicator status-{$feature['status']}'></span>";
            echo ($feature['status'] == 'online') ? 'Active' : 'Setup Required';
            echo "</div>";
            echo "<div style='margin-top: 15px;'>";
            echo "<a href='{$feature['url']}' class='btn'>Open</a>";
            echo "</div>";
            echo "</div>";
        }
        
        echo '</div>'; // End feature-grid
        echo '</div>'; // End fflbro-dashboard
        
        // Load stats via AJAX
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Load dashboard stats
            $.post(ajaxurl, {
                action: 'fflbro_api',
                fflbro_action: 'get_dashboard_stats',
                nonce: '<?php echo wp_create_nonce('fflbro_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    const stats = response.data;
                    $('#leads-today').text(stats.leads_today || 0);
                    $('#leads-total').text(stats.leads_total || 0);
                    $('#forms-pending').text(stats.forms_pending || 0);
                    $('#training-upcoming').text(stats.training_upcoming || 0);
                    $('#quotes-pending').text(stats.quotes_pending || 0);
                    $('#inventory-count').text(stats.inventory_count || 0);
                }
            });
        });
        </script>
        <?php
    }
    
    public function leads_page() {
        global $wpdb;
        $leads = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}fflbro_leads ORDER BY created_at DESC LIMIT 50");
        
        ?>
        <div class="wrap">
            <h1>Lead Management</h1>
            <div class="lead-actions">
                <button class="button button-primary" onclick="syncFacebookLeads()">Sync Facebook Leads</button>
                <button class="button" onclick="exportLeads()">Export Leads</button>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Source</th>
                        <th>Interest</th>
                        <th>Score</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td><?php echo esc_html($lead->name); ?></td>
                        <td><?php echo esc_html($lead->email); ?></td>
                        <td><?php echo esc_html($lead->phone); ?></td>
                        <td>
                            <span class="source-<?php echo esc_attr($lead->source); ?>">
                                <?php echo ucfirst($lead->source); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($lead->interest); ?></td>
                        <td>
                            <span class="score-badge score-<?php echo $lead->score >= 80 ? 'hot' : ($lead->score >= 60 ? 'warm' : 'cold'); ?>">
                                <?php echo $lead->score; ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-<?php echo esc_attr($lead->status); ?>">
                                <?php echo ucfirst($lead->status); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($lead->created_at)); ?></td>
                        <td>
                            <button class="button-link" onclick="viewLead(<?php echo $lead->id; ?>)">View</button> |
                            <button class="button-link" onclick="convertLead(<?php echo $lead->id; ?>)">Convert</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
        .lead-actions { margin: 20px 0; }
        .source-facebook { color: #1877f2; font-weight: bold; }
        .source-google { color: #4285f4; font-weight: bold; }
        .source-website { color: #2e7d32; font-weight: bold; }
        .score-badge { 
            padding: 4px 8px; 
            border-radius: 4px; 
            font-weight: bold; 
            color: white; 
        }
        .score-hot { background: #d32f2f; }
        .score-warm { background: #f57c00; }
        .score-cold { background: #1976d2; }
        .status-new { color: #2e7d32; font-weight: bold; }
        .status-contacted { color: #1976d2; font-weight: bold; }
        .status-converted { color: #388e3c; font-weight: bold; }
        </style>
        
        <script>
        function syncFacebookLeads() {
            jQuery.post(ajaxurl, {
                action: 'fflbro_api',
                fflbro_action: 'sync_facebook_leads',
                nonce: '<?php echo wp_create_nonce('fflbro_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('Facebook leads synced successfully!');
                    location.reload();
                } else {
                    alert('Error syncing leads: ' + response.data);
                }
            });
        }
        
        function viewLead(id) {
            // Open lead details modal
            alert('Opening lead #' + id + ' (modal functionality to be implemented)');
        }
        
        function convertLead(id) {
            // Convert lead to customer
            alert('Converting lead #' + id + ' to customer (functionality to be implemented)');
        }
        
        function exportLeads() {
            window.open('<?php echo admin_url('admin-ajax.php?action=fflbro_export_leads'); ?>', '_blank');
        }
        </script>
        <?php
    }
    
    public function form_4473_page() {
        global $wpdb;
        $forms = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}fflbro_form_4473 ORDER BY created_at DESC LIMIT 20");
        
        ?>
        <div class="wrap">
            <h1>Form 4473 Management</h1>
            <div class="form-actions">
                <button class="button button-primary" onclick="createNewForm()">New Form 4473</button>
                <button class="button" onclick="complianceReport()">Compliance Report</button>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Form ID</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Compliance Score</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form): ?>
                    <tr>
                        <td>#<?php echo str_pad($form->id, 6, '0', STR_PAD_LEFT); ?></td>
                        <td>Customer ID: <?php echo $form->customer_id ?: 'N/A'; ?></td>
                        <td>
                            <span class="status-<?php echo esc_attr($form->status); ?>">
                                <?php echo ucfirst($form->status); ?>
                            </span>
                        </td>
                        <td>
                            <span class="compliance-score score-<?php echo $form->compliance_score >= 90 ? 'excellent' : ($form->compliance_score >= 70 ? 'good' : 'needs-review'); ?>">
                                <?php echo $form->compliance_score; ?>%
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($form->created_at)); ?></td>
                        <td><?php echo date('M j, Y', strtotime($form->updated_at)); ?></td>
                        <td>
                            <button class="button-link" onclick="editForm(<?php echo $form->id; ?>)">Edit</button> |
                            <button class="button-link" onclick="reviewForm(<?php echo $form->id; ?>)">Review</button> |
                            <button class="button-link" onclick="printForm(<?php echo $form->id; ?>)">Print</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
        .form-actions { margin: 20px 0; }
        .status-draft { color: #666; }
        .status-pending { color: #f57c00; font-weight: bold; }
        .status-approved { color: #388e3c; font-weight: bold; }
        .status-rejected { color: #d32f2f; font-weight: bold; }
        .compliance-score {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            color: white;
        }
        .score-excellent { background: #388e3c; }
        .score-good { background: #f57c00; }
        .score-needs-review { background: #d32f2f; }
        </style>
        
        <script>
        function createNewForm() {
            window.location.href = '<?php echo admin_url('admin.php?page=fflbro-4473&action=new'); ?>';
        }
        
        function editForm(id) {
            window.location.href = '<?php echo admin_url('admin.php?page=fflbro-4473&action=edit&id='); ?>' + id;
        }
        
        function reviewForm(id) {
            alert('Opening compliance review for form #' + id);
        }
        
        function printForm(id) {
            window.open('<?php echo admin_url('admin-ajax.php?action=fflbro_print_4473&id='); ?>' + id, '_blank');
        }
        
        function complianceReport() {
            window.open('<?php echo admin_url('admin-ajax.php?action=fflbro_compliance_report'); ?>', '_blank');
        }
        </script>
        <?php
    }
    
    public function training_page() {
        global $wpdb;
        $courses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}fflbro_training ORDER BY start_date ASC");
        
        ?>
        <div class="wrap">
            <h1>Training Management</h1>
            <div class="training-actions">
                <button class="button button-primary" onclick="createCourse()">Add New Course</button>
                <button class="button" onclick="enrollmentReport()">Enrollment Report</button>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Course Name</th>
                        <th>Instructor</th>
                        <th>Capacity</th>
                        <th>Enrolled</th>
                        <th>Start Date</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><?php echo esc_html($course->course_name); ?></td>
                        <td><?php echo esc_html($course->instructor); ?></td>
                        <td><?php echo $course->capacity; ?></td>
                        <td>
                            <span class="enrollment-ratio">
                                <?php echo $course->enrolled; ?>/<?php echo $course->capacity; ?>
                                <span class="percentage">(<?php echo round(($course->enrolled / $course->capacity) * 100); ?>%)</span>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y g:i A', strtotime($course->start_date)); ?></td>
                        <td>$<?php echo number_format($course->price, 2); ?></td>
                        <td>
                            <span class="status-<?php echo esc_attr($course->status); ?>">
                                <?php echo ucfirst($course->status); ?>
                            </span>
                        </td>
                        <td>
                            <button class="button-link" onclick="editCourse(<?php echo $course->id; ?>)">Edit</button> |
                            <button class="button-link" onclick="viewEnrollments(<?php echo $course->id; ?>)">Enrollments</button> |
                            <button class="button-link" onclick="sendReminders(<?php echo $course->id; ?>)">Send Reminders</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
        .training-actions { margin: 20px 0; }
        .enrollment-ratio { font-weight: bold; }
        .percentage { color: #666; font-weight: normal; }
        .status-scheduled { color: #1976d2; font-weight: bold; }
        .status-active { color: #388e3c; font-weight: bold; }
        .status-completed { color: #666; }
        .status-cancelled { color: #d32f2f; }
        </style>
        
        <script>
        function createCourse() {
            alert('Opening course creation form (to be implemented)');
        }
        
        function editCourse(id) {
            alert('Editing course #' + id + ' (to be implemented)');
        }
        
        function viewEnrollments(id) {
            alert('Viewing enrollments for course #' + id + ' (to be implemented)');
        }
        
        function sendReminders(id) {
            alert('Sending reminders for course #' + id + ' (to be implemented)');
        }
        
        function enrollmentReport() {
            window.open('<?php echo admin_url('admin-ajax.php?action=fflbro_enrollment_report'); ?>', '_blank');
        }
        </script>
        <?php
    }
    
    public function quotes_page() {
        global $wpdb;
        $quotes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}fflbro_quotes ORDER BY created_at DESC LIMIT 20");
        
        ?>
        <div class="wrap">
            <h1>Quote Generator</h1>
            <div class="quote-actions">
                <button class="button button-primary" onclick="createQuote()">New Quote</button>
                <button class="button" onclick="quoteTemplates()">Manage Templates</button>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Quote #</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Subtotal</th>
                        <th>Fees</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Valid Until</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quotes as $quote): ?>
                    <tr>
                        <td><?php echo esc_html($quote->quote_number); ?></td>
                        <td><?php echo esc_html($quote->customer_name); ?></td>
                        <td><?php echo esc_html($quote->customer_email); ?></td>
                        <td>$<?php echo number_format($quote->subtotal, 2); ?></td>
                        <td>$<?php echo number_format($quote->fees, 2); ?></td>
                        <td class="total-amount">$<?php echo number_format($quote->total, 2); ?></td>
                        <td>
                            <span class="status-<?php echo esc_attr($quote->status); ?>">
                                <?php echo ucfirst($quote->status); ?>
                            </span>
                        </td>
                        <td><?php echo $quote->valid_until ? date('M j, Y', strtotime($quote->valid_until)) : 'N/A'; ?></td>
                        <td>
                            <button class="button-link" onclick="viewQuote(<?php echo $quote->id; ?>)">View</button> |
                            <button class="button-link" onclick="sendQuote(<?php echo $quote->id; ?>)">Send</button> |
                            <button class="button-link" onclick="downloadPDF(<?php echo $quote->id; ?>)">PDF</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
        .quote-actions { margin: 20px 0; }
        .total-amount { font-weight: bold; color: #1976d2; }
        .status-pending { color: #f57c00; font-weight: bold; }
        .status-sent { color: #1976d2; font-weight: bold; }
        .status-accepted { color: #388e3c; font-weight: bold; }
        .status-rejected { color: #d32f2f; }
        .status-expired { color: #666; }
        </style>
        
        <script>
        function createQuote() {
            window.location.href = '<?php echo admin_url('admin.php?page=fflbro-quotes&action=new'); ?>';
        }
        
        function viewQuote(id) {
            window.location.href = '<?php echo admin_url('admin.php?page=fflbro-quotes&action=view&id='); ?>' + id;
        }
        
        function sendQuote(id) {
            if (confirm('Send this quote via email?')) {
                jQuery.post(ajaxurl, {
                    action: 'fflbro_api',
                    fflbro_action: 'send_quote',
                    quote_id: id,
                    nonce: '<?php echo wp_create_nonce('fflbro_nonce'); ?>'
                }, function(response) {
                    alert(response.success ? 'Quote sent successfully!' : 'Error sending quote.');
                });
            }
        }
        
        function downloadPDF(id) {
            window.open('<?php echo admin_url('admin-ajax.php?action=fflbro_quote_pdf&id='); ?>' + id, '_blank');
        }
        
        function quoteTemplates() {
            window.location.href = '<?php echo admin_url('admin.php?page=fflbro-quotes&action=templates'); ?>';
        }
        </script>
        <?php
    }
    
    public function gunbroker_page() {
        ?>
        <div class="wrap">
            <h1>GunBroker Integration</h1>
            <div class="integration-status">
                <h3>🎯 GunBroker Marketplace Integration</h3>
                <p><span class="status-indicator status-warning"></span> Setup Required</p>
            </div>
            
            <div class="integration-setup">
                <h4>Setup Instructions:</h4>
                <ol>
                    <li>Register for GunBroker API access at <a href="https://www.gunbroker.com/api" target="_blank">gunbroker.com/api</a></li>
                    <li>Obtain your API Token and Secret</li>
                    <li>Configure settings below</li>
                    <li>Test connection and sync inventory</li>
                </ol>
                
                <form id="gunbroker-config">
                    <table class="form-table">
                        <tr>
                            <th>API Token</th>
                            <td><input type="text" name="api_token" class="regular-text" placeholder="Enter your GunBroker API token" /></td>
                        </tr>
                        <tr>
                            <th>API Secret</th>
                            <td><input type="password" name="api_secret" class="regular-text" placeholder="Enter your API secret" /></td>
                        </tr>
                        <tr>
                            <th>Sandbox Mode</th>
                            <td><label><input type="checkbox" name="sandbox" checked /> Use sandbox for testing</label></td>
                        </tr>
                    </table>
                    
                    <div class="submit-wrapper">
                        <button type="button" class="button button-primary" onclick="testGunBrokerConnection()">Test Connection</button>
                        <button type="button" class="button" onclick="saveGunBrokerConfig()">Save Configuration</button>
                    </div>
                </form>
            </div>
            
            <div class="integration-features">
                <h4>Available Features:</h4>
                <div class="feature-grid">
                    <div class="feature-item">
                        <h5>📊 Market Analytics</h5>
                        <p>Track pricing trends and competitor analysis</p>
                        <button class="button" disabled>Coming Soon</button>
                    </div>
                    <div class="feature-item">
                        <h5>📦 Bulk Listing</h5>
                        <p>List multiple items with one click</p>
                        <button class="button" disabled>Coming Soon</button>
                    </div>
                    <div class="feature-item">
                        <h5>🔄 Inventory Sync</h5>
                        <p>Automatic inventory synchronization</p>
                        <button class="button" disabled>Coming Soon</button>
                    </div>
                    <div class="feature-item">
                        <h5>📈 Performance Tracking</h5>
                        <p>Monitor listing performance and ROI</p>
                        <button class="button" disabled>Coming Soon</button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .integration-status {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .integration-setup {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .submit-wrapper {
            margin-top: 20px;
        }
        
        .integration-features {
            margin: 30px 0;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .feature-item {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
        }
        
        .feature-item h5 {
            margin: 0 0 10px 0;
            color: #23282d;
        }
        
        .feature-item p {
            color: #666;
            margin-bottom: 15px;
        }
        </style>
        
        <script>
        function testGunBrokerConnection() {
            alert('Testing GunBroker API connection (to be implemented)');
        }
        
        function saveGunBrokerConfig() {
            alert('Saving GunBroker configuration (to be implemented)');
        }
        </script>
        <?php
    }
    
    public function lipseys_page() {
        ?>
        <div class="wrap">
            <h1>Lipseys Integration</h1>
            <div class="integration-status">
                <h3>🚛 Lipseys Distributor Integration</h3>
                <p><span class="status-indicator status-warning"></span> Setup Required</p>
            </div>
            
            <div class="integration-setup">
                <h4>Setup Instructions:</h4>
                <ol>
                    <li>Contact Lipseys to request API access</li>
                    <li>Obtain your dealer credentials and API key</li>
                    <li>Configure settings below</li>
                    <li>Test connection and sync catalog</li>
                </ol>
                
                <form id="lipseys-config">
                    <table class="form-table">
                        <tr>
                            <th>Dealer ID</th>
                            <td><input type="text" name="dealer_id" class="regular-text" placeholder="Enter your Lipseys dealer ID" /></td>
                        </tr>
                        <tr>
                            <th>API Key</th>
                            <td><input type="password" name="api_key" class="regular-text" placeholder="Enter your API key" /></td>
                        </tr>
                        <tr>
                            <th>FFL Number</th>
                            <td><input type="text" name="ffl_number" class="regular-text" placeholder="Enter your FFL number" /></td>
                        </tr>
                    </table>
                    
                    <div class="submit-wrapper">
                        <button type="button" class="button button-primary" onclick="testLipseysConnection()">Test Connection</button>
                        <button type="button" class="button" onclick="saveLipseysConfig()">Save Configuration</button>
                    </div>
                </form>
            </div>
            
            <div class="integration-features">
                <h4>Available Features:</h4>
                <div class="feature-grid">
                    <div class="feature-item">
                        <h5>📋 Catalog Search</h5>
                        <p>Search entire Lipseys catalog in real-time</p>
                        <button class="button" disabled>Coming Soon</button>
                    </div>
                    <div class="feature-item">
                        <h5>🛒 Automated Ordering</h5>
                        <p>Place orders directly from your system</p>
                        <button class="button" disabled>Coming Soon</button>
                    </div>
                    <div class="feature-item">
                        <h5>📦 Inventory Sync</h5>
                        <p>Keep inventory updated with Lipseys stock</p>
                        <button class="button" disabled>Coming Soon</button>
                    </div>
                    <div class="feature-item">
                        <h5>📊 Order Tracking</h5>
                        <p>Track order status and shipping info</p>
                        <button class="button" disabled>Coming Soon</button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function testLipseysConnection() {
            alert('Testing Lipseys API connection (to be implemented)');
        }
        
        function saveLipseysConfig() {
            alert('Saving Lipseys configuration (to be implemented)');
        }
        </script>
        <?php
    }
    
    public function automation_page() {
        global $wpdb;
        $workflows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}fflbro_workflows ORDER BY created_at DESC");
        
        ?>
        <div class="wrap">
            <h1>Workflow Automation</h1>
            <div class="automation-actions">
                <button class="button button-primary" onclick="createWorkflow()">Create Workflow</button>
                <button class="button" onclick="importTemplates()">Import Templates</button>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Workflow Name</th>
                        <th>Trigger</th>
                        <th>Actions</th>
                        <th>Status</th>
                        <th>Executions</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workflows as $workflow): ?>
                    <tr>
                        <td><?php echo esc_html($workflow->name); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $workflow->trigger_type)); ?></td>
                        <td>
                            <?php 
                            $actions = json_decode($workflow->actions, true);
                            echo count($actions) . ' action(s)';
                            ?>
                        </td>
                        <td>
                            <span class="status-<?php echo esc_attr($workflow->status); ?>">
                                <?php echo ucfirst($workflow->status); ?>
                            </span>
                        </td>
                        <td><?php echo number_format($workflow->executions); ?></td>
                        <td><?php echo date('M j, Y', strtotime($workflow->created_at)); ?></td>
                        <td>
                            <button class="button-link" onclick="editWorkflow(<?php echo $workflow->id; ?>)">Edit</button> |
                            <button class="button-link" onclick="testWorkflow(<?php echo $workflow->id; ?>)">Test</button> |
                            <button class="button-link" onclick="toggleWorkflow(<?php echo $workflow->id; ?>, '<?php echo $workflow->status; ?>')"><?php echo $workflow->status == 'active' ? 'Disable' : 'Enable'; ?></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="workflow-templates">
                <h3>Quick Start Templates</h3>
                <div class="template-grid">
                    <div class="template-item">
                        <h4>New Lead Follow-up</h4>
                        <p>Automatically email new leads and assign initial score</p>
                        <button class="button" onclick="useTemplate('lead_followup')">Use Template</button>
                    </div>
                    <div class="template-item">
                        <h4>Training Reminders</h4>
                        <p>Send reminder emails before training sessions</p>
                        <button class="button" onclick="useTemplate('training_reminders')">Use Template</button>
                    </div>
                    <div class="template-item">
                        <h4>Quote Follow-up</h4>
                        <p>Follow up on pending quotes after set time</p>
                        <button class="button" onclick="useTemplate('quote_followup')">Use Template</button>
                    </div>
                    <div class="template-item">
                        <h4>Compliance Alerts</h4>
                        <p>Alert staff when compliance scores drop</p>
                        <button class="button" onclick="useTemplate('compliance_alerts')">Use Template</button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .automation-actions { margin: 20px 0; }
        .workflow-templates {
            margin: 40px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .template-item {
            background: #fff;
            padding: 20px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .template-item h4 {
            margin: 0 0 10px 0;
            color: #23282d;
        }
        .template-item p {
            color: #666;
            margin-bottom: 15px;
        }
        .status-active { color: #388e3c; font-weight: bold; }
        .status-inactive { color: #d32f2f; }
        </style>
        
        <script>
        function createWorkflow() {
            alert('Opening workflow builder (to be implemented)');
        }
        
        function editWorkflow(id) {
            alert('Editing workflow #' + id + ' (to be implemented)');
        }
        
        function testWorkflow(id) {
            alert('Testing workflow #' + id + ' (to be implemented)');
        }
        
        function toggleWorkflow(id, status) {
            const action = status === 'active' ? 'disable' : 'enable';
            if (confirm('Are you sure you want to ' + action + ' this workflow?')) {
                alert('Toggling workflow #' + id + ' (to be implemented)');
            }
        }
        
        function useTemplate(template) {
            alert('Using template: ' + template + ' (to be implemented)');
        }
        
        function importTemplates() {
            alert('Importing workflow templates (to be implemented)');
        }
        </script>
        <?php
    }
    
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>FFL-BRO Enhanced PRO Settings</h1>
            
            <div class="settings-tabs">
                <ul class="nav-tab-wrapper">
                    <li><a href="#general" class="nav-tab nav-tab-active">General</a></li>
                    <li><a href="#integrations" class="nav-tab">Integrations</a></li>
                    <li><a href="#compliance" class="nav-tab">Compliance</a></li>
                    <li><a href="#notifications" class="nav-tab">Notifications</a></li>
                </ul>
            </div>
            
            <div class="tab-content">
                <div id="general" class="tab-pane active">
                    <h3>General Settings</h3>
                    <form id="general-settings">
                        <table class="form-table">
                            <tr>
                                <th>Business Name</th>
                                <td><input type="text" name="business_name" class="regular-text" value="FFL-BRO Business" /></td>
                            </tr>
                            <tr>
                                <th>FFL Number</th>
                                <td><input type="text" name="ffl_number" class="regular-text" placeholder="e.g. 1-12-345-67-AB-12345" /></td>
                            </tr>
                            <tr>
                                <th>Contact Email</th>
                                <td><input type="email" name="contact_email" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th>Phone Number</th>
                                <td><input type="tel" name="phone" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th>Time Zone</th>
                                <td>
                                    <select name="timezone">
                                        <option value="America/New_York">Eastern Time</option>
                                        <option value="America/Chicago">Central Time</option>
                                        <option value="America/Denver">Mountain Time</option>
                                        <option value="America/Los_Angeles">Pacific Time</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                
                <div id="integrations" class="tab-pane">
                    <h3>Integration Status</h3>
                    <div class="integration-status-grid">
                        <div class="status-card">
                            <h4>📘 Facebook Leads</h4>
                            <span class="status-indicator status-online"></span> Active
                            <p>Connected and syncing leads</p>
                            <button class="button">Configure</button>
                        </div>
                        <div class="status-card">
                            <h4>🎯 GunBroker</h4>
                            <span class="status-indicator status-warning"></span> Setup Required
                            <p>API credentials needed</p>
                            <button class="button">Setup</button>
                        </div>
                        <div class="status-card">
                            <h4>🚛 Lipseys</h4>
                            <span class="status-indicator status-warning"></span> Setup Required
                            <p>Dealer credentials needed</p>
                            <button class="button">Setup</button>
                        </div>
                        <div class="status-card">
                            <h4>📧 Email Marketing</h4>
                            <span class="status-indicator status-offline"></span> Not Connected
                            <p>Connect your email service</p>
                            <button class="button">Connect</button>
                        </div>
                    </div>
                </div>
                
                <div id="compliance" class="tab-pane">
                    <h3>Compliance Settings</h3>
                    <form id="compliance-settings">
                        <table class="form-table">
                            <tr>
                                <th>Auto-backup Forms</th>
                                <td><label><input type="checkbox" name="auto_backup" checked /> Automatically backup Form 4473s</label></td>
                            </tr>
                            <tr>
                                <th>Audit Logging</th>
                                <td><label><input type="checkbox" name="audit_logging" checked /> Enable detailed audit logging</label></td>
                            </tr>
                            <tr>
                                <th>Compliance Alerts</th>
                                <td><label><input type="checkbox" name="compliance_alerts" checked /> Send alerts for compliance issues</label></td>
                            </tr>
                            <tr>
                                <th>Retention Period</th>
                                <td>
                                    <select name="retention_period">
                                        <option value="20">20 years (ATF requirement)</option>
                                        <option value="25">25 years</option>
                                        <option value="30">30 years</option>
                                        <option value="permanent">Permanent</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                
                <div id="notifications" class="tab-pane">
                    <h3>Notification Settings</h3>
                    <form id="notification-settings">
                        <table class="form-table">
                            <tr>
                                <th>New Lead Notifications</th>
                                <td><label><input type="checkbox" name="new_lead_notifications" checked /> Email me when new leads arrive</label></td>
                            </tr>
                            <tr>
                                <th>Form Completion Alerts</th>
                                <td><label><input type="checkbox" name="form_completion_alerts" checked /> Notify when forms are completed</label></td>
                            </tr>
                            <tr>
                                <th>Training Reminders</th>
                                <td><label><input type="checkbox" name="training_reminders" checked /> Send training session reminders</label></td>
                            </tr>
                            <tr>
                                <th>Quote Expiration Alerts</th>
                                <td><label><input type="checkbox" name="quote_expiration_alerts" checked /> Alert before quotes expire</label></td>
                            </tr>
                        </table>
                    </form>
                </div>
            </div>
            
            <div class="submit-wrapper">
                <button type="button" class="button button-primary" onclick="saveSettings()">Save All Settings</button>
                <button type="button" class="button" onclick="resetSettings()">Reset to Defaults</button>
            </div>
        </div>
        
        <style>
        .settings-tabs { margin: 20px 0; }
        .nav-tab-wrapper { border-bottom: 1px solid #ccc; }
        .nav-tab { 
            display: inline-block; 
            padding: 10px 15px; 
            margin: 0 5px -1px 0; 
            border: 1px solid #ccc; 
            border-bottom: none; 
            background: #f1f1f1; 
            cursor: pointer; 
        }
        .nav-tab-active { background: #fff; border-bottom: 1px solid #fff; }
        .tab-content { margin: 20px 0; }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
        .integration-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .status-card {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .status-card h4 {
            margin: 0 0 10px 0;
            color: #23282d;
        }
        .status-card p {
            color: #666;
            margin: 10px 0 15px 0;
        }
        .submit-wrapper {
            margin: 30px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        </style>
        
        <script>
        // Tab switching
        jQuery(document).ready(function($) {
            $('.nav-tab').click(function(e) {
                e.preventDefault();
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-pane').removeClass('active');
                const target = $(this).attr('href');
                $(target).addClass('active');
            });
        });
        
        function saveSettings() {
            alert('Saving all settings (to be implemented)');
        }
        
        function resetSettings() {
            if (confirm('Are you sure you want to reset all settings to defaults?')) {
                alert('Resetting settings (to be implemented)');
            }
        }
        </script>
        <?php
    }
    
    // Shortcode handlers
    public function dashboard_shortcode($atts) {
        return '<div id="fflbro-dashboard-shortcode">Loading dashboard...</div>';
    }
    
    public function quote_form_shortcode($atts) {
        return '<div id="fflbro-quote-form">Loading quote form...</div>';
    }
    
    public function training_shortcode($atts) {
        return '<div id="fflbro-training-list">Loading training courses...</div>';
    }
    
    // API handlers
    public function get_leads($request) {
        global $wpdb;
        $leads = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}fflbro_leads ORDER BY created_at DESC LIMIT 50");
        return rest_ensure_response($leads);
    }
    
    public function create_lead($request) {
        global $wpdb;
        
        $name = sanitize_text_field($request->get_param('name'));
        $email = sanitize_email($request->get_param('email'));
        $phone = sanitize_text_field($request->get_param('phone'));
        $source = sanitize_text_field($request->get_param('source'));
        $interest = sanitize_text_field($request->get_param('interest'));
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'fflbro_leads',
            array(
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'source' => $source,
                'interest' => $interest,
                'status' => 'new',
                'score' => 50
            )
        );
        
        if ($result) {
            return rest_ensure_response(array('success' => true, 'id' => $wpdb->insert_id));
        } else {
            return new WP_Error('insert_failed', 'Failed to create lead', array('status' => 500));
        }
    }
    
    public function get_inventory($request) {
        global $wpdb;
        $inventory = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}fflbro_inventory ORDER BY created_at DESC LIMIT 100");
        return rest_ensure_response($inventory);
    }
    
    public function sync_gunbroker($request) {
        // Placeholder for GunBroker sync functionality
        return rest_ensure_response(array('success' => true, 'message' => 'GunBroker sync functionality to be implemented'));
    }
    
    public function sync_lipseys($request) {
        // Placeholder for Lipseys sync functionality
        return rest_ensure_response(array('success' => true, 'message' => 'Lipseys sync functionality to be implemented'));
    }
}

// Initialize the plugin
new FFLBROEnhancedPRO();

// Include assets inline to avoid file dependencies
add_action('admin_footer', function() {
    if (isset($_GET['page']) && strpos($_GET['page'], 'fflbro') !== false) {
        ?>
        <script>
        // Inline JavaScript for FFL-BRO Enhanced PRO
        console.log('FFL-BRO Enhanced PRO v4.1.0 loaded');
        
        // Global functions for UI interactions
        window.fflbroUtils = {
            showModal: function(title, content) {
                alert(title + '\n\n' + content); // Placeholder for modal
            },
            confirmAction: function(message, callback) {
                if (confirm(message)) {
                    callback();
                }
            },
            showNotification: function(message, type) {
                console.log(type + ': ' + message); // Placeholder for notifications
            }
        };
        </script>
        
        <style>
        /* Inline CSS for FFL-BRO Enhanced PRO */
        .fflbro-enhanced {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .fflbro-enhanced .button-primary {
            background: #007cba;
            border-color: #005a87;
        }
        
        .fflbro-enhanced .button-primary:hover {
            background: #005a87;
        }
        
        .fflbro-enhanced .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        
        .fflbro-enhanced .status-online { background: #46b450; }
        .fflbro-enhanced .status-offline { background: #dc3232; }
        .fflbro-enhanced .status-warning { background: #ffb900; }
        
        .fflbro-enhanced .feature-card {
            transition: all 0.3s ease;
        }
        
        .fflbro-enhanced .feature-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        </style>
        <?php
    }
});

// Add activation notice
add_action('admin_notices', function() {
    if (get_transient('fflbro_enhanced_activated')) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>FFL-BRO Enhanced PRO v4.1.0</strong> has been activated successfully! 
            <a href="<?php echo admin_url('admin.php?page=fflbro-enhanced'); ?>">View Dashboard</a></p>
        </div>
        <?php
        delete_transient('fflbro_enhanced_activated');
    }
});

// Set activation notice
register_activation_hook(__FILE__, function() {
    set_transient('fflbro_enhanced_activated', true, 30);
});

?>