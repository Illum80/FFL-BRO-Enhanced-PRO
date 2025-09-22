# FFL-BRO Enhanced PRO

Professional WordPress plugin for FFL dealers with advanced quote generation and distributor integration.

## 🚀 Current Version: 5.1.4

**Release Date:** 2025-09-22  
**Author:** JRNeefe@gmail.com  
**Repository:** https://github.com/Illum80/ffl-bro-enhanced-pro

## 🌟 Features

### ✅ **Quote Generator Advanced**
- Multi-distributor pricing comparison (Lipseys, Sports South, Zanders)
- Professional PDF quote generation with business branding
- Advanced margin calculations and profit analysis
- Customer relationship management
- Quote status workflow (Draft → Sent → Approved → Converted)

### 🔗 **Distributor Integration**
- **Lipseys API** - Ready for JRNeefe@gmail.com credentials
- **Sports South** - Competitive pricing integration
- **Zanders** - Fast shipping and accessories

### 📊 **Analytics & Reporting**
- Quote performance dashboard
- Conversion rate tracking
- Profit margin analysis
- Customer analytics

### 🛠️ **Technical Features**
- WordPress admin integration
- Database table management
- AJAX-powered interface
- Responsive design with Tailwind CSS
- Shortcode support

## 📥 Installation

### Method 1: WordPress Admin Upload
1. Download the latest release ZIP file
2. Go to WordPress Admin > Plugins > Add New > Upload Plugin
3. Upload the ZIP file and activate the plugin
4. Configure settings under **FFL-BRO > Settings**

### Method 2: Manual Installation
```bash
# Clone the repository
git clone https://github.com/Illum80/ffl-bro-enhanced-pro.git

# Copy to WordPress plugins directory
cp -r ffl-bro-enhanced-pro /path/to/wordpress/wp-content/plugins/

# Set permissions
chmod -R 755 /path/to/wordpress/wp-content/plugins/ffl-bro-enhanced-pro
```

## ⚙️ Configuration

### API Settings
1. Navigate to **FFL-BRO > Settings** in WordPress admin
2. Configure distributor API credentials:
   - **Lipseys**: Use JRNeefe@gmail.com credentials
   - **Sports South**: Enter API key and secret
   - **Zanders**: Configure dealer portal access

### Business Settings
- Default markup percentage
- Tax rates and transfer fees
- Business information and FFL number
- Quote validity period

## 🎯 Usage

### Admin Dashboard
- WordPress Admin → **FFL-BRO Enhanced PRO**
- **Quote Generator Pro** - Create professional quotes
- **Settings** - Configure pricing and business info
- **Analytics** - Monitor performance and conversions

### Frontend Display
Add quote generator to any page or post:
```php
echo do_shortcode('[fflbro_quote_generator_advanced]');
```

## 🔧 Development

### Requirements
- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.6 or higher
- **Memory:** 256MB recommended

### File Structure
```
ffl-bro-enhanced-pro/
├── ffl-bro-enhanced-pro.php    # Main plugin file
├── includes/
│   ├── quote-generator-advanced.php
│   ├── distributors/
│   └── admin/
├── assets/
├── README.md
├── CHANGELOG.md
└── LICENSE
```

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed release notes.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'Add amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

## 📄 License

This project is licensed under the GPL v2 or later.

## 📞 Support

- **GitHub Issues:** [Report bugs and feature requests](https://github.com/Illum80/ffl-bro-enhanced-pro/issues)
- **Email:** JRNeefe@gmail.com
- **Documentation:** [Wiki](https://github.com/Illum80/ffl-bro-enhanced-pro/wiki)

## 🏆 Credits

**Developer:** JRNeefe@gmail.com  
**Repository:** https://github.com/Illum80/ffl-bro-enhanced-pro  
**Version:** 5.1.4

---

**Transform your FFL business with professional quote generation and distributor integration!**
