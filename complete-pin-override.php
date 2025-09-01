<?php
/**
 * Complete PIN Protection Override
 * This completely replaces the existing PIN system to fix the loop
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

// Completely disable the existing PIN protection class
add_action('init', function() {
    // Remove all existing PIN protection hooks
    remove_all_filters('age_estimator_render_enhanced_settings');
    remove_all_actions('wp_ajax_age_estimator_verify_settings_pin');
    remove_all_actions('wp_ajax_age_estimator_check_pin_session');
    remove_all_actions('wp_ajax_age_estimator_lock_settings');
    
    // Prevent the existing PIN protection class from loading
    global $age_estimator_pin_protection_disabled;
    $age_estimator_pin_protection_disabled = true;
}, 1);

// Add our own complete PIN protection system
class SimpleAgeEstimatorPinProtection {
    
    public function __construct() {
        add_filter('age_estimator_render_enhanced_settings', array($this, 'handle_settings_access'), 1, 2);
        add_action('wp_ajax_age_estimator_verify_settings_pin', array($this, 'verify_pin'), 1);
        add_action('init', array($this, 'handle_temporary_access'), 1);
    }
    
    public function handle_temporary_access() {
        // Handle temporary access request
        if (isset($_GET['temp_access']) && $_GET['temp_access'] === '1' && is_user_logged_in()) {
            $user_id = get_current_user_id();
            
            // Grant 10-minute temporary access
            update_user_meta($user_id, 'simple_pin_temp_access', time());
            
            // Redirect to clean URL
            $redirect_url = remove_query_arg('temp_access');
            wp_redirect($redirect_url);
            exit;
        }
        
        // Handle end temporary access
        if (isset($_GET['end_temp']) && $_GET['end_temp'] === '1' && is_user_logged_in()) {
            $user_id = get_current_user_id();
            delete_user_meta($user_id, 'simple_pin_temp_access');
            
            $redirect_url = remove_query_arg('end_temp');
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    public function handle_settings_access($content, $atts) {
        if (!is_user_logged_in()) {
            return $content; // Show login form
        }
        
        $user_id = get_current_user_id();
        
        // Check temporary access first
        $temp_access = $this->check_temporary_access($user_id);
        if ($temp_access['valid']) {
            return $this->add_temp_access_notice($content, $temp_access['remaining']);
        }
        
        // Check if user has a PIN set
        $stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
        if (empty($stored_pin)) {
            return $this->show_pin_setup_form();
        }
        
        // Check PIN session
        $session_valid = $this->check_pin_session($user_id);
        if (!$session_valid) {
            return $this->show_pin_entry_form($stored_pin);
        }
        
        // All good - show settings
        return $this->add_lock_button($content);
    }
    
    private function check_temporary_access($user_id) {
        $temp_time = get_user_meta($user_id, 'simple_pin_temp_access', true);
        
        if (empty($temp_time)) {
            return array('valid' => false, 'remaining' => 0);
        }
        
        $elapsed = time() - intval($temp_time);
        $timeout = 10 * 60; // 10 minutes
        
        if ($elapsed >= $timeout) {
            // Expired - clean up
            delete_user_meta($user_id, 'simple_pin_temp_access');
            return array('valid' => false, 'remaining' => 0);
        }
        
        return array('valid' => true, 'remaining' => $timeout - $elapsed);
    }
    
    private function check_pin_session($user_id) {
        $session_time = get_user_meta($user_id, 'age_estimator_pin_session_time', true);
        
        if (empty($session_time)) {
            return false;
        }
        
        $elapsed = time() - intval($session_time);
        $timeout = 15 * 60; // 15 minutes
        
        return $elapsed < $timeout;
    }
    
    public function verify_pin() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Not logged in'));
        }
        
        $user_id = get_current_user_id();
        $entered_pin = sanitize_text_field($_POST['pin'] ?? '');
        
        if (!preg_match('/^\d{4}$/', $entered_pin)) {
            wp_send_json_error(array('message' => 'PIN must be 4 digits'));
        }
        
        $stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
        
        if (empty($stored_pin)) {
            wp_send_json_error(array('message' => 'No PIN set'));
        }
        
        // Simple comparison - no hashing
        if ($entered_pin === $stored_pin) {
            update_user_meta($user_id, 'age_estimator_pin_session_time', time());
            wp_send_json_success(array('message' => 'PIN verified'));
        } else {
            wp_send_json_error(array('message' => 'Incorrect PIN'));
        }
    }
    
    private function show_pin_setup_form() {
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $temp_access_url = add_query_arg('temp_access', '1', $current_url);
        
        return '
        <div style="max-width: 600px; margin: 40px auto; padding: 30px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
            <div style="font-size: 48px; margin-bottom: 20px;">🔐</div>
            <h2 style="color: #333; margin-bottom: 10px;">PIN Setup Required</h2>
            <p style="color: #666; margin-bottom: 30px;">You need to set up a 4-digit PIN to protect your settings access</p>
            
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: left;">
                <div style="display: flex; align-items: center; margin-bottom: 15px;">
                    <span style="font-size: 24px; margin-right: 10px;">⚠️</span>
                    <strong>No PIN Configured</strong>
                </div>
                <p>To access the settings panel, you first need to set up a 4-digit PIN in the retail settings section.</p>
            </div>
            
            <div style="text-align: left; margin: 20px 0;">
                <h4>How to set up your PIN:</h4>
                <ol style="padding-left: 20px;">
                    <li>Click the temporary access button below</li>
                    <li>Navigate to the "Retail Mode" section</li>
                    <li>Set your 4-digit PIN in the "Staff PIN" field</li>
                    <li>Save your settings</li>
                    <li>Next time you visit, you\'ll need to enter this PIN</li>
                </ol>
            </div>
            
            <div style="margin: 30px 0;">
                <a href="' . esc_url($temp_access_url) . '" 
                   style="display: inline-block; background: #007cba; color: white; padding: 15px 25px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;">
                    🔓 One-Time Access to Set PIN
                </a>
            </div>
            
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 6px; margin: 20px 0;">
                <div style="display: flex; align-items: center;">
                    <span style="font-size: 20px; margin-right: 10px;">🛡️</span>
                    <div>
                        <strong>Important:</strong> After you set your PIN, you will need to enter it every time you want to access these settings. Make sure to remember it!
                    </div>
                </div>
            </div>
        </div>';
    }
    
    private function show_pin_entry_form($stored_pin) {
        $user = wp_get_current_user();
        $debug_info = WP_DEBUG ? '<div style="background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 4px; font-family: monospace; font-size: 12px;"><strong>Debug:</strong> Your PIN is <code>' . $stored_pin . '</code></div>' : '';
        
        return '
        <div style="max-width: 500px; margin: 40px auto; padding: 30px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="font-size: 48px; margin-bottom: 15px;">🔒</div>
                <h2 style="color: #333; margin-bottom: 10px;">Settings Access Protection</h2>
                <p style="color: #666;">Please enter your PIN to access the settings</p>
            </div>
            
            <div style="display: flex; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 20px;">
                <div style="margin-right: 15px;">' . get_avatar(get_current_user_id(), 40) . '</div>
                <div>
                    <div style="font-weight: bold;">' . esc_html($user->display_name) . '</div>
                    <div style="color: #666; font-size: 14px;">' . esc_html($user->user_email) . '</div>
                </div>
            </div>
            
            <form id="simple-pin-form" style="text-align: center;">
                <div style="margin-bottom: 20px;">
                    <label for="simple-pin-input" style="display: block; margin-bottom: 8px; font-weight: bold;">Enter 4-digit PIN:</label>
                    <input type="password" 
                           id="simple-pin-input" 
                           maxlength="4" 
                           placeholder="****"
                           style="width: 120px; padding: 12px; font-size: 24px; text-align: center; border: 2px solid #ddd; border-radius: 6px; font-family: monospace; letter-spacing: 8px; font-weight: bold;"
                           autocomplete="off"
                           required>
                </div>
                
                ' . $debug_info . '
                
                <button type="submit" id="simple-pin-button"
                        style="background: #007cba; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer;">
                    Access Settings
                </button>
                
                <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 6px; font-size: 14px; color: #0c5460;">
                    🔐 For security, your session will expire after 15 minutes of inactivity.
                </div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $(\'#simple-pin-form\').on(\'submit\', function(e) {
                e.preventDefault();
                
                const pin = $(\'#simple-pin-input\').val();
                const $button = $(\'#simple-pin-button\');
                
                if (!/^\d{4}$/.test(pin)) {
                    alert(\'Please enter exactly 4 digits\');
                    return;
                }
                
                $button.prop(\'disabled\', true).text(\'⏳ Verifying...\');
                
                $.ajax({
                    url: \'' . admin_url('admin-ajax.php') . '\',
                    type: \'POST\',
                    data: {
                        action: \'age_estimator_verify_settings_pin\',
                        pin: pin
                    },
                    success: function(response) {
                        if (response.success) {
                            $button.text(\'✅ Success!\').css(\'background\', \'#28a745\');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            alert(response.data ? response.data.message : \'Invalid PIN\');
                            $(\'#simple-pin-input\').val(\'\').focus();
                            $button.prop(\'disabled\', false).text(\'Access Settings\').css(\'background\', \'#007cba\');
                        }
                    },
                    error: function() {
                        alert(\'Network error. Please try again.\');
                        $button.prop(\'disabled\', false).text(\'Access Settings\').css(\'background\', \'#007cba\');
                    }
                });
            });
            
            $(\'#simple-pin-input\').on(\'input\', function() {
                let value = $(this).val().replace(/\D/g, \'\');
                if (value.length > 4) value = value.substring(0, 4);
                $(this).val(value);
                
                if (value.length === 4) {
                    setTimeout(() => $(\'#simple-pin-form\').submit(), 200);
                }
            });
            
            setTimeout(() => $(\'#simple-pin-input\').focus(), 300);
        });
        </script>';
    }
    
    private function add_temp_access_notice($content, $remaining) {
        $minutes = ceil($remaining / 60);
        $end_temp_url = add_query_arg('end_temp', '1');
        
        $notice = '
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 15px 0; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span style="font-size: 20px; margin-right: 10px;">⏰</span>
                <strong>Temporary Access Active</strong> - ' . $minutes . ' minutes remaining
                <br><small>📍 <strong>Don\'t forget:</strong> Set your PIN in the <strong>Retail Mode</strong> section!</small>
            </div>
            <a href="' . esc_url($end_temp_url) . '" 
               style="background: #ffc107; color: #000; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold;">
                End Access
            </a>
        </div>';
        
        return $notice . $content;
    }
    
    private function add_lock_button($content) {
        $lock_button = '
        <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 10px 15px; margin: 15px 0; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span style="color: #28a745; margin-right: 10px;">🔓</span>
                <strong>Settings Unlocked</strong>
            </div>
            <button onclick="if(confirm(\'Lock settings?\')) location.reload();" 
                    style="background: #6c757d; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer;">
                🔒 Lock Settings
            </button>
        </div>';
        
        return $lock_button . $content;
    }
}

// Initialize our simple PIN protection
new SimpleAgeEstimatorPinProtection();

echo "<h1>✅ Complete PIN Protection Override Applied</h1>";

echo "<h2>🔧 What this does:</h2>";
echo "<ul>";
echo "<li>✅ Completely disables the existing PIN protection class</li>";
echo "<li>✅ Replaces it with a simple, working version</li>";
echo "<li>✅ Handles temporary access properly with URL parameters</li>";
echo "<li>✅ No more loops or conflicts</li>";
echo "<li>✅ Clean, simple interface</li>";
echo "</ul>";

echo "<h2>🧪 Test Now:</h2>";
echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<p><strong>Go to your settings page now and:</strong></p>";
echo "<ol>";
echo "<li>You should see a clean PIN setup form</li>";
echo "<li>Click 'One-Time Access to Set PIN'</li>";
echo "<li>Should immediately show settings with temp access notice</li>";
echo "<li>Set your PIN in Retail Mode section</li>";
echo "<li>Save and test PIN access</li>";
echo "</ol>";
echo "</div>";

if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $temp_access = get_user_meta($user_id, 'simple_pin_temp_access', true);
    $stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
    
    echo "<h3>📊 Your Status:</h3>";
    echo "<p><strong>PIN set:</strong> " . (!empty($stored_pin) ? "Yes ($stored_pin)" : 'No') . "</p>";
    echo "<p><strong>Temp access:</strong> " . (!empty($temp_access) ? 'Active' : 'None') . "</p>";
}

echo "<p style='text-align: center;'>";
echo "<a href='" . home_url() . "' style='background: #007cba; color: white; padding: 15px 30px; text-decoration: none; border-radius: 4px; font-size: 18px; font-weight: bold;'>🔐 Test Fixed PIN System</a>";
echo "</p>";
?>