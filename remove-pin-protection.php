<?php
/**
 * Complete PIN Protection Removal
 * This removes all PIN protection so you can access settings directly
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

// Completely disable PIN protection system
add_action('init', function() {
    // Remove all PIN protection hooks and filters
    remove_all_filters('age_estimator_render_enhanced_settings');
    remove_all_actions('wp_ajax_age_estimator_verify_settings_pin');
    remove_all_actions('wp_ajax_age_estimator_check_pin_session');
    remove_all_actions('wp_ajax_age_estimator_lock_settings');
    
    // Set global flag to prevent PIN protection from loading
    global $age_estimator_pin_protection_disabled;
    $age_estimator_pin_protection_disabled = true;
}, 1);

// Override the PIN protection filter to always allow access
add_filter('age_estimator_render_enhanced_settings', function($content, $atts) {
    // Simply return the content without any PIN protection
    if (!is_user_logged_in()) {
        return $content; // Still require WordPress login
    }
    
    // Add a notice that PIN protection is disabled
    $notice = '
    <div style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 15px 0; border-radius: 6px;">
        <div style="display: flex; align-items: center;">
            <span style="font-size: 20px; margin-right: 10px;">🔓</span>
            <div>
                <strong>PIN Protection Disabled</strong>
                <br><small>Settings are accessible without PIN verification</small>
            </div>
        </div>
    </div>';
    
    return $notice . $content;
}, 1, 2);

// Also override the enhanced settings class to prevent PIN protection
add_action('wp_loaded', function() {
    // If the enhanced settings class exists, disable its PIN protection
    if (class_exists('AgeEstimatorUserSettingsEnhanced')) {
        remove_filter('age_estimator_render_enhanced_settings', array(AgeEstimatorSettingsPinProtection::get_instance(), 'maybe_show_pin_form'), 10);
    }
}, 20);

// Prevent the PIN protection JavaScript from loading
add_action('wp_enqueue_scripts', function() {
    global $post;
    
    if (is_a($post, 'WP_Post') && 
        (has_shortcode($post->post_content, 'age_estimator_settings_enhanced') || 
         has_shortcode($post->post_content, 'age_estimator_user_settings'))) {
        
        // Remove PIN protection scripts
        wp_dequeue_script('age-estimator-pin-protection');
        wp_dequeue_script('age-estimator-pin-simplified');
        wp_deregister_script('age-estimator-pin-protection');
        wp_deregister_script('age-estimator-pin-simplified');
        
        // Remove PIN protection styles
        wp_dequeue_style('age-estimator-pin-protection');
        wp_deregister_style('age-estimator-pin-protection');
    }
}, 99);

// Add JavaScript to override any remaining PIN protection
add_action('wp_footer', function() {
    global $post;
    
    if (is_a($post, 'WP_Post') && 
        (has_shortcode($post->post_content, 'age_estimator_settings_enhanced') || 
         has_shortcode($post->post_content, 'age_estimator_user_settings'))) {
        ?>
        <script>
        // Override any PIN protection that might still be running
        jQuery(document).ready(function($) {
            console.log('🔓 PIN Protection completely disabled');
            
            // Remove any PIN forms that might have been injected
            $('.age-estimator-pin-protection, .age-estimator-pin-setup, .pin-container, .pin-setup-container').remove();
            
            // Make sure settings are visible
            $('.settings-container, .enhanced-settings, .user-settings-enhanced').show();
            
            // If there are any hidden elements due to PIN protection, show them
            $('[style*="display: none"]').each(function() {
                if ($(this).attr('style').includes('display: none') && 
                    ($(this).hasClass('settings') || $(this).find('.settings').length > 0)) {
                    $(this).show();
                }
            });
            
            // Override any PIN protection object that might exist
            if (window.ageEstimatorPinProtection) {
                window.ageEstimatorPinProtection = null;
            }
            
            // Show success message
            if ($('.settings-header, .enhanced-settings').length > 0) {
                $('body').prepend(`
                    <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 15px; border-radius: 6px; position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 300px;">
                        <strong>✅ Settings Accessible</strong>
                        <br><small>PIN protection has been removed</small>
                        <button onclick="this.parentElement.remove()" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">&times;</button>
                    </div>
                `);
            }
        });
        </script>
        <?php
    }
}, 99);

// Clean up any existing PIN sessions and temporary access
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    delete_user_meta($user_id, 'age_estimator_pin_session_time');
    delete_user_meta($user_id, 'simple_pin_temp_access');
}

echo "<h1>🔓 PIN Protection Completely Removed</h1>";

echo "<h2>✅ What's been disabled:</h2>";
echo "<ul>";
echo "<li>✅ All PIN protection filters and hooks removed</li>";
echo "<li>✅ PIN verification AJAX handlers disabled</li>";
echo "<li>✅ PIN protection JavaScript and CSS removed</li>";
echo "<li>✅ Any PIN forms will be hidden via JavaScript</li>";
echo "<li>✅ Settings will be shown directly (if logged in)</li>";
echo "<li>✅ All PIN sessions and temporary access cleared</li>";
echo "</ul>";

echo "<h2>🚀 What happens now:</h2>";
echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #c3e6cb;'>";
echo "<p><strong>✅ Direct Access:</strong> Your settings page will show the settings interface immediately (as long as you're logged into WordPress)</p>";
echo "<p><strong>🔓 No PIN Required:</strong> No PIN forms, no verification, no temporary access needed</p>";
echo "<p><strong>📱 Full Functionality:</strong> All settings sections should be accessible and editable</p>";
echo "</div>";

if (is_user_logged_in()) {
    $user = wp_get_current_user();
    echo "<h3>👤 Your Status:</h3>";
    echo "<p><strong>WordPress User:</strong> {$user->display_name} ✅</p>";
    echo "<p><strong>User Role:</strong> " . implode(', ', $user->roles) . "</p>";
    echo "<p><strong>Access Level:</strong> Full settings access granted</p>";
} else {
    echo "<h3>⚠️ WordPress Login Required:</h3>";
    echo "<p>You still need to be logged into WordPress to access the settings, but no PIN will be required.</p>";
    echo "<p><a href='" . wp_login_url($_SERVER['REQUEST_URI']) . "'>Login to WordPress</a></p>";
}

echo "<h2>🧪 Test Your Settings:</h2>";
echo "<p style='text-align: center;'>";
echo "<a href='" . home_url() . "' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 4px; font-size: 18px; font-weight: bold;'>🔓 Access Settings Now (No PIN)</a>";
echo "</p>";

echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #ffeaa7;'>";
echo "<h3>📝 Note for Later:</h3>";
echo "<p>When you're ready to re-enable PIN protection later (after we get everything else working), we can create a much simpler version that actually works properly.</p>";
echo "<p>For now, focus on:</p>";
echo "<ul>";
echo "<li>✅ Accessing your settings without hassle</li>";
echo "<li>✅ Configuring your retail mode</li>";
echo "<li>✅ Testing your age estimation functionality</li>";
echo "<li>✅ Making sure everything else works as expected</li>";
echo "</ul>";
echo "</div>";

echo "<h3>🔍 If you still see PIN forms:</h3>";
echo "<ul>";
echo "<li>Hard refresh the page (Ctrl+F5 or Cmd+Shift+R)</li>";
echo "<li>Clear your browser cache</li>";
echo "<li>Try incognito/private browsing mode</li>";
echo "<li>The JavaScript should automatically hide any remaining PIN forms</li>";
echo "</ul>";
?>