#!/bin/bash

# =============================================================================
# LIPSEYS CURRENT STATE DIAGNOSTIC
# Checks exactly what's wrong before running fixes
# =============================================================================

echo "🔍 LIPSEYS CURRENT STATE DIAGNOSTIC"
echo "===================================="
echo ""

PLUGIN_DIR="/opt/fflbro/wordpress-main/wp-content/plugins/ffl-bro-enhanced-pro"
MAIN_PLUGIN="$PLUGIN_DIR/ffl_bro_enhanced_pro.php"
LIPSEYS_API="$PLUGIN_DIR/includes/class-lipseys-api.php"

echo "📅 Timestamp: $(date)"
echo "🖥️  Host: $(hostname)"
echo "📍 IP: $(hostname -I | awk '{print $1}')"
echo ""

echo "🐳 DOCKER STATUS"
echo "================"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep fflbro
echo ""

echo "📁 FILE EXISTENCE CHECK"
echo "======================="
echo "Plugin directory: $([ -d "$PLUGIN_DIR" ] && echo "✅ EXISTS" || echo "❌ MISSING")"
echo "Main plugin: $([ -f "$MAIN_PLUGIN" ] && echo "✅ EXISTS" || echo "❌ MISSING")"
echo "Lipseys API class: $([ -f "$LIPSEYS_API" ] && echo "✅ EXISTS" || echo "❌ MISSING")"
echo ""

if [ -f "$MAIN_PLUGIN" ]; then
    echo "📋 MAIN PLUGIN ANALYSIS"
    echo "======================="
    echo "File size: $(ls -lh "$MAIN_PLUGIN" | awk '{print $5}')"
    echo "Permissions: $(ls -l "$MAIN_PLUGIN" | awk '{print $1, $3, $4}')"
    echo "Last modified: $(stat -c %y "$MAIN_PLUGIN")"
    echo ""
    
    echo "🔍 FORM FIELD CHECK"
    echo "==================="
    echo "Dealer ID references: $(grep -c "Dealer ID" "$MAIN_PLUGIN" 2>/dev/null || echo "0")"
    echo "API Key references: $(grep -c "API Key" "$MAIN_PLUGIN" 2>/dev/null || echo "0")"
    echo "Email Address references: $(grep -c "Email Address" "$MAIN_PLUGIN" 2>/dev/null || echo "0")"
    echo "Password references: $(grep -c "Password" "$MAIN_PLUGIN" 2>/dev/null || echo "0")"
    echo "lipseys_email references: $(grep -c "lipseys_email" "$MAIN_PLUGIN" 2>/dev/null || echo "0")"
    echo "lipseys_password references: $(grep -c "lipseys_password" "$MAIN_PLUGIN" 2>/dev/null || echo "0")"
    echo ""
    
    echo "📝 CURRENT FORM CONTENT"
    echo "======================="
    echo "Form fields around line 1160:"
    grep -n -A 10 -B 2 "Dealer ID\|Email Address" "$MAIN_PLUGIN" 2>/dev/null || echo "No form fields found"
    echo ""
fi

if [ -f "$LIPSEYS_API" ]; then
    echo "🔗 API ENDPOINT CHECK"
    echo "===================="
    echo "Authentication endpoints found:"
    grep -n "Authentication" "$LIPSEYS_API" 2>/dev/null || echo "No authentication endpoints found"
    echo ""
fi

echo "🌐 WORDPRESS SITE STATUS"
echo "========================"
echo -n "Main site (8181): "
curl -s -o /dev/null -w "HTTP %{http_code}" http://localhost:8181/ && echo " ✅" || echo " ❌"
echo -n "Customer site (8182): "
curl -s -o /dev/null -w "HTTP %{http_code}" http://localhost:8182/ && echo " ✅" || echo " ❌"
echo ""

echo "📦 BACKUP FILES"
echo "==============="
ls -la /opt/fflbro/backups/ 2>/dev/null | tail -5 || echo "No backups found"
echo ""

echo "🔧 PERMISSION ISSUES"
echo "===================="
if [ -f "$MAIN_PLUGIN" ]; then
    OWNER=$(stat -c %U:%G "$MAIN_PLUGIN")
    PERMS=$(stat -c %a "$MAIN_PLUGIN")
    echo "Main plugin owner: $OWNER (should be www-data:www-data)"
    echo "Main plugin permissions: $PERMS (should be 644)"
    
    if [ "$OWNER" != "www-data:www-data" ]; then
        echo "❌ Wrong ownership - this explains why changes don't persist!"
    else
        echo "✅ Correct ownership"
    fi
fi

echo ""
echo "🎯 DIAGNOSIS SUMMARY"
echo "==================="
echo "Issues found:"

# Check for issues
ISSUES=0

if [ ! -f "$MAIN_PLUGIN" ]; then
    echo "❌ Main plugin file missing"
    ((ISSUES++))
fi

if grep -q "Dealer ID" "$MAIN_PLUGIN" 2>/dev/null; then
    echo "❌ Old 'Dealer ID' field still present"
    ((ISSUES++))
fi

if ! grep -q "lipseys_email" "$MAIN_PLUGIN" 2>/dev/null; then
    echo "❌ New 'lipseys_email' field missing"
    ((ISSUES++))
fi

if [ -f "$MAIN_PLUGIN" ]; then
    OWNER=$(stat -c %U:%G "$MAIN_PLUGIN")
    if [ "$OWNER" != "www-data:www-data" ]; then
        echo "❌ Wrong file ownership - changes won't persist"
        ((ISSUES++))
    fi
fi

if [ -f "$LIPSEYS_API" ] && grep -q "/Authentication" "$LIPSEYS_API" 2>/dev/null; then
    echo "❌ Wrong authentication endpoint (causing 404s)"
    ((ISSUES++))
fi

if [ $ISSUES -eq 0 ]; then
    echo "✅ No issues found - system should be working"
else
    echo "📊 Total issues found: $ISSUES"
    echo ""
    echo "🛠️ RECOMMENDED ACTION:"
    echo "Run the lipseys_final_fix script to address all issues at once"
fi

echo ""
echo "🔍 Diagnostic complete - $(date)"