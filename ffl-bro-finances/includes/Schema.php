<?php
namespace FFLBRO\Fin;

if (!defined('ABSPATH')) exit;

class Schema {
    public static function install() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'fflbro_fin_';
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        $tables = [
            // Vendors
            "CREATE TABLE {$prefix}vendors (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                name VARCHAR(255) NOT NULL,
                dba VARCHAR(255),
                tax_id VARCHAR(50),
                remit_address_id BIGINT UNSIGNED,
                contact_json TEXT,
                payment_terms VARCHAR(50),
                default_expense_acct BIGINT UNSIGNED,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_status (status),
                INDEX idx_name (name)
            ) $charset;",
            
            // Addresses
            "CREATE TABLE {$prefix}addresses (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                line1 VARCHAR(255),
                line2 VARCHAR(255),
                city VARCHAR(100),
                state VARCHAR(50),
                postal VARCHAR(20),
                country VARCHAR(50) DEFAULT 'US',
                phone VARCHAR(30),
                email VARCHAR(255),
                kind VARCHAR(50)
            ) $charset;",
            
            // Bills
            "CREATE TABLE {$prefix}bills (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                vendor_id BIGINT UNSIGNED NOT NULL,
                bill_no VARCHAR(100),
                bill_date DATE NOT NULL,
                due_date DATE,
                terms VARCHAR(50),
                subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
                tax_total DECIMAL(12,2) NOT NULL DEFAULT 0,
                ship_total DECIMAL(12,2) NOT NULL DEFAULT 0,
                other_total DECIMAL(12,2) NOT NULL DEFAULT 0,
                total DECIMAL(12,2) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'draft',
                memo TEXT,
                created_by BIGINT UNSIGNED,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_vendor (vendor_id),
                INDEX idx_status (status),
                INDEX idx_dates (bill_date, due_date)
            ) $charset;",
            
            // Bill Items
            "CREATE TABLE {$prefix}bill_items (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                bill_id BIGINT UNSIGNED NOT NULL,
                line_no INT NOT NULL,
                sku VARCHAR(100),
                description TEXT,
                qty DECIMAL(10,2) NOT NULL DEFAULT 1,
                unit_cost DECIMAL(12,2) NOT NULL,
                amount DECIMAL(12,2) NOT NULL,
                expense_acct BIGINT UNSIGNED,
                tracking_tags_json TEXT,
                INDEX idx_bill (bill_id)
            ) $charset;",
            
            // Payments
            "CREATE TABLE {$prefix}payments (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                bill_id BIGINT UNSIGNED NOT NULL,
                method VARCHAR(50) NOT NULL DEFAULT 'check',
                amount DECIMAL(12,2) NOT NULL,
                scheduled_on DATE,
                paid_on DATE,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                memo TEXT,
                INDEX idx_bill (bill_id),
                INDEX idx_status (status)
            ) $charset;",
            
            // Checks
            "CREATE TABLE {$prefix}checks (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                payment_id BIGINT UNSIGNED NOT NULL,
                bank_account_id BIGINT UNSIGNED NOT NULL,
                check_no VARCHAR(20),
                print_batch_id BIGINT UNSIGNED,
                micr_routing VARCHAR(20),
                micr_account VARCHAR(30),
                micr_check_no VARCHAR(20),
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                pdf_path VARCHAR(255),
                positive_pay_status VARCHAR(20),
                created_at DATETIME NOT NULL,
                INDEX idx_payment (payment_id),
                INDEX idx_bank (bank_account_id),
                INDEX idx_batch (print_batch_id)
            ) $charset;",
            
            // Bank Accounts
            "CREATE TABLE {$prefix}bank_accounts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                bank_name VARCHAR(255),
                last4 VARCHAR(4),
                routing VARCHAR(20),
                account_number_masked VARCHAR(50),
                next_check_no INT UNSIGNED NOT NULL DEFAULT 1001,
                positive_pay_format VARCHAR(50) DEFAULT 'bai2',
                ach_company_id VARCHAR(50),
                ach_company_name VARCHAR(255),
                is_default BOOLEAN DEFAULT 0
            ) $charset;",
            
            // Chart of Accounts
            "CREATE TABLE {$prefix}coa (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(50) NOT NULL,
                parent_id BIGINT UNSIGNED,
                active BOOLEAN DEFAULT 1,
                INDEX idx_type (type),
                INDEX idx_parent (parent_id)
            ) $charset;",
            
            // Journal
            "CREATE TABLE {$prefix}journal (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                txn_type VARCHAR(50) NOT NULL,
                txn_id BIGINT UNSIGNED,
                entry_date DATE NOT NULL,
                memo TEXT,
                INDEX idx_txn (txn_type, txn_id),
                INDEX idx_date (entry_date)
            ) $charset;",
            
            // Journal Lines
            "CREATE TABLE {$prefix}journal_lines (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                journal_id BIGINT UNSIGNED NOT NULL,
                acct_id BIGINT UNSIGNED NOT NULL,
                debit DECIMAL(12,2) NOT NULL DEFAULT 0,
                credit DECIMAL(12,2) NOT NULL DEFAULT 0,
                INDEX idx_journal (journal_id),
                INDEX idx_account (acct_id)
            ) $charset;",
            
            // Attachments
            "CREATE TABLE {$prefix}attachments (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                owner_type VARCHAR(50) NOT NULL,
                owner_id BIGINT UNSIGNED NOT NULL,
                storage_path VARCHAR(500),
                filename VARCHAR(255),
                mime VARCHAR(100),
                bytes BIGINT UNSIGNED,
                uploaded_by BIGINT UNSIGNED,
                uploaded_at DATETIME NOT NULL,
                INDEX idx_owner (owner_type, owner_id)
            ) $charset;",
            
            // Audit Trail
            "CREATE TABLE {$prefix}audit (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                actor_id BIGINT UNSIGNED,
                action VARCHAR(50) NOT NULL,
                target_type VARCHAR(50) NOT NULL,
                target_id BIGINT UNSIGNED,
                before_json TEXT,
                after_json TEXT,
                ip VARCHAR(50),
                ua TEXT,
                at DATETIME NOT NULL,
                INDEX idx_target (target_type, target_id),
                INDEX idx_actor (actor_id),
                INDEX idx_date (at)
            ) $charset;"
        ];
        
        foreach ($tables as $sql) {
            dbDelta($sql);
        }
    }
}
