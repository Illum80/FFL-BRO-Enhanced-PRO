<?php
/**
 * Plugin Name: FFL-BRO Professional Quote Generator
 * Description: Professional quote generator with modern UI matching the TSX design
 * Version: 1.0.0
 * Author: FFL-BRO
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
        // Enqueue React and modern styling
        wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.production.min.js', [], '18.0.0', true);
        wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', ['react'], '18.0.0', true);
        
        // Tailwind CSS for modern styling
        wp_enqueue_style('tailwind-css', 'https://cdn.tailwindcss.com', [], null);
        
        // Lucide React icons via CDN
        wp_enqueue_script('lucide-react', 'https://unpkg.com/lucide-react@latest/dist/umd/lucide-react.js', ['react'], null, true);
        
        // Custom styles
        wp_add_inline_style('tailwind-css', $this->get_custom_styles());
        
        // Localize script for AJAX
        wp_localize_script('react', 'fflBroAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ffl_bro_nonce')
        ]);
    }
    
    public function get_custom_styles() {
        return "
        .ffl-bro-container {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .ffl-bro-sidebar {
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
        }
        
        .ffl-bro-card {
            transition: all 0.2s ease-in-out;
            border: 1px solid #e5e7eb;
        }
        
        .ffl-bro-card:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .ffl-bro-btn-primary {
            background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%);
            transition: all 0.2s ease-in-out;
        }
        
        .ffl-bro-btn-primary:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-1px);
        }
        
        .ffl-bro-input {
            transition: all 0.2s ease-in-out;
            border: 1px solid #d1d5db;
        }
        
        .ffl-bro-input:focus {
            border-color: #ea580c;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1);
        }
        
        .ffl-bro-kpi-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #f3f4f6;
            transition: all 0.2s ease-in-out;
        }
        
        .ffl-bro-kpi-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
        ";
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'FFL-BRO Pro',
            'FFL-BRO Pro',
            'manage_options',
            'ffl-bro-pro',
            [$this, 'render_admin_page'],
            'dashicons-shield-alt',
            30
        );
        
        add_submenu_page(
            'ffl-bro-pro',
            'Quote Generator',
            'Quote Generator',
            'manage_options',
            'ffl-bro-quotes',
            [$this, 'render_quotes_admin']
        );
    }
    
    public function render_admin_page() {
        echo '<div id="ffl-bro-admin-dashboard"></div>';
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof React !== "undefined") {
                const adminApp = React.createElement(FFLBROAdminApp);
                ReactDOM.render(adminApp, document.getElementById("ffl-bro-admin-dashboard"));
            }
        });
        </script>';
    }
    
    public function render_quotes_admin() {
        echo '<div id="ffl-bro-quote-admin"></div>';
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof React !== "undefined") {
                const quoteApp = React.createElement(FFLBROQuoteAdmin);
                ReactDOM.render(quoteApp, document.getElementById("ffl-bro-quote-admin"));
            }
        });
        </script>';
    }
    
    public function render_dashboard($atts) {
        $atts = shortcode_atts(['type' => 'full'], $atts);
        
        ob_start();
        ?>
        <div id="ffl-bro-dashboard-<?php echo esc_attr(uniqid()); ?>" class="ffl-bro-container"></div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof React !== 'undefined' && typeof ReactDOM !== 'undefined') {
                const dashboardElement = document.querySelector('[id^="ffl-bro-dashboard-"]');
                if (dashboardElement) {
                    const dashboard = React.createElement(FFLBRODashboard, {
                        type: '<?php echo esc_js($atts['type']); ?>'
                    });
                    ReactDOM.render(dashboard, dashboardElement);
                }
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    public function render_quote_generator($atts) {
        $atts = shortcode_atts(['mode' => 'full'], $atts);
        
        ob_start();
        ?>
        <div id="ffl-bro-quote-generator-<?php echo esc_attr(uniqid()); ?>" class="ffl-bro-container"></div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof React !== 'undefined' && typeof ReactDOM !== 'undefined') {
                const quoteElement = document.querySelector('[id^="ffl-bro-quote-generator-"]');
                if (quoteElement) {
                    const quoteGenerator = React.createElement(FFLBROQuoteGenerator, {
                        mode: '<?php echo esc_js($atts['mode']); ?>'
                    });
                    ReactDOM.render(quoteGenerator, quoteElement);
                }
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    public function handle_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'ffl_bro_nonce')) {
            wp_die('Security check failed');
        }
        
        $action_type = sanitize_text_field($_POST['action_type']);
        
        switch ($action_type) {
            case 'get_dashboard_data':
                $this->get_dashboard_data();
                break;
            case 'create_quote':
                $this->create_quote();
                break;
            case 'get_quotes':
                $this->get_quotes();
                break;
            case 'search_products':
                $this->search_products();
                break;
            default:
                wp_send_json_error('Invalid action');
        }
    }
    
    private function get_dashboard_data() {
        // Mock data for now - replace with real database queries
        $data = [
            'kpis' => [
                'orders_today' => 47,
                'orders_month' => 1284,
                'revenue_today' => 18450.75,
                'revenue_month' => 324680.90,
                'active_leads' => 156,
                'conversion_rate' => 23.4,
                'pending_transfers' => 23,
                'catalog_items' => 45820,
                'system_health' => 98.7,
                'alerts_count' => 3,
                'quotes_pending' => 12,
                'forms_submitted_today' => 8
            ],
            'recent_activity' => [
                ['id' => 1, 'type' => 'order', 'message' => 'New order #1247 from Sarah Mitchell', 'time' => '2m ago', 'status' => 'success'],
                ['id' => 2, 'type' => 'sync', 'message' => 'Catalog sync completed successfully', 'time' => '15m ago', 'status' => 'success'],
                ['id' => 3, 'type' => 'alert', 'message' => 'Low inventory warning: AR-15 Lower Receivers', 'time' => '1h ago', 'status' => 'warning']
            ]
        ];
        
        wp_send_json_success($data);
    }
    
    private function create_quote() {
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $items = json_decode(stripslashes($_POST['items']), true);
        
        // Here you would save to database
        $quote_id = 'QT-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        wp_send_json_success([
            'quote_id' => $quote_id,
            'message' => 'Quote created successfully'
        ]);
    }
    
    private function get_quotes() {
        // Mock quotes data
        $quotes = [
            [
                'id' => 'QT-2025-001',
                'customer' => 'Sarah Mitchell',
                'total' => 2450.00,
                'status' => 'pending',
                'created' => '2025-09-15'
            ],
            [
                'id' => 'QT-2025-002',
                'customer' => 'David Thompson',
                'total' => 890.00,
                'status' => 'approved',
                'created' => '2025-09-14'
            ]
        ];
        
        wp_send_json_success($quotes);
    }
    
    private function search_products() {
        $search_term = sanitize_text_field($_POST['search_term']);
        
        // Mock product search
        $products = [
            [
                'id' => 1,
                'name' => 'Glock 19 Gen 5',
                'sku' => 'GLK-19-G5',
                'price' => 549.99,
                'manufacturer' => 'Glock'
            ],
            [
                'id' => 2,
                'name' => 'Smith & Wesson M&P 15',
                'sku' => 'SW-MP15',
                'price' => 899.99,
                'manufacturer' => 'Smith & Wesson'
            ]
        ];
        
        wp_send_json_success($products);
    }
}

// Initialize the plugin
new FFLBROProQuoteGenerator();

// Add the React components inline
add_action('wp_footer', function() {
    if (is_admin() || (!is_admin() && (has_shortcode(get_post()->post_content, 'ffl_bro_quote_generator') || has_shortcode(get_post()->post_content, 'ffl_bro_dashboard')))) {
        ?>
        <script>
        // Professional Quote Generator Component
        const FFLBROQuoteGenerator = ({ mode = 'full' }) => {
            const [quotes, setQuotes] = React.useState([]);
            const [loading, setLoading] = React.useState(true);
            const [showForm, setShowForm] = React.useState(false);
            const [selectedCustomer, setSelectedCustomer] = React.useState('');
            const [searchTerm, setSearchTerm] = React.useState('');
            const [quoteItems, setQuoteItems] = React.useState([]);
            
            React.useEffect(() => {
                loadQuotes();
            }, []);
            
            const loadQuotes = async () => {
                try {
                    const response = await fetch(fflBroAjax.ajaxurl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'ffl_bro_quote',
                            action_type: 'get_quotes',
                            nonce: fflBroAjax.nonce
                        })
                    });
                    const data = await response.json();
                    if (data.success) {
                        setQuotes(data.data);
                    }
                } catch (error) {
                    console.error('Error loading quotes:', error);
                } finally {
                    setLoading(false);
                }
            };
            
            const createQuote = async (formData) => {
                try {
                    const response = await fetch(fflBroAjax.ajaxurl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'ffl_bro_quote',
                            action_type: 'create_quote',
                            customer_name: formData.customerName,
                            items: JSON.stringify(formData.items),
                            nonce: fflBroAjax.nonce
                        })
                    });
                    const data = await response.json();
                    if (data.success) {
                        loadQuotes();
                        setShowForm(false);
                        alert('Quote created successfully!');
                    }
                } catch (error) {
                    console.error('Error creating quote:', error);
                }
            };
            
            if (loading) {
                return React.createElement('div', { className: 'p-8' },
                    React.createElement('div', { className: 'animate-pulse space-y-4' },
                        React.createElement('div', { className: 'h-6 bg-gray-200 rounded w-1/4' }),
                        React.createElement('div', { className: 'h-4 bg-gray-200 rounded w-1/2' }),
                        React.createElement('div', { className: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-6' },
                            [1,2,3].map(i => 
                                React.createElement('div', { key: i, className: 'h-32 bg-gray-200 rounded-lg' })
                            )
                        )
                    )
                );
            }
            
            return React.createElement('div', { className: 'space-y-8 p-8 bg-gray-50 min-h-screen' },
                // Header
                React.createElement('div', { className: 'flex items-center justify-between' },
                    React.createElement('div', null,
                        React.createElement('h1', { className: 'text-3xl font-bold text-gray-900' }, 'Quote Generator'),
                        React.createElement('p', { className: 'text-gray-600 mt-1' }, 'Create professional quotes for customers')
                    ),
                    React.createElement('button', { 
                        className: 'flex items-center gap-2 px-4 py-2 ffl-bro-btn-primary text-white rounded-lg hover:shadow-lg transition-all',
                        onClick: () => setShowForm(!showForm)
                    },
                        React.createElement('span', { className: 'text-sm' }, showForm ? '✕' : '+'),
                        showForm ? 'Cancel' : 'New Quote'
                    )
                ),
                
                // Quote Builder Form
                showForm && React.createElement('div', { className: 'bg-white rounded-xl shadow-sm border border-gray-100 p-6 ffl-bro-card' },
                    React.createElement('h3', { className: 'text-lg font-semibold text-gray-900 mb-4' }, 'Quote Builder'),
                    React.createElement('div', { className: 'grid grid-cols-1 md:grid-cols-2 gap-6' },
                        React.createElement('div', { className: 'space-y-2' },
                            React.createElement('label', { className: 'block text-sm font-medium text-gray-700' }, 'Customer Name *'),
                            React.createElement('div', { className: 'relative' },
                                React.createElement('div', { className: 'absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none' },
                                    React.createElement('span', { className: 'text-gray-400 text-sm' }, '👤')
                                ),
                                React.createElement('input', {
                                    type: 'text',
                                    className: 'w-full pl-10 pr-4 py-3 ffl-bro-input rounded-lg',
                                    placeholder: 'Enter customer name...',
                                    value: selectedCustomer,
                                    onChange: (e) => setSelectedCustomer(e.target.value)
                                })
                            )
                        ),
                        React.createElement('div', { className: 'space-y-2' },
                            React.createElement('label', { className: 'block text-sm font-medium text-gray-700' }, 'Search Products'),
                            React.createElement('div', { className: 'relative' },
                                React.createElement('div', { className: 'absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none' },
                                    React.createElement('span', { className: 'text-gray-400 text-sm' }, '🔍')
                                ),
                                React.createElement('input', {
                                    type: 'text',
                                    className: 'w-full pl-10 pr-4 py-3 ffl-bro-input rounded-lg',
                                    placeholder: 'Search by name, SKU, or manufacturer...',
                                    value: searchTerm,
                                    onChange: (e) => setSearchTerm(e.target.value)
                                })
                            )
                        )
                    ),
                    React.createElement('div', { className: 'mt-6 flex justify-end gap-3' },
                        React.createElement('button', { 
                            className: 'px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors',
                            onClick: () => setShowForm(false)
                        }, 'Cancel'),
                        React.createElement('button', { 
                            className: 'px-6 py-2 ffl-bro-btn-primary text-white rounded-lg hover:shadow-lg transition-all',
                            onClick: () => createQuote({ customerName: selectedCustomer, items: quoteItems })
                        }, 'Create Quote')
                    )
                ),
                
                // Quotes List
                React.createElement('div', { className: 'bg-white rounded-xl shadow-sm border border-gray-100 ffl-bro-card' },
                    React.createElement('div', { className: 'p-6 border-b border-gray-100' },
                        React.createElement('h3', { className: 'text-lg font-semibold text-gray-900' }, 'Recent Quotes')
                    ),
                    React.createElement('div', { className: 'p-6' },
                        quotes.length === 0 ? 
                            React.createElement('div', { className: 'text-center py-12' },
                                React.createElement('div', { className: 'text-gray-400 text-4xl mb-4' }, '📄'),
                                React.createElement('h3', { className: 'text-lg font-medium text-gray-900 mb-2' }, 'No quotes yet'),
                                React.createElement('p', { className: 'text-gray-600' }, 'Create your first quote to get started')
                            ) :
                            React.createElement('div', { className: 'overflow-x-auto' },
                                React.createElement('table', { className: 'w-full' },
                                    React.createElement('thead', null,
                                        React.createElement('tr', { className: 'border-b border-gray-200' },
                                            React.createElement('th', { className: 'text-left py-3 px-4 font-medium text-gray-700' }, 'Quote ID'),
                                            React.createElement('th', { className: 'text-left py-3 px-4 font-medium text-gray-700' }, 'Customer'),
                                            React.createElement('th', { className: 'text-left py-3 px-4 font-medium text-gray-700' }, 'Total'),
                                            React.createElement('th', { className: 'text-left py-3 px-4 font-medium text-gray-700' }, 'Status'),
                                            React.createElement('th', { className: 'text-left py-3 px-4 font-medium text-gray-700' }, 'Date'),
                                            React.createElement('th', { className: 'text-left py-3 px-4 font-medium text-gray-700' }, 'Actions')
                                        )
                                    ),
                                    React.createElement('tbody', null,
                                        quotes.map((quote, index) =>
                                            React.createElement('tr', { key: index, className: 'border-b border-gray-100 hover:bg-gray-50' },
                                                React.createElement('td', { className: 'py-4 px-4 font-medium text-blue-600' }, quote.id),
                                                React.createElement('td', { className: 'py-4 px-4' }, quote.customer),
                                                React.createElement('td', { className: 'py-4 px-4 font-semibold' }, '$' + quote.total.toFixed(2)),
                                                React.createElement('td', { className: 'py-4 px-4' },
                                                    React.createElement('span', { 
                                                        className: `px-2 py-1 rounded-full text-xs font-medium ${
                                                            quote.status === 'approved' ? 'bg-green-100 text-green-800' :
                                                            quote.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                            'bg-red-100 text-red-800'
                                                        }`
                                                    }, quote.status.charAt(0).toUpperCase() + quote.status.slice(1))
                                                ),
                                                React.createElement('td', { className: 'py-4 px-4 text-gray-600' }, quote.created),
                                                React.createElement('td', { className: 'py-4 px-4' },
                                                    React.createElement('div', { className: 'flex gap-2' },
                                                        React.createElement('button', { className: 'text-blue-600 hover:text-blue-800 text-sm' }, 'View'),
                                                        React.createElement('button', { className: 'text-green-600 hover:text-green-800 text-sm' }, 'Edit'),
                                                        React.createElement('button', { className: 'text-red-600 hover:text-red-800 text-sm' }, 'Delete')
                                                    )
                                                )
                                            )
                                        )
                                    )
                                )
                            )
                    )
                )
            );
        };
        
        // Dashboard Component
        const FFLBRODashboard = ({ type = 'full' }) => {
            const [dashboardData, setDashboardData] = React.useState(null);
            const [loading, setLoading] = React.useState(true);
            
            React.useEffect(() => {
                loadDashboardData();
            }, []);
            
            const loadDashboardData = async () => {
                try {
                    const response = await fetch(fflBroAjax.ajaxurl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'ffl_bro_quote',
                            action_type: 'get_dashboard_data',
                            nonce: fflBroAjax.nonce
                        })
                    });
                    const data = await response.json();
                    if (data.success) {
                        setDashboardData(data.data);
                    }
                } catch (error) {
                    console.error('Error loading dashboard:', error);
                } finally {
                    setLoading(false);
                }
            };
            
            const KpiCard = ({ title, value, subtitle, trend, icon, color = "blue", onClick }) => {
                const colorClasses = {
                    blue: "bg-blue-50 text-blue-600 border-blue-100",
                    orange: "bg-orange-50 text-orange-600 border-orange-100", 
                    green: "bg-green-50 text-green-600 border-green-100",
                    purple: "bg-purple-50 text-purple-600 border-purple-100"
                };
                
                return React.createElement('div', { 
                    className: `ffl-bro-kpi-card p-6 ${onClick ? 'cursor-pointer' : ''}`,
                    onClick: onClick
                },
                    React.createElement('div', { className: 'flex items-start justify-between' },
                        React.createElement('div', { className: 'flex-1' },
                            React.createElement('div', { className: 'flex items-center gap-2 mb-3' },
                                React.createElement('h3', { className: 'text-sm font-medium text-gray-600' }, title),
                                trend && React.createElement('span', { 
                                    className: `inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                                        trend > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                    }`
                                }, 
                                    React.createElement('span', { className: 'mr-1' }, trend > 0 ? '↗' : '↘'),
                                    Math.abs(trend) + '%'
                                )
                            ),
                            React.createElement('p', { className: 'text-2xl font-bold text-gray-900 mb-1' }, value),
                            subtitle && React.createElement('p', { className: 'text-sm text-gray-500' }, subtitle)
                        ),
                        icon && React.createElement('div', { className: `w-12 h-12 rounded-lg border flex items-center justify-center ${colorClasses[color]}` },
                            React.createElement('span', { className: 'text-xl' }, icon)
                        )
                    )
                );
            };
            
            if (loading) {
                return React.createElement('div', { className: 'p-8' },
                    React.createElement('div', { className: 'animate-pulse space-y-4' },
                        React.createElement('div', { className: 'h-6 bg-gray-200 rounded w-1/4' }),
                        React.createElement('div', { className: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6' },
                            [1,2,3,4].map(i => 
                                React.createElement('div', { key: i, className: 'h-32 bg-gray-200 rounded-lg' })
                            )
                        )
                    )
                );
            }
            
            return React.createElement('div', { className: 'space-y-8 p-8 bg-gray-50 min-h-screen' },
                // Header
                React.createElement('div', { className: 'flex items-center justify-between' },
                    React.createElement('div', null,
                        React.createElement('h1', { className: 'text-3xl font-bold text-gray-900' }, 'Dashboard'),
                        React.createElement('p', { className: 'text-gray-600 mt-1' }, 'Welcome back to Neefeco Arms HQ')
                    ),
                    React.createElement('div', { className: 'flex items-center gap-3' },
                        React.createElement('button', { className: 'flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors' },
                            React.createElement('span', null, '⬇'),
                            'Export Data'
                        ),
                        React.createElement('button', { className: 'flex items-center gap-2 px-4 py-2 ffl-bro-btn-primary text-white rounded-lg hover:shadow-lg transition-all' },
                            React.createElement('span', null, '🔄'),
                            'Refresh'
                        )
                    )
                ),
                
                // KPI Cards
                React.createElement('div', { className: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6' },
                    React.createElement(KpiCard, {
                        title: 'Orders Today',
                        value: dashboardData?.kpis.orders_today || '0',
                        subtitle: `${dashboardData?.kpis.orders_month?.toLocaleString() || '0'} this month`,
                        trend: 12.5,
                        icon: '📊',
                        color: 'blue'
                    }),
                    React.createElement(KpiCard, {
                        title: 'Revenue Today', 
                        value: `$${dashboardData?.kpis.revenue_today?.toLocaleString() || '0'}`,
                        subtitle: `$${dashboardData?.kpis.revenue_month?.toLocaleString() || '0'} this month`,
                        trend: 8.2,
                        icon: '💰',
                        color: 'green'
                    }),
                    React.createElement(KpiCard, {
                        title: 'Active Leads',
                        value: dashboardData?.kpis.active_leads || '0',
                        subtitle: `${dashboardData?.kpis.conversion_rate || '0'}% conversion rate`,
                        trend: -2.1,
                        icon: '👥',
                        color: 'purple'
                    }),
                    React.createElement(KpiCard, {
                        title: 'Pending Transfers',
                        value: dashboardData?.kpis.pending_transfers || '0',
                        subtitle: 'FFL transfers in progress',
                        icon: '🔄',
                        color: 'orange'
                    })
                ),
                
                // Recent Activity
                React.createElement('div', { className: 'bg-white rounded-xl shadow-sm border border-gray-100 ffl-bro-card' },
                    React.createElement('div', { className: 'p-6 border-b border-gray-100' },
                        React.createElement('h3', { className: 'text-lg font-semibold text-gray-900' }, 'Recent Activity')
                    ),
                    React.createElement('div', { className: 'p-6' },
                        React.createElement('div', { className: 'space-y-4' },
                            dashboardData?.recent_activity?.map((activity, index) =>
                                React.createElement('div', { key: index, className: 'flex items-center gap-4' },
                                    React.createElement('div', { className: `w-2 h-2 rounded-full ${
                                        activity.status === 'success' ? 'bg-green-500' :
                                        activity.status === 'warning' ? 'bg-yellow-500' :
                                        activity.status === 'error' ? 'bg-red-500' : 'bg-blue-500'
                                    }` }),
                                    React.createElement('div', { className: 'flex-1' },
                                        React.createElement('p', { className: 'text-sm text-gray-900' }, activity.message),
                                        React.createElement('p', { className: 'text-xs text-gray-500' }, activity.time)
                                    )
                                )
                            ) || [React.createElement('p', { key: 'no-activity', className: 'text-gray-500' }, 'No recent activity')]
                        )
                    )
                )
            );
        };
        
        // Admin Dashboard Component
        const FFLBROAdminApp = () => {
            return React.createElement('div', { className: 'wrap' },
                React.createElement('h1', null, 'FFL-BRO Professional Dashboard'),
                React.createElement(FFLBRODashboard, { type: 'admin' })
            );
        };
        
        // Admin Quote Component  
        const FFLBROQuoteAdmin = () => {
            return React.createElement('div', { className: 'wrap' },
                React.createElement('h1', null, 'Quote Management'),
                React.createElement(FFLBROQuoteGenerator, { mode: 'admin' })
            );
        };
        </script>
        <?php
    }
});
?>
