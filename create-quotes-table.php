<?php
require_once('wp-load.php');
global $wpdb;

$table_name = $wpdb->prefix . 'fflbro_quotes';
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS $table_name (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_number VARCHAR(50) NOT NULL UNIQUE,
    customer_name VARCHAR(255),
    customer_email VARCHAR(255),
    customer_phone VARCHAR(50),
    quote_data LONGTEXT,
    subtotal DECIMAL(10,2),
    tax DECIMAL(10,2),
    total DECIMAL(10,2),
    status VARCHAR(50) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    INDEX(quote_number),
    INDEX(customer_email),
    INDEX(status)
) $charset_collate;";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);

echo "✅ Creating table: $table_name\n";
echo "   Database: " . DB_NAME . "\n";
echo "   Prefix: " . $wpdb->prefix . "\n\n";

// Verify it was created
$result = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
if ($result == $table_name) {
    echo "✅ SUCCESS! Table created and verified!\n\n";
    
    // Show table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo "Table structure:\n";
    foreach ($columns as $column) {
        echo "  - {$column->Field} ({$column->Type})\n";
    }
} else {
    echo "❌ ERROR: Table not found after creation\n";
}

// Clean up
unlink(__FILE__);
echo "\n✅ Cleanup: Script deleted itself\n";
