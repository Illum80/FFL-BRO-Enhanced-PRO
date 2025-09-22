#!/bin/bash

echo "🔍 Diagnosing Form 4473 database issue..."

# Run PHP diagnostic directly
php -r "
define('WP_USE_THEMES', false);
require('/opt/fflbro/wordpress-main/wp-config.php');

global \$wpdb;

echo 'Database connection: ';
if (\$wpdb->db_connect_error) {
    echo 'FAILED - ' . \$wpdb->db_connect_error . PHP_EOL;
    exit(1);
} else {
    echo 'SUCCESS' . PHP_EOL;
}

// Check if table exists
\$table_exists = \$wpdb->get_var(\"SHOW TABLES LIKE '{\$wpdb->prefix}fflbro_form_4473'\");
echo 'Table exists: ' . (\$table_exists ? 'YES' : 'NO') . PHP_EOL;

if (!\$table_exists) {
    echo 'Creating table...' . PHP_EOL;
    
    \$sql = \"CREATE TABLE {\$wpdb->prefix}fflbro_form_4473 (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        form_number varchar(100) NOT NULL,
        status enum('draft', 'in_progress', 'approved', 'denied') DEFAULT 'draft',
        firearms_json text,
        created_by int(11) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY form_number (form_number)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\";
    
    \$result = \$wpdb->query(\$sql);
    if (\$result === false) {
        echo 'Table creation FAILED: ' . \$wpdb->last_error . PHP_EOL;
    } else {
        echo 'Table created successfully!' . PHP_EOL;
    }
} else {
    echo 'Table already exists, checking structure...' . PHP_EOL;
    \$columns = \$wpdb->get_results(\"DESCRIBE {\$wpdb->prefix}fflbro_form_4473\");
    foreach (\$columns as \$column) {
        echo '  Column: ' . \$column->Field . ' (' . \$column->Type . ')' . PHP_EOL;
    }
}

// Test insert with minimal data
echo 'Testing insert...' . PHP_EOL;
\$test_number = '4473TEST' . time();
\$insert_result = \$wpdb->insert(
    \$wpdb->prefix . 'fflbro_form_4473',
    array(
        'form_number' => \$test_number,
        'status' => 'draft',
        'firearms_json' => '[]',
        'created_by' => 1
    ),
    array('%s', '%s', '%s', '%d')
);

if (\$insert_result === false) {
    echo 'Insert FAILED: ' . \$wpdb->last_error . PHP_EOL;
    echo 'Last query: ' . \$wpdb->last_query . PHP_EOL;
} else {
    echo 'Insert SUCCESS - ID: ' . \$wpdb->insert_id . PHP_EOL;
    // Clean up test record
    \$wpdb->delete({\$wpdb->prefix} . 'fflbro_form_4473', array('form_number' => \$test_number));
    echo 'Test record cleaned up.' . PHP_EOL;
}
"

echo "✅ Diagnostic complete!"
