<?php
/**
 * Banner Ad Assets Loader for Age Estimator Live
 * Enqueues CSS and JavaScript files for the fullscreen banner ad feature
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add enqueue_scripts functionality through hooks
add_action('wp_enqueue_scripts', 'age_estimator_enqueue_banner_assets');

function age_estimator_enqueue_banner_assets() {
    // Only load on pages where the shortcode is present
    global $post;
    
    if (!is_a($post, 'WP_Post') || 
        (!has_shortcode($post->post_content, 'age_estimator') && 
         !has_shortcode($post->post_content, 'age_estimator_photo'))) {
        return;
    }
    
    // Enqueue fullscreen banner ad styles
    wp_enqueue_style(
        'age-estimator-fullscreen-banner',
        AGE_ESTIMATOR_URL . 'css/fullscreen-banner-ad.css',
        array(),
        AGE_ESTIMATOR_VERSION
    );
    
    // Enqueue enhanced banner ad fix styles
    wp_enqueue_style(
        'age-estimator-banner-fix',
        AGE_ESTIMATOR_URL . 'css/banner-ad-fix.css',
        array('age-estimator-fullscreen-banner'),
        AGE_ESTIMATOR_VERSION
    );
    
    // Enqueue fullscreen banner ad JavaScript
    wp_enqueue_script(
        'age-estimator-fullscreen-banner',
        AGE_ESTIMATOR_URL . 'js/fullscreen-banner-ad.js',
        array('jquery'),
        AGE_ESTIMATOR_VERSION,
        true
    );
    
    // Localize script with banner settings
    $banner_params = array(
        'enabled' => get_option('age_estimator_enable_banner_ad', false) ? '1' : '0',
        'image' => get_option('age_estimator_banner_ad_image', ''),
        'link' => get_option('age_estimator_banner_ad_link', ''),
        'height' => get_option('age_estimator_banner_ad_height', 100),
        'position' => get_option('age_estimator_banner_ad_position', 'bottom'),
        'opacity' => get_option('age_estimator_banner_ad_opacity', 0.9),
        'debug' => defined('WP_DEBUG') && WP_DEBUG ? '1' : '0'
    );
    
    wp_localize_script('age-estimator-fullscreen-banner', 'ageEstimatorBannerParams', $banner_params);
}

// Add admin notice about banner ad feature
add_action('admin_notices', 'age_estimator_banner_ad_admin_notice');

function age_estimator_banner_ad_admin_notice() {
    $screen = get_current_screen();
    
    if (!$screen || strpos($screen->id, 'age-estimator') === false) {
        return;
    }
    
    if (get_option('age_estimator_enable_banner_ad', false)) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <strong><?php _e('Age Estimator Banner Ad:', 'age-estimator'); ?></strong> 
                <?php _e('Fullscreen banner ad feature is now active! The banner will appear when users enter fullscreen mode.', 'age-estimator'); ?>
            </p>
        </div>
        <?php
    }
}

// Add CSS and JS integrity check
add_action('wp_footer', 'age_estimator_banner_ad_integrity_check');

function age_estimator_banner_ad_integrity_check() {
    global $post;
    
    if (!is_a($post, 'WP_Post') || 
        (!has_shortcode($post->post_content, 'age_estimator') && 
         !has_shortcode($post->post_content, 'age_estimator_photo'))) {
        return;
    }
    
    if (!get_option('age_estimator_enable_banner_ad', false)) {
        return;
    }
    
    ?>
    <script>
    // Banner Ad Integrity Check
    jQuery(document).ready(function($) {
        console.log('🎯 Age Estimator Banner Ad: Integrity check...');
        
        // Check if banner ad files are loaded
        var bannerAdLoaded = typeof window.ageEstimatorBannerAd !== 'undefined';
        var bannerElement = document.getElementById('age-estimator-banner-ad');
        var bannerEnabled = <?php echo get_option('age_estimator_enable_banner_ad', false) ? 'true' : 'false'; ?>;
        
        if (bannerEnabled) {
            if (bannerElement) {
                console.log('✅ Banner Ad: Element found');
            } else {
                console.warn('⚠️ Banner Ad: Element not found in DOM');
            }
            
            if (bannerAdLoaded) {
                console.log('✅ Banner Ad: JavaScript loaded successfully');
            } else {
                console.warn('⚠️ Banner Ad: JavaScript not loaded');
            }
        }
        
        // Debug info
        if (bannerEnabled && <?php echo defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false'; ?>) {
            console.log('🔧 Banner Ad Debug Info:', {
                enabled: bannerEnabled,
                elementExists: !!bannerElement,
                scriptLoaded: bannerAdLoaded,
                image: '<?php echo esc_js(get_option('age_estimator_banner_ad_image', '')); ?>',
                height: <?php echo get_option('age_estimator_banner_ad_height', 100); ?>,
                position: '<?php echo get_option('age_estimator_banner_ad_position', 'bottom'); ?>',
                opacity: <?php echo get_option('age_estimator_banner_ad_opacity', 0.9); ?>
            });
        }
    });
    </script>
    <?php
}
