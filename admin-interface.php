<?php
class FFL_BRO_Admin_Interface {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menus'));
    }
    
    public function add_admin_menus() {
        add_menu_page('FFL-BRO', 'FFL-BRO', 'manage_options', 'ffl-bro', array($this, 'render_dashboard_page'));
        add_submenu_page('ffl-bro', 'Customers', 'Customers', 'manage_options', 'ffl-bro-customers', array($this, 'render_customers_page'));
        add_submenu_page('ffl-bro', 'Distributors', 'Distributors', 'manage_options', 'ffl-bro-distributors', array($this, 'render_distributors_page'));
        add_submenu_page('ffl-bro', 'Quote Generator Pro', 'Quotes Pro', 'manage_options', 'ffl-bro-quotes-advanced', array($this, 'render_quotes_advanced_page'));
        add_submenu_page('ffl-bro', 'Form 4473', 'Form 4473', 'manage_options', 'fflbro-4473', array($this, 'render_form_4473_page'));
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
        echo '<div class="wrap"><h1>Distributors</h1>';
        echo '<p>Lipseys, RSR Group, Sports South, Orion Wholesale, Davidsons</p>';
        echo '</div>';
    }
    
    public function render_quotes_advanced_page() {
        if (class_exists('FFLBROQuoteGeneratorAdvanced')) {
            $quote_gen = new FFLBROQuoteGeneratorAdvanced();
            $quote_gen->admin_page();
        } else {
            echo '<div class="wrap"><h1>Quote Generator Pro</h1><p>Quote generator class not found.</p></div>';
        }
    }
    
    public function render_form_4473_page() {
        if (class_exists('FFL_BRO_Form_4473_Processing')) {
            $form_processor = new FFL_BRO_Form_4473_Processing();
            $form_processor->render_4473_page();
        } else {
            echo '<div class="wrap"><h1>Form 4473 Processing</h1><p>Form 4473 class not found.</p></div>';
        }
    }
}
new FFL_BRO_Admin_Interface();
