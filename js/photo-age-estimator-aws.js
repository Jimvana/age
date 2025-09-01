/**
 * Age Estimator Photo - AWS Rekognition Only
 * Simplified version using only AWS Rekognition for age detection
 */

(function($) {
    'use strict';
    
    // Global variables
    let video;
    let canvas;
    let stream = null;
    let capturedImageData = null;
    let consentGiven = false;
    
    // DOM elements
    let elements = {};
    
    // Settings from WordPress
    const settings = {
        showEmotions: false,
        showAttributes: false,
        privacyMode: false
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        console.log('Age Estimator Photo: Initializing AWS version...');
        
        // Check if parameters exist
        if (typeof ageEstimatorPhotoParams === 'undefined' && typeof ageEstimatorParams === 'undefined') {
            console.error('Age Estimator Photo: Parameters not found');
            showError('Configuration error. Please check plugin settings.');
            return;
        }
        
        // Use either the old or new parameter name for backward compatibility
        const params = typeof ageEstimatorPhotoParams !== 'undefined' ? ageEstimatorPhotoParams : ageEstimatorParams;
        
        // Parse settings
        settings.showEmotions = params.showEmotions === '1';
        settings.showAttributes = params.showAttributes === '1';
        settings.privacyMode = params.privacyMode === '1';
        
        // Initialize
        initializeElements();
        setupEventListeners();
        updateUI('ready');
    });
    
    /**
     * Initialize DOM elements
     */
    function initializeElements() {
        elements = {
            container: $('#age-estimator-photo-container, .age-estimator-photo-container'),
            cameraDiv: $('#age-estimator-photo-camera'),
            startBtn: $('#age-estimator-photo-start-camera'),
            takeBtn: $('#age-estimator-photo-take-photo'),
            retakeBtn: $('#age-estimator-photo-retake'),
            stopBtn: $('#age-estimator-photo-stop-camera'),
            loading: $('#age-estimator-photo-loading'),
            result: $('#age-estimator-photo-result'),
            preview: $('#age-estimator-photo-preview')
        };
        
        // Create video element
        video = document.getElementById('age-estimator-photo-video');
        if (!video) {
            video = document.createElement('video');
            video.id = 'age-estimator-photo-video';
            video.autoplay = true;
            video.playsinline = true;
            elements.cameraDiv.append(video);
        }
        
        // Create canvas for photo capture
        canvas = document.getElementById('age-estimator-photo-canvas');
        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.id = 'age-estimator-photo-canvas';
            canvas.style.display = 'none';
            elements.cameraDiv.append(canvas);
        }
    }
    
    /**
     * Set up event listeners
     */
    function setupEventListeners() {
        elements.startBtn.on('click', showConsentPopup);
        elements.takeBtn.on('click', takePhoto);
        elements.retakeBtn.on('click', retakePhoto);
        elements.stopBtn.on('click', stopCamera);
    }
    
    /**
     * Create consent popup HTML
     */
    function createConsentPopup() {
        const params = typeof ageEstimatorPhotoParams !== 'undefined' ? ageEstimatorPhotoParams : ageEstimatorParams;
        const consentText = params.consentText || 
            'I consent to the processing of my facial image for age verification purposes. My image will be processed securely and deleted immediately after verification.';
        
        const popupHtml = `
            <div class="consent-popup-overlay" id="consent-popup-overlay">
                <div class="consent-popup">
                    <div class="consent-popup-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                    <h3>Privacy Consent Required</h3>
                    <div class="consent-popup-text">
                        ${consentText}
                    </div>
                    <div class="consent-popup-buttons">
                        <button class="consent-popup-button agree" id="consent-agree">Agree</button>
                        <button class="consent-popup-button disagree" id="consent-disagree">Disagree</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(popupHtml);
        
        // Add event listeners to popup buttons
        $('#consent-agree').on('click', function() {
            consentGiven = true;
            hideConsentPopup();
            startCamera();
        });
        
        $('#consent-disagree').on('click', function() {
            hideConsentPopup();
            showMessage('Camera access cancelled. Click "Start Camera" if you change your mind.');
        });
    }
    
    /**
     * Show consent popup
     */
    function showConsentPopup() {
        const params = typeof ageEstimatorPhotoParams !== 'undefined' ? ageEstimatorPhotoParams : ageEstimatorParams;
        
        // Check if consent is required
        if (params.requireConsent !== '1' || consentGiven) {
            startCamera();
            return;
        }
        
        // Create popup if it doesn't exist
        if ($('#consent-popup-overlay').length === 0) {
            createConsentPopup();
        }
        
        // Show popup with animation
        const $overlay = $('#consent-popup-overlay');
        $overlay.css('display', 'flex');
        setTimeout(function() {
            $overlay.addClass('show');
        }, 10);
    }
    
    /**
     * Hide consent popup
     */
    function hideConsentPopup() {
        const $overlay = $('#consent-popup-overlay');
        $overlay.removeClass('show');
        setTimeout(function() {
            $overlay.css('display', 'none');
        }, 300);
    }
    
    /**
     * Update UI based on state
     */
    function updateUI(state) {
        // Hide all buttons first
        elements.startBtn.hide();
        elements.takeBtn.hide();
        elements.retakeBtn.hide();
        elements.stopBtn.hide();
        elements.loading.hide();
        
        switch(state) {
            case 'ready':
                elements.startBtn.show();
                showMessage('Click "Start Camera" to begin age verification.');
                break;
                
            case 'camera-active':
                elements.takeBtn.show();
                elements.stopBtn.show();
                $(video).show();
                elements.preview.hide();
                elements.cameraDiv.addClass('camera-active');
                showMessage('Position your face in the camera and click "Take Photo".');
                break;
                
            case 'photo-taken':
                elements.retakeBtn.show();
                $(video).hide();
                elements.preview.show();
                showMessage('Photo captured. Processing...');
                // Automatically analyze after taking photo
                analyzePhoto();
                break;
                
            case 'analyzing':
                elements.loading.show();
                break;
                
            case 'complete':
                elements.retakeBtn.show();
                $(video).hide();
                elements.preview.show();
                elements.cameraDiv.removeClass('camera-active');
                break;
        }
    }
    
    /**
     * Start camera
     */
    async function startCamera() {
        try {
            console.log('Starting camera...');
            
            // Request camera permission
            const constraints = {
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: 'user'
                }
            };
            
            stream = await navigator.mediaDevices.getUserMedia(constraints);
            video.srcObject = stream;
            
            // Wait for video to be ready
            video.onloadedmetadata = function() {
                video.play();
                updateUI('camera-active');
            };
            
        } catch (error) {
            console.error('Camera error:', error);
            
            if (error.name === 'NotAllowedError') {
                showError('Camera access denied. Please allow camera access and try again.');
            } else if (error.name === 'NotFoundError') {
                showError('No camera found. Please ensure your device has a camera.');
            } else {
                showError('Failed to access camera: ' + error.message);
            }
        }
    }
    
    /**
     * Take photo
     */
    function takePhoto() {
        if (!video || !video.srcObject) {
            showError('Camera not ready. Please try again.');
            return;
        }
        
        // Set canvas dimensions to match video
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        // Draw video frame to canvas
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        // Convert to base64
        capturedImageData = canvas.toDataURL('image/jpeg', 0.8);
        
        // Show preview
        elements.preview.attr('src', capturedImageData);
        
        updateUI('photo-taken');
    }
    
    /**
     * Retake photo
     */
    function retakePhoto() {
        capturedImageData = null;
        elements.result.empty();
        
        if (stream) {
            updateUI('camera-active');
        } else {
            startCamera();
        }
    }
    
    /**
     * Stop camera
     */
    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
            video.srcObject = null;
        }
        
        elements.cameraDiv.removeClass('camera-active');
        updateUI('ready');
    }
    
    /**
     * Analyze photo with AWS Rekognition
     */
    function analyzePhoto() {
        if (!capturedImageData) {
            showError('No photo to analyze. Please take a photo first.');
            return;
        }
        
        updateUI('analyzing');
        
        const params = typeof ageEstimatorPhotoParams !== 'undefined' ? ageEstimatorPhotoParams : ageEstimatorParams;
        
        // Prepare AJAX request - try both old and new action names
        const data = {
            action: 'age_estimator_detect', // Try the new unified action first
            nonce: params.nonce,
            image: capturedImageData
        };
        
        // Send to server
        $.ajax({
            url: params.ajaxUrl,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                console.log('AWS Response:', response);
                
                if (response.success && response.data) {
                    // Check if we have faces in the response
                    if (response.data.faces && response.data.faces.length > 0) {
                        displayResults(response.data.faces[0]); // Use first face detected
                    } else if (response.data.age || response.data.age === 0) {
                        // Direct response format
                        displayResults(response.data);
                    } else {
                        showError('No faces detected in the photo. Please try again with a clear photo of your face.');
                    }
                } else {
                    // Extract error message properly
                    let errorMsg = 'Unknown error occurred';
                    let details = '';
                    
                    if (response.data) {
                        if (typeof response.data === 'string') {
                            errorMsg = response.data;
                        } else if (response.data.message) {
                            errorMsg = response.data.message;
                            details = response.data.details || '';
                        }
                    }
                    
                    showError(errorMsg + (details ? '<br><small>' + details + '</small>' : ''));
                }
                
                updateUI('complete');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                
                // Try to parse error response
                let errorMessage = 'Failed to communicate with server. Please try again.';
                
                try {
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        if (xhr.responseJSON.data.message) {
                            errorMessage = xhr.responseJSON.data.message;
                        }
                    }
                } catch (e) {
                    // Keep default error message
                }
                
                // If it's the new action that failed, try the old one
                if (data.action === 'age_estimator_detect') {
                    data.action = 'age_estimator_photo_detect';
                    
                    $.ajax({
                        url: params.ajaxUrl,
                        type: 'POST',
                        data: data,
                        dataType: 'json',
                        success: arguments.callee.success,
                        error: function() {
                            showError(errorMessage);
                            updateUI('complete');
                        }
                    });
                } else {
                    showError(errorMessage);
                    updateUI('complete');
                }
            }
        });
    }
    
    /**
     * Display results
     */
    function displayResults(data) {
        const params = typeof ageEstimatorPhotoParams !== 'undefined' ? ageEstimatorPhotoParams : ageEstimatorParams;
        const ageGateEnabled = params.enableAgeGate === '1';
        const estimatedAge = data.age || (data.ageRange ? data.ageRange.Low : 0);
        const minimumAge = parseInt(params.minimumAge || 21);
        
        let html = '<div class="age-estimator-results">';
        
        if (ageGateEnabled) {
            const passed = estimatedAge >= minimumAge;
            html += '<div class="age-gate-result ' + (passed ? 'passed' : 'failed') + '">';
            html += '<div class="pass-fail-display">' + (passed ? 'PASS' : 'FAIL') + '</div>';
            html += '</div>';
        } else {
            // If age gate is not enabled, show minimal info
            html += '<div class="result-age">';
            html += '<h3>Age Verification Complete</h3>';
            html += '<div class="age-value">' + Math.round(estimatedAge) + ' years</div>';
            html += '</div>';
        }
        
        // Age gate result (for backward compatibility with detailed messages)
        if (data.ageGateResult) {
            if (data.ageGateResult.redirectUrl && !data.ageGateResult.passed) {
                html += '<div class="redirect-notice">Redirecting in 3 seconds...</div>';
                setTimeout(function() {
                    window.location.href = data.ageGateResult.redirectUrl;
                }, 3000);
            }
        }
        
        // Privacy mode - blur image if enabled
        if (settings.privacyMode && elements.preview) {
            elements.preview.css('filter', 'blur(10px)');
        }
        
        html += '</div>';
        
        elements.result.html(html);
    }
    
    /**
     * Show message
     */
    function showMessage(message) {
        elements.result.html('<p class="info-message">' + message + '</p>');
    }
    
    /**
     * Show error
     */
    function showError(message) {
        elements.result.html('<p class="error-message">' + message + '</p>');
    }
    
    /**
     * Capitalize first letter
     */
    function capitalizeFirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
})(jQuery);
