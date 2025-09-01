/**
 * Quick Fix Script for Missing PIN Fields in Retail Mode
 * This adds the PIN fields back to the retail section
 */

// Add this to your theme's functions.php or run as a mu-plugin

add_action('wp_footer', function() {
    // Only run on pages with the enhanced settings shortcode
    global $post;
    if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'age_estimator_settings_enhanced')) {
        return;
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Check if retail section exists but PIN fields are missing
        const $retailForm = $('.settings-form[data-section="retail"]');
        if ($retailForm.length > 0) {
            console.log('🔍 Checking for PIN fields in retail section...');
            
            // Check if PIN fields already exist
            const $pinField = $retailForm.find('[name="retail_pin"]');
            const $confirmField = $retailForm.find('[name="retail_pin_confirm"]');
            
            if ($pinField.length === 0 && $confirmField.length === 0) {
                console.log('❌ PIN fields missing! Adding them now...');
                
                // Find the challenge age field to insert PIN fields after
                const $challengeAge = $retailForm.find('[name="challenge_age"]').closest('.form-group');
                
                if ($challengeAge.length > 0) {
                    // Create PIN fields HTML
                    const pinFieldsHtml = `
                        <div class="form-group pin-field-group">
                            <label for="retail_pin">Staff PIN</label>
                            <input type="password" name="retail_pin" id="retail_pin" 
                                   class="form-control small" 
                                   maxlength="4" pattern="\\d{4}"
                                   placeholder="****"
                                   autocomplete="new-password">
                            <p class="form-help">4-digit PIN for override access</p>
                        </div>
                        
                        <div class="form-group pin-confirm-field-group">
                            <label for="retail_pin_confirm">Confirm PIN</label>
                            <input type="password" name="retail_pin_confirm" id="retail_pin_confirm" 
                                   class="form-control small" 
                                   maxlength="4" pattern="\\d{4}"
                                   placeholder="****"
                                   autocomplete="new-password">
                            <p class="form-help">Re-enter PIN to confirm</p>
                        </div>
                    `;
                    
                    // Insert PIN fields after challenge age
                    $challengeAge.after(pinFieldsHtml);
                    
                    console.log('✅ PIN fields added successfully!');
                    
                    // Set up conditional visibility
                    updatePinFieldVisibility();
                    
                    // Watch for retail mode checkbox changes
                    $retailForm.find('[name="retail_mode_enabled"]').on('change', updatePinFieldVisibility);
                    
                } else {
                    console.log('⚠️ Could not find challenge age field to insert PIN fields');
                }
            } else {
                console.log('✅ PIN fields already exist');
                updatePinFieldVisibility();
            }
        }
        
        function updatePinFieldVisibility() {
            const $retailForm = $('.settings-form[data-section="retail"]');
            const $retailMode = $retailForm.find('[name="retail_mode_enabled"]');
            const $pinGroups = $retailForm.find('.pin-field-group, .pin-confirm-field-group');
            
            if ($retailMode.is(':checked')) {
                $pinGroups.slideDown(200);
                console.log('🔓 PIN fields shown (retail mode enabled)');
            } else {
                $pinGroups.slideUp(200);
                console.log('🔒 PIN fields hidden (retail mode disabled)');
            }
        }
    });
    </script>
    
    <style>
    .pin-field-group, .pin-confirm-field-group {
        margin: 15px 0;
    }
    
    .pin-field-group input, .pin-confirm-field-group input {
        max-width: 150px;
    }
    
    .form-control.small {
        width: auto;
        display: inline-block;
        min-width: 100px;
    }
    
    .form-help {
        font-size: 0.9em;
        color: #666;
        margin-top: 5px;
    }
    </style>
    <?php
});
