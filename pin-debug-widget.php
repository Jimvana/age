<?php
/**
 * PIN Fields Debug and Test Script
 * Use this to debug PIN field visibility issues
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add debug information to admin dashboard
add_action('wp_dashboard_setup', function() {
    if (current_user_can('manage_options')) {
        wp_add_dashboard_widget(
            'age_estimator_pin_debug',
            'Age Estimator PIN Debug',
            'age_estimator_pin_debug_widget'
        );
    }
});

function age_estimator_pin_debug_widget() {
    $user_id = get_current_user_id();
    
    // Check if PIN fix file is loaded
    $pin_fix_loaded = file_exists(AGE_ESTIMATOR_PATH . 'pin-fields-fix.php');
    
    // Check if enhanced settings class exists
    $enhanced_class_exists = class_exists('AgeEstimatorUserSettingsEnhanced');
    
    // Check if user has a PIN set
    $user_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
    $has_pin = !empty($user_pin);
    
    // Check retail mode setting
    $retail_mode = get_user_meta($user_id, 'age_estimator_retail_mode_enabled', true);
    
    ?>
    <div style="padding: 10px; font-family: monospace;">
        <h4>🔧 PIN Debug Information</h4>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 5px; border: 1px solid #ddd;"><strong>PIN Fix File:</strong></td>
                <td style="padding: 5px; border: 1px solid #ddd; background: <?php echo $pin_fix_loaded ? '#d4edda' : '#f8d7da'; ?>">
                    <?php echo $pin_fix_loaded ? '✅ Loaded' : '❌ Missing'; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 5px; border: 1px solid #ddd;"><strong>Enhanced Settings:</strong></td>
                <td style="padding: 5px; border: 1px solid #ddd; background: <?php echo $enhanced_class_exists ? '#d4edda' : '#f8d7da'; ?>">
                    <?php echo $enhanced_class_exists ? '✅ Available' : '❌ Missing'; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 5px; border: 1px solid #ddd;"><strong>Current User PIN:</strong></td>
                <td style="padding: 5px; border: 1px solid #ddd; background: <?php echo $has_pin ? '#d4edda' : '#fff3cd'; ?>">
                    <?php echo $has_pin ? '✅ Set' : '⚠️ Not Set'; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 5px; border: 1px solid #ddd;"><strong>Retail Mode:</strong></td>
                <td style="padding: 5px; border: 1px solid #ddd; background: <?php echo $retail_mode ? '#d4edda' : '#fff3cd'; ?>">
                    <?php echo $retail_mode ? '✅ Enabled' : '⚠️ Disabled'; ?>
                </td>
            </tr>
        </table>
        
        <div style="margin-top: 15px; padding: 10px; background: #e7f3ff; border-left: 4px solid #007cba;">
            <strong>🔍 Testing Steps:</strong><br>
            1. Go to your settings page with <code>[age_estimator_settings_enhanced]</code><br>
            2. Click on "Retail Mode" in the sidebar<br>
            3. Check "Enable Retail Mode" checkbox<br>
            4. PIN fields should appear immediately<br>
            5. If not, open browser console and run: <code>debugFormData()</code>
        </div>
        
        <div style="margin-top: 10px;">
            <button type="button" onclick="testPinSave()" style="background: #007cba; color: white; padding: 8px 15px; border: none; cursor: pointer;">
                🧪 Test PIN Save
            </button>
        </div>
    </div>
    
    <script>
    function testPinSave() {
        // Test AJAX endpoint
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'debug_pin_save',
                nonce: '<?php echo wp_create_nonce('debug_pin_save'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ PIN Test PASSED!\n\nResults:\n' + JSON.stringify(response.data, null, 2));
                } else {
                    alert('❌ PIN Test FAILED!\n\nError: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('❌ AJAX Error: ' + error);
            }
        });
    }
    </script>
    <?php
}

// Add AJAX handler for PIN test
add_action('wp_ajax_debug_pin_save', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'debug_pin_save')) {
        wp_send_json_error('Invalid nonce');
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $user_id = get_current_user_id();
    
    // Test PIN saving
    $test_pin = '9999';
    $hashed = wp_hash_password($test_pin);
    
    // Save test PIN
    $save_result = update_user_meta($user_id, 'age_estimator_retail_pin', $hashed);
    
    // Retrieve and verify
    $retrieved = get_user_meta($user_id, 'age_estimator_retail_pin', true);
    $verify_result = wp_check_password($test_pin, $retrieved);
    
    wp_send_json_success(array(
        'test_pin' => $test_pin,
        'save_result' => $save_result,
        'verify_result' => $verify_result,
        'hash_length' => strlen($retrieved),
        'message' => $verify_result ? 'PIN save/verify working correctly!' : 'PIN verification failed!'
    ));
});
