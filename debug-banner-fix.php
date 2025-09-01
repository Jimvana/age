<?php
/**
 * Debug Banner Fix - Check and Fix Banner Loading Issues
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Banner Debug & Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { background: #f0f0f1; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        pre { background: white; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
        .fix-button { background: #2271b1; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; margin: 5px; }
        .fix-button:hover { background: #1e5d8c; }
    </style>
</head>
<body>
    <h1>🎯 Canvas Banner Debug & Fix Tool</h1>
    
    <?php
    // Get current banner settings
    $banner_enabled = get_option('age_estimator_enable_banner_ad', false);
    $banner_image = get_option('age_estimator_banner_ad_image', '');
    $banner_link = get_option('age_estimator_banner_ad_link', '');
    $banner_height = get_option('age_estimator_banner_ad_height', 100);
    $banner_position = get_option('age_estimator_banner_ad_position', 'bottom');
    $banner_opacity = get_option('age_estimator_banner_ad_opacity', 0.9);
    
    echo '<div class="debug-section">';
    echo '<h2>📋 Current Banner Settings</h2>';
    echo '<p><strong>Enabled:</strong> ' . ($banner_enabled ? '✅ Yes' : '❌ No') . '</p>';
    echo '<p><strong>Image URL:</strong> ' . (!empty($banner_image) ? '✅ Set' : '❌ Not set') . '</p>';
    if (!empty($banner_image)) {
        echo '<p><strong>Image:</strong> <code>' . esc_html($banner_image) . '</code></p>';
    }
    echo '<p><strong>Link URL:</strong> ' . (!empty($banner_link) ? esc_html($banner_link) : 'None') . '</p>';
    echo '<p><strong>Height:</strong> ' . $banner_height . 'px</p>';
    echo '<p><strong>Position:</strong> ' . $banner_position . '</p>';
    echo '<p><strong>Opacity:</strong> ' . $banner_opacity . '</p>';
    echo '</div>';
    
    // Test image accessibility
    if (!empty($banner_image)) {
        echo '<div class="debug-section">';
        echo '<h2>🖼️ Image Testing</h2>';
        
        // Test if image is accessible
        $response = wp_remote_head($banner_image);
        if (is_wp_error($response)) {
            echo '<div class="error">❌ <strong>Image URL Error:</strong> ' . $response->get_error_message() . '</div>';
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            $content_type = wp_remote_retrieve_header($response, 'content-type');
            
            if ($response_code === 200) {
                echo '<div class="success">✅ <strong>Image Accessible:</strong> HTTP ' . $response_code . '</div>';
                echo '<p><strong>Content Type:</strong> ' . $content_type . '</p>';
                
                // Try to get image dimensions using getimagesize
                $image_info = @getimagesize($banner_image);
                if ($image_info) {
                    echo '<p><strong>Image Dimensions:</strong> ' . $image_info[0] . ' x ' . $image_info[1] . ' pixels</p>';
                    echo '<p><strong>Image Type:</strong> ' . $image_info['mime'] . '</p>';
                } else {
                    echo '<div class="warning">⚠️ <strong>Warning:</strong> Could not read image dimensions. This might cause loading issues.</div>';
                }
                
                // Show image preview
                echo '<div style="margin-top: 15px;">';
                echo '<p><strong>Image Preview:</strong></p>';
                echo '<img src="' . esc_url($banner_image) . '" style="max-width: 400px; max-height: 200px; border: 1px solid #ddd;" onerror="this.style.display=\'none\'; this.nextSibling.style.display=\'block\';" />';
                echo '<div style="display: none; color: red; font-weight: bold;">❌ Image failed to load in browser</div>';
                echo '</div>';
            } else {
                echo '<div class="error">❌ <strong>Image Not Accessible:</strong> HTTP ' . $response_code . '</div>';
            }
        }
        echo '</div>';
    }
    
    // Check WordPress generated banner element
    echo '<div class="debug-section">';
    echo '<h2>🔍 WordPress Banner Element Debug</h2>';
    echo '<p>This simulates what the Canvas Banner JavaScript sees:</p>';
    
    if ($banner_enabled && !empty($banner_image)) {
        echo '<div id="age-estimator-banner-ad" class="age-estimator-banner-ad" style="display: none;">';
        if (!empty($banner_link)) {
            echo '<a href="' . esc_url($banner_link) . '" target="_blank" rel="noopener noreferrer" class="age-estimator-banner-link">';
        }
        echo '<img src="' . esc_url($banner_image) . '" ';
        echo 'alt="Advertisement" ';
        echo 'class="age-estimator-banner-image" ';
        echo 'data-height="' . esc_attr($banner_height) . '" ';
        echo 'data-position="' . esc_attr($banner_position) . '" ';
        echo 'data-opacity="' . esc_attr($banner_opacity) . '" />';
        if (!empty($banner_link)) {
            echo '</a>';
        }
        echo '</div>';
        
        echo '<div class="success">✅ <strong>Banner Element Generated Successfully</strong></div>';
        echo '<p>The Canvas Banner script should be able to read these settings.</p>';
    } else {
        echo '<div class="error">❌ <strong>No Banner Element:</strong> Banner is disabled or image URL is missing</div>';
    }
    echo '</div>';
    
    // JavaScript debug section
    echo '<div class="debug-section">';
    echo '<h2>🧪 Live JavaScript Test</h2>';
    echo '<p>Click the button below to test the Canvas Banner system in real-time:</p>';
    echo '<button class="fix-button" onclick="testCanvasBanner()">🔍 Test Canvas Banner Now</button>';
    echo '<button class="fix-button" onclick="forceReloadBanner()">🔄 Force Reload Banner</button>';
    echo '<button class="fix-button" onclick="showBannerDebug()">📊 Show Debug Info</button>';
    echo '<div id="js-test-results" style="margin-top: 15px;"></div>';
    echo '</div>';
    
    // Quick fixes section
    echo '<div class="debug-section">';
    echo '<h2>🔧 Quick Fixes</h2>';
    
    if (!$banner_enabled) {
        echo '<div class="warning">⚠️ <strong>Issue:</strong> Banner ad is disabled</div>';
        echo '<form method="post" style="display: inline;">';
        echo wp_nonce_field('banner_debug_action', 'banner_debug_nonce');
        echo '<input type="hidden" name="action" value="enable_banner" />';
        echo '<button type="submit" class="fix-button">✅ Enable Banner Ad</button>';
        echo '</form>';
    }
    
    if (empty($banner_image)) {
        echo '<div class="warning">⚠️ <strong>Issue:</strong> No banner image URL set</div>';
        echo '<p>Go to <a href="' . admin_url('admin.php?page=age-estimator-settings') . '">Age Estimator Settings</a> and upload a banner image.</p>';
    }
    
    if ($banner_enabled && !empty($banner_image)) {
        echo '<div class="success">✅ <strong>Configuration looks good!</strong> The issue might be with the JavaScript loading.</div>';
        echo '<form method="post" style="display: inline;">';
        echo wp_nonce_field('banner_debug_action', 'banner_debug_nonce');
        echo '<input type="hidden" name="action" value="refresh_banner_cache" />';
        echo '<button type="submit" class="fix-button">🔄 Refresh Banner Cache</button>';
        echo '</form>';
    }
    echo '</div>';
    
    // Handle form submissions
    if (isset($_POST['action']) && wp_verify_nonce($_POST['banner_debug_nonce'], 'banner_debug_action')) {
        echo '<div class="debug-section">';
        switch ($_POST['action']) {
            case 'enable_banner':
                update_option('age_estimator_enable_banner_ad', true);
                echo '<div class="success">✅ <strong>Banner ad has been enabled!</strong> Refresh the page to see updated status.</div>';
                break;
            case 'refresh_banner_cache':
                // Clear any caches and refresh
                if (function_exists('wp_cache_flush')) {
                    wp_cache_flush();
                }
                echo '<div class="success">✅ <strong>Banner cache refreshed!</strong> Try testing the banner again.</div>';
                break;
        }
        echo '</div>';
    }
    ?>
    
    <div class="debug-section">
        <h2>📝 Instructions to Fix</h2>
        <ol>
            <li><strong>Ensure banner is enabled:</strong> Check the "Enable Banner Ad" checkbox in settings</li>
            <li><strong>Upload a valid image:</strong> Use the "Upload Image" button in settings to select a PNG or JPG file</li>
            <li><strong>Check image dimensions:</strong> Recommended size is 1200x100 pixels for best results</li>
            <li><strong>Test in fullscreen:</strong> The banner only shows when camera is active AND in fullscreen mode</li>
            <li><strong>Browser console:</strong> Use <code>debugCanvasBanner()</code> and <code>reloadCanvasBanner()</code> to debug</li>
        </ol>
        
        <h3>🎯 Steps to Test:</h3>
        <ol>
            <li>Go to your Age Estimator page</li>
            <li>Open browser developer tools (F12)</li>
            <li>Start the camera</li>
            <li>Enter fullscreen mode (double-click camera or use fullscreen button)</li>
            <li>In console, type: <code>reloadCanvasBanner()</code></li>
            <li>If still showing test banner, check image URL accessibility above</li>
        </ol>
    </div>
    
    <script>
    function testCanvasBanner() {
        const results = document.getElementById('js-test-results');
        results.innerHTML = '<p>🔍 Testing Canvas Banner...</p>';
        
        // Check if banner element exists
        const bannerElement = document.getElementById('age-estimator-banner-ad');
        if (!bannerElement) {
            results.innerHTML += '<div class="error">❌ Banner element not found on page</div>';
            return;
        }
        
        // Check image element
        const bannerImage = bannerElement.querySelector('.age-estimator-banner-image');
        if (!bannerImage) {
            results.innerHTML += '<div class="error">❌ Banner image element not found</div>';
            return;
        }
        
        results.innerHTML += '<div class="success">✅ Banner elements found</div>';
        results.innerHTML += '<p><strong>Image URL:</strong> ' + bannerImage.src + '</p>';
        results.innerHTML += '<p><strong>Image Data:</strong> height=' + bannerImage.dataset.height + ', position=' + bannerImage.dataset.position + ', opacity=' + bannerImage.dataset.opacity + '</p>';
        
        // Test image loading
        const testImg = new Image();
        testImg.onload = function() {
            results.innerHTML += '<div class="success">✅ Image loads successfully (' + this.width + 'x' + this.height + ')</div>';
        };
        testImg.onerror = function() {
            results.innerHTML += '<div class="error">❌ Image failed to load - check URL accessibility</div>';
        };
        testImg.src = bannerImage.src;
    }
    
    function forceReloadBanner() {
        const results = document.getElementById('js-test-results');
        results.innerHTML = '<p>🔄 Attempting to reload banner...</p>';
        
        if (typeof window.reloadCanvasBanner === 'function') {
            window.reloadCanvasBanner();
            results.innerHTML += '<div class="success">✅ Reload command sent to Canvas Banner</div>';
        } else {
            results.innerHTML += '<div class="warning">⚠️ Canvas Banner JavaScript not loaded on this page</div>';
        }
    }
    
    function showBannerDebug() {
        const results = document.getElementById('js-test-results');
        results.innerHTML = '<p>📊 Getting debug information...</p>';
        
        if (typeof window.debugCanvasBanner === 'function') {
            const debug = window.debugCanvasBanner();
            results.innerHTML += '<div class="success">✅ Debug info retrieved</div>';
            results.innerHTML += '<pre>' + JSON.stringify(debug, null, 2) + '</pre>';
        } else {
            results.innerHTML += '<div class="warning">⚠️ Canvas Banner JavaScript not loaded on this page</div>';
        }
    }
    </script>
</body>
</html>
