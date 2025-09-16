/**
 * FFL-BRO Platform - React Component Integration
 * This script loads the comprehensive React component into WordPress
 */

(function($) {
    'use strict';

    // Load the comprehensive React component when libraries are ready
    window.FFLBroPlatform.loadActualComponent = function(container, module, isAdmin, customerMode) {
        
        // Define the comprehensive React component
        const FFLBroPlatformComponent = () => {
            const [activeModule, setActiveModule] = React.useState(module || 'dashboard');
            const [settings, setSettings] = React.useState(window.fflBroData?.settings || {});
            const [apiSettings, setApiSettings] = React.useState({
                gunbroker: { enabled: false, username: '', password: '', devKey: '' },
                lipseys: { enabled: false, username: '', password: '', apiKey: '' },
                rsr: { enabled: false, username: '', password: '', apiKey: '' },
                davidsons: { enabled: false, username: '', password: '', apiKey: '' }
            });
            const [mockMode, setMockMode] = React.useState(window.fflBroData?.mockMode !== false);

            // Mock data from the comprehensive component
            const mockData = {
                products: [
                    {
                        id: 1,
                        sku: 'GLK-19-GEN5',
                        upc: '764503911347',
                        name: 'Glock 19 Gen 5 9mm',
                        manufacturer: 'Glock',
                        model: 'G19 Gen 5',
                        category: 'Handguns',
                        caliber: '9mm Luger',
                        capacity: 15,
                        msrp: 649.99,
                        image: '/api/placeholder/300/200',
                        distributors: [
                            { name: 'RSR Group', price: 435.25, stock: 12, shipping: 'Free', eta: '1-2 days', rating: 4.8, reliability: 98.5 },
                            { name: 'Davidson\'s', price: 442.80, stock: 8, shipping: '$12.95', eta: '2-3 days', rating: 4.6, reliability: 96.2 },
                            { name: 'Lipseys', price: 438.90, stock: 15, shipping: 'Free over $500', eta: '1-2 days', rating: 4.7, reliability: 97.8 }
                        ]
                    },
                    {
                        id: 2,
                        sku: 'SIG-P320-C',
                        upc: '798681554362',
                        name: 'SIG Sauer P320 Compact 9mm',
                        manufacturer: 'SIG Sauer',
                        model: 'P320 Compact',
                        category: 'Handguns',
                        caliber: '9mm Luger',
                        capacity: 15,
                        msrp: 579.99,
                        image: '/api/placeholder/300/200',
                        distributors: [
                            { name: 'RSR Group', price: 515.25, stock: 6, shipping: 'Free', eta: '2-4 days', rating: 4.8, reliability: 98.5 },
                            { name: 'Lipseys', price: 508.90, stock: 9, shipping: '$9.95', eta: '1-2 days', rating: 4.5, reliability: 97.8 },
                            { name: 'Sports South', price: 522.50, stock: 11, shipping: 'Free over $500', eta: '2-3 days', rating: 4.7, reliability: 99.1 }
                        ]
                    }
                ],
                gunbrokerListings: [
                    {
                        id: 'GB001',
                        title: 'Glock 19 Gen5 - Like New',
                        category: 'Handguns',
                        currentBid: 525.00,
                        buyNow: 575.00,
                        bids: 8,
                        timeLeft: '2d 14h',
                        views: 156,
                        watchers: 23,
                        status: 'active'
                    },
                    {
                        id: 'GB002',
                        title: 'Smith & Wesson M&P15 Sport II',
                        category: 'Rifles',
                        currentBid: 745.00,
                        buyNow: 825.00,
                        bids: 12,
                        timeLeft: '1d 8h',
                        views: 234,
                        watchers: 45,
                        status: 'active'
                    }
                ],
                businessSettings: {
                    name: settings.business_name || 'Your FFL Business',
                    ffl: settings.ffl_license || '1-47-012-34-5F-06789',
                    address: settings.address || '123 Main Street, St. Cloud, FL 34769',
                    phone: settings.phone || '(407) 555-0123',
                    email: settings.email || 'quotes@yourffl.com',
                    website: settings.website || 'www.yourffl.com',
                    transferFee: parseFloat(settings.transfer_fee) || 25.00,
                    backgroundCheckFee: parseFloat(settings.background_check_fee) || 5.00,
                    defaultMarkup: parseFloat(settings.default_markup) || 25,
                    minimumMargin: parseFloat(settings.minimum_margin) || 10,
                    taxRate: parseFloat(settings.tax_rate) || 7.0,
                    ccProcessingFee: parseFloat(settings.cc_processing_fee) || 2.9
                }
            };

            // Helper Components
            const StatCard = ({ title, value, change, icon: Icon, color = 'blue' }) => (
                React.createElement('div', { className: 'bg-white p-6 rounded-xl border border-gray-200 shadow-sm' }, [
                    React.createElement('div', { key: 'content', className: 'flex items-center justify-between' }, [
                        React.createElement('div', { key: 'info' }, [
                            React.createElement('p', { key: 'title', className: 'text-sm text-gray-600 mb-1' }, title),
                            React.createElement('p', { key: 'value', className: 'text-2xl font-bold text-gray-900' }, value),
                            change && React.createElement('p', { key: 'change', className: 'text-sm text-green-600 mt-1' }, `+${change}%`)
                        ]),
                        React.createElement('div', { 
                            key: 'icon', 
                            className: `w-12 h-12 bg-${color}-100 rounded-full flex items-center justify-center` 
                        }, React.createElement(Icon, { className: `w-6 h-6 text-${color}-600` }))
                    ])
                ])
            );

            // Navigation Component
            const Navigation = () => {
                const modules = [
                    { id: 'dashboard', label: 'Dashboard', icon: '📊', desc: 'Overview & Analytics' },
                    { id: 'quotes', label: 'Quote Generator', icon: '🧮', desc: 'Create Professional Quotes' },
                    { id: 'gunbroker', label: 'GunBroker', icon: '🌐', desc: 'Marketplace Management' },
                    { id: 'lipseys', label: 'Lipseys', icon: '🏢', desc: 'Distributor Integration' },
                    { id: 'inventory', label: 'Inventory', icon: '📦', desc: 'Stock Management' },
                    { id: 'form4473', label: 'Form 4473', icon: '📋', desc: 'Digital Processing' }
                ];

                if (isAdmin) {
                    modules.push({ id: 'settings', label: 'Settings', icon: '⚙️', desc: 'Configuration' });
                }

                return React.createElement('div', { className: 'bg-white shadow-sm border-b border-gray-200' }, [
                    React.createElement('div', { key: 'container', className: 'max-w-7xl mx-auto px-4' }, [
                        React.createElement('div', { key: 'header', className: 'flex items-center justify-between py-4' }, [
                            React.createElement('div', { key: 'brand', className: 'flex items-center gap-3' }, [
                                React.createElement('div', { 
                                    key: 'logo',
                                    className: 'w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold' 
                                }, '⚡'),
                                React.createElement('div', { key: 'title' }, [
                                    React.createElement('h1', { key: 'name', className: 'text-xl font-bold text-gray-900' }, 'FFL-BRO Platform v4.0'),
                                    React.createElement('p', { key: 'desc', className: 'text-sm text-gray-600' }, 'Comprehensive FFL Business Management')
                                ])
                            ]),
                            React.createElement('div', { key: 'status', className: 'flex items-center gap-4' }, [
                                React.createElement('div', { key: 'mode', className: 'flex items-center gap-2' }, [
                                    React.createElement('div', { className: `w-3 h-3 rounded-full ${mockMode ? 'bg-yellow-500' : 'bg-green-500'}` }),
                                    React.createElement('span', { className: 'text-sm text-gray-600' }, mockMode ? 'Mock Data' : 'Live Data')
                                ]),
                                React.createElement('button', {
                                    key: 'toggle',
                                    onClick: () => setMockMode(!mockMode),
                                    className: 'flex items-center gap-2 px-3 py-1 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm'
                                }, [
                                    React.createElement('span', { key: 'icon' }, mockMode ? '📡' : '💾'),
                                    React.createElement('span', { key: 'text' }, mockMode ? 'Enable Live' : 'Use Mock')
                                ])
                            ])
                        ]),
                        React.createElement('div', { key: 'nav', className: 'flex flex-wrap gap-2 pb-4' }, 
                            modules.map(mod => 
                                React.createElement('button', {
                                    key: mod.id,
                                    onClick: () => setActiveModule(mod.id),
                                    className: `flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all ${
                                        activeModule === mod.id 
                                            ? 'bg-blue-600 text-white shadow-lg' 
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                    }`
                                }, [
                                    React.createElement('span', { key: 'icon' }, mod.icon),
                                    React.createElement('span', { key: 'label', className: 'hidden sm:inline' }, mod.label)
                                ])
                            )
                        )
                    ])
                ]);
            };

            // Dashboard Module
            const Dashboard = () => {
                const stats = [
                    { label: 'Active Quotes', value: '23', change: '12', icon: '🧮', color: 'blue' },
                    { label: 'GunBroker Listings', value: '247', change: '8', icon: '🌐', color: 'green' },
                    { label: 'Monthly Revenue', value: '$18,750', change: '15', icon: '💰', color: 'purple' },
                    { label: 'Form 4473s', value: '67', change: '22', icon: '📋', color: 'orange' }
                ];

                return React.createElement('div', { className: 'space-y-6' }, [
                    React.createElement('div', { key: 'stats', className: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6' }, 
                        stats.map((stat, index) => 
                            React.createElement('div', { 
                                key: index, 
                                className: 'bg-white p-6 rounded-xl border border-gray-200 shadow-sm' 
                            }, [
                                React.createElement('div', { key: 'content', className: 'flex items-center justify-between' }, [
                                    React.createElement('div', { key: 'info' }, [
                                        React.createElement('p', { key: 'label', className: 'text-sm text-gray-600 mb-1' }, stat.label),
                                        React.createElement('p', { key: 'value', className: 'text-2xl font-bold text-gray-900' }, stat.value),
                                        React.createElement('p', { key: 'change', className: 'text-sm text-green-600 mt-1' }, `+${stat.change}%`)
                                    ]),
                                    React.createElement('div', { 
                                        key: 'icon', 
                                        className: `w-12 h-12 bg-${stat.color}-100 rounded-full flex items-center justify-center text-2xl` 
                                    }, stat.icon)
                                ])
                            ])
                        )
                    ),
                    React.createElement('div', { key: 'content', className: 'grid grid-cols-1 lg:grid-cols-2 gap-6' }, [
                        React.createElement('div', { key: 'activity', className: 'bg-white p-6 rounded-xl border border-gray-200' }, [
                            React.createElement('h3', { key: 'title', className: 'text-lg font-semibold text-gray-900 mb-4' }, 'Recent Activity'),
                            React.createElement('div', { key: 'list', className: 'space-y-4' }, [
                                { type: 'quote', desc: 'Quote #Q2025-001 sent to John Smith', time: '2 min ago' },
                                { type: 'listing', desc: 'New GunBroker listing: Glock 19', time: '15 min ago' },
                                { type: 'form', desc: 'Form 4473 completed for Sarah Davis', time: '1 hour ago' }
                            ].map((activity, index) => 
                                React.createElement('div', { 
                                    key: index, 
                                    className: 'flex items-center gap-3 p-3 bg-gray-50 rounded-lg' 
                                }, [
                                    React.createElement('div', { key: 'dot', className: 'w-2 h-2 bg-blue-500 rounded-full' }),
                                    React.createElement('div', { key: 'content', className: 'flex-1' }, [
                                        React.createElement('p', { key: 'desc', className: 'text-sm text-gray-900' }, activity.desc),
                                        React.createElement('p', { key: 'time', className: 'text-xs text-gray-500' }, activity.time)
                                    ])
                                ])
                            ))
                        ]),
                        React.createElement('div', { key: 'apis', className: 'bg-white p-6 rounded-xl border border-gray-200' }, [
                            React.createElement('h3', { key: 'title', className: 'text-lg font-semibold text-gray-900 mb-4' }, 'API Status'),
                            React.createElement('div', { key: 'list', className: 'space-y-3' }, 
                                Object.entries(apiSettings).map(([api, config]) => 
                                    React.createElement('div', { 
                                        key: api, 
                                        className: 'flex items-center justify-between p-3 bg-gray-50 rounded-lg' 
                                    }, [
                                        React.createElement('div', { key: 'info', className: 'flex items-center gap-3' }, [
                                            React.createElement('div', { 
                                                key: 'status',
                                                className: `w-3 h-3 rounded-full ${config.enabled ? 'bg-green-500' : 'bg-gray-400'}` 
                                            }),
                                            React.createElement('span', { key: 'name', className: 'font-medium capitalize' }, api)
                                        ]),
                                        React.createElement('span', { 
                                            key: 'text',
                                            className: `text-sm ${config.enabled ? 'text-green-600' : 'text-gray-500'}` 
                                        }, config.enabled ? 'Connected' : 'Disabled')
                                    ])
                                )
                            )
                        ])
                    ])
                ]);
            };

            // Quote Generator Module
            const QuoteGenerator = () => {
                const [searchTerm, setSearchTerm] = React.useState('');
                const [selectedProduct, setSelectedProduct] = React.useState(null);
                const [selectedDistributor, setSelectedDistributor] = React.useState(null);
                const [customer, setCustomer] = React.useState({ name: '', email: '', phone: '' });

                const filteredProducts = searchTerm ? 
                    mockData.products.filter(product =>
                        product.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                        product.manufacturer.toLowerCase().includes(searchTerm.toLowerCase()) ||
                        product.sku.toLowerCase().includes(searchTerm.toLowerCase())
                    ) : [];

                return React.createElement('div', { className: 'space-y-6' }, [
                    React.createElement('div', { key: 'search', className: 'bg-white p-6 rounded-xl border border-gray-200' }, [
                        React.createElement('h2', { key: 'title', className: 'text-xl font-semibold text-gray-900 mb-4' }, 'Product Search'),
                        React.createElement('div', { key: 'input', className: 'relative' }, [
                            React.createElement('span', { 
                                key: 'icon',
                                className: 'absolute left-3 top-3 text-gray-400' 
                            }, '🔍'),
                            React.createElement('input', {
                                key: 'field',
                                type: 'text',
                                placeholder: 'Search products by name, manufacturer, or SKU...',
                                value: searchTerm,
                                onChange: (e) => setSearchTerm(e.target.value),
                                className: 'w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
                            })
                        ])
                    ]),

                    searchTerm && filteredProducts.length > 0 && React.createElement('div', { key: 'results', className: 'bg-white p-6 rounded-xl border border-gray-200' }, [
                        React.createElement('h3', { key: 'title', className: 'text-lg font-semibold text-gray-900 mb-4' }, 
                            `Search Results (${filteredProducts.length})`
                        ),
                        React.createElement('div', { key: 'grid', className: 'grid grid-cols-1 md:grid-cols-2 gap-4' }, 
                            filteredProducts.map(product => 
                                React.createElement('div', {
                                    key: product.id,
                                    className: `p-4 border rounded-lg cursor-pointer transition-all ${
                                        selectedProduct?.id === product.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'
                                    }`,
                                    onClick: () => setSelectedProduct(product)
                                }, [
                                    React.createElement('div', { key: 'image', className: 'w-full h-32 bg-gray-200 rounded mb-3 flex items-center justify-center' }, '📷'),
                                    React.createElement('h4', { key: 'name', className: 'font-medium text-gray-900' }, product.name),
                                    React.createElement('p', { key: 'manufacturer', className: 'text-sm text-gray-600' }, product.manufacturer),
                                    React.createElement('div', { key: 'details', className: 'mt-2 text-sm text-gray-500' }, [
                                        React.createElement('div', { key: 'sku' }, `SKU: ${product.sku}`),
                                        React.createElement('div', { key: 'msrp' }, `MSRP: $${product.msrp.toFixed(2)}`),
                                        React.createElement('div', { key: 'best' }, `Best: $${Math.min(...product.distributors.map(d => d.price)).toFixed(2)}`)
                                    ])
                                ])
                            )
                        )
                    ]),

                    selectedProduct && React.createElement('div', { key: 'distributors', className: 'bg-white p-6 rounded-xl border border-gray-200' }, [
                        React.createElement('h3', { key: 'title', className: 'text-lg font-semibold text-gray-900 mb-4' }, 
                            `Distributor Pricing for ${selectedProduct.name}`
                        ),
                        React.createElement('div', { key: 'list', className: 'space-y-3' }, 
                            selectedProduct.distributors.map((distributor, index) => 
                                React.createElement('div', {
                                    key: index,
                                    className: `p-4 border rounded-lg cursor-pointer transition-all ${
                                        selectedDistributor?.name === distributor.name ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'
                                    }`,
                                    onClick: () => setSelectedDistributor(distributor)
                                }, [
                                    React.createElement('div', { key: 'content', className: 'flex justify-between items-start' }, [
                                        React.createElement('div', { key: 'info' }, [
                                            React.createElement('div', { key: 'name', className: 'font-medium' }, distributor.name),
                                            React.createElement('div', { key: 'stock', className: 'text-sm text-gray-600' }, 
                                                `Stock: ${distributor.stock} • ETA: ${distributor.eta}`
                                            ),
                                            React.createElement('div', { key: 'rating', className: 'text-sm text-gray-500' }, 
                                                `Rating: ${distributor.rating}/5 • Reliability: ${distributor.reliability}%`
                                            )
                                        ]),
                                        React.createElement('div', { key: 'pricing', className: 'text-right' }, [
                                            React.createElement('div', { key: 'price', className: 'text-lg font-semibold text-green-600' }, 
                                                `$${distributor.price.toFixed(2)}`
                                            ),
                                            React.createElement('div', { key: 'shipping', className: 'text-sm text-gray-500' }, distributor.shipping)
                                        ])
                                    ])
                                ])
                            )
                        )
                    ]),

                    React.createElement('div', { key: 'form', className: 'grid grid-cols-1 lg:grid-cols-2 gap-6' }, [
                        React.createElement('div', { key: 'customer', className: 'bg-white p-6 rounded-xl border border-gray-200' }, [
                            React.createElement('h3', { key: 'title', className: 'text-lg font-semibold text-gray-900 mb-4' }, 'Customer Information'),
                            React.createElement('div', { key: 'fields', className: 'space-y-4' }, [
                                React.createElement('input', {
                                    key: 'name',
                                    type: 'text',
                                    placeholder: 'Customer Name',
                                    value: customer.name,
                                    onChange: (e) => setCustomer({...customer, name: e.target.value}),
                                    className: 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500'
                                }),
                                React.createElement('input', {
                                    key: 'email',
                                    type: 'email',
                                    placeholder: 'Email Address',
                                    value: customer.email,
                                    onChange: (e) => setCustomer({...customer, email: e.target.value}),
                                    className: 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500'
                                }),
                                React.createElement('input', {
                                    key: 'phone',
                                    type: 'tel',
                                    placeholder: 'Phone Number',
                                    value: customer.phone,
                                    onChange: (e) => setCustomer({...customer, phone: e.target.value}),
                                    className: 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500'
                                })
                            ])
                        ]),
                        React.createElement('div', { key: 'actions', className: 'bg-white p-6 rounded-xl border border-gray-200' }, [
                            React.createElement('h3', { key: 'title', className: 'text-lg font-semibold text-gray-900 mb-4' }, 'Quote Actions'),
                            React.createElement('div', { key: 'buttons', className: 'space-y-3' }, [
                                React.createElement('button', {
                                    key: 'generate',
                                    className: 'w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center justify-center gap-2',
                                    onClick: () => window.FFLBroPlatform.api.createQuote({customer, product: selectedProduct, distributor: selectedDistributor})
                                }, ['🧮', ' Generate Quote']),
                                React.createElement('button', {
                                    key: 'email',
                                    className: 'w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center justify-center gap-2'
                                }, ['📧', ' Email Quote']),
                                React.createElement('button', {
                                    key: 'pdf',
                                    className: 'w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center justify-center gap-2'
                                }, ['📄', ' PDF Quote'])
                            ])
                        ])
                    ])
                ]);
            };

            // Simplified modules for other components
            const SimpleModule = ({ title, icon, description, features }) => (
                React.createElement('div', { className: 'space-y-6' }, [
                    React.createElement('div', { key: 'header', className: 'bg-white p-6 rounded-xl border border-gray-200' }, [
                        React.createElement('div', { key: 'title-section', className: 'flex items-center gap-3 mb-4' }, [
                            React.createElement('div', { key: 'icon', className: 'text-3xl' }, icon),
                            React.createElement('div', { key: 'text' }, [
                                React.createElement('h2', { key: 'title', className: 'text-xl font-semibold text-gray-900' }, title),
                                React.createElement('p', { key: 'desc', className: 'text-sm text-gray-600' }, description)
                            ])
                        ]),
                        React.createElement('div', { key: 'status', className: 'p-4 bg-blue-50 rounded-lg' }, [
                            React.createElement('p', { key: 'message', className: 'text-blue-800 font-medium' }, 
                                `${title} module is ready for integration`
                            ),
                            React.createElement('p', { key: 'instructions', className: 'text-blue-700 text-sm mt-1' }, 
                                mockMode ? 'Currently showing mock data for development' : 'Connect APIs to enable full functionality'
                            )
                        ])
                    ]),
                    features && React.createElement('div', { key: 'features', className: 'bg-white p-6 rounded-xl border border-gray-200' }, [
                        React.createElement('h3', { key: 'features-title', className: 'text-lg font-semibold text-gray-900 mb-4' }, 'Key Features'),
                        React.createElement('div', { key: 'features-grid', className: 'grid grid-cols-1 md:grid-cols-2 gap-4' }, 
                            features.map((feature, index) => 
                                React.createElement('div', { 
                                    key: index, 
                                    className: 'p-4 border border-gray-200 rounded-lg' 
                                }, [
                                    React.createElement('h4', { key: 'feature-title', className: 'font-medium text-gray-900 mb-2' }, feature.title),
                                    React.createElement('p', { key: 'feature-desc', className: 'text-sm text-gray-600' }, feature.description)
                                ])
                            )
                        )
                    ])
                ])
            );

            // Render the appropriate module based on activeModule
            const renderActiveModule = () => {
                switch (activeModule) {
                    case 'dashboard':
                        return React.createElement(Dashboard);
                    case 'quotes':
                        return React.createElement(QuoteGenerator);
                    case 'gunbroker':
                        return React.createElement(SimpleModule, {
                            title: 'GunBroker Marketplace',
                            icon: '🌐',
                            description: 'Manage your GunBroker listings and marketplace presence',
                            features: [
                                { title: 'Listing Management', description: 'Create and manage marketplace listings' },
                                { title: 'Bid Tracking', description: 'Monitor bids and performance analytics' },
                                { title: 'Inventory Sync', description: 'Automatic inventory synchronization' },
                                { title: 'Sales Analytics', description: 'Track sales performance and trends' }
                            ]
                        });
                    case 'lipseys':
                        return React.createElement(SimpleModule, {
                            title: 'Lipseys Distributor',
                            icon: '🏢',
                            description: 'Access Lipseys catalog and manage distributor orders',
                            features: [
                                { title: 'Product Catalog', description: '45,000+ products available' },
                                { title: 'Real-time Pricing', description: 'Current pricing and availability' },
                                { title: 'Order Management', description: 'Place and track distributor orders' },
                                { title: 'Inventory Updates', description: 'Automatic stock level updates' }
                            ]
                        });
                    case 'inventory':
                        return React.createElement(SimpleModule, {
                            title: 'Inventory Management',
                            icon: '📦',
                            description: 'Track inventory across all distributors and platforms',
                            features: [
                                { title: 'Multi-Distributor', description: 'Track inventory from all sources' },
                                { title: 'Stock Alerts', description: 'Low stock and reorder notifications' },
                                { title: 'Sync Status', description: 'Real-time synchronization status' },
                                { title: 'Performance Analytics', description: 'Track inventory performance' }
                            ]
                        });
                    case 'form4473':
                        return React.createElement(SimpleModule, {
                            title: 'Digital Form 4473',
                            icon: '📋',
                            description: 'ATF-compliant digital form processing',
                            features: [
                                { title: 'Digital Processing', description: 'Paperless form completion' },
                                { title: 'Digital Signatures', description: 'Secure signature capture' },
                                { title: 'NICS Integration', description: 'Background check processing' },
                                { title: 'Compliance Tracking', description: 'Audit trail and reporting' }
                            ]
                        });
                    case 'settings':
                        return React.createElement(SimpleModule, {
                            title: 'Platform Settings',
                            icon: '⚙️',
                            description: 'Configure business settings and API integrations',
                            features: [
                                { title: 'Business Info', description: 'Configure FFL business details' },
                                { title: 'API Integration', description: 'Connect to distributor APIs' },
                                { title: 'Pricing Rules', description: 'Set markup and fee structures' },
                                { title: 'System Settings', description: 'Platform configuration options' }
                            ]
                        });
                    default:
                        return React.createElement(Dashboard);
                }
            };

            // Main component render
            if (customerMode && !isAdmin) {
                // Customer-facing simplified interface
                return React.createElement('div', { className: 'min-h-screen bg-gray-50' }, [
                    React.createElement('div', { key: 'header', className: 'bg-white border-b border-gray-200 p-4' }, [
                        React.createElement('div', { key: 'container', className: 'max-w-4xl mx-auto' }, [
                            React.createElement('h1', { key: 'title', className: 'text-2xl font-bold text-gray-900' }, 
                                mockData.businessSettings.name
                            ),
                            React.createElement('p', { key: 'subtitle', className: 'text-gray-600' }, 'Request a Quote')
                        ])
                    ]),
                    React.createElement('div', { key: 'content', className: 'max-w-4xl mx-auto p-6' }, 
                        React.createElement(QuoteGenerator)
                    )
                ]);
            }

            // Full admin interface
            return React.createElement('div', { className: 'min-h-screen bg-gray-50' }, [
                React.createElement(Navigation, { key: 'nav' }),
                React.createElement('div', { key: 'content', className: 'max-w-7xl mx-auto px-4 py-6' }, 
                    renderActiveModule()
                )
            ]);
        };

        // Mount the component
        ReactDOM.render(React.createElement(FFLBroPlatformComponent), container);
    };

    // Update the component loading function to use the actual component
    const originalLoadReactComponent = window.FFLBroPlatform.loadReactComponent;
    window.FFLBroPlatform.loadReactComponent = function(container, module, isAdmin, customerMode) {
        // Add a small delay to ensure all dependencies are loaded
        setTimeout(() => {
            window.FFLBroPlatform.loadActualComponent(container, module, isAdmin, customerMode);
        }, 100);
    };

})(jQuery);

// Ensure this script runs after the main platform script
if (window.FFLBroPlatform) {
    // Platform is already loaded, initialize immediately
    jQuery(document).ready(() => {
        window.FFLBroPlatform.loadComponents();
    });
} else {
    // Wait for platform to load
    jQuery(document).ready(() => {
        const checkPlatform = () => {
            if (window.FFLBroPlatform) {
                window.FFLBroPlatform.loadComponents();
            } else {
                setTimeout(checkPlatform, 100);
            }
        };
        checkPlatform();
    });
}