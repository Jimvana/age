<?php
/**
 * EXAMPLE: Complete photo-inline.php with Kiosk Mode
 * This shows how your template should look after adding kiosk mode
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="age-estimator-photo-container age-estimator-photo-inline <?php echo esc_attr($atts['class']); ?>" 
     data-mode="<?php echo esc_attr(get_option('age_estimator_mode', 'simple')); ?>" 
     data-continuous="true"
     data-kiosk-mode="<?php echo get_option('age_estimator_kiosk_mode', false) ? 'true' : 'false'; ?>"
     data-kiosk-image="<?php echo esc_url(get_option('age_estimator_kiosk_image', '')); ?>"
     data-kiosk-display-time="<?php echo esc_attr(get_option('age_estimator_kiosk_display_time', 5)); ?>">
    <?php 
    // Display logo if enabled
    $use_logo = get_option('age_estimator_use_logo', false);
    $logo_url = get_option('age_estimator_logo_url', '');
    $logo_height = get_option('age_estimator_logo_height', 40);
    
    if ($use_logo && !empty($logo_url)) {
        ?>
        <div class="age-estimator-logo-container" style="text-align: center; margin-bottom: 20px;">
            <img src="<?php echo esc_url($logo_url); ?>" 
                 alt="<?php _e('Logo', 'age-estimator'); ?>" 
                 style="max-height: <?php echo esc_attr($logo_height); ?>px; width: auto; display: inline-block;" 
                 class="age-estimator-logo" />
        </div>
        <?php
    }
    ?>
    <div id="age-estimator-photo-camera" class="age-estimator-photo-camera">
        <div class="age-estimator-photo-camera-placeholder">
            <!-- Placeholder removed to fix camera display -->
        </div>
        
        <!-- Kiosk Mode Ad Display -->
        <?php if (get_option('age_estimator_kiosk_mode', false) && get_option('age_estimator_kiosk_image', '')): ?>
        <div id="age-estimator-kiosk-display" class="age-estimator-kiosk-display">
            <img src="<?php echo esc_url(get_option('age_estimator_kiosk_image')); ?>" 
                 alt="<?php _e('Advertisement', 'age-estimator'); ?>" 
                 class="age-estimator-kiosk-image" />
        </div>
        <?php endif; ?>
        
        <!-- Video element for camera feed -->
        <video id="age-estimator-photo-video" style="display: none;" autoplay playsinline></video>
        
        <!-- Canvas for capturing photo -->
        <canvas id="age-estimator-photo-canvas" style="display: none;"></canvas>
        
        <!-- Overlay canvas for results -->
        <canvas id="age-estimator-photo-overlay" style="display: none;"></canvas>
        
        <!-- Photo preview (hidden) -->
        <img id="age-estimator-photo-preview" style="display: none;" alt="<?php _e('Captured photo', 'age-estimator'); ?>">
    </div>
    
    <div class="age-estimator-photo-controls">
        <button id="age-estimator-photo-start-camera" class="age-estimator-photo-button primary">
            <?php echo esc_html($atts['button_text']); ?>
        </button>
        
        <button id="age-estimator-photo-stop-camera" class="age-estimator-photo-button danger" style="display: none;">
            <?php _e('Stop Monitor', 'age-estimator'); ?>
        </button>
    </div>
    
    <div id="age-estimator-photo-loading" class="age-estimator-photo-loading" style="display: none;">
        <div class="spinner"></div>
        <p><?php _e('Analyzing...', 'age-estimator'); ?></p>
    </div>
    
    <div id="age-estimator-photo-result" class="age-estimator-photo-result">
        <!-- Results will be displayed here -->
    </div>
</div>