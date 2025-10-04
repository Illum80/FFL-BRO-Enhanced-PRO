// Visual Quote Generator - Multi-Distributor
const { useState } = React;

const VisualQuoteGenerator = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [quoteItems, setQuoteItems] = useState([]);
  const [customerInfo, setCustomerInfo] = useState({
    name: '',
    email: '',
    phone: ''
  });
  const [showSuccess, setShowSuccess] = useState(false);
  const [loading, setLoading] = useState(false);

  // Icon components (inline SVG)
  const SearchIcon = () => (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
      <circle cx="11" cy="11" r="8"></circle>
      <path d="m21 21-4.35-4.35"></path>
    </svg>
  );

  const PlusIcon = () => (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
      <line x1="12" y1="5" x2="12" y2="19"></line>
      <line x1="5" y1="12" x2="19" y2="12"></line>
    </svg>
  );

  const XIcon = () => (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
      <line x1="18" y1="6" x2="6" y2="18"></line>
      <line x1="6" y1="6" x2="18" y2="18"></line>
    </svg>
  );

  const PackageIcon = () => (
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
      <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
    </svg>
  );

  const CheckIcon = () => (
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
      <polyline points="20 6 9 17 4 12"></polyline>
    </svg>
  );

  // Search products
  const handleSearch = async () => {
    if (!searchTerm || searchTerm.length < 2) {
      alert('Please enter at least 2 characters');
      return;
    }

    setLoading(true);
    
    try {
      const formData = new URLSearchParams();
      formData.append('action', 'fflbro_search_multi_distributor');
      formData.append('nonce', fflbroQuote.nonce);
      formData.append('search', searchTerm);

      const response = await fetch(fflbroQuote.ajaxurl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData
      });

      const data = await response.json();
      
      if (data.success) {
        setSearchResults(data.data.products);
        console.log('Found products:', data.data.products);
      } else {
        alert('No products found');
      }
    } catch (error) {
      console.error('Search error:', error);
      alert('Search failed. Check console for details.');
    } finally {
      setLoading(false);
    }
  };

  const addToQuote = (product, distributor) => {
    const itemId = `${product.id}-${distributor.distributor_id}`;
    
    if (!quoteItems.find(item => item.itemId === itemId)) {
      setQuoteItems([...quoteItems, {
        itemId,
        productId: product.id,
        name: product.name,
        manufacturer: product.manufacturer,
        sku: distributor.sku,
        price: distributor.price,
        distributor: distributor.name,
        distributorId: distributor.distributor_id,
        shipping: distributor.shipping || 'TBD',
        eta: distributor.eta || '3-5 days',
        quantity: 1
      }]);
    }
  };

  const removeFromQuote = (itemId) => {
    setQuoteItems(quoteItems.filter(item => item.itemId !== itemId));
  };

  const calculateTotal = () => {
    const subtotal = quoteItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const tax = subtotal * 0.0825;
    const transferFee = 25.00;
    return { subtotal, tax, transferFee, total: subtotal + tax + transferFee };
  };

  const handleSubmitQuote = async () => {
    if (!customerInfo.name || !customerInfo.email || !customerInfo.phone) {
      alert('Please fill in all customer information');
      return;
    }

    if (quoteItems.length === 0) {
      alert('Please add at least one item to your quote');
      return;
    }

    setLoading(true);

    try {
      const formData = new URLSearchParams();
      formData.append('action', 'fflbro_submit_visual_quote');
      formData.append('nonce', fflbroQuote.nonce);
      formData.append('customer', JSON.stringify(customerInfo));
      formData.append('items', JSON.stringify(quoteItems));
      formData.append('totals', JSON.stringify(calculateTotal()));

      const response = await fetch(fflbroQuote.ajaxurl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData
      });

      const data = await response.json();
      
      if (data.success) {
        setShowSuccess(true);
        
        setTimeout(() => {
          setShowSuccess(false);
          setQuoteItems([]);
          setCustomerInfo({ name: '', email: '', phone: '' });
          setSearchResults([]);
          setSearchTerm('');
        }, 5000);
      } else {
        alert('Failed to submit quote: ' + (data.data?.message || 'Unknown error'));
      }
    } catch (error) {
      console.error('Submit error:', error);
      alert('Failed to submit quote. Check console.');
    } finally {
      setLoading(false);
    }
  };

  const totals = calculateTotal();

  return React.createElement('div', { style: { background: '#f9fafb', padding: '20px', minHeight: '80vh' } },
    React.createElement('div', { style: { maxWidth: '1400px', margin: '0 auto' } },
      // Search section
      React.createElement('div', { style: { background: 'white', borderRadius: '8px', padding: '20px', marginBottom: '20px', boxShadow: '0 1px 3px rgba(0,0,0,0.1)' } },
        React.createElement('h2', { style: { marginBottom: '15px', fontSize: '18px' } }, 'Search Products'),
        React.createElement('div', { style: { display: 'flex', gap: '10px' } },
          React.createElement('input', {
            type: 'text',
            placeholder: 'Search by model, manufacturer, or caliber...',
            value: searchTerm,
            onChange: (e) => setSearchTerm(e.target.value),
            onKeyPress: (e) => e.key === 'Enter' && handleSearch(),
            style: { flex: 1, padding: '10px', border: '1px solid #ddd', borderRadius: '6px', fontSize: '14px' }
          }),
          React.createElement('button', {
            onClick: handleSearch,
            disabled: loading,
            style: { padding: '10px 20px', background: loading ? '#999' : '#2563eb', color: 'white', border: 'none', borderRadius: '6px', cursor: loading ? 'not-allowed' : 'pointer', fontWeight: '500' }
          }, loading ? 'Searching...' : 'Search')
        )
      ),
      
      // Results and quote builder
      React.createElement('div', { style: { display: 'grid', gridTemplateColumns: '2fr 1fr', gap: '20px' } },
        // Left - Results
        React.createElement('div', null,
          searchResults.length === 0 && !loading ?
            React.createElement('div', { style: { background: 'white', borderRadius: '8px', padding: '60px 20px', textAlign: 'center', boxShadow: '0 1px 3px rgba(0,0,0,0.1)' } },
              React.createElement('p', { style: { color: '#666', fontSize: '16px' } }, '🔍 Search for products to start building your quote')
            )
          : React.createElement('div', { style: { background: 'white', borderRadius: '8px', padding: '20px', boxShadow: '0 1px 3px rgba(0,0,0,0.1)' } },
              React.createElement('h3', { style: { marginBottom: '15px' } }, `Found ${searchResults.length} products`),
              searchResults.map(product =>
                React.createElement('div', { key: product.id, style: { border: '1px solid #e5e7eb', borderRadius: '8px', padding: '15px', marginBottom: '15px' } },
                  React.createElement('h4', { style: { marginBottom: '10px', fontSize: '16px' } }, product.name),
                  React.createElement('p', { style: { fontSize: '14px', color: '#666', marginBottom: '10px' } }, `${product.manufacturer} - ${product.caliber}`),
                  React.createElement('div', { style: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '10px' } },
                    product.distributors.map((dist, idx) =>
                      React.createElement('div', { key: idx, style: { border: '2px solid', borderColor: dist.in_stock ? '#86efac' : '#fde047', background: dist.in_stock ? '#f0fdf4' : '#fefce8', borderRadius: '6px', padding: '12px' } },
                        React.createElement('div', { style: { fontWeight: '600', fontSize: '13px', marginBottom: '8px' } }, dist.name),
                        React.createElement('div', { style: { fontSize: '20px', fontWeight: 'bold', color: '#2563eb', marginBottom: '8px' } }, `$${dist.price.toFixed(2)}`),
                        React.createElement('div', { style: { fontSize: '12px', color: '#666', marginBottom: '4px' } }, `Stock: ${dist.stock || 'N/A'}`),
                        React.createElement('div', { style: { fontSize: '12px', color: '#666', marginBottom: '8px' } }, dist.shipping),
                        React.createElement('button', {
                          onClick: () => addToQuote(product, dist),
                          disabled: !dist.in_stock,
                          style: { width: '100%', padding: '8px', background: dist.in_stock ? '#16a34a' : '#d1d5db', color: 'white', border: 'none', borderRadius: '4px', cursor: dist.in_stock ? 'pointer' : 'not-allowed', fontSize: '13px', fontWeight: '500' }
                        }, 'Add to Quote')
                      )
                    )
                  )
                )
              )
            )
        ),
        
        // Right - Customer & Quote
        React.createElement('div', { style: { display: 'flex', flexDirection: 'column', gap: '20px' } },
          // Customer info
          React.createElement('div', { style: { background: 'white', borderRadius: '8px', padding: '20px', boxShadow: '0 1px 3px rgba(0,0,0,0.1)' } },
            React.createElement('h3', { style: { marginBottom: '15px' } }, 'Customer Information'),
            React.createElement('input', {
              type: 'text',
              placeholder: 'Full Name',
              value: customerInfo.name,
              onChange: (e) => setCustomerInfo({ ...customerInfo, name: e.target.value }),
              style: { width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '6px', marginBottom: '10px' }
            }),
            React.createElement('input', {
              type: 'email',
              placeholder: 'Email',
              value: customerInfo.email,
              onChange: (e) => setCustomerInfo({ ...customerInfo, email: e.target.value }),
              style: { width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '6px', marginBottom: '10px' }
            }),
            React.createElement('input', {
              type: 'tel',
              placeholder: 'Phone',
              value: customerInfo.phone,
              onChange: (e) => setCustomerInfo({ ...customerInfo, phone: e.target.value }),
              style: { width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '6px' }
            })
          ),
          
          // Quote items
          React.createElement('div', { style: { background: 'white', borderRadius: '8px', padding: '20px', boxShadow: '0 1px 3px rgba(0,0,0,0.1)' } },
            React.createElement('h3', { style: { marginBottom: '15px' } }, `Quote Items (${quoteItems.length})`),
            quoteItems.length === 0 ?
              React.createElement('p', { style: { textAlign: 'center', color: '#666', padding: '20px' } }, 'No items yet')
            : React.createElement('div', null,
                quoteItems.map(item =>
                  React.createElement('div', { key: item.itemId, style: { border: '1px solid #e5e7eb', borderRadius: '6px', padding: '10px', marginBottom: '10px' } },
                    React.createElement('div', { style: { display: 'flex', justifyContent: 'space-between', marginBottom: '5px' } },
                      React.createElement('strong', { style: { fontSize: '14px' } }, item.name),
                      React.createElement('button', {
                        onClick: () => removeFromQuote(item.itemId),
                        style: { background: 'transparent', border: 'none', color: '#ef4444', cursor: 'pointer', fontSize: '18px' }
                      }, '×')
                    ),
                    React.createElement('div', { style: { fontSize: '12px', color: '#666' } },
                      React.createElement('div', null, `From: ${item.distributor}`),
                      React.createElement('div', null, `Price: $${item.price.toFixed(2)}`)
                    )
                  )
                ),
                React.createElement('div', { style: { borderTop: '2px solid #e5e7eb', paddingTop: '15px', marginTop: '15px' } },
                  React.createElement('div', { style: { display: 'flex', justifyContent: 'space-between', marginBottom: '8px' } },
                    React.createElement('span', null, 'Subtotal:'),
                    React.createElement('span', null, `$${totals.subtotal.toFixed(2)}`)
                  ),
                  React.createElement('div', { style: { display: 'flex', justifyContent: 'space-between', marginBottom: '8px' } },
                    React.createElement('span', null, 'Tax:'),
                    React.createElement('span', null, `$${totals.tax.toFixed(2)}`)
                  ),
                  React.createElement('div', { style: { display: 'flex', justifyContent: 'space-between', marginBottom: '12px' } },
                    React.createElement('span', null, 'Transfer:'),
                    React.createElement('span', null, `$${totals.transferFee.toFixed(2)}`)
                  ),
                  React.createElement('div', { style: { display: 'flex', justifyContent: 'space-between', fontSize: '18px', fontWeight: 'bold', borderTop: '2px solid #111', paddingTop: '12px' } },
                    React.createElement('span', null, 'Total:'),
                    React.createElement('span', null, `$${totals.total.toFixed(2)}`)
                  )
                ),
                React.createElement('button', {
                  onClick: handleSubmitQuote,
                  disabled: !customerInfo.name || !customerInfo.email || loading,
                  style: { width: '100%', padding: '12px', marginTop: '15px', background: '#16a34a', color: 'white', border: 'none', borderRadius: '6px', cursor: 'pointer', fontWeight: 'bold', fontSize: '16px' }
                }, loading ? 'Submitting...' : 'Submit Quote')
              )
          )
        )
      ),
      
      // Success modal
      showSuccess && React.createElement('div', { style: { position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000 } },
        React.createElement('div', { style: { background: 'white', borderRadius: '8px', padding: '40px', maxWidth: '400px', textAlign: 'center' } },
          React.createElement('div', { style: { fontSize: '48px', marginBottom: '20px' } }, '✅'),
          React.createElement('h2', { style: { marginBottom: '10px' } }, 'Quote Submitted!'),
          React.createElement('p', null, `Password reset sent to: ${customerInfo.email}`)
        )
      )
    )
  );
};

window.VisualQuoteGenerator = VisualQuoteGenerator;
