/**
 * User Settings JavaScript
 * Age Estimator Plugin
 */

(function($) {
    'use strict';
    
    // Wait for DOM ready
    $(document).ready(function() {
        
        // Handle retail mode toggle
        $('#retail_mode_enabled').on('change', function() {
            if ($(this).is(':checked')) {
                $('.retail-mode-settings').slideDown();
            } else {
                $('.retail-mode-settings').slideUp();
                // Clear PIN fields when disabled
                $('#retail_pin, #retail_pin_confirm').val('');
            }
        });
        
        // Handle age gating toggle
        $('#age_gating_enabled').on('change', function() {
            if ($(this).is(':checked')) {
                $('.age-gating-settings').slideDown();
            } else {
                $('.age-gating-settings').slideUp();
            }
        });
        
        // Validate PIN input (only numbers)
        $('#retail_pin, #retail_pin_confirm').on('input', function() {
            var value = $(this).val();
            var numbers_only = value.replace(/[^0-9]/g, '');
            if (value !== numbers_only) {
                $(this).val(numbers_only);
            }
        });
        
        // Handle form submission
        $('#age-estimator-user-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $spinner = $('.spinner');
            var $message = $('.settings-message');
            var $submitButton = $form.find('button[type="submit"]');
            
            // Clear previous messages
            $message.removeClass('success error').hide().text('');
            
            // Validate retail PIN if enabled
            if ($('#retail_mode_enabled').is(':checked')) {
                var pin = $('#retail_pin').val();
                var pin_confirm = $('#retail_pin_confirm').val();
                
                if (pin || pin_confirm) {
                    if (pin.length !== 4) {
                        showMessage($message, 'error', ageEstimatorUserSettings.messages.pinRequired);
                        return;
                    }
                    
                    if (pin !== pin_confirm) {
                        showMessage($message, 'error', ageEstimatorUserSettings.messages.pinMismatch);
                        return;
                    }
                }
            }
            
            // Collect form data
            var formData = {
                action: 'age_estimator_save_user_settings',
                nonce: ageEstimatorUserSettings.nonce,
                face_tracking_distance: $('#face_tracking_distance').val(),
                retail_mode_enabled: $('#retail_mode_enabled').is(':checked') ? 1 : 0,
                retail_pin: $('#retail_pin').val(),
                retail_pin_confirm: $('#retail_pin_confirm').val(),
                age_gating_enabled: $('#age_gating_enabled').is(':checked') ? 1 : 0,
                age_gating_threshold: $('#age_gating_threshold').val()
            };
            
            // Show spinner and disable button
            $spinner.addClass('is-active');
            $submitButton.prop('disabled', true);
            
            // Send AJAX request
            $.ajax({
                url: ageEstimatorUserSettings.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        showMessage($message, 'success', response.data.message);
                        
                        // Clear PIN fields after successful save
                        $('#retail_pin, #retail_pin_confirm').val('');
                    } else {
                        showMessage($message, 'error', response.data || ageEstimatorUserSettings.messages.saveError);
                    }
                },
                error: function() {
                    showMessage($message, 'error', ageEstimatorUserSettings.messages.saveError);
                },
                complete: function() {
                    $spinner.removeClass('is-active');
                    $submitButton.prop('disabled', false);
                }
            });
        });
        
        /**
         * Show message helper
         */
        function showMessage($element, type, message) {
            $element
                .removeClass('success error')
                .addClass(type)
                .text(message)
                .fadeIn();
            
            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(function() {
                    $element.fadeOut();
                }, 5000);
            }
        }
        
        /**
         * Validate user PIN (for use in the main age estimator)
         */
        window.validateUserRetailPin = function(pin, callback) {
            $.ajax({
                url: ageEstimatorUserSettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'age_estimator_validate_user_pin',
                    nonce: ageEstimatorUserSettings.nonce,
                    pin: pin
                },
                success: function(response) {
                    if (response.success) {
                        callback(true, response.data);
                    } else {
                        callback(false, response.data);
                    }
                },
                error: function() {
                    callback(false, 'Error validating PIN');
                }
            });
        };
        
        /**
         * Get user settings (for use in the main age estimator)
         */
        window.getUserAgeEstimatorSettings = function() {
            var settings = {
                faceTrackingDistance: parseFloat($('#face_tracking_distance').val()) || 0.4,
                retailModeEnabled: $('#retail_mode_enabled').is(':checked'),
                ageGatingEnabled: $('#age_gating_enabled').is(':checked'),
                ageGatingThreshold: parseInt($('#age_gating_threshold').val()) || 18
            };
            
            // Get from user meta if not on settings page
            if (!$('#age-estimator-user-settings-form').length && window.ageEstimatorUserMeta) {
                settings = window.ageEstimatorUserMeta;
            }
            
            return settings;
        };
    });
    
})(jQuery);
