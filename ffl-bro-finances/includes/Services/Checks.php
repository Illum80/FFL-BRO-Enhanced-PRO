<?php
namespace FFLBRO\Fin\Services;

if (!defined('ABSPATH')) exit;

class Checks {
    private $checks_table;
    
    public function __construct() {
        global $wpdb;
        $this->checks_table = $wpdb->prefix . 'fflbro_fin_checks';
    }
    
    /**
     * Generate check for payment
     */
    public function generate($payment_id) {
        global $wpdb;
        
        // Get payment details
        require_once FFLBRO_FIN_PATH . 'includes/Services/Payments.php';
        $payments_service = new Payments();
        $payment = $payments_service->get($payment_id);
        
        if (!$payment) {
            return new \WP_Error('not_found', 'Payment not found');
        }
        
        if (empty($payment['check_number'])) {
            return new \WP_Error('no_check_number', 'Check number not assigned');
        }
        
        // Check if check already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->checks_table} WHERE payment_id = %d",
            $payment_id
        ));
        
        if ($existing) {
            return $existing; // Return existing check ID
        }
        
        // Create check record
        $insert_data = [
            'payment_id' => $payment_id,
            'check_number' => $payment['check_number'],
            'check_date' => $payment['payment_date'],
            'payee_name' => $payment['vendor_name'],
            'amount' => $payment['amount'],
            'memo' => 'Bill: ' . $payment['bill_number'],
            'status' => 'generated',
            'generated_at' => current_time('mysql'),
            'generated_by' => get_current_user_id()
        ];
        
        $result = $wpdb->insert($this->checks_table, $insert_data);
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to create check');
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Generate batch of checks
     */
    public function generate_batch($payment_ids) {
        $check_ids = [];
        
        foreach ($payment_ids as $payment_id) {
            $result = $this->generate($payment_id);
            if (!is_wp_error($result)) {
                $check_ids[] = $result;
            }
        }
        
        return $check_ids;
    }
    
    /**
     * Mark check as printed
     */
    public function mark_printed($check_id) {
        global $wpdb;
        
        return $wpdb->update(
            $this->checks_table,
            [
                'status' => 'printed',
                'printed_at' => current_time('mysql'),
                'printed_by' => get_current_user_id()
            ],
            ['id' => $check_id]
        );
    }
    
    /**
     * Get check details for printing
     */
    public function get_for_print($check_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT 
                c.*,
                p.payment_date,
                b.bill_number,
                v.name as vendor_name,
                v.vendor_code,
                ba.account_name,
                ba.account_number,
                ba.routing_number,
                ba.bank_name
            FROM {$this->checks_table} c
            LEFT JOIN {$wpdb->prefix}fflbro_fin_payments p ON c.payment_id = p.id
            LEFT JOIN {$wpdb->prefix}fflbro_fin_bills b ON p.bill_id = b.id
            LEFT JOIN {$wpdb->prefix}fflbro_fin_vendors v ON b.vendor_id = v.id
            LEFT JOIN {$wpdb->prefix}fflbro_fin_bank_accounts ba ON ba.is_primary = 1
            WHERE c.id = %d
        ", $check_id), ARRAY_A);
    }
    
    /**
     * Convert amount to words
     */
    public function amount_to_words($amount) {
        $amount = floatval($amount);
        $dollars = floor($amount);
        $cents = round(($amount - $dollars) * 100);
        
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        
        $result = '';
        
        if ($dollars >= 1000000) {
            $millions = floor($dollars / 1000000);
            $result .= $this->convert_group($millions, $ones, $tens, $teens) . ' Million ';
            $dollars %= 1000000;
        }
        
        if ($dollars >= 1000) {
            $thousands = floor($dollars / 1000);
            $result .= $this->convert_group($thousands, $ones, $tens, $teens) . ' Thousand ';
            $dollars %= 1000;
        }
        
        if ($dollars > 0) {
            $result .= $this->convert_group($dollars, $ones, $tens, $teens);
        }
        
        $result = trim($result);
        if (empty($result)) {
            $result = 'Zero';
        }
        
        $result .= ' Dollars';
        
        if ($cents > 0) {
            $result .= ' and ' . str_pad($cents, 2, '0', STR_PAD_LEFT) . '/100';
        }
        
        return $result;
    }
    
    private function convert_group($number, $ones, $tens, $teens) {
        $result = '';
        
        if ($number >= 100) {
            $hundreds = floor($number / 100);
            $result .= $ones[$hundreds] . ' Hundred ';
            $number %= 100;
        }
        
        if ($number >= 20) {
            $result .= $tens[floor($number / 10)] . ' ';
            $number %= 10;
        } elseif ($number >= 10) {
            $result .= $teens[$number - 10] . ' ';
            return $result;
        }
        
        if ($number > 0) {
            $result .= $ones[$number] . ' ';
        }
        
        return $result;
    }
}
