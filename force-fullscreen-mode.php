<?php
/**
 * Force Fullscreen Mode Only - Age Estimator Live Plugin Modification
 * 
 * This file contains modifications to force the Age Estimator Live plugin 
 * to only use fullscreen display mode, removing inline and modal options.
 * 
 * To apply these changes:
 * 1. Backup your original files first
 * 2. Apply the modifications below to the specified files
 * 3. Test the functionality
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * MODIFICATION 1: Update the render_shortcode function in age-estimator.php
 * 
 * Replace the render_shortcode function around line 560 with this version:
 */
function age_estimator_render_shortcode_modified($atts) {
    // Continuous mode is always enabled in Age Estimator Live
    $default_button_text = __('Start Monitoring', 'age-estimator');
    
    // Parse attributes - FORCE FULLSCREEN MODE ONLY
    $atts = shortcode_atts(array(
        'title' => __('Age Estimator Live', 'age-estimator'),
        'button_text' => $default_button_text,
        'style' => 'fullscreen', // Always force fullscreen mode
        'class' => 'age-estimator-fullscreen-only'
    ), $atts);
    
    // Force fullscreen style regardless of what's passed in
    $atts['style'] = 'fullscreen';
    
    // Check configuration based on mode
    $mode = get_option('age_estimator_mode', 'simple');
    if ($mode === 'aws' && !age_estimator_is_aws_configured()) {
        return '<div class="age-estimator-error">Age Estimator is not configured. Please configure AWS Rekognition in the admin settings.</div>';
    }
    
    // Start output buffering
    ob_start();
    
    // Always use the inline template but modify it for fullscreen behavior
    $template = AGE_ESTIMATOR_PATH . 'templates/photo-inline.php';
    
    if (file_exists($template)) {
        include $template;
    } else {
        // Enhanced fallback template with fullscreen functionality
        ?>
        <div class="age-estimator-photo-container age-estimator-photo-fullscreen-mode <?php echo esc_attr($atts['class']); ?>" 
             data-mode="<?php echo esc_attr($mode); ?>" 
             data-continuous="true"
             data-display-style="fullscreen"
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
            
            <div id="age-estimator-photo-camera" class="age-estimator-photo-camera age-estimator-fullscreen-camera">
                <!-- Fullscreen mode indicator -->
                <div class="age-estimator-fullscreen-indicator" style="display: none;">
                    <span>🖥️ Fullscreen Mode Active</span>
                </div>
                
                <!-- Kiosk Mode Ad Display -->
                <div id="age-estimator-kiosk-display" class="age-estimator-kiosk-display" style="display: none;">
                    <?php if (get_option('age_estimator_kiosk_mode', false) && get_option('age_estimator_kiosk_image', '')): ?>
                        <img src="<?php echo esc_url(get_option('age_estimator_kiosk_image')); ?>" 
                             alt="<?php _e('Advertisement', 'age-estimator'); ?>" 
                             class="age-estimator-kiosk-image" />
                    <?php endif; ?>
                </div>
                
                <!-- Video element for camera feed -->
                <video id="age-estimator-photo-video" style="display: none;" autoplay playsinline></video>
                
                <!-- Canvas for capturing photo -->
                <canvas id="age-estimator-photo-canvas" style="display: none;"></canvas>
                
                <!-- Overlay canvas for results -->
                <canvas id="age-estimator-photo-overlay" style="display: none;"></canvas>
                
                <!-- Photo preview (hidden) -->
                <img id="age-estimator-photo-preview" style="display: none;" alt="<?php _e('Captured photo', 'age-estimator'); ?>">
                
                <!-- Banner Ad for Fullscreen Mode -->
                <?php if (get_option('age_estimator_enable_banner_ad', false) && get_option('age_estimator_banner_ad_image', '')): ?>
                    <div id="age-estimator-banner-ad" class="age-estimator-banner-ad" style="display: none;">
                        <?php 
                        $banner_link = get_option('age_estimator_banner_ad_link', '');
                        $banner_image = get_option('age_estimator_banner_ad_image', '');
                        $banner_height = get_option('age_estimator_banner_ad_height', 100);
                        $banner_position = get_option('age_estimator_banner_ad_position', 'bottom');
                        $banner_opacity = get_option('age_estimator_banner_ad_opacity', 0.9);
                        
                        if (!empty($banner_link)): ?>
                            <a href="<?php echo esc_url($banner_link); ?>" target="_blank" rel="noopener noreferrer" class="age-estimator-banner-link">
                                <img src="<?php echo esc_url($banner_image); ?>" 
                                     alt="<?php _e('Advertisement', 'age-estimator'); ?>" 
                                     class="age-estimator-banner-image"
                                     data-height="<?php echo esc_attr($banner_height); ?>"
                                     data-position="<?php echo esc_attr($banner_position); ?>"
                                     data-opacity="<?php echo esc_attr($banner_opacity); ?>" />
                            </a>
                        <?php else: ?>
                            <img src="<?php echo esc_url($banner_image); ?>" 
                                 alt="<?php _e('Advertisement', 'age-estimator'); ?>" 
                                 class="age-estimator-banner-image"
                                 data-height="<?php echo esc_attr($banner_height); ?>"
                                 data-position="<?php echo esc_attr($banner_position); ?>"
                                 data-opacity="<?php echo esc_attr($banner_opacity); ?>" />
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="age-estimator-photo-controls age-estimator-fullscreen-controls">
                <button id="age-estimator-photo-start-camera" class="age-estimator-photo-button primary fullscreen-button">
                    🖥️ <?php echo esc_html($atts['button_text']); ?> (Fullscreen)
                </button>
                
                <button id="age-estimator-photo-stop-camera" class="age-estimator-photo-button danger fullscreen-button" style="display: none;">
                    ⏹️ <?php _e('Stop Monitor', 'age-estimator'); ?>
                </button>
                
                <!-- Fullscreen toggle button -->
                <button id="age-estimator-fullscreen-toggle" class="age-estimator-photo-button secondary fullscreen-toggle" style="display: none;">
                    ⛶ <?php _e('Enter Fullscreen', 'age-estimator'); ?>
                </button>
            </div>
            
            <div id="age-estimator-photo-loading" class="age-estimator-photo-loading" style="display: none;">
                <div class="spinner"></div>
                <p><?php _e('Analyzing...', 'age-estimator'); ?></p>
            </div>
            
            <div id="age-estimator-photo-result" class="age-estimator-photo-result age-estimator-fullscreen-result">
                <!-- Results will be displayed here -->
            </div>
            
            <!-- Fullscreen enhancement notice -->
            <div class="age-estimator-fullscreen-notice" style="margin-top: 15px; padding: 10px; background: #e7f3ff; border: 1px solid #2196f3; border-radius: 4px; font-size: 14px;">
                <strong>🖥️ Fullscreen Mode:</strong> <?php _e('This Age Estimator is optimized for fullscreen experience. Click the camera area or use the fullscreen button for the best results.', 'age-estimator'); ?>
            </div>
        </div>
        
        <!-- Add CSS for fullscreen mode -->
        <style>
        .age-estimator-photo-fullscreen-mode {
            position: relative;
            max-width: 100%;
        }
        
        .age-estimator-fullscreen-camera {
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .age-estimator-fullscreen-camera:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .age-estimator-fullscreen-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(33, 150, 243, 0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            z-index: 1000;
        }
        
        .age-estimator-fullscreen-controls .fullscreen-button {
            position: relative;
            min-width: 180px;
        }
        
        .age-estimator-fullscreen-notice {
            text-align: center;
        }
        
        /* Fullscreen mode styles */
        .age-estimator-photo-container:-webkit-full-screen {
            width: 100vw !important;
            height: 100vh !important;
            background: #000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .age-estimator-photo-container:-moz-full-screen {
            width: 100vw !important;
            height: 100vh !important;
            background: #000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .age-estimator-photo-container:fullscreen {
            width: 100vw !important;
            height: 100vh !important;
            background: #000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        /* Show fullscreen indicator when in fullscreen */
        .age-estimator-photo-container:-webkit-full-screen .age-estimator-fullscreen-indicator,
        .age-estimator-photo-container:-moz-full-screen .age-estimator-fullscreen-indicator,
        .age-estimator-photo-container:fullscreen .age-estimator-fullscreen-indicator {
            display: block !important;
        }
        
        /* Hide the notice in fullscreen */
        .age-estimator-photo-container:-webkit-full-screen .age-estimator-fullscreen-notice,
        .age-estimator-photo-container:-moz-full-screen .age-estimator-fullscreen-notice,
        .age-estimator-photo-container:fullscreen .age-estimator-fullscreen-notice {
            display: none !important;
        }
        </style>
        
        <!-- Add JavaScript for fullscreen functionality -->
        <script>
        jQuery(document).ready(function($) {
            console.log('🖥️ Age Estimator: Fullscreen-only mode active');
            
            // Auto-trigger fullscreen functionality
            let fullscreenAvailable = false;
            let container = $('.age-estimator-photo-container')[0];
            
            if (container) {
                // Check if fullscreen is supported
                fullscreenAvailable = !!(container.requestFullscreen || 
                                       container.webkitRequestFullscreen || 
                                       container.mozRequestFullScreen || 
                                       container.msRequestFullscreen);
                
                if (fullscreenAvailable) {
                    $('#age-estimator-fullscreen-toggle').show();
                    
                    // Add double-click to camera area for fullscreen
                    $('#age-estimator-photo-camera').on('dblclick', function() {
                        toggleFullscreen();
                    });
                    
                    // Add fullscreen button click handler
                    $('#age-estimator-fullscreen-toggle').on('click', function() {
                        toggleFullscreen();
                    });
                    
                    // Monitor fullscreen changes
                    $(document).on('fullscreenchange webkitfullscreenchange mozfullscreenchange MSFullscreenChange', function() {
                        updateFullscreenButton();
                    });
                }
            }
            
            function toggleFullscreen() {
                if (!document.fullscreenElement && 
                    !document.webkitFullscreenElement && 
                    !document.mozFullScreenElement && 
                    !document.msFullscreenElement) {
                    // Enter fullscreen
                    if (container.requestFullscreen) {
                        container.requestFullscreen();
                    } else if (container.webkitRequestFullscreen) {
                        container.webkitRequestFullscreen();
                    } else if (container.mozRequestFullScreen) {
                        container.mozRequestFullScreen();
                    } else if (container.msRequestFullscreen) {
                        container.msRequestFullscreen();
                    }
                } else {
                    // Exit fullscreen
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    } else if (document.webkitExitFullscreen) {
                        document.webkitExitFullscreen();
                    } else if (document.mozCancelFullScreen) {
                        document.mozCancelFullScreen();
                    } else if (document.msExitFullscreen) {
                        document.msExitFullscreen();
                    }
                }
            }
            
            function updateFullscreenButton() {
                const isFullscreen = !!(document.fullscreenElement || 
                                      document.webkitFullscreenElement || 
                                      document.mozFullScreenElement || 
                                      document.msFullscreenElement);
                
                $('#age-estimator-fullscreen-toggle').text(
                    isFullscreen ? '⛶ Exit Fullscreen' : '⛶ Enter Fullscreen'
                );
            }
            
            // Add tooltip for double-click functionality
            $('#age-estimator-photo-camera').attr('title', 'Double-click to toggle fullscreen mode');
        });
        </script>
        <?php
    }
    
    // Return output
    return ob_get_clean();
}

/**
 * MODIFICATION 2: Update admin settings to hide display style options
 * 
 * Add this to your functions.php or create a separate plugin file:
 */
function age_estimator_hide_display_style_admin_setting() {
    ?>
    <style>
        /* Hide the display style setting in admin */
        tr:has(select[name="age_estimator_display_style"]),
        tr:has(label[for="age_estimator_display_style"]) {
            display: none !important;
        }
        
        /* Add notice about fullscreen-only mode */
        .age-estimator-display-notice {
            background: #e7f3ff;
            border: 1px solid #2196f3;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Add notice about fullscreen-only mode in admin
        if ($('select[name="age_estimator_display_style"]').length) {
            $('select[name="age_estimator_display_style"]').closest('tr').before(
                '<tr><td colspan="2"><div class="age-estimator-display-notice">' +
                '<strong>🖥️ Display Mode:</strong> This Age Estimator is configured for <strong>Fullscreen Mode Only</strong>. ' +
                'Inline and Modal popup options have been removed for a better user experience.' +
                '</div></td></tr>'
            );
        }
    });
    </script>
    <?php
}

// Hook to add admin styling
add_action('admin_head', 'age_estimator_hide_display_style_admin_setting');

/**
 * MODIFICATION 3: Force fullscreen mode in database
 * 
 * Run this once to update the database setting:
 */
function age_estimator_force_fullscreen_in_database() {
    // Force the display style to fullscreen
    update_option('age_estimator_display_style', 'fullscreen');
    
    // Add a flag to indicate fullscreen-only mode
    update_option('age_estimator_fullscreen_only_mode', true);
    
    return true;
}

// Uncomment the line below to run the database update:
// add_action('init', 'age_estimator_force_fullscreen_in_database');

/**
 * MODIFICATION 4: Override shortcode attributes to always use fullscreen
 * 
 * Add this filter to force fullscreen regardless of shortcode attributes:
 */
function age_estimator_force_fullscreen_shortcode_atts($atts) {
    // Always force fullscreen style
    $atts['style'] = 'fullscreen';
    return $atts;
}

// Uncomment to activate the filter:
// add_filter('shortcode_atts_age_estimator', 'age_estimator_force_fullscreen_shortcode_atts');

/**
 * MODIFICATION 5: Enqueue fullscreen-specific assets
 */
function age_estimator_enqueue_fullscreen_assets() {
    // Only on pages with the shortcode
    global $post;
    if (!is_a($post, 'WP_Post') || (!has_shortcode($post->post_content, 'age_estimator') && !has_shortcode($post->post_content, 'age_estimator_photo'))) {
        return;
    }
    
    // Enqueue fullscreen banner ad styles and scripts
    wp_enqueue_style(
        'age-estimator-fullscreen-banner',
        AGE_ESTIMATOR_URL . 'css/fullscreen-banner-ad.css',
        array('age-estimator'),
        AGE_ESTIMATOR_VERSION
    );
    
    wp_enqueue_script(
        'age-estimator-fullscreen-banner',
        AGE_ESTIMATOR_URL . 'js/fullscreen-banner-ad.js',
        array('jquery', 'age-estimator'),
        AGE_ESTIMATOR_VERSION,
        true
    );
}

add_action('wp_enqueue_scripts', 'age_estimator_enqueue_fullscreen_assets', 99);

/**
 * INSTALLATION INSTRUCTIONS:
 * 
 * 1. BACKUP YOUR FILES FIRST!
 * 
 * 2. Replace the render_shortcode function in age-estimator.php:
 *    - Find the public function render_shortcode($atts) around line 560
 *    - Replace it with the age_estimator_render_shortcode_modified function above
 *    - Make sure to keep it as a class method: public function render_shortcode($atts)
 * 
 * 3. Update the admin settings to remove display style options:
 *    - The CSS and JavaScript above will hide the setting in admin
 *    - Users won't be able to change from fullscreen mode
 * 
 * 4. Run the database update:
 *    - Uncomment the add_action line for age_estimator_force_fullscreen_in_database
 *    - Load any page on your site once to update the database
 *    - Comment it back out
 * 
 * 5. Test the functionality:
 *    - All age estimator shortcodes should now display in fullscreen mode
 *    - Double-click the camera area to enter fullscreen
 *    - Use the fullscreen button to toggle fullscreen mode
 * 
 * 6. Optional: Add shortcode attribute override:
 *    - Uncomment the filter to force fullscreen even if shortcode specifies different style
 */

?>
