/**
 * Enhanced User Settings JavaScript
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
            
            // Add field validation
            this.setupValidation();
            
            // Initialize search functionality
            this.initSearch();
        }
        
        createLoadingOverlay() {
            const overlay = $('<div>', {
                id: 'settings-loading-overlay',
                class: 'loading-overlay',
                html: '<div class="spinner active"></div><p>Loading...</p>'
            }).appendTo('body');
        }
        
        showLoading() {
            $('#settings-loading-overlay').fadeIn(200);
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
            
            const $form = $(e.currentTarget);
            const section = $form.data('section');
            const formData = this.getFormData($form);
            
            // Validate form
            if (!this.validateForm($form)) {
                return;
            }
            
            // Special validation for certain fields
            if (section === 'retail' && formData.retail_mode_enabled) {
                if (formData.retail_pin && formData.retail_pin !== formData.retail_pin_confirm) {
                    this.showAlert('PIN confirmation does not match', 'error');
                    return;
                }
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
        
        validateForm($form) {
            let isValid = true;
            
            // Remove previous error states
            $form.find('.error').removeClass('error');
            $form.find('.error-message').remove();
            
            // Validate required fields
            $form.find('[required]').each(function() {
                const $field = $(this);
                if (!$field.val()) {
                    isValid = false;
                    $field.addClass('error');
                    $field.after('<span class="error-message">This field is required</span>');
                }
            });
            
            // Validate email fields
            $form.find('input[type="email"]').each(function() {
                const $field = $(this);
                const email = $field.val();
                if (email && !this.isValidEmail(email)) {
                    isValid = false;
                    $field.addClass('error');
                    $field.after('<span class="error-message">Please enter a valid email address</span>');
                }
            }.bind(this));
            
            // Validate number ranges
            $form.find('input[type="number"]').each(function() {
                const $field = $(this);
                const value = parseFloat($field.val());
                const min = parseFloat($field.attr('min'));
                const max = parseFloat($field.attr('max'));
                
                if (value < min || value > max) {
                    isValid = false;
                    $field.addClass('error');
                    $field.after(`<span class="error-message">Value must be between ${min} and ${max}</span>`);
                }
            });
            
            return isValid;
        }
        
        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
        
        saveSettings(section, data) {
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
                    if (response.success) {
                        this.showAlert(response.data.message, 'success');
                        this.hasUnsavedChanges = false;
                        
                        // Update local settings cache
                        Object.assign(this.settings.currentSettings, data);
                        
                        // Trigger custom event
                        $(document).trigger('ageEstimator:settingsSaved', [section, data]);
                        
                        // Track save
                        this.trackEvent('save_settings', section);
                    } else {
                        this.showAlert(response.data || 'Error saving settings', 'error');
                    }
                },
                error: () => {
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
            const name = $toggle.attr('name');
            const isChecked = $toggle.is(':checked');
            
            // Handle dependent fields
            this.updateConditionalFields();
            
            // Add animation
            const $label = $toggle.closest('.toggle-group').find('.toggle-label');
            $label.animate({ opacity: 0.5 }, 100).animate({ opacity: 1 }, 100);
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
        
        testDetection() {
            this.showAlert('Starting detection test...', 'info');
            
            // Open camera test modal
            this.openTestModal('detection');
            
            // Initialize camera
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: true })
                    .then(stream => {
                        const video = document.getElementById('test-video');
                        if (video) {
                            video.srcObject = stream;
                            
                            // Start detection
                            this.startDetectionTest(video, stream);
                        }
                    })
                    .catch(err => {
                        this.showAlert('Camera access denied', 'error');
                        console.error('Camera error:', err);
                    });
            }
        }
        
        startDetectionTest(video, stream) {
            // This would integrate with your face detection logic
            this.showAlert('Detection test running. Check your camera preview.', 'success');
            
            // Clean up after 10 seconds
            setTimeout(() => {
                stream.getTracks().forEach(track => track.stop());
                this.closeTestModal();
            }, 10000);
        }
        
        testNotifications() {
            this.showAlert('Testing notifications...', 'info');
            
            // Test visual feedback
            if ($('#screen_flash').is(':checked')) {
                const successColor = $('#success_color').val();
                const failureColor = $('#failure_color').val();
                
                // Flash success
                this.flashScreen(successColor);
                
                setTimeout(() => {
                    // Flash failure
                    this.flashScreen(failureColor);
                }, 1500);
            }
            
            // Test sounds
            if ($('#enable_sounds').is(':checked')) {
                const volume = $('#sound_volume').val() / 100;
                const passSound = $('#pass_sound').val();
                const failSound = $('#fail_sound').val();
                
                this.playTestSound(passSound, volume);
                
                setTimeout(() => {
                    this.playTestSound(failSound, volume);
                }, 2000);
            }
            
            this.trackEvent('test_notifications');
        }
        
        flashScreen(color) {
            const $flash = $('<div>', {
                class: 'screen-flash',
                css: {
                    position: 'fixed',
                    top: 0,
                    left: 0,
                    right: 0,
                    bottom: 0,
                    backgroundColor: color,
                    opacity: 0,
                    pointerEvents: 'none',
                    zIndex: 9999
                }
            }).appendTo('body');
            
            $flash.animate({ opacity: 0.3 }, 200)
                  .animate({ opacity: 0 }, 400, function() {
                      $(this).remove();
                  });
        }
        
        playTestSound(soundType, volume) {
            // Map sound types to URLs
            const soundUrls = {
                'default': this.settings.pluginUrl + 'sounds/chime.mp3',
                'bell': this.settings.pluginUrl + 'sounds/bell.mp3',
                'success': this.settings.pluginUrl + 'sounds/success.mp3',
                'buzzer': this.settings.pluginUrl + 'sounds/buzzer.mp3',
                'warning': this.settings.pluginUrl + 'sounds/warning.mp3'
            };
            
            const url = soundUrls[soundType] || soundUrls['default'];
            const audio = new Audio(url);
            audio.volume = volume;
            audio.play().catch(err => console.log('Sound play error:', err));
        }
        
        clearData() {
            const dataType = prompt('What data would you like to clear?\n\n1. Statistics only\n2. Settings only\n3. All data\n\nEnter 1, 2, or 3:');
            
            if (!dataType || !['1', '2', '3'].includes(dataType)) {
                return;
            }
            
            const types = {
                '1': 'statistics',
                '2': 'settings',
                '3': 'all'
            };
            
            if (!confirm('Are you sure? This action cannot be undone.')) {
                return;
            }
            
            $.ajax({
                url: this.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'age_estimator_clear_user_data',
                    nonce: this.settings.nonce,
                    data_type: types[dataType]
                },
                success: (response) => {
                    if (response.success) {
                        this.showAlert('Data cleared successfully', 'success');
                        
                        if (dataType === '2' || dataType === '3') {
                            // Reset form to defaults
                            this.applySettings(this.settings.defaults);
                        }
                        
                        if (dataType === '1' || dataType === '3') {
                            // Refresh statistics
                            this.refreshStats();
                        }
                        
                        this.trackEvent('clear_data', types[dataType]);
                    }
                }
            });
        }
        
        downloadLogs() {
            window.location.href = this.settings.ajaxUrl + '?action=age_estimator_download_logs&nonce=' + this.settings.nonce;
            this.trackEvent('download_logs');
        }
        
        resetSection(e) {
            const section = $(e.currentTarget).data('section') || this.currentSection;
            
            if (!confirm(`Reset ${section} settings to defaults?`)) {
                return;
            }
            
            // Get default values for this section
            const defaults = this.settings.defaults;
            const sectionFields = this.settings.sections[section].fields;
            
            sectionFields.forEach(field => {
                if (defaults.hasOwnProperty(field)) {
                    const $field = $(`[name="${field}"]`);
                    const value = defaults[field];
                    
                    if ($field.attr('type') === 'checkbox') {
                        $field.prop('checked', value);
                    } else {
                        $field.val(value);
                        
                        if ($field.hasClass('range-slider')) {
                            this.updateRangeValue({ target: $field[0] });
                        }
                    }
                }
            });
            
            this.showAlert(`${section} settings reset to defaults`, 'warning');
            this.markAsChanged();
        }
        
        resetAllSettings() {
            if (!confirm('Reset ALL settings to defaults? This cannot be undone.')) {
                return;
            }
            
            this.applySettings(this.settings.defaults);
            this.showAlert('All settings reset to defaults', 'warning');
            this.markAsChanged();
            this.trackEvent('reset_all_settings');
        }
        
        initCharts() {
            const $canvas = $('#usage-chart');
            if (!$canvas.length || typeof Chart === 'undefined') {
                return;
            }
            
            // Get stats data if available
            const statsData = window.statsData || [];
            
            const labels = statsData.map(item => item.date);
            const data = statsData.map(item => item.count);
            
            const ctx = $canvas[0].getContext('2d');
            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Daily Scans',
                        data: data,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
        
        refreshStats() {
            $.ajax({
                url: this.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'age_estimator_get_stats',
                    nonce: this.settings.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Update stat cards
                        $('.stat-value').each(function(index) {
                            const values = [
                                response.data.total_scans,
                                response.data.successful,
                                response.data.failed,
                                response.data.average_age
                            ];
                            $(this).text(values[index] || 0);
                        });
                        
                        // Update chart
                        if (this.chart && response.data.daily_stats) {
                            this.chart.data.labels = response.data.daily_stats.map(item => item.date);
                            this.chart.data.datasets[0].data = response.data.daily_stats.map(item => item.count);
                            this.chart.update();
                        }
                        
                        this.showAlert('Statistics refreshed', 'success');
                    }
                }
            });
        }
        
        handleKeyboardShortcuts(e) {
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                $('.settings-panel.active .settings-form').submit();
            }
            
            // Ctrl/Cmd + E to export
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                this.exportSettings();
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                this.closeTestModal();
            }
        }
        
        initTooltips() {
            // Add tooltips to help icons
            $('.form-help').each(function() {
                const $help = $(this);
                const text = $help.text();
                
                $help.attr('title', text);
                // Initialize tooltip library if available
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
            // Add search box to settings
            const $searchBox = $('<div>', {
                class: 'settings-search',
                html: '<input type="text" placeholder="Search settings..." class="search-input">'
            }).prependTo('.settings-content');
            
            $('.search-input').on('input', this.handleSearch.bind(this));
        }
        
        handleSearch(e) {
            const query = $(e.target).val().toLowerCase();
            
            if (!query) {
                $('.form-group').show();
                $('.form-section').show();
                return;
            }
            
            $('.form-group').each(function() {
                const $group = $(this);
                const text = $group.text().toLowerCase();
                
                if (text.includes(query)) {
                    $group.show();
                    $group.closest('.form-section').show();
                } else {
                    $group.hide();
                }
            });
            
            // Hide empty sections
            $('.form-section').each(function() {
                const $section = $(this);
                if ($section.find('.form-group:visible').length === 0) {
                    $section.hide();
                }
            });
        }
        
        openTestModal(type) {
            const $modal = $('<div>', {
                id: 'test-modal',
                class: 'test-modal',
                html: `
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>${type === 'detection' ? 'Detection Test' : 'Test'}</h2>
                        <video id="test-video" autoplay></video>
                        <div class="test-results"></div>
                    </div>
                `
            }).appendTo('body');
            
            $('.close').on('click', this.closeTestModal.bind(this));
        }
        
        closeTestModal() {
            $('#test-modal').remove();
            
            // Stop any running video streams
            const video = document.getElementById('test-video');
            if (video && video.srcObject) {
                video.srcObject.getTracks().forEach(track => track.stop());
            }
        }
        
        showAlert(message, type = 'success', duration = 5000) {
            const $alert = $('<div>', {
                class: `alert alert-${type} show`,
                text: message
            });
            
            $('#alert-container').append($alert);
            
            if (duration > 0) {
                setTimeout(() => {
                    $alert.removeClass('show');
                    setTimeout(() => $alert.remove(), 300);
                }, duration);
            }
            
            return $alert;
        }
        
        trackEvent(action, label = null, value = null) {
            // Track with Google Analytics if available
            if (typeof gtag !== 'undefined') {
                gtag('event', action, {
                    event_category: 'Age Estimator Settings',
                    event_label: label,
                    value: value
                });
            }
            
            // Custom tracking
            if (this.settings.trackingEnabled) {
                $.post(this.settings.ajaxUrl, {
                    action: 'age_estimator_track_event',
                    event_action: action,
                    event_label: label,
                    nonce: this.settings.nonce
                });
            }
        }
    }
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        // Only initialize if we're on a settings page
        if ($('.age-estimator-settings-enhanced').length || 
            $('#age-estimator-user-settings-form').length) {
            window.ageEstimatorSettings = new AgeEstimatorSettingsManager();
        }
    });
    
})(jQuery, window.wp);
