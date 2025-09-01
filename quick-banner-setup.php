<?php
/**
 * Quick Banner Ad Activation and Setup
 * Run this file once to enable and configure the banner ad feature
 */

// WordPress environment check
if (!defined('ABSPATH')) {
    // Load WordPress if running standalone
    require_once('../../../wp-config.php');
}

echo "<h2>🎯 Banner Ad Quick Setup</h2>";

// 1. Enable banner ad feature
echo "<h3>1. Enabling Banner Ad Feature...</h3>";
update_option('age_estimator_enable_banner_ad', true);
echo "✅ Banner ad enabled<br>";

// 2. Set default banner image (you can change this)
echo "<h3>2. Setting Default Banner Settings...</h3>";
$banner_image_url = 'https://via.placeholder.com/1200x100/2196F3/FFFFFF?text=SAMPLE+BANNER+AD';
update_option('age_estimator_banner_ad_image', $banner_image_url);
echo "✅ Banner image set: <a href='{$banner_image_url}' target='_blank'>{$banner_image_url}</a><br>";

// 3. Set other banner settings
update_option('age_estimator_banner_ad_height', 100);
update_option('age_estimator_banner_ad_position', 'bottom');
update_option('age_estimator_banner_ad_opacity', 0.9);
update_option('age_estimator_banner_ad_link', 'https://example.com'); // Optional click URL
echo "✅ Banner height: 100px<br>";
echo "✅ Banner position: bottom<br>";
echo "✅ Banner opacity: 0.9<br>";
echo "✅ Banner link: https://example.com<br>";

// 4. Check if template has banner element
echo "<h3>3. Checking Template...</h3>";
$template_file = plugin_dir_path(__FILE__) . 'templates/photo-inline.php';
if (file_exists($template_file)) {
    $template_content = file_get_contents($template_file);
    if (strpos($template_content, 'age-estimator-banner-ad') !== false) {
        echo "✅ Template contains banner element<br>";
    } else {
        echo "❌ Template missing banner element<br>";
    }
} else {
    echo "❌ Template file not found<br>";
}

// 5. Check if assets files exist
echo "<h3>4. Checking Asset Files...</h3>";
$files_to_check = [
    'js/fullscreen-banner-ad.js' => 'JavaScript file',
    'css/fullscreen-banner-ad.css' => 'CSS file', 
    'includes/banner-ad-assets.php' => 'Assets loader'
];

foreach ($files_to_check as $file => $description) {
    $file_path = plugin_dir_path(__FILE__) . $file;
    if (file_exists($file_path)) {
        echo "✅ {$description} exists<br>";
    } else {
        echo "❌ {$description} missing: {$file}<br>";
    }
}

// 6. Clear any caches
echo "<h3>5. Clearing Caches...</h3>";
// Clear WordPress object cache
wp_cache_flush();
echo "✅ WordPress cache cleared<br>";

// 7. Test banner settings
echo "<h3>6. Banner Settings Test...</h3>";
$enabled = get_option('age_estimator_enable_banner_ad', false);
$image = get_option('age_estimator_banner_ad_image', '');
$height = get_option('age_estimator_banner_ad_height', 100);
$position = get_option('age_estimator_banner_ad_position', 'bottom');

echo "Banner enabled: " . ($enabled ? '✅ YES' : '❌ NO') . "<br>";
echo "Banner image: " . ($image ? "✅ {$image}" : '❌ Not set') . "<br>";
echo "Banner height: {$height}px<br>";
echo "Banner position: {$position}<br>";

// 8. Generate test JavaScript for browser console
echo "<h3>7. Browser Console Test Code</h3>";
echo "<p>Copy this code and paste it into your browser console on the age estimator page:</p>";
echo "<textarea style='width: 100%; height: 150px; font-family: monospace;' readonly>";
echo "// Banner Ad Test Code
console.log('🎯 Testing Banner Ad Setup...');

// Check if settings are loaded
console.log('Banner Settings:', {
    enabled: " . ($enabled ? 'true' : 'false') . ",
    image: '{$image}',
    height: {$height},
    position: '{$position}'
});

// Force show banner for testing
setTimeout(() => {
    const banner = document.getElementById('age-estimator-banner-ad');
    if (banner) {
        console.log('✅ Banner element found');
        banner.style.display = 'block';
        banner.style.visibility = 'visible';
        banner.style.position = 'fixed';
        banner.style.zIndex = '99999';
        banner.style.left = '0';
        banner.style.right = '0';
        banner.style.bottom = '0';
        banner.style.height = '{$height}px';
        banner.style.opacity = '{$position}';
        banner.classList.add('force-banner-visible');
        console.log('🎯 Banner forced visible for 10 seconds');
        
        setTimeout(() => {
            banner.style.display = 'none';
            console.log('⏰ Test banner hidden');
        }, 10000);
    } else {
        console.error('❌ Banner element not found in DOM');
        console.log('💡 Banner might not be enabled in WordPress settings');
    }
}, 1000);";
echo "</textarea>";

// 9. Next steps
echo "<h3>8. Next Steps</h3>";
echo "<ol>";
echo "<li><strong>Go to your Age Estimator page</strong></li>";
echo "<li><strong>Open browser developer tools</strong> (F12)</li>";
echo "<li><strong>Paste the test code above</strong> into the console</li>";
echo "<li><strong>Start the camera and enter fullscreen</strong></li>";
echo "<li><strong>The banner should appear</strong> at the bottom</li>";
echo "</ol>";

// 10. Troubleshooting
echo "<h3>9. Troubleshooting</h3>";
echo "<p><strong>If banner still doesn't appear:</strong></p>";
echo "<ul>";
echo "<li>Check browser console for JavaScript errors</li>";
echo "<li>Verify you're starting the camera AND entering fullscreen</li>";
echo "<li>Try the force banner test code above</li>";
echo "<li>Check if template has been updated with banner element</li>";
echo "</ul>";

echo "<br><hr><br>";
echo "<p><strong>✅ Banner Ad Setup Complete!</strong></p>";
echo "<p>The banner should now appear when both camera is active AND fullscreen mode is on.</p>";
?>
