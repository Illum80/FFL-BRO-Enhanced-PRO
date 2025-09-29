#!/bin/bash

echo "🔧 Fixing Form 4473 database tables..."

# Test database connection through WordPress
php -r "
define('WP_USE_THEMES', false);
require('/opt/fflbro/wordpress-main/wp-config.php');

global \$wpdb;

// Drop and recreate the table to fix any issues
\$wpdb->query('DROP TABLE IF EXISTS {$wpdb->prefix}fflbro_form_4473');

// Create the table with proper structure
\$sql = \"CREATE TABLE {$wpdb->prefix}fflbro_form_4473 (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    form_number varchar(100) NOT NULL,
    customer_id mediumint(9) NULL,
    transaction_type enum('sale', 'transfer', 'return') DEFAULT 'sale',
    status enum('draft', 'in_progress', 'pending_approval', 'approved', 'denied', 'cancelled') DEFAULT 'draft',
    firearms_json text NOT NULL,
    transferee_first_name varchar(100) DEFAULT '',
    transferee_middle_name varchar(100) DEFAULT '',
    transferee_last_name varchar(100) DEFAULT '',
    transferee_date_of_birth date NULL,
    transferee_gender enum('M', 'F', 'X') NULL,
    transferee_ethnicity enum('hispanic', 'not_hispanic') NULL,
    transferee_race varchar(500) DEFAULT '',
    transferee_address varchar(255) DEFAULT '',
    transferee_city varchar(100) DEFAULT '',
    transferee_state varchar(50) DEFAULT '',
    transferee_zip varchar(20) DEFAULT '',
    transferee_phone varchar(20) DEFAULT '',
    transferee_email varchar(255) DEFAULT '',
    question_a boolean DEFAULT false,
    question_b boolean DEFAULT false,
    question_c boolean DEFAULT false,
    question_d boolean DEFAULT false,
    question_e boolean DEFAULT false,
    question_f boolean DEFAULT false,
    question_g boolean DEFAULT false,
    question_h boolean DEFAULT false,
    question_i boolean DEFAULT false,
    question_j boolean DEFAULT false,
    question_k boolean DEFAULT false,
    question_l boolean DEFAULT false,
    nics_transaction_number varchar(100) DEFAULT '',
    background_check_date datetime NULL,
    background_check_result enum('proceed', 'delay', 'deny') DEFAULT 'proceed',
    background_check_notes text,
    created_by int(11) NOT NULL DEFAULT 1,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY form_number_idx (form_number),
    KEY status_idx (status),
    KEY created_at_idx (created_at)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\";

\$result = \$wpdb->query(\$sql);

if (\$result === false) {
    echo 'Error creating table: ' . \$wpdb->last_error . PHP_EOL;
} else {
    echo 'Form 4473 table created successfully!' . PHP_EOL;
}

// Test creating a form
\$test_insert = \$wpdb->insert(
    \$wpdb->prefix . 'fflbro_form_4473',
    array(
        'form_number' => '4473TEST001',
        'status' => 'draft',
        'firearms_json' => '[]',
        'created_by' => 1
    ),
    array('%s', '%s', '%s', '%d')
);

if (\$test_insert) {
    echo 'Test form created successfully!' . PHP_EOL;
    \$wpdb->delete(\$wpdb->prefix . 'fflbro_form_4473', array('form_number' => '4473TEST001'));
    echo 'Test form cleaned up.' . PHP_EOL;
} else {
    echo 'Error creating test form: ' . \$wpdb->last_error . PHP_EOL;
}
"

echo "✅ Database fix completed!"
