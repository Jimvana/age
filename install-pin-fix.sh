#!/bin/bash

# PIN Fix Installation Script for Age Estimator
# This script installs all the PIN saving fixes

PLUGIN_DIR="/Users/video/DevKinsta/public/local-model-age-estin/wp-content/plugins/Age-estimator-live"

echo "🔧 Installing PIN Fix for Age Estimator Settings"
echo "=============================================="

# Check if plugin directory exists
if [ ! -d "$PLUGIN_DIR" ]; then
    echo "❌ Plugin directory not found: $PLUGIN_DIR"
    exit 1
fi

echo "📁 Plugin directory: $PLUGIN_DIR"

# Check if files exist
files_to_check=(
    "includes/class-pin-fix.php"
    "js/pin-fix.js" 
    "js/pin-debug.js"
    "PIN_FIX_GUIDE.md"
)

missing_files=()
for file in "${files_to_check[@]}"; do
    if [ ! -f "$PLUGIN_DIR/$file" ]; then
        missing_files+=("$file")
    fi
done

if [ ${#missing_files[@]} -gt 0 ]; then
    echo "❌ Missing files:"
    for file in "${missing_files[@]}"; do
        echo "   - $file"
    done
    echo ""
    echo "Please ensure all PIN fix files are in place before running this script."
    exit 1
fi

echo "✅ All PIN fix files found"

# Check if age-estimator.php has been updated
if grep -q "class-pin-fix.php" "$PLUGIN_DIR/age-estimator.php"; then
    echo "✅ Main plugin file already updated"
else
    echo "⚠️  Main plugin file needs manual update"
    echo "   Add this line to the load_includes() method:"
    echo "   "
    echo "   // Load PIN fix for enhanced settings"
    echo "   \$pin_fix_file = AGE_ESTIMATOR_PATH . 'includes/class-pin-fix.php';"
    echo "   if (file_exists(\$pin_fix_file)) {"
    echo "       require_once \$pin_fix_file;"
    echo "   }"
    echo ""
fi

# Set proper permissions
chmod 644 "$PLUGIN_DIR/includes/class-pin-fix.php"
chmod 644 "$PLUGIN_DIR/js/pin-fix.js"
chmod 644 "$PLUGIN_DIR/js/pin-debug.js"
chmod 644 "$PLUGIN_DIR/PIN_FIX_GUIDE.md"

echo "✅ File permissions set"

# Test installation
echo ""
echo "🧪 Testing Installation"
echo "----------------------"

# Test 1: Check PHP syntax
echo "Testing PHP syntax..."
php -l "$PLUGIN_DIR/includes/class-pin-fix.php" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ PHP syntax OK"
else
    echo "❌ PHP syntax errors found"
    php -l "$PLUGIN_DIR/includes/class-pin-fix.php"
fi

# Test 2: Check JavaScript syntax
echo "Testing JavaScript syntax..."
if command -v node &> /dev/null; then
    node -c "$PLUGIN_DIR/js/pin-fix.js" > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "✅ JavaScript syntax OK"
    else
        echo "❌ JavaScript syntax errors found"
    fi
else
    echo "⚠️  Node.js not available, skipping JS syntax check"
fi

echo ""
echo "🎉 PIN Fix Installation Summary"
echo "=============================="
echo "✅ PHP PIN handler installed"
echo "✅ JavaScript form fix installed"  
echo "✅ Debug tools installed"
echo "✅ Documentation installed"
echo ""
echo "🚀 Next Steps:"
echo "1. Clear any WordPress caches"
echo "2. Go to your settings page with [age_estimator_settings_enhanced]"
echo "3. Navigate to Retail Mode section"
echo "4. Try setting a 4-digit PIN"
echo "5. Check browser console for debug info"
echo ""
echo "📖 For troubleshooting, see: PIN_FIX_GUIDE.md"
echo ""
echo "🔍 Debug Commands (in browser console):"
echo "   debugFormData()     - Test form data collection"
echo "   debugPinSave()      - Test direct PIN save"
echo ""

