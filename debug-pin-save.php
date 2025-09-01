<?php
/**
 * Quick PHP fix for PIN saving issue
 * Add this temporarily to debug PIN saving
 */

// Add to your theme's functions.php or create as a plugin file

add_action('wp_ajax_age_estimator_save_user_settings', 'debug_pin_save', 5);

function debug_pin_save() {
    error_log('=== PIN SAVE DEBUG START ===');
    error_log('POST data: ' . print_r($_POST, true));
    
    // Check if this is a retail section save
    if (isset($_POST['section']) && $_POST['section'] === 'retail') {
        error_log('Retail section save detected');
        
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_user_settings')) {
            error_log('Nonce verification failed for PIN save');
            return; // Let the original handler deal with this
        }
        
        // Check user
        if (!is_user_logged_in()) {
            error_log('User not logged in for PIN save');
            return;
        }
        
        $user_id = get_current_user_id();
        $settings = $_POST['settings'];
        
        error_log('PIN save for user: ' . $user_id);
        error_log('Settings: ' . print_r($settings, true));
        
        // Check if PIN is in the data
        if (isset($settings['retail_pin'])) {
            $pin = $settings['retail_pin'];
            error_log('PIN received: ' . $pin);
            
            if (!empty($pin)) {
                // Save PIN directly (without hashing for now to test)
                $result = update_user_meta($user_id, 'age_estimator_retail_pin', $pin);
                error_log('PIN save result: ' . ($result ? 'success' : 'failed'));
                
                // Also try saving hashed version
                $hashed = wp_hash_password($pin);
                $result2 = update_user_meta($user_id, 'age_estimator_retail_pin_hashed', $hashed);
                error_log('Hashed PIN save result: ' . ($result2 ? 'success' : 'failed'));
                
                // Verify the save
                $retrieved = get_user_meta($user_id, 'age_estimator_retail_pin', true);
                error_log('Retrieved PIN: ' . $retrieved);
            } else {
                error_log('PIN is empty');
            }
        } else {
            error_log('No PIN in settings data');
        }
    }
    
    error_log('=== PIN SAVE DEBUG END ===');
    
    // Don't prevent the original handler from running
}
