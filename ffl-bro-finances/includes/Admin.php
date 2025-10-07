<?php
namespace FFLBRO\Fin;

if (!defined('ABSPATH')) exit;

class Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    public function register_menu() {
        add_menu_page(
            'FFL-BRO Finances',
            'Finances',
            'ffl_fin_read',
            'fflbro-finances',
            [$this, 'render_dashboard'],
            'dashicons-money-alt',
            58
        );
        
        add_submenu_page('fflbro-finances', 'Dashboard', 'Dashboard', 'ffl_fin_read', 'fflbro-finances');
        add_submenu_page('fflbro-finances', 'Vendors', 'Vendors', 'ffl_fin_manage', 'fflbro-fin-vendors', [$this, 'render_vendors']);
        add_submenu_page('fflbro-finances', 'Bills', 'Bills', 'ffl_fin_manage', 'fflbro-fin-bills', [$this, 'render_bills']);
        add_submenu_page('fflbro-finances', 'Payments', 'Payments', 'ffl_fin_manage', 'fflbro-fin-payments', [$this, 'render_payments']);
        add_submenu_page('fflbro-finances', 'Check Generator', 'Check Generator', 'ffl_fin_manage', 'fflbro-fin-checks', [$this, 'render_checks']);
        add_submenu_page('fflbro-finances', 'Exports', 'Exports', 'ffl_fin_admin', 'fflbro-fin-exports', [$this, 'render_exports']);
        add_submenu_page('fflbro-finances', 'Settings', 'Settings', 'ffl_fin_admin', 'fflbro-fin-settings', [$this, 'render_settings']);
    }
    
    public function enqueue_assets($hook) {
        if (strpos($hook, 'fflbro-fin') === false) return;
        wp_enqueue_style('fflbro-fin-admin', FFLBRO_FIN_URL . 'assets/admin.css', [], FFLBRO_FIN_VERSION);
    }
    
    public function render_dashboard() {
        echo '<div class="wrap"><h1>FFL-BRO Finances Dashboard</h1>';
        echo '<p>AP aging & balances (placeholder)</p></div>';
    }
    
    public function render_vendors() {
        echo '<div class="wrap"><h1>Vendors</h1>';
        echo '<p>CRUD grid (placeholder)</p></div>';
    }
    
    public function render_bills() {
        echo '<div class="wrap"><h1>Bills</h1>';
        echo '<p>Bill editor & approvals (placeholder)</p></div>';
    }
    
    public function render_payments() {
        echo '<div class="wrap"><h1>Payments</h1>';
        echo '<p>Scheduler & queue (placeholder)</p></div>';
    }
    
    public function render_checks() {
        echo '<div class="wrap"><h1>Check Generator</h1>';
        echo '<p>Prepare batch → PDF → mark printed (placeholder)</p></div>';
    }
    
    public function render_exports() {
        echo '<div class="wrap"><h1>Exports</h1>';
        echo '<p>Positive Pay / QB / Xero / ACH exports (placeholder)</p></div>';
    }
    
    public function render_settings() {
        echo '<div class="wrap"><h1>Settings</h1>';
        echo '<p>Banks, MICR, COA configuration (placeholder)</p></div>';
    }
}
