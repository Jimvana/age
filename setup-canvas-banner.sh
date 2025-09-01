#!/bin/bash
# Canvas Banner Setup Script
# Age Estimator Live Plugin

echo "🎨 Setting up Canvas Banner Ad System..."

# Check if we're in the right directory
if [ ! -f "age-estimator.php" ]; then
    echo "❌ Error: Please run this script from the Age-estimator-live plugin directory"
    exit 1
fi

# Create backup directory
BACKUP_DIR="backup/canvas-banner-setup-$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "📁 Creating backup in: $BACKUP_DIR"

# Backup existing files
if [ -f "js/fullscreen-banner-ad.js" ]; then
    cp "js/fullscreen-banner-ad.js" "$BACKUP_DIR/"
    echo "✅ Backed up: js/fullscreen-banner-ad.js"
fi

if [ -f "css/fullscreen-banner-ad.css" ]; then
    cp "css/fullscreen-banner-ad.css" "$BACKUP_DIR/"
    echo "✅ Backed up: css/fullscreen-banner-ad.css"
fi

if [ -f "templates/photo-inline.php" ]; then
    cp "templates/photo-inline.php" "$BACKUP_DIR/"
    echo "✅ Backed up: templates/photo-inline.php"
fi

# Add canvas banner class to template
if [ -f "templates/photo-inline.php" ]; then
    echo "🔧 Updating template file..."
    
    # Add canvas-banner-active class to container
    sed -i.bak 's/class="age-estimator-photo-container age-estimator-photo-fullscreen/class="age-estimator-photo-container age-estimator-photo-fullscreen canvas-banner-active/' templates/photo-inline.php
    
    # Add debug status indicator after fullscreen indicator
    sed -i.bak '/<!-- Fullscreen mode indicator -->/a\
    \
    <!-- Canvas Banner Status (Debug) -->\
    <div class="canvas-banner-status" id="canvas-banner-status">\
        Canvas Banner: Ready\
    </div>' templates/photo-inline.php
    
    echo "✅ Updated template file"
else
    echo "⚠️ Template file not found, skipping template update"
fi

# Update main plugin file to enqueue canvas banner scripts
if [ -f "age-estimator.php" ]; then
    echo "🔧 Updating main plugin file..."
    
    # Check if canvas banner scripts are already enqueued
    if ! grep -q "age-estimator-canvas-banner" "age-estimator.php"; then
        # Find the line with fullscreen banner enqueue and add canvas banner before it
        sed -i.bak '/wp_enqueue_script.*fullscreen-banner/i\
        // Canvas Banner Ad Script\
        wp_enqueue_script(\
            '"'"'age-estimator-canvas-banner'"'"',\
            plugin_dir_url(__FILE__) . '"'"'js/canvas-banner-ad.js'"'"',\
            array('"'"'jquery'"'"'),\
            '"'"'2.0.0'"'"',\
            true\
        );\
        \
        // Canvas Banner Ad Styles\
        wp_enqueue_style(\
            '"'"'age-estimator-canvas-banner'"'"',\
            plugin_dir_url(__FILE__) . '"'"'css/canvas-banner-ad.css'"'"',\
            array(),\
            '"'"'2.0.0'"'"'\
        );\
' age-estimator.php
        
        echo "✅ Added canvas banner scripts to main plugin file"
    else
        echo "ℹ️ Canvas banner scripts already enqueued"
    fi
else
    echo "⚠️ Main plugin file not found, skipping script enqueue"
fi

# Create test page
echo "🧪 Creating test page..."
cat > canvas-banner-test.php << 'EOF'
<?php
/**
 * Canvas Banner Test Page
 * Test the new canvas banner system
 */

// Add admin menu item for testing
add_action('admin_menu', function() {
    add_submenu_page(
        'options-general.php',
        'Canvas Banner Test',
        'Canvas Banner Test',
        'manage_options',
        'age-estimator-canvas-test',
        function() {
            echo '<div class="wrap">';
            echo '<h1>🎨 Canvas Banner Test</h1>';
            echo '<div style="background: #f0f0f1; padding: 15px; border-radius: 5px; margin: 20px 0;">';
            echo '<h3>🔧 Browser Console Commands:</h3>';
            echo '<ul>';
            echo '<li><code>debugCanvasBanner()</code> - Check banner state</li>';
            echo '<li><code>forceShowCanvasBanner()</code> - Force show banner</li>';
            echo '<li><code>forceHideCanvasBanner()</code> - Force hide banner</li>';
            echo '</ul>';
            echo '<h3>📋 Instructions:</h3>';
            echo '<ol>';
            echo '<li>Configure your banner ad in Age Estimator settings</li>';
            echo '<li>Start the camera below</li>';
            echo '<li>Enter fullscreen mode</li>';
            echo '<li>Banner should appear as canvas overlay</li>';
            echo '</ol>';
            echo '</div>';
            echo '<div style="margin-top: 20px; border: 1px solid #ccc; padding: 20px; background: white;">';
            echo do_shortcode('[age_estimator]');
            echo '</div>';
            echo '<script>';
            echo 'document.addEventListener("DOMContentLoaded", function() {';
            echo '  document.querySelector(".age-estimator-photo-container").classList.add("age-estimator-debug");';
            echo '});';
            echo '</script>';
            echo '</div>';
        }
    );
});
?>
EOF

echo "✅ Created test page at: canvas-banner-test.php"

# Create quick README
echo "📝 Creating README..."
cat > CANVAS-BANNER-README.md << 'EOF'
# Canvas Banner Ad System - Installation Complete ✅

## What Changed

The banner ad system has been upgraded from DIV-based overlay to Canvas-based overlay for better integration with the camera view.

### New Files Created
- `js/canvas-banner-ad.js` - Canvas banner implementation
- `css/canvas-banner-ad.css` - Canvas banner styles  
- `canvas-banner-test.php` - Test page for canvas banner
- `CANVAS-BANNER-README.md` - This file

### Files Modified
- `templates/photo-inline.php` - Added canvas-banner-active class and debug status
- `age-estimator.php` - Added canvas banner script enqueues

### Backup Location
Your original files have been backed up to: `backup/canvas-banner-setup-YYYYMMDD_HHMMSS/`

## How It Works

1. **Canvas Overlay**: Banner is drawn directly onto a canvas element overlaying the camera view
2. **Same Logic**: Uses the same fullscreen + camera active detection as the original system
3. **Better Integration**: Canvas banner scales and positions perfectly with the video feed
4. **Click Support**: Maintains click-through functionality for banner links

## Testing

1. Go to **WordPress Admin > Settings > Canvas Banner Test**
2. Configure your banner in the Age Estimator settings
3. Start camera and enter fullscreen
4. Banner should appear as canvas overlay

## Browser Console Commands

- `debugCanvasBanner()` - Check current state
- `forceShowCanvasBanner()` - Force show banner
- `forceHideCanvasBanner()` - Force hide banner

## Features

✅ **Canvas-based rendering** for better performance
✅ **Automatic sizing** to match video dimensions  
✅ **Click-through support** for banner links
✅ **Debug mode** with visual indicators
✅ **Same trigger logic** (fullscreen + camera active)
✅ **Responsive design** for mobile and desktop
✅ **Cross-browser compatibility**

## Rollback

To rollback to the original system:
1. Restore files from the backup directory
2. Remove `canvas-banner-active` class from template
3. Remove canvas banner script enqueues from main plugin file

## Support

- Check browser console for debug messages
- Use debug mode by adding `age-estimator-debug` class
- Test with the canvas banner test page

---
**Canvas Banner System v2.0** - Enhanced camera integration
EOF

echo "✅ Created README: CANVAS-BANNER-README.md"

echo ""
echo "🎉 Canvas Banner Setup Complete!"
echo ""
echo "📋 Next Steps:"
echo "1. Test the canvas banner at: /wp-admin/admin.php?page=age-estimator-canvas-test"
echo "2. Configure your banner image in Age Estimator settings"
echo "3. Start camera + enter fullscreen to see canvas banner"
echo ""
echo "🔧 Debug Commands (in browser console):"
echo "   debugCanvasBanner()        - Check banner state"
echo "   forceShowCanvasBanner()    - Force show banner"
echo "   forceHideCanvasBanner()    - Force hide banner"
echo ""
echo "📁 Backup created at: $BACKUP_DIR"
echo "📖 Read: CANVAS-BANNER-README.md for full details"
echo ""