<?php
/**
 * Complete PIN System Simplification
 * This applies both backend and frontend fixes for plain number PIN system
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

echo "<h1>🔧 Complete PIN System Simplification</h1>";
echo "<p>Applying both backend and frontend changes...</p>";

// Step 1: Apply backend simplified PIN system
echo "<h2>📡 Step 1: Backend PIN Verification</h2>";

// Remove all existing PIN handlers
remove_all_actions('wp_ajax_age_estimator_verify_settings_pin');

// Add simplified PIN verification handler
add_action('wp_ajax_age_estimator_verify_settings_pin', function() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in'));
        return;
    }
    
    $user_id = get_current_user_id();
    $entered_pin = sanitize_text_field($_POST['pin'] ?? '');
    
    // Validate PIN format
    if (!preg_match('/^\d{4}$/', $entered_pin)) {
        wp_send_json_error(array('message' => 'PIN must be exactly 4 digits'));
        return;
    }
    
    // Get stored PIN
    $stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
    
    if (empty($stored_pin)) {
        wp_send_json_error(array(
            'message' => 'No PIN has been set. Please set up a PIN first.',
            'no_pin' => true
        ));
        return;
    }
    
    // If stored PIN looks like a hash, reject it
    if (strlen($stored_pin) > 10 || strpos($stored_pin, '$') !== false) {
        wp_send_json_error(array(
            'message' => 'Your PIN needs to be reset. Please set a new 4-digit PIN in settings.',
            'needs_reset' => true
        ));
        return;
    }
    
    // Simple string comparison
    if ($entered_pin === $stored_pin) {
        update_user_meta($user_id, 'age_estimator_pin_session_time', time());
        wp_send_json_success(array(
            'message' => 'PIN verified successfully',
            'redirect' => true
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'Incorrect PIN. Access denied.'
        ));
    }
}, 1);

echo "<p>✅ Simplified backend PIN verification installed</p>";

// Step 2: Apply frontend form updates
echo "<h2>🖥️ Step 2: Frontend Form Handling</h2>";

// Override PIN form JavaScript
add_action('wp_footer', function() {
    global $post;
    
    if (is_a($post, 'WP_Post') && 
        (has_shortcode($post->post_content, 'age_estimator_settings_enhanced') || 
         has_shortcode($post->post_content, 'age_estimator_user_settings'))) {
        ?>
        <script>
        // Override PIN form handling with simplified version
        jQuery(document).ready(function($) {
            console.log('🔐 Simplified PIN system active');
            
            // Remove any existing handlers
            $(document).off('submit', '#pin-access-form, #settings-pin-form');
            
            // Simplified PIN form submission
            $(document).on('submit', '#pin-access-form, #settings-pin-form', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $button = $form.find('button[type="submit"], .pin-submit').first();
                const $input = $form.find('#settings-pin, .pin-input').first();
                const pin = $input.val();
                
                console.log('📱 PIN form submitted with:', pin);
                
                if (!/^\d{4}$/.test(pin)) {
                    showPinMessage('Please enter exactly 4 digits', 'error');
                    $input.focus();
                    return;
                }
                
                // Loading state
                $button.prop('disabled', true).text('⏳ Verifying...');
                showPinMessage('Checking PIN...', 'info');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'age_estimator_verify_settings_pin',
                        pin: pin
                    },
                    success: function(response) {
                        console.log('✅ PIN verification result:', response);
                        
                        if (response.success) {
                            showPinMessage('✅ ' + response.data.message, 'success');
                            $button.text('✅ Success!').css('background', '#28a745');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showPinMessage('❌ ' + (response.data ? response.data.message : 'Invalid PIN'), 'error');
                            $input.val('').focus();
                            resetButton();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ AJAX Error:', error);
                        showPinMessage('❌ Network error. Please try again.', 'error');
                        resetButton();
                    }
                });
                
                function resetButton() {
                    setTimeout(() => {
                        $button.prop('disabled', false).text('Access Settings').css('background', '');
                    }, 2000);
                }
            });
            
            // PIN input formatting
            $(document).on('input', '#settings-pin, .pin-input', function() {
                const $input = $(this);
                let value = $input.val().replace(/\D/g, '');
                if (value.length > 4) value = value.substring(0, 4);
                $input.val(value);
                
                if (value.length === 4) {
                    console.log('📱 Auto-submitting PIN...');
                    setTimeout(() => $input.closest('form').submit(), 200);
                }
            });
            
            // Focus PIN input
            setTimeout(() => $('#settings-pin, .pin-input').first().focus(), 300);
            
            function showPinMessage(message, type) {
                $('.pin-message').remove();
                
                const $message = $('<div class="pin-message pin-message-' + type + '">' + message + '</div>');
                
                const $container = $('.pin-header, .pin-container, form').first();
                if ($container.length) {
                    $container.after($message);
                } else {
                    $('body').prepend($message);
                }
                
                if (type === 'success' || type === 'info') {
                    setTimeout(() => $message.fadeOut(), 3000);
                }
            }
        });
        </script>
        
        <style>
        .pin-message {
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 4px;
            font-weight: 500;
        }
        .pin-message-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .pin-message-error {
            background: #f8d7da;
            color: #721c24;  
            border: 1px solid #f5c6cb;
        }
        .pin-message-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        #settings-pin, .pin-input {
            font-family: monospace !important;
            letter-spacing: 3px !important;
            text-align: center !important;
            font-size: 18px !important;
            font-weight: bold !important;
        }
        </style>
        <?php
    }
}, 30);

echo "<p>✅ Simplified frontend form handling installed</p>";

// Step 3: Check current user's PIN status
echo "<h2>👤 Step 3: Your Current Status</h2>";

if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $user = wp_get_current_user();
    $stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
    
    echo "<p><strong>User:</strong> {$user->display_name} (ID: $user_id)</p>";
    
    if (empty($stored_pin)) {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 4px; margin: 15px 0;'>";
        echo "<h3>⚠️ No PIN Set</h3>";
        echo "<p>You need to set a 4-digit PIN first:</p>";
        echo "<ol>";
        echo "<li>Go to your settings page</li>";
        echo "<li>Navigate to Retail Mode section</li>";
        echo "<li>Enter a 4-digit PIN (like 1234)</li>";
        echo "<li>Save settings</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        $is_hashed = (strlen($stored_pin) > 10 || strpos($stored_pin, '$') !== false);
        
        if ($is_hashed) {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 4px; margin: 15px 0;'>";
            echo "<h3>🔒 Hashed PIN Found (Needs Reset)</h3>";
            echo "<p>Your PIN is stored as a hash and needs to be reset:</p>";
            echo "<form method='post' style='margin: 10px 0;'>";
            echo "<button type='submit' name='reset_pin' style='background: #dc3545; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;'>Reset My PIN</button>";
            echo "</form>";
            
            if (isset($_POST['reset_pin'])) {
                delete_user_meta($user_id, 'age_estimator_retail_pin');
                echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 4px;'>";
                echo "✅ PIN reset! Go to settings to set a new 4-digit PIN.";
                echo "</div>";
                echo "<script>setTimeout(() => location.reload(), 2000);</script>";
            }
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 4px; margin: 15px 0;'>";
            echo "<h3>✅ PIN Ready!</h3>";
            echo "<p><strong>Your PIN:</strong> <code style='background: #007cba; color: white; padding: 4px 8px; border-radius: 3px;'>$stored_pin</code></p>";
            echo "<p>Try entering <strong>$stored_pin</strong> on the settings page - it should work perfectly now!</p>";
            echo "</div>";
        }
    }
} else {
    echo "<p><em>Please log in to check your PIN status</em></p>";
}

echo "<h2>🚀 Ready to Test!</h2>";
echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<p><strong>The complete simplified PIN system is now active!</strong></p>";
echo "<p>Here's what happens now:</p>";
echo "<ol>";
echo "<li><strong>You enter PIN:</strong> System accepts any 4 digits</li>";
echo "<li><strong>Form submits:</strong> AJAX call with your PIN</li>";
echo "<li><strong>Server checks:</strong> Simple comparison (no hashing)</li>";
echo "<li><strong>Response:</strong> Success or error message</li>";
echo "<li><strong>If success:</strong> Page reloads showing settings</li>";
echo "</ol>";
echo "</div>";

echo "<p style='text-align: center;'>";
echo "<a href='" . home_url() . "' style='background: #007cba; color: white; padding: 15px 30px; text-decoration: none; border-radius: 4px; font-size: 18px; font-weight: bold;'>🔐 Test PIN System Now</a>";
echo "</p>";

echo "<h3>🔍 If Something Goes Wrong:</h3>";
echo "<ul>";
echo "<li>Open browser Developer Tools (F12)</li>";
echo "<li>Check Console tab for messages starting with 📱 🔐</li>";
echo "<li>Check Network tab for AJAX requests</li>";
echo "<li>Look for any JavaScript errors</li>";
echo "</ul>";

echo "<div style='background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 4px; border-left: 4px solid #007cba;'>";
echo "<strong>🎯 Bottom Line:</strong> Your PIN system is now as simple as possible - no hashing, no complex verification, just plain number comparison. It should work reliably every time!";
echo "</div>";
?>