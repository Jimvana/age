<?php
/**
 * Kiosk Mode Admin Settings Additions
 * Add this code to your existing admin-settings.php file
 */

// Add to register_settings() method after existing settings:

// Kiosk Mode Settings
register_setting('age_estimator_settings', 'age_estimator_kiosk_mode', array(
    'type' => 'boolean',
    'sanitize_callback' => 'rest_sanitize_boolean',
    'default' => false
));

register_setting('age_estimator_settings', 'age_estimator_kiosk_image', array(
    'type' => 'string',
    'sanitize_callback' => 'esc_url_raw',
    'default' => ''
));

register_setting('age_estimator_settings', 'age_estimator_kiosk_display_time', array(
    'type' => 'integer',
    'sanitize_callback' => 'absint',
    'default' => 5
));

// Add this HTML to the settings_page() method, after the Logo section:
?>

<!-- Kiosk Mode Section -->
<div class="settings-section">
    <h3><?php _e('Kiosk Mode Settings', 'age-estimator'); ?></h3>
    
    <div class="form-group">
        <label>
            <input type="checkbox" name="age_estimator_kiosk_mode" value="1" <?php checked(get_option('age_estimator_kiosk_mode', false)); ?> />
            <?php _e('Enable Kiosk Mode', 'age-estimator'); ?>
        </label>
        <p class="description"><?php _e('Display an advertisement image when no face is detected on screen.', 'age-estimator'); ?></p>
    </div>
    
    <div class="form-group kiosk-settings" style="<?php echo get_option('age_estimator_kiosk_mode', false) ? '' : 'display: none;'; ?>">
        <label for="age_estimator_kiosk_image"><?php _e('Advertisement Image URL', 'age-estimator'); ?></label>
        <input type="url" id="age_estimator_kiosk_image" name="age_estimator_kiosk_image" 
               value="<?php echo esc_attr(get_option('age_estimator_kiosk_image', '')); ?>" 
               class="regular-text" />
        <button type="button" class="button button-secondary" id="upload-kiosk-image"><?php _e('Upload Image', 'age-estimator'); ?></button>
        <p class="description"><?php _e('Enter the URL of the image to display when no face is detected.', 'age-estimator'); ?></p>
        
        <?php if (get_option('age_estimator_kiosk_image')): ?>
            <div class="kiosk-image-preview" style="margin-top: 10px;">
                <img src="<?php echo esc_url(get_option('age_estimator_kiosk_image')); ?>" style="max-width: 300px; height: auto; border: 1px solid #ddd;" />
            </div>
        <?php endif; ?>
    </div>
    
    <div class="form-group kiosk-settings" style="<?php echo get_option('age_estimator_kiosk_mode', false) ? '' : 'display: none;'; ?>">
        <label for="age_estimator_kiosk_display_time"><?php _e('Display Time After Detection (seconds)', 'age-estimator'); ?></label>
        <input type="number" id="age_estimator_kiosk_display_time" name="age_estimator_kiosk_display_time" 
               value="<?php echo esc_attr(get_option('age_estimator_kiosk_display_time', 5)); ?>" 
               min="1" max="60" class="small-text" />
        <p class="description"><?php _e('How long to show the age result before returning to the ad (1-60 seconds).', 'age-estimator'); ?></p>
    </div>
</div>