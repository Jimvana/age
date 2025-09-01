<?php
/**
 * Complete PIN Saving Fix for Age Estimator
 * This file provides multiple solutions to fix the PIN saving issue
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AgeEstimatorPinFix {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Add our own AJAX handler with higher priority
        add_action('wp_ajax_age_estimator_save_user_settings', array($this, 'fixed_save_user_settings'), 5);
        
        // Enqueue our PIN fix JavaScript
        add_action('wp_enqueue_scripts', array($this, 'enqueue_pin_fix_script'));
        
        // Add debug endpoint
        add_action('wp_ajax_debug_pin_save', array($this, 'debug_pin_save'));
    }
    
    public function enqueue_pin_fix_script() {
        global $post;
        
        // Only load on pages with the enhanced settings shortcode
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'age_estimator_settings_enhanced')) {
            wp_enqueue_script(
                'age-estimator-pin-fix',
                AGE_ESTIMATOR_URL . 'js/pin-fix.js',
                array('jquery', 'age-estimator-user-settings-enhanced'),
                AGE_ESTIMATOR_VERSION,
                true
            );
        }
    }
    
    public function fixed_save_user_settings() {
        error_log('=== PIN FIX: Save user settings called ===');
        
        // Only handle retail section
        if (!isset($_POST['section']) || $_POST['section'] !== 'retail') {
            return; // Let original handler deal with other sections
        }
        
        error_log('PIN FIX: Handling retail section');
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_user_settings')) {
            error_log('PIN FIX: Nonce verification failed');
            wp_send_json_error('Invalid security token');
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            error_log('PIN FIX: User not logged in');
            wp_send_json_error('Not logged in');
            return;
        }
        
        $user_id = get_current_user_id();
        $section = 'retail';
        $settings = $_POST['settings'] ?? array();
        
        error_log('PIN FIX: User ID: ' . $user_id);
        error_log('PIN FIX: Settings: ' . print_r($settings, true));
        
        $saved_settings = array();
        
        // Process each setting
        foreach ($settings as $key => $value) {
            // Skip the confirmation field
            if ($key === 'retail_pin_confirm') {
                continue;
            }
            
            $meta_key = 'age_estimator_' . sanitize_key($key);
            
            error_log("PIN FIX: Processing $key = $value");
            
            // Special handling for retail PIN
            if ($key === 'retail_pin') {
                if (!empty($value)) {
                    // Validate PIN format (4 digits)
                    if (!preg_match('/^\d{4}$/', $value)) {
                        error_log('PIN FIX: Invalid PIN format: ' . $value);
                        wp_send_json_error('PIN must be exactly 4 digits');
                        return;
                    }
                    
                    // Hash the PIN before storing
                    $hashed_pin = wp_hash_password($value);
                    $result = update_user_meta($user_id, $meta_key, $hashed_pin);
                    error_log("PIN FIX: Hashed PIN save result: " . ($result ? 'success' : 'failed'));
                    
                    if ($result) {
                        $saved_settings[$key] = '****'; // Don't return actual PIN
                    }
                } else {
                    // Empty PIN - remove it
                    delete_user_meta($user_id, $meta_key);
                    error_log('PIN FIX: Removed empty PIN');
                    $saved_settings[$key] = '';
                }
            } else {
                // Handle other retail settings normally
                if (in_array($key, array('retail_mode_enabled', 'enable_logging', 'email_alerts'))) {
                    // Boolean fields
                    $value = ($value === 'true' || $value === '1' || $value === true) ? '1' : '0';
                } elseif (is_numeric($value)) {
                    // Numeric fields
                    $value = is_float($value + 0) ? floatval($value) : intval($value);
                } else {
                    // Text fields
                    $value = sanitize_text_field($value);
                }
                
                $result = update_user_meta($user_id, $meta_key, $value);
                error_log("PIN FIX: $meta_key save result: " . ($result ? 'success' : 'failed'));
                
                if ($result) {
                    $saved_settings[$key] = $value;
                }
            }
        }
        
        error_log('PIN FIX: Saved settings: ' . print_r($saved_settings, true));
        
        wp_send_json_success(array(
            'message' => 'Retail settings saved successfully!',
            'section' => $section,
            'saved_settings' => $saved_settings
        ));
        
        // Prevent original handler from running
        die();
    }
    
    public function debug_pin_save() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Get current PIN
        $current_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
        
        // Test PIN
        $test_pin = '1234';
        $hashed = wp_hash_password($test_pin);
        
        // Save test PIN
        $save_result = update_user_meta($user_id, 'age_estimator_retail_pin', $hashed);
        
        // Retrieve and verify
        $retrieved = get_user_meta($user_id, 'age_estimator_retail_pin', true);
        $verify_result = wp_check_password($test_pin, $retrieved);
        
        wp_send_json_success(array(
            'current_pin_exists' => !empty($current_pin),
            'test_pin' => $test_pin,
            'save_result' => $save_result,
            'verify_result' => $verify_result,
            'debug_info' => array(
                'user_id' => $user_id,
                'meta_key' => 'age_estimator_retail_pin',
                'hashed_length' => strlen($retrieved)
            )
        ));
    }
}

// Initialize the fix
new AgeEstimatorPinFix();
