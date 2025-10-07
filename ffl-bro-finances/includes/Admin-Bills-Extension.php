<?php
// Bills Management Methods for Admin.php
// These should be integrated into the main Admin class

public function render_bills() {
    require_once FFLBRO_FIN_PATH . 'includes/Services/Bills.php';
    require_once FFLBRO_FIN_PATH . 'includes/Services/Vendors.php';
    
    $bills_service = new Services\Bills();
    $vendors_service = new Services\Vendors();
    
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'unpaid';
    $vendor_id = isset($_GET['vendor']) ? absint($_GET['vendor']) : 0;
    
    $bills = $bills_service->get_all([
        'search' => $search,
        'status' => $status,
        'vendor_id' => $vendor_id
    ]);
    
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
                        <tr <?php if ($is_overdue) echo 'class="overdue"'; ?>>
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

