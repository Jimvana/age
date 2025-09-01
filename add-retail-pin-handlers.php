<?php
/**
 * Quick Retail PIN Fix
 * Add this to fix the retail mode PIN validation
 */

// Make sure this is running in WordPress context
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

// Add the missing retail PIN validation handler
add_action('wp_ajax_retail_pin_validate', function() {
    error_log('Retail PIN validation called');
    
    $user_id = get_current_user_id();
    $entered_pin = sanitize_text_field($_POST['pin'] ?? '');
    
    // Get stored PIN
    $stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
    
    if (empty($stored_pin)) {
        wp_send_json_error(array(
            'message' => 'No PIN has been set'
        ));
    }
    
    // Validate PIN
    if (wp_check_password($entered_pin, $stored_pin)) {
        // Set session
        update_user_meta($user_id, 'age_estimator_pin_session_time', time());
        
        wp_send_json_success(array(
            'message' => 'PIN validated successfully'
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'Invalid PIN'
        ));
    }
});

// Also add handlers for possible alternative action names
add_action('wp_ajax_age_estimator_retail_pin_validate', function() {
    error_log('Alternative retail PIN validation called');
    
    $user_id = get_current_user_id();
    $entered_pin = sanitize_text_field($_POST['pin'] ?? '');
    
    // Get stored PIN
    $stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
    
    if (empty($stored_pin)) {
        wp_send_json_error(array(
            'message' => 'No PIN has been set'
        ));
    }
    
    // Validate PIN
    if (wp_check_password($entered_pin, $stored_pin)) {
        // Set session
        update_user_meta($user_id, 'age_estimator_pin_session_time', time());
        
        wp_send_json_success(array(
            'message' => 'PIN validated successfully'
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'Invalid PIN'
        ));
    }
});

// Add handler for settings access (catch-all)
add_action('wp_ajax_settings_access_validate', function() {
    error_log('Settings access validation called');
    
    $user_id = get_current_user_id();
    $entered_pin = sanitize_text_field($_POST['pin'] ?? '');
    
    // Get stored PIN
    $stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
    
    if (empty($stored_pin)) {
        wp_send_json_error(array(
            'message' => 'No PIN has been set'
        ));
    }
    
    // Validate PIN
    if (wp_check_password($entered_pin, $stored_pin)) {
        // Set session
        update_user_meta($user_id, 'age_estimator_pin_session_time', time());
        
        wp_send_json_success(array(
            'message' => 'PIN validated successfully'
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'Invalid PIN'
        ));
    }
});

echo "<h1>✅ Additional PIN handlers registered</h1>";
echo "<p>The following AJAX handlers have been added:</p>";
echo "<ul>";
echo "<li>wp_ajax_retail_pin_validate</li>";
echo "<li>wp_ajax_age_estimator_retail_pin_validate</li>";
echo "<li>wp_ajax_settings_access_validate</li>";
echo "</ul>";

echo "<h2>🧪 Test Now</h2>";
echo "<p>Now try entering your PIN on the settings page again!</p>";
echo "<p><a href='" . home_url() . "' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>🔐 Test Settings Page</a></p>";
?>
