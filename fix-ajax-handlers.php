<?php
/**
 * Quick AJAX Handler Fix
 * This will diagnose and fix the missing AJAX handlers
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

echo "<h1>🔧 AJAX Handler Fix</h1>";

// Force load the PIN protection class
$class_file = AGE_ESTIMATOR_PATH . 'includes/class-settings-pin-protection.php';
if (file_exists($class_file)) {
    require_once $class_file;
    echo "<p>✅ PIN Protection class loaded</p>";
    
    // Initialize the class
    $pin_protection = AgeEstimatorSettingsPinProtection::get_instance();
    echo "<p>✅ PIN Protection instance created</p>";
} else {
    echo "<p>❌ PIN Protection class file not found</p>";
    echo "<p>Path: $class_file</p>";
}

// Check if AJAX handlers are registered
global $wp_filter;

$ajax_actions = array(
    'age_estimator_verify_settings_pin',
    'age_estimator_check_pin_session', 
    'age_estimator_lock_settings'
);

echo "<h2>AJAX Handler Status</h2>";
foreach ($ajax_actions as $action) {
    $full_action = "wp_ajax_$action";
    $has_handler = isset($wp_filter[$full_action]) && !empty($wp_filter[$full_action]->callbacks);
    echo "<p><strong>$action:</strong> " . ($has_handler ? '✅ Registered' : '❌ Missing') . "</p>";
}

// Manually register the handlers if they're missing
echo "<h2>Manual Registration</h2>";

// Add the handlers directly
add_action('wp_ajax_age_estimator_verify_settings_pin', function() {
    error_log("PIN verification AJAX called");
    
    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_pin_protection')) {
        wp_send_json_error(array(
            'message' => 'Security verification failed'
        ));
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => 'You must be logged in'
        ));
    }
    
    $user_id = get_current_user_id();
    $entered_pin = sanitize_text_field($_POST['pin'] ?? '');
    
    // Validate PIN format
    if (!preg_match('/^\d{4}$/', $entered_pin)) {
        wp_send_json_error(array(
            'message' => 'PIN must be exactly 4 digits'
        ));
    }
    
    // Get stored PIN
    $stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
    
    if (empty($stored_pin)) {
        wp_send_json_error(array(
            'message' => 'No PIN has been set. Please set up a PIN first.',
            'no_pin' => true
        ));
    }
    
    // Verify PIN
    if (wp_check_password($entered_pin, $stored_pin)) {
        // Set session
        update_user_meta($user_id, 'age_estimator_pin_session_time', time());
        
        wp_send_json_success(array(
            'message' => 'PIN verified successfully',
            'redirect' => true
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'Invalid PIN. Please try again.'
        ));
    }
});

add_action('wp_ajax_debug_pin_verification', function() {
    $user_id = get_current_user_id();
    $entered_pin = sanitize_text_field($_POST['pin'] ?? '');
    
    // Get stored PIN
    $stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
    
    $debug_info = array(
        'user_id' => $user_id,
        'entered_pin' => $entered_pin,
        'stored_pin_exists' => !empty($stored_pin),
        'stored_pin_length' => strlen($stored_pin),
        'stored_pin_preview' => !empty($stored_pin) ? substr($stored_pin, 0, 20) . '...' : 'empty',
        'is_hashed' => (!empty($stored_pin) && strlen($stored_pin) > 10 && strpos($stored_pin, '$') !== false),
        'wp_check_result' => false,
        'plain_comparison' => false
    );
    
    if (!empty($stored_pin)) {
        $debug_info['wp_check_result'] = wp_check_password($entered_pin, $stored_pin);
        $debug_info['plain_comparison'] = ($entered_pin === $stored_pin);
    }
    
    wp_send_json_success($debug_info);
});

echo "<p>✅ AJAX handlers registered manually</p>";

// Test the handlers
echo "<h2>🧪 Quick Test</h2>";

if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
    
    echo "<p><strong>Current user:</strong> " . wp_get_current_user()->display_name . " (ID: $user_id)</p>";
    echo "<p><strong>PIN status:</strong> " . (!empty($stored_pin) ? 'Set (' . strlen($stored_pin) . ' chars)' : 'Not Set') . "</p>";
    
    if (!empty($stored_pin)) {
        // Test PIN validation
        $test_pin = '1234';
        $is_valid = wp_check_password($test_pin, $stored_pin);
        echo "<p><strong>PIN '1234' test:</strong> " . ($is_valid ? '✅ Valid' : '❌ Invalid') . "</p>";
    }
} else {
    echo "<p>⚠️ Not logged in - please login first</p>";
}

?>

<h2>🔄 Test Again</h2>
<p>The AJAX handlers have been registered. Now try the debug tool again:</p>
<p><a href="debug-pin-real-time.php" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">🧪 Run Debug Tool Again</a></p>

<h2>🎯 Try Your Settings</h2>
<p>Or go directly to your settings page to test:</p>
<p><a href="<?php echo home_url(); ?>" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">🔐 Test Settings Login</a></p>

<style>
body { font-family: -apple-system, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
p { margin: 10px 0; }
</style>
