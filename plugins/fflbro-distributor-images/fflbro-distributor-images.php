<?php
/**
 * Plugin Name: FFL-BRO Distributor Images & Logos
 * Description: Handles all distributor logos and product images for quotes, cards, and catalog
 * Version: 1.0.0
 * Author: NEEFECO ARMS
 */

if (!defined('ABSPATH')) exit;

class FFLBRO_Distributor_Images {
    
    private $distributor_logos = array(
        'lipseys' => array(
            'name' => "Lipsey's",
            'logo_url' => 'https://www.lipseys.com/images/logo-main.png',
            'alt' => "Lipsey's Logo",
            'website' => 'https://www.lipseys.com',
            'image_cdn' => 'https://www.lipseys.com/images/items/'
        ),
        'rsr' => array(
            'name' => 'RSR Group',
            'logo_url' => 'https://www.rsrgroup.com/images/rsr-logo.svg',
            'alt' => 'RSR Group Logo',
            'website' => 'https://www.rsrgroup.com',
            'image_cdn' => 'https://img.rsrgroup.com/highres-itemimages/'
        ),
        'davidsons' => array(
            'name' => "Davidson's",
            'logo_url' => 'https://www.davidsonsinc.com/assets/images/davidsons-logo.png',
            'alt' => "Davidson's Logo",
            'website' => 'https://www.davidsonsinc.com',
            'image_cdn' => 'https://res.cloudinary.com/davidsons-inc/image/upload/'
        ),
        'sports_south' => array(
            'name' => 'Sports South',
            'logo_url' => 'https://www.sportssouth.com/assets/images/sports-south-logo.png',
            'alt' => 'Sports South Logo',
            'website' => 'https://www.sportssouth.com',
            'image_cdn' => 'https://images.sportssouth.com/'
        ),
        'orion' => array(
            'name' => 'Orion Wholesale',
            'logo_url' => 'https://www.orionwholesale.com/images/orion-logo.png',
            'alt' => 'Orion Wholesale Logo',
            'website' => 'https://www.orionwholesale.com',
            'image_cdn' => 'https://images.orionwholesale.com/'
        ),
        'zanders' => array(
            'name' => "Zander's Sporting Goods",
            'logo_url' => 'https://www.zandersporting.com/images/zanders-logo.png',
            'alt' => "Zander's Logo",
            'website' => 'https://www.zandersporting.com',
            'image_cdn' => 'https://www.zandersporting.com/images/products/'
        )
    );

    public function __construct() {
        // Add image support hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_image_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_image_styles'));
        
        // Add AJAX handlers for image loading
        add_action('wp_ajax_fflbro_get_distributor_logo', array($this, 'ajax_get_distributor_logo'));
        add_action('wp_ajax_nopriv_fflbro_get_distributor_logo', array($this, 'ajax_get_distributor_logo'));
        add_action('wp_ajax_fflbro_get_product_image', array($this, 'ajax_get_product_image'));
        add_action('wp_ajax_nopriv_fflbro_get_product_image', array($this, 'ajax_get_product_image'));
        
        // Add shortcodes
        add_shortcode('fflbro_distributor_logo', array($this, 'distributor_logo_shortcode'));
        add_shortcode('fflbro_product_image', array($this, 'product_image_shortcode'));
        
        // Add filters for image URLs
        add_filter('fflbro_format_product_image_url', array($this, 'format_product_image_url'), 10, 3);
    }

    /**
     * Enqueue image styles
     */
    public function enqueue_image_styles() {
        wp_enqueue_style(
            'fflbro-distributor-images',
            plugins_url('assets/css/distributor-images.css', __FILE__),
            array(),
            '1.0.0'
        );
        
        // Add inline styles for logo sizing
        $custom_css = "
            .distributor-logo {
                max-height: 40px;
                width: auto;
                object-fit: contain;
            }
            .distributor-logo-card {
                max-height: 60px;
                width: auto;
                object-fit: contain;
            }
            .product-image {
                max-width: 100%;
                height: auto;
                border-radius: 8px;
            }
            .product-image-thumb {
                max-width: 80px;
                max-height: 80px;
                object-fit: cover;
                border-radius: 4px;
            }
            .distributor-card {
                position: relative;
                background: white;
                border-radius: 12px;
                padding: 20px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .distributor-card-header {
                display: flex;
                align-items: center;
                gap: 15px;
                margin-bottom: 15px;
            }
            .no-image-placeholder {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                border-radius: 8px;
            }
        ";
        wp_add_inline_style('fflbro-distributor-images', $custom_css);
        
        // Enqueue JavaScript for lazy loading
        wp_enqueue_script(
            'fflbro-image-loader',
            plugins_url('assets/js/image-loader.js', __FILE__),
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('fflbro-image-loader', 'fflbroImages', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fflbro_images_nonce')
        ));
    }

    /**
     * Get distributor logo HTML
     */
    public function get_distributor_logo($distributor_id, $class = 'distributor-logo') {
        if (!isset($this->distributor_logos[$distributor_id])) {
            return $this->get_placeholder_logo($distributor_id, $class);
        }
        
        $logo = $this->distributor_logos[$distributor_id];
        
        return sprintf(
            '<img src="%s" alt="%s" class="%s" loading="lazy" onerror="this.onerror=null; this.src=\'data:image/svg+xml,%%3Csvg xmlns=%%22http://www.w3.org/2000/svg%%22 width=%%22100%%22 height=%%2240%%22%%3E%%3Crect fill=%%22%%23667eea%%22 width=%%22100%%22 height=%%2240%%22/%%3E%%3Ctext x=%%2250%%25%%22 y=%%2250%%25%%22 dominant-baseline=%%22middle%%22 text-anchor=%%22middle%%22 fill=%%22white%%22 font-family=%%22Arial%%22 font-size=%%2212%%22%%3E%s%%3C/text%%3E%%3C/svg%%3E\';">',
            esc_url($logo['logo_url']),
            esc_attr($logo['alt']),
            esc_attr($class),
            esc_attr(strtoupper(substr($logo['name'], 0, 3)))
        );
    }

    /**
     * Get placeholder logo for unknown distributors
     */
    private function get_placeholder_logo($distributor_id, $class) {
        $name = ucfirst($distributor_id);
        $initials = strtoupper(substr($name, 0, 3));
        
        return sprintf(
            '<div class="%s no-image-placeholder" style="width: 100px; height: 40px;">%s</div>',
            esc_attr($class),
            esc_html($initials)
        );
    }

    /**
     * Format product image URL based on distributor
     */
    public function format_product_image_url($image_name, $distributor_id, $product_data = array()) {
        if (empty($image_name) || !isset($this->distributor_logos[$distributor_id])) {
            return $this->get_placeholder_image_url();
        }
        
        $cdn_base = $this->distributor_logos[$distributor_id]['image_cdn'];
        
        // Different distributors have different URL patterns
        switch ($distributor_id) {
            case 'lipseys':
                // Lipseys: https://www.lipseys.com/images/items/{imageName}
                return $cdn_base . $image_name;
                
            case 'rsr':
                // RSR: https://img.rsrgroup.com/highres-itemimages/{RSRStockNo}_1.jpg
                $stock_no = isset($product_data['rsr_stock_no']) ? $product_data['rsr_stock_no'] : $image_name;
                return $cdn_base . $stock_no . '_1.jpg';
                
            case 'davidsons':
                // Davidson's uses Cloudinary CDN
                // Pattern: https://res.cloudinary.com/davidsons-inc/image/upload/{ItemNo}.jpg
                $item_no = isset($product_data['item_no']) ? $product_data['item_no'] : $image_name;
                return $cdn_base . 'c_pad,h_500,w_500/' . $item_no . '.jpg';
                
            case 'sports_south':
                // Sports South: Direct image URLs
                return $cdn_base . $image_name;
                
            case 'orion':
                // Orion Wholesale
                return $cdn_base . $image_name;
                
            case 'zanders':
                // Zanders: Item image links from CSV
                return $cdn_base . $image_name;
                
            default:
                return $this->get_placeholder_image_url();
        }
    }

    /**
     * Get placeholder image URL
     */
    private function get_placeholder_image_url() {
        // Return a base64 encoded SVG placeholder
        return 'data:image/svg+xml;base64,' . base64_encode('
            <svg xmlns="http://www.w3.org/2000/svg" width="300" height="300">
                <rect fill="#667eea" width="300" height="300"/>
                <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" 
                      fill="white" font-family="Arial" font-size="24">No Image</text>
            </svg>
        ');
    }

    /**
     * Get product image HTML
     */
    public function get_product_image($image_name, $distributor_id, $product_data = array(), $class = 'product-image') {
        $image_url = $this->format_product_image_url($image_name, $distributor_id, $product_data);
        $product_name = isset($product_data['name']) ? $product_data['name'] : 'Product';
        
        return sprintf(
            '<img src="%s" alt="%s" class="%s" loading="lazy" onerror="this.onerror=null; this.src=\'%s\';">',
            esc_url($image_url),
            esc_attr($product_name),
            esc_attr($class),
            esc_url($this->get_placeholder_image_url())
        );
    }

    /**
     * AJAX handler for getting distributor logo
     */
    public function ajax_get_distributor_logo() {
        check_ajax_referer('fflbro_images_nonce', 'nonce');
        
        $distributor_id = isset($_POST['distributor_id']) ? sanitize_text_field($_POST['distributor_id']) : '';
        $class = isset($_POST['class']) ? sanitize_text_field($_POST['class']) : 'distributor-logo';
        
        if (empty($distributor_id)) {
            wp_send_json_error('Distributor ID required');
            return;
        }
        
        $logo_html = $this->get_distributor_logo($distributor_id, $class);
        
        wp_send_json_success(array(
            'html' => $logo_html,
            'data' => isset($this->distributor_logos[$distributor_id]) ? $this->distributor_logos[$distributor_id] : array()
        ));
    }

    /**
     * AJAX handler for getting product image
     */
    public function ajax_get_product_image() {
        check_ajax_referer('fflbro_images_nonce', 'nonce');
        
        $image_name = isset($_POST['image_name']) ? sanitize_text_field($_POST['image_name']) : '';
        $distributor_id = isset($_POST['distributor_id']) ? sanitize_text_field($_POST['distributor_id']) : '';
        $product_data = isset($_POST['product_data']) ? $_POST['product_data'] : array();
        $class = isset($_POST['class']) ? sanitize_text_field($_POST['class']) : 'product-image';
        
        if (empty($distributor_id)) {
            wp_send_json_error('Distributor ID required');
            return;
        }
        
        $image_html = $this->get_product_image($image_name, $distributor_id, $product_data, $class);
        $image_url = $this->format_product_image_url($image_name, $distributor_id, $product_data);
        
        wp_send_json_success(array(
            'html' => $image_html,
            'url' => $image_url
        ));
    }

    /**
     * Shortcode for distributor logo
     * Usage: [fflbro_distributor_logo id="lipseys" class="custom-class"]
     */
    public function distributor_logo_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'class' => 'distributor-logo'
        ), $atts);
        
        if (empty($atts['id'])) {
            return '';
        }
        
        return $this->get_distributor_logo($atts['id'], $atts['class']);
    }

    /**
     * Shortcode for product image
     * Usage: [fflbro_product_image name="image.jpg" distributor="lipseys"]
     */
    public function product_image_shortcode($atts) {
        $atts = shortcode_atts(array(
            'name' => '',
            'distributor' => '',
            'class' => 'product-image'
        ), $atts);
        
        if (empty($atts['name']) || empty($atts['distributor'])) {
            return '';
        }
        
        return $this->get_product_image($atts['name'], $atts['distributor'], array(), $atts['class']);
    }

    /**
     * Get all distributor data
     */
    public function get_all_distributors() {
        return $this->distributor_logos;
    }

    /**
     * Get distributor data by ID
     */
    public function get_distributor_data($distributor_id) {
        return isset($this->distributor_logos[$distributor_id]) ? $this->distributor_logos[$distributor_id] : null;
    }
}

// Initialize the plugin
$GLOBALS['fflbro_distributor_images'] = new FFLBRO_Distributor_Images();

/**
 * Helper functions for use in themes and plugins
 */
function fflbro_get_distributor_logo($distributor_id, $class = 'distributor-logo') {
    global $fflbro_distributor_images;
    return $fflbro_distributor_images->get_distributor_logo($distributor_id, $class);
}

function fflbro_get_product_image($image_name, $distributor_id, $product_data = array(), $class = 'product-image') {
    global $fflbro_distributor_images;
    return $fflbro_distributor_images->get_product_image($image_name, $distributor_id, $product_data, $class);
}

function fflbro_get_product_image_url($image_name, $distributor_id, $product_data = array()) {
    global $fflbro_distributor_images;
    return $fflbro_distributor_images->format_product_image_url($image_name, $distributor_id, $product_data);
}
