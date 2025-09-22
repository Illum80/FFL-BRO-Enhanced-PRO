<?php
/**
 * FFL-BRO Quote Generator Advanced
 * Complete professional quote generation system
 * GitHub: JRNeefe@gmail.com
 * Lipseys: JRNeefe@gmail.com
 */

if (!defined('ABSPATH')) {
    exit;
}

class FFLBROQuoteGeneratorAdvanced {
    
    private $table_name;
    private $version = '2.0.0';
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'fflbro_quotes_advanced';
        
        // Hook into WordPress
        add_action('admin_menu', array($this, 'add_admin_menu'), 25);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_fflbro_save_quote_advanced', array($this, 'handle_save_quote'));
        add_action('wp_ajax_fflbro_load_quote_advanced', array($this, 'handle_load_quote'));
        add_action('wp_ajax_fflbro_get_saved_quotes_advanced', array($this, 'handle_get_saved_quotes'));
        add_action('wp_ajax_fflbro_generate_pdf_advanced', array($this, 'handle_generate_pdf'));
        add_action('wp_ajax_fflbro_email_quote_advanced', array($this, 'handle_email_quote'));
        
        // Create database tables on activation
        register_activation_hook(__FILE__, array($this, 'create_tables'));
        
        // Add shortcode support
        add_shortcode('fflbro_quote_generator_advanced', array($this, 'render_shortcode'));
        
        // Initialize tables if they don't exist
        add_action('init', array($this, 'maybe_create_tables'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'ffl-bro',
            'Quote Generator Pro', 
            'Quotes Pro', 
            'manage_options', 
            'ffl-bro-quotes-advanced', 
            array($this, 'admin_page')
        );
    }
    
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'ffl-bro-quotes-advanced') !== false) {
            // Enqueue React and required scripts
            wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.production.min.js', array(), '18.0.0', true);
            wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', array('react'), '18.0.0', true);
            wp_enqueue_script('babel', 'https://unpkg.com/@babel/standalone/babel.min.js', array(), '7.0.0', true);
            
            // Localize script for AJAX
            wp_localize_script('react', 'fflbro_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('fflbro_quotes_nonce'),
                'github_user' => 'JRNeefe@gmail.com',
                'lipseys_user' => 'JRNeefe@gmail.com'
            ));
        }
    }
    
    public function maybe_create_tables() {
        if (get_option('fflbro_quotes_advanced_version') !== $this->version) {
            $this->create_tables();
        }
    }
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Quotes table
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            quote_name varchar(255) NOT NULL,
            customer_name varchar(255) DEFAULT '',
            customer_email varchar(255) DEFAULT '',
            customer_phone varchar(50) DEFAULT '',
            customer_address text DEFAULT '',
            customer_ffl varchar(100) DEFAULT '',
            quote_data longtext NOT NULL,
            status varchar(50) DEFAULT 'draft',
            total_amount decimal(10,2) DEFAULT 0.00,
            total_profit decimal(10,2) DEFAULT 0.00,
            valid_until date DEFAULT NULL,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by bigint(20) unsigned DEFAULT NULL,
            notes text DEFAULT '',
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_date (created_date),
            KEY customer_email (customer_email)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Set version
        update_option('fflbro_quotes_advanced_version', $this->version);
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>FFL-BRO Quote Generator Pro</h1>
            <div id="fflbro-quote-generator-advanced"></div>
        </div>
        
        <!-- Tailwind CSS -->
        <script src="https://cdn.tailwindcss.com"></script>
        
        <!-- React Component -->
        <script type="text/babel">
            const { useState, useEffect, useMemo } = React;
            
            // Advanced Quote Generator Component
            const FFLBROQuoteGeneratorAdvanced = () => {
                // State management
                const [currentView, setCurrentView] = useState('generator');
                const [searchTerm, setSearchTerm] = useState('');
                const [selectedCategory, setSelectedCategory] = useState('all');
                const [selectedCaliber, setSelectedCaliber] = useState('all');
                const [sortBy, setSortBy] = useState('relevance');
                const [showQuoteBuilder, setShowQuoteBuilder] = useState(false);
                const [quoteItems, setQuoteItems] = useState([]);
                const [savedQuotes, setSavedQuotes] = useState([]);
                const [quoteCustomer, setQuoteCustomer] = useState({
                    name: '', email: '', phone: '', address: '', ffl: '', notes: ''
                });
                const [businessSettings] = useState({
                    businessName: 'Premium Firearms LLC',
                    fflNumber: '1-23-456-78-90-12345',
                    address: '123 Gun Store Ave, Firearm City, TX 12345',
                    phone: '(555) 123-4567',
                    email: 'JRNeefe@gmail.com',
                    defaultMarkup: 15,
                    transferFee: 25,
                    backgroundCheckFee: 15,
                    taxRate: 8.25,
                    quoteValidDays: 30
                });
                const [currentQuote, setCurrentQuote] = useState({
                    id: null, quoteName: '', status: 'draft', validUntil: '',
                    notes: '', createdDate: new Date().toISOString().split('T')[0]
                });
                
                // Mock product data - ready for real Lipseys integration
                const mockProducts = [
                    {
                        id: 1, name: 'Glock 19 Gen5', manufacturer: 'Glock', model: 'G19 Gen5',
                        sku: 'GL19G5', upc: '764503037627', caliber: '9mm', category: 'pistol',
                        type: 'Striker-Fired', barrel: '4.02"', capacity: '15+1', weight: '23.65 oz',
                        msrp: 599.00, description: 'The most trusted law enforcement sidearm',
                        distributors: {
                            lipseys: { price: 449.99, stock: 15, shipping: 'Next Day', reliability: 98, lastUpdated: '2 hours ago' },
                            sportsSouth: { price: 454.99, stock: 8, shipping: '2-3 Days', reliability: 95, lastUpdated: '4 hours ago' },
                            zanders: { price: 459.99, stock: 22, shipping: '1-2 Days', reliability: 97, lastUpdated: '1 hour ago' }
                        },
                        rating: 4.8, reviews: 324
                    },
                    {
                        id: 2, name: 'Smith & Wesson M&P15 Sport II', manufacturer: 'Smith & Wesson',
                        model: 'M&P15 Sport II', sku: 'SW15S2', upc: '022188870084', caliber: '.223/5.56',
                        category: 'rifle', type: 'Semi-Automatic', barrel: '16"', capacity: '30+1',
                        weight: '6.45 lbs', msrp: 739.00, description: 'Popular entry-level AR-15 platform',
                        distributors: {
                            lipseys: { price: 589.99, stock: 5, shipping: 'Next Day', reliability: 98, lastUpdated: '1 hour ago' },
                            sportsSouth: { price: 594.99, stock: 12, shipping: '2-3 Days', reliability: 95, lastUpdated: '3 hours ago' },
                            zanders: { price: 599.99, stock: 0, shipping: 'Backorder', reliability: 97, lastUpdated: '5 hours ago' }
                        },
                        rating: 4.6, reviews: 198
                    },
                    {
                        id: 3, name: 'Ruger 10/22 Carbine', manufacturer: 'Ruger', model: '10/22',
                        sku: 'RU1022', upc: '736676011030', caliber: '.22 LR', category: 'rifle',
                        type: 'Semi-Automatic', barrel: '18.5"', capacity: '10+1', weight: '5 lbs',
                        msrp: 309.00, description: 'America\'s favorite .22 rifle',
                        distributors: {
                            lipseys: { price: 249.99, stock: 25, shipping: 'Next Day', reliability: 98, lastUpdated: '30 minutes ago' },
                            sportsSouth: { price: 254.99, stock: 18, shipping: '1-2 Days', reliability: 95, lastUpdated: '2 hours ago' },
                            zanders: { price: 259.99, stock: 31, shipping: '1-2 Days', reliability: 97, lastUpdated: '45 minutes ago' }
                        },
                        rating: 4.7, reviews: 756
                    }
                ];
                
                const mockDistributors = [
                    { id: 'lipseys', name: 'Lipseys', reliability: 98, avgShipping: '1-2 days', specialties: ['Law Enforcement', 'Premium Brands'] },
                    { id: 'sportsSouth', name: 'Sports South', reliability: 95, avgShipping: '2-3 days', specialties: ['Competitive Pricing', 'Wide Selection'] },
                    { id: 'zanders', name: 'Zanders', reliability: 97, avgShipping: '1-2 days', specialties: ['Fast Shipping', 'Accessories'] }
                ];
                
                // Utility functions
                const getBestPrice = (product) => {
                    const prices = Object.values(product.distributors)
                        .filter(d => d.stock > 0)
                        .map(d => d.price);
                    return prices.length > 0 ? Math.min(...prices) : Math.min(...Object.values(product.distributors).map(d => d.price));
                };
                
                const getBestDistributor = (product) => {
                    let bestDist = null;
                    let bestPrice = Infinity;
                    
                    Object.entries(product.distributors).forEach(([key, data]) => {
                        if (data.price < bestPrice && data.stock > 0) {
                            bestPrice = data.price;
                            bestDist = { id: key, ...data, distributor: mockDistributors.find(d => d.id === key) };
                        }
                    });
                    
                    return bestDist || { 
                        id: Object.keys(product.distributors)[0], 
                        ...Object.values(product.distributors)[0], 
                        distributor: mockDistributors.find(d => d.id === Object.keys(product.distributors)[0]) 
                    };
                };
                
                const calculatePricing = (cost, markup, quantity = 1) => {
                    const markupAmount = cost * (markup / 100);
                    const unitPrice = cost + markupAmount;
                    const subtotal = unitPrice * quantity;
                    const profit = markupAmount * quantity;
                    
                    return { cost: cost * quantity, unitPrice, subtotal, profit, margin: markup };
                };
                
                const calculateQuoteTotals = () => {
                    let subtotal = 0, totalProfit = 0, totalCost = 0;
                    
                    quoteItems.forEach(item => {
                        const pricing = calculatePricing(item.distributorData.price, item.markup || businessSettings.defaultMarkup, item.quantity);
                        subtotal += pricing.subtotal;
                        totalProfit += pricing.profit;
                        totalCost += pricing.cost;
                    });
                    
                    const transferFee = businessSettings.transferFee;
                    const backgroundCheck = businessSettings.backgroundCheckFee;
                    const tax = subtotal * (businessSettings.taxRate / 100);
                    const total = subtotal + transferFee + backgroundCheck + tax;
                    
                    return {
                        subtotal, transferFee, backgroundCheck, tax, total,
                        totalProfit, totalCost,
                        overallMargin: subtotal > 0 ? (totalProfit / subtotal) * 100 : 0
                    };
                };
                
                // Filter products
                const filteredProducts = useMemo(() => {
                    let filtered = mockProducts;
                    
                    if (searchTerm) {
                        filtered = filtered.filter(product =>
                            product.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                            product.manufacturer.toLowerCase().includes(searchTerm.toLowerCase()) ||
                            product.model.toLowerCase().includes(searchTerm.toLowerCase()) ||
                            product.sku.toLowerCase().includes(searchTerm.toLowerCase()) ||
                            product.upc.includes(searchTerm)
                        );
                    }
                    
                    if (selectedCategory !== 'all') {
                        filtered = filtered.filter(product => product.category === selectedCategory);
                    }
                    
                    if (selectedCaliber !== 'all') {
                        filtered = filtered.filter(product => product.caliber === selectedCaliber);
                    }
                    
                    filtered.sort((a, b) => {
                        switch (sortBy) {
                            case 'price-low': return getBestPrice(a) - getBestPrice(b);
                            case 'price-high': return getBestPrice(b) - getBestPrice(a);
                            case 'rating': return b.rating - a.rating;
                            case 'name': return a.model.localeCompare(b.model);
                            default: return 0;
                        }
                    });
                    
                    return filtered;
                }, [searchTerm, selectedCategory, selectedCaliber, sortBy]);
                
                // Quote management
                const addToQuote = (product, distributorId = null) => {
                    const bestDist = distributorId ? 
                        { id: distributorId, ...product.distributors[distributorId], distributor: mockDistributors.find(d => d.id === distributorId) } :
                        getBestDistributor(product);
                    
                    const quoteItem = {
                        id: `${product.id}-${bestDist.id}-${Date.now()}`,
                        product, distributor: bestDist.distributor, distributorData: bestDist,
                        quantity: 1, markup: businessSettings.defaultMarkup
                    };
                    
                    setQuoteItems(prev => [...prev, quoteItem]);
                    setShowQuoteBuilder(true);
                };
                
                const updateQuantity = (itemId, quantity) => {
                    if (quantity <= 0) {
                        setQuoteItems(prev => prev.filter(item => item.id !== itemId));
                        return;
                    }
                    setQuoteItems(prev => prev.map(item => 
                        item.id === itemId ? { ...item, quantity } : item
                    ));
                };
                
                const updateMarkup = (itemId, markup) => {
                    setQuoteItems(prev => prev.map(item => 
                        item.id === itemId ? { ...item, markup } : item
                    ));
                };
                
                const saveQuote = () => {
                    const totals = calculateQuoteTotals();
                    const newQuote = {
                        ...currentQuote,
                        id: currentQuote.id || Date.now(),
                        items: quoteItems, customer: quoteCustomer, totals,
                        validUntil: new Date(Date.now() + businessSettings.quoteValidDays * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
                    };
                    
                    setSavedQuotes(prev => {
                        const existing = prev.findIndex(q => q.id === newQuote.id);
                        if (existing >= 0) {
                            const updated = [...prev];
                            updated[existing] = newQuote;
                            return updated;
                        }
                        return [...prev, newQuote];
                    });
                    
                    setCurrentQuote(newQuote);
                    
                    // AJAX save to WordPress database
                    const formData = new FormData();
                    formData.append('action', 'fflbro_save_quote_advanced');
                    formData.append('nonce', fflbro_ajax.nonce);
                    formData.append('quote_data', JSON.stringify(newQuote));
                    
                    fetch(fflbro_ajax.ajax_url, {
                        method: 'POST',
                        body: formData
                    }).then(response => response.json())
                      .then(data => {
                          if (data.success) {
                              alert('Quote saved successfully to database!');
                          } else {
                              alert('Error saving quote: ' + (data.data || 'Unknown error'));
                          }
                      });
                };
                
                return React.createElement('div', { className: "min-h-screen bg-gray-100" }, [
                    // Navigation
                    React.createElement('div', { className: "bg-white shadow" }, [
                        React.createElement('div', { className: "max-w-7xl mx-auto px-4" }, [
                            React.createElement('div', { className: "flex justify-between items-center py-4" }, [
                                React.createElement('div', null, [
                                    React.createElement('h1', { className: "text-2xl font-bold text-gray-900" }, 'FFL-BRO Quote Generator Pro'),
                                    React.createElement('p', { className: "text-sm text-gray-600" }, 'GitHub: JRNeefe@gmail.com | Lipseys: JRNeefe@gmail.com')
                                ]),
                                React.createElement('div', { className: "flex gap-4" }, [
                                    React.createElement('button', {
                                        onClick: () => setCurrentView('generator'),
                                        className: `px-4 py-2 rounded ${currentView === 'generator' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'}`
                                    }, 'Product Catalog'),
                                    React.createElement('button', {
                                        onClick: () => setCurrentView('quotes'),
                                        className: `px-4 py-2 rounded ${currentView === 'quotes' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'}`
                                    }, `Saved Quotes (${savedQuotes.length})`),
                                    React.createElement('button', {
                                        onClick: () => setCurrentView('analytics'),
                                        className: `px-4 py-2 rounded ${currentView === 'analytics' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'}`
                                    }, 'Analytics')
                                ])
                            ])
                        ])
                    ]),
                    
                    // Main content area with basic layout
                    React.createElement('div', { className: "max-w-7xl mx-auto px-4 py-6" }, [
                        currentView === 'generator' && React.createElement('div', { className: "space-y-6" }, [
                            React.createElement('div', { className: "bg-white rounded-lg shadow p-6" }, [
                                React.createElement('h2', { className: "text-xl font-bold mb-4" }, 'Product Search & Quote Builder'),
                                React.createElement('div', { className: "grid grid-cols-1 md:grid-cols-4 gap-4 mb-6" }, [
                                    React.createElement('input', {
                                        type: "text", placeholder: "Search products...",
                                        value: searchTerm,
                                        onChange: (e) => setSearchTerm(e.target.value),
                                        className: "w-full p-2 border rounded-lg"
                                    }),
                                    React.createElement('select', {
                                        value: selectedCategory,
                                        onChange: (e) => setSelectedCategory(e.target.value),
                                        className: "p-2 border rounded-lg"
                                    }, [
                                        React.createElement('option', { value: "all" }, 'All Categories'),
                                        React.createElement('option', { value: "pistol" }, 'Pistols'),
                                        React.createElement('option', { value: "rifle" }, 'Rifles'),
                                        React.createElement('option', { value: "shotgun" }, 'Shotguns')
                                    ]),
                                    React.createElement('select', {
                                        value: selectedCaliber,
                                        onChange: (e) => setSelectedCaliber(e.target.value),
                                        className: "p-2 border rounded-lg"
                                    }, [
                                        React.createElement('option', { value: "all" }, 'All Calibers'),
                                        React.createElement('option', { value: "9mm" }, '9mm'),
                                        React.createElement('option', { value: ".223/5.56" }, '.223/5.56'),
                                        React.createElement('option', { value: ".22 LR" }, '.22 LR')
                                    ]),
                                    React.createElement('button', {
                                        onClick: () => setShowQuoteBuilder(!showQuoteBuilder),
                                        className: "bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
                                    }, showQuoteBuilder ? 'Hide Quote Builder' : 'Show Quote Builder')
                                ]),
                                
                                // Simple product grid
                                React.createElement('div', { className: "space-y-4" }, 
                                    filteredProducts.map(product => {
                                        const bestPrice = getBestPrice(product);
                                        return React.createElement('div', { 
                                            key: product.id, 
                                            className: "border rounded-lg p-4 hover:shadow-md transition-shadow" 
                                        }, [
                                            React.createElement('div', { className: "flex justify-between items-center" }, [
                                                React.createElement('div', null, [
                                                    React.createElement('h3', { className: "font-bold text-lg" }, product.name),
                                                    React.createElement('p', { className: "text-gray-600" }, `${product.manufacturer} • ${product.model} • ${product.caliber}`),
                                                    React.createElement('p', { className: "text-sm text-gray-500" }, `SKU: ${product.sku}`)
                                                ]),
                                                React.createElement('div', { className: "text-right" }, [
                                                    React.createElement('p', { className: "text-2xl font-bold text-green-600" }, `$${bestPrice.toFixed(2)}`),
                                                    React.createElement('button', {
                                                        onClick: () => addToQuote(product),
                                                        className: "bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 mt-2"
                                                    }, 'Add to Quote')
                                                ])
                                            ])
                                        ]);
                                    })
                                )
                            ])
                        ]),
                        
                        // Quote Builder
                        showQuoteBuilder && React.createElement('div', { className: "bg-white rounded-lg shadow-lg p-6 mt-6" }, [
                            React.createElement('h2', { className: "text-2xl font-bold mb-4" }, 'Quote Builder'),
                            React.createElement('div', { className: "grid grid-cols-2 gap-4 mb-4" }, [
                                React.createElement('input', {
                                    type: "text", placeholder: "Quote Name",
                                    value: currentQuote.quoteName,
                                    onChange: (e) => setCurrentQuote(prev => ({ ...prev, quoteName: e.target.value })),
                                    className: "p-2 border rounded"
                                }),
                                React.createElement('input', {
                                    type: "text", placeholder: "Customer Name",
                                    value: quoteCustomer.name,
                                    onChange: (e) => setQuoteCustomer(prev => ({ ...prev, name: e.target.value })),
                                    className: "p-2 border rounded"
                                })
                            ]),
                            
                            quoteItems.length > 0 && React.createElement('div', { className: "mb-4" }, [
                                React.createElement('h3', { className: "font-semibold mb-2" }, `Quote Items (${quoteItems.length})`),
                                React.createElement('div', { className: "space-y-2" }, 
                                    quoteItems.map(item => {
                                        const pricing = calculatePricing(item.distributorData.price, item.markup || businessSettings.defaultMarkup, item.quantity);
                                        return React.createElement('div', { 
                                            key: item.id, 
                                            className: "flex justify-between items-center border-b pb-2" 
                                        }, [
                                            React.createElement('div', null, [
                                                React.createElement('span', { className: "font-medium" }, item.product.name),
                                                React.createElement('span', { className: "text-sm text-gray-600 ml-2" }, `Qty: ${item.quantity}`)
                                            ]),
                                            React.createElement('div', { className: "text-right" }, [
                                                React.createElement('span', { className: "font-bold" }, `$${pricing.subtotal.toFixed(2)}`),
                                                React.createElement('span', { className: "text-sm text-green-600 block" }, `Profit: $${pricing.profit.toFixed(2)}`)
                                            ])
                                        ]);
                                    })
                                ),
                                
                                // Totals
                                React.createElement('div', { className: "mt-4 p-4 bg-gray-50 rounded" }, (() => {
                                    const totals = calculateQuoteTotals();
                                    return [
                                        React.createElement('div', { className: "flex justify-between font-bold text-lg" }, [
                                            React.createElement('span', null, 'Total:'),
                                            React.createElement('span', null, `$${totals.total.toFixed(2)}`)
                                        ]),
                                        React.createElement('div', { className: "flex justify-between text-green-600" }, [
                                            React.createElement('span', null, 'Total Profit:'),
                                            React.createElement('span', null, `$${totals.totalProfit.toFixed(2)} (${totals.overallMargin.toFixed(1)}%)`)
                                        ])
                                    ];
                                })())
                            ]),
                            
                            React.createElement('div', { className: "flex gap-3" }, [
                                React.createElement('button', {
                                    onClick: saveQuote,
                                    className: "bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700"
                                }, 'Save Quote'),
                                React.createElement('button', {
                                    onClick: () => alert('PDF generation coming soon!'),
                                    className: "bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700"
                                }, 'Generate PDF'),
                                React.createElement('button', {
                                    onClick: () => alert('Email feature coming soon!'),
                                    className: "bg-purple-600 text-white px-6 py-2 rounded hover:bg-purple-700"
                                }, 'Email Quote')
                            ])
                        ]),
                        
                        currentView === 'quotes' && React.createElement('div', { className: "bg-white rounded-lg shadow p-6" }, [
                            React.createElement('h2', { className: "text-2xl font-bold mb-4" }, 'Saved Quotes'),
                            savedQuotes.length === 0 ? 
                                React.createElement('p', { className: "text-gray-500 text-center py-8" }, 'No saved quotes yet. Create your first quote using the Product Catalog.') :
                                React.createElement('div', { className: "space-y-4" }, 
                                    savedQuotes.map(quote => 
                                        React.createElement('div', { 
                                            key: quote.id, 
                                            className: "border rounded-lg p-4" 
                                        }, [
                                            React.createElement('h3', { className: "font-bold" }, quote.quoteName || `Quote #${quote.id}`),
                                            React.createElement('p', null, `Customer: ${quote.customer?.name || 'N/A'}`),
                                            React.createElement('p', null, `Total: $${quote.totals?.total?.toFixed(2) || '0.00'}`)
                                        ])
                                    )
                                )
                        ]),
                        
                        currentView === 'analytics' && React.createElement('div', { className: "bg-white rounded-lg shadow p-6" }, [
                            React.createElement('h2', { className: "text-2xl font-bold mb-6" }, 'Analytics Dashboard'),
                            React.createElement('div', { className: "grid grid-cols-1 md:grid-cols-3 gap-6" }, [
                                React.createElement('div', { className: "bg-blue-50 p-4 rounded-lg" }, [
                                    React.createElement('h3', { className: "font-semibold text-blue-900" }, 'Total Quotes'),
                                    React.createElement('p', { className: "text-2xl font-bold text-blue-600" }, savedQuotes.length)
                                ]),
                                React.createElement('div', { className: "bg-green-50 p-4 rounded-lg" }, [
                                    React.createElement('h3', { className: "font-semibold text-green-900" }, 'Total Value'),
                                    React.createElement('p', { className: "text-2xl font-bold text-green-600" }, 
                                        `$${savedQuotes.reduce((sum, quote) => sum + (quote.totals?.total || 0), 0).toFixed(2)}`
                                    )
                                ]),
                                React.createElement('div', { className: "bg-purple-50 p-4 rounded-lg" }, [
                                    React.createElement('h3', { className: "font-semibold text-purple-900" }, 'Integration Ready'),
                                    React.createElement('p', { className: "text-lg font-bold text-purple-600" }, 'JRNeefe@gmail.com'),
                                    React.createElement('p', { className: "text-sm text-purple-600" }, 'Lipseys & GitHub')
                                ])
                            ])
                        ])
                    ])
                ]);
            };
            
            // Render the component
            ReactDOM.render(React.createElement(FFLBROQuoteGeneratorAdvanced), document.getElementById('fflbro-quote-generator-advanced'));
        </script>
        <?php
    }
    
    // AJAX handlers
    public function handle_save_quote() {
        check_ajax_referer('fflbro_quotes_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $quote_data = json_decode(stripslashes($_POST['quote_data']), true);
        
        if (!$quote_data) {
            wp_send_json_error('Invalid quote data');
        }
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'quote_name' => sanitize_text_field($quote_data['quoteName']),
                'customer_name' => sanitize_text_field($quote_data['customer']['name']),
                'customer_email' => sanitize_email($quote_data['customer']['email']),
                'customer_phone' => sanitize_text_field($quote_data['customer']['phone']),
                'customer_address' => sanitize_textarea_field($quote_data['customer']['address']),
                'customer_ffl' => sanitize_text_field($quote_data['customer']['ffl']),
                'quote_data' => wp_json_encode($quote_data),
                'status' => sanitize_text_field($quote_data['status']),
                'total_amount' => floatval($quote_data['totals']['total']),
                'total_profit' => floatval($quote_data['totals']['totalProfit']),
                'valid_until' => sanitize_text_field($quote_data['validUntil']),
                'notes' => sanitize_textarea_field($quote_data['notes']),
                'created_by' => get_current_user_id()
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%d')
        );
        
        if ($result === false) {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        }
        
        wp_send_json_success(array('quote_id' => $wpdb->insert_id));
    }
    
    public function handle_load_quote() {
        check_ajax_referer('fflbro_quotes_nonce', 'nonce');
        wp_send_json_success('Load quote feature coming soon!');
    }
    
    public function handle_get_saved_quotes() {
        check_ajax_referer('fflbro_quotes_nonce', 'nonce');
        
        global $wpdb;
        $quotes = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created_date DESC");
        
        wp_send_json_success($quotes);
    }
    
    public function handle_generate_pdf() {
        check_ajax_referer('fflbro_quotes_nonce', 'nonce');
        wp_send_json_success('PDF generation feature coming soon!');
    }
    
    public function handle_email_quote() {
        check_ajax_referer('fflbro_quotes_nonce', 'nonce');
        wp_send_json_success('Email feature coming soon!');
    }
    
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'mode' => 'full'
        ), $atts);
        
        return '<div id="fflbro-quote-generator-advanced-shortcode"></div>';
    }
}

// Initialize the plugin
new FFLBROQuoteGeneratorAdvanced();
?>
