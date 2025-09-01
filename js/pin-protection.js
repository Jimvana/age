/**
 * PIN Protection JavaScript for Age Estimator Settings
 * Handles PIN verification and session management
 * 
 * @package AgeEstimator
 * @since 2.0
 */

(function($) {
    'use strict';
    
    class SettingsPinProtection {
        constructor() {
            this.config = window.ageEstimatorPinProtection || {};
            this.sessionCheckInterval = null;
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.startSessionMonitoring();
            this.initPinInput();
        }
        
        bindEvents() {
            // PIN form submission
            $(document).on('submit', '#pin-access-form', this.handlePinSubmit.bind(this));
            
            // Lock settings button
            $(document).on('click', '#lock-settings-btn', this.lockSettings.bind(this));
            
            // Temporary access button (for first-time setup)
            $(document).on('click', '#temporary-access-btn', this.grantTemporaryAccess.bind(this));
            
            // PIN input formatting
            $(document).on('input', '.pin-input', this.formatPinInput.bind(this));
            
            // Auto-submit on 4 digits
            $(document).on('input', '#settings-pin', this.autoSubmitPin.bind(this));
            
            // Clear error messages on focus
            $(document).on('focus', '.pin-input', this.clearMessages.bind(this));
            
            // Handle escape key to lock settings
            $(document).on('keydown', this.handleKeydown.bind(this));
        }
        
        initPinInput() {
            // Focus PIN input when page loads
            setTimeout(() => {
                $('#settings-pin').focus();
            }, 500);
            
            // Add visual feedback for PIN input
            $('.pin-input').on('input', function() {
                const $this = $(this);
                const value = $this.val();
                
                // Add visual indicators
                $this.removeClass('valid invalid');
                if (value.length === 4 && /^\d{4}$/.test(value)) {
                    $this.addClass('valid');
                } else if (value.length > 0) {
                    $this.addClass('invalid');
                }
            });
        }
        
        formatPinInput(event) {
            const $input = $(event.target);
            const value = $input.val();
            
            // Only allow digits
            const cleaned = value.replace(/\D/g, '');
            $input.val(cleaned);
            
            // Limit to 4 digits
            if (cleaned.length > 4) {
                $input.val(cleaned.substring(0, 4));
            }
        }
        
        autoSubmitPin(event) {
            const $input = $(event.target);
            const value = $input.val();
            
            // Auto-submit when 4 digits are entered
            if (value.length === 4 && /^\d{4}$/.test(value)) {
                setTimeout(() => {
                    $('#pin-access-form').submit();
                }, 100); // Small delay for better UX
            }
        }
        
        handlePinSubmit(event) {
            event.preventDefault();
            
            const $form = $(event.target);
            const $submitBtn = $form.find('.pin-submit');
            const $pinInput = $form.find('#settings-pin');
            const pin = $pinInput.val();
            
            // Validate PIN format
            if (!/^\d{4}$/.test(pin)) {
                this.showMessage(this.config.messages.pinRequired, 'error');
                $pinInput.focus().addClass('invalid');
                return;
            }
            
            // Show loading state
            this.setLoadingState($submitBtn, true);
            
            // Send verification request
            const data = {
                action: 'age_estimator_verify_settings_pin',
                pin: pin,
                nonce: this.config.nonce
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: this.handlePinSuccess.bind(this),
                error: this.handlePinError.bind(this),
                complete: () => {
                    this.setLoadingState($submitBtn, false);
                }
            });
        }
        
        handlePinSuccess(response) {
            if (response.success) {
                this.showMessage('✓ ' + response.data.message, 'success');
                
                // Reload page to show settings
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                this.handlePinError(response);
            }
        }
        
        handlePinError(response) {
            let message = this.config.messages.errorGeneric;
            
            if (response.data && response.data.message) {
                message = response.data.message;
            }
            
            this.showMessage(message, 'error');
            
            // Clear PIN input and focus it
            $('#settings-pin').val('').focus().addClass('invalid');
            
            // Add shake animation
            $('.pin-container').addClass('shake');
            setTimeout(() => {
                $('.pin-container').removeClass('shake');
            }, 600);
        }
        
        lockSettings() {
            if (!confirm('Are you sure you want to lock the settings? You will need to enter your PIN again.')) {
                return;
            }
            
            const data = {
                action: 'age_estimator_lock_settings',
                nonce: this.config.nonce
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.showMessage('🔒 ' + response.data.message, 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                },
                error: () => {
                    this.showMessage(this.config.messages.errorGeneric, 'error');
                }
            });
        }
        
        grantTemporaryAccess() {
            // Hide the PIN setup form and show temporary settings access
            $('.age-estimator-pin-setup').fadeOut(300, function() {
                // Create temporary notice
                const tempNotice = `
                    <div class="temporary-access-notice">
                        <div class="notice-header">
                            <div class="notice-icon">⚠️</div>
                            <h3>Temporary Access Granted</h3>
                        </div>
                        <div class="notice-content">
                            <p><strong>Remember:</strong> Set your PIN in the Retail Mode section before leaving this page!</p>
                            <button id="remind-later" class="btn btn-sm btn-outline">Set PIN Later</button>
                        </div>
                    </div>
                `;
                
                // Add notice and show settings
                $('body').prepend(tempNotice);
                
                // Load the actual settings (simulate the normal flow)
                window.location.href = window.location.href + '?temp_access=1';
            });
        }
        
        startSessionMonitoring() {
            if (!this.config.isLoggedIn) {
                return;
            }
            
            // Check session every 5 minutes
            this.sessionCheckInterval = setInterval(() => {
                this.checkSession();
            }, 5 * 60 * 1000);
            
            // Check on focus
            $(window).on('focus', () => {
                this.checkSession();
            });
        }
        
        checkSession() {
            const data = {
                action: 'age_estimator_check_pin_session',
                nonce: this.config.nonce
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success && !response.data.valid) {
                        this.handleSessionExpired();
                    }
                }
            });
        }
        
        handleSessionExpired() {
            // Clear session check interval
            if (this.sessionCheckInterval) {
                clearInterval(this.sessionCheckInterval);
            }
            
            // Show session expired message
            this.showSessionExpiredModal();
        }
        
        showSessionExpiredModal() {
            const modal = `
                <div class="pin-session-expired-modal">
                    <div class="modal-overlay"></div>
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="modal-icon">⏰</div>
                            <h3>Session Expired</h3>
                        </div>
                        <div class="modal-body">
                            <p>For security, your session has expired. Please enter your PIN to continue.</p>
                        </div>
                        <div class="modal-actions">
                            <button id="reauth-btn" class="btn btn-primary">Re-authenticate</button>
                            <button id="logout-btn" class="btn btn-outline">Logout</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modal);
            
            // Handle modal actions
            $('#reauth-btn').on('click', () => {
                window.location.reload();
            });
            
            $('#logout-btn').on('click', () => {
                window.location.href = '<?php echo wp_logout_url(); ?>';
            });
        }
        
        setLoadingState($button, loading) {
            if (loading) {
                $button.prop('disabled', true);
                $button.find('.btn-text').hide();
                $button.find('.btn-loading').show();
            } else {
                $button.prop('disabled', false);
                $button.find('.btn-text').show();
                $button.find('.btn-loading').hide();
            }
        }
        
        showMessage(message, type = 'info') {
            // Remove existing messages
            $('.pin-message').remove();
            
            // Create new message
            const $message = $(`
                <div class="pin-message ${type}">
                    ${message}
                </div>
            `);
            
            // Insert message
            $('.pin-header').after($message);
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(() => {
                    $message.fadeOut(300, () => $message.remove());
                }, 3000);
            }
        }
        
        clearMessages() {
            $('.pin-message.error').fadeOut(200, function() {
                $(this).remove();
            });
        }
        
        handleKeydown(event) {
            // Escape key to lock settings (if settings are shown)
            if (event.key === 'Escape' && $('.settings-security-bar').length > 0) {
                $('#lock-settings-btn').click();
            }
            
            // Enter key on PIN form
            if (event.key === 'Enter' && $('#pin-access-form').length > 0) {
                event.preventDefault();
                $('#pin-access-form').submit();
            }
        }
        
        // Utility method to add shake animation
        shake($element) {
            $element.addClass('shake');
            setTimeout(() => {
                $element.removeClass('shake');
            }, 600);
        }
    }
    
    // Initialize when document is ready
    $(document).ready(function() {
        new SettingsPinProtection();
    });
    
    // Add CSS animations via JavaScript if not loaded
    $(document).ready(function() {
        const shakeCSS = `
            @keyframes shake {
                0%, 20%, 40%, 60%, 80% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
            }
            .shake { animation: shake 0.6s; }
            
            .pin-input.valid { border-color: #28a745; }
            .pin-input.invalid { border-color: #dc3545; }
            
            .btn-loading .spinner {
                display: inline-block;
                width: 12px;
                height: 12px;
                border: 2px solid #ffffff;
                border-radius: 50%;
                border-top-color: transparent;
                animation: spin 1s ease-in-out infinite;
            }
            
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        `;
        
        if (!$('#pin-protection-animations').length) {
            $('<style id="pin-protection-animations">').text(shakeCSS).appendTo('head');
        }
    });
    
})(jQuery);
