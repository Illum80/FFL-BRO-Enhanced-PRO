<?php
namespace FFLBRO\Fin\Services;

if (!defined('ABSPATH')) exit;

class Vendors {
    private $table_name;
    private $address_table;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'fflbro_fin_vendors';
        $this->address_table = $wpdb->prefix . 'fflbro_fin_addresses';
    }
    
    /**
     * Get all vendors
     */
    public function get_all($args = []) {
        global $wpdb;
        
        $defaults = [
            'status' => 'active',
            'orderby' => 'name',
            'order' => 'ASC',
            'limit' => 100,
            'offset' => 0,
            'search' => ''
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "WHERE 1=1";
        
        if ($args['status'] && $args['status'] !== 'all') {
            $where .= $wpdb->prepare(" AND status = %s", $args['status']);
        }
        
        if ($args['search']) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where .= $wpdb->prepare(" AND (name LIKE %s OR vendor_code LIKE %s OR tax_id LIKE %s)", $search, $search, $search);
        }
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        $limit = absint($args['limit']);
        $offset = absint($args['offset']);
        
        $sql = "SELECT * FROM {$this->table_name} {$where} ORDER BY {$orderby} LIMIT {$limit} OFFSET {$offset}";
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Get single vendor by ID
     */
    public function get($vendor_id) {
        global $wpdb;
        
        $vendor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $vendor_id
        ), ARRAY_A);
        
        if (!$vendor) {
            return null;
        }
        
        // Get addresses
        $vendor['addresses'] = $this->get_addresses($vendor_id);
        
        return $vendor;
    }
    
    /**
     * Create new vendor
     */
    public function create($data) {
        global $wpdb;
        
        // Validate required fields
        $required = ['name', 'vendor_code'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new \WP_Error('missing_field', "Required field missing: {$field}");
            }
        }
        
        // Auto-generate vendor code if not provided
        if (empty($data["vendor_code"])) {
            $data["vendor_code"] = $this->generate_vendor_code($data["name"]);
        }

        // Check for duplicate vendor code
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE vendor_code = %s",
            $data['vendor_code']
        ));
        
        if ($exists) {
            return new \WP_Error('duplicate', 'Vendor code already exists');
        }
        
        $insert_data = [
            'name' => sanitize_text_field($data['name']),
            'vendor_code' => sanitize_text_field($data['vendor_code']),
            'tax_id' => !empty($data['tax_id']) ? sanitize_text_field($data['tax_id']) : null,
            'payment_terms' => !empty($data['payment_terms']) ? sanitize_text_field($data['payment_terms']) : 'net30',
            'account_number' => !empty($data['account_number']) ? sanitize_text_field($data['account_number']) : null,
            'phone' => !empty($data['phone']) ? sanitize_text_field($data['phone']) : null,
            'email' => !empty($data['email']) ? sanitize_email($data['email']) : null,
            'website' => !empty($data['website']) ? esc_url_raw($data['website']) : null,
            'notes' => !empty($data['notes']) ? wp_kses_post($data['notes']) : null,
            'status' => !empty($data['status']) ? sanitize_text_field($data['status']) : 'active',
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id()
        ];
        
        $result = $wpdb->insert($this->table_name, $insert_data);
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to create vendor');
        }
        
        $vendor_id = $wpdb->insert_id;
        
        // Add addresses if provided
        if (!empty($data['addresses'])) {
            foreach ($data['addresses'] as $address) {
                $this->add_address($vendor_id, $address);
            }
        }
        
        // Log audit trail
        $this->log_audit($vendor_id, 'created', 'Vendor created');
        
        return $vendor_id;
    }
    
    /**
     * Update vendor
     */
    public function update($vendor_id, $data) {
        global $wpdb;
        
        $vendor = $this->get($vendor_id);
        if (!$vendor) {
            return new \WP_Error('not_found', 'Vendor not found');
        }
        
        $update_data = [];
        
        $allowed_fields = ['name', 'vendor_code', 'tax_id', 'payment_terms', 'account_number', 
                          'phone', 'email', 'website', 'notes', 'status'];
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = sanitize_text_field($data[$field]);
            }
        }
        
        if (empty($update_data)) {
            return new \WP_Error('no_data', 'No data to update');
        }
        
        $update_data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update($this->table_name, $update_data, ['id' => $vendor_id]);
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to update vendor');
        }
        
        // Log changes
        $changes = array_keys($update_data);
        $this->log_audit($vendor_id, 'updated', 'Updated fields: ' . implode(', ', $changes));
        
        return true;
    }
    
    /**
     * Delete vendor (soft delete)
     */
    public function delete($vendor_id) {
        global $wpdb;
        
        // Check if vendor has bills
        $bills_table = $wpdb->prefix . 'fflbro_fin_bills';
        $has_bills = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$bills_table} WHERE vendor_id = %d",
            $vendor_id
        ));
        
        if ($has_bills > 0) {
            return new \WP_Error('has_bills', 'Cannot delete vendor with existing bills. Set status to inactive instead.');
        }
        
        $result = $wpdb->update(
            $this->table_name,
            ['status' => 'deleted', 'updated_at' => current_time('mysql')],
            ['id' => $vendor_id]
        );
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to delete vendor');
        }
        
        $this->log_audit($vendor_id, 'deleted', 'Vendor deleted');
        
        return true;
    }
    
    /**
     * Get vendor addresses
     */
    public function get_addresses($vendor_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->address_table} WHERE entity_type = 'vendor' AND entity_id = %d",
            $vendor_id
        ), ARRAY_A);
    }
    
    /**
     * Add address to vendor
     */
    public function add_address($vendor_id, $data) {
        global $wpdb;
        
        $insert_data = [
            'entity_type' => 'vendor',
            'entity_id' => $vendor_id,
            'address_type' => !empty($data['type']) ? sanitize_text_field($data['type']) : 'billing',
            'line1' => sanitize_text_field($data['line1']),
            'line2' => !empty($data['line2']) ? sanitize_text_field($data['line2']) : null,
            'city' => sanitize_text_field($data['city']),
            'state' => sanitize_text_field($data['state']),
            'postal_code' => sanitize_text_field($data['postal_code']),
            'country' => !empty($data['country']) ? sanitize_text_field($data['country']) : 'US',
            'is_primary' => !empty($data['is_primary']) ? 1 : 0
        ];
        
        return $wpdb->insert($this->address_table, $insert_data);
    }
    
    /**
     * Log audit trail
     */
    /**
     * Generate vendor code from name
     */
    private function generate_vendor_code($name) {
        global $wpdb;
        
        // Generate base code from name (first 6 alphanumeric chars)
        $base = strtoupper(preg_replace("/[^A-Z0-9]/", "", strtoupper($name)));
        $base = substr($base, 0, 6);
        
        if (strlen($base) < 3) {
            $base = "VEND";
        }
        
        // Find next available number
        $num = 1;
        do {
            $code = $base . "-" . str_pad($num, 3, "0", STR_PAD_LEFT);
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$this->table_name} WHERE vendor_code = %s",
                $code
            ));
            $num++;
        } while ($exists && $num < 1000);
        
        return $code;
    }
    

    private function log_audit($vendor_id, $action, $details) {
        global $wpdb;
        $audit_table = $wpdb->prefix . 'fflbro_fin_audit';
        
        $wpdb->insert($audit_table, [
            'entity_type' => 'vendor',
            'entity_id' => $vendor_id,
            'action' => $action,
            'details' => $details,
            'user_id' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Get vendor statistics
     */
    public function get_stats($vendor_id) {
        global $wpdb;
        $bills_table = $wpdb->prefix . 'fflbro_fin_bills';
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_bills,
                SUM(CASE WHEN status = 'unpaid' THEN total_amount ELSE 0 END) as unpaid_amount,
                SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
                SUM(total_amount) as lifetime_amount
            FROM {$bills_table}
            WHERE vendor_id = %d
        ", $vendor_id), ARRAY_A);
    }
}
