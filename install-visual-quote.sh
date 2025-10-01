#!/bin/bash
# Complete Visual Quote Generator with Multi-Distributor Integration
# Location: /opt/fflbro/wordpress-main/wp-content/plugins/ffl-bro-enhanced-pro/

echo "🎨 Installing Visual Quote Generator with Multi-Distributor Pricing..."
echo "======================================================================="

# Step 1: Create the React component
echo ""
echo "Step 1: Creating React component..."

mkdir -p components

cat > components/visual-quote-generator.jsx << 'REACT_EOF'
import React, { useState, useEffect } from 'react';
import { Search, Plus, X, Mail, Phone, User, ShoppingCart, Package, Check, Truck, DollarSign, Clock } from 'lucide-react';

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

  // Search products across all distributors
  const handleSearch = async () => {
    if (!searchTerm || searchTerm.length < 2) {
      alert('Please enter at least 2 characters');
      return;
    }

    setLoading(true);
    
    try {
      const response = await fetch(ajaxurl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'fflbro_search_multi_distributor',
          nonce: fflbroQuote.nonce,
          search: searchTerm
        })
      });

      const data = await response.json();
      
      if (data.success) {
        setSearchResults(data.data.products);
      } else {
        alert('Search failed: ' + data.data.message);
      }
    } catch (error) {
      console.error('Search error:', error);
      alert('Search failed. Please try again.');
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
      const response = await fetch(ajaxurl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'fflbro_submit_visual_quote',
          nonce: fflbroQuote.nonce,
          customer: JSON.stringify(customerInfo),
          items: JSON.stringify(quoteItems),
          totals: JSON.stringify(calculateTotal())
        })
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
        alert('Failed to submit quote: ' + data.data.message);
      }
    } catch (error) {
      console.error('Submit error:', error);
      alert('Failed to submit quote. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const totals = calculateTotal();

  return (
    <div className="min-h-screen bg-gray-50 p-4">
      <div className="max-w-6xl mx-auto">
        {/* Header */}
        <div className="bg-white rounded-lg shadow-md p-6 mb-6">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Get Your Multi-Distributor Quote</h1>
          <p className="text-gray-600">Search products and compare pricing from Lipseys, RSR, and Davidsons</p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Left Column - Search & Results */}
          <div className="lg:col-span-2 space-y-6">
            {/* Search Box */}
            <div className="bg-white rounded-lg shadow-md p-6">
              <h2 className="text-xl font-bold mb-4">Search Products</h2>
              <div className="flex gap-3">
                <input
                  type="text"
                  placeholder="Search by model, manufacturer, or caliber..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                  className="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  disabled={loading}
                />
                <button
                  onClick={handleSearch}
                  disabled={loading}
                  className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition flex items-center gap-2"
                >
                  <Search className="w-5 h-5" />
                  {loading ? 'Searching...' : 'Search'}
                </button>
              </div>
            </div>

            {/* Product Results */}
            {searchResults.length > 0 && (
              <div className="bg-white rounded-lg shadow-md p-6">
                <h2 className="text-xl font-bold mb-4">Search Results</h2>
                <div className="space-y-6">
                  {searchResults.map(product => (
                    <div key={product.id} className="border border-gray-200 rounded-lg p-4">
                      <div className="flex gap-4 mb-4">
                        {/* Image Placeholder */}
                        <div className="bg-gray-100 rounded-lg w-32 h-32 flex-shrink-0 flex items-center justify-center">
                          <Package className="w-16 h-16 text-gray-400" />
                        </div>
                        
                        {/* Product Info */}
                        <div className="flex-1">
                          <h3 className="text-lg font-bold text-gray-900 mb-1">{product.name}</h3>
                          <p className="text-sm text-gray-600 mb-1">{product.manufacturer}</p>
                          {product.caliber && (
                            <p className="text-sm text-gray-500 mb-1">Caliber: {product.caliber}</p>
                          )}
                          <p className="text-xs text-gray-500">Category: {product.category}</p>
                        </div>
                      </div>

                      {/* Multi-Distributor Pricing */}
                      <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                        {product.distributors.map((dist, idx) => (
                          <div key={idx} className={`border-2 rounded-lg p-3 ${
                            dist.in_stock ? 'border-green-200 bg-green-50' : 'border-yellow-200 bg-yellow-50'
                          }`}>
                            <div className="flex items-center justify-between mb-2">
                              <span className="font-semibold text-sm">{dist.name}</span>
                              <span className={`text-xs px-2 py-1 rounded ${
                                dist.in_stock ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800'
                              }`}>
                                {dist.in_stock ? `✓ ${dist.stock}` : dist.eta}
                              </span>
                            </div>

                            <div className="space-y-1 mb-3">
                              <div className="flex justify-between items-center">
                                <span className="text-xs text-gray-600">Price:</span>
                                <span className="text-lg font-bold text-blue-600">
                                  ${dist.price.toFixed(2)}
                                </span>
                              </div>
                              
                              <div className="flex items-center gap-1 text-xs text-gray-600">
                                <Truck className="w-3 h-3" />
                                <span>{dist.shipping || 'Free shipping'}</span>
                              </div>
                              
                              <div className="flex items-center gap-1 text-xs text-gray-600">
                                <Clock className="w-3 h-3" />
                                <span>{dist.eta || '3-5 days'}</span>
                              </div>

                              <div className="text-xs text-gray-500">
                                SKU: {dist.sku}
                              </div>
                            </div>

                            <button
                              onClick={() => addToQuote(product, dist)}
                              disabled={!dist.in_stock || quoteItems.find(item => item.itemId === `${product.id}-${dist.distributor_id}`)}
                              className="w-full px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition text-sm font-medium"
                            >
                              {quoteItems.find(item => item.itemId === `${product.id}-${dist.distributor_id}`) 
                                ? '✓ Added' 
                                : <><Plus className="w-3 h-3 inline mr-1" />Add to Quote</>}
                            </button>
                          </div>
                        ))}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Initial State */}
            {searchResults.length === 0 && !searchTerm && !loading && (
              <div className="bg-white rounded-lg shadow-md p-12 text-center">
                <Package className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                <p className="text-gray-500 text-lg">Search for products to start building your quote</p>
                <p className="text-gray-400 text-sm mt-2">We'll compare prices from all distributors</p>
              </div>
            )}
          </div>

          {/* Right Column - Customer Info & Quote */}
          <div className="space-y-6">
            {/* Customer Information */}
            <div className="bg-white rounded-lg shadow-md p-6">
              <h2 className="text-xl font-bold mb-4">Your Information</h2>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    <User className="w-4 h-4 inline mr-2" />
                    Full Name *
                  </label>
                  <input
                    type="text"
                    value={customerInfo.name}
                    onChange={(e) => setCustomerInfo({ ...customerInfo, name: e.target.value })}
                    placeholder="John Doe"
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    <Mail className="w-4 h-4 inline mr-2" />
                    Email Address *
                  </label>
                  <input
                    type="email"
                    value={customerInfo.email}
                    onChange={(e) => setCustomerInfo({ ...customerInfo, email: e.target.value })}
                    placeholder="john@example.com"
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    required
                  />
                  <p className="text-xs text-gray-500 mt-1">We'll email you a password to access your account</p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    <Phone className="w-4 h-4 inline mr-2" />
                    Phone Number *
                  </label>
                  <input
                    type="tel"
                    value={customerInfo.phone}
                    onChange={(e) => setCustomerInfo({ ...customerInfo, phone: e.target.value })}
                    placeholder="(555) 123-4567"
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>
              </div>
            </div>

            {/* Quote Items */}
            <div className="bg-white rounded-lg shadow-md p-6">
              <h2 className="text-xl font-bold mb-4 flex items-center gap-2">
                <ShoppingCart className="w-5 h-5" />
                Your Quote ({quoteItems.length})
              </h2>

              {quoteItems.length === 0 ? (
                <div className="text-center py-8 text-gray-500">
                  <p>No items added yet</p>
                  <p className="text-sm mt-1">Search and add products to get started</p>
                </div>
              ) : (
                <>
                  <div className="space-y-3 mb-4 max-h-96 overflow-y-auto">
                    {quoteItems.map(item => (
                      <div key={item.itemId} className="border border-gray-200 rounded-lg p-3">
                        <div className="flex items-start gap-3 mb-2">
                          <div className="bg-gray-100 rounded p-2">
                            <Package className="w-6 h-6 text-gray-400" />
                          </div>
                          <div className="flex-1 min-w-0">
                            <p className="font-medium text-gray-900 text-sm">{item.name}</p>
                            <p className="text-xs text-gray-600">{item.manufacturer}</p>
                            <p className="text-xs text-gray-500">SKU: {item.sku}</p>
                          </div>
                          <button
                            onClick={() => removeFromQuote(item.itemId)}
                            className="p-1 text-red-500 hover:bg-red-50 rounded"
                          >
                            <X className="w-4 h-4" />
                          </button>
                        </div>
                        
                        <div className="bg-gray-50 rounded p-2 space-y-1">
                          <div className="flex justify-between text-xs">
                            <span className="text-gray-600">From:</span>
                            <span className="font-medium">{item.distributor}</span>
                          </div>
                          <div className="flex justify-between text-xs">
                            <span className="text-gray-600">Price:</span>
                            <span className="font-bold text-blue-600">${item.price.toFixed(2)}</span>
                          </div>
                          <div className="flex justify-between text-xs">
                            <span className="text-gray-600">Shipping:</span>
                            <span>{item.shipping}</span>
                          </div>
                          <div className="flex justify-between text-xs">
                            <span className="text-gray-600">ETA:</span>
                            <span>{item.eta}</span>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>

                  {/* Totals */}
                  <div className="border-t border-gray-200 pt-4 space-y-2">
                    <div className="flex justify-between text-gray-600">
                      <span>Subtotal:</span>
                      <span>${totals.subtotal.toFixed(2)}</span>
                    </div>
                    <div className="flex justify-between text-gray-600">
                      <span>Tax (8.25%):</span>
                      <span>${totals.tax.toFixed(2)}</span>
                    </div>
                    <div className="flex justify-between text-gray-600">
                      <span>Transfer Fee:</span>
                      <span>${totals.transferFee.toFixed(2)}</span>
                    </div>
                    <div className="flex justify-between text-xl font-bold text-gray-900 pt-2 border-t border-gray-300">
                      <span>Total:</span>
                      <span>${totals.total.toFixed(2)}</span>
                    </div>
                  </div>

                  {/* Submit Button */}
                  <button
                    onClick={handleSubmitQuote}
                    disabled={!customerInfo.name || !customerInfo.email || !customerInfo.phone || loading}
                    className="w-full mt-6 px-6 py-4 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition font-bold text-lg"
                  >
                    {loading ? 'Submitting...' : 'Submit Quote Request'}
                  </button>
                  
                  <p className="text-xs text-gray-500 text-center mt-2">
                    We'll create your account and email you a password reset link
                  </p>
                </>
              )}
            </div>
          </div>
        </div>

        {/* Success Modal */}
        {showSuccess && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div className="bg-white rounded-lg p-8 max-w-md w-full text-center">
              <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <Check className="w-10 h-10 text-green-600" />
              </div>
              <h2 className="text-2xl font-bold text-gray-900 mb-2">Quote Submitted Successfully!</h2>
              <p className="text-gray-600 mb-4">
                We've created your account and sent a password reset email to:
              </p>
              <p className="font-medium text-blue-600 mb-4">{customerInfo.email}</p>
              <p className="text-sm text-gray-500 mb-2">
                Check your email to set your password and access your quote!
              </p>
              <p className="text-xs text-gray-400">
                Quote #: Q-{Date.now().toString().slice(-6)}
              </p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

// Make component available globally
window.VisualQuoteGenerator = VisualQuoteGenerator;
REACT_EOF

echo "✅ React component created"

# Step 2: Create backend PHP (continuation in next message due to length)
