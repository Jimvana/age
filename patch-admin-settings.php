<?php
/**
 * Permanent Fix for Admin Settings Form Validation
 * 
 * This script creates a patched version of the admin settings that ensures
 * proper default values are always used in form fields.
 */

// Make sure this is running in WordPress context
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

// Read the current admin-settings.php file
$admin_settings_file = '/Users/video/DevKinsta/public/local-model-age-estin/wp-content/plugins/Age-estimator-live/includes/admin-settings.php';
$content = file_get_contents($admin_settings_file);

if (!$content) {
    die("Could not read admin-settings.php file");
}

// Define the function to ensure default values
$helper_function = '
    /**
     * Get option with guaranteed default value
     * This ensures form fields never have invalid values that break HTML5 validation
     */
    private function get_option_with_default($option_name, $default_value) {
        $value = get_option($option_name, $default_value);
        
        // If value is 0, false, or empty string, use the default
        if ($value === false || $value === 0 || $value === \'\' || $value === \'0\') {
            return $default_value;
        }
        
        return $value;
    }
';

// Add the helper function after the constructor
$pattern = '/(public function __construct\(\) \{.*?\})/s';
$replacement = '$1' . "\n" . $helper_function;
$content = preg_replace($pattern, $replacement, $content);

// Now replace the problematic get_option calls with our helper function

// 1. age_estimator_average_samples
$content = str_replace(
    'value="<?php echo esc_attr(get_option(\'age_estimator_average_samples\', 5)); ?>"',
    'value="<?php echo esc_attr($this->get_option_with_default(\'age_estimator_average_samples\', 5)); ?>"',
    $content
);

// 2. age_estimator_logo_height  
$content = str_replace(
    'value="<?php echo esc_attr(get_option(\'age_estimator_logo_height\', 40)); ?>"',
    'value="<?php echo esc_attr($this->get_option_with_default(\'age_estimator_logo_height\', 40)); ?>"',
    $content
);

// 3. age_estimator_kiosk_display_time
$content = str_replace(
    'value="<?php echo esc_attr(get_option(\'age_estimator_kiosk_display_time\', 5)); ?>"',
    'value="<?php echo esc_attr($this->get_option_with_default(\'age_estimator_kiosk_display_time\', 5)); ?>"',
    $content
);

// 4. age_estimator_minimum_age
$content = str_replace(
    'value="<?php echo esc_attr(get_option(\'age_estimator_minimum_age\', 21)); ?>"',
    'value="<?php echo esc_attr($this->get_option_with_default(\'age_estimator_minimum_age\', 21)); ?>"',
    $content
);

// 5. Also fix other number inputs that might have the same issue
$number_inputs = [
    'age_estimator_challenge_age' => 25,
    'age_estimator_min_face_size' => 150,
    'age_estimator_max_face_size' => 350,
    'age_estimator_cache_duration' => 30,
    'age_estimator_capture_delay' => 500,
    'age_estimator_data_retention_hours' => 0
];

foreach ($number_inputs as $option => $default) {
    $old_pattern = 'value="<?php echo esc_attr(get_option(\'' . $option . '\', ' . $default . ')); ?>"';
    $new_pattern = 'value="<?php echo esc_attr($this->get_option_with_default(\'' . $option . '\', ' . $default . ')); ?>"';
    $content = str_replace($old_pattern, $new_pattern, $content);
}

// Create a backup of the original file
$backup_file = $admin_settings_file . '.backup.' . date('Y-m-d-H-i-s');
copy($admin_settings_file, $backup_file);

// Write the patched content
if (file_put_contents($admin_settings_file, $content)) {
    echo "<h2>✅ Admin Settings File Patched Successfully!</h2>\n";
    echo "<p>The admin-settings.php file has been updated to prevent form validation issues.</p>\n";
    echo "<p><strong>Backup created:</strong> " . basename($backup_file) . "</p>\n";
    echo "<p><strong>Changes made:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Added get_option_with_default() helper method</li>\n";
    echo "<li>Updated form fields to use proper defaults</li>\n";
    echo "<li>Fixed HTML5 validation issues</li>\n";
    echo "</ul>\n";
} else {
    echo "<h2>❌ Failed to Update Admin Settings File</h2>\n";
    echo "<p>Could not write to the admin-settings.php file. Check file permissions.</p>\n";
}
?>
