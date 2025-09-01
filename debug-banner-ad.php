<?php
/**
 * Banner Ad Debug Script
 * Run this to check the current banner ad configuration
 */

// Change to your WordPress directory
require_once('/Users/video/DevKinsta/public/age-estimation/wp-config.php');

echo "🎯 Age Estimator Banner Ad Debug Report\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Check if banner ad is enabled
$banner_enabled = get_option('age_estimator_enable_banner_ad', false);
echo "Banner Ad Enabled: " . ($banner_enabled ? 'YES' : 'NO') . "\n";

// Check banner image
$banner_image = get_option('age_estimator_banner_ad_image', '');
echo "Banner Image URL: " . ($banner_image ? $banner_image : 'NOT SET') . "\n";

// Check banner link
$banner_link = get_option('age_estimator_banner_ad_link', '');
echo "Banner Link URL: " . ($banner_link ? $banner_link : 'NOT SET') . "\n";

// Check banner dimensions and position
$banner_height = get_option('age_estimator_banner_ad_height', 100);
$banner_position = get_option('age_estimator_banner_ad_position', 'bottom');
$banner_opacity = get_option('age_estimator_banner_ad_opacity', 0.9);

echo "Banner Height: " . $banner_height . "px\n";
echo "Banner Position: " . $banner_position . "\n";
echo "Banner Opacity: " . ($banner_opacity * 100) . "%\n\n";

// Check if banner assets are being loaded
echo "Checking file existence:\n";
$plugin_path = dirname(__FILE__) . '/';
$css_file = $plugin_path . 'css/fullscreen-banner-ad.css';
$js_file = $plugin_path . 'js/fullscreen-banner-ad.js';
$assets_file = $plugin_path . 'includes/banner-ad-assets.php';

echo "CSS File: " . (file_exists($css_file) ? 'EXISTS' : 'MISSING') . "\n";
echo "JS File: " . (file_exists($js_file) ? 'EXISTS' : 'MISSING') . "\n";
echo "Assets File: " . (file_exists($assets_file) ? 'EXISTS' : 'MISSING') . "\n\n";

// Recommendations
echo "🔧 RECOMMENDATIONS:\n";
echo "-" . str_repeat("-", 30) . "\n";

if (!$banner_enabled) {
    echo "❌ Enable the banner ad in Age Estimator Settings > Display Options\n";
}

if (!$banner_image) {
    echo "❌ Upload a banner image (recommended: 1200x100px)\n";
}

if ($banner_enabled && $banner_image) {
    echo "✅ Banner appears to be configured!\n";
    echo "💡 Test by double-clicking the camera view to enter fullscreen\n";
}

echo "\n🎯 Quick Fix Steps:\n";
echo "1. Go to Age Estimator Settings > Display Options\n";
echo "2. Check 'Show banner ad in fullscreen mode'\n";
echo "3. Upload a banner image\n";
echo "4. Save settings\n";
echo "5. Test by entering fullscreen mode\n";

// Auto-enable banner for testing (commented out for safety)
/*
if (!$banner_enabled) {
    update_option('age_estimator_enable_banner_ad', true);
    echo "\n✅ Auto-enabled banner ad for testing!\n";
}
*/
?>
