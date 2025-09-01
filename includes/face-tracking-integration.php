<?php
/**
 * Face Tracking Integration for Age Estimator Plugin
 * Add this code to your main plugin file or create a separate include
 */

// Add this to your existing enqueue scripts function
add_action('wp_enqueue_scripts', 'age_estimator_enqueue_face_tracking', 20);

function age_estimator_enqueue_face_tracking() {
    // Only load on pages with age estimator
    if (!has_shortcode(get_post()->post_content, 'age_estimator_photo')) {
        return;
    }
    
    // Get plugin URL
    $plugin_url = plugin_dir_url(__FILE__);
    
    // Enqueue Face Tracker after face-api.js
    wp_enqueue_script(
        'face-tracker',
        $plugin_url . 'js/face-tracker.js',
        array('face-api-js'), // Depends on face-api.js
        '1.0.0',
        true
    );
    
    // Add inline script to enable debug mode in development
    if (defined('WP_DEBUG') && WP_DEBUG) {
        wp_add_inline_script('face-tracker', 'FaceTracker.setDebug(true);');
    }
}

// Optional: Add admin setting for face tracking
add_action('admin_init', 'age_estimator_face_tracking_settings');

function age_estimator_face_tracking_settings() {
    // Add to existing settings section
    add_settings_field(
        'age_estimator_enable_face_tracking',
        'Enable Face Tracking',
        'age_estimator_face_tracking_callback',
        'age-estimator-settings',
        'age_estimator_photo_section'
    );
    
    // Register setting
    register_setting('age-estimator-settings', 'age_estimator_enable_face_tracking');
}

function age_estimator_face_tracking_callback() {
    $enabled = get_option('age_estimator_enable_face_tracking', '1');
    ?>
    <label>
        <input type="checkbox" name="age_estimator_enable_face_tracking" value="1" <?php checked($enabled, '1'); ?>>
        Enable face tracking to cache results and reduce API calls
    </label>
    <p class="description">
        When enabled, recognized faces are cached for 30 seconds to avoid redundant AWS Rekognition API calls.
        This can reduce API usage by 70-90% in continuous monitoring mode.
    </p>
    <?php
}

// Add face tracking status to the parameters
add_filter('age_estimator_photo_params', 'add_face_tracking_params');

function add_face_tracking_params($params) {
    $params['faceTrackingEnabled'] = get_option('age_estimator_enable_face_tracking', '1');
    return $params;
}

// Optional: Add face tracking stats to admin dashboard
add_action('wp_ajax_age_estimator_face_tracking_stats', 'age_estimator_face_tracking_stats');

function age_estimator_face_tracking_stats() {
    check_ajax_referer('age-estimator-nonce', 'nonce');
    
    // This would be called from JavaScript to get current stats
    // In a real implementation, you might store these in a transient or database
    
    wp_send_json_success(array(
        'message' => 'Face tracking is active and reducing API calls'
    ));
}

// Add admin notice about face tracking
add_action('admin_notices', 'age_estimator_face_tracking_notice');

function age_estimator_face_tracking_notice() {
    $screen = get_current_screen();
    
    // Only show on age estimator settings page
    if ($screen->id !== 'settings_page_age-estimator-settings') {
        return;
    }
    
    // Check if face tracking is available
    if (file_exists(plugin_dir_path(__FILE__) . 'js/face-tracker.js')) {
        ?>
        <div class="notice notice-info">
            <p>
                <strong>Face Tracking Available:</strong> 
                The Age Estimator now includes face tracking to reduce AWS Rekognition API calls. 
                Each unique face is only analyzed once while in view, potentially saving 70-90% on API costs.
            </p>
        </div>
        <?php
    }
}

// Shortcode modification for face tracking indicator
add_filter('age_estimator_photo_output', 'add_face_tracking_indicator', 10, 2);

function add_face_tracking_indicator($output, $atts) {
    $face_tracking_enabled = get_option('age_estimator_enable_face_tracking', '1');
    
    if ($face_tracking_enabled === '1') {
        $indicator = '<div class="age-estimator-face-tracking-indicator" style="display: none; padding: 10px; background: #4CAF50; color: white; text-align: center; border-radius: 5px; margin-bottom: 10px;">
            <strong>Face Tracking Active</strong> - Recognized faces are cached to save API calls
        </div>';
        
        $output = $indicator . $output;
    }
    
    return $output;
}
