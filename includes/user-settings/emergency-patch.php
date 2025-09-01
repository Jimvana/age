<?php
/**
 * Emergency patch for missing render methods in AgeEstimatorUserSettingsEnhanced
 * This file adds the missing methods via runtime patching
 * 
 * Add this to your theme's functions.php or as a mu-plugin to fix the issue immediately
 */

// Hook early to patch the class
add_action('plugins_loaded', function() {
    // Check if the class exists
    if (!class_exists('AgeEstimatorUserSettingsEnhanced')) {
        return;
    }
    
    // Create an anonymous class that extends the original and adds missing methods
    class_alias(get_class(new class extends AgeEstimatorUserSettingsEnhanced {
        
        /**
         * Render detection settings fields
         */
        protected function render_detection_fields($settings) {
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
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="multi_face" 
                               <?php checked($settings['multi_face'], true); ?>>
                        <?php _e('Multi-Face Detection', 'age-estimator'); ?>
                    </label>
                </div>
            </div>
            <?php
        }
        
        /**
         * Render retail mode settings fields
         */
        protected function render_retail_fields($settings) {
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
                </div>
            </div>
            <?php
        }
        
        /**
         * Render privacy settings fields
         */
        protected function render_privacy_fields($settings) {
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
                </div>
            </div>
            <?php
        }
        
        /**
         * Render notification settings fields
         */
        protected function render_notification_fields($settings) {
            ?>
            <div class="form-section">
                <h3><?php _e('Sound Settings', 'age-estimator'); ?></h3>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="enable_sounds" 
                               <?php checked($settings['enable_sounds'], true); ?>>
                        <?php _e('Enable Sound Effects', 'age-estimator'); ?>
                    </label>
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
            </div>
            <?php
        }
        
        /**
         * Render advanced settings fields
         */
        protected function render_advanced_fields($settings) {
            ?>
            <div class="form-section">
                <h3><?php _e('Detection Mode', 'age-estimator'); ?></h3>
                
                <div class="form-group">
                    <label for="detection_mode"><?php _e('Detection Mode', 'age-estimator'); ?></label>
                    <select name="detection_mode" id="detection_mode" class="form-control">
                        <option value="local" <?php selected($settings['detection_mode'], 'local'); ?>><?php _e('Local (Face-API.js)', 'age-estimator'); ?></option>
                        <option value="aws" <?php selected($settings['detection_mode'], 'aws'); ?>><?php _e('AWS Rekognition', 'age-estimator'); ?></option>
                        <option value="hybrid" <?php selected($settings['detection_mode'], 'hybrid'); ?>><?php _e('Hybrid', 'age-estimator'); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="hardware_accel" 
                               <?php checked($settings['hardware_accel'], true); ?>>
                        <?php _e('Hardware Acceleration', 'age-estimator'); ?>
                    </label>
                </div>
            </div>
            <?php
        }
        
    }), 'AgeEstimatorUserSettingsEnhancedPatched');
    
}, 5);
