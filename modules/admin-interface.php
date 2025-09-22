<?php
class FFL_BRO_Admin_Interface {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menus'));
    }
    
    public function add_admin_menus() {
        add_menu_page('FFL-BRO', 'FFL-BRO', 'manage_options', 'ffl-bro', array($this, 'render_dashboard_page'));
        add_submenu_page('ffl-bro', 'Customers', 'Customers', 'manage_options', 'ffl-bro-customers', array($this, 'render_customers_page'));
        add_submenu_page('ffl-bro', 'Distributors', 'Distributors', 'manage_options', 'ffl-bro-distributors', array($this, 'render_distributors_page'));
    }
    
    public function render_dashboard_page() {
        echo '<div class="wrap"><h1>FFL-BRO Dashboard</h1></div>';
    }
    
    public function render_customers_page() {
        if (class_exists('FFL_BRO_Customer_Management')) {
            $customer_mgmt = new FFL_BRO_Customer_Management();
            $customer_mgmt->render_customers_page();
        }
    }
    
    public function render_distributors_page() {
        echo '<div class="wrap"><h1>Distributors</h1><p>RSR, Sports South, Orion, Davidsons</p></div>';
    }
}
new FFL_BRO_Admin_Interface();
