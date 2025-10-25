// FFL-BRO Enhanced Platform - Frontend Components
// Save as: wp-content/mu-plugins/assets/ffl-bro-enhanced.js

(function() {
    'use strict';
    
    const { useState, useEffect, createElement: h } = React;
    
    // Configuration and API
    const Config = {
        mockMode: window.fflBroSettings?.mockMode === 'true',
        debug: window.fflBroSettings?.debug === 'true',
        endpoints: window.fflBroSettings?.apiEndpoints || {},
        ajaxUrl: window.fflBroSettings?.ajaxUrl || '/wp-admin/admin-ajax.php',
        restUrl: window.fflBroSettings?.restUrl || '/wp-json/fflbro/v1/',
        nonce: window.fflBroSettings?.nonce || ''
    };
    
    // API utility
    const API = {
        async get(endpoint) {
            try {
                const url = endpoint.startsWith('http') ? endpoint : Config.restUrl + endpoint.replace(/^\//, '');
                const response = await fetch(url, {
                    headers: {
                        'X-WP-Nonce': Config.nonce
                    }
                });
                return await response.json();
            } catch (error) {
                console.error('API Error:', error);
                return { ok: false, error: error.message };
            }
        },
        
        async post(endpoint, data) {
            try {
                const url = endpoint.startsWith('http') ? endpoint : Config.restUrl + endpoint.replace(/^\//, '');
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': Config.nonce
                    },
                    body: JSON.stringify(data)
                });
                return await response.json();
            } catch (error) {
                console.error('API Error:', error);
                return { ok: false, error: error.message };
            }
        }
    };
    
    // Icon components using Unicode symbols
    const Icons = {
        Dashboard: () => h('span', { style: { fontSize: '1.2em' } }, '📊'),
        News: () => h('span', { style: { fontSize: '1.2em' } }, '📰'),
        Form: () => h('span', { style: { fontSize: '1.2em' } }, '📝'),
        Market: () => h('span', { style: { fontSize: '1.2em' } }, '📈'),
        Mobile: () => h('span', { style: { fontSize: '1.2em' } }, '📱'),
        Compliance: () => h('span', { style: { fontSize: '1.2em' } }, '🛡️'),
        Settings: () => h('span', { style: { fontSize: '1.2em' } }, '⚙️'),
        Alert: () => h('span', { style: { fontSize: '1.2em' } }, '⚠️'),
        Success: () => h('span', { style: { fontSize: '1.2em' } }, '✅'),
        Error: () => h('span', { style: { fontSize: '1.2em' } }, '❌'),
        Loading: () => h('span', { style: { fontSize: '1.2em', animation: 'spin 1s linear infinite' } }, '⏳')
    };
    
    // Loading component
    const Loading = ({ message = 'Loading...' }) => h('div', {
        className: 'flex items-center justify-center p-8'
    }, [
        h(Icons.Loading, { key: 'icon' }),
        h('span', { key: 'text', className: 'ml-2 text-gray-600' }, message)
    ]);
    
    // Error component
    const ErrorDisplay = ({ error, onRetry }) => h('div', {
        className: 'border border-red-200 bg-red-50 rounded-lg p-4'
    }, [
        h('div', { key: 'header', className: 'flex items-center gap-2 text-red-700 font-medium' }, [
            h(Icons.Error),
            'Error'
        ]),
        h('p', { key: 'message', className: 'text-red-600 mt-2' }, error),
        onRetry && h('button', {
            key: 'retry',
            className: 'mt-3 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700',
            onClick: onRetry
        }, 'Retry')
    ]);
    
    // Dashboard Component
    const Dashboard = () => {
        const [kpis, setKpis] = useState(null);
        const [loading, setLoading] = useState(true);
        const [error, setError] = useState(null);
        
        const loadData = async () => {
            setLoading(true);
            setError(null);
            try {
                const result = await API.get('dashboard/kpis');
                if (result.ok) {
                    setKpis(result.data);
                } else {
                    setError(result.error || 'Failed to load dashboard data');
                }
            } catch (err) {
                setError(err.message);
            } finally {
                setLoading(false);
            }
        };
        
        useEffect(() => {
            loadData();
            // Refresh every 30 seconds if not in mock mode
            const interval = Config.mockMode ? null : setInterval(loadData, 30000);
            return () => interval && clearInterval(interval);
        }, []);
        
        if (loading) return h(Loading, { message: 'Loading dashboard...' });
        if (error) return h(ErrorDisplay, { error, onRetry: loadData });
        if (!kpis) return h(ErrorDisplay, { error: 'No data available' });
        
        const statCards = [
            { label: 'Orders Today', value: kpis.orders_today, icon: '📦' },
            { label: 'Orders This Month', value: kpis.orders_month, icon: '📈' },
            { label: 'Revenue Today', value: `$${kpis.revenue_today?.toLocaleString() || 0}`, icon: '💰' },
            { label: 'Revenue This Month', value: `$${kpis.revenue_month?.toLocaleString() || 0}`, icon: '💰' },
            { label: 'Active Leads', value: kpis.active_leads, icon: '👥' },
            { label: 'Conversion Rate', value: `${kpis.conversion_rate}%`, icon: '🎯' }
        ];
        
        return h('div', { className: 'space-y-6' }, [
            h('div', { key: 'header', className: 'flex items-center justify-between' }, [
                h('h2', { className: 'text-2xl font-bold text-gray-900' }, 'FFL-BRO Dashboard'),
                h('div', { className: 'flex items-center gap-2 text-sm text-gray-600' }, [
                    h('span', {}, Config.mockMode ? 'Mock Data Mode' : 'Live Data Mode'),
                    Config.mockMode && h('span', { 
                        className: 'px-2 py-1 bg-yellow-100 text-yellow-800 rounded'
                    }, 'DEMO')
                ])
            ]),
            
            h('div', { key: 'stats', className: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4' }, 
                statCards.map((stat, i) => h('div', {
                    key: i,
                    className: 'bg-white border border-gray-200 rounded-lg p-6 shadow-sm'
                }, [
                    h('div', { className: 'flex items-center gap-3' }, [
                        h('span', { className: 'text-2xl' }, stat.icon),
                        h('div', {}, [
                            h('p', { className: 'text-sm text-gray-600' }, stat.label),
                            h('p', { className: 'text-2xl font-bold text-gray-900' }, stat.value)
                        ])
                    ])
                ]))
            ),
            
            h('div', { key: 'widgets', className: 'grid grid-cols-1 lg:grid-cols-2 gap-6' }, [
                h('div', { className: 'bg-white border border-gray-200 rounded-lg p-6' }, [
                    h('h3', { className: 'text-lg font-semibold mb-4' }, 'Quick Actions'),
                    h('div', { className: 'space-y-3' }, [
                        h('button', { 
                            className: 'w-full text-left p-3 border border-gray-200 rounded hover:bg-gray-50'
                        }, '📝 New Form 4473'),
                        h('button', { 
                            className: 'w-full text-left p-3 border border-gray-200 rounded hover:bg-gray-50'
                        }, '💰 Generate Quote'),
                        h('button', { 
                            className: 'w-full text-left p-3 border border-gray-200 rounded hover:bg-gray-50'
                        }, '📊 Market Research'),
                        h('button', { 
                            className: 'w-full text-left p-3 border border-gray-200 rounded hover:bg-gray-50'
                        }, '🛡️ Compliance Check')
                    ])
                ]),
                
                h('div', { className: 'bg-white border border-gray-200 rounded-lg p-6' }, [
                    h('h3', { className: 'text-lg font-semibold mb-4' }, 'System Status'),
                    h('div', { className: 'space-y-3' }, [
                        h('div', { className: 'flex justify-between items-center' }, [
                            'System Health',
                            h('span', { className: 'text-green-600 font-medium' }, `${kpis.system_health}%`)
                        ]),
                        h('div', { className: 'flex justify-between items-center' }, [
                            'Active Alerts',
                            h('span', { 
                                className: kpis.alerts_count > 0 ? 'text-orange-600 font-medium' : 'text-green-600 font-medium'
                            }, kpis.alerts_count)
                        ]),
                        h('div', { className: 'flex justify-between items-center' }, [
                            'Compliance Score',
                            h('span', { className: 'text-green-600 font-medium' }, `${kpis.compliance_score}%`)
                        ])
                    ])
                ])
            ])
        ]);
    };
    
    // FNews Component
    const FNews = () => {
        const [news, setNews] = useState([]);
        const [loading, setLoading] = useState(true);
        const [error, setError] = useState(null);
        
        const loadNews = async () => {
            setLoading(true);
            setError(null);
            try {
                const result = await API.get('fnews');
                if (result.ok) {
                    setNews(result.items || []);
                } else {
                    setError(result.error || 'Failed to load news');
                }
            } catch (err) {
                setError(err.message);
            } finally {
                setLoading(false);
            }
        };
        
        useEffect(() => {
            loadNews();
        }, []);
        
        if (loading) return h(Loading, { message: 'Loading industry news...' });
        if (error) return h(ErrorDisplay, { error, onRetry: loadNews });
        
        return h('div', { className: 'bg-white border border-gray-200 rounded-lg' }, [
            h('div', { key: 'header', className: 'border-b border-gray-200 p-4' }, [
                h('div', { className: 'flex items-center gap-2' }, [
                    h(Icons.News),
                    h('h3', { className: 'text-lg font-semibold' }, 'Industry News')
                ])
            ]),
            h('div', { key: 'content', className: 'p-4' }, 
                news.length === 0 ? 
                    h('p', { className: 'text-gray-600' }, 'No news items available') :
                    h('div', { className: 'space-y-4' }, 
                        news.map((item, i) => h('div', {
                            key: i,
                            className: 'border-l-4 border-blue-500 pl-4'
                        }, [
                            h('h4', { className: 'font-medium text-gray-900' }, item.title),
                            h('p', { className: 'text-sm text-gray-600 mt-1' }, item.summary),
                            h('div', { className: 'flex items-center justify-between mt-2' }, [
                                h('span', { 
                                    className: 'text-xs px-2 py-1 bg-gray-100 rounded'
                                }, item.category),
                                h('span', { className: 'text-xs text-gray-500' }, 
                                    new Date(item.published).toLocaleDateString())
                            ])
                        ]))
                    )
            )
        ]);
    };
    
    // Market Research Component
    const MarketResearch = () => {
        const [opportunities, setOpportunities] = useState([]);
        const [loading, setLoading] = useState(true);
        const [error, setError] = useState(null);
        
        const loadOpportunities = async () => {
            setLoading(true);
            setError(null);
            try {
                const result = await API.get('market-research/opportunities');
                if (result.ok) {
                    setOpportunities(result.opportunities || []);
                } else {
                    setError(result.error || 'Failed to load market data');
                }
            } catch (err) {
                setError(err.message);
            } finally {
                setLoading(false);
            }
        };
        
        useEffect(() => {
            loadOpportunities();
        }, []);
        
        if (loading) return h(Loading, { message: 'Analyzing market opportunities...' });
        if (error) return h(ErrorDisplay, { error, onRetry: loadOpportunities });
        
        return h('div', { className: 'space-y-6' }, [
            h('div', { key: 'header', className: 'flex items-center justify-between' }, [
                h('h2', { className: 'text-2xl font-bold text-gray-900' }, 'Market Research'),
                h('button', { 
                    className: 'px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700',
                    onClick: loadOpportunities
                }, 'Refresh Analysis')
            ]),
            
            h('div', { key: 'opportunities', className: 'space-y-4' }, 
                opportunities.length === 0 ? 
                    h('div', { className: 'text-center py-8 text-gray-600' }, 'No opportunities detected') :
                    opportunities.map((opp, i) => h('div', {
                        key: i,
                        className: 'bg-white border border-gray-200 rounded-lg p-6'
                    }, [
                        h('div', { className: 'flex items-start justify-between mb-3' }, [
                            h('h3', { className: 'text-lg font-semibold text-gray-900' }, opp.title),
                            h('span', { 
                                className: `px-3 py-1 rounded text-sm ${
                                    opp.confidence === 'high' ? 'bg-green-100 text-green-800' :
                                    opp.confidence === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                                    'bg-gray-100 text-gray-800'
                                }`
                            }, opp.confidence + ' confidence')
                        ]),
                        h('p', { className: 'text-gray-600 mb-4' }, opp.description),
                        h('div', { className: 'flex items-center justify-between' }, [
                            h('span', { className: 'text-green-600 font-medium' }, 
                                `Potential Profit: ${opp.potential_profit}`),
                            h('button', { 
                                className: 'px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700'
                            }, opp.action_required)
                        ])
                    ]))
            )
        ]);
    };
    
    // Compliance Dashboard Component
    const ComplianceDashboard = () => {
        const [status, setStatus] = useState(null);
        const [loading, setLoading] = useState(true);
        const [error, setError] = useState(null);
        
        const loadStatus = async () => {
            setLoading(true);
            setError(null);
            try {
                const result = await API.get('compliance/status');
                if (result.ok) {
                    setStatus(result.status);
                } else {
                    setError(result.error || 'Failed to load compliance data');
                }
            } catch (err) {
                setError(err.message);
            } finally {
                setLoading(false);
            }
        };
        
        useEffect(() => {
            loadStatus();
        }, []);
        
        if (loading) return h(Loading, { message: 'Loading compliance status...' });
        if (error) return h(ErrorDisplay, { error, onRetry: loadStatus });
        if (!status) return h(ErrorDisplay, { error: 'No compliance data available' });
        
        return h('div', { className: 'space-y-6' }, [
            h('div', { key: 'header', className: 'flex items-center justify-between' }, [
                h('h2', { className: 'text-2xl font-bold text-gray-900' }, 'ATF Compliance Dashboard'),
                h('span', { 
                    className: `px-3 py-1 rounded text-sm ${
                        status.overall_score >= 95 ? 'bg-green-100 text-green-800' :
                        status.overall_score >= 85 ? 'bg-yellow-100 text-yellow-800' :
                        'bg-red-100 text-red-800'
                    }`
                }, `${status.overall_score}% Compliant`)
            ]),
            
            h('div', { key: 'stats', className: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4' }, [
                {
                    label: 'Forms 4473 Pending',
                    value: status.forms_4473_pending,
                    icon: '📝',
                    status: status.forms_4473_pending > 20 ? 'warning' : 'ok'
                },
                {
                    label: 'Audit Alerts',
                    value: status.audit_alerts,
                    icon: '⚠️',
                    status: status.audit_alerts > 0 ? 'warning' : 'ok'
                },
                {
                    label: 'Last Check',
                    value: new Date(status.last_compliance_check).toLocaleDateString(),
                    icon: '🔍',
                    status: 'info'
                },
                {
                    label: 'Next Report',
                    value: new Date(status.next_scheduled_report).toLocaleDateString(),
                    icon: '📅',
                    status: 'info'
                }
            ].map((stat, i) => h('div', {
                key: i,
                className: 'bg-white border border-gray-200 rounded-lg p-4'
            }, [
                h('div', { className: 'flex items-center gap-2 mb-2' }, [
                    h('span', { className: 'text-xl' }, stat.icon),
                    h('span', { className: 'text-sm text-gray-600' }, stat.label)
                ]),
                h('div', { 
                    className: `text-lg font-bold ${
                        stat.status === 'warning' ? 'text-orange-600' :
                        stat.status === 'error' ? 'text-red-600' :
                        'text-gray-900'
                    }`
                }, stat.value)
            ]))
        ]);
    };
    
    // Individual Form Components
    const FormComponent = ({ type = 'quote' }) => {
        const [formData, setFormData] = useState({});
        const [submitting, setSubmitting] = useState(false);
        const [success, setSuccess] = useState(false);
        
        const handleSubmit = async (e) => {
            e.preventDefault();
            setSubmitting(true);
            
            // Simulate form submission
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            setSubmitting(false);
            setSuccess(true);
            setTimeout(() => setSuccess(false), 3000);
        };
        
        const forms = {
            quote: {
                title: 'Request Quote',
                fields: [
                    { name: 'customer_name', label: 'Full Name', type: 'text', required: true },
                    { name: 'email', label: 'Email', type: 'email', required: true },
                    { name: 'phone', label: 'Phone', type: 'tel', required: true },
                    { name: 'item_description', label: 'Item Description', type: 'textarea', required: true },
                    { name: 'quantity', label: 'Quantity', type: 'number', required: true }
                ]
            },
            contact: {
                title: 'Contact Us',
                fields: [
                    { name: 'name', label: 'Name', type: 'text', required: true },
                    { name: 'email', label: 'Email', type: 'email', required: true },
                    { name: 'subject', label: 'Subject', type: 'text', required: true },
                    { name: 'message', label: 'Message', type: 'textarea', required: true }
                ]
            },
            transfer: {
                title: 'FFL Transfer Request',
                fields: [
                    { name: 'buyer_name', label: 'Buyer Name', type: 'text', required: true },
                    { name: 'seller_name', label: 'Seller/Dealer Name', type: 'text', required: true },
                    { name: 'firearm_type', label: 'Firearm Type', type: 'select', options: ['Handgun', 'Rifle', 'Shotgun'], required: true },
                    { name: 'serial_number', label: 'Serial Number', type: 'text', required: true },
                    { name: 'manufacturer', label: 'Manufacturer', type: 'text', required: true }
                ]
            }
        };
        
        const currentForm = forms[type] || forms.quote;
        
        if (success) {
            return h('div', { className: 'bg-green-50 border border-green-200 rounded-lg p-6 text-center' }, [
                h(Icons.Success, { key: 'icon' }),
                h('h3', { key: 'title', className: 'text-lg font-semibold text-green-800 mt-2' }, 'Success!'),
                h('p', { key: 'message', className: 'text-green-600 mt-1' }, 'Your request has been submitted successfully.')
            ]);
        }
        
        return h('div', { className: 'bg-white border border-gray-200 rounded-lg p-6' }, [
            h('h3', { key: 'title', className: 'text-xl font-bold mb-6' }, currentForm.title),
            h('form', { key: 'form', onSubmit: handleSubmit, className: 'space-y-4' }, [
                ...currentForm.fields.map((field, i) => {
                    if (field.type === 'textarea') {
                        return h('div', { key: i }, [
                            h('label', { className: 'block text-sm font-medium text-gray-700 mb-1' }, field.label),
                            h('textarea', {
                                name: field.name,
                                required: field.required,
                                className: 'w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
                                rows: 4,
                                onChange: (e) => setFormData({...formData, [field.name]: e.target.value})
                            })
                        ]);
                    } else if (field.type === 'select') {
                        return h('div', { key: i }, [
                            h('label', { className: 'block text-sm font-medium text-gray-700 mb-1' }, field.label),
                            h('select', {
                                name: field.name,
                                required: field.required,
                                className: 'w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
                                onChange: (e) => setFormData({...formData, [field.name]: e.target.value})
                            }, [
                                h('option', { value: '' }, 'Select...'),
                                ...field.options.map(option => h('option', { key: option, value: option }, option))
                            ])
                        ]);
                    } else {
                        return h('div', { key: i }, [
                            h('label', { className: 'block text-sm font-medium text-gray-700 mb-1' }, field.label),
                            h('input', {
                                type: field.type,
                                name: field.name,
                                required: field.required,
                                className: 'w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
                                onChange: (e) => setFormData({...formData, [field.name]: e.target.value})
                            })
                        ]);
                    }
                }),
                h('button', {
                    key: 'submit',
                    type: 'submit',
                    disabled: submitting,
                    className: `w-full py-3 px-4 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 ${submitting ? 'cursor-not-allowed' : ''}`
                }, submitting ? 'Submitting...' : 'Submit Request')
            ])
        ]);
    };
    
    // Mobile Operations Component
    const MobileOperations = () => {
        return h('div', { className: 'space-y-6' }, [
            h('h2', { key: 'title', className: 'text-2xl font-bold text-gray-900' }, 'Mobile Operations'),
            h('div', { key: 'content', className: 'bg-white border border-gray-200 rounded-lg p-6' }, [
                h('div', { className: 'text-center py-8' }, [
                    h(Icons.Mobile, { key: 'icon' }),
                    h('h3', { key: 'title', className: 'text-lg font-semibold mt-4' }, 'Mobile App Coming Soon'),
                    h('p', { key: 'desc', className: 'text-gray-600 mt-2' }, 'Mobile operations features are in development. Check back soon for updates.')
                ])
            ])
        ]);
    };
    
    // Settings Component
    const Settings = () => {
        const [settings, setSettings] = useState(null);
        const [loading, setLoading] = useState(true);
        const [saving, setSaving] = useState(false);
        const [saved, setSaved] = useState(false);
        
        const loadSettings = async () => {
            setLoading(true);
            try {
                const result = await API.get('settings');
                if (result.ok) {
                    setSettings(result.settings);
                }
            } catch (err) {
                console.error('Failed to load settings:', err);
            } finally {
                setLoading(false);
            }
        };
        
        const saveSettings = async () => {
            setSaving(true);
            try {
                const result = await API.post('settings', settings);
                if (result.ok) {
                    setSaved(true);
                    setTimeout(() => setSaved(false), 3000);
                    // Reload page to apply new settings
                    setTimeout(() => window.location.reload(), 1000);
                }
            } catch (err) {
                console.error('Failed to save settings:', err);
            } finally {
                setSaving(false);
            }
        };
        
        useEffect(() => {
            loadSettings();
        }, []);
        
        if (loading) return h(Loading, { message: 'Loading settings...' });
        if (!settings) return h(ErrorDisplay, { error: 'Failed to load settings' });
        
        return h('div', { className: 'space-y-6' }, [
            h('h2', { key: 'title', className: 'text-2xl font-bold text-gray-900' }, 'Platform Settings'),
            
            h('div', { key: 'form', className: 'bg-white border border-gray-200 rounded-lg p-6' }, [
                h('h3', { className: 'text-lg font-semibold mb-4' }, 'System Configuration'),
                
                h('div', { className: 'space-y-4' }, [
                    h('div', {}, [
                        h('label', { className: 'block text-sm font-medium text-gray-700 mb-2' }, 'Data Mode'),
                        h('div', { className: 'flex gap-4' }, [
                            h('label', { className: 'flex items-center' }, [
                                h('input', {
                                    type: 'radio',
                                    name: 'mockMode',
                                    checked: settings.mock_mode === 'true',
                                    onChange: () => setSettings({...settings, mock_mode: 'true'}),
                                    className: 'mr-2'
                                }),
                                'Mock Data (Demo Mode)'
                            ]),
                            h('label', { className: 'flex items-center' }, [
                                h('input', {
                                    type: 'radio',
                                    name: 'mockMode',
                                    checked: settings.mock_mode === 'false',
                                    onChange: () => setSettings({...settings, mock_mode: 'false'}),
                                    className: 'mr-2'
                                }),
                                'Live Data'
                            ])
                        ])
                    ]),
                    
                    h('div', {}, [
                        h('label', { className: 'flex items-center' }, [
                            h('input', {
                                type: 'checkbox',
                                checked: settings.debug_mode === 'true',
                                onChange: (e) => setSettings({...settings, debug_mode: e.target.checked ? 'true' : 'false'}),
                                className: 'mr-2'
                            }),
                            'Enable Debug Mode'
                        ])
                    ])
                ]),
                
                h('div', { className: 'flex items-center gap-4 mt-6' }, [
                    h('button', {
                        onClick: saveSettings,
                        disabled: saving,
                        className: 'px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50'
                    }, saving ? 'Saving...' : 'Save Settings'),
                    
                    saved && h('span', { className: 'text-green-600 font-medium' }, '✅ Settings saved successfully!')
                ])
            ])
        ]);
    };
    
    // Component Router
    const ComponentRouter = {
        dashboard: Dashboard,
        fnews: FNews,
        form: FormComponent,
        market: MarketResearch,
        mobile: MobileOperations,
        compliance: ComplianceDashboard,
        settings: Settings
    };
    
    // Mount components when DOM is ready
    function mountComponents() {
        // Mount individual components
        document.querySelectorAll('[data-component]').forEach(element => {
            const componentName = element.dataset.component;
            const Component = ComponentRouter[componentName];
            
            if (Component) {
                const props = {};
                
                // Add type prop for form components
                if (componentName === 'form' && element.dataset.type) {
                    props.type = element.dataset.type;
                }
                
                ReactDOM.render(h(Component, props), element);
            }
        });
        
        // Mount admin components
        const adminDashboard = document.getElementById('ffl-bro-admin-dashboard');
        if (adminDashboard) {
            ReactDOM.render(h(Dashboard), adminDashboard);
        }
        
        const adminSettings = document.getElementById('ffl-bro-admin-settings');
        if (adminSettings) {
            ReactDOM.render(h(Settings), adminSettings);
        }
        
        const adminMarket = document.getElementById('ffl-bro-admin-market');
        if (adminMarket) {
            ReactDOM.render(h(MarketResearch), adminMarket);
        }
        
        const adminCompliance = document.getElementById('ffl-bro-admin-compliance');
        if (adminCompliance) {
            ReactDOM.render(h(ComplianceDashboard), adminCompliance);
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mountComponents);
    } else {
        mountComponents();
    }
    
    // Re-mount on AJAX page loads
    let lastUrl = location.href;
    new MutationObserver(() => {
        const url = location.href;
        if (url !== lastUrl) {
            lastUrl = url;
            setTimeout(mountComponents, 100);
        }
    }).observe(document, { subtree: true, childList: true });
    
})();