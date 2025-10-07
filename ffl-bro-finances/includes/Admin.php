<?php
namespace FFLBRO\Fin;

if (!defined('ABSPATH')) exit;

class Admin {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Vendor actions
        add_action('admin_post_fflbro_fin_save_vendor', [$this, 'handle_save_vendor']);
        add_action('admin_post_fflbro_fin_delete_vendor', [$this, 'handle_delete_vendor']);
        
        // Bill actions
        add_action('admin_post_fflbro_fin_save_bill', [$this, 'handle_save_bill']);
        add_action('admin_post_fflbro_fin_delete_bill', [$this, 'handle_delete_bill']);
        add_action('admin_post_fflbro_fin_approve_bill', [$this, 'handle_approve_bill']);
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
        
        // Hidden pages
        add_submenu_page(null, 'Edit Vendor', 'Edit Vendor', 'ffl_fin_manage', 'fflbro-fin-vendor-edit', [$this, 'render_vendor_edit']);
        add_submenu_page(null, 'Edit Bill', 'Edit Bill', 'ffl_fin_manage', 'fflbro-fin-bill-edit', [$this, 'render_bill_edit']);
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'fflbro-fin') === false && strpos($hook, 'fflbro-finances') === false) return;
        
        wp_enqueue_style('fflbro-fin-admin', FFLBRO_FIN_URL . 'assets/admin.css', [], FFLBRO_FIN_VERSION);
        wp_enqueue_script('fflbro-fin-admin', FFLBRO_FIN_URL . 'assets/admin.js', ['jquery'], FFLBRO_FIN_VERSION, true);
        
        wp_localize_script('fflbro-fin-admin', 'fflbroFin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fflbro_fin_nonce')
        ]);
    }

    public function render_dashboard() {
        global $wpdb;
        
        $vendors_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_fin_vendors WHERE status = 'active'");
        $unpaid_bills = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_fin_bills WHERE status IN ('unpaid', 'approved')");
        $unpaid_amount = $wpdb->get_var("SELECT SUM(total_amount) FROM {$wpdb->prefix}fflbro_fin_bills WHERE status IN ('unpaid', 'approved')") ?: 0;
        $overdue_bills = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fflbro_fin_bills WHERE status = 'unpaid' AND due_date < NOW()");
        
        ?>
        <div class="wrap fflbro-fin-wrap">
            <h1>FFL-BRO Finances Dashboard</h1>
            
            <div class="fflbro-fin-dashboard">
                <div class="fflbro-fin-stat-cards">
                    <div class="stat-card">
                        <h3>Active Vendors</h3>
                        <div class="stat-value"><?php echo number_format($vendors_count); ?></div>
                    </div>
                    
                    <div class="stat-card warning">
                        <h3>Unpaid Bills</h3>
                        <div class="stat-value"><?php echo number_format($unpaid_bills); ?></div>
                    </div>
                    
                    <div class="stat-card alert">
                        <h3>Amount Due</h3>
                        <div class="stat-value">$<?php echo number_format($unpaid_amount, 2); ?></div>
                    </div>
                    
                    <div class="stat-card alert">
                        <h3>Overdue Bills</h3>
                        <div class="stat-value"><?php echo number_format($overdue_bills); ?></div>
                    </div>
                </div>
                
                <div class="fflbro-fin-quick-actions">
                    <h2>Quick Actions</h2>
                    <a href="<?php echo admin_url('admin.php?page=fflbro-fin-vendor-edit'); ?>" class="button button-primary">+ Add Vendor</a>
                    <a href="<?php echo admin_url('admin.php?page=fflbro-fin-bill-edit'); ?>" class="button button-primary">+ Add Bill</a>
                    <a href="<?php echo admin_url('admin.php?page=fflbro-fin-bills&status=unpaid'); ?>" class="button">View Unpaid Bills</a>
                    <a href="<?php echo admin_url('admin.php?page=fflbro-fin-payments'); ?>" class="button">Process Payments</a>
                </div>
            </div>
        </div>
        <?php
    }

    // ========================================================================
    // VENDORS
    // ========================================================================
    
    public function render_vendors() {
        require_once FFLBRO_FIN_PATH . 'includes/Services/Vendors.php';
        $vendors_service = new Services\Vendors();
        
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'active';
        
        $vendors = $vendors_service->get_all(['search' => $search, 'status' => $status]);
        
        ?>
        <div class="wrap fflbro-fin-wrap">
            <h1 class="wp-heading-inline">Vendors</h1>
            <a href="<?php echo admin_url('admin.php?page=fflbro-fin-vendor-edit'); ?>" class="page-title-action">Add New</a>
            
            <?php if (isset($_GET['message'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html($this->get_message($_GET['message'])); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="get" class="fflbro-fin-search-form">
                <input type="hidden" name="page" value="fflbro-fin-vendors">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search vendors...">
                <select name="status">
                    <option value="active" <?php selected($status, 'active'); ?>>Active</option>
                    <option value="inactive" <?php selected($status, 'inactive'); ?>>Inactive</option>
                    <option value="all" <?php selected($status, 'all'); ?>>All</option>
                </select>
                <button type="submit" class="button">Search</button>
            </form>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Vendor Code</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Payment Terms</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vendors)): ?>
                        <tr>
                            <td colspan="7">No vendors found. <a href="<?php echo admin_url('admin.php?page=fflbro-fin-vendor-edit'); ?>">Add your first vendor</a></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vendors as $vendor): ?>
                            <tr>
                                <td><strong><?php echo esc_html($vendor['vendor_code']); ?></strong></td>
                                <td><?php echo esc_html($vendor['name']); ?></td>
                                <td><?php echo esc_html($vendor['email'] ?: '-'); ?></td>
                                <td><?php echo esc_html($vendor['phone'] ?: '-'); ?></td>
                                <td><?php echo esc_html(strtoupper($vendor['payment_terms'])); ?></td>
                                <td><span class="status-badge status-<?php echo esc_attr($vendor['status']); ?>"><?php echo esc_html($vendor['status']); ?></span></td>
                                <td class="fflbro-fin-actions">
                                    <a href="<?php echo admin_url('admin.php?page=fflbro-fin-vendor-edit&id=' . $vendor['id']); ?>" class="button button-small">Edit</a>
                                    <a href="<?php echo admin_url('admin.php?page=fflbro-fin-bills&vendor=' . $vendor['id']); ?>" class="button button-small">Bills</a>
                                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;">
                                        <input type="hidden" name="action" value="fflbro_fin_delete_vendor">
                                        <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                        <?php wp_nonce_field('fflbro_fin_delete_vendor'); ?>
                                        <button type="submit" class="button button-small button-link-delete" onclick="return confirm('Are you sure?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_vendor_edit() {
        require_once FFLBRO_FIN_PATH . 'includes/Services/Vendors.php';
        $vendors_service = new Services\Vendors();
        
        $vendor_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        $vendor = $vendor_id ? $vendors_service->get($vendor_id) : null;
        $is_new = !$vendor;
        
        ?>
        <div class="wrap fflbro-fin-wrap">
            <h1><?php echo $is_new ? 'Add New Vendor' : 'Edit Vendor'; ?></h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="fflbro-fin-form">
                <input type="hidden" name="action" value="fflbro_fin_save_vendor">
                <?php if (!$is_new): ?>
                    <input type="hidden" name="vendor_id" value="<?php echo $vendor_id; ?>">
                <?php endif; ?>
                <?php wp_nonce_field('fflbro_fin_save_vendor'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="vendor_code">Vendor Code *</label></th>
                        <td><input type="text" name="vendor_code" id="vendor_code" value="<?php echo esc_attr($vendor['vendor_code'] ?? ''); ?>" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="name">Vendor Name *</label></th>
                        <td><input type="text" name="name" id="name" value="<?php echo esc_attr($vendor['name'] ?? ''); ?>" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="tax_id">Tax ID / EIN</label></th>
                        <td><input type="text" name="tax_id" id="tax_id" value="<?php echo esc_attr($vendor['tax_id'] ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="payment_terms">Payment Terms</label></th>
                        <td>
                            <select name="payment_terms" id="payment_terms">
                                <option value="net30" <?php selected($vendor['payment_terms'] ?? 'net30', 'net30'); ?>>Net 30</option>
                                <option value="net60" <?php selected($vendor['payment_terms'] ?? '', 'net60'); ?>>Net 60</option>
                                <option value="cod" <?php selected($vendor['payment_terms'] ?? '', 'cod'); ?>>COD</option>
                                <option value="due_on_receipt" <?php selected($vendor['payment_terms'] ?? '', 'due_on_receipt'); ?>>Due on Receipt</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="account_number">Account Number</label></th>
                        <td><input type="text" name="account_number" id="account_number" value="<?php echo esc_attr($vendor['account_number'] ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="email">Email</label></th>
                        <td><input type="email" name="email" id="email" value="<?php echo esc_attr($vendor['email'] ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="phone">Phone</label></th>
                        <td><input type="tel" name="phone" id="phone" value="<?php echo esc_attr($vendor['phone'] ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="website">Website</label></th>
                        <td><input type="url" name="website" id="website" value="<?php echo esc_attr($vendor['website'] ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="status">Status</label></th>
                        <td>
                            <select name="status" id="status">
                                <option value="active" <?php selected($vendor['status'] ?? 'active', 'active'); ?>>Active</option>
                                <option value="inactive" <?php selected($vendor['status'] ?? '', 'inactive'); ?>>Inactive</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="notes">Notes</label></th>
                        <td><textarea name="notes" id="notes" rows="5" class="large-text"><?php echo esc_textarea($vendor['notes'] ?? ''); ?></textarea></td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large"><?php echo $is_new ? 'Create Vendor' : 'Update Vendor'; ?></button>
                    <a href="<?php echo admin_url('admin.php?page=fflbro-fin-vendors'); ?>" class="button button-large">Cancel</a>
                </p>
            </form>
        </div>
        <?php
    }

    public function handle_save_vendor() {
        check_admin_referer('fflbro_fin_save_vendor');
        if (!current_user_can('ffl_fin_manage')) wp_die('Insufficient permissions');
        
        require_once FFLBRO_FIN_PATH . 'includes/Services/Vendors.php';
        $vendors_service = new Services\Vendors();
        
        $vendor_id = isset($_POST['vendor_id']) ? absint($_POST['vendor_id']) : 0;
        $result = $vendor_id ? $vendors_service->update($vendor_id, $_POST) : $vendors_service->create($_POST);
        
        if (is_wp_error($result)) wp_die($result->get_error_message());
        
        wp_redirect(admin_url('admin.php?page=fflbro-fin-vendors&message=' . ($vendor_id ? 'updated' : 'created')));
        exit;
    }

    public function handle_delete_vendor() {
        check_admin_referer('fflbro_fin_delete_vendor');
        if (!current_user_can('ffl_fin_manage')) wp_die('Insufficient permissions');
        
        require_once FFLBRO_FIN_PATH . 'includes/Services/Vendors.php';
        $vendors_service = new Services\Vendors();
        
        $result = $vendors_service->delete($_POST['vendor_id']);
        if (is_wp_error($result)) wp_die($result->get_error_message());
        
        wp_redirect(admin_url('admin.php?page=fflbro-fin-vendors&message=deleted'));
        exit;
    }

    // ========================================================================
    // BILLS
    // ========================================================================
    
    public function render_bills() {
        require_once FFLBRO_FIN_PATH . 'includes/Services/Bills.php';
        require_once FFLBRO_FIN_PATH . 'includes/Services/Vendors.php';
        
        $bills_service = new Services\Bills();
        $vendors_service = new Services\Vendors();
        
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'unpaid';
        $vendor_id = isset($_GET['vendor']) ? absint($_GET['vendor']) : 0;
        
        $bills = $bills_service->get_all(['search' => $search, 'status' => $status, 'vendor_id' => $vendor_id]);
        $vendors = $vendors_service->get_all(['status' => 'active']);
        
        ?>
        <div class="wrap fflbro-fin-wrap">
            <h1 class="wp-heading-inline">Bills</h1>
            <a href="<?php echo admin_url('admin.php?page=fflbro-fin-bill-edit'); ?>" class="page-title-action">Add New</a>
            
            <?php if (isset($_GET['message'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html($this->get_message($_GET['message'])); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="get" class="fflbro-fin-search-form">
                <input type="hidden" name="page" value="fflbro-fin-bills">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search bills...">
                <select name="vendor">
                    <option value="">All Vendors</option>
                    <?php foreach ($vendors as $vendor): ?>
                        <option value="<?php echo $vendor['id']; ?>" <?php selected($vendor_id, $vendor['id']); ?>>
                            <?php echo esc_html($vendor['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status">
                    <option value="unpaid" <?php selected($status, 'unpaid'); ?>>Unpaid</option>
                    <option value="paid" <?php selected($status, 'paid'); ?>>Paid</option>
                    <option value="draft" <?php selected($status, 'draft'); ?>>Draft</option>
                    <option value="approved" <?php selected($status, 'approved'); ?>>Approved</option>
                    <option value="all" <?php selected($status, 'all'); ?>>All</option>
                </select>
                <button type="submit" class="button">Search</button>
            </form>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Bill #</th>
                        <th>Vendor</th>
                        <th>Invoice #</th>
                        <th>Bill Date</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bills)): ?>
                        <tr>
                            <td colspan="8">No bills found. <a href="<?php echo admin_url('admin.php?page=fflbro-fin-bill-edit'); ?>">Create your first bill</a></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bills as $bill): 
                            $is_overdue = ($bill['status'] === 'unpaid' && strtotime($bill['due_date']) < time());
                        ?>
                            <tr <?php if ($is_overdue) echo 'style="background:#ffe0e0;"'; ?>>
                                <td><strong><?php echo esc_html($bill['bill_number']); ?></strong></td>
                                <td><?php echo esc_html($bill['vendor_name']); ?></td>
                                <td><?php echo esc_html($bill['invoice_number'] ?: '-'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($bill['bill_date'])); ?></td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($bill['due_date'])); ?>
                                    <?php if ($is_overdue): ?>
                                        <span class="overdue-badge">OVERDUE</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong>$<?php echo number_format($bill['total_amount'], 2); ?></strong></td>
                                <td><span class="status-badge status-<?php echo esc_attr($bill['status']); ?>"><?php echo esc_html($bill['status']); ?></span></td>
                                <td class="fflbro-fin-actions">
                                    <a href="<?php echo admin_url('admin.php?page=fflbro-fin-bill-edit&id=' . $bill['id']); ?>" class="button button-small">Edit</a>
                                    <?php if ($bill['status'] === 'draft'): ?>
                                        <a href="<?php echo admin_url('admin-post.php?action=fflbro_fin_approve_bill&bill_id=' . $bill['id'] . '&_wpnonce=' . wp_create_nonce('approve_bill_' . $bill['id'])); ?>" class="button button-small button-primary">Approve</a>
                                    <?php endif; ?>
                                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;">
                                        <input type="hidden" name="action" value="fflbro_fin_delete_bill">
                                        <input type="hidden" name="bill_id" value="<?php echo $bill['id']; ?>">
                                        <?php wp_nonce_field('fflbro_fin_delete_bill'); ?>
                                        <button type="submit" class="button button-small button-link-delete" onclick="return confirm('Are you sure?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_bill_edit() {
        require_once FFLBRO_FIN_PATH . 'includes/Services/Bills.php';
        require_once FFLBRO_FIN_PATH . 'includes/Services/Vendors.php';
        
        $bills_service = new Services\Bills();
        $vendors_service = new Services\Vendors();
        
        $bill_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        $bill = $bill_id ? $bills_service->get($bill_id) : null;
        $is_new = !$bill;
        
        $vendors = $vendors_service->get_all(['status' => 'active']);
        
        ?>
        <div class="wrap fflbro-fin-wrap">
            <h1><?php echo $is_new ? 'Add New Bill' : 'Edit Bill'; ?></h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="fflbro-fin-form">
                <input type="hidden" name="action" value="fflbro_fin_save_bill">
                <?php if (!$is_new): ?>
                    <input type="hidden" name="bill_id" value="<?php echo $bill_id; ?>">
                <?php endif; ?>
                <?php wp_nonce_field('fflbro_fin_save_bill'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="vendor_id">Vendor *</label></th>
                        <td>
                            <select name="vendor_id" id="vendor_id" required class="regular-text">
                                <option value="">Select Vendor</option>
                                <?php foreach ($vendors as $vendor): ?>
                                    <option value="<?php echo $vendor['id']; ?>" <?php selected($bill['vendor_id'] ?? '', $vendor['id']); ?>>
                                        <?php echo esc_html($vendor['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="bill_number">Bill Number</label></th>
                        <td>
                            <input type="text" name="bill_number" id="bill_number" value="<?php echo esc_attr($bill['bill_number'] ?? ''); ?>" class="regular-text">
                            <p class="description">Leave blank to auto-generate</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="invoice_number">Vendor Invoice #</label></th>
                        <td><input type="text" name="invoice_number" id="invoice_number" value="<?php echo esc_attr($bill['invoice_number'] ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="bill_date">Bill Date *</label></th>
                        <td><input type="date" name="bill_date" id="bill_date" value="<?php echo esc_attr($bill['bill_date'] ?? date('Y-m-d')); ?>" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="due_date">Due Date</label></th>
                        <td>
                            <input type="date" name="due_date" id="due_date" value="<?php echo esc_attr($bill['due_date'] ?? ''); ?>" class="regular-text">
                            <p class="description">Leave blank to calculate from payment terms</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="subtotal">Subtotal *</label></th>
                        <td><input type="number" name="subtotal" id="subtotal" value="<?php echo esc_attr($bill['subtotal'] ?? '0.00'); ?>" step="0.01" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="tax_amount">Tax Amount</label></th>
                        <td><input type="number" name="tax_amount" id="tax_amount" value="<?php echo esc_attr($bill['tax_amount'] ?? '0.00'); ?>" step="0.01" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="total_amount">Total Amount *</label></th>
                        <td><input type="number" name="total_amount" id="total_amount" value="<?php echo esc_attr($bill['total_amount'] ?? '0.00'); ?>" step="0.01" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="status">Status</label></th>
                        <td>
                            <select name="status" id="status">
                                <option value="draft" <?php selected($bill['status'] ?? 'draft', 'draft'); ?>>Draft</option>
                                <option value="unpaid" <?php selected($bill['status'] ?? '', 'unpaid'); ?>>Unpaid</option>
                                <option value="approved" <?php selected($bill['status'] ?? '', 'approved'); ?>>Approved</option>
                                <option value="paid" <?php selected($bill['status'] ?? '', 'paid'); ?>>Paid</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="notes">Notes</label></th>
                        <td><textarea name="notes" id="notes" rows="5" class="large-text"><?php echo esc_textarea($bill['notes'] ?? ''); ?></textarea></td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large"><?php echo $is_new ? 'Create Bill' : 'Update Bill'; ?></button>
                    <a href="<?php echo admin_url('admin.php?page=fflbro-fin-bills'); ?>" class="button button-large">Cancel</a>
                </p>
            </form>
        </div>
        <?php
    }

    public function handle_save_bill() {
        check_admin_referer('fflbro_fin_save_bill');
        if (!current_user_can('ffl_fin_manage')) wp_die('Insufficient permissions');
        
        require_once FFLBRO_FIN_PATH . 'includes/Services/Bills.php';
        $bills_service = new Services\Bills();
        
        $bill_id = isset($_POST['bill_id']) ? absint($_POST['bill_id']) : 0;
        $result = $bill_id ? $bills_service->update($bill_id, $_POST) : $bills_service->create($_POST);
        
        if (is_wp_error($result)) wp_die($result->get_error_message());
        
        wp_redirect(admin_url('admin.php?page=fflbro-fin-bills&message=' . ($bill_id ? 'updated' : 'created')));
        exit;
    }

    public function handle_delete_bill() {
        check_admin_referer('fflbro_fin_delete_bill');
        if (!current_user_can('ffl_fin_manage')) wp_die('Insufficient permissions');
        
        require_once FFLBRO_FIN_PATH . 'includes/Services/Bills.php';
        $bills_service = new Services\Bills();
        
        $result = $bills_service->delete($_POST['bill_id']);
        if (is_wp_error($result)) wp_die($result->get_error_message());
        
        wp_redirect(admin_url('admin.php?page=fflbro-fin-bills&message=deleted'));
        exit;
    }

    public function handle_approve_bill() {
        $bill_id = isset($_GET['bill_id']) ? absint($_GET['bill_id']) : 0;
        check_admin_referer('approve_bill_' . $bill_id);
        if (!current_user_can('ffl_fin_manage')) wp_die('Insufficient permissions');
        
        require_once FFLBRO_FIN_PATH . 'includes/Services/Bills.php';
        $bills_service = new Services\Bills();
        
        $result = $bills_service->approve($bill_id);
        if (is_wp_error($result)) wp_die($result->get_error_message());
        
        wp_redirect(admin_url('admin.php?page=fflbro-fin-bills&message=approved'));
        exit;
    }

    // ========================================================================
    // PLACEHOLDERS (TODO)
    // ========================================================================

    public function render_payments() {
        echo '<div class="wrap"><h1>Payments</h1><p>Coming next!</p></div>';
    }

    public function render_checks() {
        echo '<div class="wrap"><h1>Check Generator</h1><p>Coming next!</p></div>';
    }

    public function render_exports() {
        echo '<div class="wrap"><h1>Exports</h1><p>Coming next!</p></div>';
    }

    public function render_settings() {
        echo '<div class="wrap"><h1>Settings</h1><p>Coming next!</p></div>';
    }

    private function get_message($key) {
        $messages = [
            'created' => 'Item created successfully.',
            'updated' => 'Item updated successfully.',
            'deleted' => 'Item deleted successfully.',
            'approved' => 'Bill approved successfully.'
        ];
        return $messages[$key] ?? '';
    }
}
