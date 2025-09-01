/**
 * Admin User PIN Management JavaScript
 * Handles AJAX interactions for PIN management in user profiles
 */

(function($) {
    'use strict';
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        initUserPinManagement();
    });
    
    function initUserPinManagement() {
        // Reset PIN button
        $('#reset-user-pin').on('click', function(e) {
            e.preventDefault();
            
            const userId = $(this).data('user-id');
            const button = $(this);
            
            if (!confirm(ageEstimatorUserPin.messages.confirmReset)) {
                return;
            }
            
            resetUserPin(userId, button);
        });
        
        // Clear session button
        $('#clear-user-session').on('click', function(e) {
            e.preventDefault();
            
            const userId = $(this).data('user-id');
            const button = $(this);
            
            if (!confirm(ageEstimatorUserPin.messages.confirmClearSession)) {
                return;
            }
            
            clearUserSession(userId, button);
        });
        
        // Set PIN button
        $('#set-user-pin').on('click', function(e) {
            e.preventDefault();
            
            const userId = $(this).data('user-id');
            const pinInput = $('#new-user-pin');
            const pin = pinInput.val();
            const button = $(this);
            
            // Validate PIN
            if (!pin) {
                showMessage(ageEstimatorUserPin.messages.pinRequired, 'error');
                pinInput.focus();
                return;
            }
            
            if (!/^\d{4}$/.test(pin)) {
                showMessage(ageEstimatorUserPin.messages.pinInvalid, 'error');
                pinInput.focus();
                return;
            }
            
            setUserPin(userId, pin, button, pinInput);
        });
        
        // Auto-format PIN input (only allow digits, max 4 characters)
        $('#new-user-pin').on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            if (value.length > 4) {
                value = value.substr(0, 4);
            }
            $(this).val(value);
        });
        
        // Enter key on PIN input
        $('#new-user-pin').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                $('#set-user-pin').click();
            }
        });
    }
    
    /**
     * Reset user PIN
     */
    function resetUserPin(userId, button) {
        setButtonLoading(button, true);
        
        $.ajax({
            url: ageEstimatorUserPin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'age_estimator_reset_user_pin',
                user_id: userId,
                nonce: ageEstimatorUserPin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    refreshPage(2000); // Refresh after 2 seconds to show updated status
                } else {
                    showMessage(response.data.message || ageEstimatorUserPin.messages.error, 'error');
                }
            },
            error: function() {
                showMessage(ageEstimatorUserPin.messages.error, 'error');
            },
            complete: function() {
                setButtonLoading(button, false);
            }
        });
    }
    
    /**
     * Clear user session
     */
    function clearUserSession(userId, button) {
        setButtonLoading(button, true);
        
        $.ajax({
            url: ageEstimatorUserPin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'age_estimator_clear_user_session',
                user_id: userId,
                nonce: ageEstimatorUserPin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    refreshPage(2000);
                } else {
                    showMessage(response.data.message || ageEstimatorUserPin.messages.error, 'error');
                }
            },
            error: function() {
                showMessage(ageEstimatorUserPin.messages.error, 'error');
            },
            complete: function() {
                setButtonLoading(button, false);
            }
        });
    }
    
    /**
     * Set user PIN
     */
    function setUserPin(userId, pin, button, pinInput) {
        setButtonLoading(button, true);
        
        $.ajax({
            url: ageEstimatorUserPin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'age_estimator_set_user_pin',
                user_id: userId,
                pin: pin,
                nonce: ageEstimatorUserPin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    pinInput.val(''); // Clear input
                    refreshPage(2000);
                } else {
                    showMessage(response.data.message || ageEstimatorUserPin.messages.error, 'error');
                }
            },
            error: function() {
                showMessage(ageEstimatorUserPin.messages.error, 'error');
            },
            complete: function() {
                setButtonLoading(button, false);
            }
        });
    }
    
    /**
     * Set button loading state
     */
    function setButtonLoading(button, loading) {
        if (loading) {
            button.addClass('loading');
            button.prop('disabled', true);
            
            // Store original text
            if (!button.data('original-text')) {
                button.data('original-text', button.text());
            }
            
            button.text('Processing...');
        } else {
            button.removeClass('loading');
            button.prop('disabled', false);
            
            // Restore original text
            if (button.data('original-text')) {
                button.text(button.data('original-text'));
            }
        }
    }
    
    /**
     * Show message to user
     */
    function showMessage(message, type) {
        const messageDiv = $('#pin-admin-messages');
        const messageParagraph = messageDiv.find('p');
        
        // Remove existing classes
        messageDiv.removeClass('notice-success notice-error notice-warning');
        
        // Add appropriate class
        messageDiv.addClass('notice-' + type);
        
        // Set message
        messageParagraph.text(message);
        
        // Show message
        messageDiv.slideDown();
        
        // Auto-hide after 5 seconds for success messages
        if (type === 'success') {
            setTimeout(function() {
                messageDiv.slideUp();
            }, 5000);
        }
        
        // Scroll to message if it's not visible
        if (!isElementInViewport(messageDiv[0])) {
            messageDiv[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    /**
     * Check if element is in viewport
     */
    function isElementInViewport(el) {
        const rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }
    
    /**
     * Refresh page after delay
     */
    function refreshPage(delay) {
        delay = delay || 1000;
        setTimeout(function() {
            window.location.reload();
        }, delay);
    }
    
    /**
     * Initialize real-time session status updates (if needed)
     */
    function initSessionStatusUpdates() {
        // Check if we're on a user profile page and there's an active session
        const sessionStatus = $('.session-active');
        if (sessionStatus.length === 0) {
            return;
        }
        
        // Update session time remaining every 30 seconds
        setInterval(function() {
            updateSessionStatus();
        }, 30000);
    }
    
    /**
     * Update session status display
     */
    function updateSessionStatus() {
        // This could make an AJAX call to get updated session info
        // For now, we'll just refresh the page periodically
        // You could implement real-time updates here if needed
    }
    
    // Initialize session updates if applicable
    initSessionStatusUpdates();
    
    // Enhanced PIN input behavior
    $(document).on('focus', '#new-user-pin', function() {
        $(this).select(); // Select all text on focus for easy replacement
    });
    
    // Add keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Only if we're on a user profile page
        if (!$('#age-estimator-pin-management').length) {
            return;
        }
        
        // Ctrl+R or Cmd+R to reset PIN (with confirmation)
        if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
            const resetButton = $('#reset-user-pin');
            if (resetButton.length && resetButton.is(':visible')) {
                e.preventDefault();
                resetButton.click();
            }
        }
        
        // Escape key to hide messages
        if (e.key === 'Escape') {
            $('#pin-admin-messages').slideUp();
        }
    });
    
    // Bulk actions warning
    $(document).on('change', '#bulk-action-selector-top, #bulk-action-selector-bottom', function() {
        const action = $(this).val();
        if (action === 'age_estimator_reset_pins' || action === 'age_estimator_clear_sessions') {
            $(this).css('border-color', '#dc3545');
            $(this).attr('title', 'This action affects user security settings. Use with caution.');
        } else {
            $(this).css('border-color', '');
            $(this).removeAttr('title');
        }
    });
    
    // Confirm bulk actions
    $(document).on('click', '#doaction, #doaction2', function(e) {
        const actionSelector = $(this).attr('id') === 'doaction' ? 
            '#bulk-action-selector-top' : '#bulk-action-selector-bottom';
        const action = $(actionSelector).val();
        
        if (action === 'age_estimator_reset_pins') {
            if (!confirm('Are you sure you want to reset PINs for the selected users? They will need to set new PINs to access their settings.')) {
                e.preventDefault();
                return false;
            }
        } else if (action === 'age_estimator_clear_sessions') {
            if (!confirm('Are you sure you want to clear PIN sessions for the selected users? They will need to re-enter their PINs.')) {
                e.preventDefault();
                return false;
            }
        }
    });
    
})(jQuery);

/**
 * Utility functions for PIN management
 */
window.AgeEstimatorUserPinUtils = {
    
    /**
     * Validate PIN format
     */
    validatePin: function(pin) {
        return typeof pin === 'string' && /^\d{4}$/.test(pin);
    },
    
    /**
     * Generate random PIN (for testing purposes)
     */
    generateRandomPin: function() {
        return Math.floor(1000 + Math.random() * 9000).toString();
    },
    
    /**
     * Format time remaining
     */
    formatTimeRemaining: function(seconds) {
        if (seconds <= 0) return 'Expired';
        
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        
        if (minutes > 0) {
            return minutes + ' min ' + secs + ' sec remaining';
        } else {
            return secs + ' sec remaining';
        }
    }
};
