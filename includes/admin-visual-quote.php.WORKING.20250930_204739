<?php
/**
 * Visual Quote Generator - Admin Page
 */

if (!defined('ABSPATH')) exit;

add_action('admin_menu', 'fflbro_visual_quote_menu', 20);

function fflbro_visual_quote_menu() {
    add_submenu_page(
        'fflbro-enhanced-pro',
        'Visual Quote Generator',
        'Quote Generator',
        'manage_options',
        'fflbro-visual-quotes',
        'fflbro_visual_quote_page'
    );
}

function fflbro_visual_quote_page() {
    ?>
    <div class="wrap" style="margin: 0; padding: 0;">
        <div id="fflbro-visual-quote-root"></div>
    </div>
    
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    
    <script>
        window.fflbroQuote = {
            nonce: '<?php echo wp_create_nonce('fflbro_quote_nonce'); ?>',
            ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>'
        };
    </script>
    
    <script src="<?php echo plugins_url('assets/js/visual-quote-generator.js', dirname(__FILE__)); ?>?v=<?php echo time(); ?>"></script>
    
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                if (window.React && window.ReactDOM && window.VisualQuoteGenerator) {
                    const root = ReactDOM.createRoot(document.getElementById('fflbro-visual-quote-root'));
                    root.render(React.createElement(window.VisualQuoteGenerator));
                    console.log('✅ Quote Generator initialized');
                } else {
                    console.error('Failed to load dependencies');
                }
            }, 200);
        });
    </script>
    <?php
}



// Debug: Log when actions are registered
add_action('init', function() {
    error_log('AJAX handlers registered - fflbro_search_products');
}, 1);
