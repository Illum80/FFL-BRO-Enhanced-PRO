#!/bin/bash

# =============================================================================
# FFL-BRO System Diagnostic Script
# Collects comprehensive environment information for troubleshooting
# =============================================================================

DIAGNOSTIC_FILE="fflbro_system_diagnostic_$(date +%Y%m%d_%H%M%S).txt"

echo "🔍 FFL-BRO System Diagnostic Report" | tee $DIAGNOSTIC_FILE
echo "Generated: $(date)" | tee -a $DIAGNOSTIC_FILE
echo "=========================================" | tee -a $DIAGNOSTIC_FILE
echo "" | tee -a $DIAGNOSTIC_FILE

# =============================================================================
# SYSTEM INFORMATION
# =============================================================================
echo "📋 SYSTEM INFORMATION" | tee -a $DIAGNOSTIC_FILE
echo "---------------------" | tee -a $DIAGNOSTIC_FILE
echo "Hostname: $(hostname)" | tee -a $DIAGNOSTIC_FILE
echo "OS: $(lsb_release -d 2>/dev/null | cut -f2 || cat /etc/os-release | grep PRETTY_NAME | cut -d'"' -f2)" | tee -a $DIAGNOSTIC_FILE
echo "Kernel: $(uname -r)" | tee -a $DIAGNOSTIC_FILE
echo "Architecture: $(uname -m)" | tee -a $DIAGNOSTIC_FILE
echo "Uptime: $(uptime)" | tee -a $DIAGNOSTIC_FILE
echo "Memory: $(free -h | grep Mem)" | tee -a $DIAGNOSTIC_FILE
echo "Disk Space:" | tee -a $DIAGNOSTIC_FILE
df -h | tee -a $DIAGNOSTIC_FILE
echo "CPU Info:" | tee -a $DIAGNOSTIC_FILE
lscpu | grep -E "(Model name|CPU\(s\)|Architecture)" | tee -a $DIAGNOSTIC_FILE
echo "" | tee -a $DIAGNOSTIC_FILE

# =============================================================================
# DOCKER INFORMATION
# =============================================================================
echo "🐳 DOCKER INFORMATION" | tee -a $DIAGNOSTIC_FILE
echo "----------------------" | tee -a $DIAGNOSTIC_FILE
echo "Docker Version:" | tee -a $DIAGNOSTIC_FILE
docker --version 2>/dev/null || echo "Docker not installed/running" | tee -a $DIAGNOSTIC_FILE
echo "" | tee -a $DIAGNOSTIC_FILE

echo "Docker Compose Version:" | tee -a $DIAGNOSTIC_FILE
docker-compose --version 2>/dev/null || docker compose version 2>/dev/null || echo "Docker Compose not found" | tee -a $DIAGNOSTIC_FILE
echo "" | tee -a $DIAGNOSTIC_FILE

echo "Running Containers:" | tee -a $DIAGNOSTIC_FILE
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" 2>/dev/null || echo "Cannot access Docker" | tee -a $DIAGNOSTIC_FILE
echo "" | tee -a $DIAGNOSTIC_FILE

echo "All Containers (including stopped):" | tee -a $DIAGNOSTIC_FILE
docker ps -a --format "table {{.Names}}\t{{.Status}}\t{{.Image}}" 2>/dev/null || echo "Cannot access Docker" | tee -a $DIAGNOSTIC_FILE
echo "" | tee -a $DIAGNOSTIC_FILE

echo "Docker Networks:" | tee -a $DIAGNOSTIC_FILE
docker network ls 2>/dev/null || echo "Cannot access Docker networks" | tee -a $DIAGNOSTIC_FILE
echo "" | tee -a $DIAGNOSTIC_FILE

echo "Docker Volumes:" | tee -a $DIAGNOSTIC_FILE
docker volume ls 2>/dev/null || echo "Cannot access Docker volumes" | tee -a $DIAGNOSTIC_FILE
echo "" | tee -a $DIAGNOSTIC_FILE

# =============================================================================
# PROJECT DIRECTORIES & PERMISSIONS
# =============================================================================
echo "📁 PROJECT DIRECTORIES & PERMISSIONS" | tee -a $DIAGNOSTIC_FILE
echo "-------------------------------------" | tee -a $DIAGNOSTIC_FILE

# Check common FFL-BRO locations
COMMON_PATHS=(
    "/opt/fflbro"
    "/opt/fflbro/wordpress-main"
    "/opt/fflbro/customer-portal"
    "~/ffl-bro-enhanced-pro"
    "~/rpi-fflbro-v4"
    "~/ffl-bro-enhanced-pro-integrated"
    "/home/pi/ffl-bro-enhanced-pro"
    "/home/pi/rpi-fflbro-v4"
)

for path in "${COMMON_PATHS[@]}"; do
    if [ -d "$path" ]; then
        echo "✅ Found: $path" | tee -a $DIAGNOSTIC_FILE
        ls -la "$path" | head -10 | tee -a $DIAGNOSTIC_FILE
        echo "" | tee -a $DIAGNOSTIC_FILE
    else
        echo "❌ Not found: $path" | tee -a $DIAGNOSTIC_FILE
    fi
done

echo "Current Working Directory: $(pwd)" | tee -a $DIAGNOSTIC_FILE
echo "Contents:" | tee -a $DIAGNOSTIC_FILE
ls -la | tee -a $DIAGNOSTIC_FILE
echo "" | tee -a $DIAGNOSTIC_FILE

# =============================================================================
# DOCKER COMPOSE FILES
# =============================================================================
echo "🔧 DOCKER COMPOSE CONFIGURATION" | tee -a $DIAGNOSTIC_FILE
echo "--------------------------------" | tee -a $DIAGNOSTIC_FILE

COMPOSE_FILES=(
    "docker-compose.yml"
    "docker-compose.yaml"
    "/opt/fflbro/docker-compose.yml"
    "~/docker-compose.yml"
)

for compose_file in "${COMPOSE_FILES[@]}"; do
    if [ -f "$compose_file" ]; then
        echo "✅ Found Docker Compose: $compose_file" | tee -a $DIAGNOSTIC_FILE
        echo "Contents:" | tee -a $DIAGNOSTIC_FILE
        cat "$compose_file" | tee -a $DIAGNOSTIC_FILE
        echo "" | tee -a $DIAGNOSTIC_FILE
    fi
done

# =============================================================================
# NETWORK & PORTS
# =============================================================================
echo "🌐 NETWORK & PORT INFORMATION" | tee -a $DIAGNOSTIC_FILE
echo "------------------------------" | tee -a $DIAGNOSTIC_FILE
echo "IP Addresses:" | tee -a $DIAGNOSTIC_FILE
ip addr show | grep -E "(inet |eth|wlan)" | tee -a $DIAGNOSTIC_FILE
echo "" | tee -a $DIAGNOSTIC_FILE

echo "Open Ports:" | tee -a $DIAGNOSTIC_FILE
netstat -tulpn 2>/dev/null | grep LISTEN | head -20 | tee -a $DIAGNOSTIC_FILE
echo "" | tee -a $DIAGNOSTIC_FILE

echo "Testing Common FFL-BRO Ports:" | tee -a $DIAGNOSTIC_FILE
PORTS=(8181 8182 3306 6379 8025 80 443)
for port in "${PORTS[@]}"; do
    if netstat -tulpn 2>/dev/null | grep ":$port " > /dev/null; then
        echo "✅ Port $port: ACTIVE" | tee -a $DIAGNOSTIC_FILE
    else
        echo "❌ Port $port: INACTIVE" | tee -a $DIAGNOSTIC_FILE
    fi
done
echo "" | tee -a $DIAGNOSTIC_FILE

# =============================================================================
# WORDPRESS INFORMATION
# =============================================================================
echo "🌐 WORDPRESS INSTALLATION INFO" | tee -a $DIAGNOSTIC_FILE
echo "-------------------------------" | tee -a $DIAGNOSTIC_FILE

# Check WordPress installations
WP_PATHS=(
    "/opt/fflbro/wordpress-main"
    "/opt/fflbro/customer-portal"
    "/var/www/html"
)

for wp_path in "${WP_PATHS[@]}"; do
    if [ -f "$wp_path/wp-config.php" ]; then
        echo "✅ WordPress found: $wp_path" | tee -a $DIAGNOSTIC_FILE
        
        # Get WordPress version
        if [ -f "$wp_path/wp-includes/version.php" ]; then
            WP_VERSION=$(grep "wp_version =" "$wp_path/wp-includes/version.php" | cut -d"'" -f2)
            echo "   Version: $WP_VERSION" | tee -a $DIAGNOSTIC_FILE
        fi
        
        # Check plugins directory
        if [ -d "$wp_path/wp-content/plugins" ]; then
            echo "   Installed Plugins:" | tee -a $DIAGNOSTIC_FILE
            ls -la "$wp_path/wp-content/plugins" | grep "^d" | awk '{print "     " $9}' | tee -a $DIAGNOSTIC_FILE
        fi
        
        # Check themes directory
        if [ -d "$wp_path/wp-content/themes" ]; then
            echo "   Installed Themes:" | tee -a $DIAGNOSTIC_FILE
            ls -la "$wp_path/wp-content/themes" | grep "^d" | awk '{print "     " $9}' | tee -a $DIAGNOSTIC_FILE
        fi
        
        # Check uploads directory permissions
        if [ -d "$wp_path/wp-content/uploads" ]; then
            echo "   Uploads Directory Permissions:" | tee -a $DIAGNOSTIC_FILE
            ls -la "$wp_path/wp-content/" | grep uploads | tee -a $DIAGNOSTIC_FILE
        fi
        
        echo "" | tee -a $DIAGNOSTIC_FILE
    fi
done

# =============================================================================
# FFL-BRO PLUGIN INFORMATION
# =============================================================================
echo "🔫 FFL-BRO PLUGIN INFORMATION" | tee -a $DIAGNOSTIC_FILE
echo "------------------------------" | tee -a $DIAGNOSTIC_FILE

# Check for FFL-BRO related files
PLUGIN_PATHS=(
    "/opt/fflbro/wordpress-main/wp-content/plugins/ffl-bro-enhanced-pro"
    "/opt/fflbro/customer-portal/wp-content/plugins/ffl-bro-enhanced-pro"
    "/var/www/html/wp-content/plugins/ffl-bro-enhanced-pro"
)

for plugin_path in "${PLUGIN_PATHS[@]}"; do
    if [ -d "$plugin_path" ]; then
        echo "✅ FFL-BRO Plugin found: $plugin_path" | tee -a $DIAGNOSTIC_FILE
        
        # Check plugin structure
        echo "   Plugin Structure:" | tee -a $DIAGNOSTIC_FILE
        find "$plugin_path" -maxdepth 2 -type f -name "*.php" | head -10 | tee -a $DIAGNOSTIC_FILE
        
        # Check for main plugin file
        if [ -f "$plugin_path/ffl-bro-enhanced-pro.php" ]; then
            echo "   Plugin Version:" | tee -a $DIAGNOSTIC_FILE
            grep -E "(Version|Plugin Name)" "$plugin_path/ffl-bro-enhanced-pro.php" | head -2 | tee -a $DIAGNOSTIC_FILE
        fi
        
        echo "" | tee -a $DIAGNOSTIC_FILE
    fi
done

# =============================================================================
# DATABASE INFORMATION
# =============================================================================
echo "🗄️ DATABASE INFORMATION" | tee -a $DIAGNOSTIC_FILE
echo "------------------------" | tee -a $DIAGNOSTIC_FILE

# Test database connections
if command -v mysql >/dev/null 2>&1; then
    echo "MySQL client available" | tee -a $DIAGNOSTIC_FILE
    
    # Test connection to common DB configs
    MYSQL_HOSTS=("localhost" "127.0.0.1" "fflbro-mysql" "mysql")
    for host in "${MYSQL_HOSTS[@]}"; do
        if mysql -h"$host" -uroot -p"wordpress" -e "SHOW DATABASES;" 2>/dev/null | grep -q wordpress; then
            echo "✅ Database accessible: $host" | tee -a $DIAGNOSTIC_FILE
            mysql -h"$host" -uroot -p"wordpress" -e "SHOW DATABASES;" 2>/dev/null | tee -a $DIAGNOSTIC_FILE
        else
            echo "❌ Database not accessible: $host" | tee -a $DIAGNOSTIC_FILE
        fi
    done
else
    echo "MySQL client not available" | tee -a $DIAGNOSTIC_FILE
fi

# Test Docker database if available
if docker ps | grep -q mysql; then
    echo "Testing Docker MySQL:" | tee -a $DIAGNOSTIC_FILE
    docker exec $(docker ps --format "{{.Names}}" | grep mysql | head -1) mysql -uroot -pwordpress -e "SHOW DATABASES;" 2>/dev/null | tee -a $DIAGNOSTIC_FILE || echo "Docker MySQL access failed" | tee -a $DIAGNOSTIC_FILE
fi
echo "" | tee -a $DIAGNOSTIC_FILE

# =============================================================================
# API & INTEGRATION STATUS
# =============================================================================
echo "🔌 API & INTEGRATION STATUS" | tee -a $DIAGNOSTIC_FILE
echo "----------------------------" | tee -a $DIAGNOSTIC_FILE

# Test WordPress REST API endpoints
SITES=("localhost:8181" "localhost:8182")
for site in "${SITES[@]}"; do
    echo "Testing WordPress REST API: $site" | tee -a $DIAGNOSTIC_FILE
    curl -s "http://$site/wp-json/" | head -3 | tee -a $DIAGNOSTIC_FILE 2>/dev/null || echo "API not accessible" | tee -a $DIAGNOSTIC_FILE
    echo "" | tee -a $DIAGNOSTIC_FILE
done

# Test external APIs
echo "Testing External API Connectivity:" | tee -a $DIAGNOSTIC_FILE
APIS=("api.lipseys.com" "api.gunbroker.com" "google.com")
for api in "${APIS[@]}"; do
    if ping -c1 "$api" >/dev/null 2>&1; then
        echo "✅ $api: REACHABLE" | tee -a $DIAGNOSTIC_FILE
    else
        echo "❌ $api: UNREACHABLE" | tee -a $DIAGNOSTIC_FILE
    fi
done
echo "" | tee -a $DIAGNOSTIC_FILE

# =============================================================================
# LOG FILES
# =============================================================================
echo "📋 RECENT LOG ENTRIES" | tee -a $DIAGNOSTIC_FILE
echo "----------------------" | tee -a $DIAGNOSTIC_FILE

# System logs
if [ -f "/var/log/syslog" ]; then
    echo "Recent System Log (last 10 lines):" | tee -a $DIAGNOSTIC_FILE
    tail -10 /var/log/syslog | tee -a $DIAGNOSTIC_FILE
    echo "" | tee -a $DIAGNOSTIC_FILE
fi

# Docker logs
if docker ps --format "{{.Names}}" | head -3; then
    echo "Recent Docker Container Logs:" | tee -a $DIAGNOSTIC_FILE
    for container in $(docker ps --format "{{.Names}}" | head -3); do
        echo "--- $container ---" | tee -a $DIAGNOSTIC_FILE
        docker logs --tail=5 "$container" 2>&1 | tee -a $DIAGNOSTIC_FILE
        echo "" | tee -a $DIAGNOSTIC_FILE
    done
fi

# WordPress logs
WP_LOG_PATHS=(
    "/opt/fflbro/wordpress-main/wp-content/debug.log"
    "/opt/fflbro/customer-portal/wp-content/debug.log"
    "/var/www/html/wp-content/debug.log"
)

for log_path in "${WP_LOG_PATHS[@]}"; do
    if [ -f "$log_path" ]; then
        echo "WordPress Debug Log: $log_path (last 5 lines)" | tee -a $DIAGNOSTIC_FILE
        tail -5 "$log_path" | tee -a $DIAGNOSTIC_FILE
        echo "" | tee -a $DIAGNOSTIC_FILE
    fi
done

# =============================================================================
# PROCESS INFORMATION
# =============================================================================
echo "⚙️ RUNNING PROCESSES" | tee -a $DIAGNOSTIC_FILE
echo "--------------------" | tee -a $DIAGNOSTIC_FILE
echo "Key Processes:" | tee -a $DIAGNOSTIC_FILE
ps aux | grep -E "(mysql|nginx|apache|php|docker|wordpress)" | grep -v grep | tee -a $DIAGNOSTIC_FILE
echo "" | tee -a $DIAGNOSTIC_FILE

# =============================================================================
# ENVIRONMENT VARIABLES
# =============================================================================
echo "🌍 ENVIRONMENT VARIABLES" | tee -a $DIAGNOSTIC_FILE
echo "-------------------------" | tee -a $DIAGNOSTIC_FILE
env | grep -E "(PATH|HOME|USER|DOCKER|WP_|MYSQL)" | sort | tee -a $DIAGNOSTIC_FILE
echo "" | tee -a $DIAGNOSTIC_FILE

# =============================================================================
# COMPLETION
# =============================================================================
echo "=========================================" | tee -a $DIAGNOSTIC_FILE
echo "✅ Diagnostic Complete!" | tee -a $DIAGNOSTIC_FILE
echo "Report saved as: $DIAGNOSTIC_FILE" | tee -a $DIAGNOSTIC_FILE
echo "File size: $(du -h $DIAGNOSTIC_FILE | cut -f1)" | tee -a $DIAGNOSTIC_FILE
echo "=========================================" | tee -a $DIAGNOSTIC_FILE

echo ""
echo "🎯 NEXT STEPS:"
echo "1. Copy the contents of '$DIAGNOSTIC_FILE' and share with Claude"
echo "2. Or upload the file directly if your system supports it"
echo "3. This will provide all necessary information for accurate troubleshooting"
echo ""
echo "📄 To view the report:"
echo "   cat $DIAGNOSTIC_FILE"
echo ""
echo "📋 To copy to clipboard (if available):"
echo "   cat $DIAGNOSTIC_FILE | xclip -selection clipboard"
