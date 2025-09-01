<?php
/**
 * Test script to verify Age Estimator Enhanced Settings Fix
 * 
 * Place this file in your WordPress root and access via browser
 * Example: http://your-site.com/test-age-estimator-fix.php
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(add_query_arg('redirect_to', $_SERVER['REQUEST_URI'])));
    exit;
}

$user_id = get_current_user_id();

echo '<h1>Age Estimator Enhanced Settings Fix Test</h1>';
echo '<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>';

echo '<h2>1. Class Check</h2>';
if (class_exists('AgeEstimatorUserSettingsEnhanced')) {
    echo '<p class="success">✅ AgeEstimatorUserSettingsEnhanced class exists</p>';
    
    $instance = AgeEstimatorUserSettingsEnhanced::get_instance();
    echo '<p class="success">✅ Can instantiate class</p>';
    
    // Check if render methods exist
    $methods = ['render_detection_fields', 'render_retail_fields', 'render_privacy_fields', 'render_notification_fields', 'render_advanced_fields'];
    foreach ($methods as $method) {
        if (method_exists($instance, $method)) {
            echo '<p class="success">✅ Method ' . $method . ' exists</p>';
        } else {
            echo '<p class="error">❌ Method ' . $method . ' missing</p>';
        }
    }
    
} else {
    echo '<p class="error">❌ AgeEstimatorUserSettingsEnhanced class not found</p>';
}

echo '<h2>2. AJAX Handlers Check</h2>';
$ajax_actions = [
    'wp_ajax_age_estimator_save_user_settings',
    'wp_ajax_age_estimator_get_user_settings',
    'wp_ajax_age_estimator_export_settings',
    'wp_ajax_age_estimator_import_settings'
];

global $wp_filter;
foreach ($ajax_actions as $action) {
    if (isset($wp_filter[$action])) {
        echo '<p class="success">✅ AJAX handler registered: ' . $action . '</p>';
    } else {
        echo '<p class="error">❌ AJAX handler missing: ' . $action . '</p>';
    }
}

echo '<h2>3. User Meta Test</h2>';

// Test saving a setting
update_user_meta($user_id, 'age_estimator_test_setting', 'test_value_' . time());
$retrieved = get_user_meta($user_id, 'age_estimator_test_setting', true);

if ($retrieved) {
    echo '<p class="success">✅ Can save/retrieve user meta</p>';
    echo '<p class="info">Test value: ' . $retrieved . '</p>';
} else {
    echo '<p class="error">❌ Cannot save/retrieve user meta</p>';
}

echo '<h2>4. Current Settings</h2>';
$settings_keys = [
    'age_estimator_face_sensitivity',
    'age_estimator_retail_mode_enabled', 
    'age_estimator_minimum_age',
    'age_estimator_enable_sounds'
];

foreach ($settings_keys as $key) {
    $value = get_user_meta($user_id, $key, true);
    if ($value !== '') {
        echo '<p class="success">✅ ' . $key . ' = ' . $value . '</p>';
    } else {
        echo '<p class="info">ℹ️ ' . $key . ' = (not set)</p>';
    }
}

echo '<h2>5. Shortcode Test</h2>';
if (shortcode_exists('age_estimator_settings_enhanced')) {
    echo '<p class="success">✅ Shortcode [age_estimator_settings_enhanced] is registered</p>';
    
    // Try to render shortcode (basic test)
    try {
        $content = do_shortcode('[age_estimator_settings_enhanced]');
        if (!empty($content)) {
            echo '<p class="success">✅ Shortcode renders content</p>';
        } else {
            echo '<p class="error">❌ Shortcode returns empty content</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Shortcode error: ' . $e->getMessage() . '</p>';
    }
} else {
    echo '<p class="error">❌ Shortcode [age_estimator_settings_enhanced] not registered</p>';
}

echo '<h2>6. JavaScript Test</h2>';
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
jQuery(document).ready(function($) {
    if (typeof ageEstimatorEnhanced !== 'undefined') {
        $('#js-test-result').html('<p class="success">✅ ageEstimatorEnhanced object available</p>');
        
        // Test AJAX call
        $.ajax({
            url: ageEstimatorEnhanced.ajaxUrl,
            type: 'POST',
            data: {
                action: 'age_estimator_get_user_settings',
                nonce: ageEstimatorEnhanced.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#ajax-test-result').html('<p class="success">✅ AJAX get_user_settings works</p>');
                } else {
                    $('#ajax-test-result').html('<p class="error">❌ AJAX get_user_settings failed: ' + response.data + '</p>');
                }
            },
            error: function() {
                $('#ajax-test-result').html('<p class="error">❌ AJAX request failed</p>');
            }
        });
    } else {
        $('#js-test-result').html('<p class="error">❌ ageEstimatorEnhanced object not found</p>');
    }
});
</script>

<div id="js-test-result"><p class="info">⏳ Testing JavaScript...</p></div>
<div id="ajax-test-result"><p class="info">⏳ Testing AJAX...</p></div>

<h2>7. Next Steps</h2>
<p>If all tests pass:</p>
<ul>
<li>The fix has been applied successfully</li>
<li>Test the settings panel: <code>[age_estimator_settings_enhanced]</code></li>
<li>Try changing and saving settings</li>
</ul>

<p>If any tests fail:</p>
<ul>
<li>Check the installation guide</li>
<li>Verify file permissions</li>
<li>Clear WordPress cache</li>
<li>Check PHP error logs</li>
</ul>

<?php
// Clean up test setting
delete_user_meta($user_id, 'age_estimator_test_setting');
?>
