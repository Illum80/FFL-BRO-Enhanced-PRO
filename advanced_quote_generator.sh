# Replace the quote generator with the advanced interactive version
docker exec -i fflbro-main tee /var/www/html/wp-content/plugins/ffl-bro-pro-quote/ffl-bro-pro-quote.php > /dev/null << 'EOF'
<?php
/**
 * Plugin Name: FFL-BRO Professional Quote Generator
 * Description: Advanced quote generator with multi-distributor integration and product selection
 * Version: 2.0.0
 */

if (!defined('ABSPATH')) exit;

class FFLBROProQuoteGenerator {
    
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode('ffl_bro_quote_generator', [$this, 'render_quote_generator']);
        add_shortcode('ffl_bro_dashboard', [$this, 'render_dashboard']);
        add_action('wp_ajax_ffl_bro_quote', [$this, 'handle_ajax']);
        add_action('wp_ajax_nopriv_ffl_bro_quote', [$this, 'handle_ajax']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }
    
    public function init() {
        // Initialize plugin
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('tailwind-css', 'https://unpkg.com/tailwindcss@3.4.0/dist/tailwind.min.css', [], '3.4.0');
        wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.development.js', [], '18.0.0', true);
        wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.development.js', ['react'], '18.0.0', true);
        wp_add_inline_style('tailwind-css', $this->get_custom_styles());
        wp_localize_script('react', 'fflBroAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ffl_bro_nonce')
        ]);
    }
    
    public function get_custom_styles() {
        return "
        .ffl-bro-container { font-family: system-ui, -apple-system, sans-serif; }
        .ffl-bro-card { 
            background: white; 
            border-radius: 12px; 
            border: 1px solid #e5e7eb; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
        .ffl-bro-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .ffl-bro-btn-primary { 
            background: linear-gradient(135deg, #ea580c, #dc2626); 
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .ffl-bro-btn-primary:hover { 
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-1px);
        }
        .ffl-bro-input { 
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 12px;
            width: 100%;
            transition: all 0.2s;
        }
        .ffl-bro-input:focus { 
            border-color: #ea580c;
            outline: none;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1);
        }
        .product-card { 
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            transition: all 0.2s;
            cursor: pointer;
        }
        .product-card:hover { 
            border-color: #ea580c;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .product-card.selected { 
            border-color: #ea580c;
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.2);
        }
        ";
    }
    
    public function add_admin_menu() {
        add_menu_page('FFL-BRO Pro', 'FFL-BRO Pro', 'manage_options', 'ffl-bro-pro', [$this, 'render_admin_page'], 'dashicons-shield-alt', 30);
        add_submenu_page('ffl-bro-pro', 'Quote Generator', 'Quote Generator', 'manage_options', 'ffl-bro-quotes', [$this, 'render_quotes_admin']);
    }
    
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>FFL-BRO Professional Dashboard</h1>
            <div id="ffl-bro-admin-dashboard" style="min-height: 400px; padding: 20px;">
                <div class="ffl-bro-card" style="padding: 40px; text-align: center;">
                    <h2>Dashboard Loading...</h2>
                </div>
            </div>
        </div>
        
        <script>
        const FFLBROAdminApp = () => {
            return React.createElement('div', { className: 'ffl-bro-container' },
                React.createElement('div', { className: 'ffl-bro-card', style: { padding: '20px', margin: '20px 0' } },
                    React.createElement('h2', { style: { color: '#ea580c', marginBottom: '10px' } }, 'Professional Dashboard'),
                    React.createElement('p', null, 'Welcome to FFL-BRO Professional Quote Generator'),
                    React.createElement('div', { style: { display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '20px', marginTop: '20px' } },
                        React.createElement('div', { className: 'ffl-bro-card', style: { padding: '20px', textAlign: 'center' } },
                            React.createElement('h3', { style: { color: '#1f2937' } }, 'Orders Today'),
                            React.createElement('p', { style: { fontSize: '24px', fontWeight: 'bold', color: '#ea580c' } }, '47'),
                            React.createElement('p', { style: { color: '#6b7280' } }, '1,284 this month')
                        ),
                        React.createElement('div', { className: 'ffl-bro-card', style: { padding: '20px', textAlign: 'center' } },
                            React.createElement('h3', { style: { color: '#1f2937' } }, 'Revenue Today'),
                            React.createElement('p', { style: { fontSize: '24px', fontWeight: 'bold', color: '#ea580c' } }, '$18,451'),
                            React.createElement('p', { style: { color: '#6b7280' } }, '$324,681 this month')
                        ),
                        React.createElement('div', { className: 'ffl-bro-card', style: { padding: '20px', textAlign: 'center' } },
                            React.createElement('h3', { style: { color: '#1f2937' } }, 'Active Leads'),
                            React.createElement('p', { style: { fontSize: '24px', fontWeight: 'bold', color: '#ea580c' } }, '156'),
                            React.createElement('p', { style: { color: '#6b7280' } }, '23.4% conversion')
                        ),
                        React.createElement('div', { className: 'ffl-bro-card', style: { padding: '20px', textAlign: 'center' } },
                            React.createElement('h3', { style: { color: '#1f2937' } }, 'Pending Transfers'),
                            React.createElement('p', { style: { fontSize: '24px', fontWeight: 'bold', color: '#ea580c' } }, '23'),
                            React.createElement('p', { style: { color: '#6b7280' } }, 'FFL transfers')
                        )
                    )
                )
            );
        };
        
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                if (typeof React !== 'undefined' && typeof ReactDOM !== 'undefined') {
                    const container = document.getElementById('ffl-bro-admin-dashboard');
                    if (container) {
                        ReactDOM.render(React.createElement(FFLBROAdminApp), container);
                    }
                }
            }, 100);
        });
        </script>
        <?php
    }
    
    public function render_quotes_admin() {
        ?>
        <div class="wrap">
            <h1>Advanced Quote Generator</h1>
            <div id="ffl-bro-quote-admin" style="min-height: 600px;">
                <div class="ffl-bro-card" style="padding: 40px; text-align: center;">
                    <h2>Advanced Quote Generator Loading...</h2>
                    <p>Interactive product selection with distributor comparison</p>
                </div>
            </div>
        </div>
        
        <script>
        const AdvancedQuoteGenerator = () => {
            const [searchTerm, setSearchTerm] = React.useState('');
            const [selectedProducts, setSelectedProducts] = React.useState([]);
            const [customerInfo, setCustomerInfo] = React.useState({ name: '', email: '', phone: '' });
            const [showQuoteForm, setShowQuoteForm] = React.useState(false);
            
            // Mock product database with distributor pricing
            const mockProducts = [
                {
                    id: 1,
                    sku: 'GLK-19-G5',
                    name: 'Glock 19 Gen 5',
                    manufacturer: 'Glock',
                    model: '19 Gen 5',
                    category: 'Handguns',
                    caliber: '9mm',
                    capacity: 15,
                    barrelLength: '4.02"',
                    image: 'https://via.placeholder.com/300x200/333/fff?text=Glock+19',
                    msrp: 649.00,
                    description: 'Reliable striker-fired pistol with enhanced ergonomics.',
                    features: ['Modular Backstraps', 'Glock Marksman Barrel', 'No Finger Grooves'],
                    distributors: [
                        { name: 'RSR Group', price: 515.25, stock: 12, shipping: 'Free', eta: '2-4 days', rating: 4.8, terms: 'NET 30' },
                        { name: 'Zanders', price: 508.90, stock: 8, shipping: '$9.95', eta: '1-2 days', rating: 4.5, terms: 'NET 15' },
                        { name: 'Sports South', price: 522.50, stock: 15, shipping: 'Free over $500', eta: '2-3 days', rating: 4.7, terms: 'NET 30' },
                        { name: 'Lipseys', price: 512.75, stock: 6, shipping: 'Free', eta: '3-5 days', rating: 4.9, terms: 'NET 45' }
                    ]
                },
                {
                    id: 2,
                    sku: 'SIG-P320-C',
                    name: 'SIG Sauer P320 Compact',
                    manufacturer: 'SIG Sauer',
                    model: 'P320 Compact',
                    category: 'Handguns',
                    caliber: '9mm',
                    capacity: 15,
                    barrelLength: '3.9"',
                    image: 'https://via.placeholder.com/300x200/444/fff?text=SIG+P320',
                    msrp: 599.99,
                    description: 'Modular striker-fired pistol with exceptional versatility.',
                    features: ['Modular FCU', 'SIGLITE Night Sights', 'Striker Safety'],
                    distributors: [
                        { name: 'RSR Group', price: 515.25, stock: 6, shipping: 'Free', eta: '2-4 days', rating: 4.8, terms: 'NET 30' },
                        { name: 'Zanders', price: 508.90, stock: 9, shipping: '$9.95', eta: '1-2 days', rating: 4.5, terms: 'NET 15' },
                        { name: 'Sports South', price: 522.50, stock: 11, shipping: 'Free over $500', eta: '2-3 days', rating: 4.7, terms: 'NET 30' }
                    ]
                },
                {
                    id: 3,
                    sku: 'RUG-1022-CAR',
                    name: 'Ruger 10/22 Carbine .22 LR',
                    manufacturer: 'Ruger',
                    model: '10/22 Carbine',
                    category: 'Rifles',
                    caliber: '.22 LR',
                    capacity: 10,
                    barrelLength: '18.5"',
                    image: 'https://via.placeholder.com/300x200/555/fff?text=Ruger+1022',
                    msrp: 309.00,
                    description: 'America\'s favorite .22 rifle with legendary reliability.',
                    features: ['Rotary Magazine', 'Easy Takedown', 'Cross-bolt Safety'],
                    distributors: [
                        { name: 'RSR Group', price: 245.75, stock: 25, shipping: 'Free', eta: '1-2 days', rating: 4.8, terms: 'NET 30' },
                        { name: 'Davidsons', price: 252.80, stock: 18, shipping: '$12.95', eta: '2-3 days', rating: 4.6, terms: 'NET 15' },
                        { name: 'Lipseys', price: 248.90, stock: 22, shipping: 'Free over $300', eta: '1-3 days', rating: 4.9, terms: 'NET 45' }
                    ]
                }
            ];
            
            const filteredProducts = mockProducts.filter(product =>
                product.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                product.manufacturer.toLowerCase().includes(searchTerm.toLowerCase()) ||
                product.sku.toLowerCase().includes(searchTerm.toLowerCase()) ||
                product.caliber.toLowerCase().includes(searchTerm.toLowerCase())
            );
            
            const ProductCard = ({ product }) => {
                const [selectedDistributor, setSelectedDistributor] = React.useState(0);
                const selectedDist = product.distributors[selectedDistributor];
                const isSelected = selectedProducts.some(p => p.id === product.id);
                
                const addToQuote = () => {
                    if (!isSelected) {
                        setSelectedProducts([...selectedProducts, { ...product, selectedDistributor, distributorInfo: selectedDist }]);
                    } else {
                        setSelectedProducts(selectedProducts.filter(p => p.id !== product.id));
                    }
                };
                
                return React.createElement('div', { 
                    className: `product-card ${isSelected ? 'selected' : ''}`,
                    style: { marginBottom: '20px' }
                },
                    React.createElement('div', { style: { padding: '16px' } },
                        React.createElement('div', { style: { display: 'flex', gap: '16px' } },
                            React.createElement('img', {
                                src: product.image,
                                alt: product.name,
                                style: { width: '120px', height: '80px', objectFit: 'cover', borderRadius: '8px', backgroundColor: '#f3f4f6' }
                            }),
                            React.createElement('div', { style: { flex: 1, minWidth: 0 } },
                                React.createElement('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'start' } },
                                    React.createElement('div', { style: { flex: 1 } },
                                        React.createElement('h3', { style: { fontWeight: 'bold', color: '#1f2937', marginBottom: '4px' } }, product.name),
                                        React.createElement('p', { style: { fontSize: '14px', color: '#6b7280' } }, `${product.manufacturer} • ${product.caliber}`),
                                        React.createElement('p', { style: { fontSize: '12px', color: '#9ca3af', marginTop: '4px' } }, `SKU: ${product.sku}`)
                                    ),
                                    React.createElement('div', { style: { display: 'flex', alignItems: 'center', gap: '8px', marginLeft: '16px' } },
                                        React.createElement('div', { style: { textAlign: 'right' } },
                                            React.createElement('div', { style: { fontSize: '18px', fontWeight: 'bold', color: '#059669' } }, `$${selectedDist.price.toFixed(2)}`),
                                            React.createElement('div', { style: { fontSize: '12px', color: '#9ca3af' } }, `MSRP: $${product.msrp.toFixed(2)}`)
                                        )
                                    )
                                ),
                                React.createElement('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: '12px' } },
                                    React.createElement('div', { style: { display: 'flex', alignItems: 'center', gap: '8px' } },
                                        React.createElement('select', {
                                            value: selectedDistributor,
                                            onChange: (e) => setSelectedDistributor(parseInt(e.target.value)),
                                            style: { fontSize: '12px', border: '1px solid #d1d5db', borderRadius: '4px', padding: '4px 8px' }
                                        },
                                            product.distributors.map((dist, idx) =>
                                                React.createElement('option', { key: idx, value: idx }, dist.name)
                                            )
                                        ),
                                        React.createElement('span', {
                                            style: {
                                                display: 'inline-flex',
                                                alignItems: 'center',
                                                padding: '4px 8px',
                                                fontSize: '12px',
                                                borderRadius: '9999px',
                                                ...(selectedDist.stock > 10 
                                                    ? { backgroundColor: '#dcfce7', color: '#166534' }
                                                    : selectedDist.stock > 0 
                                                    ? { backgroundColor: '#fef3c7', color: '#d97706' }
                                                    : { backgroundColor: '#fee2e2', color: '#dc2626' }
                                                )
                                            }
                                        }, `${selectedDist.stock} in stock`),
                                        React.createElement('span', { style: { fontSize: '12px', color: '#6b7280' } }, selectedDist.eta)
                                    ),
                                    React.createElement('button', {
                                        className: 'ffl-bro-btn-primary',
                                        onClick: addToQuote,
                                        style: { fontSize: '14px', padding: '8px 16px' }
                                    }, isSelected ? 'Remove' : 'Add to Quote')
                                )
                            )
                        )
                    )
                );
            };
            
            const QuoteForm = () => {
                const total = selectedProducts.reduce((sum, product) => sum + product.distributorInfo.price, 0);
                const taxRate = 0.07; // 7% tax
                const transferFee = 30.00;
                const backgroundCheckFee = 5.00;
                const tax = total * taxRate;
                const grandTotal = total + tax + transferFee + backgroundCheckFee;
                
                return React.createElement('div', { className: 'ffl-bro-card', style: { padding: '20px', marginTop: '20px' } },
                    React.createElement('h3', { style: { marginBottom: '20px', color: '#1f2937' } }, 'Quote Summary'),
                    React.createElement('div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px', marginBottom: '20px' } },
                        React.createElement('div', null,
                            React.createElement('h4', { style: { marginBottom: '10px' } }, 'Customer Information'),
                            React.createElement('input', {
                                className: 'ffl-bro-input',
                                placeholder: 'Customer Name',
                                value: customerInfo.name,
                                onChange: (e) => setCustomerInfo({...customerInfo, name: e.target.value}),
                                style: { marginBottom: '10px' }
                            }),
                            React.createElement('input', {
                                className: 'ffl-bro-input',
                                placeholder: 'Email Address',
                                value: customerInfo.email,
                                onChange: (e) => setCustomerInfo({...customerInfo, email: e.target.value}),
                                style: { marginBottom: '10px' }
                            }),
                            React.createElement('input', {
                                className: 'ffl-bro-input',
                                placeholder: 'Phone Number',
                                value: customerInfo.phone,
                                onChange: (e) => setCustomerInfo({...customerInfo, phone: e.target.value})
                            })
                        ),
                        React.createElement('div', null,
                            React.createElement('h4', { style: { marginBottom: '10px' } }, 'Quote Totals'),
                            React.createElement('div', { style: { border: '1px solid #e5e7eb', borderRadius: '8px', padding: '15px' } },
                                React.createElement('div', { style: { display: 'flex', justifyContent: 'space-between', marginBottom: '8px' } },
                                    React.createElement('span', null, 'Subtotal:'),
                                    React.createElement('span', { style: { fontWeight: 'bold' } }, `$${total.toFixed(2)}`)
                                ),
                                React.createElement('div', { style: { display: 'flex', justifyContent: 'space-between', marginBottom: '8px' } },
                                    React.createElement('span', null, 'Tax (7%):'),
                                    React.createElement('span', null, `$${tax.toFixed(2)}`)
                                ),
                                React.createElement('div', { style: { display: 'flex', justifyContent: 'space-between', marginBottom: '8px' } },
                                    React.createElement('span', null, 'Transfer Fee:'),
                                    React.createElement('span', null, `$${transferFee.toFixed(2)}`)
                                ),
                                React.createElement('div', { style: { display: 'flex', justifyContent: 'space-between', marginBottom: '12px' } },
                                    React.createElement('span', null, 'Background Check:'),
                                    React.createElement('span', null, `$${backgroundCheckFee.toFixed(2)}`)
                                ),
                                React.createElement('hr', { style: { margin: '12px 0' } }),
                                React.createElement('div', { style: { display: 'flex', justifyContent: 'space-between', fontSize: '18px', fontWeight: 'bold' } },
                                    React.createElement('span', null, 'Total:'),
                                    React.createElement('span', { style: { color: '#ea580c' } }, `$${grandTotal.toFixed(2)}`)
                                )
                            )
                        )
                    ),
                    React.createElement('div', { style: { marginTop: '20px' } },
                        React.createElement('h4', { style: { marginBottom: '10px' } }, 'Selected Products'),
                        selectedProducts.map(product =>
                            React.createElement('div', { key: product.id, style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '10px', border: '1px solid #e5e7eb', borderRadius: '8px', marginBottom: '8px' } },
                                React.createElement('div', null,
                                    React.createElement('span', { style: { fontWeight: 'bold' } }, product.name),
                                    React.createElement('br'),
                                    React.createElement('span', { style: { fontSize: '14px', color: '#6b7280' } }, `${product.distributorInfo.name} • ${product.distributorInfo.eta}`)
                                ),
                                React.createElement('span', { style: { fontWeight: 'bold', color: '#059669' } }, `$${product.distributorInfo.price.toFixed(2)}`)
                            )
                        )
                    ),
                    React.createElement('div', { style: { display: 'flex', gap: '10px', marginTop: '20px' } },
                        React.createElement('button', { className: 'ffl-bro-btn-primary' }, 'Generate PDF Quote'),
                        React.createElement('button', { className: 'ffl-bro-btn-primary' }, 'Email Quote'),
                        React.createElement('button', { 
                            style: { padding: '8px 16px', border: '1px solid #d1d5db', borderRadius: '8px', backgroundColor: 'white', cursor: 'pointer' },
                            onClick: () => setShowQuoteForm(false)
                        }, 'Continue Shopping')
                    )
                );
            };
            
            return React.createElement('div', { style: { padding: '20px' } },
                React.createElement('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' } },
                    React.createElement('h2', null, 'Interactive Product Selection'),
                    React.createElement('div', { style: { display: 'flex', gap: '10px' } },
                        React.createElement('span', { style: { padding: '8px 12px', backgroundColor: '#f3f4f6', borderRadius: '8px' } }, 
                            `${selectedProducts.length} items selected`
                        ),
                        selectedProducts.length > 0 && React.createElement('button', {
                            className: 'ffl-bro-btn-primary',
                            onClick: () => setShowQuoteForm(!showQuoteForm)
                        }, showQuoteForm ? 'Hide Quote' : 'Create Quote')
                    )
                ),
                React.createElement('div', { style: { marginBottom: '20px' } },
                    React.createElement('input', {
                        className: 'ffl-bro-input',
                        placeholder: 'Search products by name, manufacturer, SKU, or caliber...',
                        value: searchTerm,
                        onChange: (e) => setSearchTerm(e.target.value),
                        style: { fontSize: '16px', padding: '15px' }
                    })
                ),
                showQuoteForm && React.createElement(QuoteForm),
                React.createElement('div', { style: { marginTop: '20px' } },
                    filteredProducts.length === 0 ? 
                        React.createElement('div', { style: { textAlign: 'center', padding: '40px', color: '#6b7280' } },
                            React.createElement('h3', null, 'No products found'),
                            React.createElement('p', null, 'Try adjusting your search terms')
                        ) :
                        filteredProducts.map(product => React.createElement(ProductCard, { key: product.id, product }))
                )
            );
        };
        
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                if (typeof React !== 'undefined' && typeof ReactDOM !== 'undefined') {
                    const container = document.getElementById('ffl-bro-quote-admin');
                    if (container) {
                        ReactDOM.render(React.createElement(AdvancedQuoteGenerator), container);
                    }
                }
            }, 100);
        });
        </script>
        <?php
    }
    
    public function render_dashboard($atts) {
        return '<div>Frontend dashboard shortcode working</div>';
    }
    
    public function render_quote_generator($atts) {
        return '<div>Frontend quote generator shortcode working</div>';
    }
    
    public function handle_ajax() {
        wp_send_json_success(['message' => 'AJAX working']);
    }
}

new FFLBROProQuoteGenerator();
?>
EOF

echo "Advanced Quote Generator installed successfully!"
EOF