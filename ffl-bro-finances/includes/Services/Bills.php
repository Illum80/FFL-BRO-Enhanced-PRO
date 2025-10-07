<?php
namespace FFLBRO\Fin\Services;

if (!defined('ABSPATH')) exit;

class Bills {
    private $bills_table;
    private $items_table;
    private $vendors_table;
    
    public function __construct() {
        global $wpdb;
        $this->bills_table = $wpdb->prefix . 'fflbro_fin_bills';
        $this->items_table = $wpdb->prefix . 'fflbro_fin_bill_items';
        $this->vendors_table = $wpdb->prefix . 'fflbro_fin_vendors';
    }
    
    /**
     * Get all bills with vendor info
     */
    public function get_all($args = []) {
        global $wpdb;
        
        $defaults = [
            'status' => 'all',
            'vendor_id' => 0,
            'orderby' => 'bill_date',
            'order' => 'DESC',
            'limit' => 100,
            'offset' => 0,
            'search' => ''
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "WHERE 1=1";
        
        if ($args['status'] && $args['status'] !== 'all') {
            $where .= $wpdb->prepare(" AND b.status = %s", $args['status']);
        }
        
        if ($args['vendor_id']) {
            $where .= $wpdb->prepare(" AND b.vendor_id = %d", $args['vendor_id']);
        }
        
        if ($args['search']) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where .= $wpdb->prepare(" AND (b.bill_number LIKE %s OR b.invoice_number LIKE %s OR v.name LIKE %s)", 
                $search, $search, $search);
        }
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        $limit = absint($args['limit']);
        $offset = absint($args['offset']);
        
        $sql = "
            SELECT 
                b.*,
                v.name as vendor_name,
                v.vendor_code
            FROM {$this->bills_table} b
            LEFT JOIN {$this->vendors_table} v ON b.vendor_id = v.id
            {$where}
            ORDER BY {$orderby}
            LIMIT {$limit} OFFSET {$offset}
        ";
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Get single bill with all details
     */
    public function get($bill_id) {
        global $wpdb;
        
        $bill = $wpdb->get_row($wpdb->prepare("
            SELECT 
                b.*,
                v.name as vendor_name,
                v.vendor_code,
                v.payment_terms
            FROM {$this->bills_table} b
            LEFT JOIN {$this->vendors_table} v ON b.vendor_id = v.id
            WHERE b.id = %d
        ", $bill_id), ARRAY_A);
        
        if (!$bill) {
            return null;
        }
        
        // Get line items
        $bill['items'] = $this->get_items($bill_id);
        
        return $bill;
    }
    
    /**
     * Get bill line items
     */
    public function get_items($bill_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->items_table} WHERE bill_id = %d ORDER BY line_number",
            $bill_id
        ), ARRAY_A);
    }
    
    /**
     * Create new bill
     */
    public function create($data) {
        global $wpdb;
        
        // Validate
        if (empty($data['vendor_id'])) {
            return new \WP_Error('missing_vendor', 'Vendor is required');
        }
        
        // Verify vendor exists
        $vendor = $wpdb->get_row($wpdb->prepare(
            "SELECT id, payment_terms FROM {$this->vendors_table} WHERE id = %d",
            $data['vendor_id']
        ));
        
        if (!$vendor) {
            return new \WP_Error('invalid_vendor', 'Vendor not found');
        }
        
        // Generate bill number if not provided
        if (empty($data['bill_number'])) {
            $data['bill_number'] = $this->generate_bill_number();
        }
        
        // Calculate due date based on payment terms
        $bill_date = !empty($data['bill_date']) ? $data['bill_date'] : current_time('mysql');
        $due_date = $this->calculate_due_date($bill_date, $vendor->payment_terms);
        
        $insert_data = [
            'vendor_id' => absint($data['vendor_id']),
            'bill_number' => sanitize_text_field($data['bill_number']),
            'invoice_number' => !empty($data['invoice_number']) ? sanitize_text_field($data['invoice_number']) : null,
            'bill_date' => $bill_date,
            'due_date' => !empty($data['due_date']) ? $data['due_date'] : $due_date,
            'subtotal' => floatval($data['subtotal'] ?? 0),
            'tax_amount' => floatval($data['tax_amount'] ?? 0),
            'total_amount' => floatval($data['total_amount'] ?? 0),
            'status' => !empty($data['status']) ? sanitize_text_field($data['status']) : 'draft',
            'notes' => !empty($data['notes']) ? wp_kses_post($data['notes']) : null,
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id()
        ];
        
        $result = $wpdb->insert($this->bills_table, $insert_data);
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to create bill');
        }
        
        $bill_id = $wpdb->insert_id;
        
        // Add line items if provided
        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $index => $item) {
                $this->add_item($bill_id, $item, $index + 1);
            }
        }
        
        $this->log_audit($bill_id, 'created', 'Bill created');
        
        return $bill_id;
    }
    
    /**
     * Update bill
     */
    public function update($bill_id, $data) {
        global $wpdb;
        
        $bill = $this->get($bill_id);
        if (!$bill) {
            return new \WP_Error('not_found', 'Bill not found');
        }
        
        $update_data = [];
        $allowed_fields = ['vendor_id', 'bill_number', 'invoice_number', 'bill_date', 
                          'due_date', 'subtotal', 'tax_amount', 'total_amount', 'status', 'notes'];
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                if (in_array($field, ['subtotal', 'tax_amount', 'total_amount'])) {
                    $update_data[$field] = floatval($data[$field]);
                } else {
                    $update_data[$field] = sanitize_text_field($data[$field]);
                }
            }
        }
        
        if (empty($update_data)) {
            return new \WP_Error('no_data', 'No data to update');
        }
        
        $update_data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update($this->bills_table, $update_data, ['id' => $bill_id]);
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to update bill');
        }
        
        // Update items if provided
        if (isset($data['items']) && is_array($data['items'])) {
            // Delete existing items
            $wpdb->delete($this->items_table, ['bill_id' => $bill_id]);
            
            // Add new items
            foreach ($data['items'] as $index => $item) {
                $this->add_item($bill_id, $item, $index + 1);
            }
        }
        
        $this->log_audit($bill_id, 'updated', 'Bill updated');
        
        return true;
    }
    
    /**
     * Delete bill
     */
    public function delete($bill_id) {
        global $wpdb;
        
        // Check if bill has payments
        $payments_table = $wpdb->prefix . 'fflbro_fin_payments';
        $has_payments = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$payments_table} WHERE bill_id = %d",
            $bill_id
        ));
        
        if ($has_payments > 0) {
            return new \WP_Error('has_payments', 'Cannot delete bill with payments');
        }
        
        // Delete items first
        $wpdb->delete($this->items_table, ['bill_id' => $bill_id]);
        
        // Delete bill
        $result = $wpdb->delete($this->bills_table, ['id' => $bill_id]);
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to delete bill');
        }
        
        $this->log_audit($bill_id, 'deleted', 'Bill deleted');
        
        return true;
    }
    
    /**
     * Add line item to bill
     */
    public function add_item($bill_id, $data, $line_number = 1) {
        global $wpdb;
        
        $insert_data = [
            'bill_id' => $bill_id,
            'line_number' => $line_number,
            'description' => sanitize_text_field($data['description'] ?? ''),
            'quantity' => floatval($data['quantity'] ?? 1),
            'unit_price' => floatval($data['unit_price'] ?? 0),
            'amount' => floatval($data['amount'] ?? 0),
            'account_code' => !empty($data['account_code']) ? sanitize_text_field($data['account_code']) : null
        ];
        
        return $wpdb->insert($this->items_table, $insert_data);
    }
    
    /**
     * Approve bill for payment
     */
    public function approve($bill_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->bills_table,
            [
                'status' => 'approved',
                'approved_at' => current_time('mysql'),
                'approved_by' => get_current_user_id(),
                'updated_at' => current_time('mysql')
            ],
            ['id' => $bill_id]
        );
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to approve bill');
        }
        
        $this->log_audit($bill_id, 'approved', 'Bill approved for payment');
        
        return true;
    }
    
    /**
     * Mark bill as paid
     */
    public function mark_paid($bill_id, $payment_id = null) {
        global $wpdb;
        
        $update_data = [
            'status' => 'paid',
            'paid_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        if ($payment_id) {
            $update_data['payment_id'] = $payment_id;
        }
        
        $result = $wpdb->update($this->bills_table, $update_data, ['id' => $bill_id]);
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to mark bill as paid');
        }
        
        $this->log_audit($bill_id, 'paid', 'Bill marked as paid');
        
        return true;
    }
    
    /**
     * Generate unique bill number
     */
    private function generate_bill_number() {
        $prefix = 'BILL-' . date('Ym') . '-';
        global $wpdb;
        
        $last_number = $wpdb->get_var($wpdb->prepare(
            "SELECT bill_number FROM {$this->bills_table} 
             WHERE bill_number LIKE %s 
             ORDER BY id DESC LIMIT 1",
            $prefix . '%'
        ));
        
        if ($last_number) {
            $num = intval(substr($last_number, -4)) + 1;
        } else {
            $num = 1;
        }
        
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Calculate due date based on payment terms
     */
    private function calculate_due_date($bill_date, $payment_terms) {
        $date = new \DateTime($bill_date);
        
        switch ($payment_terms) {
            case 'net30':
                $date->modify('+30 days');
                break;
            case 'net60':
                $date->modify('+60 days');
                break;
            case 'cod':
            case 'due_on_receipt':
                // Due immediately
                break;
            default:
                $date->modify('+30 days');
        }
        
        return $date->format('Y-m-d H:i:s');
    }
    
    /**
     * Get aging report
     */
    public function get_aging_report() {
        global $wpdb;
        
        $today = current_time('mysql');
        
        return $wpdb->get_results("
            SELECT 
                v.name as vendor_name,
                COUNT(b.id) as bill_count,
                SUM(CASE WHEN b.due_date >= '{$today}' THEN b.total_amount ELSE 0 END) as current,
                SUM(CASE WHEN DATEDIFF('{$today}', b.due_date) BETWEEN 1 AND 30 THEN b.total_amount ELSE 0 END) as days_1_30,
                SUM(CASE WHEN DATEDIFF('{$today}', b.due_date) BETWEEN 31 AND 60 THEN b.total_amount ELSE 0 END) as days_31_60,
                SUM(CASE WHEN DATEDIFF('{$today}', b.due_date) > 60 THEN b.total_amount ELSE 0 END) as over_60,
                SUM(b.total_amount) as total
            FROM {$this->bills_table} b
            INNER JOIN {$this->vendors_table} v ON b.vendor_id = v.id
            WHERE b.status = 'unpaid'
            GROUP BY b.vendor_id, v.name
            ORDER BY total DESC
        ", ARRAY_A);
    }
    
    /**
     * Log audit trail
     */
    private function log_audit($bill_id, $action, $details) {
        global $wpdb;
        $audit_table = $wpdb->prefix . 'fflbro_fin_audit';
        
        $wpdb->insert($audit_table, [
            'entity_type' => 'bill',
            'entity_id' => $bill_id,
            'action' => $action,
            'details' => $details,
            'user_id' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ]);
    }
}
