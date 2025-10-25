import React, { useState, useEffect, useMemo } from 'react';
import { 
  BarChart3, 
  Package, 
  Users, 
  ArrowRightLeft, 
  FileText, 
  Calendar, 
  Activity, 
  Settings, 
  UserCheck, 
  FileSearch,
  Menu,
  X,
  Download,
  Upload,
  Play,
  RefreshCw,
  Plus,
  Edit,
  Trash2,
  Eye,
  Search,
  Bell,
  Home,
  TrendingUp,
  Shield,
  Zap,
  AlertTriangle,
  CheckCircle2,
  Clock,
  User,
  Building2,
  Phone,
  Mail,
  MapPin,
  CreditCard,
  Save,
  EyeOff,
  Filter,
  ShoppingCart,
  Star,
  DollarSign,
  Truck,
  Grid,
  List,
  Send,
  Share2,
  Copy,
  Check,
  ExternalLink,
  Heart,
  Calculator,
  Percent,
  Minus
} from 'lucide-react';

// Mock API with enhanced data
const mockAPI = {
  getDashboardKPIs: () => Promise.resolve({
    orders_today: 47,
    orders_month: 1_284,
    revenue_today: 18_450.75,
    revenue_month: 324_680.90,
    active_leads: 156,
    conversion_rate: 23.4,
    pending_transfers: 23,
    catalog_items: 45_820,
    system_health: 98.7,
    alerts_count: 3,
    quotes_pending: 12,
    forms_submitted_today: 8
  }),
  
  getRecentActivity: () => Promise.resolve([
    { id: 1, type: "order", message: "New order #1247 from Sarah Mitchell", time: "2m ago", status: "success" },
    { id: 2, type: "sync", message: "Catalog sync completed successfully", time: "15m ago", status: "success" },
    { id: 3, type: "alert", message: "Low inventory warning: AR-15 Lower Receivers", time: "1h ago", status: "warning" },
    { id: 4, type: "lead", message: "New lead from website contact form", time: "2h ago", status: "info" },
    { id: 5, type: "quote", message: "Quote #QT-2025-001 sent to David Thompson", time: "3h ago", status: "success" },
    { id: 6, type: "form", message: "4473 form completed for Jennifer Chen", time: "4h ago", status: "success" }
  ])
};

// Enhanced KPI Card Component
const KpiCard = ({ title, value, subtitle, trend, icon: Icon, color = "blue", loading = false, onClick }) => {
  if (loading) {
    return (
      <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 animate-pulse">
        <div className="flex items-start justify-between">
          <div className="flex-1">
            <div className="h-4 bg-gray-200 rounded mb-3 w-3/4"></div>
            <div className="h-8 bg-gray-200 rounded mb-2"></div>
            <div className="h-3 bg-gray-200 rounded w-1/2"></div>
          </div>
          <div className="w-12 h-12 bg-gray-200 rounded-lg"></div>
        </div>
      </div>
    );
  }

  const colorClasses = {
    blue: "bg-blue-50 text-blue-600 border-blue-100",
    orange: "bg-orange-50 text-orange-600 border-orange-100",
    green: "bg-green-50 text-green-600 border-green-100",
    purple: "bg-purple-50 text-purple-600 border-purple-100",
    red: "bg-red-50 text-red-600 border-red-100",
    indigo: "bg-indigo-50 text-indigo-600 border-indigo-100"
  };

  return (
    <div 
      className={`bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow ${onClick ? 'cursor-pointer' : ''}`}
      onClick={onClick}
    >
      <div className="flex items-start justify-between">
        <div className="flex-1">
          <div className="flex items-center gap-2 mb-3">
            <h3 className="text-sm font-medium text-gray-600">{title}</h3>
            {trend && (
              <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                trend > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
              }`}>
                <TrendingUp className={`w-3 h-3 mr-1 ${trend < 0 ? 'rotate-180' : ''}`} />
                {Math.abs(trend)}%
              </span>
            )}
          </div>
          <p className="text-2xl font-bold text-gray-900 mb-1">{value}</p>
          {subtitle && <p className="text-sm text-gray-500">{subtitle}</p>}
        </div>
        {Icon && (
          <div className={`w-12 h-12 rounded-lg border flex items-center justify-center ${colorClasses[color]}`}>
            <Icon className="w-6 h-6" />
          </div>
        )}
      </div>
    </div>
  );
};

// Form Components
const InputField = ({ label, type = "text", required = false, error, icon: Icon, ...props }) => (
  <div className="space-y-2">
    <label className="block text-sm font-medium text-gray-700">
      {label} {required && <span className="text-red-500">*</span>}
    </label>
    <div className="relative">
      {Icon && (
        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <Icon className="h-5 w-5 text-gray-400" />
        </div>
      )}
      <input
        type={type}
        className={`w-full ${Icon ? 'pl-10' : 'pl-4'} pr-4 py-3 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 ${
          error ? 'border-red-300' : 'border-gray-300'
        }`}
        {...props}
      />
    </div>
    {error && <p className="text-sm text-red-600">{error}</p>}
  </div>
);

const SelectField = ({ label, options, required = false, error, ...props }) => (
  <div className="space-y-2">
    <label className="block text-sm font-medium text-gray-700">
      {label} {required && <span className="text-red-500">*</span>}
    </label>
    <select
      className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 ${
        error ? 'border-red-300' : 'border-gray-300'
      }`}
      {...props}
    >
      <option value="">Select {label.toLowerCase()}...</option>
      {options.map((option) => (
        <option key={option.value} value={option.value}>
          {option.label}
        </option>
      ))}
    </select>
    {error && <p className="text-sm text-red-600">{error}</p>}
  </div>
);

const TextareaField = ({ label, required = false, error, ...props }) => (
  <div className="space-y-2">
    <label className="block text-sm font-medium text-gray-700">
      {label} {required && <span className="text-red-500">*</span>}
    </label>
    <textarea
      className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 ${
        error ? 'border-red-300' : 'border-gray-300'
      }`}
      rows={4}
      {...props}
    />
    {error && <p className="text-sm text-red-600">{error}</p>}
  </div>
);

const CheckboxField = ({ label, description, ...props }) => (
  <div className="flex items-start gap-3">
    <input
      type="checkbox"
      className="mt-1 h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded"
      {...props}
    />
    <div className="flex-1">
      <label className="text-sm font-medium text-gray-700">{label}</label>
      {description && <p className="text-xs text-gray-500 mt-1">{description}</p>}
    </div>
  </div>
);

// Navigation Component
const Navigation = ({ activeTab, onTabChange, isMobileMenuOpen, setIsMobileMenuOpen }) => {
  const navItems = [
    { id: 'dashboard', label: 'Dashboard', icon: Home },
    { id: 'catalog', label: 'Catalog & Feeds', icon: Package },
    { id: 'orders', label: 'Orders', icon: BarChart3 },
    { id: 'leads', label: 'Leads', icon: Users },
    { id: 'transfers', label: 'FFL Transfers', icon: ArrowRightLeft },
    { id: 'forms', label: 'Business Forms', icon: FileText },
    { id: 'quote-generator', label: 'Quote Generator', icon: Calculator },
    { id: 'form-4473', label: 'Form 4473', icon: Shield },
    { id: 'credentials', label: 'Credentials', icon: CreditCard },
    { id: 'jobs', label: 'Jobs & Schedules', icon: Calendar },
    { id: 'monitoring', label: 'Monitoring', icon: Activity },
    { id: 'settings', label: 'Settings', icon: Settings },
    { id: 'users', label: 'Users & Roles', icon: UserCheck },
    { id: 'audit', label: 'Audit & Logs', icon: FileSearch }
  ];

  return (
    <>
      {/* Mobile menu button */}
      <div className="lg:hidden fixed top-6 left-6 z-50">
        <button
          onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
          className="bg-white p-3 rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-shadow"
        >
          {isMobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
        </button>
      </div>

      {/* Sidebar */}
      <div className={`${isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'} lg:translate-x-0 transition-transform duration-300 ease-in-out fixed lg:static inset-y-0 left-0 z-40 w-72 bg-gradient-to-b from-slate-900 to-slate-800 text-white flex flex-col shadow-2xl`}>
        
        {/* Logo */}
        <div className="p-8 border-b border-slate-700/50">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg flex items-center justify-center">
              <Shield className="w-6 h-6 text-white" />
            </div>
            <div>
              <h1 className="text-xl font-bold text-white">
                Neefeco Arms
              </h1>
              <p className="text-sm text-slate-300">Head Quarters</p>
            </div>
          </div>
        </div>

        {/* Navigation */}
        <nav className="flex-1 py-6 px-4 space-y-1">
          {navItems.map((item) => (
            <button
              key={item.id}
              onClick={() => {
                onTabChange(item.id);
                setIsMobileMenuOpen(false);
              }}
              className={`w-full flex items-center px-4 py-3 text-left rounded-xl transition-all duration-200 group ${
                activeTab === item.id 
                  ? 'bg-orange-600 text-white shadow-lg' 
                  : 'hover:bg-slate-700/50 text-slate-300 hover:text-white'
              }`}
            >
              <item.icon className={`w-5 h-5 mr-3 flex-shrink-0 transition-transform ${
                activeTab === item.id ? 'scale-110' : 'group-hover:scale-105'
              }`} />
              <span className="text-sm font-medium">{item.label}</span>
            </button>
          ))}
        </nav>

        {/* Footer */}
        <div className="p-6 border-t border-slate-700/50">
          <div className="flex items-center gap-3">
            <div className="w-8 h-8 bg-slate-700 rounded-lg flex items-center justify-center">
              <CheckCircle2 className="w-4 h-4 text-green-400" />
            </div>
            <div className="text-xs">
              <p className="text-slate-300">System Status</p>
              <p className="text-green-400 font-medium">All Systems Operational</p>
            </div>
          </div>
        </div>
      </div>

      {/* Mobile overlay */}
      {isMobileMenuOpen && (
        <div 
          className="lg:hidden fixed inset-0 z-30 bg-black bg-opacity-50"
          onClick={() => setIsMobileMenuOpen(false)}
        />
      )}
    </>
  );
};

// Main HQ Component
const FFLBROHQ = () => {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [dashboardData, setDashboardData] = useState(null);
  const [recentActivity, setRecentActivity] = useState([]);
  
  // Quote Generator State
  const [quoteItems, setQuoteItems] = useState([]);
  const [selectedCustomer, setSelectedCustomer] = useState('');
  const [searchTerm, setSearchTerm] = useState('');
  
  // Form 4473 State
  const [form4473Data, setForm4473Data] = useState({
    lastName: '',
    firstName: '',
    middleName: '',
    dateOfBirth: '',
    height: '',
    weight: '',
    streetAddress: '',
    city: '',
    state: '',
    zipCode: '',
    phone: '',
    email: '',
    idType: '',
    idNumber: '',
    isUnlawfulUser: false,
    isFugitive: false,
    isUnderIndictment: false,
    hasConvictions: false
  });

  // Business Forms State
  const [businessForms, setBusinessForms] = useState({
    contactForm: {
      name: '',
      email: '',
      phone: '',
      subject: '',
      message: '',
      preferredContact: 'email',
      urgency: 'normal'
    },
    
    transferRequest: {
      customerName: '',
      customerEmail: '',
      customerPhone: '',
      sellerName: '',
      sellerFFL: '',
      itemDescription: '',
      serialNumber: '',
      manufacturer: '',
      model: '',
      caliber: '',
      type: '',
      specialInstructions: ''
    },
    
    trainingRegistration: {
      studentName: '',
      studentEmail: '',
      studentPhone: '',
      course: '',
      preferredDate: '',
      experience: '',
      specialNeeds: '',
      emergencyContact: '',
      emergencyPhone: ''
    }
  });

  // Load data on component mount
  useEffect(() => {
    const loadData = async () => {
      setIsLoading(true);
      try {
        const [kpiData, activityData] = await Promise.all([
          mockAPI.getDashboardKPIs(),
          mockAPI.getRecentActivity()
        ]);
        
        setDashboardData(kpiData);
        setRecentActivity(activityData);
      } catch (error) {
        console.error('Error loading data:', error);
      } finally {
        setIsLoading(false);
      }
    };

    loadData();
  }, []);

  // Render Dashboard
  const renderDashboard = () => (
    <div className="space-y-8">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Dashboard</h1>
          <p className="text-gray-600 mt-1">Welcome back to Neefeco Arms HQ</p>
        </div>
        <div className="flex items-center gap-3">
          <button className="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <Download className="w-4 h-4" />
            Export Data
          </button>
          <button className="flex items-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
            <RefreshCw className="w-4 h-4" />
            Refresh
          </button>
        </div>
      </div>

      {/* KPI Cards Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <KpiCard
          title="Orders Today"
          value={dashboardData?.orders_today || '0'}
          subtitle={`${dashboardData?.orders_month?.toLocaleString() || '0'} this month`}
          trend={12.5}
          icon={BarChart3}
          color="blue"
          loading={isLoading}
          onClick={() => setActiveTab('orders')}
        />
        <KpiCard
          title="Revenue Today"
          value={`$${dashboardData?.revenue_today?.toLocaleString() || '0'}`}
          subtitle={`$${dashboardData?.revenue_month?.toLocaleString() || '0'} this month`}
          trend={8.2}
          icon={DollarSign}
          color="green"
          loading={isLoading}
        />
        <KpiCard
          title="Active Leads"
          value={dashboardData?.active_leads || '0'}
          subtitle={`${dashboardData?.conversion_rate || '0'}% conversion rate`}
          trend={-2.1}
          icon={Users}
          color="purple"
          loading={isLoading}
          onClick={() => setActiveTab('leads')}
        />
        <KpiCard
          title="Pending Transfers"
          value={dashboardData?.pending_transfers || '0'}
          subtitle="FFL transfers in progress"
          icon={ArrowRightLeft}
          color="orange"
          loading={isLoading}
          onClick={() => setActiveTab('transfers')}
        />
      </div>

      {/* Secondary KPIs */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <KpiCard
          title="Catalog Items"
          value={dashboardData?.catalog_items?.toLocaleString() || '0'}
          subtitle="Products available"
          icon={Package}
          color="indigo"
          loading={isLoading}
          onClick={() => setActiveTab('catalog')}
        />
        <KpiCard
          title="System Health"
          value={`${dashboardData?.system_health || '0'}%`}
          subtitle="All systems operational"
          icon={CheckCircle2}
          color="green"
          loading={isLoading}
          onClick={() => setActiveTab('monitoring')}
        />
        <KpiCard
          title="Pending Quotes"
          value={dashboardData?.quotes_pending || '0'}
          subtitle="Awaiting customer response"
          icon={Calculator}
          color="blue"
          loading={isLoading}
          onClick={() => setActiveTab('quote-generator')}
        />
        <KpiCard
          title="Forms Today"
          value={dashboardData?.forms_submitted_today || '0'}
          subtitle="4473 and business forms"
          icon={FileText}
          color="purple"
          loading={isLoading}
          onClick={() => setActiveTab('forms')}
        />
      </div>

      {/* Recent Activity */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100">
        <div className="p-6 border-b border-gray-100">
          <h3 className="text-lg font-semibold text-gray-900">Recent Activity</h3>
        </div>
        <div className="p-6">
          <div className="space-y-4">
            {recentActivity.map((activity) => (
              <div key={activity.id} className="flex items-center gap-4">
                <div className={`w-2 h-2 rounded-full ${
                  activity.status === 'success' ? 'bg-green-500' :
                  activity.status === 'warning' ? 'bg-yellow-500' :
                  activity.status === 'error' ? 'bg-red-500' :
                  'bg-blue-500'
                }`} />
                <div className="flex-1">
                  <p className="text-sm text-gray-900">{activity.message}</p>
                  <p className="text-xs text-gray-500">{activity.time}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );

  // Render Quote Generator
  const renderQuoteGenerator = () => (
    <div className="space-y-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Quote Generator</h1>
          <p className="text-gray-600 mt-1">Create professional quotes for customers</p>
        </div>
        <button className="flex items-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
          <Plus className="w-4 h-4" />
          New Quote
        </button>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Quote Builder</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <InputField
            label="Customer Name"
            value={selectedCustomer}
            onChange={(e) => setSelectedCustomer(e.target.value)}
            placeholder="Enter customer name..."
            icon={User}
          />
          <InputField
            label="Search Products"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            placeholder="Search by name, SKU, or manufacturer..."
            icon={Search}
          />
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <p className="text-gray-600">Quote generator content will be added here...</p>
      </div>
    </div>
  );

  // Render Form 4473
  const renderForm4473 = () => (
    <div className="space-y-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Form 4473</h1>
          <p className="text-gray-600 mt-1">Firearms Transaction Record</p>
        </div>
        <div className="flex items-center gap-3">
          <button className="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <Download className="w-4 h-4" />
            Download PDF
          </button>
          <button className="flex items-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
            <Save className="w-4 h-4" />
            Save Form
          </button>
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-6">Personal Information</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <InputField
            label="Last Name"
            value={form4473Data.lastName}
            onChange={(e) => setForm4473Data({...form4473Data, lastName: e.target.value})}
            required
          />
          <InputField
            label="First Name"
            value={form4473Data.firstName}
            onChange={(e) => setForm4473Data({...form4473Data, firstName: e.target.value})}
            required
          />
          <InputField
            label="Middle Name"
            value={form4473Data.middleName}
            onChange={(e) => setForm4473Data({...form4473Data, middleName: e.target.value})}
          />
          <InputField
            label="Date of Birth"
            type="date"
            value={form4473Data.dateOfBirth}
            onChange={(e) => setForm4473Data({...form4473Data, dateOfBirth: e.target.value})}
            required
          />
          <InputField
            label="Height"
            value={form4473Data.height}
            onChange={(e) => setForm4473Data({...form4473Data, height: e.target.value})}
            placeholder="5'10"
          />
          <InputField
            label="Weight"
            value={form4473Data.weight}
            onChange={(e) => setForm4473Data({...form4473Data, weight: e.target.value})}
            placeholder="180 lbs"
          />
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-6">Address Information</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div className="md:col-span-2">
            <InputField
              label="Street Address"
              value={form4473Data.streetAddress}
              onChange={(e) => setForm4473Data({...form4473Data, streetAddress: e.target.value})}
              required
              icon={MapPin}
            />
          </div>
          <InputField
            label="City"
            value={form4473Data.city}
            onChange={(e) => setForm4473Data({...form4473Data, city: e.target.value})}
            required
          />
          <InputField
            label="State"
            value={form4473Data.state}
            onChange={(e) => setForm4473Data({...form4473Data, state: e.target.value})}
            required
          />
          <InputField
            label="ZIP Code"
            value={form4473Data.zipCode}
            onChange={(e) => setForm4473Data({...form4473Data, zipCode: e.target.value})}
            required
          />
          <InputField
            label="Phone Number"
            type="tel"
            value={form4473Data.phone}
            onChange={(e) => setForm4473Data({...form4473Data, phone: e.target.value})}
            icon={Phone}
          />
          <InputField
            label="Email Address"
            type="email"
            value={form4473Data.email}
            onChange={(e) => setForm4473Data({...form4473Data, email: e.target.value})}
            icon={Mail}
          />
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-6">Background Check Questions</h3>
        <div className="space-y-4">
          <CheckboxField
            label="Are you the actual transferee/buyer of the firearm(s) listed on this form?"
            description="Warning: You are not the actual transferee/buyer if you are acquiring the firearm(s) on behalf of another person."
            checked={form4473Data.isUnlawfulUser}
            onChange={(e) => setForm4473Data({...form4473Data, isUnlawfulUser: e.target.checked})}
          />
          <CheckboxField
            label="Are you under indictment or information in any court for a felony?"
            checked={form4473Data.isUnderIndictment}
            onChange={(e) => setForm4473Data({...form4473Data, isUnderIndictment: e.target.checked})}
          />
          <CheckboxField
            label="Have you ever been convicted in any court of a felony?"
            checked={form4473Data.hasConvictions}
            onChange={(e) => setForm4473Data({...form4473Data, hasConvictions: e.target.checked})}
          />
          <CheckboxField
            label="Are you a fugitive from justice?"
            checked={form4473Data.isFugitive}
            onChange={(e) => setForm4473Data({...form4473Data, isFugitive: e.target.checked})}
          />
        </div>
      </div>
    </div>
  );

  // Render Business Forms
  const renderBusinessForms = () => (
    <div className="space-y-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Business Forms</h1>
          <p className="text-gray-600 mt-1">Contact, FFL Transfer, and Training forms</p>
        </div>
      </div>

      {/* Contact Form */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-6">Contact Form</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <InputField
            label="Full Name"
            value={businessForms.contactForm.name}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              contactForm: { ...businessForms.contactForm, name: e.target.value }
            })}
            required
            icon={User}
          />
          <InputField
            label="Email Address"
            type="email"
            value={businessForms.contactForm.email}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              contactForm: { ...businessForms.contactForm, email: e.target.value }
            })}
            required
            icon={Mail}
          />
          <InputField
            label="Phone Number"
            type="tel"
            value={businessForms.contactForm.phone}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              contactForm: { ...businessForms.contactForm, phone: e.target.value }
            })}
            icon={Phone}
          />
          <SelectField
            label="Subject"
            value={businessForms.contactForm.subject}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              contactForm: { ...businessForms.contactForm, subject: e.target.value }
            })}
            options={[
              { value: 'general', label: 'General Inquiry' },
              { value: 'ffl', label: 'FFL Transfer' },
              { value: 'training', label: 'Training Classes' },
              { value: 'products', label: 'Product Information' },
              { value: 'support', label: 'Technical Support' }
            ]}
            required
          />
          <div className="md:col-span-2">
            <TextareaField
              label="Message"
              value={businessForms.contactForm.message}
              onChange={(e) => setBusinessForms({
                ...businessForms,
                contactForm: { ...businessForms.contactForm, message: e.target.value }
              })}
              required
              placeholder="Please describe your inquiry in detail..."
            />
          </div>
        </div>
        <div className="mt-6 flex justify-end">
          <button className="flex items-center gap-2 px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
            <Send className="w-4 h-4" />
            Send Message
          </button>
        </div>
      </div>

      {/* FFL Transfer Request */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-6">FFL Transfer Request</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <InputField
            label="Customer Name"
            value={businessForms.transferRequest.customerName}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              transferRequest: { ...businessForms.transferRequest, customerName: e.target.value }
            })}
            required
            icon={User}
          />
          <InputField
            label="Customer Email"
            type="email"
            value={businessForms.transferRequest.customerEmail}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              transferRequest: { ...businessForms.transferRequest, customerEmail: e.target.value }
            })}
            required
            icon={Mail}
          />
          <InputField
            label="Customer Phone"
            type="tel"
            value={businessForms.transferRequest.customerPhone}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              transferRequest: { ...businessForms.transferRequest, customerPhone: e.target.value }
            })}
            icon={Phone}
          />
          <InputField
            label="Seller/Dealer Name"
            value={businessForms.transferRequest.sellerName}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              transferRequest: { ...businessForms.transferRequest, sellerName: e.target.value }
            })}
            required
          />
          <InputField
            label="Seller FFL Number"
            value={businessForms.transferRequest.sellerFFL}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              transferRequest: { ...businessForms.transferRequest, sellerFFL: e.target.value }
            })}
            placeholder="XX-X-XX-XX-XXX-XXXXX"
          />
          <InputField
            label="Manufacturer"
            value={businessForms.transferRequest.manufacturer}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              transferRequest: { ...businessForms.transferRequest, manufacturer: e.target.value }
            })}
            required
          />
          <InputField
            label="Model"
            value={businessForms.transferRequest.model}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              transferRequest: { ...businessForms.transferRequest, model: e.target.value }
            })}
            required
          />
          <InputField
            label="Caliber"
            value={businessForms.transferRequest.caliber}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              transferRequest: { ...businessForms.transferRequest, caliber: e.target.value }
            })}
          />
          <InputField
            label="Serial Number"
            value={businessForms.transferRequest.serialNumber}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              transferRequest: { ...businessForms.transferRequest, serialNumber: e.target.value }
            })}
            required
          />
          <SelectField
            label="Firearm Type"
            value={businessForms.transferRequest.type}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              transferRequest: { ...businessForms.transferRequest, type: e.target.value }
            })}
            options={[
              { value: 'handgun', label: 'Handgun' },
              { value: 'rifle', label: 'Rifle' },
              { value: 'shotgun', label: 'Shotgun' },
              { value: 'other', label: 'Other' }
            ]}
            required
          />
          <div className="md:col-span-2">
            <TextareaField
              label="Special Instructions"
              value={businessForms.transferRequest.specialInstructions}
              onChange={(e) => setBusinessForms({
                ...businessForms,
                transferRequest: { ...businessForms.transferRequest, specialInstructions: e.target.value }
              })}
              placeholder="Any special handling instructions or additional information..."
            />
          </div>
        </div>
        <div className="mt-6 flex justify-end">
          <button className="flex items-center gap-2 px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
            <ArrowRightLeft className="w-4 h-4" />
            Submit Transfer Request
          </button>
        </div>
      </div>

      {/* Training Registration */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-6">Training Registration</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <InputField
            label="Student Name"
            value={businessForms.trainingRegistration.studentName}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              trainingRegistration: { ...businessForms.trainingRegistration, studentName: e.target.value }
            })}
            required
            icon={User}
          />
          <InputField
            label="Email Address"
            type="email"
            value={businessForms.trainingRegistration.studentEmail}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              trainingRegistration: { ...businessForms.trainingRegistration, studentEmail: e.target.value }
            })}
            required
            icon={Mail}
          />
          <InputField
            label="Phone Number"
            type="tel"
            value={businessForms.trainingRegistration.studentPhone}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              trainingRegistration: { ...businessForms.trainingRegistration, studentPhone: e.target.value }
            })}
            required
            icon={Phone}
          />
          <SelectField
            label="Course"
            value={businessForms.trainingRegistration.course}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              trainingRegistration: { ...businessForms.trainingRegistration, course: e.target.value }
            })}
            options={[
              { value: 'ccw', label: 'Florida Concealed Carry & Home Defense' },
              { value: 'basic', label: 'Basic Pistol Safety' },
              { value: 'advanced', label: 'Advanced Pistol Techniques' },
              { value: 'private', label: 'Private Training Session' },
              { value: 'dryfire', label: 'Dry Fire Training with DryFireMag' }
            ]}
            required
          />
          <InputField
            label="Preferred Date"
            type="date"
            value={businessForms.trainingRegistration.preferredDate}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              trainingRegistration: { ...businessForms.trainingRegistration, preferredDate: e.target.value }
            })}
          />
          <SelectField
            label="Experience Level"
            value={businessForms.trainingRegistration.experience}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              trainingRegistration: { ...businessForms.trainingRegistration, experience: e.target.value }
            })}
            options={[
              { value: 'beginner', label: 'Beginner (No Experience)' },
              { value: 'novice', label: 'Novice (Some Experience)' },
              { value: 'intermediate', label: 'Intermediate' },
              { value: 'advanced', label: 'Advanced' }
            ]}
          />
          <InputField
            label="Emergency Contact"
            value={businessForms.trainingRegistration.emergencyContact}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              trainingRegistration: { ...businessForms.trainingRegistration, emergencyContact: e.target.value }
            })}
            required
          />
          <InputField
            label="Emergency Phone"
            type="tel"
            value={businessForms.trainingRegistration.emergencyPhone}
            onChange={(e) => setBusinessForms({
              ...businessForms,
              trainingRegistration: { ...businessForms.trainingRegistration, emergencyPhone: e.target.value }
            })}
            required
          />
          <div className="md:col-span-2">
            <TextareaField
              label="Special Needs or Accommodations"
              value={businessForms.trainingRegistration.specialNeeds}
              onChange={(e) => setBusinessForms({
                ...businessForms,
                trainingRegistration: { ...businessForms.trainingRegistration, specialNeeds: e.target.value }
              })}
              placeholder="Please describe any special needs, accommodations, or medical conditions we should be aware of..."
            />
          </div>
        </div>
        <div className="mt-6 flex justify-end">
          <button className="flex items-center gap-2 px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
            <User className="w-4 h-4" />
            Register for Training
          </button>
        </div>
      </div>
    </div>
  );

  // Main render function
  const renderContent = () => {
    switch (activeTab) {
      case 'dashboard':
        return renderDashboard();
      case 'quote-generator':
        return renderQuoteGenerator();
      case 'form-4473':
        return renderForm4473();
      case 'forms':
        return renderBusinessForms();
      default:
        return (
          <div className="flex items-center justify-center h-96">
            <div className="text-center">
              <Package className="w-16 h-16 text-gray-400 mx-auto mb-4" />
              <h3 className="text-lg font-medium text-gray-900 mb-2">
                {activeTab.charAt(0).toUpperCase() + activeTab.slice(1).replace('-', ' ')}
              </h3>
              <p className="text-gray-600">This section is under development.</p>
            </div>
          </div>
        );
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 flex">
      <Navigation 
        activeTab={activeTab} 
        onTabChange={setActiveTab}
        isMobileMenuOpen={isMobileMenuOpen}
        setIsMobileMenuOpen={setIsMobileMenuOpen}
      />
      
      <main className="flex-1 lg:ml-0 p-8 overflow-auto">
        {renderContent()}
      </main>
    </div>
  );
};

export default FFLBROHQ;