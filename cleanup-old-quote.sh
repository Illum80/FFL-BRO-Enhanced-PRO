#!/bin/bash
# Clean up OLD quote generator references and add NEW one

PLUGIN_FILE="/opt/fflbro/wordpress-main/wp-content/plugins/ffl-bro-enhanced-pro/ffl-bro-enhanced-pro.php"

echo "🧹 Cleaning up old quote generator references..."

# Backup first
sudo cp "$PLUGIN_FILE" "$PLUGIN_FILE.backup.$(date +%Y%m%d_%H%M%S)"
echo "✓ Backup created"

# Remove old includes
sudo sed -i '/visual-quote-backend\.php/d' "$PLUGIN_FILE"
sudo sed -i '/enhanced-quote-generator\.php/d' "$PLUGIN_FILE"
sudo sed -i '/file_exists.*visual-quote-backend/d' "$PLUGIN_FILE"
echo "✓ Removed old includes"

# Find the Visual Quote Generator comment and add new include after it
if ! grep -q "includes/admin-visual-quote.php" "$PLUGIN_FILE"; then
    sudo sed -i '/\/\/ Visual Quote Generator/a require_once plugin_dir_path(__FILE__) . '\''includes/admin-visual-quote.php'\'';' "$PLUGIN_FILE"
    echo "✓ Added new include"
else
    echo "✓ New include already present"
fi

echo ""
echo "📄 Current Visual Quote Generator section:"
grep -A3 "// Visual Quote Generator" "$PLUGIN_FILE"

echo ""
echo "✅ Cleanup complete! Refresh WordPress now."
