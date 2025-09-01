/**
 * Photo-Based Age Estimator JavaScript - Continuous Monitoring Version with Overlay Display
 * Shows pass/fail results as overlays next to detected faces instead of in a stream below
 * Integrated with Face Tracking for API optimization
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
    let cameraSelector = null;
    let currentDeviceId = null;
    let availableCameras = [];
    
    // CRITICAL: Track video ready state
    let videoReady = false;
    let videoDimensions = { width: 0, height: 0 };
    
    // Continuous monitoring settings
    let monitoringInterval = null;
    let lastCaptureTime = 0;
    let isProcessing = false;
    let detectionActive = false;
    
    // Configuration - get from settings or use defaults
    const MONITORING_CONFIG = {
        checkInterval: 100, // Check for faces every 100ms
        minFaceSize: ageEstimatorPhotoParams?.minFaceSize || 150,
        maxFaceSize: ageEstimatorPhotoParams?.maxFaceSize || 350,
        captureDelay: ageEstimatorPhotoParams?.captureDelay || 500,
        cooldownPeriod: ageEstimatorPhotoParams?.cooldownPeriod || 5000,
        faceStabilityFrames: 3,
        minConfidence: 0.7
    };
    
    // Age averaging configuration
    const AVERAGING_CONFIG = {
        enabled: ageEstimatorPhotoParams?.enableAveraging === '1',
        samplesToAverage: parseInt(ageEstimatorPhotoParams?.averageSamples) || 5,
        sampleDelay: 1000 // Delay between samples in milliseconds
    };
    
    // Age averaging state
    let ageAveragingState = {
        isCollecting: false,
        samples: [],
        currentSampleCount: 0,
        targetSamples: AVERAGING_CONFIG.samplesToAverage
    };
    
    // Face tracking with results
    let faceHistory = [];
    let currentFaceInRange = false;
    let faceInRangeStartTime = 0;
    let lastDetection = null;
    
    // Active face results for overlay display
    let activeFaceResults = new Map(); // Maps face ID to result data
    let lastSeenFaces = new Map(); // Track when faces were last seen
    
    // Fullscreen scale tracking
    let fullscreenScale = null;
    
    // Kiosk mode variables
    let kioskReturnTimer = null;
    let lastResultTime = 0;
    
    // Detection settings
    const settings = {
        useAws: false,
        showEmotions: true,
        showAttributes: true,
        privacyMode: false,
        minimumAge: 21,
        enableAgeGate: false,
        kioskMode: false,
        kioskImage: '',
        kioskDisplayTime: 5
    };
    
    // Initialize the photo age estimator with continuous monitoring
    const photoAgeEstimator = {
        init: function() {
            console.log('Age Estimator Photo Continuous Overlay - Initializing...');
            
            // Check if parameters are available
            if (typeof ageEstimatorPhotoParams === 'undefined') {
                console.error('Age Estimator Photo: Parameters not defined');
                return;
            }
            
            // Parse settings
            settings.useAws = ageEstimatorPhotoParams.mode === 'aws';
            settings.showEmotions = ageEstimatorPhotoParams.showEmotions === '1';
            settings.showAttributes = ageEstimatorPhotoParams.showAttributes === '1';
            settings.privacyMode = ageEstimatorPhotoParams.privacyMode === '1';
            settings.minimumAge = parseInt(ageEstimatorPhotoParams.minimumAge || 21);
            settings.enableAgeGate = ageEstimatorPhotoParams.enableAgeGate === '1';
            settings.kioskMode = ageEstimatorPhotoParams.kioskMode === '1';
            settings.kioskImage = ageEstimatorPhotoParams.kioskImage || '';
            settings.kioskDisplayTime = parseInt(ageEstimatorPhotoParams.kioskDisplayTime || 5);
            
            console.log('Age Estimator Photo Continuous Overlay - Settings:', settings);
            console.log('Averaging Config:', AVERAGING_CONFIG);
            console.log('Kiosk Mode:', settings.kioskMode ? 'Enabled' : 'Disabled');
            if (settings.kioskMode) {
                console.log('Kiosk Image:', settings.kioskImage || 'No image set');
                console.log('Kiosk Display Time:', settings.kioskDisplayTime, 'seconds');
            }
            
            // Initialize DOM elements
            this.initializeElements();
            
            // Set up event listeners
            this.setupEventListeners();
            
            // Load models if needed
            if (!settings.useAws) {
                console.log('Age Estimator Photo Continuous: Starting model loading...');
                this.loadModels();
            } else {
                // For AWS mode, we still need face recognition models for tracking
                this.loadTrackingModels();
            }
            
            // Set initial state
            this.updateUI('initial');
            
            // Hide result container - we'll use overlays instead
            if (resultContainer) {
                resultContainer.style.display = 'none';
            }
            
            // Show kiosk display initially if kiosk mode is enabled
            if (settings.kioskMode && settings.kioskImage) {
                this.showKioskDisplay();
            }
        },
        
        initializeElements: function() {
            // Get DOM elements
            cameraContainer = document.getElementById('age-estimator-photo-camera');
            startCameraButton = document.getElementById('age-estimator-photo-start-camera');
            stopCameraButton = document.getElementById('age-estimator-photo-stop-camera');
            resultContainer = document.getElementById('age-estimator-photo-result');
            photoPreview = document.getElementById('age-estimator-photo-preview');
            
            if (!cameraContainer) {
                console.error('Age Estimator Photo Continuous: Camera container not found');
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
            video.style.height = 'auto';
            video.style.borderRadius = '8px';
            video.style.position = 'relative';
            video.style.zIndex = '1';
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
            overlayCanvas.style.zIndex = '10'; // Increased from 2
            cameraContainer.style.position = 'relative';
            cameraContainer.style.overflow = 'hidden'; // Ensure kiosk clips when moved off-screen
            cameraContainer.appendChild(overlayCanvas);
            
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
            
            // Add cache metrics display
            const metricsDisplay = document.createElement('div');
            metricsDisplay.id = 'age-estimator-metrics';
            metricsDisplay.style.position = 'absolute';
            metricsDisplay.style.bottom = '10px';
            metricsDisplay.style.left = '10px';
            metricsDisplay.style.padding = '5px 10px';
            metricsDisplay.style.backgroundColor = 'rgba(0,0,0,0.7)';
            metricsDisplay.style.color = 'white';
            metricsDisplay.style.borderRadius = '5px';
            metricsDisplay.style.fontSize = '11px';
            metricsDisplay.style.display = 'none';
            cameraContainer.appendChild(metricsDisplay);
            
            // Add averaging progress display
            if (AVERAGING_CONFIG.enabled && !settings.useAws) {
                const averagingDisplay = document.createElement('div');
                averagingDisplay.id = 'age-estimator-averaging-progress';
                averagingDisplay.style.position = 'absolute';
                averagingDisplay.style.top = '60px';
                averagingDisplay.style.right = '10px';
                averagingDisplay.style.padding = '10px';
                averagingDisplay.style.backgroundColor = 'rgba(0,0,0,0.8)';
                averagingDisplay.style.color = 'white';
                averagingDisplay.style.borderRadius = '5px';
                averagingDisplay.style.fontSize = '14px';
                averagingDisplay.style.display = 'none';
                averagingDisplay.style.minWidth = '200px';
                averagingDisplay.style.zIndex = '1000';
                cameraContainer.appendChild(averagingDisplay);
            }
            
            // Add camera selector
            const cameraSelectorContainer = document.createElement('div');
            cameraSelectorContainer.id = 'age-estimator-camera-selector-container';
            cameraSelectorContainer.style.position = 'absolute';
            cameraSelectorContainer.style.top = '10px';
            cameraSelectorContainer.style.left = '60px';
            cameraSelectorContainer.style.zIndex = '30';
            cameraSelectorContainer.style.display = 'none';
            
            cameraSelector = document.createElement('select');
            cameraSelector.id = 'age-estimator-camera-selector';
            cameraSelector.style.padding = '8px 12px';
            cameraSelector.style.backgroundColor = 'rgba(0,0,0,0.7)';
            cameraSelector.style.color = 'white';
            cameraSelector.style.border = 'none';
            cameraSelector.style.borderRadius = '5px';
            cameraSelector.style.cursor = 'pointer';
            cameraSelector.style.fontSize = '14px';
            cameraSelector.style.minWidth = '150px';
            cameraSelector.style.outline = 'none';
            
            cameraSelectorContainer.appendChild(cameraSelector);
            cameraContainer.appendChild(cameraSelectorContainer);
            
            // Add fullscreen button
            const fullscreenButton = document.createElement('button');
            fullscreenButton.id = 'age-estimator-fullscreen';
            fullscreenButton.className = 'age-estimator-fullscreen-button';
            fullscreenButton.innerHTML = this.getFullscreenIcon();
            fullscreenButton.title = 'Toggle Fullscreen';
            fullscreenButton.style.position = 'absolute';
            fullscreenButton.style.top = '10px';
            fullscreenButton.style.left = '10px';
            fullscreenButton.style.width = '40px';
            fullscreenButton.style.height = '40px';
            fullscreenButton.style.padding = '8px';
            fullscreenButton.style.backgroundColor = 'rgba(0,0,0,0.7)';
            fullscreenButton.style.color = 'white';
            fullscreenButton.style.border = 'none';
            fullscreenButton.style.borderRadius = '5px';
            fullscreenButton.style.cursor = 'pointer';
            fullscreenButton.style.display = 'none';
            fullscreenButton.style.zIndex = '30';
            fullscreenButton.style.transition = 'background-color 0.3s';
            cameraContainer.appendChild(fullscreenButton);
            
            // Add kiosk mode display element if enabled
            if (settings.kioskMode && settings.kioskImage) {
                // Add CSS rules for kiosk hiding
                const style = document.createElement('style');
                style.id = 'age-estimator-kiosk-styles';
                style.textContent = `
                    #age-estimator-kiosk-display {
                        position: absolute !important;
                        top: 0 !important;
                        left: 0 !important;
                        width: 100% !important;
                        height: 100% !important;
                        background-size: contain !important;
                        background-position: center !important;
                        background-repeat: no-repeat !important;
                        background-color: #000 !important;
                        z-index: 20 !important;
                        border-radius: 8px !important;
                        transition: all 0.3s ease-in-out !important;
                        overflow: hidden !important;
                    }
                    #age-estimator-kiosk-display.kiosk-hidden {
                        transform: translateX(-200%) scale(0) !important;
                        opacity: 0 !important;
                        visibility: hidden !important;
                        pointer-events: none !important;
                        left: -9999px !important;
                        top: -9999px !important;
                        width: 0 !important;
                        height: 0 !important;
                    }
                    #age-estimator-kiosk-display.kiosk-visible {
                        transform: translateX(0) scale(1) !important;
                        opacity: 1 !important;
                        visibility: visible !important;
                        pointer-events: auto !important;
                        left: 0 !important;
                        top: 0 !important;
                        width: 100% !important;
                        height: 100% !important;
                    }
                `;
                document.head.appendChild(style);
                
                const kioskDisplay = document.createElement('div');
                kioskDisplay.id = 'age-estimator-kiosk-display';
                kioskDisplay.className = 'kiosk-visible';
                kioskDisplay.style.backgroundImage = `url('${settings.kioskImage}')`;
                cameraContainer.appendChild(kioskDisplay);
                console.log('Kiosk display element created and added to DOM');
            }
        },
        
        setupEventListeners: function() {
            if (startCameraButton) {
                startCameraButton.addEventListener('click', this.startCamera.bind(this));
            }
            if (stopCameraButton) {
                stopCameraButton.addEventListener('click', this.stopCamera.bind(this));
            }
            
            // Add resize handler for fullscreen adjustments
            window.addEventListener('resize', () => {
                if (this.isFullscreen() && video && overlayCanvas) {
                    setTimeout(() => {
                        this.updateOverlayForFullscreen();
                    }, 100);
                }
            });
            
            // Camera selector listener
            if (cameraSelector) {
                cameraSelector.addEventListener('change', this.switchCamera.bind(this));
                
                // Add hover effect
                cameraSelector.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = 'rgba(0,0,0,0.9)';
                });
                cameraSelector.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = 'rgba(0,0,0,0.7)';
                });
            }
            
            // Fullscreen button listener
            const fullscreenButton = document.getElementById('age-estimator-fullscreen');
            if (fullscreenButton) {
                fullscreenButton.addEventListener('click', this.toggleFullscreen.bind(this));
                
                // Add hover effect
                fullscreenButton.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = 'rgba(0,0,0,0.9)';
                });
                fullscreenButton.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = 'rgba(0,0,0,0.7)';
                });
            }
            
            // Listen for fullscreen changes
            document.addEventListener('fullscreenchange', this.handleFullscreenChange.bind(this));
            document.addEventListener('webkitfullscreenchange', this.handleFullscreenChange.bind(this));
            document.addEventListener('mozfullscreenchange', this.handleFullscreenChange.bind(this));
            document.addEventListener('MSFullscreenChange', this.handleFullscreenChange.bind(this));
        },
        
        loadModels: async function() {
            console.log('Age Estimator Photo Continuous: Starting model loading...');
            
            // Wait for face-api.js to be available
            let attempts = 0;
            while (typeof faceapi === 'undefined' && attempts < 50) {
                console.log('Age Estimator Photo Continuous: Waiting for face-api.js... attempt', attempts + 1);
                await new Promise(resolve => setTimeout(resolve, 100));
                attempts++;
            }
            
            if (typeof faceapi === 'undefined') {
                console.error('Age Estimator Photo Continuous: face-api.js not loaded after waiting');
                isModelLoaded = false;
                return;
            }
            
            try {
                const modelsPath = ageEstimatorPhotoParams.modelsPath;
                console.log('Age Estimator Photo Continuous: Loading models from', modelsPath);
                
                // Load models sequentially
                await faceapi.nets.ssdMobilenetv1.loadFromUri(modelsPath);
                await faceapi.nets.ageGenderNet.loadFromUri(modelsPath);
                await faceapi.nets.faceExpressionNet.loadFromUri(modelsPath);
                await faceapi.nets.faceLandmark68Net.loadFromUri(modelsPath);
                await faceapi.nets.faceRecognitionNet.loadFromUri(modelsPath);
                
                isModelLoaded = true;
                console.log('Age Estimator Photo Continuous: All models loaded successfully');
                
                // Initialize face tracker
                if (typeof FaceTracker !== 'undefined') {
                    await FaceTracker.init();
                    console.log('Face Tracker initialized');
                }
                
                // Update UI to show models are ready
                this.showMessage('Models loaded successfully! Click "Start Monitoring" to begin automatic detection.');
                
            } catch (error) {
                console.error('Age Estimator Photo Continuous: Error loading models:', error);
                isModelLoaded = false;
                this.showError('Failed to load face detection models. Please check your internet connection or try refreshing the page.');
            }
        },
        
        loadTrackingModels: async function() {
            // Load only the models needed for face tracking when in AWS mode
            console.log('Age Estimator Photo Continuous: Loading tracking models for AWS mode...');
            
            // Wait for face-api.js
            let attempts = 0;
            while (typeof faceapi === 'undefined' && attempts < 50) {
                await new Promise(resolve => setTimeout(resolve, 100));
                attempts++;
            }
            
            if (typeof faceapi === 'undefined') {
                console.error('face-api.js not available');
                return;
            }
            
            try {
                const modelsPath = ageEstimatorPhotoParams.modelsPath;
                
                // Load only detection and recognition models
                await faceapi.nets.ssdMobilenetv1.loadFromUri(modelsPath);
                await faceapi.nets.faceLandmark68Net.loadFromUri(modelsPath);
                await faceapi.nets.faceRecognitionNet.loadFromUri(modelsPath);
                
                // Mark models as loaded
                isModelLoaded = true;
                console.log('Age Estimator Photo Continuous: Tracking models loaded successfully');
                
                // Initialize face tracker
                if (typeof FaceTracker !== 'undefined') {
                    await FaceTracker.init();
                    console.log('Face Tracker initialized for AWS mode');
                }
                
                // Update UI to show models are ready
                this.showMessage('Face tracking models loaded! Click "Start Monitoring" to begin automatic detection.');
                
            } catch (error) {
                console.error('Error loading tracking models:', error);
                isModelLoaded = false;
                this.showError('Failed to load face tracking models. Please check your internet connection or try refreshing the page.');
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
        
        enumerateCameras: async function() {
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                availableCameras = devices.filter(device => device.kind === 'videoinput');
                
                // Clear existing options
                cameraSelector.innerHTML = '';
                
                // Add camera options
                availableCameras.forEach((camera, index) => {
                    const option = document.createElement('option');
                    option.value = camera.deviceId;
                    option.text = camera.label || `Camera ${index + 1}`;
                    cameraSelector.appendChild(option);
                });
                
                // Show selector if multiple cameras
                const cameraSelectorContainer = document.getElementById('age-estimator-camera-selector-container');
                if (cameraSelectorContainer && availableCameras.length > 1) {
                    cameraSelectorContainer.style.display = 'block';
                }
                
                return availableCameras;
            } catch (error) {
                console.error('Error enumerating cameras:', error);
                return [];
            }
        },
        
        startCamera: async function() {
            try {
                console.log('Age Estimator Photo Continuous: Starting camera...');
                
                // Disable start button during initialization
                if (startCameraButton) {
                    startCameraButton.disabled = true;
                    startCameraButton.textContent = 'Starting...';
                }
                
                // Enter fullscreen immediately while we have user gesture
                this.enterFullscreen();
                
                // Check if mobile and optimize UI
                if (this.isMobileDevice()) {
                    console.log('Mobile device detected');
                    // For iOS Safari, scroll to hide address bar
                    if (this.isIOSSafari()) {
                        window.scrollTo(0, 1);
                    }
                    // Don't show fullscreen hints on mobile since button is hidden
                }
                
                // Get available cameras first
                await this.enumerateCameras();
                
                // Set up video constraints
                const constraints = {
                    video: {
                        width: { ideal: 640 },
                        height: { ideal: 480 }
                    }
                };
                
                // Use specific device if selected
                if (currentDeviceId) {
                    constraints.video.deviceId = { exact: currentDeviceId };
                } else if (availableCameras.length > 0) {
                    // Try to use front camera by default
                    const frontCamera = availableCameras.find(camera => 
                        camera.label.toLowerCase().includes('front') ||
                        camera.label.toLowerCase().includes('user')
                    );
                    if (frontCamera) {
                        constraints.video.deviceId = { exact: frontCamera.deviceId };
                        currentDeviceId = frontCamera.deviceId;
                        cameraSelector.value = frontCamera.deviceId;
                    }
                } else {
                    // Fallback to facingMode
                    constraints.video.facingMode = 'user';
                }
                
                stream = await navigator.mediaDevices.getUserMedia(constraints);
                
                video.srcObject = stream;
                
                // Wait for video to be fully ready
                video.onloadedmetadata = () => {
                    console.log('Age Estimator Photo Continuous: Video metadata loaded');
                    
                    // Store video dimensions
                    videoDimensions.width = video.videoWidth;
                    videoDimensions.height = video.videoHeight;
                    videoReady = true;
                    
                    console.log(`Video dimensions: ${videoDimensions.width}x${videoDimensions.height}`);
                    
                    // Set canvas dimensions ONCE when video is ready
                    canvas.width = videoDimensions.width;
                    canvas.height = videoDimensions.height;
                    overlayCanvas.width = videoDimensions.width;
                    overlayCanvas.height = videoDimensions.height;
                    
                    // Show video and overlay
                    video.style.display = 'block';
                    overlayCanvas.style.display = 'block';
                    console.log('Video and overlay now visible');
            
            // Hide kiosk display when camera starts and reset timer
            if (settings.kioskMode) {
                console.log('Camera ready - hiding kiosk display');
                this.hideKioskDisplay();
                // Set lastResultTime to prevent immediate re-show
                lastResultTime = Date.now();
            }
                    
                    // Show status indicator
                    const statusIndicator = document.getElementById('age-estimator-status');
                    if (statusIndicator) {
                        statusIndicator.style.display = 'block';
                        statusIndicator.textContent = 'Monitoring...';
                    }
                    
                    // Show metrics display - but not on mobile
                    const metricsDisplay = document.getElementById('age-estimator-metrics');
                    if (metricsDisplay && !this.isMobileDevice()) {
                        metricsDisplay.style.display = 'block';
                        this.updateMetricsDisplay();
                    }
                    
                    this.updateUI('monitoring');
                    this.showMessage('Monitoring active. Move closer to the camera to trigger automatic capture.');
                    
                    // Start continuous monitoring
                    this.startMonitoring();
                    
                    // Start metrics update interval
                    setInterval(() => this.updateMetricsDisplay(), 1000);
                    
                    // Show fullscreen button (including on mobile)
                    const fullscreenButton = document.getElementById('age-estimator-fullscreen');
                    if (fullscreenButton) {
                        fullscreenButton.style.display = 'block';
                        
                        // Make button larger on mobile for easier tapping
                        if (this.isMobileDevice()) {
                            fullscreenButton.style.width = '50px';
                            fullscreenButton.style.height = '50px';
                        }
                    }
                    
                    // Add camera active class
                    const container = document.querySelector('.age-estimator-photo-container');
                    if (container) {
                        container.classList.add('camera-active');
                    }
                    
                    // Update camera selector with actual device labels
                    // (labels are only available after getUserMedia permission)
                    this.enumerateCameras();
                };
                
            } catch (error) {
                console.error('Age Estimator Photo Continuous: Camera error:', error);
                this.showError('Error accessing camera. Please ensure you have granted camera permissions and are using HTTPS.');
                
                // Re-enable start button on error
                if (startCameraButton) {
                    startCameraButton.disabled = false;
                    startCameraButton.textContent = 'Start Monitoring';
                }
            }
        },
        
        stopCamera: function() {
            console.log('Age Estimator Photo Continuous: Stopping camera...');
            
            detectionActive = false;
            
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            
            if (monitoringInterval) {
                clearInterval(monitoringInterval);
                monitoringInterval = null;
            }
            
            video.style.display = 'none';
            overlayCanvas.style.display = 'none';
            
            const statusIndicator = document.getElementById('age-estimator-status');
            if (statusIndicator) {
                statusIndicator.style.display = 'none';
            }
            
            const metricsDisplay = document.getElementById('age-estimator-metrics');
            if (metricsDisplay) {
                metricsDisplay.style.display = 'none';
            }
            
            // Hide fullscreen button and camera selector
            const fullscreenButton = document.getElementById('age-estimator-fullscreen');
            if (fullscreenButton) {
                fullscreenButton.style.display = 'none';
            }
            
            const cameraSelectorContainer = document.getElementById('age-estimator-camera-selector-container');
            if (cameraSelectorContainer) {
                cameraSelectorContainer.style.display = 'none';
            }
            
            // Exit fullscreen if active
            if (this.isFullscreen()) {
                this.exitFullscreen();
            }
            
            // Clear active results
            activeFaceResults.clear();
            
            // Clear kiosk timer
            if (kioskReturnTimer) {
                clearTimeout(kioskReturnTimer);
                kioskReturnTimer = null;
            }
            
            // Remove camera active class
            const container = document.querySelector('.age-estimator-photo-container');
            if (container) {
                container.classList.remove('camera-active');
            }
            
            // Show kiosk display if enabled
            if (settings.kioskMode) {
                this.showKioskDisplay();
            }
            
            this.updateUI('initial');
            this.showMessage('Monitoring stopped. Click "Start Monitoring" to begin again.');
        },
        
        startMonitoring: function() {
            // Always need models loaded for face detection
            if (!isModelLoaded) {
                console.error('Models not loaded, cannot start monitoring');
                this.showError('Face detection models not loaded. Please refresh the page.');
                return;
            }
            
            detectionActive = true;
            console.log('Age Estimator Photo Continuous: Starting continuous monitoring');
            
            // Clear any existing interval
            if (monitoringInterval) {
                clearInterval(monitoringInterval);
            }
            
            // Start monitoring loop
            monitoringInterval = setInterval(() => {
                if (detectionActive && !isProcessing) {
                    this.checkForFaces();
                }
            }, MONITORING_CONFIG.checkInterval);
            
            // Start periodic cache cleanup
            setInterval(() => {
                if (typeof FaceTracker !== 'undefined') {
                    const removed = FaceTracker.cleanup();
                    if (removed > 0) {
                        console.log(`Cleaned up ${removed} expired faces from cache`);
                    }
                }
            }, 5000);
        },
        
        checkForFaces: async function() {
            if (!video || !videoReady || (isProcessing && !ageAveragingState.isCollecting)) return;
            
            try {
                // Detect with descriptors for tracking
                const detections = await faceapi.detectAllFaces(
                    video,
                    new faceapi.SsdMobilenetv1Options({
                        minConfidence: MONITORING_CONFIG.minConfidence
                    })
                )
                .withFaceLandmarks()
                .withFaceDescriptors();
                
                // Draw overlay with pass/fail results
                this.drawOverlayWithResults(detections);
                
                // Process detections
                if (detections.length > 0) {
                    // Hide kiosk display when face is detected
                    if (settings.kioskMode) {
                        console.log('Face detected, hiding kiosk display');
                        this.hideKioskDisplay();
                        // Clear any existing timer that might show the kiosk again
                        if (kioskReturnTimer) {
                            clearTimeout(kioskReturnTimer);
                            kioskReturnTimer = null;
                        }
                    }
                    
                    const detection = detections[0];
                    const face = detection.detection;
                    const faceWidth = face.box.width;
                    
                    // Check if face is in capture range
                    const inRange = faceWidth >= MONITORING_CONFIG.minFaceSize && 
                                   faceWidth <= MONITORING_CONFIG.maxFaceSize;
                    
                    // Update status
                    this.updateStatus(faceWidth, inRange);
                    
                    if (inRange) {
                        // Check cache first
                        if (typeof FaceTracker !== 'undefined' && settings.useAws) {
                            const cachedData = FaceTracker.checkFace(detection);
                            
                            if (cachedData) {
                                // Store cached result for overlay display
                                const faceId = this.getFaceId(detection);
                                // Check if we already have a result for this face
                                const existingResult = activeFaceResults.get(faceId);
                                
                                // Only update if result has changed or doesn't exist
                                if (!existingResult || existingResult.age !== cachedData.age) {
                                    activeFaceResults.set(faceId, {
                                        age: cachedData.age,
                                        passed: settings.enableAgeGate ? cachedData.age >= settings.minimumAge : null,
                                        cached: true,
                                        timestamp: Date.now(),
                                        displayUntil: Date.now() + 500, // Ensure minimum display time
                                        opacity: 0 // Start with fade-in
                                    });
                                    
                                    // Update last result time and handle kiosk mode for cached results
                                    lastResultTime = Date.now();
                                    if (settings.kioskMode) {
                                        this.hideKioskDisplay();
                                        this.resetKioskTimer();
                                    }
                                } else if (existingResult) {
                                    // Extend display time if face is still visible
                                    existingResult.displayUntil = Math.max(existingResult.displayUntil, Date.now() + 500);
                                    // Ensure opacity is full for existing results
                                    existingResult.opacity = 1;
                                }
                                return; // Skip capture process
                            }
                        }
                        
                        // No cache hit, proceed with normal capture logic
                        if (!currentFaceInRange) {
                            // Face just entered range
                            currentFaceInRange = true;
                            faceInRangeStartTime = Date.now();
                            console.log('Face entered capture range');
                        } else if (!ageAveragingState.isCollecting) {
                            // Face still in range, check if stable enough to capture (but not during averaging)
                            const timeInRange = Date.now() - faceInRangeStartTime;
                            if (timeInRange >= MONITORING_CONFIG.captureDelay) {
                                // Check cooldown
                                const timeSinceLastCapture = Date.now() - lastCaptureTime;
                                if (timeSinceLastCapture >= MONITORING_CONFIG.cooldownPeriod) {
                                    // Store detection for later use
                                    lastDetection = detection;
                                    console.log('Triggering automatic capture');
                                    
                                    // Add a small delay to ensure face is stable
                                    setTimeout(() => {
                                        this.captureAndAnalyze();
                                    }, 200);
                                }
                            }
                        }
                    } else {
                        currentFaceInRange = false;
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
                    lastDetection = null;
                    this.updateStatus(0, false);
                    
                    // Show kiosk display if enabled and no recent results
                    if (settings.kioskMode) {
                        const timeSinceLastResult = Date.now() - lastResultTime;
                        const shouldShowKiosk = lastResultTime === 0 || timeSinceLastResult > (settings.kioskDisplayTime * 1000);
                        if (shouldShowKiosk) {
                            this.showKioskDisplay();
                        }
                    }
                }
                
                // Clean up old results and faces
                const now = Date.now();
                const minDisplayTime = 500; // Keep results visible for at least 500ms
                const maxAge = 5000; // Maximum time to keep results
                
                // Clean up faces that haven't been seen recently
                for (const [faceId, lastSeen] of lastSeenFaces) {
                    if (now - lastSeen > 1000) { // Face not seen for 1 second
                        lastSeenFaces.delete(faceId);
                    }
                }
                
                // Clean up results, but respect minimum display time
                for (const [faceId, result] of activeFaceResults) {
                    const age = now - result.timestamp;
                    
                    // Always respect displayUntil if set
                    if (result.displayUntil && now < result.displayUntil) {
                        continue; // Keep this result
                    }
                    
                    // Remove if too old
                    if (age > maxAge) {
                        activeFaceResults.delete(faceId);
                    }
                }
                
            } catch (error) {
                console.error('Error in face detection:', error);
            }
        },
        
        getFaceId: function(detection) {
            // Generate a more stable ID based on face position with tolerance
            const originalBox = detection.detection.box;
            
            // Apply same flip transformation as in drawing
            const flippedX = overlayCanvas.width - originalBox.x - originalBox.width;
            
            // Round to nearest 20 pixels to make ID more stable
            const gridSize = 20;
            const x = Math.round(flippedX / gridSize) * gridSize;
            const y = Math.round(originalBox.y / gridSize) * gridSize;
            const id = `${x}_${y}`;
            
            return id;
        },
        
        formatAgeResult: function(age, passed, showResults) {
            // Check if user wants to show full age results or just pass/fail
            if (!showResults || showResults === '0' || showResults === false) {
                // Only show pass/fail status
                if (passed === true) {
                    return 'PASS';
                } else if (passed === false) {
                    return 'FAIL';
                } else {
                    return 'CHECKED'; // For when age gating is not enabled
                }
            }
            
            // Show full age estimation
            let result = `Age: ${age}`;
            
            if (passed === true) {
                result += ' (PASS)';
            } else if (passed === false) {
                result += ' (FAIL)';
            }
            
            return result;
        },
        
        drawOverlayWithResults: function(detections) {
            const ctx = overlayCanvas.getContext('2d');
            ctx.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
            
            const now = Date.now();
            
            // Calculate scale factors for fullscreen mode
            let scaleX = 1;
            let scaleY = 1;
            
            if (fullscreenScale) {
                scaleX = fullscreenScale.x;
                scaleY = fullscreenScale.y;
                console.log('Using fullscreen scale:', fullscreenScale);
            }
            
            // Debug: Log active results
            if (activeFaceResults.size > 0) {
                // Only log averaged results to reduce spam
                for (const [faceId, result] of activeFaceResults) {
                    if (result.averaged) {
                        console.log(`Averaged result - Face ${faceId}: age=${result.age}, displayUntil=${result.displayUntil - now}ms`);
                    }
                }
            }
            
            // First, draw any results that should still be displayed even without detection
            for (const [faceId, result] of activeFaceResults) {
                // Check if this result should still be displayed
                if (result.displayUntil && now < result.displayUntil) {
                    // Extract position from faceId
                    const [x, y] = faceId.split('_').map(Number);
                    
                    // Check if this face is currently detected
                    const isCurrentlyDetected = detections.some(detection => {
                        const detectionFaceId = this.getFaceId(detection);
                        return detectionFaceId === faceId;
                    });
                    
                    // If not currently detected, draw the result at last known position
                    if (!isCurrentlyDetected) {
                        // Estimate box position (centered around the grid point)
                        // Apply scaling for fullscreen mode
                        const estimatedBox = {
                            x: (x - 50) * scaleX,
                            y: (y - 50) * scaleY,
                            width: 100 * scaleX,
                            height: 100 * scaleY
                        };
                        
                        // Draw a faded result
                        const fadeOpacity = Math.min(0.7, result.opacity || 1);
                        ctx.globalAlpha = fadeOpacity;
                        this.drawResultOverlay(ctx, estimatedBox, result, scaleX, scaleY);
                        ctx.globalAlpha = 1.0;
                    }
                }
            }
            
            // Then draw current detections
            if (detections.length > 0) {
                detections.forEach(detection => {
                    const originalBox = detection.detection.box;
                    
                    // Apply scaling for fullscreen mode
                    const box = {
                        x: (overlayCanvas.width - (originalBox.x + originalBox.width) * scaleX),
                        y: originalBox.y * scaleY,
                        width: originalBox.width * scaleX,
                        height: originalBox.height * scaleY
                    };
                    
                    const faceWidth = box.width;
                    const faceId = this.getFaceId(detection);
                    
                    // Update last seen time for this face
                    lastSeenFaces.set(faceId, Date.now());
                    
                    // Check if we have results for this face
                    const faceResult = activeFaceResults.get(faceId);
                    
                    // Update opacity for smooth fade-in
                    if (faceResult && faceResult.opacity !== undefined && faceResult.opacity < 1) {
                        faceResult.opacity = Math.min(1, faceResult.opacity + 0.1);
                    }
                    
                    // Determine box color based on status
                    let color = '#ffffff'; // Default white
                    let lineWidth = 2 * Math.min(scaleX, scaleY); // Scale line width
                    
                    if (faceResult) {
                        if (settings.enableAgeGate) {
                            color = faceResult.passed ? '#4CAF50' : '#f44336'; // Green for pass, red for fail
                        } else {
                            color = '#2196F3'; // Blue for age detected
                        }
                        lineWidth = 3 * Math.min(scaleX, scaleY);
                    } else if (faceWidth >= MONITORING_CONFIG.minFaceSize * Math.min(scaleX, scaleY) && 
                              faceWidth <= MONITORING_CONFIG.maxFaceSize * Math.min(scaleX, scaleY)) {
                        color = '#ffa500'; // Orange - in range, processing
                    } else if (faceWidth < MONITORING_CONFIG.minFaceSize * Math.min(scaleX, scaleY)) {
                        color = '#888888'; // Gray - too far
                    } else {
                        color = '#ff9800'; // Orange - too close
                    }
                    
                    // Draw face box
                    ctx.strokeStyle = color;
                    ctx.lineWidth = lineWidth;
                    ctx.strokeRect(box.x, box.y, box.width, box.height);
                    
                    // Draw result overlay if available
                    if (faceResult) {
                        if (faceResult.opacity !== undefined && faceResult.opacity < 1) {
                            ctx.globalAlpha = faceResult.opacity;
                        }
                        this.drawResultOverlay(ctx, box, faceResult, scaleX, scaleY);
                        ctx.globalAlpha = 1.0;
                    } else {
                        // Draw proximity hint
                        this.drawProximityHint(ctx, box, faceWidth / Math.min(scaleX, scaleY), scaleX, scaleY);
                    }
                });
            }
        },
        
        drawResultOverlay: function(ctx, box, result, scaleX = 1, scaleY = 1) {
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
            
            // Format the result text based on user setting
            const resultText = this.formatAgeResult(result.age, result.passed, showResults);
            
            // Adapt sizes based on screen/canvas size and scaling
            const isMobile = window.innerWidth <= 768;
            const scaleFactor = (isMobile ? 0.8 : 1) * Math.min(scaleX, scaleY);
            
            // Position the result display to the right of the face
            const resultX = box.x + box.width + (10 * scaleX);
            const resultY = box.y + box.height / 2;
            
            // Calculate font size for text measurement
            const fontSize = Math.max(10, (isMobile ? 12 : 14) * scaleFactor);
            ctx.font = `bold ${fontSize}px Arial`;
            
            // Dynamic card width based on result text length
            const textWidth = ctx.measureText(resultText).width + (20 * scaleX); // Add padding
            const cardWidth = Math.max((isMobile ? 80 : 100) * scaleFactor, textWidth);
            const cardHeight = (settings.enableAgeGate ? 40 : 35) * scaleFactor;
            
            // Ensure result stays within canvas bounds
            let displayX = resultX;
            if (displayX + cardWidth > overlayCanvas.width) {
                displayX = box.x - cardWidth - (10 * scaleX); // Show on left if no room on right
            }
            
            // Draw card background
            ctx.fillStyle = 'rgba(0, 0, 0, 0.8)';
            ctx.fillRect(displayX, resultY - cardHeight/2, cardWidth, cardHeight);
            
            // Draw card border
            ctx.strokeStyle = result.passed !== null ? (result.passed ? '#4CAF50' : '#f44336') : '#2196F3';
            ctx.lineWidth = 2 * Math.min(scaleX, scaleY);
            ctx.strokeRect(displayX, resultY - cardHeight/2, cardWidth, cardHeight);
            
            // Draw formatted result text
            ctx.fillStyle = 'white';
            ctx.font = `bold ${fontSize}px Arial`;
            ctx.textAlign = 'center';
            
            // Use the formatted result text
            if (settings.enableAgeGate && result.passed !== null) {
                // Color the text based on pass/fail
                ctx.fillStyle = result.passed ? '#4CAF50' : '#f44336';
            }
            
            ctx.fillText(resultText, displayX + cardWidth/2, resultY + (2 * scaleY));
            
            // Draw cached or averaged indicator if applicable
            if (result.averaged) {
                ctx.fillStyle = '#2196F3';
                ctx.font = `${Math.max(8, (isMobile ? 9 : 10) * scaleFactor)}px Arial`;
                ctx.fillText('AVERAGED', displayX + cardWidth/2, resultY - cardHeight/2 - (5 * scaleY));
            } else if (result.cached) {
                ctx.fillStyle = '#4CAF50';
                ctx.font = `${Math.max(8, (isMobile ? 9 : 10) * scaleFactor)}px Arial`;
                ctx.fillText('CACHED', displayX + cardWidth/2, resultY - cardHeight/2 - (5 * scaleY));
            }
        },
        
        drawProximityHint: function(ctx, box, faceWidth, scaleX = 1, scaleY = 1) {
            // Draw proximity hints above the face box
            ctx.fillStyle = '#ffffff';
            const fontSize = Math.max(10, 12 * Math.min(scaleX, scaleY));
            ctx.font = `${fontSize}px Arial`;
            ctx.textAlign = 'center';
            
            let message = '';
            if (faceWidth < MONITORING_CONFIG.minFaceSize) {
                message = 'Move closer';
            } else if (faceWidth > MONITORING_CONFIG.maxFaceSize) {
                message = 'Too close';
            } else if (currentFaceInRange) {
                const timeInRange = Date.now() - faceInRangeStartTime;
                const timeUntilCapture = MONITORING_CONFIG.captureDelay - timeInRange;
                if (timeUntilCapture > 0) {
                    message = `Hold still...`;
                }
            }
            
            if (message) {
                const textX = box.x + box.width / 2;
                const textY = box.y - (10 * scaleY);
                
                // Draw background for text
                const textWidth = ctx.measureText(message).width;
                ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
                ctx.fillRect(textX - textWidth/2 - (5 * scaleX), textY - (15 * scaleY), textWidth + (10 * scaleX), 20 * scaleY);
                
                // Draw text
                ctx.fillStyle = '#ffffff';
                ctx.fillText(message, textX, textY);
            }
        },
        
        updateStatus: function(faceWidth, inRange) {
            const statusIndicator = document.getElementById('age-estimator-status');
            if (!statusIndicator) return;
            
            if (faceWidth === 0) {
                statusIndicator.textContent = 'No face detected';
                statusIndicator.style.backgroundColor = 'rgba(128,128,128,0.7)';
            } else if (inRange) {
                statusIndicator.textContent = `Face in range`;
                statusIndicator.style.backgroundColor = 'rgba(0,255,0,0.7)';
            } else if (faceWidth < MONITORING_CONFIG.minFaceSize) {
                statusIndicator.textContent = `Move closer`;
                statusIndicator.style.backgroundColor = 'rgba(255,0,0,0.7)';
            } else {
                statusIndicator.textContent = `Too close`;
                statusIndicator.style.backgroundColor = 'rgba(255,165,0,0.7)';
            }
        },
        
        updateMetricsDisplay: function() {
            const metricsDisplay = document.getElementById('age-estimator-metrics');
            if (!metricsDisplay || typeof FaceTracker === 'undefined') return;
            
            // Don't update metrics on mobile devices
            if (this.isMobileDevice()) return;
            
            const metrics = FaceTracker.getMetrics();
            metricsDisplay.innerHTML = `
                Cache: ${metrics.cacheSize} faces | 
                Hit Rate: ${metrics.hitRate}% | 
                API Saved: ${metrics.apiReduction}%
            `;
        },
        
        captureAndAnalyze: async function() {
            if (isProcessing || !videoReady) {
                console.log('Skipping capture - processing or video not ready');
                return;
            }
            
            console.log('captureAndAnalyze called - Averaging enabled:', AVERAGING_CONFIG.enabled, 'AWS mode:', settings.useAws, 'Already collecting:', ageAveragingState.isCollecting);
            
            // Check if we should start averaging instead
            if (AVERAGING_CONFIG.enabled && !settings.useAws && !ageAveragingState.isCollecting) {
                console.log('Starting age averaging process...');
                this.startAgeAveraging();
                return;
            }
            
            isProcessing = true;
            lastCaptureTime = Date.now();
            currentFaceInRange = false;
            
            console.log('Age Estimator Photo Continuous: Capturing photo...');
            
            // Show flash effect
            this.showFlashEffect();
            
            // Verify video dimensions before capture
            if (!video.videoWidth || !video.videoHeight) {
                console.error('Video dimensions not available!');
                this.showError('Video not ready for capture. Please try again.');
                isProcessing = false;
                return;
            }
            
            // Ensure canvas uses stored dimensions
            if (canvas.width !== videoDimensions.width || canvas.height !== videoDimensions.height) {
                console.warn(`Canvas dimension mismatch. Resetting to ${videoDimensions.width}x${videoDimensions.height}`);
                canvas.width = videoDimensions.width;
                canvas.height = videoDimensions.height;
            }
            
            console.log(`Capturing with dimensions: ${canvas.width}x${canvas.height}`);
            
            try {
                // Capture photo with high quality
                const ctx = canvas.getContext('2d');
                
                // Clear canvas first
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                // Draw video frame to canvas
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                // Convert to data URL with high quality
                capturedImageData = canvas.toDataURL('image/jpeg', 0.95);
                
                // Validate captured image
                console.log(`Captured image data URL length: ${capturedImageData.length}`);
                
                if (capturedImageData.length < 5000) {
                    throw new Error('Captured image is too small, likely a canvas issue');
                }
                
                // Update status
                const statusIndicator = document.getElementById('age-estimator-status');
                if (statusIndicator) {
                    statusIndicator.textContent = 'Analyzing...';
                    statusIndicator.style.backgroundColor = 'rgba(0,123,255,0.7)';
                }
                
                if (settings.useAws) {
                    await this.analyzeWithAws();
                } else {
                    await this.analyzeWithLocal();
                }
                
            } catch (error) {
                console.error('Age Estimator Photo Continuous: Capture/Analysis error:', error);
                this.showError('Capture failed: ' + error.message);
            } finally {
                isProcessing = false;
                
                // Reset status after a delay
                setTimeout(() => {
                    const statusIndicator = document.getElementById('age-estimator-status');
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
            console.log('Age Estimator Photo Continuous: Analyzing with AWS Rekognition...');
            
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
                
                console.log('Age Estimator Photo Continuous: AWS response:', response);
                
                if (response.success) {
                    if (response.data.faces && response.data.faces.length > 0) {
                        const faceData = response.data.faces[0];
                        
                        // Cache the result if we have the detection
                        if (lastDetection && typeof FaceTracker !== 'undefined') {
                            FaceTracker.addFace(lastDetection, faceData);
                            console.log('Face data cached for future use');
                        }
                        
                        // Store result for overlay display
                        const faceId = this.getFaceId(lastDetection);
                        
                        // Check if we already have a result for this face
                        const existingResult = activeFaceResults.get(faceId);
                        
                        // Only update if result has changed or doesn't exist
                        if (!existingResult || existingResult.age !== Math.round(faceData.age)) {
                            const age = Math.round(faceData.age);
                            const passed = settings.enableAgeGate ? age >= settings.minimumAge : null;
                            
                            activeFaceResults.set(faceId, {
                                age: age,
                                passed: passed,
                                cached: false,
                                timestamp: Date.now(),
                                displayUntil: Date.now() + 500, // Ensure minimum display time
                                opacity: 0 // Start with fade-in
                            });
                            
                            // Play appropriate sound based on pass/fail
                            if (typeof AgeEstimatorSounds !== 'undefined' && settings.enableAgeGate) {
                                if (passed) {
                                    AgeEstimatorSounds.playPassSound();
                                } else {
                                    AgeEstimatorSounds.playFailSound();
                                }
                            }
                        }
                        
                        // Update last result time and handle kiosk mode
                        lastResultTime = Date.now();
                        if (settings.kioskMode) {
                            this.hideKioskDisplay();
                            this.resetKioskTimer();
                        }
                        
                        // Trigger retail mode event if enabled
                        $(document).trigger('ageEstimatorResult', {
                            age: Math.round(faceData.age),
                            passed: settings.enableAgeGate ? faceData.age >= settings.minimumAge : null,
                            method: 'aws'
                        });
                        
                        // Show success message
                        this.showMessage('Analysis complete! Result displayed next to face.');
                    } else {
                        console.warn('AWS returned success but no faces detected');
                        this.showMessage('No face detected in captured image.');
                    }
                } else {
                    throw new Error(response.data?.message || 'Analysis failed');
                }
            } catch (error) {
                console.error('Age Estimator Photo Continuous: AWS error:', error);
                if (error.responseJSON) {
                    console.error('Server response:', error.responseJSON);
                }
                
                this.showError('AWS analysis failed: ' + error.message);
            }
        },
        
        analyzeWithLocal: async function() {
            console.log('Age Estimator Photo Continuous: Analyzing with local detection...');
            
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
                            console.log('Age Estimator Photo Continuous: Image loaded, detecting faces...');
                            
                            const detections = await faceapi.detectAllFaces(img)
                                .withAgeAndGender()
                                .withFaceExpressions();
                            
                            console.log('Age Estimator Photo Continuous: Local detection found', detections.length, 'faces');
                            
                            if (detections.length > 0) {
                                const detection = detections[0];
                                const estimatedAge = Math.round(detection.age);
                                const gender = detection.gender || 'unknown';
                                const genderProbability = detection.genderProbability || 0;
                                const passed = settings.enableAgeGate ? estimatedAge >= settings.minimumAge : null;
                                
                                // Log the scan if logging is enabled
                                if (ageEstimatorPhotoParams.enableLogging === '1') {
                                    this.logSimpleScan({
                                        age: estimatedAge,
                                        gender: gender,
                                        confidence: genderProbability,
                                        face_detected: 1,
                                        age_gate_result: settings.enableAgeGate ? (passed ? 'passed' : 'failed') : '',
                                        capture_time: new Date().toISOString(),
                                        averaged: false
                                    });
                                }
                                
                                // Store result for overlay display
                                const faceId = this.getFaceId(lastDetection);
                                
                                // Check if we already have a result for this face
                                const existingResult = activeFaceResults.get(faceId);
                                
                                // Only update if result has changed or doesn't exist
                                if (!existingResult || existingResult.age !== Math.round(detection.age)) {
                                    activeFaceResults.set(faceId, {
                                        age: Math.round(detection.age),
                                        passed: settings.enableAgeGate ? detection.age >= settings.minimumAge : null,
                                        cached: false,
                                        timestamp: Date.now(),
                                        displayUntil: Date.now() + 500, // Ensure minimum display time
                                        opacity: 0 // Start with fade-in
                                    });
                                }
                                
                                // Update last result time and handle kiosk mode
                                lastResultTime = Date.now();
                                if (settings.kioskMode) {
                                    this.hideKioskDisplay();
                                    this.resetKioskTimer();
                                }
                                
                                // Trigger retail mode event if enabled
                                $(document).trigger('ageEstimatorResult', {
                                    age: Math.round(detection.age),
                                    passed: settings.enableAgeGate ? detection.age >= settings.minimumAge : null,
                                    method: 'local'
                                });
                                
                                this.showMessage('Analysis complete! Result displayed next to face.');
                                resolve();
                            } else {
                                console.log('Age Estimator Photo Continuous: No faces detected in image');
                                this.showMessage('No face detected in captured image.');
                                resolve();
                            }
                        } catch (error) {
                            console.error('Age Estimator Photo Continuous: Local detection error:', error);
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
        
        startAgeAveraging: function() {
            console.log('Starting age averaging collection...');
            console.log('Current lastDetection:', lastDetection);
            
            // Store the current detection before we start
            if (!lastDetection) {
                console.error('No face detection available for averaging');
                this.showError('No face detected. Please ensure face is in range.');
                return;
            }
            
            // Store detection for later use
            this.averagingDetection = lastDetection;
            
            // Reset state
            ageAveragingState.isCollecting = true;
            ageAveragingState.samples = [];
            ageAveragingState.currentSampleCount = 0;
            
            // Show averaging UI
            this.showAveragingProgress();
            
            // Disable capture for cooldown period
            isProcessing = true;
            
            // Start capturing samples
            this.captureNextSample();
        },
        
        captureNextSample: function() {
            if (!ageAveragingState.isCollecting) return;
            
            console.log(`Preparing to capture sample ${ageAveragingState.currentSampleCount + 1} of ${ageAveragingState.targetSamples}`);
            
            // Update UI to show we're about to capture
            this.updateAveragingProgress();
            
            // Wait a moment then capture
            setTimeout(() => {
                if (!ageAveragingState.isCollecting) return;
                
                // Capture the image
                this.captureSampleImage();
            }, 500);
        },
        
        captureSampleImage: async function() {
            console.log('Capturing sample image for averaging...');
            
            // Show flash effect
            this.showFlashEffect();
            
            // Capture photo
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Create a temporary image for this sample
            const sampleImageData = canvas.toDataURL('image/jpeg', 0.95);
            
            try {
                // Analyze the sample
                const img = new Image();
                img.crossOrigin = 'anonymous';
                
                img.onload = async () => {
                    try {
                        const detections = await faceapi.detectAllFaces(img)
                            .withAgeAndGender()
                            .withFaceExpressions();
                        
                        if (detections.length > 0) {
                            const age = Math.round(detections[0].age);
                            ageAveragingState.samples.push(age);
                            ageAveragingState.currentSampleCount++;
                            
                            console.log(`Sample ${ageAveragingState.currentSampleCount}: Age ${age}`);
                            
                            this.updateAveragingProgress();
                            
                            if (ageAveragingState.currentSampleCount < ageAveragingState.targetSamples) {
                                // Need more samples
                                setTimeout(() => {
                                    this.captureNextSample();
                                }, AVERAGING_CONFIG.sampleDelay);
                            } else {
                                // We have all samples, calculate average
                                this.calculateAndDisplayAverage();
                            }
                        } else {
                            console.log('No face detected in sample, retrying...');
                            setTimeout(() => {
                                this.captureNextSample();
                            }, 500);
                        }
                    } catch (error) {
                        console.error('Error analyzing sample:', error);
                        this.showError('Error during sample analysis: ' + error.message);
                        ageAveragingState.isCollecting = false;
                        isProcessing = false;
                    }
                };
                
                img.onerror = () => {
                    console.error('Failed to load sample image');
                    this.showError('Failed to process sample image');
                    ageAveragingState.isCollecting = false;
                    isProcessing = false;
                };
                
                img.src = sampleImageData;
                
            } catch (error) {
                console.error('Error in sample capture:', error);
                this.showError('Error capturing sample: ' + error.message);
                ageAveragingState.isCollecting = false;
                isProcessing = false;
            }
        },
        
        showAveragingProgress: function() {
            const averagingDisplay = document.getElementById('age-estimator-averaging-progress');
            if (averagingDisplay) {
                averagingDisplay.style.display = 'block';
                this.updateAveragingProgress();
            }
        },
        
        updateAveragingProgress: function() {
            const averagingDisplay = document.getElementById('age-estimator-averaging-progress');
            if (!averagingDisplay) return;
            
            const progress = ageAveragingState.currentSampleCount;
            const total = ageAveragingState.targetSamples;
            const percentage = Math.round((progress / total) * 100);
            
            let samplesHTML = '<div style="margin-bottom: 10px;">';
            samplesHTML += `<strong style="color: #2196F3;">Collecting Age Samples</strong><br>`;
            samplesHTML += `<span style="font-size: 18px;">Sample ${progress} of ${total}</span>`;
            samplesHTML += '</div>';
            
            // Progress bar
            samplesHTML += `
                <div style="background: #333; height: 20px; border-radius: 10px; overflow: hidden; margin-bottom: 10px;">
                    <div style="background: linear-gradient(90deg, #1976D2 0%, #2196F3 50%, #42A5F5 100%); 
                                height: 100%; width: ${percentage}%; transition: width 0.3s;">
                    </div>
                </div>
            `;
            
            // Show collected samples as checkmarks
            if (ageAveragingState.samples.length > 0) {
                samplesHTML += '<div style="margin-top: 10px; font-size: 12px;">';
                samplesHTML += '<strong>Samples collected:</strong><br>';
                samplesHTML += '<div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 5px;">';
                ageAveragingState.samples.forEach((age, index) => {
                    // Show checkmark icon instead of age value
                    samplesHTML += `<span style="background: #4CAF50; color: white; padding: 4px 8px; 
                                               border-radius: 50%; font-weight: bold; width: 24px; height: 24px;
                                               display: inline-flex; align-items: center; justify-content: center;
                                               font-size: 16px;">✓</span>`;
                });
                samplesHTML += '</div></div>';
            }
            
            averagingDisplay.innerHTML = samplesHTML;
        },
        
        calculateAndDisplayAverage: function() {
            console.log('Calculating average from samples:', ageAveragingState.samples);
            
            // Calculate average
            const sum = ageAveragingState.samples.reduce((a, b) => a + b, 0);
            const averageAge = Math.round(sum / ageAveragingState.samples.length);
            
            // Calculate standard deviation for confidence
            const variance = ageAveragingState.samples.reduce((acc, age) => {
                return acc + Math.pow(age - averageAge, 2);
            }, 0) / ageAveragingState.samples.length;
            const stdDev = Math.sqrt(variance);
            
            // Hide averaging progress
            const averagingDisplay = document.getElementById('age-estimator-averaging-progress');
            if (averagingDisplay) {
                averagingDisplay.style.display = 'none';
            }
            
            // Store the last captured image as the result image
            capturedImageData = canvas.toDataURL('image/jpeg', 0.95);
            
            // Display results
            this.displayAveragedResults({
                averageAge: averageAge,
                samples: ageAveragingState.samples,
                stdDev: stdDev,
                minAge: Math.min(...ageAveragingState.samples),
                maxAge: Math.max(...ageAveragingState.samples)
            });
            
            // Reset averaging state
            ageAveragingState.isCollecting = false;
            ageAveragingState.samples = [];
            ageAveragingState.currentSampleCount = 0;
            
            // Clean up stored detection
            this.averagingDetection = null;
            
            // Reset processing state after cooldown
            setTimeout(() => {
                isProcessing = false;
                lastCaptureTime = Date.now();
                console.log('Processing reset, face detection should resume');
            }, MONITORING_CONFIG.cooldownPeriod);
        },
        
        displayAveragedResults: function(data) {
            console.log('Displaying averaged results:', data);
            
            // Make sure result container is visible
            if (resultContainer) {
                resultContainer.style.display = 'block';
            }
            
            const minimumAge = parseInt(ageEstimatorPhotoParams.minimumAge || 21);
            const ageGateEnabled = ageEstimatorPhotoParams.enableAgeGate === '1';
            const passed = data.averageAge >= minimumAge;
            
            // Store result for overlay display next to face
            const detectionToUse = this.averagingDetection || lastDetection;
            console.log('Using detection for averaged result:', detectionToUse);
            
            if (detectionToUse) {
                const faceId = this.getFaceId(detectionToUse);
                console.log('Storing averaged result with faceId:', faceId);
                
                activeFaceResults.set(faceId, {
                    age: data.averageAge,
                    passed: settings.enableAgeGate ? passed : null,
                    cached: false,
                    averaged: true, // Mark as averaged result
                    timestamp: Date.now(),
                    displayUntil: Date.now() + 5000, // Show averaged results longer
                    opacity: 1 // Start fully visible for averaged results
                });
                
                console.log('Stored averaged result for overlay display:', {
                    faceId: faceId,
                    age: data.averageAge,
                    passed: passed,
                    activeFaceResults: activeFaceResults.size
                });
            } else {
                console.error('No detection available to display averaged result');
            }
            
            // Log the averaged scan if logging is enabled
            if (ageEstimatorPhotoParams.enableLogging === '1') {
                this.logSimpleScan({
                    age: data.averageAge,
                    gender: '', // Not available for averaged results
                    confidence: 0, // Not applicable for averaged results
                    face_detected: 1,
                    age_gate_result: settings.enableAgeGate ? (passed ? 'passed' : 'failed') : '',
                    capture_time: new Date().toISOString(),
                    averaged: true,
                    samples_count: data.samples.length,
                    samples_range: `${data.minAge}-${data.maxAge}`,
                    std_dev: data.stdDev.toFixed(1)
                });
            }
            
            // Update last result time and handle kiosk mode
            lastResultTime = Date.now();
            if (settings.kioskMode) {
                this.hideKioskDisplay();
                this.resetKioskTimer();
            }
            
            // Play appropriate sound
            if (typeof AgeEstimatorSounds !== 'undefined' && ageGateEnabled) {
                if (passed) {
                    AgeEstimatorSounds.playPassSound();
                } else {
                    AgeEstimatorSounds.playFailSound();
                }
            }
            
            // Trigger retail mode event if enabled
            $(document).trigger('ageEstimatorResult', {
                age: data.averageAge,
                passed: settings.enableAgeGate ? passed : null,
                method: 'averaged',
                samples: data.samples.length
            });
            
            let resultHTML = '<div class="continuous-result averaged-result">';
            
            // Add averaging indicator
            resultHTML += `
                <div class="averaging-indicator" style="position: absolute; top: 10px; right: 10px; 
                                                       background: #2196F3; color: white; padding: 4px 10px; 
                                                       border-radius: 12px; font-size: 12px; font-weight: bold;">
                    AVERAGED (${data.samples.length} samples)
                </div>
            `;
            
            if (ageGateEnabled) {
                resultHTML += `
                    <div class="age-gate-result ${passed ? 'passed' : 'failed'}">
                        <div class="pass-fail-display">
                            ${passed ? 'PASS' : 'FAIL'}
                        </div>
                    </div>
                `;
            }
            
            resultHTML += `
                <div class="age-result averaged-age-result">
                    <h3>Age Verification Complete</h3>
                    <div class="primary-result" style="background: rgba(33, 150, 243, 0.1); 
                                                     padding: 15px; border-radius: 8px; margin: 10px 0;">
                        <p class="age-display">Average Age: <strong style="color: #1976D2; font-size: 28px;">${data.averageAge} years</strong></p>
                    </div>
                    <div class="averaging-details" style="margin-top: 15px; padding: 10px; 
                                                        background: rgba(0,0,0,0.05); border-radius: 5px; 
                                                        border-left: 4px solid #2196F3;">
                        <p style="margin: 5px 0; font-size: 14px;">
                            <strong style="color: #1976D2;">Sample Details:</strong><br>
                            <span style="font-family: monospace;">Ages collected: ${data.samples.join(', ')}</span><br>
                            <span style="font-family: monospace;">Range: ${data.minAge} - ${data.maxAge} years</span><br>
                            <span style="font-family: monospace;">Standard Deviation: ±${data.stdDev.toFixed(1)} years</span>
                        </p>
                    </div>
                </div>
            `;
            
            resultHTML += `
                <div class="capture-info">
                    <p>Averaged at ${new Date().toLocaleTimeString()}</p>
                    <p style="font-size: 11px; color: #666;">Multiple samples collected for accuracy</p>
                </div>
            `;
            
            resultHTML += '</div>';
            
            // Append to results
            const newResult = document.createElement('div');
            newResult.innerHTML = resultHTML;
            newResult.style.marginBottom = '20px';
            newResult.style.paddingBottom = '20px';
            newResult.style.borderBottom = '1px solid #ddd';
            newResult.style.position = 'relative';
            newResult.style.border = '2px solid #2196F3';
            newResult.style.borderRadius = '8px';
            newResult.style.padding = '15px';
            newResult.style.background = 'linear-gradient(135deg, rgba(33, 150, 243, 0.05) 0%, rgba(33, 150, 243, 0.02) 100%)';
            
            resultContainer.insertBefore(newResult, resultContainer.firstChild);
            
            // Keep only last 5 results
            while (resultContainer.children.length > 5) {
                resultContainer.removeChild(resultContainer.lastChild);
            }
            
            // Update metrics
            this.updateMetricsDisplay();
            
            // Ensure overlay is visible
            if (overlayCanvas) {
                console.log('Overlay canvas display:', overlayCanvas.style.display, 'z-index:', overlayCanvas.style.zIndex);
            }
            
            // Show success message
            this.showMessage('Age averaging complete! Average result displayed next to face.');
            
            // Force a redraw of the overlay to ensure the result is displayed
            if (typeof faceapi !== 'undefined' && video) {
                console.log('Forcing overlay redraw for averaged result');
                // Trigger a manual check for faces to redraw the overlay
                this.checkForFaces();
            }
        },
        
        showMessage: function(message) {
            console.log('Age Estimator Photo Continuous: ' + message);
            
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = 'age-estimator-toast';
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 12px 24px;
                border-radius: 25px;
                font-size: 14px;
                z-index: 10000;
                animation: slideUp 0.3s ease;
            `;
            
            document.body.appendChild(toast);
            
            // Remove after 3 seconds
            setTimeout(() => {
                toast.style.animation = 'slideDown 0.3s ease';
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        },
        
        showError: function(error) {
            console.error('Age Estimator Photo Continuous: ' + error);
            // You can implement an error toast notification here if desired
        },
        
        getFullscreenIcon: function() {
            return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path>
            </svg>`;
        },
        
        getExitFullscreenIcon: function() {
            return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"></path>
            </svg>`;
        },
        
        // Enhanced fullscreen toggle
        toggleFullscreen: function() {
            console.log('Toggling fullscreen, current state:', this.isFullscreen());
            
            if (!this.isFullscreen()) {
                this.enterFullscreen();
            } else {
                this.exitFullscreen();
            }
        },
        
        // Enhanced fullscreen check
        isFullscreen: function() {
            return !!(document.fullscreenElement ||
                      document.webkitFullscreenElement ||
                      document.mozFullScreenElement ||
                      document.msFullscreenElement ||
                      document.querySelector('.pseudo-fullscreen'));
        },
        
        // Enhanced fullscreen enter
        enterFullscreen: function() {
            console.log('Attempting to enter fullscreen...');
            
            const container = document.querySelector('.age-estimator-photo-container');
            if (!container) {
                console.error('Container not found for fullscreen');
                return;
            }
            
            try {
                // Try standard fullscreen first
                if (container.requestFullscreen) {
                    container.requestFullscreen();
                } else if (container.webkitRequestFullscreen) {
                    container.webkitRequestFullscreen();
                } else if (container.mozRequestFullScreen) {
                    container.mozRequestFullScreen();
                } else if (container.msRequestFullscreen) {
                    container.msRequestFullscreen();
                } else {
                    // Fallback for browsers that don't support fullscreen
                    this.enterPseudoFullscreen();
                }
            } catch (error) {
                console.error('Error entering fullscreen:', error);
                this.enterPseudoFullscreen();
            }
        },
        
        // Pseudo-fullscreen fallback
        enterPseudoFullscreen: function() {
            const container = document.querySelector('.age-estimator-photo-container');
            if (!container) return;
            
            // Store original styles
            container.dataset.originalStyles = container.style.cssText;
            
            // Apply pseudo-fullscreen styles
            container.style.cssText = `
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                z-index: 9999 !important;
                background: black !important;
            `;
            
            // Add pseudo-fullscreen class
            container.classList.add('pseudo-fullscreen');
            
            // Hide body scrollbar
            document.body.style.overflow = 'hidden';
            
            // Update overlay
            setTimeout(() => {
                this.updateOverlayForFullscreen();
            }, 100);
        },

        // Add fullscreen change handler to fix overlay visibility
        handleFullscreenChange: function() {
            console.log('Fullscreen change detected');
            
            const isFullscreen = this.isFullscreen();
            const fullscreenButton = document.getElementById('age-estimator-fullscreen');
            
            if (fullscreenButton) {
                fullscreenButton.innerHTML = isFullscreen ? this.getExitFullscreenIcon() : this.getFullscreenIcon();
                fullscreenButton.title = isFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen';
            }
            
            if (isFullscreen) {
                console.log('Entered fullscreen - updating overlay');
                // Critical: Update overlay for fullscreen with a delay to ensure proper sizing
                setTimeout(() => {
                    this.updateOverlayForFullscreen();
                }, 200); // Increased delay to ensure fullscreen transition is complete
                
                // Also trigger another update after a longer delay as backup
                setTimeout(() => {
                    this.updateOverlayForFullscreen();
                }, 500);
            } else {
                console.log('Exited fullscreen - resetting overlay');
                this.resetOverlayFromFullscreen();
            }
        },

        // Add function to properly update overlay dimensions for fullscreen
        updateOverlayForFullscreen: function() {
            console.log('Updating overlay for fullscreen...');
            
            if (!video || !overlayCanvas) {
                console.warn('Video or overlay canvas not available');
                return;
            }
            
            // Get the actual video display dimensions in fullscreen
            const videoRect = video.getBoundingClientRect();
            console.log('Video rect in fullscreen:', videoRect);
            
            // Update overlay canvas to match video display size exactly
            overlayCanvas.style.position = 'absolute';
            overlayCanvas.style.top = videoRect.top + 'px';
            overlayCanvas.style.left = videoRect.left + 'px';
            overlayCanvas.style.width = videoRect.width + 'px';
            overlayCanvas.style.height = videoRect.height + 'px';
            
            // Update canvas drawing dimensions to match the video's display size
            overlayCanvas.width = videoRect.width;
            overlayCanvas.height = videoRect.height;
            
            // Store the scale factors for drawing adjustments
            const scaleX = videoRect.width / videoDimensions.width;
            const scaleY = videoRect.height / videoDimensions.height;
            
            fullscreenScale = { x: scaleX, y: scaleY };
            
            console.log('Overlay updated for fullscreen:', {
                position: `${videoRect.left}px, ${videoRect.top}px`,
                size: `${videoRect.width}x${videoRect.height}`,
                scale: fullscreenScale
            });
            
            // Ensure overlay is visible and on top
            overlayCanvas.style.display = 'block';
            overlayCanvas.style.zIndex = '1000';
            overlayCanvas.style.pointerEvents = 'none';
            
            // Force a redraw of any existing overlays with the new dimensions
            if (typeof this.drawOverlayWithResults === 'function') {
                // Clear and redraw with current detections
                this.forceOverlayRedraw();
            }
        },

        // Add function to reset overlay when exiting fullscreen
        resetOverlayFromFullscreen: function() {
            console.log('Resetting overlay from fullscreen...');
            
            if (!overlayCanvas) return;
            
            // Reset overlay to original position and size
            overlayCanvas.style.position = 'absolute';
            overlayCanvas.style.top = '0';
            overlayCanvas.style.left = '0';
            overlayCanvas.style.width = '100%';
            overlayCanvas.style.height = '100%';
            overlayCanvas.style.zIndex = '10';
            
            // Reset canvas dimensions to original video dimensions
            if (videoDimensions.width > 0 && videoDimensions.height > 0) {
                overlayCanvas.width = videoDimensions.width;
                overlayCanvas.height = videoDimensions.height;
            }
            
            // Clear fullscreen scale
            fullscreenScale = null;
            
            console.log('Overlay reset to normal dimensions');
            
            // Force a redraw
            this.forceOverlayRedraw();
        },

        // Add function to force redraw overlays
        forceOverlayRedraw: function() {
            console.log('Forcing overlay redraw...');
            
            // Trigger a detection check to redraw overlays
            if (detectionActive && video && videoReady) {
                // Use a small timeout to ensure the canvas dimensions are fully updated
                setTimeout(() => {
                    this.checkForFaces();
                }, 50);
            }
        },
        
        getFullscreenIcon: function() {
            return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path>
            </svg>`;
        },
        
        getExitFullscreenIcon: function() {
            return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"></path>
            </svg>`;
        },
        
        isIOSSafari: function() {
            const ua = window.navigator.userAgent;
            const iOS = !!ua.match(/iPad/i) || !!ua.match(/iPhone/i);
            const webkit = !!ua.match(/WebKit/i);
            const iOSSafari = iOS && webkit && !ua.match(/CriOS/i);
            return iOSSafari;
        },
        
        // Enhanced iOS detection
        isIOS: function() {
            return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        },
        
        // Improved fullscreen check
        isFullscreen: function() {
            // Check standard fullscreen
            if (document.fullscreenElement || 
                document.webkitFullscreenElement || 
                document.mozFullScreenElement || 
                document.msFullscreenElement) {
                return true;
            }
            
            // Check iOS specific fullscreen
            if (this.isIOS() && video) {
                return video.webkitDisplayingFullscreen;
            }
            
            // Check for pseudo-fullscreen
            const container = document.querySelector('.age-estimator-photo-container');
            if (container && container.classList.contains('pseudo-fullscreen')) {
                return true;
            }
            
            return false;
        },
        
        // Enhanced enter fullscreen that works on mobile
        enterFullscreen: function() {
            console.log('Attempting to enter fullscreen...');
            
            // Get the container element
            const container = document.querySelector('.age-estimator-photo-container');
            if (!container) {
                console.error('Container not found');
                return;
            }
            
            // For iOS devices, we need a different approach
            if (this.isIOS()) {
                console.log('iOS detected, using special fullscreen approach');
                
                // Create a fullscreen wrapper if it doesn't exist
                let fullscreenWrapper = document.getElementById('age-estimator-fullscreen-wrapper');
                if (!fullscreenWrapper) {
                    fullscreenWrapper = document.createElement('div');
                    fullscreenWrapper.id = 'age-estimator-fullscreen-wrapper';
                    fullscreenWrapper.style.cssText = `
                        position: fixed !important;
                        top: 0 !important;
                        left: 0 !important;
                        width: 100% !important;
                        height: 100% !important;
                        background: black !important;
                        z-index: 999999 !important;
                        display: none;
                    `;
                    document.body.appendChild(fullscreenWrapper);
                }
                
                // Clone the container into the fullscreen wrapper
                const containerClone = container.cloneNode(true);
                containerClone.id = 'age-estimator-fullscreen-clone';
                
                // Clear the wrapper and add the clone
                fullscreenWrapper.innerHTML = '';
                fullscreenWrapper.appendChild(containerClone);
                fullscreenWrapper.style.display = 'block';
                
                // Hide the original container
                container.style.display = 'none';
                
                // Re-attach video stream to cloned video element
                const clonedVideo = containerClone.querySelector('#age-estimator-photo-video');
                if (clonedVideo && stream) {
                    clonedVideo.srcObject = stream;
                }
                
                // Update references to point to cloned elements
                this.updateElementReferences(containerClone);
                
                // Add close button for iOS
                this.addIOSFullscreenCloseButton(fullscreenWrapper);
                
                // Trigger fullscreen change event
                this.handleFullscreenChange();
                
                // Prevent scrolling
                document.body.style.overflow = 'hidden';
                
            } else {
                // Standard fullscreen API approach for other devices
                console.log('Using standard fullscreen API');
                
                // Try container fullscreen first
                const requestFullscreen = container.requestFullscreen || 
                                        container.webkitRequestFullscreen || 
                                        container.mozRequestFullScreen || 
                                        container.msRequestFullscreen;
                
                if (requestFullscreen) {
                    // Add fullscreen styles to container
                    container.style.cssText += `
                        width: 100% !important;
                        height: 100% !important;
                        max-width: 100% !important;
                        max-height: 100% !important;
                        position: fixed !important;
                        top: 0 !important;
                        left: 0 !important;
                        background: black !important;
                        z-index: 999999 !important;
                    `;
                    
                    requestFullscreen.call(container).then(() => {
                        console.log('Fullscreen entered successfully');
                        this.adjustFullscreenLayout();
                    }).catch(err => {
                        console.error('Fullscreen request failed:', err);
                        
                        // Fallback: Use pseudo-fullscreen
                        this.enterPseudoFullscreen();
                    });
                } else {
                    // No fullscreen API available, use pseudo-fullscreen
                    this.enterPseudoFullscreen();
                }
            }
        },
        
        // Pseudo-fullscreen for devices without proper fullscreen API
        enterPseudoFullscreen: function() {
            console.log('Using pseudo-fullscreen fallback');
            
            const container = document.querySelector('.age-estimator-photo-container');
            if (!container) return;
            
            // Store original styles
            container.dataset.originalStyles = container.style.cssText;
            
            // Apply fullscreen-like styles
            container.style.cssText = `
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                background: black !important;
                z-index: 999999 !important;
                margin: 0 !important;
                padding: 0 !important;
                border-radius: 0 !important;
            `;
            
            // Hide body scrollbar
            document.body.style.overflow = 'hidden';
            
            // Add pseudo-fullscreen class
            container.classList.add('pseudo-fullscreen');
            
            // Adjust layout
            this.adjustFullscreenLayout();
            
            // Update fullscreen button
            this.handleFullscreenChange();
            
            // Show exit instruction on mobile
            if (this.isMobileDevice()) {
                this.showMobileFullscreenHint();
            }
        },
        
        // Exit fullscreen with mobile support
        exitFullscreen: function() {
            console.log('Attempting to exit fullscreen...');
            
            // Handle iOS fullscreen exit
            if (this.isIOS()) {
                const fullscreenWrapper = document.getElementById('age-estimator-fullscreen-wrapper');
                const originalContainer = document.querySelector('.age-estimator-photo-container');
                
                if (fullscreenWrapper && originalContainer) {
                    // Show original container
                    originalContainer.style.display = '';
                    
                    // Hide and remove wrapper
                    fullscreenWrapper.style.display = 'none';
                    
                    // Re-attach video stream to original video
                    const originalVideo = originalContainer.querySelector('#age-estimator-photo-video');
                    if (originalVideo && stream) {
                        originalVideo.srcObject = stream;
                    }
                    
                    // Restore original references
                    this.restoreOriginalReferences();
                    
                    // Re-enable scrolling
                    document.body.style.overflow = '';
                }
            } else {
                // Check for pseudo-fullscreen first
                const container = document.querySelector('.age-estimator-photo-container');
                if (container && container.classList.contains('pseudo-fullscreen')) {
                    this.exitPseudoFullscreen();
                } else {
                    // Try standard fullscreen exit
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    } else if (document.webkitExitFullscreen) {
                        document.webkitExitFullscreen();
                    } else if (document.mozCancelFullScreen) {
                        document.mozCancelFullScreen();
                    } else if (document.msExitFullscreen) {
                        document.msExitFullscreen();
                    }
                }
            }
            
            // Trigger fullscreen change handler
            this.handleFullscreenChange();
        },
        
        // Exit pseudo-fullscreen
        exitPseudoFullscreen: function() {
            const container = document.querySelector('.age-estimator-photo-container');
            if (!container) return;
            
            // Restore original styles
            if (container.dataset.originalStyles) {
                container.style.cssText = container.dataset.originalStyles;
                delete container.dataset.originalStyles;
            }
            
            // Remove pseudo-fullscreen class
            container.classList.remove('pseudo-fullscreen');
            
            // Restore body scrollbar
            document.body.style.overflow = '';
            
            // Reset layout
            this.resetOverlayDimensions();
        },
        
        // Enhanced mobile detection
        isMobileDevice: function() {
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            const isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
            const isSmallScreen = window.innerWidth <= 1024; // Increased threshold for tablets
            
            return isMobile || (isTouchDevice && isSmallScreen);
        },
        
        handleFullscreenChange: function() {
            console.log('Fullscreen change detected');
            
            const isFullscreen = this.isFullscreen();
            const fullscreenButton = document.getElementById('age-estimator-fullscreen');
            
            if (fullscreenButton) {
                fullscreenButton.innerHTML = isFullscreen ? this.getExitFullscreenIcon() : this.getFullscreenIcon();
                fullscreenButton.title = isFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen';
            }
            
            if (isFullscreen) {
                console.log('Entered fullscreen - updating overlay');
                // Critical: Update overlay for fullscreen with a delay to ensure proper sizing
                setTimeout(() => {
                    this.updateOverlayForFullscreen();
                }, 200); // Increased delay to ensure fullscreen transition is complete
                
                // Also trigger another update after a longer delay as backup
                setTimeout(() => {
                    this.updateOverlayForFullscreen();
                }, 500);
            } else {
                console.log('Exited fullscreen - resetting overlay');
                this.resetOverlayFromFullscreen();
            }
        },
        
        updateOverlayForFullscreen: function() {
            console.log('Updating overlay for fullscreen...');
            
            if (!video || !overlayCanvas) {
                console.warn('Video or overlay canvas not available');
                return;
            }
            
            // Get the actual video display dimensions in fullscreen
            const videoRect = video.getBoundingClientRect();
            console.log('Video rect in fullscreen:', videoRect);
            
            // Update overlay canvas to match video display size exactly
            overlayCanvas.style.position = 'absolute';
            overlayCanvas.style.top = videoRect.top + 'px';
            overlayCanvas.style.left = videoRect.left + 'px';
            overlayCanvas.style.width = videoRect.width + 'px';
            overlayCanvas.style.height = videoRect.height + 'px';
            
            // Update canvas drawing dimensions to match the video's display size
            overlayCanvas.width = videoRect.width;
            overlayCanvas.height = videoRect.height;
            
            // Store the scale factors for drawing adjustments
            const scaleX = videoRect.width / videoDimensions.width;
            const scaleY = videoRect.height / videoDimensions.height;
            
            fullscreenScale = { x: scaleX, y: scaleY };
            
            console.log('Overlay updated for fullscreen:', {
                position: `${videoRect.left}px, ${videoRect.top}px`,
                size: `${videoRect.width}x${videoRect.height}`,
                scale: fullscreenScale
            });
            
            // Ensure overlay is visible and on top
            overlayCanvas.style.display = 'block';
            overlayCanvas.style.zIndex = '1000';
            overlayCanvas.style.pointerEvents = 'none';
            
            // Force a redraw of any existing overlays with the new dimensions
            if (typeof this.drawOverlayWithResults === 'function') {
                // Clear and redraw with current detections
                this.forceOverlayRedraw();
            }
        },
        
        resetOverlayDimensions: function() {
            if (!overlayCanvas || !videoDimensions.width || !videoDimensions.height) return;
            
            // Reset scale factors
            fullscreenScale = null;
            
            // Reset dimensions
            overlayCanvas.width = videoDimensions.width;
            overlayCanvas.height = videoDimensions.height;
            overlayCanvas.style.width = '100%';
            overlayCanvas.style.height = '100%';
            overlayCanvas.style.left = '0';
            overlayCanvas.style.top = '0';
            overlayCanvas.style.position = 'absolute';
        },
        
        // Add function to reset overlay when exiting fullscreen
        resetOverlayFromFullscreen: function() {
            console.log('Resetting overlay from fullscreen...');
            
            if (!overlayCanvas) return;
            
            // Reset overlay to original position and size
            overlayCanvas.style.position = 'absolute';
            overlayCanvas.style.top = '0';
            overlayCanvas.style.left = '0';
            overlayCanvas.style.width = '100%';
            overlayCanvas.style.height = '100%';
            overlayCanvas.style.zIndex = '10';
            
            // Reset canvas dimensions to original video dimensions
            if (videoDimensions.width > 0 && videoDimensions.height > 0) {
                overlayCanvas.width = videoDimensions.width;
                overlayCanvas.height = videoDimensions.height;
            }
            
            // Clear fullscreen scale
            fullscreenScale = null;
            
            console.log('Overlay reset to normal dimensions');
            
            // Force a redraw
            this.forceOverlayRedraw();
        },
        
        // Add function to force redraw overlays
        forceOverlayRedraw: function() {
            console.log('Forcing overlay redraw...');
            
            // Trigger a detection check to redraw overlays
            if (detectionActive && video && videoReady) {
                // Use a small timeout to ensure the canvas dimensions are fully updated
                setTimeout(() => {
                    this.checkForFaces();
                }, 50);
            }
        },
        
        // Adjust layout for fullscreen mode
        adjustFullscreenLayout: function() {
            const container = document.querySelector('.age-estimator-photo-container, #age-estimator-fullscreen-clone');
            if (!container) return;
            
            // Get all elements that need adjustment
            const video = container.querySelector('#age-estimator-photo-video');
            const overlayCanvas = container.querySelector('#age-estimator-photo-overlay');
            const kioskDisplay = container.querySelector('#age-estimator-kiosk-display');
            const cameraContainer = container.querySelector('#age-estimator-photo-camera');
            
            // Adjust camera container to fill screen
            if (cameraContainer) {
                cameraContainer.style.cssText = `
                    width: 100% !important;
                    height: 100% !important;
                    position: relative !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    background: black !important;
                `;
            }
            
            // Adjust video to maintain aspect ratio
            if (video && video.videoWidth && video.videoHeight) {
                const screenRatio = window.innerWidth / window.innerHeight;
                const videoRatio = video.videoWidth / video.videoHeight;
                
                if (screenRatio > videoRatio) {
                    // Screen is wider than video
                    video.style.cssText = `
                        height: 100% !important;
                        width: auto !important;
                        max-width: none !important;
                        display: block !important;
                    `;
                } else {
                    // Screen is taller than video
                    video.style.cssText = `
                        width: 100% !important;
                        height: auto !important;
                        max-height: none !important;
                        display: block !important;
                    `;
                }
            }
            
            // Adjust overlay canvas to match video
            if (overlayCanvas && video) {
                // Wait for video position to settle
                setTimeout(() => {
                    const videoRect = video.getBoundingClientRect();
                    overlayCanvas.style.cssText = `
                        position: absolute !important;
                        left: ${videoRect.left}px !important;
                        top: ${videoRect.top}px !important;
                        width: ${videoRect.width}px !important;
                        height: ${videoRect.height}px !important;
                        pointer-events: none !important;
                        z-index: 10 !important;
                    `;
                }, 100);
            }
            
            // Ensure kiosk display covers full area
            if (kioskDisplay) {
                kioskDisplay.style.cssText += `
                    position: absolute !important;
                    top: 0 !important;
                    left: 0 !important;
                    width: 100% !important;
                    height: 100% !important;
                    z-index: 20 !important;
                `;
            }
            
            // Show fullscreen button on mobile (for exit)
            const fullscreenButton = container.querySelector('#age-estimator-fullscreen');
            if (fullscreenButton && this.isMobileDevice()) {
                fullscreenButton.style.display = 'block';
                fullscreenButton.style.zIndex = '999999';
            }
        },
        
        // Add iOS fullscreen close button
        addIOSFullscreenCloseButton: function(wrapper) {
            const closeButton = document.createElement('button');
            closeButton.id = 'age-estimator-ios-close';
            closeButton.innerHTML = '✕';
            closeButton.style.cssText = `
                position: absolute !important;
                top: 20px !important;
                right: 20px !important;
                width: 50px !important;
                height: 50px !important;
                background: rgba(255, 255, 255, 0.2) !important;
                border: 2px solid white !important;
                border-radius: 50% !important;
                color: white !important;
                font-size: 24px !important;
                font-weight: bold !important;
                cursor: pointer !important;
                z-index: 1000000 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            `;
            
            closeButton.onclick = () => this.exitFullscreen();
            wrapper.appendChild(closeButton);
        },
        
        // Update element references for cloned container
        updateElementReferences: function(container) {
            // Update all the element references to point to cloned elements
            video = container.querySelector('#age-estimator-photo-video');
            canvas = container.querySelector('#age-estimator-photo-canvas');
            overlayCanvas = container.querySelector('#age-estimator-photo-overlay');
            cameraContainer = container.querySelector('#age-estimator-photo-camera');
            
            // Re-adjust layout
            this.adjustFullscreenLayout();
        },
        
        // Restore original element references
        restoreOriginalReferences: function() {
            const originalContainer = document.querySelector('.age-estimator-photo-container');
            if (originalContainer) {
                video = originalContainer.querySelector('#age-estimator-photo-video');
                canvas = originalContainer.querySelector('#age-estimator-photo-canvas');
                overlayCanvas = originalContainer.querySelector('#age-estimator-photo-overlay');
                cameraContainer = originalContainer.querySelector('#age-estimator-photo-camera');
            }
        },
        
        // Show mobile fullscreen hint
        showMobileFullscreenHint: function() {
            const hint = document.createElement('div');
            hint.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 20px;
                border-radius: 10px;
                text-align: center;
                z-index: 1000000;
                font-size: 16px;
            `;
            hint.innerHTML = 'Tap the exit button to leave fullscreen';
            
            document.body.appendChild(hint);
            
            setTimeout(() => {
                hint.style.opacity = '0';
                hint.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    document.body.removeChild(hint);
                }, 500);
            }, 3000);
        },
        
        showKioskDisplay: function() {
            const kioskDisplay = document.getElementById('age-estimator-kiosk-display');
            if (kioskDisplay) {
                // Use CSS classes for showing
                kioskDisplay.classList.remove('kiosk-hidden');
                kioskDisplay.classList.add('kiosk-visible');
                console.log('Kiosk display shown (class: kiosk-visible)');
                
                // Force a reflow to ensure the change is applied
                kioskDisplay.offsetHeight;
                
                // Debug: check computed styles
                const computed = window.getComputedStyle(kioskDisplay);
                console.log('Kiosk computed styles after show:', {
                    transform: computed.transform,
                    opacity: computed.opacity,
                    visibility: computed.visibility,
                    left: computed.left,
                    className: kioskDisplay.className
                });
            } else {
                console.warn('Kiosk display element not found');
            }
        },
        
        hideKioskDisplay: function() {
            const kioskDisplay = document.getElementById('age-estimator-kiosk-display');
            if (kioskDisplay) {
                // Use CSS classes for hiding
                kioskDisplay.classList.remove('kiosk-visible');
                kioskDisplay.classList.add('kiosk-hidden');
                console.log('Kiosk display hidden (class: kiosk-hidden)');
                
                // Force a reflow to ensure the change is applied
                kioskDisplay.offsetHeight;
                
                // Debug: check computed styles
                const computed = window.getComputedStyle(kioskDisplay);
                console.log('Kiosk computed styles after hide:', {
                    transform: computed.transform,
                    opacity: computed.opacity,
                    visibility: computed.visibility,
                    left: computed.left,
                    className: kioskDisplay.className
                });
            } else {
                console.warn('Kiosk display element not found when trying to hide');
            }
        },
        
        resetKioskTimer: function() {
            // Clear existing timer
            if (kioskReturnTimer) {
                clearTimeout(kioskReturnTimer);
                kioskReturnTimer = null;
            }
            
            // Set new timer to return to kiosk display
            kioskReturnTimer = setTimeout(() => {
                console.log('Kiosk timer expired, checking if should show kiosk...');
                // Update lastResultTime to allow kiosk to show again
                lastResultTime = Date.now() - (settings.kioskDisplayTime * 1000) - 1000;
                // The next checkForFaces call will show the kiosk if no faces are detected
            }, settings.kioskDisplayTime * 1000);
            console.log('Kiosk timer set for', settings.kioskDisplayTime, 'seconds');
        },
        
        switchCamera: async function() {
            const selectedDeviceId = cameraSelector.value;
            if (selectedDeviceId === currentDeviceId) return;
            
            console.log('Switching to camera:', selectedDeviceId);
            
            // Store current monitoring state
            const wasMonitoring = detectionActive;
            
            // Stop current stream
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            
            // Clear current state
            detectionActive = false;
            if (monitoringInterval) {
                clearInterval(monitoringInterval);
                monitoringInterval = null;
            }
            
            // Update device ID
            currentDeviceId = selectedDeviceId;
            
            try {
                // Start new stream with selected camera
                const constraints = {
                    video: {
                        deviceId: { exact: selectedDeviceId },
                        width: { ideal: 640 },
                        height: { ideal: 480 }
                    }
                };
                
                stream = await navigator.mediaDevices.getUserMedia(constraints);
                video.srcObject = stream;
                
                // Wait for video to be ready
                video.onloadedmetadata = () => {
                    // Update video dimensions
                    videoDimensions.width = video.videoWidth;
                    videoDimensions.height = video.videoHeight;
                    videoReady = true;
                    
                    // Update canvas dimensions
                    canvas.width = videoDimensions.width;
                    canvas.height = videoDimensions.height;
                    overlayCanvas.width = videoDimensions.width;
                    overlayCanvas.height = videoDimensions.height;
                    
                    // Restart monitoring if it was active
                    if (wasMonitoring) {
                        this.startMonitoring();
                    }
                    
                    console.log('Camera switched successfully');
                };
                
            } catch (error) {
                console.error('Error switching camera:', error);
                this.showError('Failed to switch camera. Please try again.');
                
                // Try to restart with previous camera
                currentDeviceId = null;
                this.startCamera();
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
        },
        
        showMessage: function(message) {
            console.log('Age Estimator Photo Continuous: ' + message);
            
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = 'age-estimator-toast';
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 12px 24px;
                border-radius: 25px;
                font-size: 14px;
                z-index: 10000;
                animation: slideUp 0.3s ease;
            `;
            
            document.body.appendChild(toast);
            
            // Remove after 3 seconds
            setTimeout(() => {
                toast.style.animation = 'slideDown 0.3s ease';
                setTimeout(() => {
                    if (document.body.contains(toast)) {
                        document.body.removeChild(toast);
                    }
                }, 300);
            }, 3000);
        },
        
        showError: function(error) {
            console.error('Age Estimator Photo Continuous: ' + error);
            
            // Create error toast notification
            const toast = document.createElement('div');
            toast.className = 'age-estimator-error-toast';
            toast.textContent = error;
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: rgba(244, 67, 54, 0.9);
                color: white;
                padding: 12px 24px;
                border-radius: 25px;
                font-size: 14px;
                z-index: 10000;
                animation: slideUp 0.3s ease;
            `;
            
            document.body.appendChild(toast);
            
            // Remove after 5 seconds (longer for errors)
            setTimeout(() => {
                toast.style.animation = 'slideDown 0.3s ease';
                setTimeout(() => {
                    if (document.body.contains(toast)) {
                        document.body.removeChild(toast);
                    }
                }, 300);
            }, 5000);
        }
    };
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Age Estimator Photo Continuous Overlay: DOM loaded, initializing...');
        photoAgeEstimator.init();
    });
    
    // Make photoAgeEstimator globally available
    window.photoAgeEstimator = photoAgeEstimator;
    
})(jQuery);

// Mobile fullscreen implementation complete - removed force hide code