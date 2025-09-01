/**
 * Photo-Based Age Estimator JavaScript - Continuous Monitoring Version
 * Handles automatic face detection and capture based on proximity
 * Integrated with Face Tracking for API optimization
 * 
 * FIXED VERSION - Ensures proper canvas dimensions for AWS Rekognition
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
    
    // CRITICAL: Track video ready state
    let videoReady = false;
    let videoDimensions = { width: 0, height: 0 };
    
    // Continuous monitoring settings
    let monitoringInterval = null;
    let lastCaptureTime = 0;
    let isProcessing = false;
    let detectionActive = false;
    
    // Kiosk mode variables
    let kioskMode = false;
    let kioskImage = '';
    let kioskDisplayTime = 5;
    let kioskTimeout = null;
    let kioskDisplay = null;
    
    // Configuration - get from settings or use defaults
    const MONITORING_CONFIG = {
        checkInterval: 100, // Check for faces every 100ms
        minFaceSize: ageEstimatorPhotoParams?.minFaceSize || 150, // Minimum face width in pixels to trigger capture
        maxFaceSize: ageEstimatorPhotoParams?.maxFaceSize || 350, // Maximum face width (too close)
        captureDelay: ageEstimatorPhotoParams?.captureDelay || 500, // Wait time after face is in range before capturing
        cooldownPeriod: ageEstimatorPhotoParams?.cooldownPeriod || 5000, // Wait time before capturing another face
        faceStabilityFrames: 3, // Number of frames face must be stable
        minConfidence: 0.7 // Minimum detection confidence
    };
    
    // Face tracking
    let faceHistory = [];
    let currentFaceInRange = false;
    let faceInRangeStartTime = 0;
    let lastDetection = null; // Store last detection for caching
    
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
            console.log('Age Estimator Photo Continuous - Initializing...');
            
            // Check if parameters are available
            if (typeof ageEstimatorPhotoParams === 'undefined') {
                console.error('Age Estimator Photo: Parameters not defined');
                return;
            }
            
            // Log parameters for debugging
            console.log('Age Estimator Photo Continuous - Parameters:', ageEstimatorPhotoParams);
            
            // Parse settings
            settings.useAws = ageEstimatorPhotoParams.mode === 'aws';
            settings.showEmotions = ageEstimatorPhotoParams.showEmotions === '1';
            settings.showAttributes = ageEstimatorPhotoParams.showAttributes === '1';
            settings.privacyMode = ageEstimatorPhotoParams.privacyMode === '1';
            
            console.log('Age Estimator Photo Continuous - Settings:', settings);
            
            // Initialize DOM elements
            this.initializeElements();
            
            // Initialize kiosk mode
            this.initializeKioskMode();
            
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
            
            // Show ready message
            this.showMessage('Ready to start. Click "Start Monitoring" to begin automatic face detection.');
            
            // Show kiosk display initially if enabled
            if (kioskMode && kioskDisplay && !stream) {
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
            cameraContainer.style.position = 'relative';
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
        },
        
        setupEventListeners: function() {
            if (startCameraButton) {
                startCameraButton.addEventListener('click', this.startCamera.bind(this));
            }
            if (stopCameraButton) {
                stopCameraButton.addEventListener('click', this.stopCamera.bind(this));
            }
        },
        
        initializeKioskMode: function() {
            const container = document.querySelector('.age-estimator-photo-container');
            if (container) {
                // Get kiosk mode settings
                kioskMode = container.getAttribute('data-kiosk-mode') === 'true';
                kioskImage = container.getAttribute('data-kiosk-image');
                kioskDisplayTime = parseInt(container.getAttribute('data-kiosk-display-time')) || 5;
                kioskDisplay = document.getElementById('age-estimator-kiosk-display');
                
                console.log('=== Kiosk Mode Initialization ===');
                console.log('Kiosk Mode:', kioskMode ? 'Enabled' : 'Disabled');
                console.log('Kiosk Image:', kioskImage || 'Not set');
                console.log('Display Time:', kioskDisplayTime + ' seconds');
                console.log('Kiosk Display Element:', kioskDisplay ? 'Found' : 'Not found');
                
                // Start with kiosk display if enabled and camera not active
                if (kioskMode && kioskDisplay && !stream) {
                    console.log('Showing kiosk display on initialization');
                    this.showKioskDisplay();
                }
            }
        },
        
        showKioskDisplay: function() {
            console.log('showKioskDisplay called');
            console.log('kioskMode:', kioskMode);
            console.log('kioskDisplay element:', kioskDisplay);
            
            if (!kioskMode || !kioskDisplay) {
                console.log('Cannot show kiosk - mode disabled or element not found');
                return;
            }
            
            // Check if there's an image to display
            const kioskImg = kioskDisplay.querySelector('img');
            if (!kioskImg) {
                console.log('No kiosk image found in display element');
                return;
            }
            
            console.log('Showing kiosk display');
            
            // Hide camera and results
            if (video) {
                video.style.display = 'none';
                console.log('Video hidden');
            }
            if (overlayCanvas) {
                overlayCanvas.style.display = 'none';
                console.log('Overlay canvas hidden');
            }
            if (resultContainer) {
                // Don't clear results, just hide them
                resultContainer.style.display = 'none';
                console.log('Result container hidden');
            }
            
            // Show kiosk display
            kioskDisplay.style.display = 'block';
            console.log('Kiosk display shown - current style:', kioskDisplay.style.display);
            console.log('Kiosk image src:', kioskImg.src);
            
            // Clear any existing timeout
            if (kioskTimeout) {
                clearTimeout(kioskTimeout);
                kioskTimeout = null;
            }
        },
        
        hideKioskDisplay: function() {
            if (!kioskDisplay) return;
            
            console.log('Hiding kiosk display');
            kioskDisplay.style.display = 'none';
            
            // Show camera when kiosk is hidden
            if (video && stream) {
                video.style.display = 'block';
            }
        },
        
        scheduleReturnToKiosk: function() {
            if (!kioskMode) {
                console.log('Kiosk mode not enabled, skipping schedule');
                return;
            }
            
            console.log(`Scheduling return to kiosk in ${kioskDisplayTime} seconds`);
            console.log('Kiosk display element:', kioskDisplay);
            console.log('Current kiosk display style:', kioskDisplay ? kioskDisplay.style.display : 'element not found');
            
            // Clear any existing timeout
            if (kioskTimeout) {
                clearTimeout(kioskTimeout);
            }
            
            // Store reference to this for use in timeout
            const self = this;
            
            // Schedule return to kiosk display
            kioskTimeout = setTimeout(() => {
                console.log('Kiosk timeout fired - returning to ad display');
                self.showKioskDisplay();
                // Reset monitoring state
                currentFaceInRange = false;
                faceInRangeStartTime = 0;
                isProcessing = false;
            }, kioskDisplayTime * 1000);
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
            
            console.log('Age Estimator Photo Continuous: face-api.js is available, loading models...');
            
            try {
                const modelsPath = ageEstimatorPhotoParams.modelsPath;
                console.log('Age Estimator Photo Continuous: Loading models from', modelsPath);
                
                // Load models sequentially
                console.log('Loading SSD MobileNet v1...');
                await faceapi.nets.ssdMobilenetv1.loadFromUri(modelsPath);
                
                console.log('Loading Age Gender Net...');
                await faceapi.nets.ageGenderNet.loadFromUri(modelsPath);
                
                console.log('Loading Face Expression Net...');
                await faceapi.nets.faceExpressionNet.loadFromUri(modelsPath);
                
                // Load face recognition models for tracking
                console.log('Loading Face Landmark Net...');
                await faceapi.nets.faceLandmark68Net.loadFromUri(modelsPath);
                
                console.log('Loading Face Recognition Net...');
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
                console.log('Loading SSD MobileNet v1 for face detection...');
                await faceapi.nets.ssdMobilenetv1.loadFromUri(modelsPath);
                
                console.log('Loading Face Landmark Net for face tracking...');
                await faceapi.nets.faceLandmark68Net.loadFromUri(modelsPath);
                
                console.log('Loading Face Recognition Net for face tracking...');
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
        
        startCamera: async function() {
            try {
                console.log('Age Estimator Photo Continuous: Starting camera...');
                
                // Disable start button during initialization
                if (startCameraButton) {
                    startCameraButton.disabled = true;
                    startCameraButton.textContent = 'Starting...';
                }
                
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'user',
                        width: { ideal: 640 },
                        height: { ideal: 480 }
                    }
                });
                
                video.srcObject = stream;
                
                // Hide kiosk display when camera starts
                this.hideKioskDisplay();
                
                // CRITICAL: Wait for video to be fully ready
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
                    
                    console.log(`Canvas dimensions set to: ${canvas.width}x${canvas.height}`);
                    
                    // Show video and overlay
                    video.style.display = 'block';
                    overlayCanvas.style.display = 'block';
                    
                    // Show status indicator
                    const statusIndicator = document.getElementById('age-estimator-status');
                    if (statusIndicator) {
                        statusIndicator.style.display = 'block';
                        statusIndicator.textContent = 'Monitoring...';
                    }
                    
                    // Show metrics display
                    const metricsDisplay = document.getElementById('age-estimator-metrics');
                    if (metricsDisplay) {
                        metricsDisplay.style.display = 'block';
                        this.updateMetricsDisplay();
                    }
                    
                    this.updateUI('monitoring');
                    this.showMessage('Monitoring active. Move closer to the camera to trigger automatic capture.');
                    
                    // Start continuous monitoring
                    this.startMonitoring();
                    
                    // Start metrics update interval
                    setInterval(() => this.updateMetricsDisplay(), 1000);
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
            if (!video || !videoReady || isProcessing) return;
            
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
                
                // Draw overlay
                this.drawOverlay(detections);
                
                // Process detections
                if (detections.length > 0) {
                    // Hide kiosk when face detected
                    this.hideKioskDisplay();
                    
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
                                // Display cached result immediately
                                console.log('Using cached result for face');
                                this.displayCachedAwsResults(cachedData);
                                return; // Skip capture process
                            }
                        }
                        
                        // No cache hit, proceed with normal capture logic
                        if (!currentFaceInRange) {
                            // Face just entered range
                            currentFaceInRange = true;
                            faceInRangeStartTime = Date.now();
                            console.log('Face entered capture range');
                        } else {
                            // Face still in range, check if stable enough to capture
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
                }
                
            } catch (error) {
                console.error('Error in face detection:', error);
            }
        },
        
        drawOverlay: function(detections) {
            const ctx = overlayCanvas.getContext('2d');
            ctx.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
            
            if (detections.length > 0) {
                const detection = detections[0];
                const box = detection.detection.box;
                const faceWidth = box.width;
                
                // Check if this face is cached
                let isCached = false;
                if (typeof FaceTracker !== 'undefined' && settings.useAws) {
                    const cachedData = FaceTracker.checkFace(detection);
                    isCached = cachedData !== null;
                }
                
                // Determine color based on proximity and cache status
                let color = '#ff0000'; // Red - too far
                let message = 'Move closer';
                
                if (faceWidth >= MONITORING_CONFIG.minFaceSize && faceWidth <= MONITORING_CONFIG.maxFaceSize) {
                    if (isCached) {
                        color = '#4CAF50'; // Green - cached
                        message = 'Cached';
                    } else {
                        color = '#00ff00'; // Bright green - in range
                        message = 'In range';
                        
                        // Show countdown if capturing soon
                        if (currentFaceInRange) {
                            const timeInRange = Date.now() - faceInRangeStartTime;
                            const timeUntilCapture = MONITORING_CONFIG.captureDelay - timeInRange;
                            if (timeUntilCapture > 0) {
                                message = `Capturing in ${Math.ceil(timeUntilCapture / 1000)}s`;
                            }
                        }
                    }
                } else if (faceWidth > MONITORING_CONFIG.maxFaceSize) {
                    color = '#ffa500'; // Orange - too close
                    message = 'Too close';
                }
                
                // Draw face box
                ctx.strokeStyle = color;
                ctx.lineWidth = isCached ? 4 : 3;
                ctx.strokeRect(box.x, box.y, box.width, box.height);
                
                // Draw capture progress if in range and not cached
                if (!isCached && currentFaceInRange && color === '#00ff00') {
                    const timeInRange = Date.now() - faceInRangeStartTime;
                    const captureProgress = Math.min(timeInRange / MONITORING_CONFIG.captureDelay, 1);
                    
                    // Draw progress fill
                    ctx.fillStyle = `rgba(0, 255, 0, ${0.1 + 0.2 * captureProgress})`;
                    ctx.fillRect(box.x, box.y, box.width, box.height);
                }
                
                // Draw message
                ctx.fillStyle = color;
                ctx.font = 'bold 16px Arial';
                ctx.fillText(message, box.x, box.y - 10);
                
                // Draw cached indicator if applicable
                if (isCached) {
                    ctx.fillStyle = '#4CAF50';
                    ctx.fillRect(box.x + box.width - 60, box.y - 25, 55, 20);
                    ctx.fillStyle = 'white';
                    ctx.font = 'bold 12px Arial';
                    ctx.fillText('CACHED', box.x + box.width - 55, box.y - 10);
                }
                
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
        
        updateMetricsDisplay: function() {
            const metricsDisplay = document.getElementById('age-estimator-metrics');
            if (!metricsDisplay || typeof FaceTracker === 'undefined') return;
            
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
            
            isProcessing = true;
            lastCaptureTime = Date.now();
            currentFaceInRange = false;
            
            console.log('Age Estimator Photo Continuous: Capturing photo...');
            
            // Show flash effect
            this.showFlashEffect();
            
            // CRITICAL: Verify video dimensions before capture
            if (!video.videoWidth || !video.videoHeight) {
                console.error('Video dimensions not available!');
                this.showError('Video not ready for capture. Please try again.');
                isProcessing = false;
                return;
            }
            
            // CRITICAL: Ensure canvas uses stored dimensions, not current video dimensions
            // This prevents dimension mismatch issues
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
                
                // Verify image format
                if (!capturedImageData.startsWith('data:image/jpeg;base64,')) {
                    throw new Error('Invalid image format');
                }
                
                // Extract base64 data
                const base64Data = capturedImageData.split(',')[1];
                if (!base64Data || base64Data.length < 1000) {
                    throw new Error('Invalid base64 data');
                }
                
                // DEBUG: Show captured image in debug mode
                if (window.location.hash === '#debug') {
                    const debugWin = window.open('', '_blank', 'width=800,height=600');
                    debugWin.document.write(`
                        <h2>Captured Image Debug</h2>
                        <p>Canvas: ${canvas.width}x${canvas.height}</p>
                        <p>Video: ${video.videoWidth}x${video.videoHeight}</p>
                        <p>Image size: ${capturedImageData.length} chars</p>
                        <p>Base64 size: ${base64Data.length} chars</p>
                        <p>Time: ${new Date().toLocaleTimeString()}</p>
                        <img src="${capturedImageData}" style="border: 2px solid red; max-width: 100%;">
                        <textarea style="width: 100%; height: 100px;">${capturedImageData.substring(0, 200)}...</textarea>
                    `);
                }
                
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
                // Log what we're sending
                console.log('Sending to AWS:', {
                    imageLength: capturedImageData.length,
                    imageStart: capturedImageData.substring(0, 50),
                    isDataURL: capturedImageData.startsWith('data:')
                });
                
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
                        
                        this.displayAwsResults(response.data.faces);
                    } else {
                        console.warn('AWS returned success but no faces detected');
                        this.displayNoFaceMessage();
                    }
                } else {
                    throw new Error(response.data?.message || 'Analysis failed');
                }
            } catch (error) {
                console.error('Age Estimator Photo Continuous: AWS error:', error);
                if (error.responseJSON) {
                    console.error('Server response:', error.responseJSON);
                }
                
                // Try fallback to local if available
                if (isModelLoaded && !settings.useAws) {
                    console.log('Age Estimator Photo Continuous: Falling back to local analysis');
                    await this.analyzeWithLocal();
                } else {
                    this.showError('AWS analysis failed: ' + error.message);
                }
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
                                this.displayLocalResults(detections);
                                resolve();
                            } else {
                                console.log('Age Estimator Photo Continuous: No faces detected in image');
                                this.displayNoFaceMessage();
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
        
        displayCachedAwsResults: function(cachedData) {
            console.log('Displaying cached AWS results');
            
            // Make sure result container is visible
            if (resultContainer) {
                resultContainer.style.display = 'block';
            }
            
            // Create a minimal result display for cached data
            const estimatedAge = Math.round(cachedData.age);
            const minimumAge = parseInt(ageEstimatorPhotoParams.minimumAge || 21);
            const ageGateEnabled = ageEstimatorPhotoParams.enableAgeGate === '1';
            
            // Play appropriate sound for cached results too
            if (typeof AgeEstimatorSounds !== 'undefined' && ageGateEnabled) {
                const passed = estimatedAge >= minimumAge;
                if (passed) {
                    AgeEstimatorSounds.playPassSound();
                } else {
                    AgeEstimatorSounds.playFailSound();
                }
            }
            
            let resultHTML = '<div class="continuous-result cached-result">';
            
            // Add cached indicator
            resultHTML += `
                <div class="cache-indicator" style="position: absolute; top: 10px; right: 10px; background: #4CAF50; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;">
                    CACHED
                </div>
            `;
            
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
                        <h3>Age Verification Complete (Cached)</h3>
                        <div class="primary-result">
                            <p class="age-display">Estimated Age: <strong>${estimatedAge} years</strong></p>
                        </div>
                    </div>
                `;
            }
            
            resultHTML += `
                <div class="capture-info">
                    <p>Cached result used at ${new Date().toLocaleTimeString()}</p>
                    <p style="font-size: 11px; color: #666;">No API call needed - result from cache</p>
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
            newResult.style.border = '2px solid #4CAF50';
            newResult.style.borderRadius = '8px';
            newResult.style.padding = '15px';
            newResult.style.backgroundColor = 'rgba(76, 175, 80, 0.05)';
            
            resultContainer.insertBefore(newResult, resultContainer.firstChild);
            
            // Keep only last 5 results
            while (resultContainer.children.length > 5) {
                resultContainer.removeChild(resultContainer.lastChild);
            }
            
            // Update metrics
            this.updateMetricsDisplay();
            
            // Schedule return to kiosk after showing results
            this.scheduleReturnToKiosk();
        },
        
        displayAwsResults: function(faces) {
            console.log('Age Estimator Photo Continuous: Displaying AWS results for', faces.length, 'face(s)');
            
            // Make sure result container is visible
            if (resultContainer) {
                resultContainer.style.display = 'block';
            }
            
            const face = faces[0];
            const estimatedAge = Math.round(face.age);
            const minimumAge = parseInt(ageEstimatorPhotoParams.minimumAge || 21);
            const ageGateEnabled = ageEstimatorPhotoParams.enableAgeGate === '1';
            
            // Play appropriate sound based on pass/fail
            if (typeof AgeEstimatorSounds !== 'undefined' && ageGateEnabled) {
                const passed = estimatedAge >= minimumAge;
                if (passed) {
                    AgeEstimatorSounds.playPassSound();
                } else {
                    AgeEstimatorSounds.playFailSound();
                }
            }
            
            let resultHTML = '<div class="continuous-result">';
            
            // Add new indicator
            resultHTML += `
                <div class="new-indicator" style="position: absolute; top: 10px; right: 10px; background: #2196F3; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;">
                    NEW
                </div>
            `;
            
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
                    <p style="font-size: 11px; color: #666;">AWS API call made</p>
                </div>
            `;
            
            resultHTML += '</div>';
            
            // Append to results instead of replacing
            const newResult = document.createElement('div');
            newResult.innerHTML = resultHTML;
            newResult.style.marginBottom = '20px';
            newResult.style.paddingBottom = '20px';
            newResult.style.borderBottom = '1px solid #ddd';
            newResult.style.position = 'relative';
            
            resultContainer.insertBefore(newResult, resultContainer.firstChild);
            
            // Keep only last 5 results
            while (resultContainer.children.length > 5) {
                resultContainer.removeChild(resultContainer.lastChild);
            }
            
            // Update metrics
            this.updateMetricsDisplay();
            
            // Schedule return to kiosk after showing results
            this.scheduleReturnToKiosk();
        },
        
        displayLocalResults: function(detections) {
            console.log('Age Estimator Photo Continuous: Displaying local results for', detections.length, 'face(s)');
            
            // Make sure result container is visible
            if (resultContainer) {
                resultContainer.style.display = 'block';
            }
            
            const detection = detections[0];
            const estimatedAge = Math.round(detection.age);
            const minimumAge = parseInt(ageEstimatorPhotoParams.minimumAge || 21);
            const ageGateEnabled = ageEstimatorPhotoParams.enableAgeGate === '1';
            
            // Play sound for local results
            if (typeof AgeEstimatorSounds !== 'undefined' && ageGateEnabled) {
                const passed = estimatedAge >= minimumAge;
                if (passed) {
                    AgeEstimatorSounds.playPassSound();
                } else {
                    AgeEstimatorSounds.playFailSound();
                }
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
            
            // Schedule return to kiosk after showing results
            this.scheduleReturnToKiosk();
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
            console.log('Age Estimator Photo Continuous: Stopping camera and monitoring...');
            
            // Stop monitoring
            detectionActive = false;
            videoReady = false;
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
            
            // Hide metrics display
            const metricsDisplay = document.getElementById('age-estimator-metrics');
            if (metricsDisplay) {
                metricsDisplay.style.display = 'none';
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
                
                // Show final metrics
                if (typeof FaceTracker !== 'undefined') {
                    const metrics = FaceTracker.getMetrics();
                    this.showMessage(`Session Summary: ${metrics.totalChecks} checks, ${metrics.hitRate}% cache hit rate, ${metrics.apiReduction}% API calls saved`);
                }
                
                // Show kiosk display when camera stops
                if (kioskMode) {
                    this.showKioskDisplay();
                }
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
        }
    };
    
    // Make globally accessible
    window.photoAgeEstimator = photoAgeEstimator;
    
    // Initialize when document is ready
    $(document).ready(function() {
        photoAgeEstimator.init();
    });
    
})(jQuery);
