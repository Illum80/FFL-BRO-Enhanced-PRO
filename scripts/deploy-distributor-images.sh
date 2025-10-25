#!/bin/bash
#
# FFL-BRO Distributor Images & Logos Deployment Script
# Installs and configures the distributor images plugin
#
# Usage: sudo bash deploy-distributor-images.sh
#

set -e

echo "========================================="
echo "FFL-BRO Distributor Images Deployment"
echo "========================================="
echo ""

# Color codes
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PLUGIN_NAME="fflbro-distributor-images"
PLUGIN_DIR="/opt/fflbro/wordpress-main/wp-content/plugins/${PLUGIN_NAME}"
ASSETS_DIR="${PLUGIN_DIR}/assets"
CSS_DIR="${ASSETS_DIR}/css"
JS_DIR="${ASSETS_DIR}/js"

echo -e "${BLUE}Step 1: Creating plugin directory structure...${NC}"
sudo mkdir -p "${PLUGIN_DIR}"
sudo mkdir -p "${CSS_DIR}"
sudo mkdir -p "${JS_DIR}"
echo -e "${GREEN}✓ Directories created${NC}"
echo ""

echo -e "${BLUE}Step 2: Copying plugin files...${NC}"

# Main plugin file
if [ -f "fflbro-distributor-images.php" ]; then
    sudo cp fflbro-distributor-images.php "${PLUGIN_DIR}/"
    echo -e "${GREEN}✓ Main plugin file copied${NC}"
else
    echo -e "${YELLOW}! Warning: fflbro-distributor-images.php not found in current directory${NC}"
fi

# JavaScript file
if [ -f "image-loader.js" ]; then
    sudo cp image-loader.js "${JS_DIR}/"
    echo -e "${GREEN}✓ JavaScript file copied${NC}"
else
    echo -e "${YELLOW}! Warning: image-loader.js not found in current directory${NC}"
fi

# Create CSS file if it doesn't exist
cat > /tmp/distributor-images.css << 'EOF'
/* FFL-BRO Distributor Images Styles */

.distributor-logo {
    max-height: 40px;
    width: auto;
    object-fit: contain;
    transition: transform 0.2s ease;
}

.distributor-logo:hover {
    transform: scale(1.05);
}

.distributor-logo-card {
    max-height: 60px;
    width: auto;
    object-fit: contain;
    cursor: pointer;
}

.product-image {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.product-image:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.product-image-thumb {
    max-width: 80px;
    max-height: 80px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #e2e8f0;
}

.distributor-card {
    position: relative;
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.distributor-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.distributor-card-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.distributor-logo-container {
    min-width: 100px;
    min-height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.no-image-placeholder {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    border-radius: 8px;
    font-size: 14px;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    padding: 20px 0;
}

.product-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.product-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.product-card-image {
    width: 100%;
    height: 200px;
    object-fit: contain;
    background: #f8fafc;
    padding: 15px;
}

.product-card-content {
    padding: 15px;
}

.distributor-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    background: #f1f5f9;
    border-radius: 4px;
    font-size: 12px;
    color: #64748b;
}

.distributor-badge img {
    height: 16px;
    width: auto;
}

/* Quote Generator Styles */
.quote-product-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e2e8f0;
}

.quote-distributor-logo {
    height: 24px;
    width: auto;
}

/* Loading States */
.image-loading {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: .5;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .product-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .distributor-card {
        padding: 15px;
    }
    
    .product-card-image {
        height: 150px;
    }
}
EOF

sudo cp /tmp/distributor-images.css "${CSS_DIR}/"
echo -e "${GREEN}✓ CSS file created${NC}"
echo ""

echo -e "${BLUE}Step 3: Setting file permissions...${NC}"
sudo chown -R www-data:www-data "${PLUGIN_DIR}"
sudo chmod -R 755 "${PLUGIN_DIR}"
echo -e "${GREEN}✓ Permissions set${NC}"
echo ""

echo -e "${BLUE}Step 4: Activating plugin via WP-CLI...${NC}"
docker exec fflbro-main wp plugin activate ${PLUGIN_NAME} --allow-root 2>/dev/null || {
    echo -e "${YELLOW}! Note: Activate plugin manually in WordPress admin${NC}"
}
echo -e "${GREEN}✓ Plugin ready to activate${NC}"
echo ""

echo -e "${BLUE}Step 5: Creating helper functions file...${NC}"

cat > /tmp/fflbro-image-helpers.php << 'EOFHELPER'
<?php
/**
 * FFL-BRO Image Helper Functions
 * Include this file in your theme or plugin to use image functions
 */

if (!function_exists('fflbro_distributor_card')) {
    /**
     * Generate a complete distributor card with logo
     * 
     * @param string $distributor_id Distributor ID (lipseys, rsr, etc)
     * @param array $stats Additional stats to display
     * @return string HTML for distributor card
     */
    function fflbro_distributor_card($distributor_id, $stats = array()) {
        global $fflbro_distributor_images;
        
        $distributor = $fflbro_distributor_images->get_distributor_data($distributor_id);
        if (!$distributor) {
            return '';
        }
        
        $logo = fflbro_get_distributor_logo($distributor_id, 'distributor-logo-card');
        
        ob_start();
        ?>
        <div class="distributor-card" data-distributor-id="<?php echo esc_attr($distributor_id); ?>">
            <div class="distributor-card-header">
                <div class="distributor-logo-container">
                    <?php echo $logo; ?>
                </div>
                <div>
                    <h3><?php echo esc_html($distributor['name']); ?></h3>
                    <a href="<?php echo esc_url($distributor['website']); ?>" target="_blank" class="text-sm text-blue-600">
                        Visit Website →
                    </a>
                </div>
            </div>
            
            <?php if (!empty($stats)): ?>
            <div class="distributor-stats">
                <?php foreach ($stats as $label => $value): ?>
                    <div class="stat-item">
                        <span class="stat-label"><?php echo esc_html($label); ?>:</span>
                        <span class="stat-value"><?php echo esc_html($value); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('fflbro_product_card')) {
    /**
     * Generate a product card with image
     * 
     * @param array $product Product data
     * @param string $distributor_id Distributor ID
     * @return string HTML for product card
     */
    function fflbro_product_card($product, $distributor_id) {
        $image_name = isset($product['image']) ? $product['image'] : '';
        $product_name = isset($product['name']) ? $product['name'] : 'Product';
        $price = isset($product['price']) ? '$' . number_format($product['price'], 2) : '';
        $stock = isset($product['stock']) ? $product['stock'] : 'Unknown';
        
        $image = fflbro_get_product_image($image_name, $distributor_id, $product, 'product-card-image');
        
        ob_start();
        ?>
        <div class="product-card">
            <?php echo $image; ?>
            <div class="product-card-content">
                <h4><?php echo esc_html($product_name); ?></h4>
                <?php if ($price): ?>
                    <p class="price"><?php echo esc_html($price); ?></p>
                <?php endif; ?>
                <div class="distributor-badge">
                    <?php echo fflbro_get_distributor_logo($distributor_id, 'distributor-badge-logo'); ?>
                    <span>In Stock: <?php echo esc_html($stock); ?></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('fflbro_quote_product_row')) {
    /**
     * Generate a product row for quote table
     * 
     * @param array $product Product data
     * @param string $distributor_id Distributor ID
     * @return string HTML for product row
     */
    function fflbro_quote_product_row($product, $distributor_id) {
        $image = fflbro_get_product_image(
            $product['image'] ?? '', 
            $distributor_id, 
            $product, 
            'quote-product-image'
        );
        
        ob_start();
        ?>
        <tr class="quote-product-row">
            <td><?php echo $image; ?></td>
            <td><?php echo esc_html($product['name'] ?? ''); ?></td>
            <td><?php echo fflbro_get_distributor_logo($distributor_id, 'quote-distributor-logo'); ?></td>
            <td><?php echo '$' . number_format($product['price'] ?? 0, 2); ?></td>
            <td><?php echo esc_html($product['quantity'] ?? 1); ?></td>
            <td><?php echo '$' . number_format(($product['price'] ?? 0) * ($product['quantity'] ?? 1), 2); ?></td>
        </tr>
        <?php
        return ob_get_clean();
    }
}
EOFHELPER

sudo cp /tmp/fflbro-image-helpers.php "${PLUGIN_DIR}/"
echo -e "${GREEN}✓ Helper functions created${NC}"
echo ""

echo "========================================="
echo -e "${GREEN}Installation Complete!${NC}"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Go to: http://192.168.2.161:8181/wp-admin/plugins.php"
echo "2. Activate 'FFL-BRO Distributor Images & Logos' plugin"
echo "3. View distributors at: http://192.168.2.161:8181/wp-admin/admin.php?page=fflbro-distributors"
echo ""
echo "Usage in your code:"
echo "  - fflbro_get_distributor_logo('lipseys')"
echo "  - fflbro_get_product_image('image.jpg', 'lipseys', \$product_data)"
echo "  - fflbro_distributor_card('lipseys', array('Products' => 16887))"
echo ""
echo "Plugin location: ${PLUGIN_DIR}"
echo "========================================="
