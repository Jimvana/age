<?php
/**
 * Kiosk Mode Template Additions
 * Add this to your photo-inline.php template
 */
?>

<!-- Add this to the opening div tag attributes -->
<div class="age-estimator-photo-container age-estimator-photo-inline <?php echo esc_attr($atts['class']); ?>" 
     data-mode="<?php echo esc_attr(get_option('age_estimator_mode', 'simple')); ?>" 
     data-continuous="true"
     data-kiosk-mode="<?php echo get_option('age_estimator_kiosk_mode', false) ? 'true' : 'false'; ?>"
     data-kiosk-image="<?php echo esc_url(get_option('age_estimator_kiosk_image', '')); ?>"
     data-kiosk-display-time="<?php echo esc_attr(get_option('age_estimator_kiosk_display_time', 5)); ?>">

<!-- Add this after the camera placeholder div and before the video element -->
<!-- Kiosk Mode Ad Display -->
<?php if (get_option('age_estimator_kiosk_mode', false) && get_option('age_estimator_kiosk_image', '')): ?>
<div id="age-estimator-kiosk-display" class="age-estimator-kiosk-display">
    <img src="<?php echo esc_url(get_option('age_estimator_kiosk_image')); ?>" 
         alt="<?php _e('Advertisement', 'age-estimator'); ?>" 
         class="age-estimator-kiosk-image" />
</div>
<?php endif; ?>