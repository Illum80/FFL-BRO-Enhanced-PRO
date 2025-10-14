<?php
require_once('wp-load.php');
global $wpdb;

$table = $wpdb->prefix . 'fflbro_products';

echo "Checking products table: $table\n\n";

// Count by distributor
$results = $wpdb->get_results("
    SELECT distributor, COUNT(*) as count 
    FROM $table 
    GROUP BY distributor
");

echo "Products by distributor:\n";
echo "========================\n";
foreach ($results as $row) {
    echo "  " . str_pad(ucfirst($row->distributor), 12) . ": " . number_format($row->count) . " products\n";
}

$total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
echo "  " . str_pad("TOTAL", 12) . ": " . number_format($total) . " products\n\n";

// Sample search for "sig" to debug
echo "Testing search for 'sig':\n";
echo "========================\n";
$sig_results = $wpdb->get_results($wpdb->prepare("
    SELECT distributor, manufacturer, description 
    FROM $table 
    WHERE (description LIKE %s OR manufacturer LIKE %s)
    AND quantity > 0
    LIMIT 5
", '%sig%', '%sig%'));

if (empty($sig_results)) {
    echo "  No results found for 'sig'\n";
} else {
    foreach ($sig_results as $product) {
        echo "  [{$product->distributor}] {$product->manufacturer} - {$product->description}\n";
    }
}

echo "\n";
unlink(__FILE__);
