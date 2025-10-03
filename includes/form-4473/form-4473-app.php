<?php
/**
 * Digital ATF Form 4473 - Admin Page
 */

if (!defined('ABSPATH')) exit;

function fflbro_form_4473_page() {
    ?>
    <div class="wrap">
        <h1>📋 Digital ATF Form 4473</h1>
        <div id="fflbro-form-4473-root"></div>
    </div>
    
    <script>
        var fflbroForm4473 = {
            nonce: '<?php echo wp_create_nonce('fflbro_nonce'); ?>',
            ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>'
        };
    </script>
    <?php
}

// Enqueue scripts and styles
add_action('admin_enqueue_scripts', function($hook) {
    if (strpos($hook, 'fflbro-form-4473') === false) return;
    
    wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.production.min.js', array(), '18.2.0', true);
    wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', array('react'), '18.2.0', true);
    
    $plugin_url = plugin_dir_url(__FILE__);
    
    wp_enqueue_script('fflbro-form-4473-app', 
        $plugin_url . 'form-4473-component.js', 
        array('react', 'react-dom'), 
        '1.0.1', 
        true
    );
    
    wp_enqueue_style('fflbro-form-4473-styles',
        $plugin_url . 'form-4473-styles.css',
        array(),
        '1.0.1'
    );
});
