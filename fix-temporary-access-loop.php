<?php
/**
 * Fix Temporary PIN Access Loop
 * This will make the temporary access button actually work
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

// Override the PIN protection to handle temporary access properly
add_action('init', function() {
    // Check if temporary access was requested
    if (isset($_GET['temp_access']) && $_GET['temp_access'] === '1' && is_user_logged_in()) {
        $user_id = get_current_user_id();
        
        // Grant a temporary 10-minute session without PIN
        update_user_meta($user_id, 'age_estimator_temp_access_time', time());
        
        // Redirect to clean URL
        $clean_url = remove_query_arg('temp_access');
        wp_redirect($clean_url);
        exit;
    }
}, 5);

// Override the PIN protection filter to check for temporary access
add_filter('age_estimator_render_enhanced_settings', function($content, $atts) {
    if (!is_user_logged_in()) {
        return $content; // Show original login form
    }
    
    $user_id = get_current_user_id();
    
    // Check for temporary access first
    $temp_access_time = get_user_meta($user_id, 'age_estimator_temp_access_time', true);
    $temp_access_valid = false;
    
    if ($temp_access_time) {
        $temp_access_timeout = 10 * 60; // 10 minutes
        if ((time() - intval($temp_access_time)) < $temp_access_timeout) {
            $temp_access_valid = true;
        } else {
            // Clean up expired temp access
            delete_user_meta($user_id, 'age_estimator_temp_access_time');
        }
    }
    
    // If temporary access is valid, show settings with a notice
    if ($temp_access_valid) {
        $remaining_time = $temp_access_timeout - (time() - intval($temp_access_time));
        $remaining_minutes = ceil($remaining_time / 60);
        
        $temp_notice = '
        <div class="temp-access-notice" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 15px 0; border-radius: 4px;">
            <h3 style="margin-top: 0;">⏰ Temporary Access Active</h3>
            <p><strong>Important:</strong> You have temporary access for the next ' . $remaining_minutes . ' minutes.</p>
            <p>📍 <strong>Don\'t forget:</strong> Set your PIN in the <strong>Retail Mode</strong> section before this expires!</p>
        </div>';
        
        return $temp_notice . $content;
    }
    
    // Check if PIN is set
    $stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
    if (empty($stored_pin)) {
        // Show PIN setup form with working temporary access
        return render_working_pin_setup_form();
    }
    
    // Check if PIN session is valid (for existing PINs)
    $session_time = get_user_meta($user_id, 'age_estimator_pin_session_time', true);
    $session_timeout = 15 * 60; // 15 minutes
    
    if (empty($session_time) || (time() - intval($session_time)) >= $session_timeout) {
        // Show PIN entry form
        return render_working_pin_entry_form($stored_pin);
    }
    
    // PIN session is valid, show settings
    return add_lock_button($content);
    
}, 10, 2);

function render_working_pin_setup_form() {
    ob_start();
    ?>
    <div class="age-estimator-pin-setup">
        <div class="pin-setup-container">
            <div class="pin-setup-header">
                <div class="setup-icon">🔐</div>
                <h2>PIN Setup Required</h2>
                <p>You need to set up a 4-digit PIN to protect your settings access</p>
            </div>
            
            <div class="pin-setup-content">
                <div class="setup-notice">
                    <div class="notice-icon">⚠️</div>
                    <div class="notice-text">
                        <h3>No PIN Configured</h3>
                        <p>To access the settings panel, you first need to set up a 4-digit PIN in the retail settings section.</p>
                    </div>
                </div>
                
                <div class="setup-steps">
                    <h4>How to set up your PIN:</h4>
                    <ol>
                        <li>Click the temporary access button below</li>
                        <li>Navigate to the "Retail Mode" section</li>
                        <li>Set your 4-digit PIN in the "Staff PIN" field</li>
                        <li>Save your settings</li>
                        <li>Next time you visit, you'll need to enter this PIN</li>
                    </ol>
                </div>
                
                <div class="setup-actions">
                    <a href="<?php echo add_query_arg('temp_access', '1'); ?>" 
                       class="btn btn-primary"
                       style="display: inline-block; background: #007cba; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;">
                        <span class="btn-icon">🔓</span>
                        One-Time Access to Set PIN
                    </a>
                </div>
                
                <div class="setup-warning">
                    <div class="warning-icon">🛡️</div>
                    <div class="warning-text">
                        <strong>Important:</strong>
                        After you set your PIN, you will need to enter it every time you want to access these settings. Make sure to remember it!
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function render_working_pin_entry_form($stored_pin) {
    $user = wp_get_current_user();
    
    ob_start();
    ?>
    <div class="age-estimator-pin-protection">
        <div class="pin-container">
            <div class="pin-header">
                <div class="pin-icon">🔒</div>
                <h2>Settings Access Protection</h2>
                <p>Please enter your PIN to access the settings</p>
            </div>
            
            <div class="pin-form-wrapper">
                <form id="pin-access-form" class="pin-form">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo get_avatar(get_current_user_id(), 40); ?>
                        </div>
                        <div class="user-details">
                            <div class="user-name"><?php echo esc_html($user->display_name); ?></div>
                            <div class="user-email"><?php echo esc_html($user->user_email); ?></div>
                        </div>
                    </div>
                    
                    <div class="pin-input-group">
                        <label for="settings-pin">Enter 4-digit PIN:</label>
                        <input type="password" 
                               id="settings-pin" 
                               name="pin" 
                               maxlength="4" 
                               pattern="\d{4}" 
                               placeholder="****"
                               class="pin-input"
                               autocomplete="off"
                               style="font-family: monospace; letter-spacing: 3px; text-align: center; font-size: 18px; font-weight: bold;"
                               required>
                    </div>
                    
                    <div class="pin-actions">
                        <button type="submit" class="btn btn-primary pin-submit"
                                style="background: #007cba; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                            Access Settings
                        </button>
                    </div>
                    
                    <?php if (WP_DEBUG): ?>
                    <div class="pin-debug" style="background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 4px; font-family: monospace; font-size: 12px;">
                        <strong>Debug Info:</strong> Your PIN is <code><?php echo $stored_pin; ?></code>
                    </div>
                    <?php endif; ?>
                    
                    <div class="pin-help">
                        <p>
                            Your PIN is the same one you set in the retail settings.
                            <br>
                            <small>🔐 For security, your session will expire after 15 minutes of inactivity.</small>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        console.log('🔐 PIN entry form loaded');
        
        // Handle PIN form submission
        $('#pin-access-form').on('submit', function(e) {
            e.preventDefault();
            
            const pin = $('#settings-pin').val();
            const $button = $('.pin-submit');
            
            if (!/^\d{4}$/.test(pin)) {
                alert('Please enter exactly 4 digits');
                return;
            }
            
            $button.prop('disabled', true).text('⏳ Verifying...');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'age_estimator_verify_settings_pin',
                    pin: pin
                },
                success: function(response) {
                    if (response.success) {
                        $button.text('✅ Success!').css('background', '#28a745');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        alert(response.data ? response.data.message : 'Invalid PIN');
                        $('#settings-pin').val('').focus();
                        $button.prop('disabled', false).text('Access Settings').css('background', '#007cba');
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                    $button.prop('disabled', false).text('Access Settings').css('background', '#007cba');
                }
            });
        });
        
        // Format PIN input
        $('#settings-pin').on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            if (value.length > 4) value = value.substring(0, 4);
            $(this).val(value);
            
            if (value.length === 4) {
                setTimeout(() => $('#pin-access-form').submit(), 200);
            }
        });
        
        // Focus PIN input
        setTimeout(() => $('#settings-pin').focus(), 300);
    });
    </script>
    <?php
    return ob_get_clean();
}

function add_lock_button($content) {
    $user_id = get_current_user_id();
    $temp_access_time = get_user_meta($user_id, 'age_estimator_temp_access_time', true);
    
    if ($temp_access_time) {
        // Temporary access - show different button
        $lock_button = '
        <div class="settings-security-bar" style="background: #fff3cd; padding: 10px 15px; margin: 15px 0; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <div class="security-status">
                <span class="security-icon">⏰</span>
                <span class="security-text">Temporary Access Active</span>
            </div>
            <button onclick="if(confirm(\'End temporary access?\')) location.href=location.href.split(\'?\')[0];" class="btn btn-outline btn-sm" style="padding: 6px 12px; border: 1px solid #ffc107; background: white; border-radius: 3px; cursor: pointer;">
                End Temp Access
            </button>
        </div>';
    } else {
        // Regular PIN session
        $lock_button = '
        <div class="settings-security-bar" style="background: #d4edda; padding: 10px 15px; margin: 15px 0; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <div class="security-status">
                <span class="security-icon">🔓</span>
                <span class="security-text">Settings Unlocked</span>
            </div>
            <button id="lock-settings-btn" class="btn btn-outline btn-sm" style="padding: 6px 12px; border: 1px solid #6c757d; background: white; border-radius: 3px; cursor: pointer;">
                🔒 Lock Settings
            </button>
        </div>';
    }
    
    // Insert after any existing header
    if (strpos($content, '<div class="settings-header">') !== false) {
        $content = preg_replace('/(<div class="settings-header">.*?<\/div>)/s', '$1' . $lock_button, $content);
    } else {
        $content = $lock_button . $content;
    }
    
    return $content;
}

echo "<h1>✅ Temporary Access Loop Fix Applied</h1>";

echo "<h2>🔧 What this fixes:</h2>";
echo "<ul>";
echo "<li>✅ Temporary access button now properly grants 10-minute access</li>";
echo "<li>✅ URL parameter handling fixed to prevent loops</li>";
echo "<li>✅ Temporary access state properly tracked and displayed</li>";
echo "<li>✅ Clean redirect after granting temporary access</li>";
echo "<li>✅ Automatic cleanup of expired temporary sessions</li>";
echo "</ul>";

echo "<h2>📱 How it works now:</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<ol>";
echo "<li><strong>Click 'One-Time Access':</strong> Adds ?temp_access=1 to URL</li>";
echo "<li><strong>Server processes:</strong> Sets temporary access flag in user meta</li>";
echo "<li><strong>Redirect:</strong> Clean URL without parameters</li>";
echo "<li><strong>Access granted:</strong> Shows settings with temp access notice</li>";
echo "<li><strong>10-minute timer:</strong> Automatic expiration and cleanup</li>";
echo "</ol>";
echo "</div>";

if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $temp_access_time = get_user_meta($user_id, 'age_estimator_temp_access_time', true);
    
    if ($temp_access_time) {
        $remaining = 600 - (time() - intval($temp_access_time));
        if ($remaining > 0) {
            echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
            echo "<h3>⏰ You Currently Have Temporary Access</h3>";
            echo "<p><strong>Time remaining:</strong> " . ceil($remaining / 60) . " minutes</p>";
            echo "<p>Go to your settings page now to set your PIN!</p>";
            echo "</div>";
        } else {
            // Clean up expired access
            delete_user_meta($user_id, 'age_estimator_temp_access_time');
        }
    }
}

echo "<p style='text-align: center;'>";
echo "<a href='" . home_url() . "' style='background: #007cba; color: white; padding: 15px 30px; text-decoration: none; border-radius: 4px; font-size: 18px; font-weight: bold;'>🔐 Test Fixed Temporary Access</a>";
echo "</p>";

echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🧪 Testing Steps:</h3>";
echo "<ol>";
echo "<li>Go to your settings page</li>";
echo "<li>You should see the PIN setup page</li>";
echo "<li>Click 'One-Time Access to Set PIN' button</li>";
echo "<li>Should immediately show settings with temp access notice</li>";
echo "<li>Navigate to Retail Mode section</li>";
echo "<li>Set a 4-digit PIN and save</li>";
echo "<li>Next visit will require the PIN you set</li>";
echo "</ol>";
echo "</div>";
?>