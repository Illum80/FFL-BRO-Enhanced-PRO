<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', 'fflbro_visual_quote_admin_menu');

function fflbro_visual_quote_admin_menu() {
    add_submenu_page(
        'fflbro-enhanced-pro',
        'Visual Quote Generator',
        'Quote Generator',
        'manage_options',
        'fflbro-visual-quotes',
        'fflbro_visual_quote_admin_page'
    );
}

function fflbro_visual_quote_admin_page() {
    ?>
    <div class="wrap" style="margin: 0; padding: 0;">
        <div id="fflbro-visual-quote-root"></div>
    </div>
    
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script>
        var fflbroQuote = {
            nonce: '<?php echo wp_create_nonce('fflbro_quote_nonce'); ?>',
            ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>'
        };
    </script>
    <script src="<?php echo plugins_url('components/visual-quote-generator.jsx', dirname(__FILE__)); ?>"></script>
    <script>
        setTimeout(() => {
            if (window.VisualQuoteGenerator) {
                const root = ReactDOM.createRoot(document.getElementById('fflbro-visual-quote-root'));
                root.render(React.createElement(VisualQuoteGenerator));
            }
        }, 500);
    </script>
    <?php
}
