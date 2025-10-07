<?php
namespace FFLBRO\Fin\Services;

if (!defined('ABSPATH')) exit;

class Payments {
    private $payments_table;
    private $bills_table;
    
    public function __construct() {
        global $wpdb;
        $this->payments_table = $wpdb->prefix . 'fflbro_fin_payments';
        $this->bills_table = $wpdb->prefix . 'fflbro_fin_bills';
    }
    
    /**
     * Get all payments
     */
    public function get_all($args = []) {
        global $wpdb;
        
        $defaults = [
            'status' => 'all',
            'method' => 'all',
            'orderby' => 'payment_date',
            'order' => 'DESC',
            'limit' => 100,
            'offset' => 0
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "WHERE 1=1";
        
        if ($args['status'] && $args['status'] !== 'all') {
            $where .= $wpdb->prepare(" AND p.status = %s", $args['status']);
        }
        
        if ($args['method'] && $args['method'] !== 'all') {
            $where .= $wpdb->prepare(" AND p.payment_method = %s", $args['method']);
        }
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        $limit = absint($args['limit']);
        $offset = absint($args['offset']);
        
        $sql = "
            SELECT 
                p.*,
                b.bill_number,
                v.name as vendor_name
            FROM {$this->payments_table} p
            LEFT JOIN {$this->bills_table} b ON p.bill_id = b.id
            LEFT JOIN {$wpdb->prefix}fflbro_fin_vendors v ON b.vendor_id = v.id
            {$where}
            ORDER BY {$orderby}
            LIMIT {$limit} OFFSET {$offset}
        ";
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Get single payment
     */
    public function get($payment_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT 
                p.*,
                b.bill_number,
                b.total_amount as bill_total,
                v.name as vendor_name,
                v.vendor_code
            FROM {$this->payments_table} p
            LEFT JOIN {$this->bills_table} b ON p.bill_id = b.id
            LEFT JOIN {$wpdb->prefix}fflbro_fin_vendors v ON b.vendor_id = v.id
            WHERE p.id = %d
        ", $payment_id), ARRAY_A);
    }
    
    /**
     * Create payment
     */
    public function create($data) {
        global $wpdb;
        
        if (empty($data['bill_id'])) {
            return new \WP_Error('missing_bill', 'Bill is required');
        }
        
        // Verify bill exists and is approved
        $bill = $wpdb->get_row($wpdb->prepare(
            "SELECT id, status, total_amount FROM {$this->bills_table} WHERE id = %d",
            $data['bill_id']
        ));
        
        if (!$bill) {
            return new \WP_Error('invalid_bill', 'Bill not found');
        }
        
        if ($bill->status !== 'approved' && $bill->status !== 'unpaid') {
            return new \WP_Error('invalid_status', 'Only approved or unpaid bills can be paid');
        }
        
        $insert_data = [
            'bill_id' => absint($data['bill_id']),
            'payment_date' => !empty($data['payment_date']) ? $data['payment_date'] : current_time('mysql'),
            'amount' => floatval($data['amount'] ?? $bill->total_amount),
            'payment_method' => sanitize_text_field($data['payment_method'] ?? 'check'),
            'reference_number' => !empty($data['reference_number']) ? sanitize_text_field($data['reference_number']) : null,
            'check_number' => !empty($data['check_number']) ? absint($data['check_number']) : null,
            'status' => !empty($data['status']) ? sanitize_text_field($data['status']) : 'pending',
            'notes' => !empty($data['notes']) ? wp_kses_post($data['notes']) : null,
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id()
        ];
        
        $result = $wpdb->insert($this->payments_table, $insert_data);
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to create payment');
        }
        
        $payment_id = $wpdb->insert_id;
        
        // If payment is completed, mark bill as paid
        if ($insert_data['status'] === 'completed') {
            require_once FFLBRO_FIN_PATH . 'includes/Services/Bills.php';
            $bills_service = new Bills();
            $bills_service->mark_paid($data['bill_id'], $payment_id);
        }
        
        $this->log_audit($payment_id, 'created', 'Payment created');
        
        return $payment_id;
    }
    
    /**
     * Update payment
     */
    public function update($payment_id, $data) {
        global $wpdb;
        
        $payment = $this->get($payment_id);
        if (!$payment) {
            return new \WP_Error('not_found', 'Payment not found');
        }
        
        $update_data = [];
        $allowed_fields = ['payment_date', 'amount', 'payment_method', 'reference_number', 
                          'check_number', 'status', 'notes'];
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                if ($field === 'amount') {
                    $update_data[$field] = floatval($data[$field]);
                } else {
                    $update_data[$field] = sanitize_text_field($data[$field]);
                }
            }
        }
        
        if (empty($update_data)) {
            return new \WP_Error('no_data', 'No data to update');
        }
        
        $result = $wpdb->update($this->payments_table, $update_data, ['id' => $payment_id]);
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to update payment');
        }
        
        // If status changed to completed, mark bill as paid
        if (isset($update_data['status']) && $update_data['status'] === 'completed') {
            require_once FFLBRO_FIN_PATH . 'includes/Services/Bills.php';
            $bills_service = new Bills();
            $bills_service->mark_paid($payment['bill_id'], $payment_id);
        }
        
        $this->log_audit($payment_id, 'updated', 'Payment updated');
        
        return true;
    }
    
    /**
     * Delete payment
     */
    public function delete($payment_id) {
        global $wpdb;
        
        $payment = $this->get($payment_id);
        if (!$payment) {
            return new \WP_Error('not_found', 'Payment not found');
        }
        
        // Don't allow deleting completed payments
        if ($payment['status'] === 'completed') {
            return new \WP_Error('completed', 'Cannot delete completed payments');
        }
        
        $result = $wpdb->delete($this->payments_table, ['id' => $payment_id]);
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to delete payment');
        }
        
        $this->log_audit($payment_id, 'deleted', 'Payment deleted');
        
        return true;
    }
    
    /**
     * Get pending payments ready for processing
     */
    public function get_pending_batch() {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT 
                p.*,
                b.bill_number,
                v.name as vendor_name,
                v.vendor_code
            FROM {$this->payments_table} p
            INNER JOIN {$this->bills_table} b ON p.bill_id = b.id
            INNER JOIN {$wpdb->prefix}fflbro_fin_vendors v ON b.vendor_id = v.id
            WHERE p.status = 'pending'
            AND p.payment_method = 'check'
            AND p.check_number IS NULL
            ORDER BY p.payment_date ASC
        ", ARRAY_A);
    }
    
    /**
     * Assign check numbers to pending payments
     */
    public function assign_check_numbers($payment_ids, $starting_check_number) {
        global $wpdb;
        
        $check_num = absint($starting_check_number);
        
        foreach ($payment_ids as $payment_id) {
            $wpdb->update(
                $this->payments_table,
                ['check_number' => $check_num],
                ['id' => absint($payment_id)]
            );
            $check_num++;
        }
        
        return true;
    }
    
    /**
     * Mark payment as completed
     */
    public function mark_completed($payment_id) {
        global $wpdb;
        
        $payment = $this->get($payment_id);
        if (!$payment) {
            return new \WP_Error('not_found', 'Payment not found');
        }
        
        $result = $wpdb->update(
            $this->payments_table,
            ['status' => 'completed', 'completed_at' => current_time('mysql')],
            ['id' => $payment_id]
        );
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to complete payment');
        }
        
        // Mark bill as paid
        require_once FFLBRO_FIN_PATH . 'includes/Services/Bills.php';
        $bills_service = new Bills();
        $bills_service->mark_paid($payment['bill_id'], $payment_id);
        
        $this->log_audit($payment_id, 'completed', 'Payment marked as completed');
        
        return true;
    }
    
    /**
     * Log audit trail
     */
    private function log_audit($payment_id, $action, $details) {
        global $wpdb;
        $audit_table = $wpdb->prefix . 'fflbro_fin_audit';
        
        $wpdb->insert($audit_table, [
            'entity_type' => 'payment',
            'entity_id' => $payment_id,
            'action' => $action,
            'details' => $details,
            'user_id' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ]);
    }
}
