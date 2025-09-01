/**
 * Integration Guide for Adaptive Detection Frequency
 * 
 * This file shows how to integrate the adaptive detection frequency module
 * into your existing photo-age-estimator-continuous.js file
 */

// ============================================
// STEP 1: Add to your plugin's PHP file to enqueue the script
// ============================================
/*
Add this to your age-estimator.php file where other scripts are enqueued:

wp_enqueue_script(
    'age-estimator-adaptive-frequency',
    plugin_dir_url(__FILE__) . 'js/adaptive-detection-frequency.js',
    array('jquery'),
    '1.0.0',
    true
);
*/

// ============================================
// STEP 2: Replace the existing continuous monitoring code
// ============================================

// Find and replace the startMonitoring function in photo-age-estimator-continuous.js:

startMonitoring: function() {
    // Always need models loaded for face detection
    if (!isModelLoaded) {
        console.error('Models not loaded, cannot start monitoring');
        this.showError('Face detection models not loaded. Please refresh the page.');
        return;
    }
    
    detectionActive = true;
    console.log('Age Estimator Photo Continuous: Starting adaptive monitoring');
    
    // Initialize adaptive detection if available
    if (typeof AdaptiveDetectionFrequency !== 'undefined') {
        console.log('Using Adaptive Detection Frequency');
        
        // Initialize the adaptive module
        const adaptiveDetection = AdaptiveDetectionFrequency.init({
            MIN_INTERVAL: 100,      // 10fps when faces detected
            MAX_INTERVAL: 1000,     // 1fps when no faces
            NO_FACE_THRESHOLD: 3,   // Frames without faces before slowing
            DEBUG_MODE: false       // Set to true for debug info
        });
        
        // Set the detection callback
        adaptiveDetection.setDetectionCallback(async () => {
            if (!detectionActive || isProcessing) {
                return { facesDetected: false, faceCount: 0 };
            }
            
            try {
                // Perform face detection
                const detections = await this.detectFaces();
                
                // Return result for adaptive frequency
                return {
                    facesDetected: detections.length > 0,
                    faceCount: detections.length
                };
            } catch (error) {
                console.error('Detection error:', error);
                return { facesDetected: false, faceCount: 0 };
            }
        });
        
        // Set status update callback
        adaptiveDetection.setStatusCallback((statusData) => {
            // Update adaptive status display
            const statusElement = document.getElementById('age-estimator-adaptive-status');
            if (statusElement) {
                statusElement.innerHTML = `
                    <div style="background: rgba(0,0,0,0.7); color: white; padding: 5px; border-radius: 3px; font-size: 11px;">
                        ${statusData.status}<br>
                        CPU Saved: ${statusData.metrics.cpuSaved}%
                    </div>
                `;
            }
        });
        
        // Store reference for later use
        this.adaptiveDetection = adaptiveDetection;
        
        // Start adaptive monitoring
        adaptiveDetection.start();
        
    } else {
        // Fallback to fixed interval if adaptive module not available
        console.log('Adaptive Detection not available, using fixed interval');
        
        // Clear any existing interval
        if (monitoringInterval) {
            clearInterval(monitoringInterval);
        }
        
        // Start monitoring loop with fixed interval
        monitoringInterval = setInterval(() => {
            if (detectionActive && !isProcessing) {
                this.checkForFaces();
            }
        }, MONITORING_CONFIG.checkInterval);
    }
    
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

// ============================================
// STEP 3: Create a separate detectFaces method
// ============================================

// Add this new method to your photoAgeEstimator object:

detectFaces: async function() {
    if (!video || !videoReady) return [];
    
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
        
        // Process detections (existing logic from checkForFaces)
        this.processDetections(detections);
        
        return detections;
    } catch (error) {
        console.error('Error in face detection:', error);
        return [];
    }
},

// ============================================
// STEP 4: Move detection processing to separate method
// ============================================

processDetections: function(detections) {
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
},

// ============================================
// STEP 5: Update stopCamera to stop adaptive monitoring
// ============================================

// In your stopCamera method, add:

stopCamera: function() {
    console.log('Age Estimator Photo Continuous: Stopping camera and monitoring...');
    
    // Stop adaptive monitoring if available
    if (this.adaptiveDetection) {
        this.adaptiveDetection.stop();
        this.adaptiveDetection = null;
    }
    
    // Stop regular monitoring
    detectionActive = false;
    videoReady = false;
    if (monitoringInterval) {
        clearInterval(monitoringInterval);
        monitoringInterval = null;
    }
    
    // ... rest of existing stopCamera code ...
},

// ============================================
// STEP 6: Add adaptive status display to UI
// ============================================

// In your initializeElements function, add:

// Add adaptive status display
const adaptiveStatus = document.createElement('div');
adaptiveStatus.id = 'age-estimator-adaptive-status';
adaptiveStatus.style.position = 'absolute';
adaptiveStatus.style.bottom = '10px';
adaptiveStatus.style.right = '10px';
adaptiveStatus.style.fontSize = '11px';
adaptiveStatus.style.display = 'none';
cameraContainer.appendChild(adaptiveStatus);

// Show it when monitoring starts
// In startCamera method after showing other indicators:
const adaptiveStatusElement = document.getElementById('age-estimator-adaptive-status');
if (adaptiveStatusElement) {
    adaptiveStatusElement.style.display = 'block';
}

// ============================================
// STEP 7: Update metrics display
// ============================================

// Modify updateMetricsDisplay to include adaptive metrics:

updateMetricsDisplay: function() {
    const metricsDisplay = document.getElementById('age-estimator-metrics');
    if (!metricsDisplay) return;
    
    let metricsHTML = '';
    
    // Face tracker metrics
    if (typeof FaceTracker !== 'undefined') {
        const metrics = FaceTracker.getMetrics();
        metricsHTML += `
            Cache: ${metrics.cacheSize} faces | 
            Hit Rate: ${metrics.hitRate}% | 
            API Saved: ${metrics.apiReduction}%
        `;
    }
    
    // Adaptive detection metrics
    if (this.adaptiveDetection) {
        const adaptiveMetrics = this.adaptiveDetection.getMetrics();
        metricsHTML += `<br>
            Detection Rate: ${adaptiveMetrics.adaptiveRatio} | 
            CPU Saved: ${adaptiveMetrics.cpuSaved}%
        `;
    }
    
    metricsDisplay.innerHTML = metricsHTML;
}
