<?php
/**
 * Emergency PIN Fields Visibility Fix
 * Add this to force PIN fields to always be visible
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add JavaScript to force PIN fields visibility
add_action('wp_footer', function() {
    global $post;
    
    // Only on pages with the enhanced settings shortcode
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'age_estimator_settings_enhanced')) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            console.log('🔧 PIN Fields Fix: Ensuring PIN fields are visible');
            
            // Force PIN fields to be visible every few seconds
            function ensurePinFieldsVisible() {
                const $retailSection = $('.settings-panel[id="retail"]');
                if ($retailSection.length) {
                    const $pinFields = $retailSection.find('[name="retail_pin"], [name="retail_pin_confirm"]');
                    const $pinGroups = $pinFields.closest('.form-group');
                    
                    console.log('🔧 PIN fields found:', $pinFields.length);
                    console.log('🔧 PIN groups found:', $pinGroups.length);
                    
                    // Make sure they're visible
                    $pinGroups.show();
                    $pinFields.prop('disabled', false);
                    
                    // Check if retail mode checkbox exists and is checked
                    const $retailMode = $retailSection.find('[name="retail_mode_enabled"]');
                    if ($retailMode.length) {
                        console.log('🔧 Retail mode checkbox found, checked:', $retailMode.is(':checked'));
                        
                        // If retail mode is enabled, ensure PIN fields are visible
                        if ($retailMode.is(':checked')) {
                            $pinGroups.show();
                        }
                        
                        // Add change event to toggle PIN fields
                        $retailMode.off('change.pinfix').on('change.pinfix', function() {
                            console.log('🔧 Retail mode toggled:', $(this).is(':checked'));
                            if ($(this).is(':checked')) {
                                $pinGroups.slideDown(200);
                            } else {
                                $pinGroups.slideUp(200);
                            }
                        });
                    } else {
                        // If checkbox not found, just show PIN fields
                        console.log('🔧 Retail mode checkbox not found, showing PIN fields anyway');
                        $pinGroups.show();
                    }
                }
            }
            
            // Run immediately and every 2 seconds for 10 seconds
            ensurePinFieldsVisible();
            let attempts = 0;
            const fixInterval = setInterval(function() {
                ensurePinFieldsVisible();
                attempts++;
                if (attempts >= 5) {
                    clearInterval(fixInterval);
                    console.log('🔧 PIN Fields Fix: Stopped automatic checking');
                }
            }, 2000);
            
            // Also run when navigating to retail section
            $(document).on('click', '.nav-link[href="#retail"]', function() {
                setTimeout(ensurePinFieldsVisible, 300);
            });
        });
        </script>
        <?php
    }
});

// Add CSS to ensure PIN fields are visible
add_action('wp_head', function() {
    global $post;
    
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'age_estimator_settings_enhanced')) {
        ?>
        <style>
        .settings-panel[id="retail"] [name="retail_pin"],
        .settings-panel[id="retail"] [name="retail_pin_confirm"] {
            display: block !important;
        }
        
        .settings-panel[id="retail"] [name="retail_pin"].form-group,
        .settings-panel[id="retail"] [name="retail_pin_confirm"].form-group {
            display: block !important;
        }
        
        /* Make sure PIN field containers are visible */
        .form-group:has([name="retail_pin"]),
        .form-group:has([name="retail_pin_confirm"]) {
            display: block !important;
        }
        </style>
        <?php
    }
});
