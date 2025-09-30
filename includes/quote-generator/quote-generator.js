const { useState, useEffect } = React;

const QuoteGenerator = () => {
    const [searchTerm, setSearchTerm] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [quoteItems, setQuoteItems] = useState([]);
    const [loading, setLoading] = useState(false);
    const [customerInfo, setCustomerInfo] = useState({ name: '', email: '', phone: '' });
    const [activeTab, setActiveTab] = useState('search');
    const [savedQuoteNumber, setSavedQuoteNumber] = useState('');

    // Live search as user types (debounced)
    useEffect(() => {
        if (searchTerm.length < 3) {
            setSearchResults([]);
            return;
        }

        const delaySearch = setTimeout(() => {
            handleSearch();
        }, 500); // Wait 500ms after user stops typing

        return () => clearTimeout(delaySearch);
    }, [searchTerm]);

    const handleSearch = async () => {
        if (searchTerm.length < 3) {
            alert('Please enter at least 3 characters');
            return;
        }

        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('action', 'fflbro_search_products');
            formData.append('nonce', fflbroQuote.nonce);
            formData.append('search', searchTerm);

            const response = await fetch(fflbroQuote.ajaxurl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                setSearchResults(data.data.products);
            } else {
                console.error('Search error:', data);
                setSearchResults([]);
            }
        } catch (error) {
            console.error('Search failed:', error);
            setSearchResults([]);
        } finally {
            setLoading(false);
        }
    };

    const addToQuote = (product, dist) => {
        const markup = parseFloat(fflbroQuote.settings[dist.distributor + '_markup'] || 15);
        const cost = parseFloat(dist.price);
        const retailPrice = cost * (1 + markup / 100);
        
        setQuoteItems(prev => [...prev, {
            id: Date.now() + '-' + dist.item_number,
            product: { description: product.description, manufacturer: product.manufacturer },
            distributor: dist.distributor,
            item_number: dist.item_number,
            cost, 
            markup_percent: markup, 
            retail_price: retailPrice,
            quantity: 1, 
            line_total: retailPrice
        }]);
        setActiveTab('quote');
    };

    const updateQuantity = (itemId, qty) => {
        if (qty < 1) { removeItem(itemId); return; }
        setQuoteItems(prev => prev.map(item => 
            item.id === itemId ? { ...item, quantity: qty, line_total: item.retail_price * qty } : item
        ));
    };

    const removeItem = (itemId) => setQuoteItems(prev => prev.filter(item => item.id !== itemId));

    const calculateTotals = () => {
        const subtotal = quoteItems.reduce((sum, item) => sum + item.line_total, 0);
        const tax = subtotal * (parseFloat(fflbroQuote.settings.tax_rate) / 100);
        return { subtotal, tax, total: subtotal + tax };
    };

    const saveQuote = async () => {
        if (quoteItems.length === 0) { alert('Please add items'); return; }
        if (!customerInfo.name || !customerInfo.email) { alert('Please enter customer info'); return; }
        
        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('action', 'fflbro_save_quote');
            formData.append('nonce', fflbroQuote.nonce);
            formData.append('quote_data', JSON.stringify({ customer: customerInfo, items: quoteItems }));
            
            const response = await fetch(fflbroQuote.ajaxurl, { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.success) {
                setSavedQuoteNumber(data.data.quote_number);
                alert('Quote saved! #' + data.data.quote_number);
            } else {
                alert('Failed to save: ' + (data.data?.message || 'Unknown error'));
            }
        } catch (error) {
            alert('Error: ' + error.message);
        } finally {
            setLoading(false);
        }
    };

    const totals = calculateTotals();

    return React.createElement('div', { className: 'fflbro-quote-generator' },
        React.createElement('div', { className: 'quote-tabs' },
            React.createElement('button', { 
                className: activeTab === 'search' ? 'active' : '', 
                onClick: () => setActiveTab('search') 
            }, `Search Products (${searchResults.length})`),
            React.createElement('button', { 
                className: activeTab === 'quote' ? 'active' : '', 
                onClick: () => setActiveTab('quote') 
            }, `Quote Builder (${quoteItems.length})`)
        ),
        activeTab === 'search' ? React.createElement('div', { className: 'search-tab' },
            React.createElement('div', { className: 'search-box' },
                React.createElement('input', {
                    type: 'text', 
                    placeholder: 'Start typing to search products... (min 3 characters)',
                    value: searchTerm, 
                    onChange: (e) => setSearchTerm(e.target.value),
                    className: 'search-input'
                }),
                React.createElement('button', {
                    onClick: handleSearch, 
                    disabled: loading || searchTerm.length < 3, 
                    className: 'search-button'
                }, loading ? 'Searching...' : 'Search Products')
            ),
            loading && React.createElement('div', { style: { textAlign: 'center', padding: '2rem' } },
                React.createElement('p', null, 'Searching across all distributors...')
            ),
            React.createElement('div', { className: 'search-results' },
                !loading && searchResults.length === 0 && searchTerm.length < 3 ? 
                    React.createElement('div', { className: 'no-results' },
                        React.createElement('p', null, 'Start typing to search products (minimum 3 characters)')
                    ) :
                !loading && searchResults.length === 0 && searchTerm.length >= 3 ?
                    React.createElement('div', { className: 'no-results' },
                        React.createElement('p', null, `No products found for "${searchTerm}"`)
                    ) :
                    searchResults.map((product, idx) =>
                        React.createElement('div', { key: idx, className: 'product-card' },
                            React.createElement('div', { className: 'product-info' },
                                React.createElement('h3', null, product.manufacturer),
                                React.createElement('p', null, product.description),
                                React.createElement('div', { className: 'best-price' },
                                    `Best Price: $${product.best_price?.toFixed(2) || 'N/A'}`
                                )
                            ),
                            React.createElement('div', { className: 'distributor-options' },
                                Object.entries(product.distributors).map(([key, dist]) =>
                                    React.createElement('div', { key, className: 'distributor-option' },
                                        React.createElement('div', { className: 'dist-info' },
                                            React.createElement('strong', null, dist.distributor.toUpperCase()),
                                            React.createElement('span', { className: 'price' }, `$${dist.price.toFixed(2)}`),
                                            React.createElement('span', { className: 'stock' },
                                                dist.quantity > 0 ? `${dist.quantity} in stock` : 'Out of stock'
                                            )
                                        ),
                                        React.createElement('button', {
                                            onClick: () => addToQuote(product, dist),
                                            disabled: dist.quantity === 0,
                                            className: 'add-button'
                                        }, 'Add to Quote')
                                    )
                                )
                            )
                        )
                    )
            )
        ) : React.createElement('div', { className: 'quote-tab' },
            React.createElement('div', { className: 'customer-info-section' },
                React.createElement('h3', null, 'Customer Information'),
                React.createElement('div', { className: 'customer-form' },
                    React.createElement('input', {
                        type: 'text', placeholder: 'Customer Name',
                        value: customerInfo.name, 
                        onChange: (e) => setCustomerInfo({...customerInfo, name: e.target.value})
                    }),
                    React.createElement('input', {
                        type: 'email', placeholder: 'Email',
                        value: customerInfo.email, 
                        onChange: (e) => setCustomerInfo({...customerInfo, email: e.target.value})
                    }),
                    React.createElement('input', {
                        type: 'tel', placeholder: 'Phone',
                        value: customerInfo.phone, 
                        onChange: (e) => setCustomerInfo({...customerInfo, phone: e.target.value})
                    })
                )
            ),
            React.createElement('div', { className: 'quote-items-section' },
                React.createElement('h3', null, 'Quote Items'),
                quoteItems.length === 0 ? 
                    React.createElement('div', { className: 'no-items' },
                        React.createElement('p', null, 'No items yet. Search for products to add them.')
                    ) :
                    React.createElement('div', null,
                        React.createElement('table', { className: 'quote-items-table' },
                            React.createElement('thead', null,
                                React.createElement('tr', null,
                                    React.createElement('th', null, 'Product'),
                                    React.createElement('th', null, 'Distributor'),
                                    React.createElement('th', null, 'Cost'),
                                    React.createElement('th', null, 'Markup'),
                                    React.createElement('th', null, 'Price'),
                                    React.createElement('th', null, 'Qty'),
                                    React.createElement('th', null, 'Total'),
                                    React.createElement('th', null, '')
                                )
                            ),
                            React.createElement('tbody', null,
                                quoteItems.map(item =>
                                    React.createElement('tr', { key: item.id },
                                        React.createElement('td', null,
                                            React.createElement('strong', null, item.product.manufacturer),
                                            React.createElement('br'),
                                            React.createElement('small', null, item.product.description)
                                        ),
                                        React.createElement('td', null, item.distributor.toUpperCase()),
                                        React.createElement('td', null, `$${item.cost.toFixed(2)}`),
                                        React.createElement('td', null, `${item.markup_percent}%`),
                                        React.createElement('td', null, `$${item.retail_price.toFixed(2)}`),
                                        React.createElement('td', null,
                                            React.createElement('input', {
                                                type: 'number', min: '1', value: item.quantity,
                                                onChange: (e) => updateQuantity(item.id, parseInt(e.target.value)),
                                                className: 'quantity-input'
                                            })
                                        ),
                                        React.createElement('td', null, `$${item.line_total.toFixed(2)}`),
                                        React.createElement('td', null,
                                            React.createElement('button', {
                                                onClick: () => removeItem(item.id),
                                                className: 'remove-button'
                                            }, 'Remove')
                                        )
                                    )
                                )
                            )
                        ),
                        React.createElement('div', { className: 'quote-totals' },
                            React.createElement('div', { className: 'total-line' },
                                React.createElement('span', null, 'Subtotal:'),
                                React.createElement('strong', null, `$${totals.subtotal.toFixed(2)}`)
                            ),
                            React.createElement('div', { className: 'total-line' },
                                React.createElement('span', null, `Tax (${fflbroQuote.settings.tax_rate}%):`),
                                React.createElement('strong', null, `$${totals.tax.toFixed(2)}`)
                            ),
                            React.createElement('div', { className: 'total-line grand-total' },
                                React.createElement('span', null, 'Total:'),
                                React.createElement('strong', null, `$${totals.total.toFixed(2)}`)
                            )
                        ),
                        React.createElement('div', { className: 'quote-actions' },
                            React.createElement('button', {
                                onClick: saveQuote, disabled: loading, className: 'save-button'
                            }, loading ? 'Saving...' : 'Save Quote'),
                            savedQuoteNumber && React.createElement('div', { className: 'saved-quote-info' },
                                `Quote saved! Reference: ${savedQuoteNumber}`
                            )
                        )
                    )
            )
        )
    );
};

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('quote-generator-app');
    if (container) {
        const root = ReactDOM.createRoot(container);
        root.render(React.createElement(QuoteGenerator));
    }
});
