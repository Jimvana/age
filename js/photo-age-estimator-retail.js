/**
 * Photo-Based Age Estimator - Retail Mode Extension
 * Adds Challenge 25 compliance features to continuous monitoring
 */

(function($) {
    'use strict';
    
    // Retail Mode Controller
    window.AgeEstimatorRetailMode = {
        // Configuration
        config: {
            isEnabled: false,
            challengeAge: 25,
            minimumAge: 18,
            requirePin: false,
            pin: '',
            enableLogging: false,
            currentMode: 'public' // 'public' or 'retail'
        },
        
        // State
        state: {
            staffMember: 'Staff', // Default staff member for forced retail mode
            shiftStartTime: new Date(),
            checksToday: 0,
            challengesToday: 0,
            currentWorkflow: null
        },
        
        // Initialize retail mode
        init: function() {
            // FORCE RETAIL MODE - Always enabled
            this.config.isEnabled = true;
            this.config.currentMode = 'retail'; // Force retail mode
            
            // Check if retail mode is enabled
            if (typeof ageEstimatorPhotoParams !== 'undefined') {
                // Check user-specific settings first if logged in
                if (ageEstimatorPhotoParams.isLoggedIn && ageEstimatorPhotoParams.userRetailMode) {
                    this.config.requirePin = false; // Disable PIN requirement for forced retail mode
                } else {
                    // Fall back to system-wide settings
                    this.config.pin = ageEstimatorPhotoParams.retailPin || '';
                    this.config.requirePin = false; // Disable PIN requirement for forced retail mode
                }
                this.config.challengeAge = parseInt(ageEstimatorPhotoParams.challengeAge) || 25;
                this.config.enableLogging = ageEstimatorPhotoParams.enableLogging === '1';
                
                console.log('Retail Mode Config:', {
                    isEnabled: this.config.isEnabled,
                    challengeAge: this.config.challengeAge,
                    enableLogging: this.config.enableLogging,
                    enableLoggingParam: ageEstimatorPhotoParams.enableLogging
                });
            }
            
            // Skip mode switcher - don't add it
            // this.addModeSwitcher();
            
            // Initialize event handlers
            this.setupEventHandlers();
            
            // Check for stored session
            this.checkStoredSession();
            
            // Automatically enter retail mode
            this.enterRetailMode();
        },
        
        // Add mode switcher UI
        addModeSwitcher: function() {
            const container = $('.age-estimator-photo-container');
            if (container.length === 0) return;
            
            // Create mode switcher
            const modeSwitcher = $(`
                <div class="age-estimator-mode-switcher">
                    <button class="mode-button public active" data-mode="public">
                        <span class="icon">👥</span>
                        <span class="label">Public Mode</span>
                    </button>
                    <button class="mode-button retail" data-mode="retail">
                        <span class="icon">🏪</span>
                        <span class="label">Retail Mode</span>
                    </button>
                </div>
            `);
            
            container.prepend(modeSwitcher);
        },
        
        // Display retail result based on user settings
        displayRetailResult: function(ageData, showResults) {
            const { age, passed, alert_level, confidence } = ageData;
            
            let resultHTML = '';
            
            if (showResults && showResults !== '0' && showResults !== false) {
                // Show full age estimation
                resultHTML = `
                    <div class="retail-result-panel ${alert_level}">
                        <div class="result-header">
                            <h3>Age Verification Result</h3>
                        </div>
                        <div class="result-body">
                            <div class="result-age">Estimated Age: ${age} years</div>
                            <div class="result-status ${passed ? 'pass' : 'fail'}">
                                ${passed ? 'PASS' : 'FAIL'}
                            </div>
                            ${alert_level === 'red' ? '<div class="id-check-required">⚠️ ID CHECK REQUIRED</div>' : ''}
                        </div>
                    </div>
                `;
            } else {
                // Show only pass/fail status (hide age estimation)
                resultHTML = `
                    <div class="retail-result-panel simplified ${alert_level}">
                        <div class="result-header">
                            <h3>Verification Result</h3>
                        </div>
                        <div class="result-body">
                            <div class="result-status-only ${passed ? 'pass' : 'fail'}">
                                ${passed ? 'PASS' : 'FAIL'}
                            </div>
                            ${alert_level === 'red' ? '<div class="id-check-required">⚠️ ID CHECK REQUIRED</div>' : ''}
                        </div>
                    </div>
                `;
            }
            
            // Display the result
            const resultContainer = document.getElementById('retail-result-display') || 
                                   document.getElementById('age-estimator-photo-result');
            if (resultContainer) {
                resultContainer.innerHTML = resultHTML;
                resultContainer.style.display = 'block';
            }
        },
        
        // Setup event handlers
        setupEventHandlers: function() {
            const self = this;
            
            // Mode switching
            $(document).on('click', '.age-estimator-mode-switcher .mode-button', function() {
                const mode = $(this).data('mode');
                self.switchMode(mode);
            });
            
            // PIN entry
            $(document).on('submit', '#retail-pin-form', function(e) {
                e.preventDefault();
                self.validatePin();
            });
            
            // Settings PIN entry
            $(document).on('submit', '#settings-pin-form', function(e) {
                e.preventDefault();
                self.validateSettingsPin();
            });
            
            // Staff login
            $(document).on('submit', '#retail-staff-form', function(e) {
                e.preventDefault();
                self.startShift();
            });
            
            // ID confirmation buttons
            $(document).on('click', '.retail-id-confirm', function() {
                const result = $(this).data('result');
                self.confirmIdCheck(result);
            });
            
            // Camera toggle button
            $(document).on('click', '#retail-camera-toggle', function() {
                self.toggleCamera();
            });
            
            // Settings link protection - DISABLED - Direct access enabled
            /*
            $(document).on('click', '.retail-header-link', function(e) {
                e.preventDefault();
                self.requestSettingsAccess($(this).attr('href'));
            });
            */
            
            // OVERRIDE: Allow direct settings access without PIN
            $(document).on('click', '.retail-header-link', function(e) {
                // Let the link work normally - no PIN protection
                console.log('🔓 Settings link clicked - PIN protection bypassed');
                // Don't prevent default - let the link work normally
            });
        },
        
        // Switch between modes
        switchMode: function(mode) {
            if (mode === 'retail' && this.config.requirePin && !this.state.staffMember) {
                this.showPinEntry();
                return;
            }
            
            this.config.currentMode = mode;
            
            // Update UI
            $('.mode-button').removeClass('active');
            $(`.mode-button[data-mode="${mode}"]`).addClass('active');
            
            // Update display based on mode
            if (mode === 'retail') {
                this.enterRetailMode();
            } else {
                this.exitRetailMode();
            }
        },
        
        // Show PIN entry dialog
        showPinEntry: function() {
            const dialog = $(`
                <div class="age-estimator-modal" id="retail-pin-modal">
                    <div class="modal-content">
                        <h3>Enter Retail Mode PIN</h3>
                        <form id="retail-pin-form">
                            <input type="password" id="retail-pin-input" 
                                   pattern="[0-9]{4}" maxlength="4" 
                                   placeholder="4-digit PIN" required>
                            <div class="modal-buttons">
                                <button type="button" class="cancel-btn">Cancel</button>
                                <button type="submit" class="submit-btn">Enter</button>
                            </div>
                        </form>
                    </div>
                </div>
            `);
            
            $('body').append(dialog);
            $('#retail-pin-input').focus();
            
            // Cancel button
            dialog.find('.cancel-btn').on('click', function() {
                dialog.remove();
            });
        },
        
        // Validate PIN
        validatePin: function() {
            const enteredPin = $('#retail-pin-input').val();
            const self = this;
            
            // If user is logged in and has retail mode enabled, validate against user PIN
            if (ageEstimatorPhotoParams.isLoggedIn && ageEstimatorPhotoParams.userRetailMode) {
                // Show loading state
                $('#retail-pin-form button[type="submit"]').prop('disabled', true).text('Validating...');
                
                // Use the validateUserRetailPin function from user-settings.js if available
                if (typeof window.validateUserRetailPin === 'function') {
                    window.validateUserRetailPin(enteredPin, function(isValid, data) {
                        if (isValid) {
                            $('#retail-pin-modal').remove();
                            self.showStaffLogin();
                        } else {
                            alert('Incorrect PIN. Please try again.');
                            $('#retail-pin-input').val('').focus();
                            $('#retail-pin-form button[type="submit"]').prop('disabled', false).text('Enter');
                        }
                    });
                } else {
                    // Fallback AJAX validation
                    $.ajax({
                        url: ageEstimatorPhotoParams.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'age_estimator_validate_user_pin',
                            nonce: ageEstimatorPhotoParams.nonce,
                            pin: enteredPin
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#retail-pin-modal').remove();
                                self.showStaffLogin();
                            } else {
                                alert('Incorrect PIN. Please try again.');
                                $('#retail-pin-input').val('').focus();
                            }
                        },
                        error: function() {
                            alert('Error validating PIN. Please try again.');
                        },
                        complete: function() {
                            $('#retail-pin-form button[type="submit"]').prop('disabled', false).text('Enter');
                        }
                    });
                }
            } else {
                // Use system-wide PIN validation
                if (enteredPin === this.config.pin) {
                    $('#retail-pin-modal').remove();
                    this.showStaffLogin();
                } else {
                    alert('Incorrect PIN. Please try again.');
                    $('#retail-pin-input').val('').focus();
                }
            }
        },
        
        // Request access to settings with PIN protection - DISABLED
        requestSettingsAccess: function(settingsUrl) {
            // PIN protection disabled - allow direct access
            console.log('🔓 Direct settings access - PIN protection bypassed');
            window.location.href = settingsUrl; // Direct navigation instead of popup
            return;
            
            /* ORIGINAL PIN PROTECTION CODE - DISABLED
            if (!this.config.pin && !ageEstimatorPhotoParams.retailPin) {
                // No PIN configured, allow direct access
                window.open(settingsUrl, '_blank');
                return;
            }
            
            this.showSettingsPinEntry(settingsUrl);
            */
        },
        
        // Show PIN entry dialog for settings access
        showSettingsPinEntry: function(settingsUrl) {
            const dialog = $(`
                <div class="age-estimator-modal" id="settings-pin-modal">
                    <div class="modal-content">
                        <h3>🔒 Settings Access</h3>
                        <p style="margin-bottom: 20px; color: #666; text-align: center;">Enter retail mode PIN to access settings</p>
                        <form id="settings-pin-form">
                            <input type="password" id="settings-pin-input" 
                                   pattern="[0-9]{4}" maxlength="4" 
                                   placeholder="4-digit PIN" required>
                            <div class="modal-buttons">
                                <button type="button" class="cancel-btn">Cancel</button>
                                <button type="submit" class="submit-btn">Access Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            `);
            
            $('body').append(dialog);
            $('#settings-pin-input').focus();
            
            // Store the settings URL for later use
            dialog.data('settingsUrl', settingsUrl);
            
            // Cancel button
            dialog.find('.cancel-btn').on('click', function() {
                dialog.remove();
            });
        },
        
        // Validate PIN for settings access
        validateSettingsPin: function() {
            const enteredPin = $('#settings-pin-input').val();
            const modal = $('#settings-pin-modal');
            const settingsUrl = modal.data('settingsUrl');
            const self = this;
            
            // Show loading state
            $('#settings-pin-form button[type="submit"]').prop('disabled', true).text('Validating...');
            
            // If user is logged in and has retail mode enabled, validate against user PIN
            if (ageEstimatorPhotoParams.isLoggedIn && ageEstimatorPhotoParams.userRetailMode) {
                // Use the validateUserRetailPin function from user-settings.js if available
                if (typeof window.validateUserRetailPin === 'function') {
                    window.validateUserRetailPin(enteredPin, function(isValid, data) {
                        if (isValid) {
                            modal.remove();
                            window.open(settingsUrl, '_blank');
                        } else {
                            alert('Incorrect PIN. Access denied.');
                            $('#settings-pin-input').val('').focus();
                            $('#settings-pin-form button[type="submit"]').prop('disabled', false).text('Access Settings');
                        }
                    });
                } else {
                    // Fallback AJAX validation
                    $.ajax({
                        url: ageEstimatorPhotoParams.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'age_estimator_validate_user_pin',
                            nonce: ageEstimatorPhotoParams.nonce,
                            pin: enteredPin
                        },
                        success: function(response) {
                            if (response.success) {
                                modal.remove();
                                window.open(settingsUrl, '_blank');
                            } else {
                                alert('Incorrect PIN. Access denied.');
                                $('#settings-pin-input').val('').focus();
                            }
                        },
                        error: function() {
                            alert('Error validating PIN. Please try again.');
                        },
                        complete: function() {
                            $('#settings-pin-form button[type="submit"]').prop('disabled', false).text('Access Settings');
                        }
                    });
                }
            } else {
                // Use system-wide PIN validation
                const configuredPin = this.config.pin || ageEstimatorPhotoParams.retailPin;
                if (enteredPin === configuredPin) {
                    modal.remove();
                    window.open(settingsUrl, '_blank');
                } else {
                    alert('Incorrect PIN. Access denied.');
                    $('#settings-pin-input').val('').focus();
                    $('#settings-pin-form button[type="submit"]').prop('disabled', false).text('Access Settings');
                }
            }
        },
        
        // Show staff login
        showStaffLogin: function() {
            const dialog = $(`
                <div class="age-estimator-modal" id="retail-staff-modal">
                    <div class="modal-content">
                        <h3>Staff Login</h3>
                        <form id="retail-staff-form">
                            <input type="text" id="staff-name" 
                                   placeholder="Enter your name" required>
                            <div class="modal-buttons">
                                <button type="button" class="cancel-btn">Cancel</button>
                                <button type="submit" class="submit-btn">Start Shift</button>
                            </div>
                        </form>
                    </div>
                </div>
            `);
            
            $('body').append(dialog);
            $('#staff-name').focus();
            
            // Cancel button
            dialog.find('.cancel-btn').on('click', function() {
                dialog.remove();
            });
        },
        
        // Start shift
        startShift: function() {
            const staffName = $('#staff-name').val();
            
            this.state.staffMember = staffName;
            this.state.shiftStartTime = new Date();
            
            // Store in session
            sessionStorage.setItem('retailModeStaff', staffName);
            sessionStorage.setItem('retailModeShiftStart', this.state.shiftStartTime.toISOString());
            
            $('#retail-staff-modal').remove();
            this.switchMode('retail');
        },
        
        // Enter retail mode
        enterRetailMode: function() {
            const container = $('.age-estimator-photo-container');
            container.addClass('retail-mode');
            
            // Add retail UI elements
            this.addRetailUI();
            
            // Hide the original start button - we'll use our own toggle
            $('#age-estimator-photo-start-camera').hide();
            $('#age-estimator-photo-stop-camera').hide();
            
            // Show legal notice
            this.showLegalNotice();
        },
        
        // Exit retail mode
        exitRetailMode: function() {
            const container = $('.age-estimator-photo-container');
            container.removeClass('retail-mode');
            
            // Remove retail UI elements
            $('.retail-mode-ui').remove();
            
            // Remove body class
            $('body').removeClass('has-retail-ui');
            
            // Reset button text
            $('#age-estimator-photo-start-camera').text('Start Monitoring');
        },
        
        // Add retail UI elements
        addRetailUI: function() {
            // Remove any existing retail UI
            $('.retail-mode-ui').remove();
            
            // Get the settings URL from params
            const settingsUrl = ageEstimatorPhotoParams.retailSettingsUrl || '';
            
            // Create the header content - make it clickable if URL is provided
            let headerLeftContent = `
                <h3>🏪 Challenge 25 Compliance System</h3>
                <div class="retail-info">
                    <span>Staff: ${this.state.staffMember}</span>
                    <span>Checks Today: <strong id="checks-count">${this.state.checksToday}</strong></span>
                    <span>Challenges: <strong id="challenges-count">${this.state.challengesToday}</strong></span>
                </div>
            `;
            
            // Wrap in anchor tag if URL is provided (will be intercepted by click handler for PIN protection)
            if (settingsUrl) {
                headerLeftContent = `
                    <a href="${settingsUrl}" class="retail-header-link" style="text-decoration: none; color: inherit;">
                        ${headerLeftContent}
                    </a>
                `;
            }
            
            const retailUI = $(`
                <div class="retail-mode-ui">
                    <div class="retail-header">
                        <div class="retail-header-left${settingsUrl ? ' clickable' : ''}">
                            ${headerLeftContent}
                        </div>
                        <div class="retail-controls">
                            <button id="retail-camera-toggle" class="camera-toggle-btn off">
                                <span class="toggle-icon">📷</span>
                                <span class="toggle-text">START</span>
                            </button>
                        </div>
                    </div>
                </div>
            `);
            
            // Append to body for fixed positioning at bottom
            $('body').append(retailUI).addClass('has-retail-ui');
            
            // Add hover effect if URL is configured
            if (settingsUrl) {
                $('.retail-header-left').css('cursor', 'pointer');
                $('.retail-header-left').on('mouseenter', function() {
                    $(this).css('opacity', '0.8');
                }).on('mouseleave', function() {
                    $(this).css('opacity', '1');
                });
            }
        },
        
        // Show legal notice
        showLegalNotice: function() {
            if (sessionStorage.getItem('retailLegalNoticeShown')) {
                return;
            }
            
            const notice = $(`
                <div class="age-estimator-modal" id="retail-legal-notice">
                    <div class="modal-content">
                        <h3>⚠️ Legal Notice</h3>
                        <div class="notice-content">
                            <p><strong>This system is a SUPPORT TOOL only</strong></p>
                            <ul>
                                <li>Staff MUST verify physical ID</li>
                                <li>Final decision is YOURS</li>
                                <li>System assists Challenge 25</li>
                                <li>Does NOT replace legal duties</li>
                            </ul>
                        </div>
                        <button class="submit-btn" id="accept-legal">I UNDERSTAND - CONTINUE</button>
                    </div>
                </div>
            `);
            
            $('body').append(notice);
            
            $('#accept-legal').on('click', function() {
                sessionStorage.setItem('retailLegalNoticeShown', 'true');
                notice.remove();
            });
        },
        
        // Process age result in retail mode
        processRetailResult: function(age) {
            // Always process in retail mode - no check needed
            console.log('Processing retail result for age:', age);
            console.log('Logging enabled:', this.config.enableLogging);
            
            // Increment check counter
            this.state.checksToday++;
            $('#checks-count').text(this.state.checksToday);
            
            // Determine alert level
            let alertLevel = 'green';
            let alertClass = 'alert-green';
            let message = '';
            let requiresAction = false;
            
            if (age < this.config.challengeAge) {
                alertLevel = 'red';
                alertClass = 'alert-red';
                message = 'ID REQUIRED - Customer appears UNDER 25';
                requiresAction = true;
                this.state.challengesToday++;
                $('#challenges-count').text(this.state.challengesToday);
            } else if (age >= this.config.challengeAge && age <= this.config.challengeAge + 1) {
                alertLevel = 'amber';
                alertClass = 'alert-amber';
                message = 'BORDERLINE - Consider checking ID';
            } else {
                alertLevel = 'green';
                alertClass = 'alert-green';
                message = 'CLEARLY OVER 25 - Use your judgment';
            }
            
            // Show retail alert
            this.showRetailAlert(age, alertLevel, alertClass, message, requiresAction);
            
            // Log if enabled
            if (this.config.enableLogging) {
                this.logCheck(age, alertLevel);
            }
        },
        
        // Show retail alert
        showRetailAlert: function(age, alertLevel, alertClass, message, requiresAction) {
            // Get user setting for showing results
            let showResults = true; // Default value
            
            // Check for user setting in parameters
            if (typeof ageEstimatorPhotoParams !== 'undefined') {
                if (ageEstimatorPhotoParams.isLoggedIn && ageEstimatorPhotoParams.userMeta && 
                    typeof ageEstimatorPhotoParams.userMeta.showResults !== 'undefined') {
                    showResults = ageEstimatorPhotoParams.userMeta.showResults;
                } else if (typeof ageEstimatorPhotoParams.showResults !== 'undefined') {
                    showResults = ageEstimatorPhotoParams.showResults !== '0' && ageEstimatorPhotoParams.showResults !== false;
                }
            }
            
            console.log('Retail Mode - Show Results Setting:', showResults); // Debug log
            
            // Format age display based on user setting
            let ageDisplayHtml = '';
            if (showResults) {
                ageDisplayHtml = `<p class="age-display">Estimated Age: <strong>${age} years</strong></p>`;
            } else {
                // Only show pass/fail status for age gating
                const passed = age >= this.config.minimumAge;
                ageDisplayHtml = `<p class="age-display">Age Verification: <strong>${passed ? 'PASS' : 'FAIL'}</strong></p>`;
            }
            
            const alertHtml = $(`
                <div class="retail-alert ${alertClass}">
                    <div class="alert-icon">
                        ${alertLevel === 'red' ? '🔴' : alertLevel === 'amber' ? '🟡' : '🟢'}
                    </div>
                    <div class="alert-content">
                        <h2>${message}</h2>
                        ${ageDisplayHtml}
                        ${requiresAction ? `
                            <div class="alert-actions">
                                <p class="action-prompt">YOU MUST CHECK VALID ID</p>
                                <div class="id-buttons">
                                    <button class="retail-id-confirm no-sale" data-result="no-sale">
                                        NO SALE - No ID
                                    </button>
                                    <button class="retail-id-confirm verified-over" data-result="verified-over">
                                        ID CHECKED - Over ${this.config.minimumAge}
                                    </button>
                                    <button class="retail-id-confirm verified-under" data-result="verified-under">
                                        ID CHECKED - Under ${this.config.minimumAge}
                                    </button>
                                </div>
                            </div>
                        ` : `
                            <div class="alert-actions">
                                <button class="retail-id-confirm proceed" data-result="proceed">
                                    Proceed with Sale
                                </button>
                            </div>
                        `}
                    </div>
                </div>
            `);
            
            // Replace result container content
            $('#age-estimator-photo-result').html(alertHtml);
        },
        
        // Confirm ID check
        confirmIdCheck: function(result) {
            // Log the confirmation
            if (this.config.enableLogging) {
                this.logIdConfirmation(result);
            }
            
            // Show confirmation message
            let confirmMessage = '';
            switch(result) {
                case 'no-sale':
                    confirmMessage = '❌ Sale refused - No ID provided';
                    break;
                case 'verified-over':
                    confirmMessage = '✅ ID verified - Customer over ' + this.config.minimumAge;
                    break;
                case 'verified-under':
                    confirmMessage = '❌ Sale refused - Customer under ' + this.config.minimumAge;
                    break;
                case 'proceed':
                    confirmMessage = '✅ Sale completed';
                    break;
            }
            
            $('#age-estimator-photo-result').html(`
                <div class="retail-confirmation">
                    <p>${confirmMessage}</p>
                    <p class="timestamp">Logged at ${new Date().toLocaleTimeString()}</p>
                </div>
            `);
            
            // Reset after delay
            setTimeout(() => {
                $('#age-estimator-photo-result').empty();
            }, 3000);
        },
        
        // Log check to database
        logCheck: function(age, alertLevel) {
            console.log('logCheck called:', {
                age: age,
                alertLevel: alertLevel,
                enableLogging: this.config.enableLogging,
                staff: this.state.staffMember
            });
            
            if (!this.config.enableLogging) {
                console.log('Logging is disabled, skipping log');
                return;
            }
            
            // Get additional data for logging
            const gender = ''; // Not available in retail mode display
            const confidence = 0; // Not available in retail mode display
            const captureTime = new Date().toISOString();
            
            const logData = {
                action: 'age_estimator_log_check',
                nonce: ageEstimatorPhotoParams.nonce,
                age: age,
                alert_level: alertLevel,
                staff: this.state.staffMember || 'Unknown',
                gender: gender,
                confidence: confidence,
                capture_time: captureTime
            };
            
            console.log('Sending log data:', logData);
            
            $.ajax({
                url: ageEstimatorPhotoParams.ajaxUrl,
                type: 'POST',
                data: logData,
                success: function(response) {
                    if (response.success) {
                        console.log('Check logged successfully:', response.data);
                    } else {
                        console.error('Failed to log check:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error logging check:', error);
                    console.error('XHR response:', xhr.responseText);
                }
            });
        },
        
        // Log ID confirmation
        logIdConfirmation: function(result) {
            if (!this.config.enableLogging) return;
            
            $.ajax({
                url: ageEstimatorPhotoParams.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'age_estimator_log_id_confirmation',
                    nonce: ageEstimatorPhotoParams.nonce,
                    result: result,
                    staff: this.state.staffMember || 'Unknown'
                }
            });
        },
        
        // Check stored session
        checkStoredSession: function() {
            const staff = sessionStorage.getItem('retailModeStaff');
            const shiftStart = sessionStorage.getItem('retailModeShiftStart');
            
            if (staff && shiftStart) {
                this.state.staffMember = staff;
                this.state.shiftStartTime = new Date(shiftStart);
                
                // Check if shift is still valid (e.g., within 12 hours)
                const now = new Date();
                const shiftDuration = now - this.state.shiftStartTime;
                const maxShiftDuration = 12 * 60 * 60 * 1000; // 12 hours
                
                if (shiftDuration > maxShiftDuration) {
                    // Clear expired session
                    sessionStorage.removeItem('retailModeStaff');
                    sessionStorage.removeItem('retailModeShiftStart');
                    this.state.staffMember = null;
                    this.state.shiftStartTime = null;
                }
            }
        },
        
        // Toggle camera on/off
        toggleCamera: function() {
            const toggleBtn = $('#retail-camera-toggle');
            const isOn = toggleBtn.hasClass('on');
            
            if (isOn) {
                // Turn off camera
                console.log('Turning camera OFF');
                
                // Click the stop button if it exists
                const stopButton = document.getElementById('age-estimator-photo-stop-camera');
                if (stopButton && stopButton.style.display !== 'none') {
                    stopButton.click();
                } else if (window.photoAgeEstimator && window.photoAgeEstimator.stopCamera) {
                    window.photoAgeEstimator.stopCamera();
                }
                
                // Update button state
                toggleBtn.removeClass('on').addClass('off');
                toggleBtn.find('.toggle-text').text('START');
                
            } else {
                // Turn on camera
                console.log('Turning camera ON');
                
                // Click the start button if it exists
                const startButton = document.getElementById('age-estimator-photo-start-camera');
                if (startButton) {
                    startButton.click();
                } else if (window.photoAgeEstimator && window.photoAgeEstimator.startCamera) {
                    window.photoAgeEstimator.startCamera();
                }
                
                // Update button state
                toggleBtn.removeClass('off').addClass('on');
                toggleBtn.find('.toggle-text').text('STOP');
            }
        },
        
        // Monitor camera state changes
        monitorCameraState: function() {
            const self = this;
            
            // Watch for camera state changes
            const observer = new MutationObserver(function(mutations) {
                const stopButton = document.getElementById('age-estimator-photo-stop-camera');
                const toggleBtn = $('#retail-camera-toggle');
                
                if (stopButton && stopButton.style.display !== 'none') {
                    // Camera is on
                    if (!toggleBtn.hasClass('on')) {
                        toggleBtn.removeClass('off').addClass('on');
                        toggleBtn.find('.toggle-text').text('STOP');
                    }
                } else {
                    // Camera is off
                    if (!toggleBtn.hasClass('off')) {
                        toggleBtn.removeClass('on').addClass('off');
                        toggleBtn.find('.toggle-text').text('START');
                    }
                }
            });
            
            // Observe button changes
            const buttonsContainer = document.querySelector('.age-estimator-photo-container');
            if (buttonsContainer) {
                observer.observe(buttonsContainer, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['style']
                });
            }
        }
    };
    
    // Hook into the main age estimator result display
    $(document).on('ageEstimatorResult', function(event, data) {
        // ALWAYS process in retail mode
        if (data.age) {
            AgeEstimatorRetailMode.processRetailResult(data.age);
        }
    });
    
    // Initialize when document is ready
    $(document).ready(function() {
        AgeEstimatorRetailMode.init();
        // Start monitoring camera state
        setTimeout(() => {
            AgeEstimatorRetailMode.monitorCameraState();
        }, 1000);
    });
    
})(jQuery);
