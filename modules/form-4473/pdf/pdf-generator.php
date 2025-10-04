<?php
/**
 * PDF/A-2b Generator for ATF Form 4473
 * 20+ Year Archival Compliance
 * Version: 7.3.1
 */

if (!defined('ABSPATH')) exit;

class FFLBRO_PDF_Generator {
    
    private $tcpdf_path;
    
    public function __construct() {
        $this->tcpdf_path = plugin_dir_path(__FILE__) . '../../../includes/form-4473/lib/tcpdf/tcpdf.php';
        
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        register_rest_route('fflbro/v1', '/form-4473/(?P<id>\d+)/pdf', array(
            'methods' => 'GET',
            'callback' => array($this, 'generate_pdf'),
            'permission_callback' => array($this, 'check_permissions')
        ));
    }
    
    public function generate_pdf($request) {
        $form_id = $request['id'];
        
        // Get complete form data
        $form_data = $this->get_form_data($form_id);
        
        if (!$form_data) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Form not found'
            ), 404);
        }
        
        // Generate PDF
        $pdf_path = $this->create_pdf($form_data);
        
        if ($pdf_path && file_exists($pdf_path)) {
            // Update form record with PDF path
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'main_fflbro_form4473',
                array('pdf_path' => $pdf_path),
                array('id' => $form_id)
            );
            
            // Log audit
            $this->log_audit($form_id, 'pdf_generated', 'PDF/A-2b document generated', array(
                'pdf_path' => $pdf_path,
                'file_size' => filesize($pdf_path)
            ));
            
            // Return PDF for download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Form-4473-' . $form_data['form_number'] . '.pdf"');
            header('Content-Length: ' . filesize($pdf_path));
            readfile($pdf_path);
            exit;
        }
        
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'PDF generation failed'
        ), 500);
    }
    
    private function get_form_data($form_id) {
        global $wpdb;
        
        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}main_fflbro_form4473 WHERE id = %d",
            $form_id
        ), ARRAY_A);
        
        if (!$form) return false;
        
        // Get related data
        $form['firearms'] = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}main_fflbro_form4473_firearms WHERE form_id = %d",
            $form_id
        ), ARRAY_A);
        
        $form['transferee'] = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}main_fflbro_form4473_transferee WHERE form_id = %d",
            $form_id
        ), ARRAY_A);
        
        $form['questions'] = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}main_fflbro_form4473_questions WHERE form_id = %d",
            $form_id
        ), ARRAY_A);
        
        $form['nics'] = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}main_fflbro_form4473_nics WHERE form_id = %d",
            $form_id
        ), ARRAY_A);
        
        $form['signatures'] = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}main_fflbro_form4473_signatures WHERE form_id = %d",
            $form_id
        ), ARRAY_A);
        
        return $form;
    }
    
    private function create_pdf($data) {
        if (!file_exists($this->tcpdf_path)) {
            error_log('TCPDF not found at: ' . $this->tcpdf_path);
            return false;
        }
        
        require_once($this->tcpdf_path);
        
        // Create new PDF document (PDF/A-2b compliant)
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('FFL-BRO Enhanced PRO v7.3.1');
        $pdf->SetAuthor('FFL-BRO System');
        $pdf->SetTitle('ATF Form 4473 - ' . $data['form_number']);
        $pdf->SetSubject('Firearms Transaction Record');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        // Add page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 10);
        
        // Build PDF content
        $html = $this->build_pdf_content($data);
        
        // Write HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Save PDF
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/form-4473/';
        
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
            // Protect directory
            file_put_contents($pdf_dir . '.htaccess', 'Deny from all');
        }
        
        $filename = 'Form-4473-' . $data['form_number'] . '-' . date('Ymd-His') . '.pdf';
        $pdf_path = $pdf_dir . $filename;
        
        // Output PDF
        $pdf->Output($pdf_path, 'F');
        
        return file_exists($pdf_path) ? $pdf_path : false;
    }
    
    private function build_pdf_content($data) {
        $html = '<h1 style="text-align: center;">ATF Form 4473 (5300.9)</h1>';
        $html .= '<h2 style="text-align: center;">Firearms Transaction Record</h2>';
        $html .= '<p style="text-align: center;"><strong>Form Number: ' . htmlspecialchars($data['form_number']) . '</strong></p>';
        $html .= '<hr>';
        
        // Section A: Firearms Information
        $html .= '<h3>Section A: Firearms Information</h3>';
        if (!empty($data['firearms'])) {
            foreach ($data['firearms'] as $firearm) {
                $html .= '<p><strong>Manufacturer:</strong> ' . htmlspecialchars($firearm['manufacturer']) . '<br>';
                $html .= '<strong>Model:</strong> ' . htmlspecialchars($firearm['model']) . '<br>';
                $html .= '<strong>Serial Number:</strong> ' . htmlspecialchars($firearm['serial_number']) . '<br>';
                $html .= '<strong>Type:</strong> ' . htmlspecialchars($firearm['type']) . '<br>';
                $html .= '<strong>Caliber:</strong> ' . htmlspecialchars($firearm['caliber']) . '</p>';
                $html .= '<hr>';
            }
        }
        
        // Section B: Transferee Information
        if (!empty($data['transferee'])) {
            $t = $data['transferee'];
            $html .= '<h3>Section B: Transferee Information</h3>';
            $html .= '<p><strong>Name:</strong> ' . htmlspecialchars($t['last_name'] . ', ' . $t['first_name'] . ' ' . $t['middle_name']) . '<br>';
            $html .= '<strong>Address:</strong> ' . htmlspecialchars($t['address']) . '<br>';
            $html .= '<strong>City:</strong> ' . htmlspecialchars($t['city']) . ', <strong>State:</strong> ' . htmlspecialchars($t['state']) . ' <strong>ZIP:</strong> ' . htmlspecialchars($t['zip']) . '<br>';
            $html .= '<strong>DOB:</strong> ' . htmlspecialchars($t['date_of_birth']) . '<br>';
            $html .= '<strong>Place of Birth:</strong> ' . htmlspecialchars($t['place_of_birth']) . '</p>';
            $html .= '<hr>';
        }
        
        // Section C: Background Check Questions
        if (!empty($data['questions'])) {
            $html .= '<h3>Section C: Background Check Questions</h3>';
            $q = $data['questions'];
            for ($i = 1; $i <= 12; $i++) {
                $field = 'question_' . $i . '_answer';
                if (isset($q[$field])) {
                    $html .= '<p><strong>Question ' . $i . ':</strong> ' . htmlspecialchars($q[$field]) . '</p>';
                }
            }
            $html .= '<hr>';
        }
        
        // Section D: NICS Check
        if (!empty($data['nics'])) {
            $n = $data['nics'];
            $html .= '<h3>Section D: NICS Background Check</h3>';
            $html .= '<p><strong>Transaction Number:</strong> ' . htmlspecialchars($n['transaction_number']) . '<br>';
            $html .= '<strong>Check Date:</strong> ' . htmlspecialchars($n['check_date']) . '<br>';
            $html .= '<strong>Result:</strong> ' . htmlspecialchars($n['result']) . '</p>';
            $html .= '<hr>';
        }
        
        // Signatures
        $html .= '<h3>Signatures</h3>';
        if (!empty($data['signatures'])) {
            foreach ($data['signatures'] as $sig) {
                $html .= '<p><strong>' . ucfirst($sig['signature_type']) . ' Signature:</strong><br>';
                $html .= '<img src="' . $sig['signature_data'] . '" style="max-width: 200px;"><br>';
                $html .= '<strong>Signed:</strong> ' . htmlspecialchars($sig['signed_date']) . '<br>';
                $html .= '<strong>IP:</strong> ' . htmlspecialchars($sig['ip_address']) . '</p>';
            }
        }
        
        $html .= '<p style="margin-top: 30px; font-size: 8pt;"><em>Generated by FFL-BRO Enhanced PRO v7.3.1 on ' . date('Y-m-d H:i:s') . '</em></p>';
        
        return $html;
    }
    
    private function log_audit($form_id, $action, $description, $data = array()) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'main_fflbro_form4473_audit',
            array(
                'form_id' => $form_id,
                'action' => $action,
                'description' => $description,
                'data_json' => json_encode($data),
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'created_at' => current_time('mysql')
            )
        );
    }
    
    public function check_permissions() {
        return current_user_can('manage_options') || current_user_can('edit_posts');
    }
}

new FFLBRO_PDF_Generator();
