/**
 * Enhanced Multi-Distributor Quote Generator
 * React Component
 * Save as: includes/quote-generator/quote-generator.js
 */

const { useState, useEffect } = React;

const QuoteGenerator = () => {
    const [searchTerm, setSearchTerm] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [quoteItems, setQuoteItems] = useState([]);
    const [loading, setLoading] = useState(false);
    const [customerInfo, setCustomerInfo] = useState({
        name: '',
        email: '',
        phone: ''
    });
    const [activeTab, setActiveTab] = useState('search');
    const [savedQuoteNumber, setSavedQuoteNumber] = useState('');

    // Search products across distributors
    const handleSearch = async () => {
        if (!searchTerm.trim()) {
            alert('Please enter a search term');
            return;
        }

        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('action', 'fflbro_search_products');
            formData.append('nonce', fflbroQuote.nonce);
            formData.append('search', searchTerm);
            formData.append('distributor', 'all');

            const response = await fetch(fflbroQuote.ajaxurl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                setSearchResults(data.data.products);
                if (data.data.products.length === 0) {
                    alert('No products found matching your search');
                }
            } else {
                alert('Search failed: ' + (data.data?.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Search error:', error);
            alert('Search failed: ' + error.message);
        } finally {
            setLoading(false);
        }
    };

    // Add item to quote
    const addToQuote = (product, distributor) => {
        const markup = parseFloat(fflbroQuote.settings[distributor + '_markup'] || 15);
        const cost = parseFloat(distributor.price);
        const retailPrice = cost * (1 + markup / 100);

        const quoteItem = {
            id: Date.now() + '-' + distributor.item_number,
            product: {
                description: product.description,
                manufacturer: product.manufacturer
            },
            distributor: distributor.distributor,
            item_number: distributor.item_number,
            cost: cost,
            markup_percent: markup,
            retail_price: retailPrice,
            quantity: 1,
            line_total: retailPrice
        };

        setQuoteItems(prev => [...prev, quoteItem]);
        setActiveTab('quote');
    };

    // Update quantity
    const updateQuantity = (itemId, newQuantity) => {
        if (newQuantity < 1) {
            removeItem(itemId);
            return;
        }

        setQuoteItems(prev => prev.map(item => 
            item.id === itemId
                ? { ...item, quantity: newQuantity, line_total: item.retail_price * newQuantity }
                : item
        ));
    };

    // Remove item
    const removeItem = (itemId) => {
        setQuoteItems(prev => prev.filter(item => item.id !== itemId));
    };

    // Calculate totals
    const calculateTotals = () => {
        const subtotal = quoteItems.reduce((sum, item) => sum + item.line_total, 0);
        const tax = subtotal * (parseFloat(fflbroQuote.settings.tax_rate) / 100);
        const total = subtotal + tax;

        return { subtotal, tax, total };
    };

    // Save quote
    const saveQuote = async () => {
        if (quoteItems.length === 0) {
            alert('Please add items to the quote');
            return;
        }

        if (!customerInfo.name || !customerInfo.email) {
            alert('Please enter customer name and email');
            return;
        }

        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('action', 'fflbro_save_quote');
            formData.append('nonce', fflbroQuote.nonce);
            formData.append('quote_data', JSON.stringify({
                customer: customerInfo,
                items: quoteItems
            }));

            const response = await fetch(fflbroQuote.ajaxurl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                setSavedQuoteNumber(data.data.quote_number);
                alert('Quote saved successfully! Quote #: ' + data.data.quote_number);
            } else {
                alert('Failed to save quote: ' + (data.data?.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Save error:', error);
            alert('Failed to save quote: ' + error.message);
        } finally {
            setLoading(false);
        }
    };

    const totals = calculateTotals();

    return (
        <div className="fflbro-quote-generator">
            {/* Tab Navigation */}
            <div className="quote-tabs">
                <button 
                    className={activeTab === 'search' ? 'active' : ''} 
                    onClick={() => setActiveTab('search')}
                >
                    Search Products ({searchResults.length})
                </button>
                <button 
                    className={activeTab === 'quote' ? 'active' : ''} 
                    onClick={() => setActiveTab('quote')}
                >
                    Quote Builder ({quoteItems.length})
                </button>
            </div>

            {/* Search Tab */}
            {activeTab === 'search' && (
                <div className="search-tab">
                    <div className="search-box">
                        <input
                            type="text"
                            placeholder="Search by manufacturer, model, or description..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                            className="search-input"
                        />
                        <button 
                            onClick={handleSearch} 
                            disabled={loading}
                            className="search-button"
                        >
                            {loading ? 'Searching...' : 'Search All Distributors'}
                        </button>
                    </div>

                    <div className="search-results">
                        {searchResults.length === 0 ? (
                            <div className="no-results">
                                <p>Enter a search term above to find products across Lipseys, RSR, and Davidsons</p>
                            </div>
                        ) : (
                            searchResults.map((product, idx) => (
                                <div key={idx} className="product-card">
                                    <div className="product-info">
                                        <h3>{product.manufacturer}</h3>
                                        <p>{product.description}</p>
                                        <div className="best-price">
                                            Best Price: ${product.best_price?.toFixed(2) || 'N/A'}
                                        </div>
                                    </div>

                                    <div className="distributor-options">
                                        {Object.entries(product.distributors).map(([distKey, dist]) => (
                                            <div key={distKey} className="distributor-option">
                                                <div className="dist-info">
                                                    <strong>{dist.distributor.toUpperCase()}</strong>
                                                    <span className="price">${dist.price.toFixed(2)}</span>
                                                    <span className="stock">
                                                        {dist.quantity > 0 ? `${dist.quantity} in stock` : 'Out of stock'}
                                                    </span>
                                                </div>
                                                <button
                                                    onClick={() => addToQuote(product, dist)}
                                                    disabled={dist.quantity === 0}
                                                    className="add-button"
                                                >
                                                    Add to Quote
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            )}

            {/* Quote Tab */}
            {activeTab === 'quote' && (
                <div className="quote-tab">
                    <div className="customer-info-section">
                        <h3>Customer Information</h3>
                        <div className="customer-form">
                            <input
                                type="text"
                                placeholder="Customer Name"
                                value={customerInfo.name}
                                onChange={(e) => setCustomerInfo({...customerInfo, name: e.target.value})}
                            />
                            <input
                                type="email"
                                placeholder="Email Address"
                                value={customerInfo.email}
                                onChange={(e) => setCustomerInfo({...customerInfo, email: e.target.value})}
                            />
                            <input
                                type="tel"
                                placeholder="Phone Number"
                                value={customerInfo.phone}
                                onChange={(e) => setCustomerInfo({...customerInfo, phone: e.target.value})}
                            />
                        </div>
                    </div>

                    <div className="quote-items-section">
                        <h3>Quote Items</h3>
                        {quoteItems.length === 0 ? (
                            <div className="no-items">
                                <p>No items added yet. Search for products to add them to your quote.</p>
                            </div>
                        ) : (
                            <div className="quote-items-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Distributor</th>
                                            <th>Cost</th>
                                            <th>Markup</th>
                                            <th>Price</th>
                                            <th>Qty</th>
                                            <th>Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {quoteItems.map((item) => (
                                            <tr key={item.id}>
                                                <td>
                                                    <div className="product-cell">
                                                        <strong>{item.product.manufacturer}</strong>
                                                        <br/>
                                                        <small>{item.product.description}</small>
                                                    </div>
                                                </td>
                                                <td>{item.distributor.toUpperCase()}</td>
                                                <td>${item.cost.toFixed(2)}</td>
                                                <td>{item.markup_percent}%</td>
                                                <td>${item.retail_price.toFixed(2)}</td>
                                                <td>
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        value={item.quantity}
                                                        onChange={(e) => updateQuantity(item.id, parseInt(e.target.value))}
                                                        className="quantity-input"
                                                    />
                                                </td>
                                                <td>${item.line_total.toFixed(2)}</td>
                                                <td>
                                                    <button
                                                        onClick={() => removeItem(item.id)}
                                                        className="remove-button"
                                                    >
                                                        Remove
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>

                                <div className="quote-totals">
                                    <div className="total-line">
                                        <span>Subtotal:</span>
                                        <strong>${totals.subtotal.toFixed(2)}</strong>
                                    </div>
                                    <div className="total-line">
                                        <span>Tax ({fflbroQuote.settings.tax_rate}%):</span>
                                        <strong>${totals.tax.toFixed(2)}</strong>
                                    </div>
                                    <div className="total-line grand-total">
                                        <span>Total:</span>
                                        <strong>${totals.total.toFixed(2)}</strong>
                                    </div>
                                </div>

                                <div className="quote-actions">
                                    <button 
                                        onClick={saveQuote} 
                                        disabled={loading}
                                        className="save-button"
                                    >
                                        {loading ? 'Saving...' : 'Save Quote'}
                                    </button>
                                    {savedQuoteNumber && (
                                        <div className="saved-quote-info">
                                            Quote saved! Reference #: <strong>{savedQuoteNumber}</strong>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
};

// Mount the React app
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('quote-generator-app');
    if (container) {
        const root = ReactDOM.createRoot(container);
        root.render(<QuoteGenerator />);
    }
});