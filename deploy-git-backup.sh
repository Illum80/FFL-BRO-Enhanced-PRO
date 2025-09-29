#!/bin/bash

# ============================================================================
# DEPLOY GIT AND BACKUP SYSTEM FOR FFL-BRO ENHANCED PRO
# Execute these commands to secure your working plugin immediately
# ============================================================================

echo "🚀 DEPLOYING GIT AND BACKUP SYSTEM FOR FFL-BRO ENHANCED PRO"
echo "============================================================"

# Navigate to your plugin directory
cd /opt/fflbro/wordpress-main/wp-content/plugins/ffl-bro-enhanced-pro/

echo "📍 Current location: $(pwd)"
echo "📄 Plugin file: $(ls -la ffl-bro-enhanced-pro.php | awk '{print $5 " bytes, " $9}')"
echo "📏 Line count: $(wc -l ffl-bro-enhanced-pro.php)"
echo ""

# ============================================================================
# STEP 1: INITIALIZE GIT REPOSITORY
# ============================================================================

echo "🔧 STEP 1: INITIALIZING GIT REPOSITORY"
echo "======================================"

# Initialize Git repository
if [ ! -d ".git" ]; then
    git init
    echo "✅ Git repository initialized"
else
    echo "✅ Git repository already exists"
fi

# Configure Git user (replace with your details)
git config user.email "jrneefe@gmail.com"
git config user.name "NEEFECO ARMS"
echo "✅ Git user configured"

# Create .gitignore file
cat > .gitignore << 'EOF'
# WordPress Plugin .gitignore
*.log
.DS_Store
Thumbs.db
node_modules/
vendor/
*.tmp
*.cache
.env
*.backup
*.bak
*~
.idea/
.vscode/
EOF

echo "✅ .gitignore created"

# Create README.md
cat > README.md << 'EOF'
# FFL-BRO Enhanced PRO

Professional FFL management system with comprehensive business tools.

## Features
- Enhanced Quote Generator v6.3
- Multi-Distributor Integration (Lipseys, RSR, Sports South, Orion)
- Digital Form 4473 Processing
- Customer Management System
- Business Analytics & Reporting
- Compliance Monitoring

## Version History
- v1.0.0: Initial working version (1903 lines)
- Working Lipseys integration with 16,887 products
- Complete admin interface with 13 modules

## Installation
1. Upload to WordPress plugins directory
2. Activate plugin
3. Configure distributor credentials in Settings
4. Test connections and start generating quotes

## Support
For support and updates, contact NEEFECO ARMS.
EOF

echo "✅ README.md created"

# ============================================================================
# STEP 2: CREATE INITIAL COMMIT
# ============================================================================

echo ""
echo "📝 STEP 2: CREATING INITIAL COMMIT"
echo "=================================="

# Stage all files
git add .
echo "✅ Files staged for commit"

# Create initial commit with timestamp
COMMIT_MESSAGE="INITIAL COMMIT: Working FFL-BRO Enhanced PRO v6.3 - $(date)"
git commit -m "$COMMIT_MESSAGE"
echo "✅ Initial commit created"

# Create tags for this working version
git tag -a v1.0.0 -m "Initial working version - 1903 lines of code - $(date)"
git tag -a working-backup -m "Confirmed working state - $(date)"
echo "✅ Tags created: v1.0.0, working-backup"

# ============================================================================
# STEP 3: CREATE BACKUP DIRECTORY STRUCTURE
# ============================================================================

echo ""
echo "💾 STEP 3: CREATING BACKUP SYSTEM"
echo "================================="

# Create backup directories
BACKUP_ROOT="/opt/fflbro/backups/FFL-BRO-Enhanced-PRO"
sudo mkdir -p "${BACKUP_ROOT}/permanent"
sudo mkdir -p "${BACKUP_ROOT}/daily"
sudo mkdir -p "${BACKUP_ROOT}/weekly"
sudo mkdir -p "${BACKUP_ROOT}/monthly"
sudo mkdir -p "${BACKUP_ROOT}/checksums"
sudo mkdir -p "${BACKUP_ROOT}/corrupted"
sudo mkdir -p "/opt/fflbro/scripts"

# Set ownership
sudo chown -R pi:pi "${BACKUP_ROOT}"
sudo chown -R pi:pi "/opt/fflbro/scripts"

echo "✅ Backup directory structure created"

# ============================================================================
# STEP 4: CREATE IMMUTABLE BACKUP
# ============================================================================

echo ""
echo "🔒 STEP 4: CREATING IMMUTABLE BACKUP"
echo "===================================="

TIMESTAMP=$(date +"%Y-%m-%d-%H%M%S")
BACKUP_FILE="${BACKUP_ROOT}/permanent/FFL-BRO-Enhanced-PRO_${TIMESTAMP}_WORKING_v1.0.0.tar.gz"

# Create compressed backup
tar -czf "$BACKUP_FILE" -C /opt/fflbro/wordpress-main/wp-content/plugins/ ffl-bro-enhanced-pro/
echo "✅ Compressed backup created: $(basename "$BACKUP_FILE")"

# Generate checksums
sha256sum "$BACKUP_FILE" > "${BACKUP_ROOT}/checksums/$(basename "$BACKUP_FILE").sha256"
echo "✅ Checksum generated"

# Make backup read-only and immutable
chmod 400 "$BACKUP_FILE"
chmod 400 "${BACKUP_ROOT}/checksums/$(basename "$BACKUP_FILE").sha256"

# Attempt to make immutable (might need sudo)
if sudo chattr +i "$BACKUP_FILE" 2>/dev/null; then
    echo "✅ Backup made immutable (protected from deletion)"
else
    echo "⚠️ Could not make immutable - backup is read-only only"
fi

# ============================================================================
# STEP 5: CREATE BACKUP SCRIPTS
# ============================================================================

echo ""
echo "🔧 STEP 5: CREATING AUTOMATED BACKUP SCRIPTS"
echo "============================================="

# Create daily backup script
cat > /opt/fflbro/scripts/ffl-bro-backup.sh << 'BACKUP_SCRIPT_EOF'
#!/bin/bash

PLUGIN_PATH="/opt/fflbro/wordpress-main/wp-content/plugins/ffl-bro-enhanced-pro"
BACKUP_ROOT="/opt/fflbro/backups/FFL-BRO-Enhanced-PRO"
TIMESTAMP=$(date +"%Y-%m-%d-%H%M%S")

# Get version from plugin file
VERSION=$(grep "Version:" "${PLUGIN_PATH}/ffl-bro-enhanced-pro.php" | head -1 | sed 's/.*Version: *\([0-9.]*\).*/\1/')

# Create daily backup
BACKUP_FILE="${BACKUP_ROOT}/daily/FFL-BRO-Enhanced-PRO_${TIMESTAMP}_v${VERSION}.tar.gz"
mkdir -p "$(dirname "$BACKUP_FILE")"

# Create backup
tar -czf "$BACKUP_FILE" -C "$(dirname "$PLUGIN_PATH")" "$(basename "$PLUGIN_PATH")"

# Generate checksum
sha256sum "$BACKUP_FILE" > "${BACKUP_FILE}.sha256"

# Protect files
chmod 400 "$BACKUP_FILE" "${BACKUP_FILE}.sha256"

echo "$(date): Backup created - $(basename "$BACKUP_FILE")"

# Weekly backup on Sundays
if [[ $(date +%u) -eq 7 ]]; then
    WEEKLY_DIR="${BACKUP_ROOT}/weekly/$(date +%Y-W%U)"
    mkdir -p "$WEEKLY_DIR"
    cp "$BACKUP_FILE" "$WEEKLY_DIR/"
    echo "$(date): Weekly backup created"
fi

# Monthly backup on first day
if [[ $(date +%d) -eq 01 ]]; then
    MONTHLY_DIR="${BACKUP_ROOT}/monthly/$(date +%Y-%m)"
    mkdir -p "$MONTHLY_DIR"
    cp "$BACKUP_FILE" "$MONTHLY_DIR/"
    echo "$(date): Monthly backup created"
fi

# Cleanup old daily backups (keep 7 days)
find "${BACKUP_ROOT}/daily" -name "*.tar.gz" -mtime +7 -delete 2>/dev/null || true
find "${BACKUP_ROOT}/daily" -name "*.sha256" -mtime +7 -delete 2>/dev/null || true
BACKUP_SCRIPT_EOF

chmod +x /opt/fflbro/scripts/ffl-bro-backup.sh
echo "✅ Automated backup script created"

# Create integrity check script
cat > /opt/fflbro/scripts/integrity-check.sh << 'INTEGRITY_SCRIPT_EOF'
#!/bin/bash

PLUGIN_DIR="/opt/fflbro/wordpress-main/wp-content/plugins/ffl-bro-enhanced-pro"
CHECKSUM_FILE="/opt/fflbro/backups/FFL-BRO-Enhanced-PRO/checksums/current-state.sha256"

if [[ ! -f "$CHECKSUM_FILE" ]]; then
    # First run - generate baseline
    find "$PLUGIN_DIR" -type f -name "*.php" -exec sha256sum {} \; > "$CHECKSUM_FILE"
    echo "$(date): Baseline checksums created"
else
    # Verify integrity
    if cd / && sha256sum -c "$CHECKSUM_FILE" --quiet; then
        echo "$(date): ✓ All files intact"
    else
        echo "$(date): ⚠ FILE INTEGRITY WARNING - Changes detected"
        # Auto-backup on changes
        /opt/fflbro/scripts/ffl-bro-backup.sh
    fi
fi
INTEGRITY_SCRIPT_EOF

chmod +x /opt/fflbro/scripts/integrity-check.sh
echo "✅ Integrity check script created"

# Create emergency restore script
cat > /opt/fflbro/scripts/emergency-restore.sh << 'RESTORE_SCRIPT_EOF'
#!/bin/bash

PLUGIN_DIR="/opt/fflbro/wordpress-main/wp-content/plugins/ffl-bro-enhanced-pro"
BACKUP_ROOT="/opt/fflbro/backups/FFL-BRO-Enhanced-PRO"

echo "🚨 EMERGENCY RESTORE PROCEDURE"
echo "==============================="

# Find latest verified backup
echo "Searching for verified backups..."
LATEST_BACKUP=""

# Check permanent backups first
for backup in $(find "${BACKUP_ROOT}/permanent" -name "*.tar.gz" -type f | sort -r); do
    if sha256sum -c "${backup}.sha256" --quiet 2>/dev/null; then
        LATEST_BACKUP="$backup"
        break
    fi
done

# If no permanent backup, check daily
if [[ -z "$LATEST_BACKUP" ]]; then
    for backup in $(find "${BACKUP_ROOT}/daily" -name "*.tar.gz" -type f | sort -r); do
        if sha256sum -c "${backup}.sha256" --quiet 2>/dev/null; then
            LATEST_BACKUP="$backup"
            break
        fi
    done
fi

if [[ -z "$LATEST_BACKUP" ]]; then
    echo "❌ ERROR: No verified backup found!"
    exit 1
fi

echo "✅ Found verified backup: $(basename "$LATEST_BACKUP")"

# Backup current corrupted version
echo "Backing up current state..."
mkdir -p "${BACKUP_ROOT}/corrupted"
tar -czf "${BACKUP_ROOT}/corrupted/corrupted_$(date +%Y%m%d_%H%M%S).tar.gz" "$PLUGIN_DIR"

# Restore from backup
echo "Restoring from backup..."
tar -xzf "$LATEST_BACKUP" -C /opt/fflbro/wordpress-main/wp-content/plugins/

echo "✅ Restoration complete!"
echo "Plugin restored from: $(basename "$LATEST_BACKUP")"
echo "Corrupted version backed up to: ${BACKUP_ROOT}/corrupted/"
RESTORE_SCRIPT_EOF

chmod +x /opt/fflbro/scripts/emergency-restore.sh
echo "✅ Emergency restore script created"

# ============================================================================
# STEP 6: VERIFICATION AND STATUS
# ============================================================================

echo ""
echo "🔍 STEP 6: VERIFICATION AND STATUS"
echo "=================================="

# Verify Git status
echo "📊 GIT STATUS:"
git status --short
echo ""

# Show Git log
echo "📝 GIT LOG:"
git log --oneline -3
echo ""

# Show tags
echo "🏷️ GIT TAGS:"
git tag -l
echo ""

# Show backup status
echo "💾 BACKUP STATUS:"
echo "Backup root: $BACKUP_ROOT"
ls -la "${BACKUP_ROOT}/permanent/" 2>/dev/null | tail -3
echo ""

# Show checksums
echo "🔒 CHECKSUMS:"
ls -la "${BACKUP_ROOT}/checksums/" 2>/dev/null | tail -3
echo ""

# Show scripts
echo "🔧 SCRIPTS CREATED:"
ls -la /opt/fflbro/scripts/
echo ""

# ============================================================================
# STEP 7: SETUP AUTOMATION (OPTIONAL)
# ============================================================================

echo ""
echo "⚙️ STEP 7: SETUP AUTOMATION (OPTIONAL)"
echo "======================================"

echo "To set up automated backups, run:"
echo "crontab -e"
echo ""
echo "Then add these lines:"
echo "# Daily backup at 2 AM"
echo "0 2 * * * /opt/fflbro/scripts/ffl-bro-backup.sh >> /opt/fflbro/logs/backup.log 2>&1"
echo ""
echo "# Integrity check every 4 hours"
echo "0 */4 * * * /opt/fflbro/scripts/integrity-check.sh >> /opt/fflbro/logs/integrity.log 2>&1"
echo ""

# Create log directory
mkdir -p /opt/fflbro/logs
echo "✅ Log directory created: /opt/fflbro/logs"

# ============================================================================
# COMPLETION SUMMARY
# ============================================================================

echo ""
echo "🎉 DEPLOYMENT COMPLETE!"
echo "======================="
echo ""
echo "✅ Git repository initialized with working plugin"
echo "✅ Initial commit created with tags (v1.0.0, working-backup)"
echo "✅ Immutable backup created: $BACKUP_FILE"
echo "✅ Automated backup scripts installed"
echo "✅ Emergency restore script ready"
echo "✅ Integrity monitoring script ready"
echo ""
echo "🎯 WHAT YOU CAN DO NOW:"
echo "======================"
echo ""
echo "1. 📝 MAKE CHANGES:"
echo "   - Edit your plugin files normally"
echo "   - Use: git add . && git commit -m 'description of changes'"
echo ""
echo "2. 💾 MANUAL BACKUP:"
echo "   - Run: /opt/fflbro/scripts/ffl-bro-backup.sh"
echo ""
echo "3. 🔍 CHECK INTEGRITY:"
echo "   - Run: /opt/fflbro/scripts/integrity-check.sh"
echo ""
echo "4. 🚨 EMERGENCY RESTORE:"
echo "   - Run: /opt/fflbro/scripts/emergency-restore.sh"
echo ""
echo "5. 📊 VIEW STATUS:"
echo "   - Git: git status"
echo "   - Backups: ls -la ${BACKUP_ROOT}/permanent/"
echo ""
echo "🔐 YOUR PLUGIN IS NOW FULLY PROTECTED!"
echo "======================================"
echo "- Working version preserved in Git"
echo "- Immutable backup created"
echo "- Automated backup system ready"
echo "- Emergency recovery procedures in place"
echo ""
echo "You can now confidently make changes knowing your working version is safe!"
