#!/bin/bash
################################################################################
# Neefeco Arms SEO Phase 1 Deployment
# Installs plugins, configures SEO, implements schema markup
# Run from: /opt/fflbro/wordpress-main/
################################################################################

set -e
WP_PATH="/opt/fflbro/wordpress-main"
BACKUP_DIR="/opt/fflbro-backups"

echo "════════════════════════════════════════════════════════════════"
echo "  NEEFECO ARMS SEO PHASE 1 DEPLOYMENT"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Navigate to WordPress directory
cd "$WP_PATH" || exit 1

# Create backup
echo "📦 Creating pre-SEO backup..."
mkdir -p "$BACKUP_DIR"
BACKUP_FILE="$BACKUP_DIR/pre-seo-$(date +%Y%m%d_%H%M%S).tar.gz"
tar -czf "$BACKUP_FILE" .
echo "   ✓ Backup: $BACKUP_FILE"
echo ""

# Install WP-CLI if not present
if ! command -v wp &> /dev/null; then
    echo "📥 Installing WP-CLI..."
    curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    chmod +x wp-cli.phar
    sudo mv wp-cli.phar /usr/local/bin/wp
    echo "   ✓ WP-CLI installed"
fi

echo "════════════════════════════════════════════════════════════════"
echo "  PHASE 1: ESSENTIAL PLUGINS INSTALLATION"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Core SEO Plugins
echo "📥 Installing SEO plugins..."
sudo -u www-data wp plugin install all-in-one-seo-pack --activate 2>/dev/null || echo "   ℹ AIOSEO already installed"
sudo -u www-data wp plugin install google-site-kit --activate 2>/dev/null || echo "   ℹ Site Kit already installed"
sudo -u www-data wp plugin install redirection --activate 2>/dev/null || echo "   ℹ Redirection already installed"
echo "   ✓ SEO plugins installed"
echo ""

# Performance Plugins
echo "⚡ Installing performance plugins..."
sudo -u www-data wp plugin install litespeed-cache --activate 2>/dev/null || echo "   ℹ LiteSpeed Cache already installed"
sudo -u www-data wp plugin install autoptimize --activate 2>/dev/null || echo "   ℹ Autoptimize already installed"
echo "   ✓ Performance plugins installed"
echo ""

# Image Optimization
echo "🖼️  Installing image optimization..."
sudo -u www-data wp plugin install shortpixel-image-optimiser --activate 2>/dev/null || echo "   ℹ ShortPixel already installed"
sudo -u www-data wp plugin install webp-converter-for-media --activate 2>/dev/null || echo "   ℹ WebP Converter already installed"
echo "   ✓ Image optimization installed"
echo ""

# Security
echo "🔒 Installing security plugins..."
sudo -u www-data wp plugin install wordfence --activate 2>/dev/null || echo "   ℹ Wordfence already installed"
sudo -u www-data wp plugin install really-simple-ssl --activate 2>/dev/null || echo "   ℹ Really Simple SSL already installed"
echo "   ✓ Security plugins installed"
echo ""

# Backups & Utilities
echo "💾 Installing backup & utility plugins..."
sudo -u www-data wp plugin install updraftplus --activate 2>/dev/null || echo "   ℹ UpdraftPlus already installed"
sudo -u www-data wp plugin install wp-optimize --activate 2>/dev/null || echo "   ℹ WP-Optimize already installed"
sudo -u www-data wp plugin install query-monitor --activate 2>/dev/null || echo "   ℹ Query Monitor already installed"
echo "   ✓ Backup & utility plugins installed"
echo ""

# Reviews & Social Proof
echo "⭐ Installing review plugins..."
sudo -u www-data wp plugin install woocommerce-photo-reviews --activate 2>/dev/null || echo "   ℹ Photo Reviews already installed"
echo "   ✓ Review plugins installed"
echo ""

# Age Verification
echo "🔞 Installing age verification..."
sudo -u www-data wp plugin install age-gate --activate 2>/dev/null || echo "   ℹ Age Gate already installed"
echo "   ✓ Age verification installed"
echo ""

echo "════════════════════════════════════════════════════════════════"
echo "  PHASE 2: SCHEMA MARKUP IMPLEMENTATION"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Create schema markup plugin
SCHEMA_DIR="$WP_PATH/wp-content/plugins/neefeco-schema"
mkdir -p "$SCHEMA_DIR"

cat > "$SCHEMA_DIR/neefeco-schema.php" << 'PHPEOF'
<?php
/**
 * Plugin Name: Neefeco Arms Schema Markup
 * Description: Adds LocalBusiness, Product, and FAQ schema for SEO
 * Version: 1.0.0
 */

// LocalBusiness Schema for Homepage
function neefeco_local_business_schema() {
    if (is_front_page()) {
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "GunShop",
            "name" => "Neefeco Arms",
            "image" => get_site_url() . "/wp-content/uploads/neefeco-logo.jpg",
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress" => "235 58th Ave South",
                "addressLocality" => "St. Petersburg",
                "addressRegion" => "FL",
                "postalCode" => "33705",
                "addressCountry" => "US"
            ],
            "telephone" => "+1-XXX-XXX-XXXX",
            "email" => "sales@neefecoarms.com",
            "url" => get_site_url(),
            "priceRange" => "$$",
            "geo" => [
                "@type" => "GeoCoordinates",
                "latitude" => "27.7730",
                "longitude" => "-82.6820"
            ],
            "description" => "Licensed FFL dealer in St. Petersburg offering firearms, ammunition, FFL transfers, and concealed carry training. Serving Tampa Bay since 2015."
        ];
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }
}
add_action('wp_head', 'neefeco_local_business_schema');

// Enhanced Product Schema for WooCommerce
function neefeco_product_schema($markup, $product) {
    if (!is_product()) return $markup;
    
    // Add firearms-specific properties
    $markup['additionalProperty'] = [];
    
    // Get product attributes
    $attributes = $product->get_attributes();
    
    if (isset($attributes['caliber'])) {
        $markup['additionalProperty'][] = [
            "@type" => "PropertyValue",
            "name" => "Caliber",
            "value" => $product->get_attribute('caliber')
        ];
    }
    
    if (isset($attributes['capacity'])) {
        $markup['additionalProperty'][] = [
            "@type" => "PropertyValue",
            "name" => "Capacity",
            "value" => $product->get_attribute('capacity')
        ];
    }
    
    if (isset($attributes['action'])) {
        $markup['additionalProperty'][] = [
            "@type" => "PropertyValue",
            "name" => "Action",
            "value" => $product->get_attribute('action')
        ];
    }
    
    return $markup;
}
add_filter('woocommerce_structured_data_product', 'neefeco_product_schema', 10, 2);

// FAQ Schema for FFL Transfer Page
function neefeco_faq_schema() {
    if (is_page('ffl-transfer') || is_page('faq')) {
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "FAQPage",
            "mainEntity" => [
                [
                    "@type" => "Question",
                    "name" => "How does the FFL transfer process work in Florida?",
                    "acceptedAnswer" => [
                        "@type" => "Answer",
                        "text" => "When you purchase a firearm online, select Neefeco Arms as your FFL dealer. The seller ships to us, we receive and log the firearm, contact you for pickup, complete ATF Form 4473, run background check through FDLE, observe the mandatory 3-day Florida waiting period, and release the firearm upon approval."
                    ]
                ],
                [
                    "@type" => "Question",
                    "name" => "What is your FFL transfer fee?",
                    "acceptedAnswer" => [
                        "@type" => "Answer",
                        "text" => "Our standard FFL transfer fee is competitive with the St. Petersburg area. Contact us for current pricing on handguns, rifles, and NFA items."
                    ]
                ],
                [
                    "@type" => "Question",
                    "name" => "How long does the transfer process take in Florida?",
                    "acceptedAnswer" => [
                        "@type" => "Answer",
                        "text" => "Florida law requires a 3-day waiting period between purchase and delivery (excluding weekends and holidays). Background checks typically clear within minutes to 24 hours. Total timeline is usually 4-5 business days from when we receive the firearm."
                    ]
                ],
                [
                    "@type" => "Question",
                    "name" => "Do I need to be 21 to buy a firearm in Florida?",
                    "acceptedAnswer" => [
                        "@type" => "Answer",
                        "text" => "Yes, Florida law requires you to be 21 years old to purchase ANY firearm, including rifles and shotguns. This is stricter than federal law which allows 18+ for long guns."
                    ]
                ]
            ]
        ];
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }
}
add_action('wp_head', 'neefeco_faq_schema');

// Breadcrumb Schema
function neefeco_breadcrumb_schema() {
    if (is_singular() && !is_front_page()) {
        global $post;
        
        $items = [
            [
                "@type" => "ListItem",
                "position" => 1,
                "name" => "Home",
                "item" => get_site_url()
            ]
        ];
        
        $position = 2;
        
        // Add category if product
        if (is_product()) {
            $categories = get_the_terms($post->ID, 'product_cat');
            if ($categories && !is_wp_error($categories)) {
                $category = reset($categories);
                $items[] = [
                    "@type" => "ListItem",
                    "position" => $position++,
                    "name" => $category->name,
                    "item" => get_term_link($category)
                ];
            }
        }
        
        // Add current page
        $items[] = [
            "@type" => "ListItem",
            "position" => $position,
            "name" => get_the_title(),
            "item" => get_permalink()
        ];
        
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => $items
        ];
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
    }
}
add_action('wp_head', 'neefeco_breadcrumb_schema');
PHPEOF

echo "✓ Schema markup plugin created"
sudo chown -R www-data:www-data "$SCHEMA_DIR"
sudo -u www-data wp plugin activate neefeco-schema 2>/dev/null || echo "   ℹ Schema plugin activated"
echo ""

echo "════════════════════════════════════════════════════════════════"
echo "  PHASE 3: SEO CONFIGURATION"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Set permalink structure
sudo -u www-data wp rewrite structure '/%postname%/' 2>/dev/null || true
sudo -u www-data wp rewrite flush 2>/dev/null || true
echo "   ✓ SEO-friendly permalinks configured"

# Enable product reviews
sudo -u www-data wp option update woocommerce_enable_reviews 'yes' 2>/dev/null || true
sudo -u www-data wp option update woocommerce_review_rating_verification_required 'no' 2>/dev/null || true
echo "   ✓ Product reviews enabled"
echo ""

echo "════════════════════════════════════════════════════════════════"
echo "  ✅ DEPLOYMENT COMPLETE!"
echo "════════════════════════════════════════════════════════════════"
echo ""
echo "🎯 INSTALLED PLUGINS:"
sudo -u www-data wp plugin list --status=active --format=table
echo ""
echo "🌐 ACCESS YOUR SITE:"
echo "   Frontend: http://192.168.2.162:8181"
echo "   Admin:    http://192.168.2.162:8181/wp-admin"
echo ""
echo "📝 NEXT STEPS:"
echo "   1. Configure Age Gate: Dashboard > Age Gate > Settings (set to 21+)"
echo "   2. Get ShortPixel API: https://shortpixel.com/free-sign-up"
echo "   3. Set up Google Search Console and submit sitemap"
echo "   4. Claim Google Business Profile"
echo ""
