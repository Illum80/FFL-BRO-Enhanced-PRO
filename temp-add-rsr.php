<?php
// Temporary file to add RSR - add this to your plugin temporarily
add_action('admin_menu', function() {
    add_submenu_page(
        'fflbro-enhanced-pro',
        'Add RSR',
        'Add RSR',
        'manage_options',
        'add-rsr-temp',
        function() {
            global $wpdb;
            
            if (isset($_GET['do_add'])) {
                $result = $wpdb->replace(
                    $wpdb->prefix . 'fflbro_sync_progress',
                    array(
                        'distributor' => 'rsr',
                        'status' => 'ready',
                        'total_items' => 0,
                        'processed_items' => 0,
                        'current_item' => 'Ready to sync',
                        'last_updated' => current_time('mysql')
                    )
                );
                
                echo '<div class="notice notice-success"><p>RSR added successfully!</p></div>';
            }
            
            $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}fflbro_sync_progress ORDER BY distributor");
            echo '<div class="wrap"><h1>Current Distributors</h1>';
            echo '<a href="?page=add-rsr-temp&do_add=1" class="button">Add RSR to Database</a><br><br>';
            foreach ($results as $row) {
                echo "- {$row->distributor}: {$row->status}<br>";
            }
            echo '</div>';
        }
    );
});
