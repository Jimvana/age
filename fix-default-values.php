<?php
/**
 * Fix Default Values - Run this once to fix the settings issue
 * 
 * This script fixes the form validation issue by setting proper default values
 * for settings that are currently showing as 0 in the admin panel.
 */

// Make sure this is running in WordPress context
if (!defined('ABSPATH')) {
    // If running from command line or direct access, include WordPress
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

// Define the default values that should be set
$default_values = array(
    'age_estimator_average_samples' => 5,
    'age_estimator_logo_height' => 40,
    'age_estimator_kiosk_display_time' => 5,
    'age_estimator_minimum_age' => 21,
    'age_estimator_challenge_age' => 25,
    'age_estimator_min_face_size' => 150,
    'age_estimator_max_face_size' => 350,
    'age_estimator_face_sensitivity' => 0.4,
    'age_estimator_cache_duration' => 30,
    'age_estimator_capture_delay' => 500,
    'age_estimator_cooldown_period' => 5000,
    'age_estimator_sound_volume' => 0.7,
    'age_estimator_data_retention_hours' => 0,
    'age_estimator_log_retention_days' => 90,
    'age_estimator_mode' => 'simple',
    'age_estimator_aws_region' => 'us-east-1',
    'age_estimator_display_style' => 'inline'
);

echo "<h2>Fixing Age Estimator Default Values</h2>\n";
echo "<p>This will fix the form validation issues by setting proper default values.</p>\n";

$updated = 0;
$already_set = 0;

foreach ($default_values as $option_name => $default_value) {
    $current_value = get_option($option_name);
    
    // Only update if the current value is 0, empty, or false (but not if it's a valid non-zero value)
    if ($current_value === false || $current_value === 0 || $current_value === '' || $current_value === '0') {
        $result = update_option($option_name, $default_value);
        if ($result) {
            echo "✅ Updated <strong>$option_name</strong> from '$current_value' to '$default_value'<br>\n";
            $updated++;
        } else {
            echo "⚠️ Failed to update <strong>$option_name</strong><br>\n";
        }
    } else {
        echo "ℹ️ <strong>$option_name</strong> already has value '$current_value' - not changing<br>\n";
        $already_set++;
    }
}

echo "<hr>\n";
echo "<p><strong>Summary:</strong></p>\n";
echo "<ul>\n";
echo "<li>✅ Updated: $updated settings</li>\n";
echo "<li>ℹ️ Already set: $already_set settings</li>\n";
echo "</ul>\n";

if ($updated > 0) {
    echo "<p style='color: green;'><strong>Success!</strong> The form validation issues should now be fixed. You can now save your settings in the admin panel.</p>\n";
} else {
    echo "<p style='color: blue;'><strong>Info:</strong> All settings already had proper values. If you're still having issues, there might be another cause.</p>\n";
}

echo "<p><strong>Next steps:</strong></p>\n";
echo "<ol>\n";
echo "<li>Go to your WordPress admin panel</li>\n";
echo "<li>Navigate to Age Estimator → Settings</li>\n";
echo "<li>Try saving your settings - it should work now!</li>\n";
echo "<li>You can delete this fix file after confirming everything works</li>\n";
echo "</ol>\n";

echo "<hr>\n";
echo "<p><em>You can safely delete this file after running it once.</em></p>\n";
?>
