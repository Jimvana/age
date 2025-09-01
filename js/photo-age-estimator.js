/**
 * Photo-Based Age Estimator JavaScript - Live Version
 * Handles automatic face detection and capture based on proximity
 * 
 * Age Estimator Live - Always uses continuous monitoring mode
 */

(function($) {
    'use strict';
    
    // DOM elements
    let video;
    let canvas;
    let overlayCanvas;
    let photoPreview;
    let cameraContainer;
    let startCameraButton;
    let stopCameraButton;
    let resultContainer;
    let stream = null;
    let capturedImageData = null;
    let isModelLoaded = false;
    let fullscreenButton;
    let isFullscreenMode = false;
    
    // Continuous monitoring settings
    let monitoringInterval = null;
    let lastCaptureTime = 0;
    let isProcessing = false;
    let detectionActive = false;
    
    // Configuration
    const MONITORING_CONFIG = {
        checkInterval: 100, // Check for faces every 100ms
        minFaceSize: 150, // Minimum face width in pixels to trigger capture
        maxFaceSize: 350, // Maximum face width (too close)
        captureDelay: 500, // Wait 500ms after face is in range before capturing
        cooldownPeriod: 5000, // Wait 5 seconds before capturing another face
        faceStabilityFrames: 3, // Number of frames face must be stable
        minConfidence: 0.7 // Minimum detection confidence
    };
    
    // Face tracking
    let faceHistory = [];
    let currentFaceInRange = false;
    let faceInRangeStartTime = 0;
    
    // Kiosk mode variables
    let kioskMode = false;
    let kioskImage = '';
    let kioskDisplayTime = 5;
    let kioskTimeout = null;
    let kioskDisplay = null;
    let noFaceTimeout = null;
    const NO_FACE_DELAY = 3000; // 3 seconds delay before showing ad
    
    // Detection settings
    const settings = {
        useAws: false,
        showEmotions: true,
        showAttributes: true,
        privacyMode: false
    };
    
    // Initialize the photo age estimator with continuous monitoring
    const photoAgeEstimator = {
        init: function() {
            console.log('Age Estimator Live - Initializing...');
            
            // Check if parameters are available
            if (typeof ageEstimatorPhotoParams === 'undefined') {
                console.error('Age Estimator Live: Parameters not defined');
                return;
            }
            
            // Log parameters for debugging
            console.log('Age Estimator Live - Parameters:', ageEstimatorPhotoParams);
            
            // Parse settings
            settings.useAws = ageEstimatorPhotoParams.mode === 'aws';
            settings.showEmotions = ageEstimatorPhotoParams.showEmotions === '1';
            settings.showAttributes = ageEstimatorPhotoParams.showAttributes === '1';
            settings.privacyMode = ageEstimatorPhotoParams.privacyMode === '1';
            
            console.log('Age Estimator Live - Settings:', settings);
            console.log('[DEBUG] Mode from params:', ageEstimatorPhotoParams.mode);
            console.log('[DEBUG] Using AWS?:', settings.useAws);
            console.log('Age Estimator Live - Continuous monitoring is always enabled');
            
            // Initialize DOM elements
            this.initializeElements();
            
            // Initialize kiosk mode
            this.initializeKioskMode();
            
            // Set up event listeners
            this.setupEventListeners();
            
            // Load models always - needed for face detection regardless of mode
            console.log('Age Estimator Live: Starting model loading for face detection...');
            this.loadModels();
            
            // Set initial state
            this.updateUI('initial');
            
            // Show ready message
            this.showMessage('Ready to start. Click "Start Monitoring" to begin automatic face detection.');
        },
        
        initializeElements: function() {
            // Get DOM elements
            cameraContainer = document.getElementById('age-estimator-photo-camera');
            startCameraButton = document.getElementById('age-estimator-photo-start-camera');
            stopCameraButton = document.getElementById('age-estimator-photo-stop-camera');
            resultContainer = document.getElementById('age-estimator-photo-result');
            photoPreview = document.getElementById('age-estimator-photo-preview');
            
            if (!cameraContainer) {
                console.error('Age Estimator Live: Camera container not found');
                return;
            }
            
            // Update button text for continuous mode
            if (startCameraButton) {
                startCameraButton.textContent = 'Start Monitoring';
            }
            
            // Create video element
            video = document.createElement('video');
            video.id = 'age-estimator-photo-video';
            video.setAttribute('playsinline', '');
            video.setAttribute('autoplay', '');
            video.setAttribute('muted', '');
            video.style.display = 'none';
            video.style.width = '100%';
            video.style.maxWidth = '400px';
            video.style.height = 'auto';
            video.style.borderRadius = '8px';
            cameraContainer.appendChild(video);
            
            // Create canvas for photo capture
            canvas = document.createElement('canvas');
            canvas.id = 'age-estimator-photo-canvas';
            canvas.style.display = 'none';
            cameraContainer.appendChild(canvas);
            
            // Create overlay canvas for visual feedback
            overlayCanvas = document.createElement('canvas');
            overlayCanvas.id = 'age-estimator-photo-overlay';
            overlayCanvas.style.position = 'absolute';
            overlayCanvas.style.top = '0';
            overlayCanvas.style.left = '0';
            overlayCanvas.style.width = '100%';
            overlayCanvas.style.height = '100%';
            overlayCanvas.style.pointerEvents = 'none';
            overlayCanvas.style.borderRadius = '8px';
            overlayCanvas.style.display = 'none';
            overlayCanvas.style.zIndex = '10'; // Ensure overlay is on top
            cameraContainer.style.position = 'relative';
            cameraContainer.appendChild(overlayCanvas);
            
            console.log('[DEBUG] Overlay canvas created:', overlayCanvas);
            
            // Add status indicator
            const statusIndicator = document.createElement('div');
            statusIndicator.id = 'age-estimator-status';
            statusIndicator.style.position = 'absolute';
            statusIndicator.style.top = '10px';
            statusIndicator.style.right = '10px';
            statusIndicator.style.padding = '5px 10px';
            statusIndicator.style.backgroundColor = 'rgba(0,0,0,0.7)';
            statusIndicator.style.color = 'white';
            statusIndicator.style.borderRadius = '5px';
            statusIndicator.style.fontSize = '12px';
            statusIndicator.style.display = 'none';
            cameraContainer.appendChild(statusIndicator);
            
            // Add fullscreen button
            this.addFullscreenButton();
            
            // Create kiosk display element if needed
            const container = document.querySelector('.age-estimator-photo-container');
            if (container) {
                kioskMode = container.getAttribute('data-kiosk-mode') === 'true';
                kioskImage = container.getAttribute('data-kiosk-image');
                
                if (kioskMode && kioskImage && !document.getElementById('age-estimator-kiosk-display')) {
                    kioskDisplay = document.createElement('div');
                    kioskDisplay.id = 'age-estimator-kiosk-display';
                    kioskDisplay.className = 'age-estimator-kiosk-display';
                    kioskDisplay.style.position = 'absolute';
                    kioskDisplay.style.top = '0';
                    kioskDisplay.style.left = '0';
                    kioskDisplay.style.width = '100%';
                    kioskDisplay.style.height = '100%';
                    kioskDisplay.style.backgroundColor = '#000';
                    kioskDisplay.style.display = 'none';
                    kioskDisplay.style.zIndex = '20';
                    kioskDisplay.innerHTML = '<img src="' + kioskImage + '" alt="Advertisement" style="width: 100%; height: 100%; object-fit: contain;" />';
                    cameraContainer.appendChild(kioskDisplay);
                }
            }
        },
        
        initializeKioskMode: function() {
            const container = document.querySelector('.age-estimator-photo-container');
            if (!container) return;
            
            // Get kiosk mode settings from data attributes
            kioskMode = container.getAttribute('data-kiosk-mode') === 'true';
            kioskImage = container.getAttribute('data-kiosk-image');
            kioskDisplayTime = parseInt(container.getAttribute('data-kiosk-display-time')) || 5;
            kioskDisplay = document.getElementById('age-estimator-kiosk-display');
            
            console.log('Kiosk Mode:', kioskMode, 'Image:', kioskImage, 'Display Time:', kioskDisplayTime);
        },
        
        showKioskDisplay: function() {
            if (!kioskMode || !kioskDisplay) return;
            
            console.log('Showing kiosk advertisement');
            
            // Hide video but keep overlay for smooth transition
            if (video) video.style.opacity = '0';
            
            // Show kiosk display
            kioskDisplay.style.display = 'block';
            
            // Clear any existing timeouts
            if (kioskTimeout) {
                clearTimeout(kioskTimeout);
                kioskTimeout = null;
            }
            if (noFaceTimeout) {
                clearTimeout(noFaceTimeout);
                noFaceTimeout = null;
            }
        },
        
        hideKioskDisplay: function() {
            if (!kioskDisplay) return;
            
            console.log('Hiding kiosk advertisement');
            kioskDisplay.style.display = 'none';
            
            // Show video
            if (video && stream) {
                video.style.opacity = '1';
            }
        },
        
        scheduleKioskDisplay: function() {
            if (!kioskMode) return;
            
            // Clear any existing timeout
            if (noFaceTimeout) {
                clearTimeout(noFaceTimeout);
            }
            
            // Schedule showing the kiosk display after delay
            noFaceTimeout = setTimeout(() => {
                this.showKioskDisplay();
            }, NO_FACE_DELAY);
        },
        
        scheduleReturnToKiosk: function() {
            if (!kioskMode) return;
            
            console.log(`Scheduling return to kiosk in ${kioskDisplayTime} seconds`);
            
            // Clear any existing timeout
            if (kioskTimeout) {
                clearTimeout(kioskTimeout);
            }
            
            // Schedule return to kiosk display
            kioskTimeout = setTimeout(() => {
                this.showKioskDisplay();
                // Clear the result
                if (resultContainer) {
                    resultContainer.innerHTML = '';
                }
            }, kioskDisplayTime * 1000);
        },
        
        addFullscreenButton: function() {
            // Create fullscreen button
            fullscreenButton = document.createElement('button');
            fullscreenButton.id = 'age-estimator-fullscreen';
            fullscreenButton.className = 'age-estimator-fullscreen-button';
            fullscreenButton.style.position = 'absolute';
            fullscreenButton.style.top = '10px';
            fullscreenButton.style.left = '10px';
            fullscreenButton.style.width = '40px';
            fullscreenButton.style.height = '40px';
            fullscreenButton.style.padding = '8px';
            fullscreenButton.style.border = '2px solid #fff';
            fullscreenButton.style.borderRadius = '5px';
            fullscreenButton.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
            fullscreenButton.style.color = 'white';
            fullscreenButton.style.cursor = 'pointer';
            fullscreenButton.style.display = 'none';
            fullscreenButton.style.zIndex = '30';
            fullscreenButton.innerHTML = this.getFullscreenIcon();
            fullscreenButton.title = 'Toggle fullscreen';
            
            cameraContainer.appendChild(fullscreenButton);
            
            // Add click event listener
            fullscreenButton.addEventListener('click', this.toggleFullscreen.bind(this));
        },
        
        setupEventListeners: function() {
            if (startCameraButton) {
                startCameraButton.addEventListener('click', this.startCamera.bind(this));
            }
            if (stopCameraButton) {
                stopCameraButton.addEventListener('click', this.stopCamera.bind(this));
            }
            
            // Setup fullscreen listeners
            document.addEventListener('fullscreenchange', this.handleFullscreenChange.bind(this));
            document.addEventListener('webkitfullscreenchange', this.handleFullscreenChange.bind(this));
            document.addEventListener('mozfullscreenchange', this.handleFullscreenChange.bind(this));
            document.addEventListener('MSFullscreenChange', this.handleFullscreenChange.bind(this));
            
            // Handle window resize in fullscreen
            window.addEventListener('resize', () => {
                if (isFullscreenMode) {
                    this.updateFullscreenDimensions();
                }
            });
        },
        
        loadModels: async function() {
            console.log('Age Estimator Live: Starting model loading...');
            
            // Wait for face-api.js to be available
            let attempts = 0;
            while (typeof faceapi === 'undefined' && attempts < 50) {
                console.log('Age Estimator Live: Waiting for face-api.js... attempt', attempts + 1);
                await new Promise(resolve => setTimeout(resolve, 100));
                attempts++;
            }
            
            if (typeof faceapi === 'undefined') {
                console.error('Age Estimator Live: face-api.js not loaded after waiting');
                isModelLoaded = false;
                return;
            }
            
            console.log('Age Estimator Live: face-api.js is available, loading models...');
            
            try {
                const modelsPath = ageEstimatorPhotoParams.modelsPath;
                console.log('Age Estimator Live: Loading models from', modelsPath);
                
                // Load models sequentially
                console.log('Loading SSD MobileNet v1 for face detection...');
                await faceapi.nets.ssdMobilenetv1.loadFromUri(modelsPath);
                
                // Only load age/gender and expression models if not using AWS
                if (!settings.useAws) {
                    console.log('Loading Age Gender Net for local age estimation...');
                    await faceapi.nets.ageGenderNet.loadFromUri(modelsPath);
                    
                    console.log('Loading Face Expression Net...');
                    await faceapi.nets.faceExpressionNet.loadFromUri(modelsPath);
                } else {
                    console.log('[DEBUG] AWS mode - skipping age/gender model loading');
                }
                
                isModelLoaded = true;
                console.log('Age Estimator Live: All models loaded successfully');
                
                // Test that models are really loaded
                console.log('[DEBUG] Testing loaded models:');
                console.log('[DEBUG] ssdMobilenetv1 loaded:', faceapi.nets.ssdMobilenetv1.isLoaded);
                if (!settings.useAws) {
                    console.log('[DEBUG] ageGenderNet loaded:', faceapi.nets.ageGenderNet.isLoaded);
                    console.log('[DEBUG] faceExpressionNet loaded:', faceapi.nets.faceExpressionNet.isLoaded);
                }
                
                // Update UI to show models are ready
                this.showMessage('Face detection models loaded! Click "Start Monitoring" to begin automatic detection.');
                
                // Check for autostart setting
                this.checkAutostart();
                
                // Models are ready for both Simple and AWS modes
                
            } catch (error) {
                console.error('Age Estimator Live: Error loading models:', error);
                isModelLoaded = false;
                this.showError('Failed to load face detection models. Please check your internet connection or try refreshing the page.');
            }
        },
        
        checkAutostart: function() {
            console.log('Age Estimator Live: Checking autostart setting...');
            
            // Check if user is logged in and has autostart enabled
            if (typeof ageEstimatorPhotoParams !== 'undefined' && 
                ageEstimatorPhotoParams.userMeta && 
                ageEstimatorPhotoParams.userMeta.cameraAutostart === true) {
                
                console.log('Age Estimator Live: Autostart enabled, starting camera automatically...');
                this.showMessage('Auto-starting camera...');
                
                // Add a small delay to ensure everything is properly initialized
                setTimeout(() => {
                    this.startCamera();
                }, 1000);
            } else {
                console.log('Age Estimator Live: Autostart not enabled or user not logged in');
            }
        },
        
        updateUI: function(state) {
            if (startCameraButton) startCameraButton.style.display = 'none';
            if (stopCameraButton) stopCameraButton.style.display = 'none';
            
            switch(state) {
                case 'initial':
                    if (startCameraButton) {
                        startCameraButton.style.display = 'inline-block';
                        startCameraButton.disabled = false;
                    }
                    break;
                case 'monitoring':
                    if (stopCameraButton) stopCameraButton.style.display = 'inline-block';
                    break;
                case 'processing':
                    // Keep stop button visible but show processing state
                    break;
            }
        },
        
        startCamera: async function() {
            try {
                console.log('Age Estimator Live: Starting camera...');
                
                // Disable start button during initialization
                if (startCameraButton) {
                    startCameraButton.disabled = true;
                    startCameraButton.textContent = 'Starting...';
                }
                
                // Enter fullscreen immediately while we have user gesture
                this.enterFullscreen();
                
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'user',
                        width: { ideal: 640 },
                        height: { ideal: 480 }
                    }
                });
                
                video.srcObject = stream;
                
                video.onloadedmetadata = () => {
                    console.log('Age Estimator Live: Video metadata loaded');
                    console.log('[DEBUG] Video dimensions:', video.videoWidth, 'x', video.videoHeight);
                    
                    // Set canvas dimensions
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    overlayCanvas.width = video.videoWidth;
                    overlayCanvas.height = video.videoHeight;
                    
                    // Also update the overlay canvas positioning to match video
                    const videoRect = video.getBoundingClientRect();
                    console.log('[DEBUG] Video rect:', videoRect);
                    
                    console.log('[DEBUG] Canvas dimensions set to:', canvas.width, 'x', canvas.height);
                    console.log('[DEBUG] Overlay canvas dimensions:', overlayCanvas.width, 'x', overlayCanvas.height);
                    
                    // Show video and overlay
                    video.style.display = 'block';
                    overlayCanvas.style.display = 'block';
                    
                    // Show status indicator
                    const statusIndicator = document.getElementById('age-estimator-status');
                    if (statusIndicator) {
                        statusIndicator.style.display = 'block';
                        statusIndicator.textContent = 'Monitoring...';
                    }
                    
                    // Show fullscreen button
                    if (fullscreenButton) {
                        fullscreenButton.style.display = 'block';
                    }
                    
                    this.updateUI('monitoring');
                    this.showMessage('Monitoring active. Move closer to the camera to trigger automatic capture.');
                    
                    // Start continuous monitoring
                    this.startMonitoring();
                        
                // If kiosk mode is enabled, start with the ad
                if (kioskMode) {
                    // Give a moment for video to initialize, then show ad
                    setTimeout(() => {
                        this.showKioskDisplay();
                    }, 500);
                }
            };
                
            } catch (error) {
                console.error('Age Estimator Live: Camera error:', error);
                this.showError('Error accessing camera. Please ensure you have granted camera permissions and are using HTTPS.');
                
                // Re-enable start button on error
                if (startCameraButton) {
                    startCameraButton.disabled = false;
                    startCameraButton.textContent = 'Start Monitoring';
                }
            }
        },
        
        startMonitoring: function() {
            console.log('[DEBUG] startMonitoring called');
            console.log('[DEBUG] isModelLoaded:', isModelLoaded);
            
            // Always need models loaded for face detection
            if (!isModelLoaded) {
                console.error('[DEBUG] Face detection models not loaded, cannot start monitoring');
                this.showError('Face detection models not loaded. Please refresh the page.');
                return;
            }
            
            detectionActive = true;
            console.log('Age Estimator Live: Starting continuous monitoring');
            
            // Clear any existing interval
            if (monitoringInterval) {
                clearInterval(monitoringInterval);
            }
            
            // Test face detection immediately
            console.log('[DEBUG] Testing face detection immediately...');
            this.checkForFaces();
            
            // Start monitoring loop
            let checkCount = 0;
            monitoringInterval = setInterval(() => {
                checkCount++;
                console.log(`[DEBUG] Monitoring interval check #${checkCount}`);
                if (detectionActive && !isProcessing) {
                    this.checkForFaces();
                }
            }, MONITORING_CONFIG.checkInterval);
        },
        
        checkForFaces: async function() {
            console.log('[DEBUG] checkForFaces called');
            console.log('[DEBUG] video:', video);
            console.log('[DEBUG] video.videoWidth:', video ? video.videoWidth : 'no video');
            console.log('[DEBUG] isProcessing:', isProcessing);
            console.log('[DEBUG] isModelLoaded:', isModelLoaded);
            console.log('[DEBUG] settings.useAws:', settings.useAws);
            console.log('[DEBUG] faceapi available:', typeof faceapi !== 'undefined');
            
            if (!video || !video.videoWidth || isProcessing) {
                console.log('[DEBUG] Skipping face check - conditions not met');
                return;
            }
            
            try {
                // Quick face detection without full analysis
                console.log('[DEBUG] About to call faceapi.detectAllFaces');
                const detections = await faceapi.detectAllFaces(
                    video,
                    new faceapi.SsdMobilenetv1Options({
                        minConfidence: MONITORING_CONFIG.minConfidence
                    })
                );
                console.log('[DEBUG] Detections result:', detections);
                console.log('[DEBUG] Number of faces detected:', detections ? detections.length : 0);
                
                // Draw overlay
                this.drawOverlay(detections);
                
                // Process detections
                if (detections.length > 0) {
                    const face = detections[0];
                    const faceWidth = face.box.width;
                    
                    // Check if face is in capture range
                    const inRange = faceWidth >= MONITORING_CONFIG.minFaceSize && 
                                   faceWidth <= MONITORING_CONFIG.maxFaceSize;
                    
                    // Update status
                    this.updateStatus(faceWidth, inRange);
                    
                    // Handle face in range
                    if (inRange && !currentFaceInRange) {
                        // Face just entered range
                        currentFaceInRange = true;
                        faceInRangeStartTime = Date.now();
                        console.log('Face entered capture range');
                    } else if (inRange && currentFaceInRange) {
                        // Face still in range, check if stable enough to capture
                        const timeInRange = Date.now() - faceInRangeStartTime;
                        if (timeInRange >= MONITORING_CONFIG.captureDelay) {
                            // Check cooldown
                            const timeSinceLastCapture = Date.now() - lastCaptureTime;
                            if (timeSinceLastCapture >= MONITORING_CONFIG.cooldownPeriod) {
                                console.log('Triggering automatic capture');
                                await this.captureAndAnalyze();
                            }
                        }
                    } else if (!inRange && currentFaceInRange) {
                        // Face left range
                        currentFaceInRange = false;
                        console.log('Face left capture range');
                    }
                    
                    // Add to face history
                    faceHistory.push({
                        width: faceWidth,
                        timestamp: Date.now()
                    });
                    
                    // Keep only recent history
                    const historyLimit = Date.now() - 1000; // Last 1 second
                    faceHistory = faceHistory.filter(h => h.timestamp > historyLimit);
                    
                } else {
                    // No face detected
                    currentFaceInRange = false;
                    faceHistory = [];
                    this.updateStatus(0, false);
                    
                    // Schedule showing kiosk display if enabled
                    if (kioskMode && !noFaceTimeout && !kioskTimeout) {
                        this.scheduleKioskDisplay();
                    }
                }
                
            } catch (error) {
                console.error('[DEBUG] Error in face detection:', error);
                console.error('[DEBUG] Error stack:', error.stack);
            }
        },
        
        drawOverlay: function(detections) {
            console.log('[DEBUG] drawOverlay called with detections:', detections);
            
            if (!overlayCanvas) {
                console.error('[DEBUG] No overlay canvas found!');
                return;
            }
            
            const ctx = overlayCanvas.getContext('2d');
            if (!ctx) {
                console.error('[DEBUG] Could not get 2d context from overlay canvas');
                return;
            }
            
            console.log('[DEBUG] Clearing overlay canvas');
            ctx.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
            
            if (detections.length > 0) {
            // Face detected - hide kiosk display immediately
            this.hideKioskDisplay();
                    
                    // Cancel any scheduled kiosk display
                    if (noFaceTimeout) {
                        clearTimeout(noFaceTimeout);
                        noFaceTimeout = null;
                    }
                    
                    console.log('[DEBUG] Drawing face box for', detections.length, 'faces');
                    const face = detections[0];
                const box = face.box;
                const faceWidth = box.width;
                
                // Determine color based on proximity
                let color = '#ff0000'; // Red - too far
                let message = 'Move closer';
                
                if (faceWidth >= MONITORING_CONFIG.minFaceSize && faceWidth <= MONITORING_CONFIG.maxFaceSize) {
                    color = '#00ff00'; // Green - in range
                    message = 'In range';
                    
                    // Show countdown if capturing soon
                    if (currentFaceInRange) {
                        const timeInRange = Date.now() - faceInRangeStartTime;
                        const timeUntilCapture = MONITORING_CONFIG.captureDelay - timeInRange;
                        if (timeUntilCapture > 0) {
                            message = `Capturing in ${Math.ceil(timeUntilCapture / 1000)}s`;
                        }
                    }
                } else if (faceWidth > MONITORING_CONFIG.maxFaceSize) {
                    color = '#ffa500'; // Orange - too close
                    message = 'Too close';
                }
                
                // Draw face box
                ctx.strokeStyle = color;
                ctx.lineWidth = 3;
                ctx.strokeRect(box.x, box.y, box.width, box.height);
                console.log('[DEBUG] Drew box at:', box.x, box.y, box.width, box.height, 'color:', color);
                
                // Draw message
                ctx.fillStyle = color;
                ctx.font = 'bold 16px Arial';
                ctx.fillText(message, box.x, box.y - 10);
                
                // Draw center indicator
                const centerX = box.x + box.width / 2;
                const centerY = box.y + box.height / 2;
                ctx.beginPath();
                ctx.arc(centerX, centerY, 5, 0, 2 * Math.PI);
                ctx.fillStyle = color;
                ctx.fill();
            }
        },
        
        updateStatus: function(faceWidth, inRange) {
            const statusIndicator = document.getElementById('age-estimator-status');
            if (!statusIndicator) return;
            
            if (faceWidth === 0) {
                statusIndicator.textContent = 'No face detected';
                statusIndicator.style.backgroundColor = 'rgba(128,128,128,0.7)';
            } else if (inRange) {
                statusIndicator.textContent = `Face detected (${Math.round(faceWidth)}px) - In range`;
                statusIndicator.style.backgroundColor = 'rgba(0,255,0,0.7)';
            } else if (faceWidth < MONITORING_CONFIG.minFaceSize) {
                statusIndicator.textContent = `Face detected (${Math.round(faceWidth)}px) - Too far`;
                statusIndicator.style.backgroundColor = 'rgba(255,0,0,0.7)';
            } else {
                statusIndicator.textContent = `Face detected (${Math.round(faceWidth)}px) - Too close`;
                statusIndicator.style.backgroundColor = 'rgba(255,165,0,0.7)';
            }
        },
        
        captureAndAnalyze: async function() {
            if (isProcessing) return;
            
            isProcessing = true;
            lastCaptureTime = Date.now();
            currentFaceInRange = false;
            
            console.log('Age Estimator Live: Capturing photo...');
            
            // Wait a bit to ensure face is stable
            await new Promise(resolve => setTimeout(resolve, 100));
            
            // Show flash effect
            this.showFlashEffect();
            
            // Capture photo
            const ctx = canvas.getContext('2d');
            
            // Ensure canvas dimensions match video
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            capturedImageData = canvas.toDataURL('image/jpeg', 0.95); // Increased quality
            
            console.log('[DEBUG] Captured image data URL length:', capturedImageData.length);
            console.log('[DEBUG] Captured image data preview:', capturedImageData.substring(0, 50) + '...');
            
            // Show captured preview
            if (photoPreview) {
                photoPreview.src = capturedImageData;
                photoPreview.style.display = 'block';
                photoPreview.style.maxWidth = '200px';
                photoPreview.style.height = 'auto';
                photoPreview.style.borderRadius = '8px';
                photoPreview.style.marginTop = '10px';
            }
            
            // Update status
            const statusIndicator = document.getElementById('age-estimator-status');
            if (statusIndicator) {
                statusIndicator.textContent = 'Analyzing...';
                statusIndicator.style.backgroundColor = 'rgba(0,123,255,0.7)';
            }
            
            this.showMessage('Photo captured! Analyzing... <span class="loader">⏳</span>');
            
            try {
                // Use AWS for age estimation if in AWS mode, otherwise use local
                if (settings.useAws) {
                    console.log('[DEBUG] Using AWS Rekognition for age estimation');
                    await this.analyzeWithAws();
                } else {
                    console.log('[DEBUG] Using local face-api.js for age estimation');
                    await this.analyzeWithLocal();
                }
            } catch (error) {
                console.error('Age Estimator Live: Analysis error:', error);
                this.showError('Analysis failed: ' + error.message);
            } finally {
                isProcessing = false;
                
                // Reset status after a delay
                setTimeout(() => {
                    if (statusIndicator && detectionActive) {
                        statusIndicator.textContent = 'Monitoring...';
                        statusIndicator.style.backgroundColor = 'rgba(0,0,0,0.7)';
                    }
                }, 3000);
            }
        },
        
        showFlashEffect: function() {
            const flash = document.createElement('div');
            flash.style.position = 'absolute';
            flash.style.top = '0';
            flash.style.left = '0';
            flash.style.width = '100%';
            flash.style.height = '100%';
            flash.style.backgroundColor = 'white';
            flash.style.opacity = '0.8';
            flash.style.pointerEvents = 'none';
            flash.style.borderRadius = '8px';
            flash.style.transition = 'opacity 0.3s';
            
            cameraContainer.appendChild(flash);
            
            setTimeout(() => {
                flash.style.opacity = '0';
                setTimeout(() => {
                    cameraContainer.removeChild(flash);
                }, 300);
            }, 100);
        },
        
        analyzeWithAws: async function() {
            console.log('Age Estimator Live: Analyzing with AWS Rekognition...');
            
            try {
                const response = await $.ajax({
                    url: ageEstimatorPhotoParams.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'age_estimator_detect',
                        nonce: ageEstimatorPhotoParams.nonce,
                        image: capturedImageData
                    },
                    timeout: 30000
                });
                
                console.log('Age Estimator Live: AWS response:', response);
                
                if (response.success) {
                    console.log('[DEBUG] AWS response data:', response.data);
                    if (response.data && response.data.faces && response.data.faces.length > 0) {
                        console.log('[DEBUG] Found', response.data.faces.length, 'faces in AWS response');
                        this.displayAwsResults(response.data.faces);
                    } else {
                        console.log('[DEBUG] No faces in AWS response');
                        this.displayNoFaceMessage();
                    }
                } else {
                    throw new Error(response.data?.message || 'Analysis failed');
                }
            } catch (error) {
                console.error('Age Estimator Live: AWS error:', error);
                if (isModelLoaded) {
                    console.log('Age Estimator Live: Falling back to local analysis');
                    await this.analyzeWithLocal();
                } else {
                    throw new Error('AWS analysis failed. Please check your AWS configuration or try again later.');
                }
            }
        },
        
        analyzeWithLocal: async function() {
            console.log('Age Estimator Live: Analyzing with local detection...');
            
            if (typeof faceapi === 'undefined') {
                throw new Error('face-api.js library not loaded. Please refresh the page.');
            }
            
            if (!isModelLoaded) {
                throw new Error('Face detection models not loaded. Please refresh the page.');
            }
            
            try {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                
                return new Promise((resolve, reject) => {
                    img.onload = async () => {
                        try {
                            console.log('Age Estimator Live: Image loaded, detecting faces...');
                            
                            const detections = await faceapi.detectAllFaces(img)
                                .withAgeAndGender()
                                .withFaceExpressions();
                            
                            console.log('Age Estimator Live: Local detection found', detections.length, 'faces');
                            
                            if (detections.length > 0) {
                                this.displayLocalResults(detections);
                                resolve();
                            } else {
                                console.log('Age Estimator Live: No faces detected in image');
                                this.displayNoFaceMessage();
                                resolve();
                            }
                        } catch (error) {
                            console.error('Age Estimator Live: Local detection error:', error);
                            reject(new Error('Face detection failed: ' + error.message));
                        }
                    };
                    
                    img.onerror = () => {
                        reject(new Error('Failed to load captured image for analysis'));
                    };
                    
                    img.src = capturedImageData;
                });
            } catch (error) {
                throw new Error('Local detection failed: ' + error.message);
            }
        },
        
        displayAwsResults: function(faces) {
            console.log('Age Estimator Live: Displaying AWS results for', faces.length, 'face(s)');
            
            const face = faces[0];
            const estimatedAge = Math.round(face.age);
            const minimumAge = parseInt(ageEstimatorPhotoParams.minimumAge || 21);
            const ageGateEnabled = ageEstimatorPhotoParams.enableAgeGate === '1';
            
            let resultHTML = '<div class="continuous-result">';
            
            if (ageGateEnabled) {
                const passed = estimatedAge >= minimumAge;
                resultHTML += `
                    <div class="age-gate-result ${passed ? 'passed' : 'failed'}">
                        <div class="pass-fail-display">
                            ${passed ? 'PASS' : 'FAIL'}
                        </div>
                    </div>
                `;
            } else {
                resultHTML += `
                    <div class="age-result aws-result">
                        <h3>Age Verification Complete</h3>
                        <div class="primary-result">
                            <p class="age-display">Estimated Age: <strong>${estimatedAge} years</strong></p>
                        </div>
                    </div>
                `;
            }
            
            resultHTML += `
                <div class="capture-info">
                    <img src="${capturedImageData}" alt="Captured photo" style="max-width: 100px; border-radius: 8px;">
                    <p>Captured at ${new Date().toLocaleTimeString()}</p>
                </div>
            `;
            
            resultHTML += '</div>';
            
            // Append to results instead of replacing
            const newResult = document.createElement('div');
            newResult.innerHTML = resultHTML;
            newResult.style.marginBottom = '20px';
            newResult.style.paddingBottom = '20px';
            newResult.style.borderBottom = '1px solid #ddd';
            
            resultContainer.insertBefore(newResult, resultContainer.firstChild);
            
            // Keep only last 5 results
            while (resultContainer.children.length > 5) {
                resultContainer.removeChild(resultContainer.lastChild);
            }
            
            // Schedule return to kiosk if enabled
            if (kioskMode) {
                this.scheduleReturnToKiosk();
            }
        },
        
        displayLocalResults: function(detections) {
            console.log('Age Estimator Live: Displaying local results for', detections.length, 'face(s)');
            
            const detection = detections[0];
            const estimatedAge = Math.round(detection.age);
            const minimumAge = parseInt(ageEstimatorPhotoParams.minimumAge || 21);
            const ageGateEnabled = ageEstimatorPhotoParams.enableAgeGate === '1';
            const gender = detection.gender || 'unknown';
            const genderProbability = detection.genderProbability || 0;
            
            // Log the scan if logging is enabled
            if (ageEstimatorPhotoParams.enableLogging === '1') {
                this.logSimpleScan({
                    age: estimatedAge,
                    gender: gender,
                    confidence: genderProbability,
                    face_detected: 1,
                    age_gate_result: ageGateEnabled ? (estimatedAge >= minimumAge ? 'passed' : 'failed') : '',
                    capture_time: new Date().toISOString()
                });
            }
            
            let resultHTML = '<div class="continuous-result">';
            
            if (ageGateEnabled) {
                const passed = estimatedAge >= minimumAge;
                resultHTML += `
                    <div class="age-gate-result ${passed ? 'passed' : 'failed'}">
                        <div class="pass-fail-display">
                            ${passed ? 'PASS' : 'FAIL'}
                        </div>
                    </div>
                `;
            } else {
                resultHTML += `
                    <div class="age-result local-result">
                        <h3>Age Verification Complete</h3>
                        <div class="primary-result">
                            <p class="age-display">Estimated Age: <strong>${estimatedAge} years</strong></p>
                        </div>
                    </div>
                `;
            }
            
            resultHTML += `
                <div class="capture-info">
                    <img src="${capturedImageData}" alt="Captured photo" style="max-width: 100px; border-radius: 8px;">
                    <p>Captured at ${new Date().toLocaleTimeString()}</p>
                </div>
            `;
            
            resultHTML += '</div>';
            
            // Append to results instead of replacing
            const newResult = document.createElement('div');
            newResult.innerHTML = resultHTML;
            newResult.style.marginBottom = '20px';
            newResult.style.paddingBottom = '20px';
            newResult.style.borderBottom = '1px solid #ddd';
            
            resultContainer.insertBefore(newResult, resultContainer.firstChild);
            
            // Keep only last 5 results
            while (resultContainer.children.length > 5) {
                resultContainer.removeChild(resultContainer.lastChild);
            }
            
            // Schedule return to kiosk if enabled
            if (kioskMode) {
                this.scheduleReturnToKiosk();
            }
        },
        
        displayNoFaceMessage: function() {
            let resultHTML = `
                <div class="no-face-message continuous-result">
                    <h3>No Face Detected</h3>
                    <div class="capture-info">
                        <img src="${capturedImageData}" alt="Analyzed photo" style="max-width: 100px; border: 2px solid #ff0000; border-radius: 8px;">
                        <p>No face detected at ${new Date().toLocaleTimeString()}</p>
                    </div>
                </div>
            `;
            
            // Append to results
            const newResult = document.createElement('div');
            newResult.innerHTML = resultHTML;
            newResult.style.marginBottom = '20px';
            newResult.style.paddingBottom = '20px';
            newResult.style.borderBottom = '1px solid #ddd';
            
            resultContainer.insertBefore(newResult, resultContainer.firstChild);
            
            // Keep only last 5 results
            while (resultContainer.children.length > 5) {
                resultContainer.removeChild(resultContainer.lastChild);
            }
        },
        
        stopCamera: function() {
            console.log('Age Estimator Live: Stopping camera and monitoring...');
            
            // Stop monitoring
            detectionActive = false;
            if (monitoringInterval) {
                clearInterval(monitoringInterval);
                monitoringInterval = null;
            }
            
            // Clear overlay
            if (overlayCanvas) {
                const ctx = overlayCanvas.getContext('2d');
                ctx.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
                overlayCanvas.style.display = 'none';
            }
            
            // Hide status indicator
            const statusIndicator = document.getElementById('age-estimator-status');
            if (statusIndicator) {
                statusIndicator.style.display = 'none';
            }
            
            // Stop camera stream
            if (stream) {
                const tracks = stream.getTracks();
                tracks.forEach(track => track.stop());
                
                video.srcObject = null;
                stream = null;
                
                video.style.display = 'none';
                
                capturedImageData = null;
                
                if (photoPreview) {
                    photoPreview.style.display = 'none';
                }
                
                // Reset button
                if (startCameraButton) {
                    startCameraButton.disabled = false;
                    startCameraButton.textContent = 'Start Monitoring';
                }
                
                this.updateUI('initial');
                this.showMessage('Monitoring stopped. Click "Start Monitoring" to begin again.');
                
                // Hide fullscreen button and exit fullscreen if active
                if (fullscreenButton) {
                    fullscreenButton.style.display = 'none';
                }
                
                if (isFullscreenMode) {
                    this.exitFullscreen();
                }
                
                // Clear kiosk timeouts
                if (kioskTimeout) {
                    clearTimeout(kioskTimeout);
                    kioskTimeout = null;
                }
                if (noFaceTimeout) {
                    clearTimeout(noFaceTimeout);
                    noFaceTimeout = null;
                }
                
                // Hide kiosk display
                if (kioskDisplay) {
                    kioskDisplay.style.display = 'none';
                }
            }
        },
        
        // Fullscreen functionality
        getFullscreenIcon: function() {
            return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path>
            </svg>`;
        },
        
        getExitFullscreenIcon: function() {
            return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 9h3a2 2 0 0 0 2-2V4m4 0v3a2 2 0 0 0 2 2h3m0 5v3a2 2 0 0 1-2 2h-3m-4 0h-3a2 2 0 0 1-2-2v-3"></path>
            </svg>`;
        },
        
        toggleFullscreen: function() {
            if (!this.isFullscreen()) {
                this.enterFullscreen();
            } else {
                this.exitFullscreen();
            }
        },
        
        isFullscreen: function() {
            return document.fullscreenElement || 
                   document.webkitFullscreenElement || 
                   document.mozFullScreenElement || 
                   document.msFullscreenElement;
        },
        
        enterFullscreen: function() {
            const container = document.querySelector('.age-estimator-photo-container');
            if (!container) return;
            
            // Add fullscreen class
            container.classList.add('fullscreen-active');
            isFullscreenMode = true;
            
            // Request fullscreen
            if (container.requestFullscreen) {
                container.requestFullscreen();
            } else if (container.webkitRequestFullscreen) {
                container.webkitRequestFullscreen();
            } else if (container.mozRequestFullScreen) {
                container.mozRequestFullScreen();
            } else if (container.msRequestFullscreen) {
                container.msRequestFullscreen();
            }
            
            // Update button icon
            if (fullscreenButton) {
                fullscreenButton.innerHTML = this.getExitFullscreenIcon();
            }
            
            // Ensure video and overlay are properly sized
            setTimeout(() => {
                this.updateFullscreenDimensions();
            }, 100);
        },
        
        exitFullscreen: function() {
            const container = document.querySelector('.age-estimator-photo-container');
            if (container) {
                container.classList.remove('fullscreen-active');
            }
            isFullscreenMode = false;
            
            // Exit fullscreen
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
            
            // Update button icon
            if (fullscreenButton) {
                fullscreenButton.innerHTML = this.getFullscreenIcon();
            }
            
            // Reset dimensions
            setTimeout(() => {
                this.resetDimensions();
            }, 100);
        },
        
        updateFullscreenDimensions: function() {
            if (!video || !overlayCanvas) return;
            
            // Get viewport dimensions
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            
            // Calculate scale to fill screen while maintaining aspect ratio
            const videoAspectRatio = video.videoWidth / video.videoHeight;
            const viewportAspectRatio = viewportWidth / viewportHeight;
            
            let scale = 1;
            if (videoAspectRatio > viewportAspectRatio) {
                // Video is wider than viewport
                scale = viewportWidth / video.videoWidth;
            } else {
                // Video is taller than viewport
                scale = viewportHeight / video.videoHeight;
            }
            
            // Apply scale to overlay canvas
            const scaledWidth = video.videoWidth * scale;
            const scaledHeight = video.videoHeight * scale;
            
            overlayCanvas.style.width = scaledWidth + 'px';
            overlayCanvas.style.height = scaledHeight + 'px';
            overlayCanvas.style.position = 'absolute';
            overlayCanvas.style.top = '50%';
            overlayCanvas.style.left = '50%';
            overlayCanvas.style.transform = 'translate(-50%, -50%)';
        },
        
        resetDimensions: function() {
            if (!video || !overlayCanvas) return;
            
            overlayCanvas.style.width = '100%';
            overlayCanvas.style.height = '100%';
            overlayCanvas.style.position = 'absolute';
            overlayCanvas.style.top = '0';
            overlayCanvas.style.left = '0';
            overlayCanvas.style.transform = 'none';
        },
        
        handleFullscreenChange: function() {
            if (!this.isFullscreen() && isFullscreenMode) {
                // Fullscreen was exited externally (e.g., Esc key)
                const container = document.querySelector('.age-estimator-photo-container');
                if (container) {
                    container.classList.remove('fullscreen-active');
                }
                isFullscreenMode = false;
                
                if (fullscreenButton) {
                    fullscreenButton.innerHTML = this.getFullscreenIcon();
                }
                
                this.resetDimensions();
            }
        },
        
        showMessage: function(message) {
            if (resultContainer) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'system-message';
                messageDiv.innerHTML = '<p>' + message + '</p>';
                messageDiv.style.padding = '10px';
                messageDiv.style.backgroundColor = '#f0f0f0';
                messageDiv.style.borderRadius = '5px';
                messageDiv.style.marginBottom = '10px';
                
                resultContainer.insertBefore(messageDiv, resultContainer.firstChild);
                
                // Remove old messages
                const messages = resultContainer.getElementsByClassName('system-message');
                while (messages.length > 3) {
                    resultContainer.removeChild(messages[messages.length - 1]);
                }
            }
        },
        
        showError: function(message) {
            if (resultContainer) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'system-error';
                errorDiv.innerHTML = '<p class="error">' + message + '</p>';
                errorDiv.style.padding = '10px';
                errorDiv.style.backgroundColor = '#ffe0e0';
                errorDiv.style.borderRadius = '5px';
                errorDiv.style.marginBottom = '10px';
                
                resultContainer.insertBefore(errorDiv, resultContainer.firstChild);
            }
        },
        
        logSimpleScan: function(data) {
            console.log('Logging simple mode scan:', data);
            
            // Only log if a face was detected
            if (!data.face_detected || data.face_detected === 0) {
                console.log('Skipping log - no face detected');
                return;
            }
            
            // Send scan data to server for logging
            $.ajax({
                url: ageEstimatorPhotoParams.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'age_estimator_log_simple_scan',
                    nonce: ageEstimatorPhotoParams.nonce,
                    ...data
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Scan logged successfully:', response.data);
                    } else {
                        console.error('Failed to log scan:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error logging scan:', error);
                }
            });
        }
    };
    
    // Make globally accessible
    window.photoAgeEstimator = photoAgeEstimator;
            
            // Add debug function
            window.debugAgeEstimator = function() {
                console.log('=== Age Estimator Debug Info ===');
                console.log('isModelLoaded:', isModelLoaded);
                console.log('detectionActive:', detectionActive);
                console.log('isProcessing:', isProcessing);
                console.log('monitoringInterval:', monitoringInterval);
                console.log('video element:', video);
                console.log('video ready:', video && video.videoWidth > 0);
                console.log('faceapi available:', typeof faceapi !== 'undefined');
                if (typeof faceapi !== 'undefined') {
                    console.log('Models loaded:');
                    console.log('- ssdMobilenetv1:', faceapi.nets.ssdMobilenetv1.isLoaded);
                    if (!settings.useAws) {
                        console.log('- ageGenderNet:', faceapi.nets.ageGenderNet.isLoaded);
                        console.log('- faceExpressionNet:', faceapi.nets.faceExpressionNet.isLoaded);
                    } else {
                        console.log('- AWS mode: age/gender models not needed');
                    }
                }
                console.log('Settings:', settings);
                console.log('MONITORING_CONFIG:', MONITORING_CONFIG);
            };
    
    // Initialize when document is ready
    $(document).ready(function() {
        photoAgeEstimator.init();
    });
    
})(jQuery);
