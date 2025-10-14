<?php
// Web-accessible database check
require_once('wp-config.php');

header('Content-Type: text/plain');

global $wpdb;

echo "FFL-BRO Database Status Check\n";
echo "============================\n\n";

// Check if products table exists
$table_name = $wpdb->prefix . 'fflbro_products';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

if ($table_exists) {
    echo "✅ Products table exists: $table_name\n";
    
    // Count total products
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "📊 Total products: $total\n";
    
    // Count by distributor
    $distributors = $wpdb->get_results("SELECT distributor, COUNT(*) as count FROM $table_name GROUP BY distributor");
    echo "\n📋 Products by distributor:\n";
    foreach ($distributors as $dist) {
        echo "   - {$dist->distributor}: {$dist->count}\n";
    }
    
    // Count Lipseys specifically  
    $lipseys = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE distributor = 'lipseys'");
    echo "\n🔫 Lipseys products: $lipseys\n";
    
    if ($lipseys > 0) {
        $sample = $wpdb->get_row("SELECT * FROM $table_name WHERE distributor = 'lipseys' LIMIT 1");
        echo "\n📋 Sample Lipseys product:\n";
        echo "   - ID: " . $sample->id . "\n";
        echo "   - Item: " . $sample->item_number . "\n"; 
        echo "   - Description: " . substr($sample->description, 0, 60) . "...\n";
        echo "   - Price: $" . $sample->price . "\n";
    }
    
} else {
    echo "❌ Products table NOT found: $table_name\n\n";
    
    // Show existing FFL-BRO tables
    $tables = $wpdb->get_results("SHOW TABLES LIKE 'wp_fflbro_%'");
    echo "Existing FFL-BRO tables:\n";
    foreach ($tables as $table) {
        $table_name = array_values((array) $table)[0];
        echo "   - $table_name\n";
    }
}

echo "\n=== DIAGNOSIS ===\n";
if (!$table_exists) {
    echo "❌ Problem: No products table - plugin not properly activated\n";
    echo "🔧 Solution: Deactivate and reactivate FFL-BRO Enhanced PRO plugin\n";
} elseif ($lipseys == 0) {
    echo "❌ Problem: No Lipseys products in database\n"; 
    echo "🔧 Solution: Run Lipseys catalog sync to populate products\n";
} else {
    echo "✅ Database looks good - search should work!\n";
}
