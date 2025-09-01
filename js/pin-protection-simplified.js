/**
 * Simplified PIN Protection JavaScript 
 * Works with the new plain number PIN system
 * 
 * @package AgeEstimator
 * @since 2.0 - Simplified
 */

(function($) {
    'use strict';
    
    class SimplifiedPinProtection {
        constructor() {
            console.log('🔐 Simplified PIN Protection loaded');
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.focusPinInput();
        }
        
        bindEvents() {
            // Override any existing form handlers
            $(document).off('submit', '#pin-access-form');
            $(document).off('submit', '#settings-pin-form');
            
            // Handle both possible form IDs
            $(document).on('submit', '#pin-access-form, #settings-pin-form', this.handlePinSubmit.bind(this));
            
            // PIN input formatting - only allow digits
            $(document).on('input', '.pin-input, #settings-pin', this.formatPinInput.bind(this));
            
            // Auto-submit when 4 digits entered
            $(document).on('input', '#settings-pin', this.maybeAutoSubmit.bind(this));
            
            // Clear messages when user starts typing
            $(document).on('focus', '.pin-input, #settings-pin', this.clearMessages.bind(this));
            
            // Lock settings button
            $(document).on('click', '#lock-settings-btn', this.lockSettings.bind(this));
        }
        
        focusPinInput() {
            setTimeout(() => {
                const $input = $('#settings-pin, .pin-input').first();
                if ($input.length) {
                    $input.focus();
                    console.log('📱 PIN input focused');
                }
            }, 500);
        }
        
        formatPinInput(event) {
            const $input = $(event.target);
            let value = $input.val();
            
            // Only allow digits
            value = value.replace(/\D/g, '');
            
            // Max 4 digits
            if (value.length > 4) {
                value = value.substring(0, 4);
            }
            
            $input.val(value);
            
            // Visual feedback
            if (value.length === 4) {
                $input.addClass('complete').removeClass('incomplete');
            } else {
                $input.addClass('incomplete').removeClass('complete');
            }
        }
        
        maybeAutoSubmit(event) {
            const $input = $(event.target);
            const value = $input.val();
            
            // Auto-submit when exactly 4 digits
            if (value.length === 4 && /^\d{4}$/.test(value)) {
                console.log('📱 Auto-submitting PIN form');
                setTimeout(() => {
                    $input.closest('form').submit();
                }, 200);
            }
        }
        
        handlePinSubmit(event) {
            event.preventDefault();
            console.log('🔐 PIN form submitted');
            
            const $form = $(event.target);
            const $submitBtn = $form.find('button[type="submit"], .pin-submit').first();
            const $pinInput = $form.find('#settings-pin, .pin-input').first();
            const pin = $pinInput.val();
            
            // Validate PIN
            if (!pin || !/^\d{4}$/.test(pin)) {
                this.showMessage('Please enter exactly 4 digits', 'error');
                $pinInput.focus();
                return;
            }
            
            console.log('📱 Submitting PIN:', pin);
            
            // Show loading state
            this.setLoadingState($submitBtn, true);
            this.showMessage('Verifying PIN...', 'info');
            
            // Simple AJAX call - no complex nonce handling needed
            $.ajax({
                url: window.ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'age_estimator_verify_settings_pin',
                    pin: pin,
                    nonce: this.getNonce() // Try to get nonce, but server will be lenient
                },
                success: (response) => {
                    console.log('✅ PIN verification response:', response);
                    this.handlePinSuccess(response);
                },
                error: (xhr, status, error) => {
                    console.error('❌ PIN verification error:', error, xhr.responseText);
                    this.handlePinError(xhr, status, error);
                },
                complete: () => {
                    this.setLoadingState($submitBtn, false);
                }
            });
        }
        
        handlePinSuccess(response) {
            if (response.success) {
                this.showMessage('✅ ' + response.data.message, 'success');
                console.log('🔓 PIN verified, reloading page...');
                
                // Reload page to show settings
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                this.handlePinError(response);
            }
        }
        
        handlePinError(response) {
            let message = 'PIN verification failed. Please try again.';
            
            if (response.data && response.data.message) {
                message = response.data.message;
            } else if (response.responseJSON && response.responseJSON.data && response.responseJSON.data.message) {
                message = response.responseJSON.data.message;
            }
            
            console.error('❌ PIN error:', message);
            this.showMessage(message, 'error');
            
            // Clear PIN and refocus
            $('#settings-pin, .pin-input').val('').focus();
        }
        
        lockSettings() {
            if (!confirm('Lock the settings? You will need to enter your PIN again.')) {
                return;
            }
            
            $.ajax({
                url: window.ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'age_estimator_lock_settings',
                    nonce: this.getNonce()
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage('🔒 Settings locked', 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    }
                }
            });
        }
        
        getNonce() {
            // Try multiple sources for nonce
            if (window.ageEstimatorPinProtection && window.ageEstimatorPinProtection.nonce) {
                return window.ageEstimatorPinProtection.nonce;
            }
            
            const $nonceField = $('input[name="pin_nonce"], input[name="_wpnonce"]').first();
            if ($nonceField.length) {
                return $nonceField.val();
            }
            
            return 'simplified-pin'; // Fallback - server will be lenient
        }
        
        setLoadingState($button, loading) {
            if (!$button.length) return;
            
            if (loading) {
                $button.prop('disabled', true);
                $button.data('original-text', $button.text());
                $button.html('⏳ Verifying...');
            } else {
                $button.prop('disabled', false);
                $button.html($button.data('original-text') || 'Submit');
            }
        }
        
        showMessage(message, type = 'info') {
            console.log(`📱 Message (${type}):`, message);
            
            // Remove existing messages
            $('.pin-message, .message, .notice').remove();
            
            // Create new message
            const $message = $(`
                <div class="pin-message pin-message-${type}">
                    ${message}
                </div>
            `);
            
            // Find a good place to insert the message
            const $container = $('.pin-header, .pin-container, .settings-header, form').first();
            if ($container.length) {
                $container.after($message);
            } else {
                $('body').prepend($message);
            }
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(() => {
                    $message.fadeOut(300, () => $message.remove());
                }, 3000);
            }
        }
        
        clearMessages() {
            $('.pin-message.pin-message-error').fadeOut(200, function() {
                $(this).remove();
            });
        }
    }
    
    // Initialize when document ready
    $(document).ready(function() {
        // Wait a bit for other scripts to load
        setTimeout(() => {
            new SimplifiedPinProtection();
        }, 100);
    });
    
    // Add CSS for PIN messages and input states
    $(document).ready(function() {
        const css = `
            .pin-message {
                padding: 12px 16px;
                margin: 15px 0;
                border-radius: 4px;
                font-weight: 500;
                border: 1px solid;
            }
            
            .pin-message-success {
                background: #d4edda;
                color: #155724;
                border-color: #c3e6cb;
            }
            
            .pin-message-error {
                background: #f8d7da;
                color: #721c24;
                border-color: #f5c6cb;
            }
            
            .pin-message-info {
                background: #d1ecf1;
                color: #0c5460;
                border-color: #bee5eb;
            }
            
            .pin-input.complete, #settings-pin.complete {
                border-color: #28a745 !important;
                box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
            }
            
            .pin-input.incomplete, #settings-pin.incomplete {
                border-color: #6c757d !important;
            }
            
            .pin-input, #settings-pin {
                font-family: monospace;
                letter-spacing: 2px;
                text-align: center;
                font-size: 18px !important;
                font-weight: bold;
            }
        `;
        
        if (!$('#simplified-pin-styles').length) {
            $('<style id="simplified-pin-styles">').text(css).appendTo('head');
        }
    });
    
})(jQuery);
