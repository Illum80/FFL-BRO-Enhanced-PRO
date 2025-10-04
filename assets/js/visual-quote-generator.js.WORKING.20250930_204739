/**
 * Visual Quote Generator - Multi-Distributor
 * Pure React.createElement (no JSX)
 */
(function() {
    const { useState } = React;
    const e = React.createElement;
    
    // Icons
    const SearchIcon = () => e('svg', { style: { width: '20px', height: '20px' }, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, 
        e('circle', { cx: 11, cy: 11, r: 8 }),
        e('path', { d: 'M21 21l-4.35-4.35' })
    );
    
    const PlusIcon = () => e('svg', { style: { width: '20px', height: '20px' }, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 2 },
        e('line', { x1: 12, y1: 5, x2: 12, y2: 19 }),
        e('line', { x1: 5, y1: 12, x2: 19, y2: 12 })
    );
    
    const TrashIcon = () => e('svg', { style: { width: '20px', height: '20px' }, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 2 },
        e('polyline', { points: '3 6 5 6 21 6' }),
        e('path', { d: 'M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2' })
    );
    
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
                    setError(data.data?.message || 'Failed');
                }
            })
            .catch(err => {
                setLoading(false);
                setError('Error: ' + err.message);
            });
        };
        
        return e('div', { style: { fontFamily: 'system-ui', maxWidth: '1400px', margin: '0 auto', padding: '20px' } },
            e('style', null, `
                .search-box { display: flex; gap: 10px; margin-bottom: 20px; }
                .search-input { flex: 1; padding: 12px; border: 2px solid #ddd; border-radius: 4px; font-size: 16px; }
                .btn { padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; display: inline-flex; align-items: center; gap: 8px; }
                .btn-primary { background: #2271b1; color: white; }
                .btn-primary:hover { background: #135e96; }
                .btn-secondary { background: #f0f0f1; color: #2c3338; }
                .error { background: #fcf0f1; color: #d63638; padding: 12px; border-radius: 4px; margin: 10px 0; }
                .results { background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 400px; overflow-y: auto; }
                .result-item { padding: 15px; border-bottom: 1px solid #f0f0f1; display: flex; justify-content: space-between; align-items: center; }
                .result-item:hover { background: #f6f7f7; }
                .quote-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 30px; }
                .section { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
                .quote-item { display: grid; grid-template-columns: 2fr 100px 100px 50px; gap: 15px; align-items: center; padding: 15px; border-bottom: 1px solid #f0f0f1; }
                .form-group { margin-bottom: 20px; }
                .form-label { display: block; margin-bottom: 5px; font-weight: 500; }
                .form-input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
            `),
            
            e('div', { className: 'section' },
                e('h2', { style: { marginTop: 0 } }, 'Search Products'),
                e('div', { className: 'search-box' },
                    e('input', {
                        className: 'search-input',
                        type: 'text',
                        placeholder: 'Search by name, SKU, manufacturer...',
                        value: searchTerm,
                        onChange: (ev) => setSearchTerm(ev.target.value),
                        onKeyPress: (ev) => ev.key === 'Enter' && handleSearch()
                    }),
                    e('button', { className: 'btn btn-primary', onClick: handleSearch, disabled: loading },
                        e(SearchIcon),
                        loading ? 'Searching...' : 'Search'
                    )
                ),
                error && e('div', { className: 'error' }, error),
                searchResults.length > 0 && e('div', { className: 'results' },
                    searchResults.map((p, idx) => e('div', { key: idx, className: 'result-item' },
                        e('div', null,
                            e('div', { style: { fontWeight: 'bold' } }, p.name),
                            e('div', { style: { color: '#666', fontSize: '14px' } },
                                p.manufacturer, ' - ', p.sku,
                                p.distributor && e('span', { style: { color: '#2271b1', marginLeft: '10px' } }, `(${p.distributor})`)
                            )
                        ),
                        e('div', { style: { display: 'flex', alignItems: 'center', gap: '15px' } },
                            e('div', { style: { fontWeight: 'bold', fontSize: '18px' } }, '$' + p.price.toFixed(2)),
                            e('button', { className: 'btn btn-secondary', onClick: () => addToQuote(p) },
                                e(PlusIcon), 'Add'
                            )
                        )
                    ))
                )
            ),
            
            e('div', { className: 'quote-grid' },
                e('div', { className: 'section' },
                    e('h2', { style: { marginTop: 0 } }, `Quote Items (${quoteItems.length})`),
                    quoteItems.length === 0 ? 
                        e('div', { style: { textAlign: 'center', padding: '60px', color: '#666' } }, 'No items yet') :
                        quoteItems.map((item, idx) => e('div', { key: idx, className: 'quote-item' },
                            e('div', null,
                                e('div', { style: { fontWeight: 'bold' } }, item.name),
                                e('div', { style: { color: '#666', fontSize: '13px' } }, item.sku)
                            ),
                            e('input', {
                                type: 'number',
                                min: 1,
                                value: item.qty,
                                onChange: (ev) => updateQuantity(idx, ev.target.value),
                                style: { padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }
                            }),
                            e('div', { style: { fontWeight: 'bold', textAlign: 'right' } }, 
                                '$' + (item.price * item.qty).toFixed(2)
                            ),
                            e('button', { 
                                onClick: () => removeFromQuote(idx),
                                style: { background: '#dc3232', color: 'white', border: 'none', padding: '8px', borderRadius: '4px', cursor: 'pointer' }
                            },
                                e(TrashIcon)
                            )
                        ))
                ),
                
                e('div', { className: 'section' },
                    e('h2', { style: { marginTop: 0 } }, 'Customer Info'),
                    e('div', { className: 'form-group' },
                        e('label', { className: 'form-label' }, 'Name'),
                        e('input', {
                            className: 'form-input',
                            type: 'text',
                            value: customerInfo.name,
                            onChange: (ev) => setCustomerInfo({ ...customerInfo, name: ev.target.value })
                        })
                    ),
                    e('div', { className: 'form-group' },
                        e('label', { className: 'form-label' }, 'Email *'),
                        e('input', {
                            className: 'form-input',
                            type: 'email',
                            value: customerInfo.email,
                            onChange: (ev) => setCustomerInfo({ ...customerInfo, email: ev.target.value })
                        })
                    ),
                    e('div', { className: 'form-group' },
                        e('label', { className: 'form-label' }, 'Phone'),
                        e('input', {
                            className: 'form-input',
                            type: 'tel',
                            value: customerInfo.phone,
                            onChange: (ev) => setCustomerInfo({ ...customerInfo, phone: ev.target.value })
                        })
                    ),
                    e('button', {
                        className: 'btn btn-primary',
                        style: { width: '100%', justifyContent: 'center' },
                        onClick: submitQuote,
                        disabled: loading || quoteItems.length === 0
                    }, loading ? 'Submitting...' : 'Generate Quote')
                )
            ),
            
            showSuccess && e('div', { 
                style: { position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 100000 },
                onClick: () => setShowSuccess(false)
            },
                e('div', { style: { background: 'white', padding: '40px', borderRadius: '8px', textAlign: 'center' } },
                    e('div', { style: { fontSize: '64px', marginBottom: '20px' } }, '✅'),
                    e('h2', null, 'Quote Submitted!'),
                    e('p', null, `Sent to: ${customerInfo.email}`)
                )
            )
        );
    };
    
    window.VisualQuoteGenerator = VisualQuoteGenerator;
    console.log('✓ Component loaded');
})();
