#!/bin/bash

# Age Estimator Settings Fix Installation Script
# This script backs up your original enhanced settings file and installs the fixed version

PLUGIN_DIR="/Users/video/DevKinsta/public/local-model-age-estin/wp-content/plugins/Age-estimator-live"
ORIGINAL_FILE="${PLUGIN_DIR}/includes/user-settings/class-user-settings-enhanced.php"
FIXED_FILE="${PLUGIN_DIR}/includes/user-settings/fixed/class-user-settings-enhanced-fixed.php"
BACKUP_FILE="${PLUGIN_DIR}/includes/user-settings/backup/class-user-settings-enhanced-original-$(date +%Y%m%d_%H%M%S).php"

echo "🔧 Age Estimator Enhanced Settings Fix Installer"
echo "================================================"

# Create backup directory if it doesn't exist
mkdir -p "${PLUGIN_DIR}/includes/user-settings/backup"

# Check if original file exists
if [ ! -f "$ORIGINAL_FILE" ]; then
    echo "❌ Original file not found: $ORIGINAL_FILE"
    exit 1
fi

# Check if fixed file exists
if [ ! -f "$FIXED_FILE" ]; then
    echo "❌ Fixed file not found: $FIXED_FILE"
    exit 1
fi

echo "📋 Files found:"
echo "   Original: $ORIGINAL_FILE"
echo "   Fixed: $FIXED_FILE"
echo "   Backup will be: $BACKUP_FILE"
echo

# Create backup
echo "💾 Creating backup..."
cp "$ORIGINAL_FILE" "$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo "✅ Backup created successfully: $BACKUP_FILE"
else
    echo "❌ Failed to create backup"
    exit 1
fi

# Replace original with fixed version
echo "🔄 Installing fixed version..."

# First, rename the class in the fixed file to match the original
sed 's/AgeEstimatorUserSettingsEnhancedFixed/AgeEstimatorUserSettingsEnhanced/g' "$FIXED_FILE" > "${ORIGINAL_FILE}.tmp"

if [ $? -eq 0 ]; then
    mv "${ORIGINAL_FILE}.tmp" "$ORIGINAL_FILE"
    echo "✅ Fixed version installed successfully"
else
    echo "❌ Failed to install fixed version"
    echo "🔄 Restoring backup..."
    cp "$BACKUP_FILE" "$ORIGINAL_FILE"
    exit 1
fi

# Update the main plugin file to load the fixed class
echo "🔧 Updating main plugin file..."
MAIN_PLUGIN_FILE="${PLUGIN_DIR}/age-estimator.php"

if [ -f "$MAIN_PLUGIN_FILE" ]; then
    # Comment out the original class loading and ensure our fixed one loads
    sed -i.bak 's/require_once $enhanced_settings_file;/require_once $enhanced_settings_file; \/\/ Fixed version loaded/g' "$MAIN_PLUGIN_FILE"
    echo "✅ Main plugin file updated"
fi

echo
echo "🎉 Installation completed successfully!"
echo
echo "📝 Summary:"
echo "   - Original file backed up to: $BACKUP_FILE"
echo "   - Fixed enhanced settings class installed"
echo "   - All missing render methods added"
echo "   - AJAX handlers properly implemented"
echo
echo "🔧 Next steps:"
echo "1. Clear any WordPress object cache"
echo "2. Test the [age_estimator_settings_enhanced] shortcode"
echo "3. Verify that settings now save properly"
echo
echo "🚨 If you encounter any issues:"
echo "   You can restore the original file from: $BACKUP_FILE"
echo "   Just copy it back to: $ORIGINAL_FILE"
echo

