<?php
// Quick database check for Lipseys products
require_once('/opt/fflbro/wordpress-main/wp-config.php');

global $wpdb;

echo "Checking FFL-BRO database tables...\n";
echo "====================================\n";

// Check if products table exists
$table_name = $wpdb->prefix . 'fflbro_products';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

if ($table_exists) {
    echo "✅ Products table exists: $table_name\n";
    
    // Count total products
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "📊 Total products: $total\n";
    
    // Count Lipseys products specifically
    $lipseys = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE distributor = 'lipseys'");
    echo "🔫 Lipseys products: $lipseys\n";
    
    // Show sample product
    if ($lipseys > 0) {
        $sample = $wpdb->get_row("SELECT * FROM $table_name WHERE distributor = 'lipseys' LIMIT 1");
        echo "📋 Sample product:\n";
        echo "   - Item: " . $sample->item_number . "\n";
        echo "   - Description: " . substr($sample->description, 0, 50) . "...\n";
        echo "   - Price: $" . $sample->price . "\n";
    }
} else {
    echo "❌ Products table does NOT exist: $table_name\n";
    echo "🔧 Need to run database activation first\n";
}
