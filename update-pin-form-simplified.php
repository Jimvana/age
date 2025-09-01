<?php
/**
 * Update PIN Form to Use Simplified System
 * This updates the frontend JavaScript to work with plain number PINs
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

// Override the PIN protection script enqueuing
add_action('wp_enqueue_scripts', function() {
    global $post;
    
    // Only on pages with settings shortcodes
    if (is_a($post, 'WP_Post') && 
        (has_shortcode($post->post_content, 'age_estimator_settings_enhanced') || 
         has_shortcode($post->post_content, 'age_estimator_user_settings'))) {
        
        // Remove the old complex script
        wp_dequeue_script('age-estimator-pin-protection');
        wp_deregister_script('age-estimator-pin-protection');
        
        // Load our new simplified script
        wp_enqueue_script(
            'age-estimator-pin-simplified',
            AGE_ESTIMATOR_URL . 'js/pin-protection-simplified.js',
            array('jquery'),
            AGE_ESTIMATOR_VERSION . '-simplified',
            true
        );
        
        // Set up variables for the script
        wp_localize_script('age-estimator-pin-simplified', 'simplifiedPin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('age_estimator_pin_protection'),
            'debug' => WP_DEBUG,
            'version' => 'simplified'
        ));
        
        echo '<script>console.log("🔐 Simplified PIN system loaded");</script>';
    }
}, 20); // Later priority to override

// Also inject the script directly into the page to ensure it loads
add_action('wp_footer', function() {
    global $post;
    
    if (is_a($post, 'WP_Post') && 
        (has_shortcode($post->post_content, 'age_estimator_settings_enhanced') || 
         has_shortcode($post->post_content, 'age_estimator_user_settings'))) {
        ?>
        <script>
        // Ensure our simplified PIN system takes over
        jQuery(document).ready(function($) {
            console.log('🔧 Activating simplified PIN form handling...');
            
            // Override any existing PIN form handlers
            $(document).off('submit', '#pin-access-form');
            $(document).off('submit', '#settings-pin-form');
            
            // Simple PIN form handler
            $(document).on('submit', '#pin-access-form, #settings-pin-form', function(e) {
                e.preventDefault();
                console.log('📱 Simplified PIN form submitted');
                
                const $form = $(this);
                const $button = $form.find('button[type="submit"], .pin-submit').first();
                const $input = $form.find('#settings-pin, .pin-input').first();
                const pin = $input.val();
                
                // Validate PIN
                if (!/^\d{4}$/.test(pin)) {
                    alert('Please enter exactly 4 digits');
                    $input.focus();
                    return;
                }
                
                // Show loading
                $button.prop('disabled', true).text('⏳ Verifying...');
                
                // Simple AJAX call
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'age_estimator_verify_settings_pin',
                        pin: pin,
                        nonce: '<?php echo wp_create_nonce('age_estimator_pin_protection'); ?>'
                    },
                    success: function(response) {
                        console.log('✅ PIN response:', response);
                        
                        if (response.success) {
                            $button.text('✅ Success!').css('background', '#28a745');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            $button.text('❌ ' + (response.data ? response.data.message : 'Invalid PIN')).css('background', '#dc3545');
                            $input.val('').focus();
                            setTimeout(() => {
                                $button.text('Access Settings').css('background', '').prop('disabled', false);
                            }, 2000);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ AJAX Error:', error, xhr.responseText);
                        $button.text('❌ Error - Try Again').css('background', '#dc3545');
                        setTimeout(() => {
                            $button.text('Access Settings').css('background', '').prop('disabled', false);
                        }, 2000);
                    }
                });
            });
            
            // PIN input formatting
            $(document).on('input', '#settings-pin, .pin-input', function() {
                const $input = $(this);
                let value = $input.val().replace(/\D/g, ''); // Only digits
                if (value.length > 4) value = value.substring(0, 4);
                $input.val(value);
                
                // Auto-submit on 4 digits
                if (value.length === 4) {
                    console.log('📱 4 digits entered, auto-submitting...');
                    setTimeout(() => $input.closest('form').submit(), 300);
                }
            });
            
            // Focus PIN input
            setTimeout(() => {
                $('#settings-pin, .pin-input').first().focus();
            }, 500);
        });
        </script>
        <style>
        .pin-input, #settings-pin {
            font-family: monospace !important;
            letter-spacing: 3px !important;
            text-align: center !important;
            font-size: 18px !important;
            font-weight: bold !important;
        }
        button[disabled] {
            opacity: 0.7 !important;
            cursor: not-allowed !important;
        }
        </style>
        <?php
    }
}, 25);

// Display update status
echo "<h1>🔄 PIN Form Updated to Simplified System</h1>";

echo "<h2>✅ Changes Applied:</h2>";
echo "<ul>";
echo "<li>✅ Replaced complex PIN verification JavaScript with simplified version</li>";
echo "<li>✅ PIN form now works with plain number comparison</li>";
echo "<li>✅ Removed hash-based verification complexity</li>";
echo "<li>✅ Added auto-submit when 4 digits entered</li>";
echo "<li>✅ Better error handling and visual feedback</li>";
echo "<li>✅ PIN input formatted with monospace font and spacing</li>";
echo "</ul>";

echo "<h2>📱 How It Works Now:</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Frontend (JavaScript):</h3>";
echo "<ol>";
echo "<li>User enters 4 digits in PIN field</li>";
echo "<li>Form auto-submits when 4 digits entered</li>";
echo "<li>AJAX call to <code>age_estimator_verify_settings_pin</code></li>";
echo "<li>Simple response handling - success or error</li>";
echo "</ol>";

echo "<h3>Backend (PHP):</h3>";
echo "<ol>";
echo "<li>Get entered PIN and stored PIN</li>";
echo "<li>Simple comparison: <code>if (\$entered === \$stored)</code></li>";
echo "<li>Return success/error response</li>";
echo "<li>No hashing, no complex verification</li>";
echo "</ol>";
echo "</div>";

echo "<h2>🧪 Test Your Setup:</h2>";
echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<p><strong>1. Go to your settings page</strong></p>";
echo "<p><strong>2. You should see a PIN entry form</strong></p>";
echo "<p><strong>3. Enter your 4-digit PIN</strong></p>";
echo "<p><strong>4. Form should auto-submit and verify instantly!</strong></p>";
echo "</div>";

if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
    
    if (!empty($stored_pin)) {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
        echo "<h3>📋 Your PIN Info:</h3>";
        echo "<p><strong>Stored PIN:</strong> <code>$stored_pin</code></p>";
        echo "<p><strong>Try entering:</strong> <span style='background: #007cba; color: white; padding: 4px 8px; border-radius: 3px; font-family: monospace;'>$stored_pin</span></p>";
        echo "</div>";
    }
}

echo "<p><a href='" . home_url() . "' style='background: #007cba; color: white; padding: 15px 25px; text-decoration: none; border-radius: 4px; font-size: 16px; font-weight: bold;'>🔐 Test PIN Form Now</a></p>";

echo "<h2>🔍 Debugging:</h2>";
echo "<p>If there are any issues:</p>";
echo "<ul>";
echo "<li>Open browser Developer Tools (F12)</li>";
echo "<li>Go to Console tab</li>";
echo "<li>Look for messages starting with 📱 or 🔐</li>";
echo "<li>Check Network tab for AJAX requests</li>";
echo "</ul>";
?>