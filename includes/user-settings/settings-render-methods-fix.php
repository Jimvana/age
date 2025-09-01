<?php
/**
 * Missing render methods for AgeEstimatorUserSettingsEnhanced class
 * Add these methods to the class-user-settings-enhanced.php file
 * Insert them after the render_general_fields() method
 */

// Add these methods to the AgeEstimatorUserSettingsEnhanced class:

/**
 * Render detection settings fields
 */
private function render_detection_fields($settings) {
    ?>
    <div class="form-section">
        <h3><?php _e('Detection Settings', 'age-estimator'); ?></h3>
        
        <div class="form-group">
            <label for="face_sensitivity"><?php _e('Face Detection Sensitivity', 'age-estimator'); ?></label>
            <div class="range-group">
                <input type="range" name="face_sensitivity" id="face_sensitivity" 
                       min="0.1" max="0.9" step="0.1" 
                       value="<?php echo esc_attr($settings['face_sensitivity']); ?>" 
                       class="range-slider">
                <span class="range-value"><?php echo esc_html($settings['face_sensitivity']); ?></span>
            </div>
            <p class="form-help"><?php _e('Lower values = more sensitive detection', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label for="detection_interval"><?php _e('Detection Interval (ms)', 'age-estimator'); ?></label>
            <input type="number" name="detection_interval" id="detection_interval" 
                   class="form-control small" 
                   min="100" max="2000" step="100"
                   value="<?php echo esc_attr($settings['detection_interval']); ?>">
            <p class="form-help"><?php _e('How often to check for faces (in milliseconds)', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="face_tracking" 
                       <?php checked($settings['face_tracking'], true); ?>>
                <?php _e('Enable Face Tracking', 'age-estimator'); ?>
            </label>
            <p class="form-help"><?php _e('Track faces between frames for smoother detection', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="multi_face" 
                       <?php checked($settings['multi_face'], true); ?>>
                <?php _e('Multi-Face Detection', 'age-estimator'); ?>
            </label>
            <p class="form-help"><?php _e('Detect and process multiple faces simultaneously', 'age-estimator'); ?></p>
        </div>
    </div>
    
    <div class="form-section">
        <h3><?php _e('Face Size Settings', 'age-estimator'); ?></h3>
        
        <div class="form-group">
            <label for="min_face_size"><?php _e('Minimum Face Size (px)', 'age-estimator'); ?></label>
            <input type="number" name="min_face_size" id="min_face_size" 
                   class="form-control small" 
                   min="50" max="300" 
                   value="<?php echo esc_attr($settings['min_face_size']); ?>">
            <p class="form-help"><?php _e('Minimum face size to detect', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label for="max_face_size"><?php _e('Maximum Face Size (px)', 'age-estimator'); ?></label>
            <input type="number" name="max_face_size" id="max_face_size" 
                   class="form-control small" 
                   min="200" max="800" 
                   value="<?php echo esc_attr($settings['max_face_size']); ?>">
            <p class="form-help"><?php _e('Maximum face size to detect', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label for="averaging_samples"><?php _e('Averaging Samples', 'age-estimator'); ?></label>
            <input type="number" name="averaging_samples" id="averaging_samples" 
                   class="form-control small" 
                   min="1" max="10" 
                   value="<?php echo esc_attr($settings['averaging_samples']); ?>">
            <p class="form-help"><?php _e('Number of samples to average for age estimation', 'age-estimator'); ?></p>
        </div>
    </div>
    <?php
}

/**
 * Render retail mode settings fields
 */
private function render_retail_fields($settings) {
    ?>
    <div class="form-section">
        <h3><?php _e('Retail Compliance', 'age-estimator'); ?></h3>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="retail_mode_enabled" 
                       <?php checked($settings['retail_mode_enabled'], true); ?>>
                <?php _e('Enable Retail Mode', 'age-estimator'); ?>
            </label>
            <p class="form-help"><?php _e('Enable Challenge 25 compliance features', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label for="challenge_age"><?php _e('Challenge Age', 'age-estimator'); ?></label>
            <input type="number" name="challenge_age" id="challenge_age" 
                   class="form-control small" 
                   min="18" max="30" 
                   value="<?php echo esc_attr($settings['challenge_age']); ?>">
            <p class="form-help"><?php _e('Challenge customers who appear under this age', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label for="retail_pin"><?php _e('Staff PIN', 'age-estimator'); ?></label>
            <input type="password" name="retail_pin" id="retail_pin" 
                   class="form-control small" 
                   maxlength="4" pattern="\d{4}"
                   placeholder="****">
            <p class="form-help"><?php _e('4-digit PIN for override access', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label for="retail_pin_confirm"><?php _e('Confirm PIN', 'age-estimator'); ?></label>
            <input type="password" name="retail_pin_confirm" id="retail_pin_confirm" 
                   class="form-control small" 
                   maxlength="4" pattern="\d{4}"
                   placeholder="****">
            <p class="form-help"><?php _e('Re-enter PIN to confirm', 'age-estimator'); ?></p>
        </div>
    </div>
    
    <div class="form-section">
        <h3><?php _e('Logging & Alerts', 'age-estimator'); ?></h3>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="enable_logging" 
                       <?php checked($settings['enable_logging'], true); ?>>
                <?php _e('Enable Transaction Logging', 'age-estimator'); ?>
            </label>
            <p class="form-help"><?php _e('Log all age verification attempts', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="email_alerts" 
                       <?php checked($settings['email_alerts'], true); ?>>
                <?php _e('Email Alerts', 'age-estimator'); ?>
            </label>
            <p class="form-help"><?php _e('Send email alerts for failed verifications', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label for="staff_email"><?php _e('Staff Email', 'age-estimator'); ?></label>
            <input type="email" name="staff_email" id="staff_email" 
                   class="form-control medium" 
                   value="<?php echo esc_attr($settings['staff_email'] ?? ''); ?>"
                   placeholder="manager@example.com">
            <p class="form-help"><?php _e('Email address for compliance alerts', 'age-estimator'); ?></p>
        </div>
    </div>
    <?php
}

/**
 * Render privacy settings fields
 */
private function render_privacy_fields($settings) {
    ?>
    <div class="form-section">
        <h3><?php _e('Privacy Options', 'age-estimator'); ?></h3>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="privacy_mode" 
                       <?php checked($settings['privacy_mode'], true); ?>>
                <?php _e('Privacy Mode', 'age-estimator'); ?>
            </label>
            <p class="form-help"><?php _e('Blur faces in camera preview', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="require_consent" 
                       <?php checked($settings['require_consent'], true); ?>>
                <?php _e('Require Consent', 'age-estimator'); ?>
            </label>
            <p class="form-help"><?php _e('Users must consent before camera activation', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label for="data_retention"><?php _e('Data Retention (days)', 'age-estimator'); ?></label>
            <input type="number" name="data_retention" id="data_retention" 
                   class="form-control small" 
                   min="0" max="365" 
                   value="<?php echo esc_attr($settings['data_retention']); ?>">
            <p class="form-help"><?php _e('How long to retain scan data (0 = no retention)', 'age-estimator'); ?></p>
        </div>
    </div>
    
    <div class="form-section">
        <h3><?php _e('Security Settings', 'age-estimator'); ?></h3>
        
        <div class="form-group">
            <label for="session_timeout"><?php _e('Session Timeout (minutes)', 'age-estimator'); ?></label>
            <input type="number" name="session_timeout" id="session_timeout" 
                   class="form-control small" 
                   min="5" max="60" 
                   value="<?php echo esc_attr($settings['session_timeout']); ?>">
            <p class="form-help"><?php _e('Auto-logout after inactivity', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="two_factor" 
                       <?php checked($settings['two_factor'], true); ?>>
                <?php _e('Two-Factor Authentication', 'age-estimator'); ?>
            </label>
            <p class="form-help"><?php _e('Require 2FA for settings access', 'age-estimator'); ?></p>
        </div>
    </div>
    <?php
}

/**
 * Render notification settings fields
 */
private function render_notification_fields($settings) {
    ?>
    <div class="form-section">
        <h3><?php _e('Sound Settings', 'age-estimator'); ?></h3>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="enable_sounds" 
                       <?php checked($settings['enable_sounds'], true); ?>>
                <?php _e('Enable Sound Effects', 'age-estimator'); ?>
            </label>
            <p class="form-help"><?php _e('Play sounds for pass/fail results', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label for="sound_volume"><?php _e('Sound Volume', 'age-estimator'); ?></label>
            <div class="range-group">
                <input type="range" name="sound_volume" id="sound_volume" 
                       min="0" max="100" 
                       value="<?php echo esc_attr($settings['sound_volume']); ?>" 
                       class="range-slider">
                <span class="range-value"><?php echo esc_html($settings['sound_volume']); ?>%</span>
            </div>
        </div>
        
        <div class="form-group">
            <label for="pass_sound"><?php _e('Pass Sound', 'age-estimator'); ?></label>
            <select name="pass_sound" id="pass_sound" class="form-control">
                <option value="default" <?php selected($settings['pass_sound'], 'default'); ?>><?php _e('Default Chime', 'age-estimator'); ?></option>
                <option value="bell" <?php selected($settings['pass_sound'], 'bell'); ?>><?php _e('Bell', 'age-estimator'); ?></option>
                <option value="success" <?php selected($settings['pass_sound'], 'success'); ?>><?php _e('Success Tone', 'age-estimator'); ?></option>
                <option value="custom" <?php selected($settings['pass_sound'], 'custom'); ?>><?php _e('Custom', 'age-estimator'); ?></option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="fail_sound"><?php _e('Fail Sound', 'age-estimator'); ?></label>
            <select name="fail_sound" id="fail_sound" class="form-control">
                <option value="default" <?php selected($settings['fail_sound'], 'default'); ?>><?php _e('Default Buzzer', 'age-estimator'); ?></option>
                <option value="buzzer" <?php selected($settings['fail_sound'], 'buzzer'); ?>><?php _e('Buzzer', 'age-estimator'); ?></option>
                <option value="warning" <?php selected($settings['fail_sound'], 'warning'); ?>><?php _e('Warning Tone', 'age-estimator'); ?></option>
                <option value="custom" <?php selected($settings['fail_sound'], 'custom'); ?>><?php _e('Custom', 'age-estimator'); ?></option>
            </select>
        </div>
        
        <div class="form-group">
            <button type="button" id="test-notifications" class="btn btn-secondary">
                <?php _e('Test Notifications', 'age-estimator'); ?>
            </button>
        </div>
    </div>
    
    <div class="form-section">
        <h3><?php _e('Visual Feedback', 'age-estimator'); ?></h3>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="screen_flash" 
                       <?php checked($settings['screen_flash'], true); ?>>
                <?php _e('Screen Flash', 'age-estimator'); ?>
            </label>
            <p class="form-help"><?php _e('Flash screen on pass/fail', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label for="success_color"><?php _e('Success Color', 'age-estimator'); ?></label>
            <input type="color" name="success_color" id="success_color" 
                   value="<?php echo esc_attr($settings['success_color']); ?>">
        </div>
        
        <div class="form-group">
            <label for="failure_color"><?php _e('Failure Color', 'age-estimator'); ?></label>
            <input type="color" name="failure_color" id="failure_color" 
                   value="<?php echo esc_attr($settings['failure_color']); ?>">
        </div>
    </div>
    <?php
}

/**
 * Render advanced settings fields
 */
private function render_advanced_fields($settings) {
    ?>
    <div class="form-section">
        <h3><?php _e('Detection Mode', 'age-estimator'); ?></h3>
        
        <div class="form-group">
            <label for="detection_mode"><?php _e('Detection Mode', 'age-estimator'); ?></label>
            <select name="detection_mode" id="detection_mode" class="form-control">
                <option value="local" <?php selected($settings['detection_mode'], 'local'); ?>><?php _e('Local (Face-API.js)', 'age-estimator'); ?></option>
                <option value="aws" <?php selected($settings['detection_mode'], 'aws'); ?>><?php _e('AWS Rekognition', 'age-estimator'); ?></option>
                <option value="hybrid" <?php selected($settings['detection_mode'], 'hybrid'); ?>><?php _e('Hybrid (Local + AWS)', 'age-estimator'); ?></option>
            </select>
            <p class="form-help"><?php _e('Choose face detection backend', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label for="cache_duration"><?php _e('Cache Duration (seconds)', 'age-estimator'); ?></label>
            <input type="number" name="cache_duration" id="cache_duration" 
                   class="form-control small" 
                   min="0" max="3600" 
                   value="<?php echo esc_attr($settings['cache_duration']); ?>">
            <p class="form-help"><?php _e('How long to cache detection results', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="hardware_accel" 
                       <?php checked($settings['hardware_accel'], true); ?>>
                <?php _e('Hardware Acceleration', 'age-estimator'); ?>
            </label>
            <p class="form-help"><?php _e('Use GPU acceleration when available', 'age-estimator'); ?></p>
        </div>
    </div>
    
    <div class="form-section">
        <h3><?php _e('Experimental Features', 'age-estimator'); ?></h3>
        
        <div class="info-card warning">
            <h4><?php _e('Warning', 'age-estimator'); ?></h4>
            <p><?php _e('These features are experimental and may affect performance or accuracy.', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="emotion_detection" 
                       <?php checked($settings['emotion_detection'], true); ?>>
                <?php _e('Emotion Detection', 'age-estimator'); ?>
            </label>
            <p class="form-help"><?php _e('Detect facial expressions and emotions', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="gender_detection" 
                       <?php checked($settings['gender_detection'], true); ?>>
                <?php _e('Gender Detection', 'age-estimator'); ?>
            </label>
            <p class="form-help"><?php _e('Include gender estimation in results', 'age-estimator'); ?></p>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="facial_attributes" 
                       <?php checked($settings['facial_attributes'], true); ?>>
                <?php _e('Facial Attributes', 'age-estimator'); ?>
            </label>
            <p class="form-help"><?php _e('Detect additional facial attributes', 'age-estimator'); ?></p>
        </div>
    </div>
    
    <div class="form-section">
        <h3><?php _e('Data Management', 'age-estimator'); ?></h3>
        
        <div class="button-group">
            <button type="button" id="export-settings" class="btn btn-secondary">
                <?php _e('Export Settings', 'age-estimator'); ?>
            </button>
            <button type="button" id="import-settings" class="btn btn-secondary">
                <?php _e('Import Settings', 'age-estimator'); ?>
            </button>
            <input type="file" id="import-file" accept=".json" style="display: none;">
        </div>
        
        <div class="button-group">
            <button type="button" id="clear-data" class="btn btn-danger">
                <?php _e('Clear Data', 'age-estimator'); ?>
            </button>
            <button type="button" id="download-logs" class="btn btn-secondary">
                <?php _e('Download Logs', 'age-estimator'); ?>
            </button>
        </div>
        
        <div class="button-group">
            <button type="button" id="test-detection" class="btn btn-primary">
                <?php _e('Test Camera Detection', 'age-estimator'); ?>
            </button>
        </div>
    </div>
    <?php
}
