# FFL-BRO Distributor Images & Logos System

Complete WordPress plugin for managing distributor logos and product images across your FFL-BRO platform.

## 📦 What's Included

- **Main Plugin**: Handles all logo and image functionality
- **6 Distributor Integrations**: Lipseys, RSR, Davidson's, Sports South, Orion, Zanders
- **Lazy Loading**: Optimized image loading for performance
- **Fallback System**: Automatic placeholders for missing images
- **Helper Functions**: Easy-to-use functions for themes and plugins
- **Shortcodes**: WordPress shortcodes for content areas

## 🎯 Features

✅ **Real Distributor Logos**
- Lipseys (logo-main.png)
- RSR Group (rsr-logo.svg)
- Davidson's (davidsons-logo.png)
- Sports South (sports-south-logo.png)
- Orion Wholesale (orion-logo.png)
- Zanders (zanders-logo.png)

✅ **Product Image CDNs**
- Automatic URL formatting per distributor
- High-resolution image support
- Fallback placeholders
- Lazy loading support

✅ **WordPress Integration**
- AJAX endpoints for dynamic loading
- Shortcode support
- Helper functions
- WP-CLI activation

## 🚀 Quick Installation

### Method 1: Automated Script (Recommended)

```bash
# Navigate to script location
cd /path/to/files

# Run deployment script
sudo bash deploy-distributor-images.sh
```

### Method 2: Manual Installation

1. **Create plugin directory:**
```bash
sudo mkdir -p /opt/fflbro/wordpress-main/wp-content/plugins/fflbro-distributor-images
sudo mkdir -p /opt/fflbro/wordpress-main/wp-content/plugins/fflbro-distributor-images/assets/{css,js}
```

2. **Copy files:**
```bash
# Main plugin file
sudo cp fflbro-distributor-images.php /opt/fflbro/wordpress-main/wp-content/plugins/fflbro-distributor-images/

# JavaScript
sudo cp image-loader.js /opt/fflbro/wordpress-main/wp-content/plugins/fflbro-distributor-images/assets/js/

# CSS (auto-created by plugin)
```

3. **Set permissions:**
```bash
sudo chown -R www-data:www-data /opt/fflbro/wordpress-main/wp-content/plugins/fflbro-distributor-images
sudo chmod -R 755 /opt/fflbro/wordpress-main/wp-content/plugins/fflbro-distributor-images
```

4. **Activate plugin:**
- Go to: http://192.168.2.161:8181/wp-admin/plugins.php
- Find "FFL-BRO Distributor Images & Logos"
- Click "Activate"

## 📖 Usage Guide

### Basic Usage in PHP

```php
// Display distributor logo
echo fflbro_get_distributor_logo('lipseys');

// Display product image
echo fflbro_get_product_image('image.jpg', 'lipseys', array('name' => 'Product Name'));

// Get image URL only
$url = fflbro_get_product_image_url('image.jpg', 'lipseys');

// Display complete distributor card
echo fflbro_distributor_card('lipseys', array(
    'Products' => '16,887',
    'Last Sync' => '2 mins ago',
    'Status' => 'Connected'
));

// Display product card
echo fflbro_product_card(array(
    'name' => 'Glock 17 Gen 5',
    'image' => '00010.jpg',
    'price' => 485.99,
    'stock' => 25
), 'lipseys');
```

### Shortcodes

```
[fflbro_distributor_logo id="lipseys" class="my-custom-class"]

[fflbro_product_image name="image.jpg" distributor="lipseys" class="my-product-class"]
```

### JavaScript/AJAX

```javascript
// Load distributor logo dynamically
jQuery.ajax({
    url: fflbroImages.ajaxurl,
    type: 'POST',
    data: {
        action: 'fflbro_get_distributor_logo',
        nonce: fflbroImages.nonce,
        distributor_id: 'lipseys',
        class: 'distributor-logo'
    },
    success: function(response) {
        if (response.success) {
            jQuery('#logo-container').html(response.data.html);
        }
    }
});

// Load product image dynamically
jQuery.ajax({
    url: fflbroImages.ajaxurl,
    type: 'POST',
    data: {
        action: 'fflbro_get_product_image',
        nonce: fflbroImages.nonce,
        image_name: 'image.jpg',
        distributor_id: 'lipseys',
        product_data: {
            name: 'Product Name',
            rsr_stock_no: '12345'
        }
    },
    success: function(response) {
        if (response.success) {
            jQuery('#image-container').html(response.data.html);
        }
    }
});
```

## 🎨 Distributor Details

### Lipseys
- **Logo**: https://www.lipseys.com/images/logo-main.png
- **Image CDN**: https://www.lipseys.com/images/items/
- **Pattern**: `{imageName}` from API field
- **Example**: https://www.lipseys.com/images/items/00010.jpg

### RSR Group
- **Logo**: https://www.rsrgroup.com/images/rsr-logo.svg
- **Image CDN**: https://img.rsrgroup.com/highres-itemimages/
- **Pattern**: `{RSRStockNo}_1.jpg`
- **Example**: https://img.rsrgroup.com/highres-itemimages/12345_1.jpg

### Davidson's
- **Logo**: https://www.davidsonsinc.com/assets/images/davidsons-logo.png
- **Image CDN**: https://res.cloudinary.com/davidsons-inc/image/upload/
- **Pattern**: `c_pad,h_500,w_500/{ItemNo}.jpg`
- **Example**: https://res.cloudinary.com/davidsons-inc/image/upload/c_pad,h_500,w_500/12345.jpg

### Sports South
- **Logo**: https://www.sportssouth.com/assets/images/sports-south-logo.png
- **Image CDN**: https://images.sportssouth.com/
- **Pattern**: Direct image path
- **Example**: https://images.sportssouth.com/12345.jpg

### Orion Wholesale
- **Logo**: https://www.orionwholesale.com/images/orion-logo.png
- **Image CDN**: https://images.orionwholesale.com/
- **Pattern**: Direct image path
- **Example**: https://images.orionwholesale.com/12345.jpg

### Zanders
- **Logo**: https://www.zandersporting.com/images/zanders-logo.png
- **Image CDN**: https://www.zandersporting.com/images/products/
- **Pattern**: Image from itemimagelinks.csv
- **Example**: https://www.zandersporting.com/images/products/12345.jpg

## 🔧 Integration with Existing Modules

### Update Quote Generator

```php
// In your quote generator module
function generate_quote_html($products) {
    $html = '<table class="quote-table">';
    $html .= '<thead><tr><th>Image</th><th>Product</th><th>Distributor</th><th>Price</th></tr></thead>';
    $html .= '<tbody>';
    
    foreach ($products as $product) {
        $image = fflbro_get_product_image(
            $product['image_name'],
            $product['distributor'],
            $product,
            'quote-product-image'
        );
        
        $logo = fflbro_get_distributor_logo($product['distributor'], 'quote-distributor-logo');
        
        $html .= '<tr>';
        $html .= '<td>' . $image . '</td>';
        $html .= '<td>' . esc_html($product['name']) . '</td>';
        $html .= '<td>' . $logo . '</td>';
        $html .= '<td>$' . number_format($product['price'], 2) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    return $html;
}
```

### Update Distributor Dashboard

```php
// In modules/distributors-enhanced.php
function display_distributor_cards() {
    $distributors = array('lipseys', 'rsr', 'davidsons', 'sports_south', 'orion', 'zanders');
    
    foreach ($distributors as $dist_id) {
        echo fflbro_distributor_card($dist_id, array(
            'Status' => 'Connected',
            'Products' => get_distributor_product_count($dist_id),
            'Last Sync' => get_last_sync_time($dist_id)
        ));
    }
}
```

### Update Catalog Display

```php
// Display products in catalog
function display_product_catalog($products) {
    echo '<div class="product-grid">';
    
    foreach ($products as $product) {
        echo fflbro_product_card($product, $product['distributor']);
    }
    
    echo '</div>';
}
```

## 🎯 CSS Classes Reference

- `.distributor-logo` - Small logo (40px height)
- `.distributor-logo-card` - Card logo (60px height)
- `.product-image` - Full product image
- `.product-image-thumb` - Thumbnail (80x80px)
- `.distributor-card` - Complete distributor card
- `.product-card` - Product card with image
- `.no-image-placeholder` - Fallback placeholder
- `.quote-product-image` - Image in quote (80x80px)
- `.quote-distributor-logo` - Logo in quote (24px)

## 🐛 Troubleshooting

### Images Not Loading?

1. **Check plugin is activated:**
```bash
docker exec fflbro-main wp plugin list --allow-root
```

2. **Verify file permissions:**
```bash
ls -la /opt/fflbro/wordpress-main/wp-content/plugins/fflbro-distributor-images/
```

3. **Check error logs:**
```bash
tail -50 /opt/fflbro/wordpress-main/wp-content/debug.log
```

4. **Test AJAX endpoint:**
```bash
curl -X POST http://192.168.2.161:8181/wp-admin/admin-ajax.php \
  -d "action=fflbro_get_distributor_logo&distributor_id=lipseys&nonce=YOUR_NONCE"
```

### Logos Not Appearing?

1. **Hard refresh browser:** Ctrl + Shift + F5
2. **Clear WordPress cache:**
```bash
docker exec fflbro-main wp cache flush --allow-root
```

3. **Check CSS is loading:**
- Open browser dev tools (F12)
- Go to Network tab
- Look for `distributor-images.css`

### Product Images Showing Placeholder?

1. **Verify image name format** matches distributor pattern
2. **Check API response** includes image field
3. **Test image URL directly** in browser
4. **Verify CDN URLs** in plugin code match actual distributor CDNs

## 📝 Notes

- **Image caching**: Images are loaded with browser cache headers for performance
- **Lazy loading**: Images load as they scroll into view
- **Fallbacks**: Automatic placeholders for missing images
- **Responsive**: All images scale properly on mobile devices
- **Performance**: Optimized with lazy loading and compression

## 🔄 Updates

To update distributor logos or add new distributors, edit the `$distributor_logos` array in `fflbro-distributor-images.php`:

```php
'new_distributor' => array(
    'name' => 'New Distributor Name',
    'logo_url' => 'https://example.com/logo.png',
    'alt' => 'New Distributor Logo',
    'website' => 'https://example.com',
    'image_cdn' => 'https://cdn.example.com/images/'
)
```

## 📞 Support

For issues or questions:
1. Check this README first
2. Review the helper functions in `fflbro-image-helpers.php`
3. Consult WordPress error logs
4. Test with browser dev tools

---

**Version**: 1.0.0  
**Last Updated**: October 25, 2025  
**Compatibility**: WordPress 5.8+, PHP 7.4+
