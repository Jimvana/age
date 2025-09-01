<?php
/**
 * Activation script for enhanced settings
 * Run this file once to activate the enhanced settings system
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

echo "<h2>Activating Enhanced Settings...</h2>";

// Check if enhanced settings class exists
$enhanced_file = AGE_ESTIMATOR_PATH . 'includes/user-settings/class-user-settings-enhanced.php';
if (file_exists($enhanced_file)) {
    echo "✓ Enhanced settings file found<br>";
    
    // Update option to use enhanced settings
    update_option('age_estimator_use_enhanced_settings', true);
    echo "✓ Enhanced settings activated<br>";
    
    // Create a test page with the shortcode
    $page_title = 'Age Estimator Settings';
    $page_content = '[age_estimator_settings_enhanced theme="light" layout="sidebar" show_stats="true" allow_export="true"]';
    
    // Check if page already exists
    $page = get_page_by_title($page_title);
    
    if (!$page) {
        $page_data = array(
            'post_title'    => $page_title,
            'post_content'  => $page_content,
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_author'   => get_current_user_id(),
        );
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id) {
            echo "✓ Settings page created successfully<br>";
            echo "Page URL: <a href='" . get_permalink($page_id) . "' target='_blank'>" . get_permalink($page_id) . "</a><br>";
        }
    } else {
        echo "✓ Settings page already exists<br>";
        echo "Page URL: <a href='" . get_permalink($page->ID) . "' target='_blank'>" . get_permalink($page->ID) . "</a><br>";
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
    echo "✓ Permalinks refreshed<br>";
    
    echo "<br><strong>Enhanced settings activated successfully!</strong><br>";
    echo "<br>You can now:<br>";
    echo "1. Visit the settings page created above<br>";
    echo "2. Add the shortcode to any page: [age_estimator_settings_enhanced]<br>";
    echo "3. Or use the original shortcode: [age_estimator_user_settings]<br>";
    
} else {
    echo "✗ Enhanced settings file not found at: $enhanced_file<br>";
}
