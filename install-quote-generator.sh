#!/bin/bash

##################################################################
# Enhanced Multi-Distributor Quote Generator Installation
# Version: 2.0.0
# Uses MODULAR approach - NO SED operations!
##################################################################

echo "🎯 Enhanced Multi-Distributor Quote Generator Installation"
echo "=========================================================="
echo ""

# Navigate to plugin directory
cd /opt/fflbro/wordpress-main/wp-content/plugins/ffl-bro-enhanced-pro || {
    echo "❌ Plugin directory not found!"
    exit 1
}

echo "📂 Working in: $(pwd)"
echo ""

# Step 1: Create directory structure
echo "📁 Creating directory structure..."
mkdir -p includes/quote-generator
echo "✅ Directories created"
echo ""

# Step 2: Test syntax first
echo "🧪 Testing current PHP syntax..."
php -l ffl-bro-enhanced-pro.php

if [ $? -ne 0 ]; then
    echo "❌ Current plugin has syntax errors! Fix these first."
    exit 1
fi
echo "✅ Current syntax clean"
echo ""

# Step 3: Check if already included
echo "🔍 Checking for existing installation..."
if grep -q "enhanced-quote-generator.php" ffl-bro-enhanced-pro.php; then
    echo "⚠️  Quote generator already included - skipping include step"
else
    echo "🔗 Adding quote generator include to main plugin..."
    # Safely append the include statement
    echo "" >> ffl-bro-enhanced-pro.php
    echo "// Enhanced Quote Generator Module v7.2.0" >> ffl-bro-enhanced-pro.php
    echo "require_once plugin_dir_path(__FILE__) . 'includes/quote-generator/enhanced-quote-generator.php';" >> ffl-bro-enhanced-pro.php
    
    # Test syntax after addition
    php -l ffl-bro-enhanced-pro.php
    if [ $? -ne 0 ]; then
        echo "❌ Syntax error after adding include! Rolling back..."
        git checkout -- ffl-bro-enhanced-pro.php
        exit 1
    fi
    echo "✅ Quote generator included successfully"
fi
echo ""

# Step 4: Set proper permissions
echo "🔐 Setting permissions..."
sudo chown -R www-data:www-data .
chmod 644 ffl-bro-enhanced-pro.php
echo "✅ Permissions set"
echo ""

# Step 5: Create database table
echo "🗄️  Creating database table..."
if command -v wp &> /dev/null; then
    wp db query "CREATE TABLE IF NOT EXISTS wp_fflbro_quotes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        quote_number VARCHAR(50) NOT NULL UNIQUE,
        customer_name VARCHAR(255),
        customer_email VARCHAR(255),
        customer_phone VARCHAR(50),
        quote_data LONGTEXT,
        subtotal DECIMAL(10,2),
        tax DECIMAL(10,2),
        total DECIMAL(10,2),
        status VARCHAR(50) DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME,
        INDEX(quote_number),
        INDEX(customer_email),
        INDEX(status)
    )" --path=/opt/fflbro/wordpress-main 2>&1
    
    if [ $? -eq 0 ]; then
        echo "✅ Database table created"
    else
        echo "⚠️  Database table may already exist or wp-cli had an issue"
    fi
else
    echo "⚠️  wp-cli not found. Run this SQL manually:"
    echo "CREATE TABLE IF NOT EXISTS wp_fflbro_quotes (...)"
fi
echo ""

# Step 6: Git commit
echo "💾 Committing to git..."
git add .
git commit -m "v7.2.0: Enhanced Multi-Distributor Quote Generator - Base Installation

- Created includes/quote-generator/ directory structure
- Added quote generator include to main plugin
- Database table for quote storage
- Modular architecture (no SED operations)
- Ready for frontend files (JS/CSS)

Status: Backend ready, frontend files needed next"

git tag -a v7.2.0-base -m "Quote Generator backend installation"
echo "✅ Committed and tagged as v7.2.0-base"
echo ""

# Final instructions
echo "═══════════════════════════════════════════════════════"
echo "✅ Base Installation Complete!"
echo "═══════════════════════════════════════════════════════"
echo ""
echo "📋 NEXT STEPS:"
echo ""
echo "1. Create the PHP backend module:"
echo "   Save the 'Enhanced Quote Generator PHP Module' artifact as:"
echo "   includes/quote-generator/enhanced-quote-generator.php"
echo ""
echo "2. Create the JavaScript file:"
echo "   Save the 'Quote Generator React Component' artifact as:"
echo "   includes/quote-generator/quote-generator.js"
echo ""
echo "3. Create the CSS file:"
echo "   Save the 'Quote Generator Styles' artifact as:"
echo "   includes/quote-generator/quote-generator.css"
echo ""
echo "4. After saving all 3 files, test with:"
echo "   php -l ffl-bro-enhanced-pro.php"
echo "   php -l includes/quote-generator/enhanced-quote-generator.php"
echo ""
echo "5. Create WordPress page with shortcode:"
echo "   [fflbro_quote_generator]"
echo ""
echo "═══════════════════════════════════════════════════════"
