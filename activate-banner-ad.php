<?php
/**
 * Banner Ad Feature Activation
 * Age Estimator Live Plugin
 * 
 * Run this file once to activate the banner ad feature
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include the banner ad assets loading
require_once AGE_ESTIMATOR_PATH . 'includes/banner-ad-assets.php';

// Add admin notice about successful activation
add_action('admin_notices', function() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p>
            <strong><?php _e('Banner Ad Feature Activated!', 'age-estimator'); ?></strong><br>
            <?php _e('The fullscreen banner ad feature has been successfully added to your Age Estimator Live plugin.', 'age-estimator'); ?><br>
            <?php _e('You can now configure banner ads in the Age Estimator settings under "Display Options > Fullscreen Banner Ad".', 'age-estimator'); ?>
        </p>
        <p>
            <strong><?php _e('How to use:', 'age-estimator'); ?></strong>
        </p>
        <ol>
            <li><?php _e('Go to Age Estimator Settings > Display Options', 'age-estimator'); ?></li>
            <li><?php _e('Scroll down to "Fullscreen Banner Ad" section', 'age-estimator'); ?></li>
            <li><?php _e('Enable "Show banner ad in fullscreen mode"', 'age-estimator'); ?></li>
            <li><?php _e('Upload your banner image (recommended: 1200x100 pixels)', 'age-estimator'); ?></li>
            <li><?php _e('Configure position, height, opacity, and optional click URL', 'age-estimator'); ?></li>
            <li><?php _e('Save settings and test by double-clicking the camera view to enter fullscreen', 'age-estimator'); ?></li>
        </ol>
        <p>
            <em><?php _e('The banner will only appear when users are in fullscreen mode on the camera view.', 'age-estimator'); ?></em>
        </p>
    </div>
    <?php
}, 1);

// Log successful activation
error_log('Age Estimator Banner Ad: Feature activated successfully');

// Return success message
echo "✅ Banner Ad feature has been successfully activated!\n";
echo "📋 Configuration: Age Estimator Settings > Display Options > Fullscreen Banner Ad\n";
echo "🎯 Test: Double-click the camera view to enter fullscreen mode\n";
