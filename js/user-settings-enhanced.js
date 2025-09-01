/**
 * FIXED Enhanced User Settings JavaScript
 * Age Estimator Plugin - Advanced Frontend Settings Management
 * 
 * @package AgeEstimator
 * @since 2.0
 */

(function($, wp) {
    'use strict';
    
    // Main Settings Manager Class
    class AgeEstimatorSettingsManager {
        constructor() {
            this.settings = window.ageEstimatorEnhanced || {};
            this.currentSection = 'general';
            this.hasUnsavedChanges = false;
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.initializeUI();
            this.loadSettings();
            this.initCharts();
            
            // Set up auto-save
            this.setupAutoSave();
            
            // Initialize tooltips
            this.initTooltips();
        }
        
        bindEvents() {
            // Navigation
            $(document).on('click', '.nav-link', this.handleNavigation.bind(this));
            
            // Form submission
            $(document).on('submit', '.settings-form', this.handleFormSubmit.bind(this));
            
            // Input changes
            $(document).on('change', '.settings-form input, .settings-form select', this.markAsChanged.bind(this));
            
            // Range sliders
            $(document).on('input', '.range-slider', this.updateRangeValue.bind(this));
            
            // Toggle switches
            $(document).on('change', '.toggle-switch input', this.handleToggle.bind(this));
            
            // Import/Export
            $(document).on('click', '#export-settings', this.exportSettings.bind(this));
            $(document).on('click', '#import-settings', this.triggerImport.bind(this));
            $(document).on('change', '#import-file', this.importSettings.bind(this));
            
            // Test functions
            $(document).on('click', '#test-detection', this.testDetection.bind(this));
            $(document).on('click', '#test-notifications', this.testNotifications.bind(this));
            
            // Data management
            $(document).on('click', '#clear-data', this.clearData.bind(this));
            $(document).on('click', '#download-logs', this.downloadLogs.bind(this));
            
            // Reset functions
            $(document).on('click', '.reset-section', this.resetSection.bind(this));
            $(document).on('click', '#reset-all', this.resetAllSettings.bind(this));
            
            // Prevent unsaved changes
            $(window).on('beforeunload', this.handleBeforeUnload.bind(this));
            
            // Keyboard shortcuts
            $(document).on('keydown', this.handleKeyboardShortcuts.bind(this));
        }
        
        initializeUI() {
            // Add loading overlay
            this.createLoadingOverlay();
            
            // Initialize color pickers if available
            this.initColorPickers();
            
            // Set up conditional fields
            this.setupConditionalFields();
            
            // Add field validation - FIXED: Method now exists
            this.setupValidation();
            
            // Initialize search functionality
            this.initSearch();
        }
        
        // ADDED: Missing setupValidation method
        setupValidation() {
            // Basic validation setup
            $('.settings-form').each(function() {
                const $form = $(this);
                
                // Add validation classes
                $form.find('input[required]').addClass('required-field');
                $form.find('input[type="email"]').addClass('email-field');
                $form.find('input[type="number"]').addClass('number-field');
                
                // Add real-time validation
                $form.find('input').on('blur', function() {
                    const $field = $(this);
                    const value = $field.val();
                    
                    // Remove previous errors
                    $field.removeClass('error');
                    $field.siblings('.error-message').remove();
                    
                    // Validate if has value or is required
                    if (value || $field.attr('required')) {
                        if ($field.attr('required') && !value) {
                            $field.addClass('error');
                            $field.after('<span class="error-message">This field is required</span>');
                        } else if ($field.attr('type') === 'email' && value && !this.isValidEmail(value)) {
                            $field.addClass('error');
                            $field.after('<span class="error-message">Please enter a valid email</span>');
                        }
                    }
                }.bind(this));
            }.bind(this));
        }
        
        createLoadingOverlay() {
            if ($('#settings-loading-overlay').length === 0) {
                const overlay = $('<div>', {
                    id: 'settings-loading-overlay',
                    class: 'loading-overlay',
                    html: '<div class="spinner active"></div><p>Loading...</p>',
                    css: {
                        position: 'fixed',
                        top: 0,
                        left: 0,
                        width: '100%',
                        height: '100%',
                        backgroundColor: 'rgba(0,0,0,0.5)',
                        zIndex: 9999,
                        display: 'none',
                        justifyContent: 'center',
                        alignItems: 'center',
                        flexDirection: 'column'
                    }
                }).appendTo('body');
            }
        }
        
        showLoading() {
            $('#settings-loading-overlay').css('display', 'flex').fadeIn(200);
        }
        
        hideLoading() {
            $('#settings-loading-overlay').fadeOut(200);
        }
        
        handleNavigation(e) {
            e.preventDefault();
            
            // Check for unsaved changes
            if (this.hasUnsavedChanges) {
                if (!confirm('You have unsaved changes. Do you want to leave without saving?')) {
                    return;
                }
            }
            
            const $link = $(e.currentTarget);
            const target = $link.attr('href').substring(1);
            
            // Update active states
            $('.nav-link').removeClass('active');
            $link.addClass('active');
            
            // Show target panel
            $('.settings-panel').removeClass('active').fadeOut(200);
            $(`#${target}`).addClass('active').fadeIn(200);
            
            this.currentSection = target;
            
            // Update URL hash
            window.location.hash = target;
            
            // Track navigation
            this.trackEvent('navigation', target);
        }
        
        handleFormSubmit(e) {
            e.preventDefault();
            
            console.log('🚀 Form submitted');
            
            const $form = $(e.currentTarget);
            const section = $form.data('section');
            const formData = this.getFormData($form);
            
            console.log('📋 Section:', section);
            console.log('📋 Form data:', formData);
            
            // Validate form
            if (!this.validateForm($form)) {
                console.log('❌ Form validation failed');
                return;
            }
            
            console.log('✅ Form validation passed');
            
            // FIXED: Improved PIN validation for retail section
            if (section === 'retail') {
                const retailEnabled = formData.retail_mode_enabled === true || 
                                   formData.retail_mode_enabled === 'true' || 
                                   formData.retail_mode_enabled === '1';
                
                const hasPin = formData.retail_pin && formData.retail_pin.length > 0;
                const hasConfirmPin = formData.retail_pin_confirm && formData.retail_pin_confirm.length > 0;
                
                console.log('🔐 PIN validation:', {
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
                        console.log('❌ PIN mismatch');
                        return;
                    }
                }
                
                // If only one PIN field is filled, show error
                if (hasPin && !hasConfirmPin) {
                    this.showAlert('Please confirm your PIN', 'error');
                    console.log('❌ PIN confirmation missing');
                    return;
                }
                
                // Remove confirm PIN from data being sent (don't save it)
                delete formData.retail_pin_confirm;
                
                console.log('✅ PIN validation passed');
            }
            
            this.saveSettings(section, formData);
        }
        
        getFormData($form) {
            const data = {};
            
            // Get all inputs
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
            });
            
            return data;
        }
        
        // FIXED: validateForm method with proper error handling
        validateForm($form) {
            let isValid = true;
            
            // Remove previous error states
            $form.find('.error').removeClass('error');
            $form.find('.error-message').remove();
            
            // Validate required fields
            $form.find('[required]').each(function() {
                const $field = $(this);
                const value = $field.val();
                if (!value || value.trim() === '') {
                    isValid = false;
                    $field.addClass('error');
                    $field.after('<span class="error-message">This field is required</span>');
                }
            });
            
            // FIXED: Validate email fields with proper null checking
            $form.find('input[type="email"]').each((index, element) => {
                const $field = $(element);
                const email = $field.val();
                if (email && email.trim() !== '' && !this.isValidEmail(email)) {
                    isValid = false;
                    $field.addClass('error');
                    $field.after('<span class="error-message">Please enter a valid email address</span>');
                }
            });
            
            // FIXED: Validate number ranges with proper error handling
            $form.find('input[type="number"]').each(function() {
                const $field = $(this);
                const value = $field.val();
                
                if (value && value.trim() !== '') {
                    const numValue = parseFloat(value);
                    const min = $field.attr('min');
                    const max = $field.attr('max');
                    
                    if (min !== undefined && numValue < parseFloat(min)) {
                        isValid = false;
                        $field.addClass('error');
                        $field.after(`<span class="error-message">Value must be at least ${min}</span>`);
                    } else if (max !== undefined && numValue > parseFloat(max)) {
                        isValid = false;
                        $field.addClass('error');
                        $field.after(`<span class="error-message">Value must be at most ${max}</span>`);
                    }
                }
            });
            
            return isValid;
        }
        
        isValidEmail(email) {
            if (!email || typeof email !== 'string') {
                return false;
            }
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
        
        saveSettings(section, data) {
            console.log('💾 Saving settings for section:', section);
            console.log('💾 Data to save:', data);
            
            this.showLoading();
            
            $.ajax({
                url: this.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'age_estimator_save_user_settings',
                    nonce: this.settings.nonce,
                    section: section,
                    settings: data
                },
                success: (response) => {
                    console.log('📡 Save response:', response);
                    
                    if (response.success) {
                        this.showAlert(response.data.message || 'Settings saved successfully!', 'success');
                        this.hasUnsavedChanges = false;
                        
                        // Update local settings cache
                        if (this.settings.currentSettings) {
                            Object.assign(this.settings.currentSettings, data);
                        }
                        
                        // Trigger custom event
                        $(document).trigger('ageEstimator:settingsSaved', [section, data]);
                        
                        // Track save
                        this.trackEvent('save_settings', section);
                    } else {
                        this.showAlert(response.data || 'Error saving settings', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.log('❌ Save error:', error);
                    this.showAlert('Network error. Please try again.', 'error');
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        }
        
        loadSettings() {
            // Settings are already loaded via localization
            if (this.settings.currentSettings) {
                this.applySettings(this.settings.currentSettings);
            } else {
                // Fetch from server
                this.fetchSettings();
            }
        }
        
        fetchSettings() {
            $.ajax({
                url: this.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'age_estimator_get_user_settings',
                    nonce: this.settings.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.settings.currentSettings = response.data;
                        this.applySettings(response.data);
                    }
                }
            });
        }
        
        applySettings(settings) {
            if (!settings) return;
            
            // Apply settings to form fields
            Object.keys(settings).forEach(key => {
                const value = settings[key];
                const $field = $(`[name="${key}"]`);
                
                if ($field.length) {
                    if ($field.attr('type') === 'checkbox') {
                        $field.prop('checked', value);
                    } else if ($field.attr('type') === 'radio') {
                        $(`[name="${key}"][value="${value}"]`).prop('checked', true);
                    } else {
                        $field.val(value);
                        
                        // Update range slider display
                        if ($field.hasClass('range-slider')) {
                            this.updateRangeValue({ target: $field[0] });
                        }
                    }
                }
            });
            
            // Update conditional fields
            this.updateConditionalFields();
        }
        
        updateRangeValue(e) {
            const slider = e.target;
            const $slider = $(slider);
            const value = slider.value;
            const $display = $slider.siblings('.range-value');
            
            if ($display.length) {
                let unit = '';
                const id = $slider.attr('id');
                
                if (id) {
                    if (id.includes('time')) unit = 's';
                    else if (id.includes('volume')) unit = '%';
                    else if (id.includes('timeout')) unit = ' min';
                    else if (id.includes('interval')) unit = 'ms';
                }
                
                $display.text(value + unit);
            }
            
            // Update slider background
            const percent = ((value - slider.min) / (slider.max - slider.min)) * 100;
            $slider.css('background', `linear-gradient(to right, #667eea 0%, #667eea ${percent}%, #e9ecef ${percent}%, #e9ecef 100%)`);
        }
        
        handleToggle(e) {
            const $toggle = $(e.currentTarget);
            
            // Handle dependent fields
            this.updateConditionalFields();
            
            // Add animation
            const $label = $toggle.closest('.toggle-group').find('.toggle-label');
            if ($label.length) {
                $label.animate({ opacity: 0.5 }, 100).animate({ opacity: 1 }, 100);
            }
        }
        
        setupConditionalFields() {
            // Define field dependencies
            this.fieldDependencies = {
                'age_gating_enabled': ['minimum_age'],
                'retail_mode_enabled': ['challenge_age', 'retail_pin', 'retail_pin_confirm', 'enable_logging', 'email_alerts', 'staff_email'],
                'email_alerts': ['staff_email'],
                'enable_sounds': ['sound_volume', 'pass_sound', 'fail_sound'],
                'screen_flash': ['success_color', 'failure_color'],
                'two_factor': ['two_factor_method']
            };
        }
        
        updateConditionalFields() {
            if (!this.fieldDependencies) {
                this.setupConditionalFields();
            }
            
            Object.keys(this.fieldDependencies).forEach(parentField => {
                const $parent = $(`[name="${parentField}"]`);
                const dependentFields = this.fieldDependencies[parentField];
                const isEnabled = $parent.is(':checked') || $parent.val() === 'true';
                
                dependentFields.forEach(field => {
                    const $field = $(`[name="${field}"]`);
                    const $group = $field.closest('.form-group');
                    
                    if (isEnabled) {
                        $group.slideDown(200);
                        $field.prop('disabled', false);
                    } else {
                        $group.slideUp(200);
                        $field.prop('disabled', true);
                    }
                });
            });
        }
        
        markAsChanged() {
            this.hasUnsavedChanges = true;
            
            // Show indicator
            if (!$('.unsaved-indicator').length) {
                $('.settings-header').append('<span class="unsaved-indicator">Unsaved changes</span>');
            }
        }
        
        handleBeforeUnload(e) {
            if (this.hasUnsavedChanges) {
                const message = 'You have unsaved changes. Are you sure you want to leave?';
                e.returnValue = message;
                return message;
            }
        }
        
        // ADDED: Missing setupAutoSave method
        setupAutoSave() {
            // Auto-save every 60 seconds if there are changes
            setInterval(() => {
                if (this.hasUnsavedChanges) {
                    this.autoSave();
                }
            }, 60000);
        }
        
        autoSave() {
            const $activeForm = $('.settings-panel.active .settings-form');
            if ($activeForm.length) {
                const section = $activeForm.data('section');
                const formData = this.getFormData($activeForm);
                
                $.ajax({
                    url: this.settings.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'age_estimator_save_user_settings',
                        nonce: this.settings.nonce,
                        section: section,
                        settings: formData,
                        auto_save: true
                    },
                    success: (response) => {
                        if (response.success) {
                            this.hasUnsavedChanges = false;
                            $('.unsaved-indicator').fadeOut();
                            this.showAlert('Settings auto-saved', 'info', 2000);
                        }
                    }
                });
            }
        }
        
        // ADDED: Missing methods for export/import
        exportSettings() {
            $.ajax({
                url: this.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'age_estimator_export_settings',
                    nonce: this.settings.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const settings = response.data.settings;
                        const blob = new Blob([JSON.stringify(settings, null, 2)], { type: 'application/json' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `age-estimator-settings-${Date.now()}.json`;
                        a.click();
                        URL.revokeObjectURL(url);
                        
                        this.showAlert('Settings exported successfully!', 'success');
                        this.trackEvent('export_settings');
                    }
                }
            });
        }
        
        triggerImport() {
            $('#import-file').click();
        }
        
        importSettings(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const settings = JSON.parse(e.target.result);
                    
                    $.ajax({
                        url: this.settings.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'age_estimator_import_settings',
                            nonce: this.settings.nonce,
                            settings: JSON.stringify(settings)
                        },
                        success: (response) => {
                            if (response.success) {
                                this.showAlert('Settings imported successfully!', 'success');
                                this.applySettings(settings);
                                this.trackEvent('import_settings');
                            } else {
                                this.showAlert(response.data || 'Import failed', 'error');
                            }
                        }
                    });
                } catch (err) {
                    this.showAlert('Invalid settings file', 'error');
                }
            };
            reader.readAsText(file);
            
            // Reset file input
            e.target.value = '';
        }
        
        // ADDED: Missing methods - stubs to prevent errors
        testDetection() {
            this.showAlert('Detection test feature not yet implemented', 'info');
        }
        
        testNotifications() {
            this.showAlert('Notification test feature not yet implemented', 'info');
        }
        
        clearData() {
            if (confirm('Are you sure you want to clear all data? This cannot be undone.')) {
                this.showAlert('Data clearing feature not yet implemented', 'info');
            }
        }
        
        downloadLogs() {
            this.showAlert('Log download feature not yet implemented', 'info');
        }
        
        resetSection(section) {
            if (confirm(`Reset ${section} settings to defaults?`)) {
                this.showAlert('Reset feature not yet implemented', 'info');
            }
        }
        
        resetAllSettings() {
            if (confirm('Reset ALL settings to defaults? This cannot be undone.')) {
                this.showAlert('Reset all feature not yet implemented', 'info');
            }
        }
        
        initCharts() {
            // Chart initialization - stub to prevent errors
            console.log('Charts feature available but not initialized');
        }
        
        handleKeyboardShortcuts(e) {
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                $('.settings-panel.active .settings-form').submit();
            }
        }
        
        // ADDED: Missing initTooltips method
        initTooltips() {
            // Basic tooltip functionality
            $('.form-help').each(function() {
                const $help = $(this);
                const text = $help.text();
                $help.attr('title', text);
            });
        }
        
        initColorPickers() {
            $('input[type="color"]').each(function() {
                const $input = $(this);
                const $preview = $('<span>', {
                    class: 'color-preview',
                    css: {
                        display: 'inline-block',
                        width: '30px',
                        height: '30px',
                        marginLeft: '10px',
                        border: '2px solid #ddd',
                        borderRadius: '4px',
                        verticalAlign: 'middle',
                        backgroundColor: $input.val()
                    }
                });
                
                $input.after($preview);
                
                $input.on('change', function() {
                    $preview.css('backgroundColor', $(this).val());
                });
            });
        }
        
        initSearch() {
            // Search functionality - basic implementation
            console.log('Search functionality available but not fully implemented');
        }
        
        showAlert(message, type = 'success', duration = 5000) {
            // Remove existing alerts
            $('.alert').remove();
            
            const $alert = $('<div>', {
                class: `alert alert-${type}`,
                text: message,
                css: {
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    padding: '15px 20px',
                    borderRadius: '5px',
                    color: '#fff',
                    backgroundColor: type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8',
                    zIndex: 10000,
                    boxShadow: '0 4px 6px rgba(0,0,0,0.1)'
                }
            });
            
            $('body').append($alert);
            
            if (duration > 0) {
                setTimeout(() => {
                    $alert.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, duration);
            }
            
            return $alert;
        }
        
        trackEvent(action, label = null, value = null) {
            // Basic event tracking
            console.log('Event tracked:', action, label, value);
            
            // Track with Google Analytics if available
            if (typeof gtag !== 'undefined') {
                gtag('event', action, {
                    event_category: 'Age Estimator Settings',
                    event_label: label,
                    value: value
                });
            }
        }
    }
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        // Only initialize if we're on a settings page
        if ($('.age-estimator-settings-enhanced').length || 
            $('#age-estimator-user-settings-form').length) {
            
            console.log('🎯 Initializing Age Estimator Settings Manager');
            window.ageEstimatorSettings = new AgeEstimatorSettingsManager();
            console.log('✅ Settings Manager initialized');
        }
    });
    
})(jQuery, window.wp);

// Make debug functions available globally
window.debugFormData = function() {
    if (window.ageEstimatorSettings) {
        const $form = $('.settings-form[data-section="retail"]');
        if ($form.length === 0) {
            console.log('Retail form not found');
            return;
        }
        
        return window.ageEstimatorSettings.getFormData($form);
    } else {
        console.log('Settings manager not available');
    }
};

window.debugPinSave = function() {
    const testData = {
        retail_mode_enabled: true,
        challenge_age: 25,
        retail_pin: '1234'
    };
    
    console.log('Testing PIN save with data:', testData);
    
    if (typeof ageEstimatorEnhanced === 'undefined') {
        console.log('ageEstimatorEnhanced not available');
        return;
    }
    
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

console.log('✅ Age Estimator Enhanced Settings JavaScript Loaded (FIXED VERSION)');
