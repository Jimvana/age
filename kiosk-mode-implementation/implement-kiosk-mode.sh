#!/bin/bash
# Kiosk Mode Implementation Script
# This script helps apply the kiosk mode changes to your Age Estimator plugin

PLUGIN_DIR="/Users/video/DevKinsta/public/age-estimator/wp-content/plugins/Age-estimator-live"
IMPLEMENTATION_DIR="$PLUGIN_DIR/kiosk-mode-implementation"

echo "Age Estimator - Kiosk Mode Implementation"
echo "========================================"
echo ""

# Check if we're in the right directory
if [ ! -d "$PLUGIN_DIR" ]; then
    echo "Error: Plugin directory not found at $PLUGIN_DIR"
    exit 1
fi

# Create backup directory
BACKUP_DIR="$PLUGIN_DIR/backups/kiosk-mode-$(date +%Y%m%d-%H%M%S)"
echo "Creating backup directory: $BACKUP_DIR"
mkdir -p "$BACKUP_DIR"

# Backup existing files
echo "Backing up existing files..."
cp "$PLUGIN_DIR/includes/admin-settings.php" "$BACKUP_DIR/" 2>/dev/null
cp "$PLUGIN_DIR/templates/photo-inline.php" "$BACKUP_DIR/" 2>/dev/null
cp "$PLUGIN_DIR/js/photo-age-estimator-continuous.js" "$BACKUP_DIR/" 2>/dev/null
cp "$PLUGIN_DIR/css/photo-age-estimator.css" "$BACKUP_DIR/" 2>/dev/null
cp "$PLUGIN_DIR/js/admin.js" "$BACKUP_DIR/" 2>/dev/null

echo ""
echo "Backups created in: $BACKUP_DIR"
echo ""
echo "IMPORTANT: Manual Implementation Required"
echo "========================================="
echo ""
echo "The implementation files have been created in:"
echo "$IMPLEMENTATION_DIR"
echo ""
echo "Please manually apply the changes from these files:"
echo ""
echo "1. admin-settings-patch.php -> Add to includes/admin-settings.php"
echo "2. template-patch.php -> Add to templates/photo-inline.php"
echo "3. javascript-additions.js -> Add to js/photo-age-estimator-continuous.js"
echo "4. kiosk-styles.css -> Add to css/photo-age-estimator.css"
echo "5. admin-javascript.js -> Add to js/admin.js"
echo ""
echo "Refer to README.md for detailed implementation instructions."
echo ""
echo "After implementation:"
echo "1. Go to WordPress Admin > Age Estimator > Settings"
echo "2. Enable Kiosk Mode"
echo "3. Upload your advertisement image"
echo "4. Configure display settings"
echo ""
echo "For rollback, restore files from: $BACKUP_DIR"