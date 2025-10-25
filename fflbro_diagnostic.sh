#!/bin/bash

#####################################################################
# FFL-BRO STACK & DIRECTORY DIAGNOSTIC
# Find the ACTUAL running stack and fix the RIGHT directories
# NO MORE RUNNING IN CIRCLES!
#####################################################################

set -e

# Colors for clarity
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${BLUE}"
echo "=================================================================="
echo "  🔍 FFL-BRO STACK DIAGNOSTIC - STOP RUNNING IN CIRCLES"
echo "  Finding the ACTUAL running stack and directories"
echo "=================================================================="
echo -e "${NC}"

# Function to print status
print_status() {
    echo -e "${CYAN}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Step 1: Find running Docker containers
print_status "🔍 Scanning for running Docker containers..."
echo ""

if command -v docker &> /dev/null; then
    echo "Running containers:"
    docker ps --format "table {{.Names}}\t{{.Image}}\t{{.Ports}}\t{{.Status}}"
    echo ""
    
    # Check for WordPress containers specifically
    WP_CONTAINERS=$(docker ps --filter "ancestor=wordpress" --format "{{.Names}}" 2>/dev/null || echo "")
    if [ ! -z "$WP_CONTAINERS" ]; then
        print_success "Found WordPress containers: $WP_CONTAINERS"
    else
        print_warning "No WordPress containers found running"
    fi
    echo ""
else
    print_error "Docker not found or not running"
    exit 1
fi

# Step 2: Find volume mounts and actual directories
print_status "📁 Finding Docker volume mounts..."
echo ""

for container in $(docker ps --format "{{.Names}}"); do
    echo "=== Container: $container ==="
    docker inspect $container --format='{{range .Mounts}}{{.Source}} -> {{.Destination}}{{"\n"}}{{end}}' 2>/dev/null || echo "Cannot inspect $container"
    echo ""
done

# Step 3: Find all possible FFL-BRO directories
print_status "🗂️  Searching for FFL-BRO installations..."
echo ""

SEARCH_PATHS=(
    "/opt/fflbro"
    "/opt/neefebro" 
    "/opt/ffl-bro"
    "/home/pi/fflbro"
    "/home/pi/neefebro"
    "/var/www"
    "/opt/docker"
)

FOUND_DIRS=()

for path in "${SEARCH_PATHS[@]}"; do
    if [ -d "$path" ]; then
        echo "Found: $path"
        FOUND_DIRS+=("$path")
        
        # Check what's inside
        ls -la "$path" 2>/dev/null | head -10
        echo ""
        
        # Look for WordPress specifically
        if [ -d "$path/wordpress-main" ]; then
            print_success "WordPress main found in: $path/wordpress-main"
        fi
        if [ -d "$path/wordpress-customer" ]; then
            print_success "WordPress customer found in: $path/wordpress-customer"
        fi
        
        # Look for docker-compose files
        if [ -f "$path/docker-compose.yml" ]; then
            print_success "Docker compose found: $path/docker-compose.yml"
        fi
        echo "---"
    fi
done

# Step 4: Find WordPress plugin directories
print_status "🔌 Searching for WordPress plugin directories..."
echo ""

find /opt /home -name "wp-content" -type d 2>/dev/null | while read wp_content; do
    if [ -d "$wp_content/plugins" ]; then
        echo "WordPress plugins: $wp_content/plugins"
        
        # Check for FFL-BRO plugins
        find "$wp_content/plugins" -name "*ffl*" -o -name "*bro*" 2>/dev/null | while read plugin; do
            echo "  └── FFL-BRO plugin: $plugin"
            
            # Check plugin file details
            if [ -f "$plugin" ]; then
                echo "      File size: $(du -h "$plugin" | cut -f1)"
                echo "      Modified: $(stat -c %y "$plugin" 2>/dev/null || echo "Unknown")"
            elif [ -d "$plugin" ]; then
                echo "      Directory size: $(du -sh "$plugin" | cut -f1)"
                echo "      Files: $(find "$plugin" -type f | wc -l)"
            fi
        done
        echo ""
    fi
done

# Step 5: Check which WordPress sites are actually accessible
print_status "🌐 Testing WordPress site accessibility..."
echo ""

PORTS_TO_CHECK=(8181 8182 80 443 8080 8181 8183)

for port in "${PORTS_TO_CHECK[@]}"; do
    if curl -s --connect-timeout 3 "http://localhost:$port" > /dev/null 2>&1; then
        print_success "WordPress responding on port $port"
        
        # Try to get WordPress info
        TITLE=$(curl -s "http://localhost:$port" | grep -o '<title>[^<]*</title>' | sed 's/<[^>]*>//g' 2>/dev/null || echo "")
        if [ ! -z "$TITLE" ]; then
            echo "    Site title: $TITLE"
        fi
    else
        echo "Port $port: Not responding"
    fi
done
echo ""

# Step 6: Check for docker-compose files and their configurations
print_status "⚙️  Analyzing Docker Compose configurations..."
echo ""

find /opt /home -name "docker-compose*.yml" 2>/dev/null | while read compose_file; do
    echo "=== Docker Compose: $compose_file ==="
    
    # Extract WordPress port mappings
    if grep -q "wordpress" "$compose_file"; then
        echo "WordPress services found:"
        grep -A 5 -B 5 "ports:" "$compose_file" | grep -E "(wordpress|ports|^[[:space:]]*-)"
        echo ""
    fi
    
    # Extract volume mappings  
    echo "Volume mappings:"
    grep -A 10 "volumes:" "$compose_file" | grep -E "^\s*-.*:.*$" | head -5
    echo "---"
done

# Step 7: Generate recommendations
print_status "💡 RECOMMENDATIONS TO STOP RUNNING IN CIRCLES:"
echo ""

echo -e "${YELLOW}Based on the scan above:${NC}"
echo ""
echo "1. 🎯 IDENTIFY THE ACTIVE STACK:"
echo "   - Look for the WordPress containers that are ACTUALLY running"
echo "   - Note which ports they're using (8181, 8182, etc.)"
echo "   - Find the corresponding docker-compose.yml file"
echo ""
echo "2. 📁 FIND THE CORRECT PLUGIN DIRECTORY:"
echo "   - Check the volume mounts from active containers"
echo "   - Locate the wp-content/plugins directory that's ACTUALLY being used"
echo "   - This is where you need to update files"
echo ""
echo "3. 🔧 FIX PERMISSIONS PROPERLY:"
echo "   - Set ownership to www-data:www-data for the active directories"
echo "   - Use 755 for directories, 644 for files"
echo "   - Apply to the CORRECT path, not old/unused ones"
echo ""
echo "4. ✅ UPDATE THE RIGHT FILES:"
echo "   - Only modify files in the active plugin directory"
echo "   - Back up before making changes"
echo "   - Test changes immediately"
echo ""

print_status "🎯 NEXT STEP: Copy this output and identify your ACTIVE stack!"
print_status "📋 Then run the appropriate fix script for THAT specific stack."

echo ""
echo -e "${GREEN}=================================================================="
echo "  🏁 DIAGNOSTIC COMPLETE"
echo "  Now you know exactly which stack is running!"
echo "=================================================================="
echo -e "${NC}"