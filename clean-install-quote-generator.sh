#!/bin/bash
# Visual Quote Generator - CLEAN INSTALL Script
# Removes ALL old versions first, then installs fresh

set -e  # Exit on error

PLUGIN_DIR="/opt/fflbro/wordpress-main/wp-content/plugins/ffl-bro-enhanced-pro"
BLUE='\033[0;34m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${RED}════════════════════════════════════════════════════════════${NC}"
echo -e "${RED}   CLEAN INSTALL - Removing ALL old quote generator files${NC}"
echo -e "${RED}════════════════════════════════════════════════════════════${NC}"
echo ""

# Check if running from correct directory
if [ ! -f "ffl-bro-enhanced-pro.php" ]; then
    echo -e "${RED}Error: Must run from plugin directory${NC}"
    echo "Run: cd $PLUGIN_DIR"
    exit 1
fi

echo -e "${YELLOW}Step 1: Backing up and removing OLD files${NC}"
mkdir -p backups/removed_$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backups/removed_$(date +%Y%m%d_%H%M%S)"

# Remove old JSX component
if [ -f "components/visual-quote-generator.jsx" ]; then
    sudo mv components/visual-quote-generator.jsx "$BACKUP_DIR/"
    echo -e "${GREEN}✓ Removed old .jsx component${NC}"
fi

# Remove old admin page (we'll create fresh)
if [ -f "includes/admin-visual-quote.php" ]; then
    sudo mv includes/admin-visual-quote.php "$BACKUP_DIR/"
    echo -e "${GREEN}✓ Removed old admin page${NC}"
fi

# Remove any old .js versions
if [ -f "assets/js/visual-quote-generator.js" ]; then
    sudo mv assets/js/visual-quote-generator.js "$BACKUP_DIR/"
    echo -e "${GREEN}✓ Removed old .js version${NC}"
fi

# Remove old component directory references
if [ -d "components" ]; then
    if [ -f "components/visual-quote-generator.js" ]; then
        sudo mv components/visual-quote-generator.js "$BACKUP_DIR/"
        echo -e "${GREEN}✓ Removed old component .js${NC}"
    fi
fi

echo -e "${GREEN}✓ All old files backed up to: $BACKUP_DIR${NC}"
echo ""

echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}   Installing FRESH Visual Quote Generator${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo ""

echo -e "${YELLOW}Step 2: Database Diagnostics${NC}"
echo "Checking distributor tables and product data..."

cat > /tmp/ffl_diagnostics.sql << 'SQLEOF'
-- Check for custom distributor tables
SELECT 'Custom Tables Found:' as info;
SELECT TABLE_NAME 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE()
  AND (TABLE_NAME LIKE '%lipsey%' 
    OR TABLE_NAME LIKE '%rsr%' 
    OR TABLE_NAME LIKE '%davidson%'
    OR TABLE_NAME LIKE '%ffl%distributor%')
ORDER BY TABLE_NAME;

-- Check wp_fflbro_products table
SELECT '' as info;
SELECT 'FFL Bro Products Table:' as info;
SELECT COUNT(*) as total_products FROM wp_fflbro_products WHERE 1=1;

-- Check columns
SELECT '' as info;
SELECT 'Available Columns:' as info;
SELECT COLUMN_NAME 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'wp_fflbro_products'
  AND COLUMN_NAME REGEXP 'lipsey|rsr|davidson|item|sku';

-- Products by distributor
SELECT '' as info;
SELECT 'Products by Distributor:' as info;
SELECT 
  CASE 
    WHEN lipseys_item_no IS NOT NULL THEN 'Lipseys'
    WHEN rsr_stock_no IS NOT NULL THEN 'RSR'
    WHEN davidsons_item_no IS NOT NULL THEN 'Davidsons'
    ELSE 'Unknown'
  END as distributor,
  COUNT(*) as count
FROM wp_fflbro_products
GROUP BY distributor;

-- Sample products
SELECT '' as info;
SELECT 'Sample Products (first 3):' as info;
SELECT id, 
       LEFT(item_name, 40) as name, 
       manufacturer,
       lipseys_item_no as lipsey_sku, 
       rsr_stock_no as rsr_sku, 
       davidsons_item_no as david_sku,
       retail_price, 
       quantity
FROM wp_fflbro_products 
WHERE lipseys_item_no IS NOT NULL OR rsr_stock_no IS NOT NULL OR davidsons_item_no IS NOT NULL
LIMIT 3;
SQLEOF

mysql wordpress < /tmp/ffl_diagnostics.sql 2>&1
echo ""
echo -e "${GREEN}✓ Database diagnostics complete${NC}"
echo ""

echo -e "${YELLOW}Step 3: Creating NEW React Component (.js file)${NC}"
mkdir -p assets/js
sudo tee assets/js/visual-quote-generator.js > /dev/null << 'JSEOF'
/**
 * Visual Quote Generator - Multi-Distributor
 * Pure React.createElement (no JSX) - works directly in browser
 */
(function() {
    const { useState } = React;
    const e = React.createElement;
    
    // Icons
    const SearchIcon = () => e('svg', {
        style: { width: '20px', height: '20px' },
        viewBox: '0 0 24 24',
        fill: 'none',
        stroke: 'currentColor',
        strokeWidth: 2
    }, 
        e('circle', { cx: 11, cy: 11, r: 8 }),
        e('path', { d: 'M21 21l-4.35-4.35' })
    );
    
    const PlusIcon = () => e('svg', {
        style: { width: '20px', height: '20px' },
        viewBox: '0 0 24 24',
        fill: 'none',
        stroke: 'currentColor',
        strokeWidth: 2
    },
        e('line', { x1: 12, y1: 5, x2: 12, y2: 19 }),
        e('line', { x1: 5, y1: 12, x2: 19, y2: 12 })
    );
    
    const TrashIcon = () => e('svg', {
        style: { width: '20px', height: '20px' },
        viewBox: '0 0 24 24',
        fill: 'none',
        stroke: 'currentColor',
        strokeWidth: 2
    },
        e('polyline', { points: '3 6 5 6 21 6' }),
        e('path', { d: 'M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2' })
    );
    
    // Main Component
    const VisualQuoteGenerator = () => {
        const [searchTerm, setSearchTerm] = useState('');
        const [searchResults, setSearchResults] = useState([]);
        const [quoteItems, setQuoteItems] = useState([]);
        const [customerInfo, setCustomerInfo] = useState({ name: '', email: '', phone: '' });
        const [loading, setLoading] = useState(false);
        const [showSuccess, setShowSuccess] = useState(false);
        const [error, setError] = useState('');
        
        const handleSearch = () => {
            if (searchTerm.length < 2) {
                setError('Enter at least 2 characters');
                return;
            }
            
            setLoading(true);
            setError('');
            
            fetch(window.fflbroQuote.ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'fflbro_search_products',
                    nonce: window.fflbroQuote.nonce,
                    search: searchTerm
                })
            })
            .then(res => res.json())
            .then(data => {
                setLoading(false);
                if (data.success) {
                    setSearchResults(data.data.products || []);
                    if (data.data.products.length === 0) {
                        setError('No products found for "' + searchTerm + '"');
                    }
                } else {
                    setError(data.data?.message || 'Search failed');
                }
            })
            .catch(err => {
                setLoading(false);
                setError('Error: ' + err.message);
            });
        };
        
        const addToQuote = (product) => {
            setQuoteItems([...quoteItems, { ...product, qty: 1 }]);
            setSearchResults([]);
            setSearchTerm('');
            setError('');
        };
        
        const removeFromQuote = (index) => {
            setQuoteItems(quoteItems.filter((_, i) => i !== index));
        };
        
        const updateQuantity = (index, qty) => {
            const newItems = [...quoteItems];
            newItems[index].qty = Math.max(1, parseInt(qty) || 1);
            setQuoteItems(newItems);
        };
        
        const calculateTotal = () => {
            return quoteItems.reduce((sum, item) => sum + (item.price * item.qty), 0);
        };
        
        const submitQuote = () => {
            if (quoteItems.length === 0) {
                setError('Add items to quote first');
                return;
            }
            if (!customerInfo.email) {
                setError('Customer email required');
                return;
            }
            
            setLoading(true);
            setError('');
            
            fetch(window.fflbroQuote.ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'fflbro_submit_quote',
                    nonce: window.fflbroQuote.nonce,
                    customer: JSON.stringify(customerInfo),
                    items: JSON.stringify(quoteItems)
                })
            })
            .then(res => res.json())
            .then(data => {
                setLoading(false);
                if (data.success) {
                    setShowSuccess(true);
                    setTimeout(() => {
                        setShowSuccess(false);
                        setQuoteItems([]);
                        setCustomerInfo({ name: '', email: '', phone: '' });
                    }, 3000);
                } else {
                    setError(data.data?.message || 'Failed to submit');
                }
            })
            .catch(err => {
                setLoading(false);
                setError('Error: ' + err.message);
            });
        };
        
        // Main render
        return e('div', { style: { fontFamily: 'system-ui, -apple-system, sans-serif', maxWidth: '1400px', margin: '0 auto', padding: '20px' } },
            
            // Inline styles
            e('style', null, `
                .fflbro-search-section { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 30px; }
                .fflbro-search-box { display: flex; gap: 10px; margin-bottom: 20px; }
                .fflbro-search-input { flex: 1; padding: 12px; border: 2px solid #ddd; border-radius: 4px; font-size: 16px; }
                .fflbro-btn { padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: 500; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
                .fflbro-btn-primary { background: #2271b1; color: white; }
                .fflbro-btn-primary:hover { background: #135e96; }
                .fflbro-btn-primary:disabled { background: #ccc; cursor: not-allowed; }
                .fflbro-btn-secondary { background: #f0f0f1; color: #2c3338; }
                .fflbro-btn-secondary:hover { background: #dcdcde; }
                .fflbro-btn-danger { background: #dc3232; color: white; padding: 8px 12px; }
                .fflbro-btn-danger:hover { background: #b32d2e; }
                .fflbro-error { background: #fcf0f1; color: #d63638; padding: 12px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #d63638; }
                .fflbro-search-results { background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 400px; overflow-y: auto; }
                .fflbro-result-item { padding: 15px; border-bottom: 1px solid #f0f0f1; display: flex; justify-content: space-between; align-items: center; }
                .fflbro-result-item:hover { background: #f6f7f7; }
                .fflbro-quote-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
                .fflbro-quote-items { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
                .fflbro-quote-item { display: grid; grid-template-columns: 2fr 100px 100px 50px; gap: 15px; align-items: center; padding: 15px; border-bottom: 1px solid #f0f0f1; }
                .fflbro-customer-form { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
                .fflbro-form-group { margin-bottom: 20px; }
                .fflbro-form-label { display: block; margin-bottom: 5px; font-weight: 500; }
                .fflbro-form-input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
                .fflbro-qty-input { width: 80px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
                .fflbro-total-section { margin-top: 20px; padding-top: 20px; border-top: 2px solid #ddd; text-align: right; }
                .fflbro-total-amount { font-size: 24px; font-weight: bold; color: #2271b1; }
                .fflbro-success-modal { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100000; }
                .fflbro-success-content { background: white; padding: 40px; border-radius: 8px; text-align: center; max-width: 400px; }
            `),
            
            // Search Section
            e('div', { className: 'fflbro-search-section' },
                e('h2', { style: { marginTop: 0 } }, 'Search Products Across All Distributors'),
                e('div', { className: 'fflbro-search-box' },
                    e('input', {
                        className: 'fflbro-search-input',
                        type: 'text',
                        placeholder: 'Search by name, SKU, UPC, manufacturer...',
                        value: searchTerm,
                        onChange: (ev) => setSearchTerm(ev.target.value),
                        onKeyPress: (ev) => ev.key === 'Enter' && handleSearch()
                    }),
                    e('button', { 
                        className: 'fflbro-btn fflbro-btn-primary', 
                        onClick: handleSearch, 
                        disabled: loading 
                    },
                        e(SearchIcon),
                        loading ? 'Searching...' : 'Search'
                    )
                ),
                error && e('div', { className: 'fflbro-error' }, error),
                searchResults.length > 0 && e('div', { className: 'fflbro-search-results' },
                    searchResults.map((product, idx) => e('div', { key: idx, className: 'fflbro-result-item' },
                        e('div', null,
                            e('div', { style: { fontWeight: 'bold', marginBottom: '5px' } }, product.name),
                            e('div', { style: { color: '#666', fontSize: '14px' } },
                                product.manufacturer || 'Unknown',
                                ' - ',
                                product.sku || 'No SKU',
                                product.distributor && e('span', { style: { color: '#2271b1', marginLeft: '10px' } }, `(${product.distributor})`)
                            )
                        ),
                        e('div', { style: { display: 'flex', alignItems: 'center', gap: '15px' } },
                            e('div', { style: { fontWeight: 'bold', fontSize: '18px' } }, 
                                '$' + parseFloat(product.price || 0).toFixed(2)
                            ),
                            e('button', { 
                                className: 'fflbro-btn fflbro-btn-secondary', 
                                onClick: () => addToQuote(product) 
                            },
                                e(PlusIcon),
                                'Add'
                            )
                        )
                    ))
                )
            ),
            
            // Quote Section
            e('div', { className: 'fflbro-quote-grid' },
                // Quote Items
                e('div', { className: 'fflbro-quote-items' },
                    e('h2', { style: { marginTop: 0 } }, `Quote Items (${quoteItems.length})`),
                    quoteItems.length === 0 ? 
                        e('div', { style: { color: '#666', textAlign: 'center', padding: '60px 20px' } },
                            e('p', { style: { fontSize: '16px', margin: 0 } }, 'No items in quote yet'),
                            e('p', { style: { fontSize: '14px', marginTop: '10px' } }, 'Search and add products above')
                        ) :
                        e('div', null,
                            quoteItems.map((item, idx) => e('div', { key: idx, className: 'fflbro-quote-item' },
                                e('div', null,
                                    e('div', { style: { fontWeight: 'bold', marginBottom: '3px' } }, item.name),
                                    e('div', { style: { color: '#666', fontSize: '13px' } }, 
                                        item.sku,
                                        item.distributor && ` • ${item.distributor}`
                                    )
                                ),
                                e('input', {
                                    className: 'fflbro-qty-input',
                                    type: 'number',
                                    min: 1,
                                    value: item.qty,
                                    onChange: (ev) => updateQuantity(idx, ev.target.value)
                                }),
                                e('div', { style: { fontWeight: 'bold', textAlign: 'right' } }, 
                                    '$' + (item.price * item.qty).toFixed(2)
                                ),
                                e('button', { 
                                    className: 'fflbro-btn-danger', 
                                    onClick: () => removeFromQuote(idx),
                                    title: 'Remove'
                                },
                                    e(TrashIcon)
                                )
                            )),
                            e('div', { className: 'fflbro-total-section' },
                                e('div', { style: { fontSize: '16px', marginBottom: '10px', color: '#666' } }, 'Total:'),
                                e('div', { className: 'fflbro-total-amount' }, '$' + calculateTotal().toFixed(2))
                            )
                        )
                ),
                
                // Customer Form
                e('div', { className: 'fflbro-customer-form' },
                    e('h2', { style: { marginTop: 0 } }, 'Customer Information'),
                    e('div', { className: 'fflbro-form-group' },
                        e('label', { className: 'fflbro-form-label' }, 'Name'),
                        e('input', {
                            className: 'fflbro-form-input',
                            type: 'text',
                            value: customerInfo.name,
                            onChange: (ev) => setCustomerInfo({ ...customerInfo, name: ev.target.value })
                        })
                    ),
                    e('div', { className: 'fflbro-form-group' },
                        e('label', { className: 'fflbro-form-label' }, 'Email *'),
                        e('input', {
                            className: 'fflbro-form-input',
                            type: 'email',
                            required: true,
                            value: customerInfo.email,
                            onChange: (ev) => setCustomerInfo({ ...customerInfo, email: ev.target.value })
                        })
                    ),
                    e('div', { className: 'fflbro-form-group' },
                        e('label', { className: 'fflbro-form-label' }, 'Phone'),
                        e('input', {
                            className: 'fflbro-form-input',
                            type: 'tel',
                            value: customerInfo.phone,
                            onChange: (ev) => setCustomerInfo({ ...customerInfo, phone: ev.target.value })
                        })
                    ),
                    e('button', {
                        className: 'fflbro-btn fflbro-btn-primary',
                        style: { width: '100%', marginTop: '20px', justifyContent: 'center' },
                        onClick: submitQuote,
                        disabled: loading || quoteItems.length === 0
                    }, loading ? 'Submitting...' : 'Generate Quote & Send Email')
                )
            ),
            
            // Success Modal
            showSuccess && e('div', { className: 'fflbro-success-modal', onClick: () => setShowSuccess(false) },
                e('div', { className: 'fflbro-success-content' },
                    e('div', { style: { fontSize: '64px', marginBottom: '20px' } }, '✅'),
                    e('h2', { style: { marginTop: 0 } }, 'Quote Submitted!'),
                    e('p', { style: { color: '#666' } }, `Email sent to: ${customerInfo.email}`)
                )
            )
        );
    };
    
    // Export to window
    window.VisualQuoteGenerator = VisualQuoteGenerator;
    console.log('✓ Visual Quote Generator component loaded');
})();
JSEOF

echo -e "${GREEN}✓ Created fresh React component${NC}"
echo ""

echo -e "${YELLOW}Step 4: Creating NEW Admin Page with AJAX${NC}"
sudo tee includes/admin-visual-quote.php > /dev/null << 'PHPEOF'
<?php
/**
 * Visual Quote Generator - Admin Page
 * FRESH INSTALL - No old code
 */

if (!defined('ABSPATH')) exit;

// Add menu item
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
    
    <!-- Load React from CDN -->
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    
    <!-- Pass WordPress data to JavaScript -->
    <script>
        window.fflbroQuote = {
            nonce: '<?php echo wp_create_nonce('fflbro_quote_nonce'); ?>',
            ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>'
        };
    </script>
    
    <!-- Load our component -->
    <script src="<?php echo plugins_url('assets/js/visual-quote-generator.js', dirname(__FILE__)); ?>?v=<?php echo time(); ?>"></script>
    
    <!-- Initialize React app -->
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                if (window.React && window.ReactDOM && window.VisualQuoteGenerator) {
                    const root = ReactDOM.createRoot(document.getElementById('fflbro-visual-quote-root'));
                    root.render(React.createElement(window.VisualQuoteGenerator));
                    console.log('✅ Quote Generator initialized successfully');
                } else {
                    console.error('❌ Failed to load:', {
                        React: !!window.React,
                        ReactDOM: !!window.ReactDOM,
                        Component: !!window.VisualQuoteGenerator
                    });
                }
            }, 200);
        });
    </script>
    <?php
}

// AJAX: Search products
add_action('wp_ajax_fflbro_search_products', 'fflbro_search_products_handler');

function fflbro_search_products_handler() {
    check_ajax_referer('fflbro_quote_nonce', 'nonce');
    
    global $wpdb;
    $search = sanitize_text_field($_POST['search']);
    
    if (strlen($search) < 2) {
        wp_send_json_error(['message' => 'Search term too short']);
    }
    
    // Search wp_fflbro_products table across all distributors
    $sql = $wpdb->prepare("
        SELECT 
            id,
            item_name as name,
            manufacturer,
            COALESCE(lipseys_item_no, rsr_stock_no, davidsons_item_no, '') as sku,
            CASE 
                WHEN lipseys_item_no IS NOT NULL AND lipseys_item_no != '' THEN 'Lipseys'
                WHEN rsr_stock_no IS NOT NULL AND rsr_stock_no != '' THEN 'RSR'
                WHEN davidsons_item_no IS NOT NULL AND davidsons_item_no != '' THEN 'Davidsons'
                ELSE NULL
            END as distributor,
            COALESCE(retail_price, wholesale_cost, 0) as price,
            wholesale_cost,
            quantity,
            upc_code
        FROM {$wpdb->prefix}fflbro_products
        WHERE (
            item_name LIKE %s
            OR manufacturer LIKE %s
            OR lipseys_item_no LIKE %s
            OR rsr_stock_no LIKE %s
            OR davidsons_item_no LIKE %s
            OR upc_code LIKE %s
        )
        ORDER BY 
            CASE 
                WHEN item_name LIKE %s THEN 1
                WHEN manufacturer LIKE %s THEN 2
                ELSE 3
            END,
            item_name
        LIMIT 50
    ", 
        "%{$search}%", "%{$search}%", "%{$search}%", 
        "%{$search}%", "%{$search}%", "%{$search}%",
        "{$search}%", "{$search}%"
    );
    
    $products = $wpdb->get_results($sql);
    
    // Format results
    $formatted = array_map(function($p) {
        return [
            'id' => (int)$p->id,
            'name' => $p->name,
            'manufacturer' => $p->manufacturer ?: 'Unknown',
            'sku' => $p->sku ?: 'No SKU',
            'distributor' => $p->distributor,
            'price' => round(floatval($p->price), 2),
            'quantity' => (int)($p->quantity ?: 0)
        ];
    }, $products);
    
    wp_send_json_success([
        'products' => $formatted,
        'count' => count($formatted),
        'search_term' => $search
    ]);
}

// AJAX: Submit quote
add_action('wp_ajax_fflbro_submit_quote', 'fflbro_submit_quote_handler');

function fflbro_submit_quote_handler() {
    check_ajax_referer('fflbro_quote_nonce', 'nonce');
    
    $customer = json_decode(stripslashes($_POST['customer']), true);
    $items = json_decode(stripslashes($_POST['items']), true);
    
    if (empty($customer['email']) || empty($items)) {
        wp_send_json_error(['message' => 'Missing customer email or items']);
    }
    
    // Calculate total
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['qty'];
    }
    
    // Build email
    $to = sanitize_email($customer['email']);
    $subject = 'Your FFL Firearm Quote - ' . date('Y-m-d');
    
    $message = "Thank you for your quote request!\n\n";
    $message .= "CUSTOMER INFORMATION:\n";
    $message .= "Name: " . sanitize_text_field($customer['name']) . "\n";
    $message .= "Email: " . $to . "\n";
    $message .= "Phone: " . sanitize_text_field($customer['phone']) . "\n\n";
    $message .= "QUOTE ITEMS:\n";
    $message .= str_repeat("-", 70) . "\n";
    
    foreach ($items as $item) {
        $line_total = $item['price'] * $item['qty'];
        $message .= sprintf(
            "%-40s Qty: %2d  @ $%7.2f = $%8.2f\n",
            substr($item['name'], 0, 40),
            $item['qty'],
            $item['price'],
            $line_total
        );
        if (!empty($item['sku'])) {
            $message .= "  SKU: " . $item['sku'];
            if (!empty($item['distributor'])) {
                $message .= " (" . $item['distributor'] . ")";
            }
            $message .= "\n";
        }
        $message .= "\n";
    }
    
    $message .= str_repeat("-", 70) . "\n";
    $message .= sprintf("TOTAL: $%0.2f\n\n", $total);
    $message .= "This quote is valid for 30 days.\n";
    $message .= "Please contact us to complete your order.\n";
    
    // Send email
    $headers = ['Content-Type: text/plain; charset=UTF-8'];
    $sent = wp_mail($to, $subject, $message, $headers);
    
    // Also email to admin
    $admin_email = get_option('admin_email');
    wp_mail($admin_email, 'New Quote Request: ' . $customer['name'], $message, $headers);
    
    if ($sent) {
        wp_send_json_success(['message' => 'Quote sent successfully']);
    } else {
        wp_send_json_error(['message' => 'Failed to send email']);
    }
}
PHPEOF

echo -e "${GREEN}✓ Created fresh admin page with AJAX handlers${NC}"
echo ""

echo -e "${YELLOW}Step 5: Update main plugin file${NC}"

# Remove old includes
sudo sed -i '/visual-quote/d' ffl-bro-enhanced-pro.php

# Add new include
if ! grep -q "includes/admin-visual-quote.php" ffl-bro-enhanced-pro.php; then
    # Find a good place to add it (after other includes)
    sudo sed -i "/require_once.*includes.*admin/a require_once plugin_dir_path(__FILE__) . 'includes/admin-visual-quote.php';" ffl-bro-enhanced-pro.php
    echo -e "${GREEN}✓ Added include to main plugin file${NC}"
else
    echo -e "${GREEN}✓ Already included${NC}"
fi
echo ""

echo -e "${YELLOW}Step 6: Set permissions${NC}"
sudo chown -R www-data:www-data assets includes
sudo chmod -R 755 assets includes
sudo chmod 644 assets/js/visual-quote-generator.js includes/admin-visual-quote.php
echo -e "${GREEN}✓ Permissions set${NC}"
echo ""

echo -e "${YELLOW}Step 7: Quick database test${NC}"
echo "Sample query result:"
mysql wordpress -e "
SELECT COUNT(*) as total_products,
       SUM(CASE WHEN lipseys_item_no IS NOT NULL THEN 1 ELSE 0 END) as lipseys,
       SUM(CASE WHEN rsr_stock_no IS NOT NULL THEN 1 ELSE 0 END) as rsr,
       SUM(CASE WHEN davidsons_item_no IS NOT NULL THEN 1 ELSE 0 END) as davidsons
FROM wp_fflbro_products;" 2>&1
echo ""

echo -e "${GREEN}════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}         ✅ CLEAN INSTALLATION COMPLETE!${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}🎯 Next Step:${NC}"
echo ""
echo "   Open in browser:"
echo "   ${YELLOW}http://192.168.2.161:8181/wp-admin/admin.php?page=fflbro-visual-quotes${NC}"
echo ""
echo -e "${BLUE}What to check:${NC}"
echo "   1. Page loads without errors"
echo "   2. Open browser console (F12) - should see: ✅ Quote Generator initialized"
echo "   3. Try searching for 'glock' or 'smith' or any product"
echo "   4. Add items to quote"
echo "   5. Fill customer info and generate quote"
echo ""
echo -e "${BLUE}Files installed:${NC}"
echo "   📄 assets/js/visual-quote-generator.js  (NEW React component)"
echo "   📄 includes/admin-visual-quote.php      (NEW admin page + AJAX)"
echo ""
echo -e "${BLUE}Old files backed up to:${NC}"
echo "   📁 $BACKUP_DIR"
echo ""
echo -e "${YELLOW}Troubleshooting:${NC}"
echo "   If no products load, check: mysql wordpress -e 'SELECT COUNT(*) FROM wp_fflbro_products;'"
echo "   View browser console (F12) for any JavaScript errors"
echo ""
rm -f /tmp/ffl_diagnostics.sql
