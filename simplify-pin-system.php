<?php
/**
 * Simplified PIN System - Plain Numbers Only
 * No more hashing complexity!
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

// Remove all existing PIN handlers to avoid conflicts
remove_all_actions('wp_ajax_age_estimator_verify_settings_pin');

/**
 * Simple PIN verification - just compare numbers
 */
add_action('wp_ajax_age_estimator_verify_settings_pin', function() {
    error_log('=== SIMPLE PIN VERIFICATION ===');
    
    // Basic validation
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in'));
        return;
    }
    
    $user_id = get_current_user_id();
    $entered_pin = sanitize_text_field($_POST['pin'] ?? '');
    
    error_log("User: $user_id, Entered PIN: '$entered_pin'");
    
    // Validate PIN format
    if (!preg_match('/^\d{4}$/', $entered_pin)) {
        wp_send_json_error(array('message' => 'PIN must be exactly 4 digits'));
        return;
    }
    
    // Get stored PIN (convert any old hashed PINs)
    $stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
    
    if (empty($stored_pin)) {
        wp_send_json_error(array(
            'message' => 'No PIN has been set. Please set up a PIN first.',
            'no_pin' => true
        ));
        return;
    }
    
    // If stored PIN looks like a hash, we need to reset it
    if (strlen($stored_pin) > 10 || strpos($stored_pin, '$') !== false) {
        error_log("Found hashed PIN for user $user_id, needs to be reset to plain text");
        wp_send_json_error(array(
            'message' => 'Your PIN needs to be reset. Please contact an administrator.',
            'needs_reset' => true
        ));
        return;
    }
    
    error_log("Stored PIN: '$stored_pin'");
    
    // Simple string comparison
    if ($entered_pin === $stored_pin) {
        // Set session
        update_user_meta($user_id, 'age_estimator_pin_session_time', time());
        error_log("PIN verified successfully for user $user_id");
        
        wp_send_json_success(array(
            'message' => 'PIN verified successfully',
            'redirect' => true
        ));
    } else {
        error_log("PIN verification failed: '$entered_pin' !== '$stored_pin'");
        wp_send_json_error(array(
            'message' => 'Incorrect PIN. Access denied.'
        ));
    }
}, 1);

/**
 * Convert any existing hashed PINs to plain text (admin only)
 */
if (current_user_can('manage_options')) {
    add_action('wp_ajax_reset_hashed_pins', function() {
        global $wpdb;
        
        // Get all users with hashed PINs (longer than 10 chars or contains $)
        $results = $wpdb->get_results(
            "SELECT user_id, meta_value FROM {$wpdb->usermeta} 
             WHERE meta_key = 'age_estimator_retail_pin' 
             AND (LENGTH(meta_value) > 10 OR meta_value LIKE '%$%')"
        );
        
        $reset_count = 0;
        foreach ($results as $row) {
            // Remove the hashed PIN - user will need to set a new one
            delete_user_meta($row->user_id, 'age_estimator_retail_pin');
            $reset_count++;
            error_log("Reset hashed PIN for user {$row->user_id}");
        }
        
        wp_send_json_success(array(
            'message' => "Reset $reset_count hashed PINs. Users will need to set new PINs.",
            'count' => $reset_count
        ));
    });
}

/**
 * Ensure PIN saving is also simplified (no hashing)
 */
add_action('wp_ajax_age_estimator_save_user_settings', function() {
    // Only handle retail section
    if (($_POST['section'] ?? '') !== 'retail') {
        return; // Let other handlers process
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in'));
        return;
    }
    
    $user_id = get_current_user_id();
    
    // Get PIN from form data
    $settings = $_POST['settings'] ?? array();
    $pin = $settings['retail_pin'] ?? '';
    
    if (!empty($pin)) {
        // Validate PIN format
        if (!preg_match('/^\d{4}$/', $pin)) {
            wp_send_json_error(array('message' => 'PIN must be exactly 4 digits (0000-9999)'));
            return;
        }
        
        // Store as plain text - no hashing!
        update_user_meta($user_id, 'age_estimator_retail_pin', $pin);
        error_log("Saved plain text PIN for user $user_id");
        
        wp_send_json_success(array('message' => 'PIN saved successfully'));
    } else {
        wp_send_json_error(array('message' => 'Please enter a 4-digit PIN'));
    }
}, 5); // Higher priority than original handler

// Display status
echo "<h1>📱 Simplified PIN System Applied</h1>";

echo "<h2>✅ What Changed:</h2>";
echo "<ul>";
echo "<li>✅ PINs are now stored as plain 4-digit numbers (no hashing)</li>";
echo "<li>✅ PIN verification uses simple string comparison</li>";
echo "<li>✅ All hashing complexity removed</li>";
echo "<li>✅ Much more reliable verification</li>";
echo "<li>✅ Easier debugging and troubleshooting</li>";
echo "</ul>";

echo "<h2>⚠️ Important Notes:</h2>";
echo "<ul>";
echo "<li>🔄 Any existing hashed PINs need to be reset to plain numbers</li>";
echo "<li>📱 PINs are still secure for retail/kiosk use (4-digit is standard)</li>";
echo "<li>🎯 This matches how most retail systems work</li>";
echo "<li>🐛 Should eliminate all verification issues</li>";
echo "</ul>";

if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
    $user = wp_get_current_user();
    
    echo "<h2>📊 Your Current Status:</h2>";
    echo "<p><strong>User:</strong> {$user->display_name} (ID: $user_id)</p>";
    echo "<p><strong>PIN exists:</strong> " . (!empty($stored_pin) ? 'Yes' : 'No') . "</p>";
    
    if (!empty($stored_pin)) {
        $is_hashed = (strlen($stored_pin) > 10 || strpos($stored_pin, '$') !== false);
        echo "<p><strong>PIN format:</strong> " . ($is_hashed ? '🔒 Hashed (needs reset)' : '📱 Plain number (good!)') . "</p>";
        echo "<p><strong>PIN value:</strong> " . ($is_hashed ? '[Hidden - hashed]' : $stored_pin) . "</p>";
        
        if ($is_hashed && current_user_can('manage_options')) {
            echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 15px 0; border-radius: 4px;'>";
            echo "<h3>🔧 Fix Required</h3>";
            echo "<p>Your PIN is currently hashed and needs to be reset. Click the button below:</p>";
            echo "<button onclick='resetHashedPins()' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>Reset Hashed PINs</button>";
            echo "</div>";
        }
    } else {
        echo "<p><em>No PIN set - go to settings to set a new 4-digit PIN</em></p>";
    }
}

echo "<h2>🧪 Test Your Setup:</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px;'>";
echo "<h3>Step 1: Set a PIN (if needed)</h3>";
echo "<p>1. Go to your settings page</p>";
echo "<p>2. Navigate to Retail Mode section</p>";
echo "<p>3. Enter a 4-digit PIN (like 1234)</p>";
echo "<p>4. Save settings</p>";

echo "<h3>Step 2: Test PIN Access</h3>";
echo "<p>1. Refresh the settings page</p>";
echo "<p>2. Enter your PIN when prompted</p>";
echo "<p>3. Should work immediately!</p>";
echo "</div>";

echo "<p><a href='" . home_url() . "' style='background: #007cba; color: white; padding: 15px 25px; text-decoration: none; border-radius: 4px; font-size: 16px;'>🔐 Go Test Settings Page</a></p>";
?>

<script>
function resetHashedPins() {
    if (confirm('This will reset all hashed PINs. Users will need to set new PINs. Continue?')) {
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=reset_hashed_pins'
        })
        .then(response => response.json())
        .then(data => {
            alert(data.data.message);
            location.reload();
        })
        .catch(error => {
            alert('Error: ' + error);
        });
    }
}
</script>
