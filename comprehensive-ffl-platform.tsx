import React, { useState, useEffect, useRef } from 'react';
import { 
  Settings, Shield, Package, Globe, Building2, FileText, Users, BarChart3,
  Search, Plus, Minus, X, Calculator, Mail, Send, Download, TrendingUp,
  AlertCircle, CheckCircle, Clock, DollarSign, Eye, Edit, Zap, Wifi, WifiOff,
  Database, Server, Key, Lock, Unlock, RefreshCw, Upload, Save, User,
  Phone, MapPin, Calendar, CreditCard, PenTool, HelpCircle, Trash2,
  ExternalLink, Filter, ChevronDown, ChevronUp, Star, Info, Target,
  Truck, Link, Camera, Scan, Flag, AlertTriangle, CheckCircle2
} from 'lucide-react';

// Configuration and Settings Management
const FFLBroConfig = {
  version: '4.0.0',
  apiEndpoints: {
    gunbroker: 'https://api.gunbroker.com/v1',
    lipseys: 'https://api.lipseys.com/v2',
    rsr: 'https://api.rsrgroup.com/v1',
    davidsons: 'https://api.davidsonsinc.com/v1'
  },
  mockMode: true,
  debug: true
};

// Mock Data Store
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
    name: 'Neefeco Arms',
    ffl: '1-47-012-34-5F-06789',
    address: '123 Main Street, St. Cloud, FL 34769',
    phone: '(407) 555-0123',
    email: 'quotes@neefecoarms.com',
    website: 'www.neefecoarms.com',
    transferFee: 25.00,
    backgroundCheckFee: 5.00,
    defaultMarkup: 25,
    minimumMargin: 10,
    taxRate: 7.0,
    ccProcessingFee: 2.9
  }
};

// Main Platform Component
const FFLBroPlatform = () => {
  const [activeModule, setActiveModule] = useState('dashboard');
  const [settings, setSettings] = useState(mockData.businessSettings);
  const [apiSettings, setApiSettings] = useState({
    gunbroker: { enabled: false, username: '', password: '', devKey: '' },
    lipseys: { enabled: false, username: '', password: '', apiKey: '' },
    rsr: { enabled: false, username: '', password: '', apiKey: '' },
    davidsons: { enabled: false, username: '', password: '', apiKey: '' }
  });
  const [mockMode, setMockMode] = useState(FFLBroConfig.mockMode);

  // Navigation Component
  const Navigation = () => {
    const modules = [
      { id: 'dashboard', label: 'Dashboard', icon: BarChart3, desc: 'Overview & Analytics' },
      { id: 'quotes', label: 'Quote Generator', icon: Calculator, desc: 'Create Professional Quotes' },
      { id: 'gunbroker', label: 'GunBroker', icon: Globe, desc: 'Marketplace Management' },
      { id: 'lipseys', label: 'Lipseys', icon: Building2, desc: 'Distributor Integration' },
      { id: 'inventory', label: 'Inventory', icon: Package, desc: 'Stock Management' },
      { id: 'form4473', label: 'Form 4473', icon: FileText, desc: 'Digital Processing' },
      { id: 'settings', label: 'Settings', icon: Settings, desc: 'Configuration' }
    ];

    return (
      <div className="bg-white shadow-sm border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4">
          <div className="flex items-center justify-between py-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                <Zap className="w-5 h-5 text-white" />
              </div>
              <div>
                <h1 className="text-xl font-bold text-gray-900">FFL-BRO Platform v{FFLBroConfig.version}</h1>
                <p className="text-sm text-gray-600">Comprehensive FFL Business Management</p>
              </div>
            </div>
            
            <div className="flex items-center gap-4">
              <div className="flex items-center gap-2">
                <div className={`w-3 h-3 rounded-full ${mockMode ? 'bg-yellow-500' : 'bg-green-500'}`} />
                <span className="text-sm text-gray-600">
                  {mockMode ? 'Mock Data' : 'Live Data'}
                </span>
              </div>
              
              <button
                onClick={() => setMockMode(!mockMode)}
                className="flex items-center gap-2 px-3 py-1 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm"
              >
                {mockMode ? <WifiOff className="w-4 h-4" /> : <Wifi className="w-4 h-4" />}
                {mockMode ? 'Enable Live' : 'Use Mock'}
              </button>
            </div>
          </div>
          
          <div className="flex flex-wrap gap-2 pb-4">
            {modules.map(module => {
              const Icon = module.icon;
              return (
                <button
                  key={module.id}
                  onClick={() => setActiveModule(module.id)}
                  className={`flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all ${
                    activeModule === module.id 
                      ? 'bg-blue-600 text-white shadow-lg' 
                      : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                  }`}
                >
                  <Icon className="w-4 h-4" />
                  <span className="hidden sm:inline">{module.label}</span>
                </button>
              );
            })}
          </div>
        </div>
      </div>
    );
  };

  // Dashboard Module
  const Dashboard = () => {
    const stats = [
      { label: 'Active Quotes', value: '23', change: '+12%', icon: Calculator, color: 'blue' },
      { label: 'GunBroker Listings', value: '247', change: '+8%', icon: Globe, color: 'green' },
      { label: 'Monthly Revenue', value: '$18,750', change: '+15%', icon: DollarSign, color: 'purple' },
      { label: 'Form 4473s', value: '67', change: '+22%', icon: FileText, color: 'orange' }
    ];

    return (
      <div className="space-y-6">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {stats.map((stat, index) => {
            const Icon = stat.icon;
            return (
              <div key={index} className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-gray-600 mb-1">{stat.label}</p>
                    <p className="text-2xl font-bold text-gray-900">{stat.value}</p>
                    <p className="text-sm text-green-600 mt-1">{stat.change}</p>
                  </div>
                  <div className={`w-12 h-12 bg-${stat.color}-100 rounded-full flex items-center justify-center`}>
                    <Icon className={`w-6 h-6 text-${stat.color}-600`} />
                  </div>
                </div>
              </div>
            );
          })}
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="bg-white p-6 rounded-xl border border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
            <div className="space-y-4">
              {[
                { type: 'quote', desc: 'Quote #Q2025-001 sent to John Smith', time: '2 min ago' },
                { type: 'listing', desc: 'New GunBroker listing: Glock 19', time: '15 min ago' },
                { type: 'form', desc: 'Form 4473 completed for Sarah Davis', time: '1 hour ago' }
              ].map((activity, index) => (
                <div key={index} className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                  <div className="w-2 h-2 bg-blue-500 rounded-full" />
                  <div className="flex-1">
                    <p className="text-sm text-gray-900">{activity.desc}</p>
                    <p className="text-xs text-gray-500">{activity.time}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div className="bg-white p-6 rounded-xl border border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">API Status</h3>
            <div className="space-y-3">
              {Object.entries(apiSettings).map(([api, config]) => (
                <div key={api} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                  <div className="flex items-center gap-3">
                    <div className={`w-3 h-3 rounded-full ${config.enabled ? 'bg-green-500' : 'bg-gray-400'}`} />
                    <span className="font-medium capitalize">{api}</span>
                  </div>
                  <span className={`text-sm ${config.enabled ? 'text-green-600' : 'text-gray-500'}`}>
                    {config.enabled ? 'Connected' : 'Disabled'}
                  </span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    );
  };

  // Quote Generator Module (Enhanced)
  const QuoteGenerator = () => {
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedProduct, setSelectedProduct] = useState(null);
    const [selectedDistributor, setSelectedDistributor] = useState(null);
    const [quote, setQuote] = useState({ items: [], customer: {}, notes: '' });

    const filteredProducts = mockData.products.filter(product =>
      product.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      product.manufacturer.toLowerCase().includes(searchTerm.toLowerCase()) ||
      product.sku.toLowerCase().includes(searchTerm.toLowerCase())
    );

    return (
      <div className="space-y-6">
        <div className="bg-white p-6 rounded-xl border border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">Product Search</h2>
          <div className="relative">
            <Search className="absolute left-3 top-3 w-5 h-5 text-gray-400" />
            <input
              type="text"
              placeholder="Search products by name, manufacturer, or SKU..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>

        {searchTerm && filteredProducts.length > 0 && (
          <div className="bg-white p-6 rounded-xl border border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">
              Search Results ({filteredProducts.length})
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {filteredProducts.map(product => (
                <div
                  key={product.id}
                  className={`p-4 border rounded-lg cursor-pointer transition-all ${
                    selectedProduct?.id === product.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'
                  }`}
                  onClick={() => setSelectedProduct(product)}
                >
                  <img src={product.image} alt={product.name} className="w-full h-32 object-cover rounded mb-3" />
                  <h4 className="font-medium text-gray-900">{product.name}</h4>
                  <p className="text-sm text-gray-600">{product.manufacturer}</p>
                  <div className="mt-2 text-sm text-gray-500">
                    <div>SKU: {product.sku}</div>
                    <div>MSRP: ${product.msrp.toFixed(2)}</div>
                    <div>Best: ${Math.min(...product.distributors.map(d => d.price)).toFixed(2)}</div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {selectedProduct && (
          <div className="bg-white p-6 rounded-xl border border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">
              Distributor Pricing for {selectedProduct.name}
            </h3>
            <div className="space-y-3">
              {selectedProduct.distributors.map((distributor, index) => (
                <div
                  key={index}
                  className={`p-4 border rounded-lg cursor-pointer transition-all ${
                    selectedDistributor?.name === distributor.name ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'
                  }`}
                  onClick={() => setSelectedDistributor(distributor)}
                >
                  <div className="flex justify-between items-start">
                    <div>
                      <div className="font-medium">{distributor.name}</div>
                      <div className="text-sm text-gray-600">
                        Stock: {distributor.stock} • ETA: {distributor.eta}
                      </div>
                      <div className="text-sm text-gray-500">
                        Rating: {distributor.rating}/5 • Reliability: {distributor.reliability}%
                      </div>
                    </div>
                    <div className="text-right">
                      <div className="text-lg font-semibold text-green-600">${distributor.price.toFixed(2)}</div>
                      <div className="text-sm text-gray-500">{distributor.shipping}</div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="bg-white p-6 rounded-xl border border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Customer Information</h3>
            <div className="space-y-4">
              <input
                type="text"
                placeholder="Customer Name"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              />
              <input
                type="email"
                placeholder="Email Address"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              />
              <input
                type="tel"
                placeholder="Phone Number"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div className="bg-white p-6 rounded-xl border border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Quote Actions</h3>
            <div className="space-y-3">
              <button className="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center justify-center gap-2">
                <Calculator className="w-4 h-4" />
                Generate Quote
              </button>
              <button className="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center justify-center gap-2">
                <Send className="w-4 h-4" />
                Email Quote
              </button>
              <button className="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center justify-center gap-2">
                <Download className="w-4 h-4" />
                PDF Quote
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  };

  // GunBroker Module
  const GunBrokerModule = () => {
    const [searchTerm, setSearchTerm] = useState('');

    return (
      <div className="space-y-6">
        <div className="bg-white p-6 rounded-xl border border-gray-200">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-3">
              <Globe className="w-6 h-6 text-blue-600" />
              <div>
                <h2 className="text-xl font-semibold text-gray-900">GunBroker Marketplace</h2>
                <p className="text-sm text-gray-600">
                  Status: {apiSettings.gunbroker.enabled ? 'Connected' : 'Disconnected'}
                </p>
              </div>
            </div>
            <div className="flex gap-2">
              <button className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                <Plus className="w-4 h-4" />
                New Listing
              </button>
              <button className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 flex items-center gap-2">
                <RefreshCw className="w-4 h-4" />
                Sync
              </button>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="p-4 bg-blue-50 rounded-lg">
              <div className="flex items-center gap-2 mb-2">
                <Package className="w-5 h-5 text-blue-600" />
                <span className="font-medium text-blue-900">Active Listings</span>
              </div>
              <div className="text-2xl font-bold text-blue-900">247</div>
              <div className="text-sm text-blue-700">+12.5% this month</div>
            </div>
            <div className="p-4 bg-green-50 rounded-lg">
              <div className="flex items-center gap-2 mb-2">
                <Eye className="w-5 h-5 text-green-600" />
                <span className="font-medium text-green-900">Total Views</span>
              </div>
              <div className="text-2xl font-bold text-green-900">15,420</div>
              <div className="text-sm text-green-700">+8.2% this week</div>
            </div>
            <div className="p-4 bg-purple-50 rounded-lg">
              <div className="flex items-center gap-2 mb-2">
                <Target className="w-5 h-5 text-purple-600" />
                <span className="font-medium text-purple-900">Conversion Rate</span>
              </div>
              <div className="text-2xl font-bold text-purple-900">12.5%</div>
              <div className="text-sm text-purple-700">+2.1% improvement</div>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl border border-gray-200">
          <div className="p-6 border-b border-gray-200">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-semibold text-gray-900">Active Listings</h3>
              <div className="flex items-center gap-2">
                <div className="relative">
                  <Search className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                  <input
                    type="text"
                    placeholder="Search listings..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <button className="p-2 border border-gray-200 rounded-lg hover:bg-gray-50">
                  <Filter className="w-4 h-4 text-gray-600" />
                </button>
              </div>
            </div>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr>
                  <th className="text-left p-4 font-medium text-gray-700">Item</th>
                  <th className="text-left p-4 font-medium text-gray-700">Current Bid</th>
                  <th className="text-left p-4 font-medium text-gray-700">Buy Now</th>
                  <th className="text-left p-4 font-medium text-gray-700">Bids</th>
                  <th className="text-left p-4 font-medium text-gray-700">Time Left</th>
                  <th className="text-left p-4 font-medium text-gray-700">Views</th>
                  <th className="text-left p-4 font-medium text-gray-700">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {mockData.gunbrokerListings.map(listing => (
                  <tr key={listing.id} className="hover:bg-gray-50">
                    <td className="p-4">
                      <div>
                        <p className="font-medium text-gray-900">{listing.title}</p>
                        <p className="text-sm text-gray-600">{listing.category}</p>
                      </div>
                    </td>
                    <td className="p-4">
                      <span className="font-semibold text-green-600">${listing.currentBid}</span>
                    </td>
                    <td className="p-4">
                      <span className="text-gray-900">${listing.buyNow}</span>
                    </td>
                    <td className="p-4">
                      <span className="text-gray-900">{listing.bids}</span>
                    </td>
                    <td className="p-4">
                      <span className="text-gray-900">{listing.timeLeft}</span>
                    </td>
                    <td className="p-4">
                      <span className="text-gray-600">{listing.views}</span>
                    </td>
                    <td className="p-4">
                      <div className="flex items-center gap-2">
                        <button className="p-1 text-gray-600 hover:text-blue-600">
                          <Eye className="w-4 h-4" />
                        </button>
                        <button className="p-1 text-gray-600 hover:text-blue-600">
                          <Edit className="w-4 h-4" />
                        </button>
                        <button className="p-1 text-gray-600 hover:text-blue-600">
                          <ExternalLink className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    );
  };

  // Lipseys Module
  const LipseysModule = () => {
    return (
      <div className="space-y-6">
        <div className="bg-white p-6 rounded-xl border border-gray-200">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-3">
              <Building2 className="w-6 h-6 text-orange-600" />
              <div>
                <h2 className="text-xl font-semibold text-gray-900">Lipseys Distributor</h2>
                <p className="text-sm text-gray-600">
                  Status: {apiSettings.lipseys.enabled ? 'Connected' : 'Disconnected'}
                </p>
              </div>
            </div>
            <div className="flex gap-2">
              <button className="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 flex items-center gap-2">
                <Download className="w-4 h-4" />
                Download Catalog
              </button>
              <button className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 flex items-center gap-2">
                <RefreshCw className="w-4 h-4" />
                Sync Inventory
              </button>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div className="p-4 bg-orange-50 rounded-lg">
              <div className="flex items-center gap-2 mb-2">
                <Package className="w-5 h-5 text-orange-600" />
                <span className="font-medium text-orange-900">Available Items</span>
              </div>
              <div className="text-2xl font-bold text-orange-900">45,000+</div>
            </div>
            <div className="p-4 bg-green-50 rounded-lg">
              <div className="flex items-center gap-2 mb-2">
                <CheckCircle className="w-5 h-5 text-green-600" />
                <span className="font-medium text-green-900">In Stock</span>
              </div>
              <div className="text-2xl font-bold text-green-900">38,500+</div>
            </div>
            <div className="p-4 bg-blue-50 rounded-lg">
              <div className="flex items-center gap-2 mb-2">
                <Users className="w-5 h-5 text-blue-600" />
                <span className="font-medium text-blue-900">Orders</span>
              </div>
              <div className="text-2xl font-bold text-blue-900">15</div>
              <div className="text-sm text-blue-700">This month</div>
            </div>
            <div className="p-4 bg-purple-50 rounded-lg">
              <div className="flex items-center gap-2 mb-2">
                <Truck className="w-5 h-5 text-purple-600" />
                <span className="font-medium text-purple-900">Avg. Delivery</span>
              </div>
              <div className="text-2xl font-bold text-purple-900">2.5 days</div>
            </div>
          </div>
        </div>

        <div className="bg-white p-6 rounded-xl border border-gray-200">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Product Catalog Search</h3>
          <div className="flex items-center gap-4 mb-6">
            <div className="flex-1 relative">
              <Search className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
              <input
                type="text"
                placeholder="Search products by brand, model, caliber..."
                className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500"
              />
            </div>
            <select className="px-4 py-2 border border-gray-200 rounded-lg">
              <option>All Categories</option>
              <option>Handguns</option>
              <option>Rifles</option>
              <option>Shotguns</option>
              <option>Ammunition</option>
              <option>Accessories</option>
            </select>
            <button className="bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700">
              Search
            </button>
          </div>

          <div className="text-center py-8 text-gray-500">
            <Building2 className="w-12 h-12 mx-auto mb-2 text-gray-400" />
            <p>Connect to Lipseys API to search their catalog</p>
            <p className="text-sm">Over 45,000 products available</p>
          </div>
        </div>
      </div>
    );
  };

  // Form 4473 Module (Simplified)
  const Form4473Module = () => {
    const [currentStep, setCurrentStep] = useState('overview');

    return (
      <div className="space-y-6">
        <div className="bg-white p-6 rounded-xl border border-gray-200">
          <div className="flex items-center gap-3 mb-4">
            <FileText className="w-6 h-6 text-blue-600" />
            <div>
              <h2 className="text-xl font-semibold text-gray-900">ATF Form 4473 Digital Processing</h2>
              <p className="text-sm text-gray-600">Streamlined compliance and record keeping</p>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div className="p-4 bg-blue-50 rounded-lg">
              <div className="flex items-center gap-2 mb-2">
                <FileText className="w-5 h-5 text-blue-600" />
                <span className="font-medium text-blue-900">Forms This Month</span>
              </div>
              <div className="text-2xl font-bold text-blue-900">67</div>
              <div className="text-sm text-blue-700">+18.5% increase</div>
            </div>
            <div className="p-4 bg-yellow-50 rounded-lg">
              <div className="flex items-center gap-2 mb-2">
                <Clock className="w-5 h-5 text-yellow-600" />
                <span className="font-medium text-yellow-900">Pending Review</span>
              </div>
              <div className="text-2xl font-bold text-yellow-900">3</div>
            </div>
            <div className="p-4 bg-green-50 rounded-lg">
              <div className="flex items-center gap-2 mb-2">
                <CheckCircle className="w-5 h-5 text-green-600" />
                <span className="font-medium text-green-900">NICS Approved</span>
              </div>
              <div className="text-2xl font-bold text-green-900">64</div>
              <div className="text-sm text-green-700">This month</div>
            </div>
            <div className="p-4 bg-purple-50 rounded-lg">
              <div className="flex items-center gap-2 mb-2">
                <Shield className="w-5 h-5 text-purple-600" />
                <span className="font-medium text-purple-900">Compliance Rate</span>
              </div>
              <div className="text-2xl font-bold text-purple-900">100%</div>
            </div>
          </div>
        </div>

        <div className="bg-white p-6 rounded-xl border border-gray-200">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-lg font-semibold text-gray-900">Form Actions</h3>
            <button className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
              <Plus className="w-4 h-4" />
              New Form 4473
            </button>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="p-6 border border-gray-200 rounded-lg hover:border-blue-300 cursor-pointer">
              <FileText className="w-8 h-8 text-blue-600 mb-3" />
              <h4 className="font-semibold text-gray-900 mb-2">Digital Form Creation</h4>
              <p className="text-sm text-gray-600">Create new ATF Form 4473 with guided workflow</p>
            </div>
            <div className="p-6 border border-gray-200 rounded-lg hover:border-green-300 cursor-pointer">
              <Database className="w-8 h-8 text-green-600 mb-3" />
              <h4 className="font-semibold text-gray-900 mb-2">Record Management</h4>
              <p className="text-sm text-gray-600">Manage and search existing form records</p>
            </div>
            <div className="p-6 border border-gray-200 rounded-lg hover:border-purple-300 cursor-pointer">
              <Shield className="w-8 h-8 text-purple-600 mb-3" />
              <h4 className="font-semibold text-gray-900 mb-2">Compliance Reports</h4>
              <p className="text-sm text-gray-600">Generate compliance and audit reports</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl border border-gray-200">
          <div className="p-6 border-b border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900">Recent Forms</h3>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr>
                  <th className="text-left p-4 font-medium text-gray-700">Form #</th>
                  <th className="text-left p-4 font-medium text-gray-700">Customer</th>
                  <th className="text-left p-4 font-medium text-gray-700">Firearm</th>
                  <th className="text-left p-4 font-medium text-gray-700">Date</th>
                  <th className="text-left p-4 font-medium text-gray-700">Status</th>
                  <th className="text-left p-4 font-medium text-gray-700">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {[
                  { formNumber: '4473-2025-001234', customer: 'John Smith', firearm: 'Glock G19 Gen5', date: '2025-09-07', status: 'Completed' },
                  { formNumber: '4473-2025-001235', customer: 'Sarah Johnson', firearm: 'Sig Sauer P320', date: '2025-09-06', status: 'NICS Check' },
                  { formNumber: '4473-2025-001236', customer: 'Mike Davis', firearm: 'S&W M&P15 Sport II', date: '2025-09-05', status: 'Completed' }
                ].map((form, index) => (
                  <tr key={index} className="hover:bg-gray-50">
                    <td className="p-4">
                      <span className="font-mono text-sm text-blue-600">{form.formNumber}</span>
                    </td>
                    <td className="p-4">
                      <p className="font-medium text-gray-900">{form.customer}</p>
                    </td>
                    <td className="p-4">
                      <p className="text-gray-900">{form.firearm}</p>
                    </td>
                    <td className="p-4">
                      <span className="text-gray-600">{form.date}</span>
                    </td>
                    <td className="p-4">
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                        form.status === 'Completed' ? 'bg-green-100 text-green-700' :
                        form.status === 'NICS Check' ? 'bg-yellow-100 text-yellow-700' :
                        'bg-blue-100 text-blue-700'
                      }`}>
                        {form.status}
                      </span>
                    </td>
                    <td className="p-4">
                      <div className="flex items-center gap-2">
                        <button className="p-1 text-gray-600 hover:text-blue-600">
                          <Eye className="w-4 h-4" />
                        </button>
                        <button className="p-1 text-gray-600 hover:text-blue-600">
                          <Edit className="w-4 h-4" />
                        </button>
                        <button className="p-1 text-gray-600 hover:text-blue-600">
                          <Download className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    );
  };

  // Settings Module
  const SettingsModule = () => {
    const [activeTab, setActiveTab] = useState('business');

    const tabs = [
      { id: 'business', label: 'Business Info', icon: Building2 },
      { id: 'apis', label: 'API Settings', icon: Database },
      { id: 'pricing', label: 'Pricing Rules', icon: DollarSign },
      { id: 'system', label: 'System', icon: Settings }
    ];

    return (
      <div className="space-y-6">
        <div className="bg-white p-6 rounded-xl border border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900 mb-6">Platform Settings</h2>
          
          <div className="flex flex-wrap gap-2 mb-6">
            {tabs.map(tab => {
              const Icon = tab.icon;
              return (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={`flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all ${
                    activeTab === tab.id 
                      ? 'bg-blue-600 text-white' 
                      : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                  }`}
                >
                  <Icon className="w-4 h-4" />
                  {tab.label}
                </button>
              );
            })}
          </div>

          {activeTab === 'business' && (
            <div className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Business Name</label>
                  <input
                    type="text"
                    value={settings.name}
                    onChange={(e) => setSettings({...settings, name: e.target.value})}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">FFL License Number</label>
                  <input
                    type="text"
                    value={settings.ffl}
                    onChange={(e) => setSettings({...settings, ffl: e.target.value})}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                  <input
                    type="tel"
                    value={settings.phone}
                    onChange={(e) => setSettings({...settings, phone: e.target.value})}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                  <input
                    type="email"
                    value={settings.email}
                    onChange={(e) => setSettings({...settings, email: e.target.value})}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div className="md:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 mb-2">Business Address</label>
                  <input
                    type="text"
                    value={settings.address}
                    onChange={(e) => setSettings({...settings, address: e.target.value})}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>
            </div>
          )}

          {activeTab === 'apis' && (
            <div className="space-y-6">
              {Object.entries(apiSettings).map(([api, config]) => (
                <div key={api} className="p-6 border border-gray-200 rounded-lg">
                  <div className="flex items-center justify-between mb-4">
                    <div className="flex items-center gap-3">
                      <div className={`w-4 h-4 rounded-full ${config.enabled ? 'bg-green-500' : 'bg-gray-400'}`} />
                      <h3 className="text-lg font-semibold text-gray-900 capitalize">{api} Integration</h3>
                    </div>
                    <button
                      onClick={() => setApiSettings({
                        ...apiSettings,
                        [api]: { ...config, enabled: !config.enabled }
                      })}
                      className={`px-4 py-2 rounded-lg font-medium ${
                        config.enabled 
                          ? 'bg-red-100 text-red-700 hover:bg-red-200' 
                          : 'bg-green-100 text-green-700 hover:bg-green-200'
                      }`}
                    >
                      {config.enabled ? 'Disable' : 'Enable'}
                    </button>
                  </div>
                  
                  {config.enabled && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input
                          type="text"
                          value={config.username}
                          onChange={(e) => setApiSettings({
                            ...apiSettings,
                            [api]: { ...config, username: e.target.value }
                          })}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                          placeholder="API Username"
                        />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Password/API Key</label>
                        <input
                          type="password"
                          value={config.password}
                          onChange={(e) => setApiSettings({
                            ...apiSettings,
                            [api]: { ...config, password: e.target.value }
                          })}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                          placeholder="API Password/Key"
                        />
                      </div>
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}

          {activeTab === 'pricing' && (
            <div className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Default Markup %</label>
                  <input
                    type="number"
                    value={settings.defaultMarkup}
                    onChange={(e) => setSettings({...settings, defaultMarkup: parseFloat(e.target.value)})}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Minimum Margin %</label>
                  <input
                    type="number"
                    value={settings.minimumMargin}
                    onChange={(e) => setSettings({...settings, minimumMargin: parseFloat(e.target.value)})}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Transfer Fee $</label>
                  <input
                    type="number"
                    value={settings.transferFee}
                    onChange={(e) => setSettings({...settings, transferFee: parseFloat(e.target.value)})}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Background Check Fee $</label>
                  <input
                    type="number"
                    value={settings.backgroundCheckFee}
                    onChange={(e) => setSettings({...settings, backgroundCheckFee: parseFloat(e.target.value)})}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Tax Rate %</label>
                  <input
                    type="number"
                    value={settings.taxRate}
                    onChange={(e) => setSettings({...settings, taxRate: parseFloat(e.target.value)})}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">CC Processing Fee %</label>
                  <input
                    type="number"
                    value={settings.ccProcessingFee}
                    onChange={(e) => setSettings({...settings, ccProcessingFee: parseFloat(e.target.value)})}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>
            </div>
          )}

          {activeTab === 'system' && (
            <div className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="p-4 border border-gray-200 rounded-lg">
                  <h4 className="font-medium text-gray-900 mb-2">Data Mode</h4>
                  <div className="flex items-center gap-4">
                    <label className="flex items-center gap-2">
                      <input
                        type="radio"
                        checked={mockMode}
                        onChange={() => setMockMode(true)}
                        className="w-4 h-4 text-blue-600"
                      />
                      <span>Mock Data</span>
                    </label>
                    <label className="flex items-center gap-2">
                      <input
                        type="radio"
                        checked={!mockMode}
                        onChange={() => setMockMode(false)}
                        className="w-4 h-4 text-blue-600"
                      />
                      <span>Live Data</span>
                    </label>
                  </div>
                </div>
                <div className="p-4 border border-gray-200 rounded-lg">
                  <h4 className="font-medium text-gray-900 mb-2">Debug Mode</h4>
                  <div className="flex items-center gap-2">
                    <input
                      type="checkbox"
                      checked={FFLBroConfig.debug}
                      onChange={(e) => FFLBroConfig.debug = e.target.checked}
                      className="w-4 h-4 text-blue-600"
                    />
                    <span>Enable Debug Logging</span>
                  </div>
                </div>
              </div>
              
              <div className="p-4 bg-gray-50 rounded-lg">
                <h4 className="font-medium text-gray-900 mb-2">System Information</h4>
                <div className="space-y-2 text-sm text-gray-600">
                  <div>Platform Version: {FFLBroConfig.version}</div>
                  <div>Data Mode: {mockMode ? 'Mock' : 'Live'}</div>
                  <div>Debug: {FFLBroConfig.debug ? 'Enabled' : 'Disabled'}</div>
                </div>
              </div>
            </div>
          )}

          <div className="pt-6 border-t border-gray-200">
            <button className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
              <Save className="w-4 h-4" />
              Save Settings
            </button>
          </div>
        </div>
      </div>
    );
  };

  // Inventory Module (placeholder)
  const InventoryModule = () => (
    <div className="space-y-6">
      <div className="bg-white p-6 rounded-xl border border-gray-200">
        <h2 className="text-xl font-semibold text-gray-900 mb-4">Inventory Management</h2>
        <p className="text-gray-600 mb-6">Comprehensive inventory tracking across all distributors and platforms.</p>
        
        <div className="text-center py-12 text-gray-500">
          <Package className="w-16 h-16 mx-auto mb-4 text-gray-400" />
          <p className="text-lg font-medium">Inventory Module</p>
          <p>Multi-distributor inventory synchronization</p>
          <p className="text-sm mt-2">Connect APIs to enable real-time inventory tracking</p>
        </div>
      </div>
    </div>
  );

  // Main render function
  const renderActiveModule = () => {
    switch (activeModule) {
      case 'dashboard': return <Dashboard />;
      case 'quotes': return <QuoteGenerator />;
      case 'gunbroker': return <GunBrokerModule />;
      case 'lipseys': return <LipseysModule />;
      case 'inventory': return <InventoryModule />;
      case 'form4473': return <Form4473Module />;
      case 'settings': return <SettingsModule />;
      default: return <Dashboard />;
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <Navigation />
      <div className="max-w-7xl mx-auto px-4 py-6">
        {renderActiveModule()}
      </div>
    </div>
  );
};

export default FFLBroPlatform;