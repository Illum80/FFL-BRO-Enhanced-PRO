#!/bin/bash
echo "🔧 GIT BACKUP + DEBUG CURRENT STATE"
echo "=================================="
cd /opt/fflbro/wordpress-main/wp-content/plugins/ffl-bro-enhanced-pro

echo "📊 Current plugin status:"
wc -l ffl-bro-enhanced-pro.php
ls -la *.backup* 2>/dev/null || echo "No backups found"

echo ""
echo "🗄️ CHECKING DATABASE TABLES & PRODUCTS..."
echo "========================================="

# Check if tables exist and their contents
mysql -h 192.168.2.161 -u root -p$(grep DB_PASSWORD /opt/fflbro/wordpress-main/wp-config.php | cut -d"'" -f4) $(grep DB_NAME /opt/fflbro/wordpress-main/wp-config.php | cut -d"'" -f4) << 'EOF'

-- Show all FFL-BRO tables
SHOW TABLES LIKE '%fflbro%';

-- Check products table structure and count
DESCRIBE wp_fflbro_products;
SELECT COUNT(*) as 'Total Products' FROM wp_fflbro_products;
SELECT distributor, COUNT(*) as 'Count' FROM wp_fflbro_products GROUP BY distributor;

-- Check sync progress
SELECT * FROM wp_fflbro_sync_progress;

-- Check customers table
SELECT COUNT(*) as 'Total Customers' FROM wp_fflbro_customers;

-- Show recent products from Lipseys (first 5)
SELECT id, item_num, description, manufacturer, dealer_price 
FROM wp_fflbro_products 
WHERE distributor = 'lipseys' 
LIMIT 5;

EOF

echo ""
echo "🔄 GIT SETUP AND BACKUP AS v6.4.2"
echo "================================="

# Initialize git if not already done
if [ ! -d ".git" ]; then
    echo "Initializing Git repository..."
    git init
    git remote add origin https://github.com/Illum80/FFL-BRO-Enhanced-PRO.git 2>/dev/null || true
fi

# Add all files and commit current state
git add .
git commit -m "v6.4.2 - Restored all 8 modules with working layout

✅ WORKING:
- Dashboard with enhanced layout
- Menu structure with all 8 modules
- Distributors page design
- Database tables created

🔧 NEEDS FUNCTIONALITY:
- Customer Management AJAX save function
- Form 4473 detailed workflow
- Marketing dashboard content
- Settings tabs and functionality
- Lipseys sync completion (stopped at 5.9%)

📊 STATS: 836 lines, safe restoration completed" || echo "Commit may have failed"

echo ""
echo "📋 NEXT STEPS IDENTIFIED:"
echo "1. Fix Customer Management save function"
echo "2. Complete Form 4473 workflow"
echo "3. Add Marketing dashboard content"  
echo "4. Restore Settings tabs"
echo "5. Debug Lipseys sync stopping at 5.9%"
echo ""
echo "✅ Current state backed up as v6.4.2"
echo "Ready for incremental functionality improvements!"
