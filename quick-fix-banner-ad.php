<?php
/**
 * Banner Ad Quick Fix Script
 * This script will automatically enable and configure the banner ad feature
 */

// Change to your WordPress directory
require_once('/Users/video/DevKinsta/public/age-estimation/wp-config.php');

echo "🔧 Age Estimator Banner Ad Quick Fix\n";
echo "=" . str_repeat("=", 40) . "\n\n";

// Enable banner ad
$current_setting = get_option('age_estimator_enable_banner_ad', false);
if (!$current_setting) {
    update_option('age_estimator_enable_banner_ad', true);
    echo "✅ Enabled banner ad feature\n";
} else {
    echo "ℹ️  Banner ad already enabled\n";
}

// Set default banner image if none exists
$banner_image = get_option('age_estimator_banner_ad_image', '');
if (!$banner_image) {
    // Create a placeholder banner image URL (you can change this)
    $placeholder_image = 'https://via.placeholder.com/1200x100/2196F3/ffffff?text=Your+Banner+Ad+Here';
    update_option('age_estimator_banner_ad_image', $placeholder_image);
    echo "✅ Set placeholder banner image\n";
    echo "   Image URL: $placeholder_image\n";
} else {
    echo "ℹ️  Banner image already set: $banner_image\n";
}

// Set default banner settings
$defaults = [
    'age_estimator_banner_ad_height' => 100,
    'age_estimator_banner_ad_position' => 'bottom',
    'age_estimator_banner_ad_opacity' => 0.9,
    'age_estimator_banner_ad_link' => ''
];

foreach ($defaults as $option => $default_value) {
    $current_value = get_option($option, false);
    if ($current_value === false) {
        update_option($option, $default_value);
        echo "✅ Set $option to $default_value\n";
    }
}

echo "\n🎯 Banner Ad Configuration Complete!\n";
echo "-" . str_repeat("-", 35) . "\n";
echo "Height: " . get_option('age_estimator_banner_ad_height') . "px\n";
echo "Position: " . get_option('age_estimator_banner_ad_position') . "\n";
echo "Opacity: " . (get_option('age_estimator_banner_ad_opacity') * 100) . "%\n";
echo "Image: " . get_option('age_estimator_banner_ad_image') . "\n";

echo "\n📋 Next Steps:\n";
echo "1. Replace the placeholder image with your own banner\n";
echo "2. Go to Age Estimator Settings > Display Options to customize\n";
echo "3. Test by entering fullscreen mode (double-click camera area)\n";
echo "\n💡 The banner will only show in fullscreen mode!\n";
?>
