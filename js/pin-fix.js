/**
 * Quick Fix for Staff PIN Saving Issue
 * Age Estimator Settings - PIN Validation Fix
 * 
 * Add this script to override the problematic PIN validation
 */

(function($) {
    'use strict';
    
    // Override the handleFormSubmit method to fix PIN validation
    $(document).ready(function() {
        // Wait for the original settings manager to load
        setTimeout(function() {
            if (typeof window.ageEstimatorSettings !== 'undefined') {
                
                // Store original method
                const originalHandleFormSubmit = window.ageEstimatorSettings.handleFormSubmit;
                
                // Override with fixed version
                window.ageEstimatorSettings.handleFormSubmit = function(e) {
                    e.preventDefault();
                    
                    const $form = $(e.currentTarget);
                    const section = $form.data('section');
                    const formData = this.getFormData($form);
                    
                    console.log('Form submission for section:', section);
                    console.log('Form data:', formData);
                    
                    // Validate form
                    if (!this.validateForm($form)) {
                        console.log('Form validation failed');
                        return;
                    }
                    
                    // FIXED: Improved PIN validation for retail section
                    if (section === 'retail') {
                        const retailEnabled = formData.retail_mode_enabled === true || 
                                           formData.retail_mode_enabled === 'true' || 
                                           formData.retail_mode_enabled === '1';
                        
                        const hasPin = formData.retail_pin && formData.retail_pin.length > 0;
                        const hasConfirmPin = formData.retail_pin_confirm && formData.retail_pin_confirm.length > 0;
                        
                        console.log('PIN validation:', {
                            retailEnabled,
                            hasPin,
                            hasConfirmPin,
                            pin: formData.retail_pin,
                            confirmPin: formData.retail_pin_confirm
                        });
                        
                        // Only validate PIN confirmation if both fields have values
                        if (hasPin && hasConfirmPin) {
                            if (formData.retail_pin !== formData.retail_pin_confirm) {
                                this.showAlert('PIN confirmation does not match', 'error');
                                console.log('PIN mismatch');
                                return;
                            }
                        }
                        
                        // If only one PIN field is filled, show error
                        if (hasPin && !hasConfirmPin) {
                            this.showAlert('Please confirm your PIN', 'error');
                            return;
                        }
                        
                        // Remove confirm PIN from data being sent (don't save it)
                        delete formData.retail_pin_confirm;
                        
                        console.log('PIN validation passed, proceeding with save');
                    }
                    
                    this.saveSettings(section, formData);
                };
                
                console.log('PIN validation fix applied');
                
            } else {
                console.log('Settings manager not found, retrying...');
                // Retry after another second
                setTimeout(arguments.callee, 1000);
            }
        }, 1000);
    });
    
    // Also add a debug function to test PIN saving directly
    window.debugPinSave = function() {
        if (typeof ageEstimatorEnhanced === 'undefined') {
            console.log('ageEstimatorEnhanced not available');
            return;
        }
        
        const testData = {
            retail_mode_enabled: true,
            challenge_age: 25,
            enable_logging: true,
            retail_pin: '1234'  // Test PIN
        };
        
        console.log('Testing PIN save with data:', testData);
        
        $.ajax({
            url: ageEstimatorEnhanced.ajaxUrl,
            type: 'POST',
            data: {
                action: 'age_estimator_save_user_settings',
                nonce: ageEstimatorEnhanced.nonce,
                section: 'retail',
                settings: testData
            },
            success: function(response) {
                console.log('PIN save test result:', response);
                if (response.success) {
                    alert('PIN save test PASSED!');
                } else {
                    alert('PIN save test FAILED: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.log('PIN save test error:', error);
                alert('PIN save test ERROR: ' + error);
            }
        });
    };
    
    // Add debugging for form data collection
    window.debugFormData = function() {
        const $form = $('.settings-form[data-section="retail"]');
        if ($form.length === 0) {
            console.log('Retail form not found');
            return;
        }
        
        const data = {};
        
        $form.find('input, select, textarea').each(function() {
            const $input = $(this);
            const name = $input.attr('name');
            
            if (!name) return;
            
            if ($input.attr('type') === 'checkbox') {
                data[name] = $input.is(':checked');
            } else if ($input.attr('type') === 'radio') {
                if ($input.is(':checked')) {
                    data[name] = $input.val();
                }
            } else {
                data[name] = $input.val();
            }
            
            console.log(`Field ${name} (${$input.attr('type')}):`, data[name]);
        });
        
        console.log('Complete form data:', data);
        return data;
    };
    
})(jQuery);
