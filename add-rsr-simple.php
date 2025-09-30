<?php 
require_once '/opt/fflbro/wordpress-main/wp-config.php';
global $wpdb;
$wpdb->query("INSERT INTO {$wpdb->prefix}fflbro_sync_progress (distributor, status, total_items, processed_items, current_item, last_updated) VALUES ('rsr', 'ready', 0, 0, 'Ready to sync', NOW()) ON DUPLICATE KEY UPDATE status='ready'");
echo 'RSR added to database';

