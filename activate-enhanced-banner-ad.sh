#!/bin/bash

# Enhanced Banner Ad Activation Script
# This script activates the camera-aware banner ad functionality

echo "🎯 Activating Enhanced Camera-Aware Banner Ad..."

# Get the script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "📁 Plugin directory: $SCRIPT_DIR"

# Check if required files exist
echo "🔍 Checking required files..."

FILES=(
    "js/fullscreen-banner-ad.js"
    "css/fullscreen-banner-ad.css"
    "includes/banner-ad-assets.php"
    "templates/photo-inline.php"
)

for file in "${FILES[@]}"; do
    if [[ -f "$SCRIPT_DIR/$file" ]]; then
        echo "✅ Found: $file"
    else
        echo "❌ Missing: $file"
        exit 1
    fi
done

# Check if banner ad assets loader exists
if [[ -f "$SCRIPT_DIR/includes/banner-ad-assets.php" ]]; then
    echo "✅ Banner ad assets loader found"
else
    echo "⚠️ Creating banner ad assets loader..."
    
    # Create the assets loader if it doesn't exist
    cat > "$SCRIPT_DIR/includes/banner-ad-assets.php" << 'EOF'
<?php
/**
 * Banner Ad Assets Loader
 * Age Estimator Live Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue banner ad assets only on pages with age estimator shortcode
add_action('wp_enqueue_scripts', 'age_estimator_enqueue_banner_ad_assets');

function age_estimator_enqueue_banner_ad_assets() {
    global $post;
    
    // Only load on pages with the shortcode
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'age_estimator')) {
        
        // Check if banner ad is enabled
        if (get_option('age_estimator_enable_banner_ad', false)) {
            
            // Enqueue CSS
            wp_enqueue_style(
                'age-estimator-banner-ad',
                plugin_dir_url(__FILE__) . '../css/fullscreen-banner-ad.css',
                array(),
                filemtime(plugin_dir_path(__FILE__) . '../css/fullscreen-banner-ad.css')
            );
            
            // Enqueue JavaScript
            wp_enqueue_script(
                'age-estimator-banner-ad',
                plugin_dir_url(__FILE__) . '../js/fullscreen-banner-ad.js',
                array('jquery'),
                filemtime(plugin_dir_path(__FILE__) . '../js/fullscreen-banner-ad.js'),
                true
            );
            
            // Add banner settings to JavaScript
            wp_localize_script('age-estimator-banner-ad', 'ageEstimatorBannerParams', array(
                'enabled' => get_option('age_estimator_enable_banner_ad', false),
                'image' => get_option('age_estimator_banner_ad_image', ''),
                'link' => get_option('age_estimator_banner_ad_link', ''),
                'height' => get_option('age_estimator_banner_ad_height', 100),
                'position' => get_option('age_estimator_banner_ad_position', 'bottom'),
                'opacity' => get_option('age_estimator_banner_ad_opacity', 0.9),
                'debug' => defined('WP_DEBUG') && WP_DEBUG
            ));
        }
    }
}

// Add body class for banner ad pages
add_filter('body_class', 'age_estimator_banner_ad_body_class');

function age_estimator_banner_ad_body_class($classes) {
    global $post;
    
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'age_estimator')) {
        if (get_option('age_estimator_enable_banner_ad', false)) {
            $classes[] = 'age-estimator-banner-enabled';
        }
    }
    
    return $classes;
}

echo "✅ Banner ad assets loader created\n";
EOF
fi

# Check main plugin file for assets inclusion
MAIN_PLUGIN="$SCRIPT_DIR/age-estimator.php"

if [[ -f "$MAIN_PLUGIN" ]]; then
    if grep -q "banner-ad-assets.php" "$MAIN_PLUGIN"; then
        echo "✅ Banner ad assets already included in main plugin"
    else
        echo "⚠️ Adding banner ad assets inclusion to main plugin..."
        
        # Create backup
        cp "$MAIN_PLUGIN" "$MAIN_PLUGIN.backup.$(date +%Y%m%d_%H%M%S)"
        
        # Add inclusion line near other includes
        sed -i '/load_includes/a\
        // Load banner ad assets\
        $banner_assets_file = AGE_ESTIMATOR_PATH . '"'"'includes/banner-ad-assets.php'"'"';\
        if (file_exists($banner_assets_file)) {\
            require_once $banner_assets_file;\
        }' "$MAIN_PLUGIN"
        
        echo "✅ Banner ad assets inclusion added"
    fi
else
    echo "❌ Main plugin file not found: $MAIN_PLUGIN"
fi

# Clear any WordPress caches
echo "🗑️ Clearing caches..."

# Try to clear common cache plugins
if command -v wp &> /dev/null; then
    wp cache flush 2>/dev/null || true
    wp rewrite flush 2>/dev/null || true
fi

echo ""
echo "🎉 Enhanced Camera-Aware Banner Ad Activation Complete!"
echo ""
echo "📋 Next Steps:"
echo "1. Go to WordPress Admin → Age Estimator → Settings → Display Options"
echo "2. Enable 'Show banner ad in fullscreen mode'"
echo "3. Upload your banner image and configure settings"
echo "4. Test on your age estimator page:"
echo "   - Start camera → Enter fullscreen → Banner should appear"
echo "   - Stop camera OR exit fullscreen → Banner should disappear"
echo ""
echo "🐛 Debug Tools:"
echo "   - Open: $SCRIPT_DIR/banner-debug-test.html"
echo "   - Use browser console commands for troubleshooting"
echo ""
echo "📚 Documentation: See BANNER-AD-README.md for full details"
echo ""
