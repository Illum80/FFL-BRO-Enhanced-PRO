/**
 * FFL-BRO Platform JavaScript Loader
 * Handles loading and mounting of the React platform component
 */

(function($) {
    'use strict';

    // Platform initialization
    const FFLBroPlatform = {
        
        // Configuration
        config: {
            debug: false,
            mockMode: true,
            version: '4.0.0'
        },

        // Initialize the platform
        init: function() {
            this.config.debug = window.fflBroData?.settings?.debug_mode || false;
            this.config.mockMode = window.fflBroData?.mockMode || true;
            
            this.log('FFL-BRO Platform initializing...');
            
            // Wait for React to be loaded
            this.waitForReact().then(() => {
                this.loadComponents();
            });
        },

        // Wait for React libraries to load
        waitForReact: function() {
            return new Promise((resolve) => {
                const checkReact = () => {
                    if (window.React && window.ReactDOM && window.Babel) {
                        this.log('React libraries loaded');
                        resolve();
                    } else {
                        setTimeout(checkReact, 100);
                    }
                };
                checkReact();
            });
        },

        // Load and mount components
        loadComponents: function() {
            // Admin interface
            const adminApp = document.getElementById('ffl-bro-admin-app');
            if (adminApp) {
                this.mountAdminApp(adminApp);
            }

            // Frontend platform
            const platformApp = document.getElementById('ffl-bro-platform-app');
            if (platformApp) {
                this.mountPlatformApp(platformApp);
            }

            // Quote generator shortcode
            const quoteGenerator = document.getElementById('ffl-bro-quote-generator');
            if (quoteGenerator) {
                this.mountQuoteGenerator(quoteGenerator);
            }

            // Form 4473 shortcode
            const form4473 = document.getElementById('ffl-bro-form4473');
            if (form4473) {
                this.mountForm4473(form4473);
            }
        },

        // Mount admin application
        mountAdminApp: function(container) {
            const module = container.dataset.module || 'dashboard';
            
            this.log('Mounting admin app with module:', module);
            
            // The React component code will be loaded from the first artifact
            // This is a placeholder for the actual component loading
            const element = React.createElement('div', {
                className: 'ffl-bro-admin-container'
            }, 'FFL-BRO Admin Loading...');
            
            ReactDOM.render(element, container);
            
            // Load the actual React component
            this.loadReactComponent(container, module, true);
        },

        // Mount platform application for frontend
        mountPlatformApp: function(container) {
            const module = container.dataset.module || 'dashboard';
            const customerMode = container.dataset.customerMode === 'true';
            
            this.log('Mounting platform app with module:', module, 'customer mode:', customerMode);
            
            this.loadReactComponent(container, module, false, customerMode);
        },

        // Mount quote generator
        mountQuoteGenerator: function(container) {
            const customerMode = container.dataset.customerMode === 'true';
            
            this.log('Mounting quote generator, customer mode:', customerMode);
            
            this.loadReactComponent(container, 'quotes', false, customerMode);
        },

        // Mount Form 4473
        mountForm4473: function(container) {
            this.log('Mounting Form 4473');
            
            this.loadReactComponent(container, 'form4473', false, false);
        },

        // Load React component using Babel transformation
        loadReactComponent: function(container, module, isAdmin, customerMode = false) {
            // This function would load the React component code from the artifacts
            // and transform it using Babel, then mount it to the container
            
            // For now, we'll create a placeholder
            const PlaceholderComponent = () => {
                return React.createElement('div', {
                    className: 'ffl-bro-loading'
                }, [
                    React.createElement('h2', { key: 'title' }, 'FFL-BRO Platform'),
                    React.createElement('p', { key: 'desc' }, `Module: ${module}`),
                    React.createElement('p', { key: 'mode' }, `Mode: ${isAdmin ? 'Admin' : 'Frontend'}`),
                    customerMode && React.createElement('p', { key: 'customer' }, 'Customer Mode: Enabled')
                ]);
            };

            ReactDOM.render(React.createElement(PlaceholderComponent), container);
        },

        // API helper functions
        api: {
            // Make AJAX request
            request: function(action, data) {
                return new Promise((resolve, reject) => {
                    $.ajax({
                        url: window.fflBroData.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'ffl_bro_' + action,
                            nonce: window.fflBroData.nonce,
                            ...data
                        },
                        success: function(response) {
                            if (response.success) {
                                resolve(response.data);
                            } else {
                                reject(response.data || 'Request failed');
                            }
                        },
                        error: function(xhr, status, error) {
                            reject(error);
                        }
                    });
                });
            },

            // Get products
            getProducts: function(search = '', category = '') {
                return this.request('get_products', { search, category });
            },

            // Create quote
            createQuote: function(quoteData) {
                return this.request('create_quote', { quote_data: quoteData });
            },

            // Sync inventory
            syncInventory: function(provider) {
                return this.request('sync_inventory', { provider });
            },

            // Process Form 4473
            processForm4473: function(formData) {
                return this.request('process_4473', { form_data: formData });
            }
        },

        // Utility functions
        log: function(...args) {
            if (this.config.debug) {
                console.log('[FFL-BRO]', ...args);
            }
        },

        error: function(...args) {
            console.error('[FFL-BRO]', ...args);
        }
    };

    // Global access
    window.FFLBroPlatform = FFLBroPlatform;

    // Initialize when document is ready
    $(document).ready(function() {
        FFLBroPlatform.init();
    });

    // Handle dynamic content loading
    $(document).on('DOMNodeInserted', function(e) {
        const target = e.target;
        if (target.nodeType === 1) { // Element node
            // Check for new FFL-BRO containers
            const containers = target.querySelectorAll ? 
                target.querySelectorAll('[id^="ffl-bro-"]') : [];
            
            containers.forEach(container => {
                if (!container.dataset.initialized) {
                    container.dataset.initialized = 'true';
                    FFLBroPlatform.loadComponents();
                }
            });
        }
    });

})(jQuery);

/**
 * React Component Factory
 * This would normally load the actual React components from the artifacts
 */
window.FFLBroComponents = {
    
    // Mock data for development
    mockData: {
        products: [
            {
                id: 1,
                sku: 'GLK-19-GEN5',
                name: 'Glock 19 Gen 5 9mm',
                manufacturer: 'Glock',
                msrp: 649.99,
                distributors: [
                    { name: 'RSR Group', price: 435.25, stock: 12, shipping: 'Free' },
                    { name: 'Lipseys', price: 438.90, stock: 15, shipping: 'Free over $500' },
                    { name: 'Davidson\'s', price: 442.80, stock: 8, shipping: '$12.95' }
                ]
            },
            {
                id: 2,
                sku: 'SIG-P320-C',
                name: 'SIG Sauer P320 Compact 9mm',
                manufacturer: 'SIG Sauer',
                msrp: 579.99,
                distributors: [
                    { name: 'RSR Group', price: 515.25, stock: 6, shipping: 'Free' },
                    { name: 'Lipseys', price: 508.90, stock: 9, shipping: '$9.95' },
                    { name: 'Sports South', price: 522.50, stock: 11, shipping: 'Free over $500' }
                ]
            }
        ],
        
        quotes: [
            {
                id: 1,
                quote_number: 'Q2025-001',
                customer_name: 'John Smith',
                customer_email: 'john@example.com',
                status: 'sent',
                total: 575.99,
                created_at: '2025-09-01'
            }
        ],
        
        gunbrokerListings: [
            {
                id: 'GB001',
                title: 'Glock 19 Gen5 - Like New',
                currentBid: 525.00,
                buyNow: 575.00,
                bids: 8,
                timeLeft: '2d 14h',
                views: 156,
                status: 'active'
            }
        ],
        
        form4473Records: [
            {
                id: 1,
                form_number: '4473-2025-001234',
                customer_name: 'John Smith',
                firearm: 'Glock G19 Gen5',
                status: 'completed',
                date: '2025-09-07'
            }
        ]
    },

    // Component factory functions
    createDashboard: function(props) {
        return React.createElement('div', {
            className: 'ffl-bro-dashboard'
        }, 'Dashboard Component - ' + (props.module || 'default'));
    },

    createQuoteGenerator: function(props) {
        return React.createElement('div', {
            className: 'ffl-bro-quotes'
        }, 'Quote Generator Component - ' + (props.customerMode ? 'Customer' : 'Admin'));
    },

    createGunBroker: function(props) {
        return React.createElement('div', {
            className: 'ffl-bro-gunbroker'
        }, 'GunBroker Integration Component');
    },

    createLipseys: function(props) {
        return React.createElement('div', {
            className: 'ffl-bro-lipseys'
        }, 'Lipseys Integration Component');
    },

    createForm4473: function(props) {
        return React.createElement('div', {
            className: 'ffl-bro-form4473'
        }, 'Form 4473 Digital Processing Component');
    },

    createInventory: function(props) {
        return React.createElement('div', {
            className: 'ffl-bro-inventory'
        }, 'Inventory Management Component');
    },

    createSettings: function(props) {
        return React.createElement('div', {
            className: 'ffl-bro-settings'
        }, 'Settings Component');
    }
};

/**
 * Settings Management
 */
window.FFLBroSettings = {
    
    // Get setting value
    get: function(key, defaultValue = null) {
        const settings = window.fflBroData?.settings || {};
        return settings[key] || defaultValue;
    },

    // Set setting value (saves to WordPress options)
    set: function(key, value) {
        return window.FFLBroPlatform.api.request('update_setting', {
            key: key,
            value: value
        });
    },

    // Get API credentials
    getApiCredentials: function(provider) {
        return this.get(`api_${provider}`, {});
    },

    // Set API credentials
    setApiCredentials: function(provider, credentials) {
        return this.set(`api_${provider}`, credentials);
    },

    // Check if mock mode is enabled
    isMockMode: function() {
        return this.get('mock_mode', true);
    },

    // Toggle mock mode
    toggleMockMode: function() {
        const currentMode = this.isMockMode();
        return this.set('mock_mode', !currentMode);
    }
};

/**
 * Development utilities
 */
if (window.FFLBroPlatform?.config?.debug) {
    window.FFLBroDebug = {
        
        // Reload components
        reload: function() {
            window.FFLBroPlatform.loadComponents();
        },

        // Get mock data
        getMockData: function() {
            return window.FFLBroComponents.mockData;
        },

        // Test API endpoint
        testApi: function(action, data = {}) {
            return window.FFLBroPlatform.api.request(action, data);
        },

        // Component mounting test
        testMount: function(containerId, module) {
            const container = document.getElementById(containerId);
            if (container) {
                window.FFLBroPlatform.loadReactComponent(container, module, false);
            }
        }
    };
}