/**
 * Adaptive Detection Frequency Module for Age Estimator
 * Automatically adjusts detection frequency based on face presence
 * 
 * This module optimizes performance by:
 * - Running at 1000ms (1fps) when no faces are detected
 * - Speeding up to 100ms (10fps) when faces are present
 * - Smoothly transitioning between frequencies
 */

(function() {
    'use strict';
    
    // Configuration for adaptive frequency
    const ADAPTIVE_CONFIG = {
        // Frequency settings (in milliseconds)
        MIN_INTERVAL: 100,      // Fastest detection (10fps) when faces present
        MAX_INTERVAL: 1000,     // Slowest detection (1fps) when no faces
        TRANSITION_SPEED: 200,  // How quickly to transition between frequencies
        
        // Face detection settings
        NO_FACE_THRESHOLD: 3,   // Number of frames without faces before slowing down
        FACE_DETECTED_FRAMES: 1, // Number of frames with faces before speeding up
        
        // Performance settings
        ENABLE_SMOOTHING: true,  // Enable smooth transitions
        DEBUG_MODE: false        // Show debug information
    };
    
    // State tracking
    const state = {
        currentInterval: ADAPTIVE_CONFIG.MAX_INTERVAL,
        targetInterval: ADAPTIVE_CONFIG.MAX_INTERVAL,
        noFaceCount: 0,
        faceDetectedCount: 0,
        isTransitioning: false,
        lastDetectionTime: 0,
        performanceMetrics: {
            totalDetections: 0,
            faceDetections: 0,
            intervalChanges: 0,
            cpuSaved: 0
        }
    };
    
    // References
    let monitoringInterval = null;
    let transitionInterval = null;
    let detectionCallback = null;
    let statusUpdateCallback = null;
    
    /**
     * Initialize the adaptive detection frequency module
     */
    function init(options = {}) {
        // Merge custom options with defaults
        Object.assign(ADAPTIVE_CONFIG, options);
        
        console.log('Adaptive Detection Frequency: Initializing with config:', ADAPTIVE_CONFIG);
        
        // Reset state
        resetState();
        
        return {
            start: startAdaptiveMonitoring,
            stop: stopAdaptiveMonitoring,
            updateFrequency: updateDetectionFrequency,
            getMetrics: getPerformanceMetrics,
            setDetectionCallback: setDetectionCallback,
            setStatusCallback: setStatusUpdateCallback
        };
    }
    
    /**
     * Reset state to initial values
     */
    function resetState() {
        state.currentInterval = ADAPTIVE_CONFIG.MAX_INTERVAL;
        state.targetInterval = ADAPTIVE_CONFIG.MAX_INTERVAL;
        state.noFaceCount = 0;
        state.faceDetectedCount = 0;
        state.isTransitioning = false;
        state.lastDetectionTime = Date.now();
    }
    
    /**
     * Set the detection callback function
     */
    function setDetectionCallback(callback) {
        detectionCallback = callback;
    }
    
    /**
     * Set the status update callback function
     */
    function setStatusUpdateCallback(callback) {
        statusUpdateCallback = callback;
    }
    
    /**
     * Start adaptive monitoring
     */
    function startAdaptiveMonitoring() {
        console.log('Adaptive Detection: Starting monitoring');
        
        // Clear any existing intervals
        stopAdaptiveMonitoring();
        
        // Start with slow interval
        state.currentInterval = ADAPTIVE_CONFIG.MAX_INTERVAL;
        scheduleNextDetection();
        
        // Update status
        updateStatus('Started - Low frequency mode');
    }
    
    /**
     * Stop adaptive monitoring
     */
    function stopAdaptiveMonitoring() {
        if (monitoringInterval) {
            clearTimeout(monitoringInterval);
            monitoringInterval = null;
        }
        
        if (transitionInterval) {
            clearInterval(transitionInterval);
            transitionInterval = null;
        }
        
        console.log('Adaptive Detection: Stopped monitoring');
        updateStatus('Stopped');
    }
    
    /**
     * Schedule the next detection based on current interval
     */
    function scheduleNextDetection() {
        // Clear any existing timeout
        if (monitoringInterval) {
            clearTimeout(monitoringInterval);
        }
        
        // Schedule next detection
        monitoringInterval = setTimeout(async () => {
            await performDetection();
            
            // Schedule next detection if still active
            if (monitoringInterval !== null) {
                scheduleNextDetection();
            }
        }, state.currentInterval);
    }
    
    /**
     * Perform face detection and update frequency
     */
    async function performDetection() {
        const startTime = performance.now();
        state.performanceMetrics.totalDetections++;
        
        // Call the detection callback
        if (detectionCallback) {
            try {
                const result = await detectionCallback();
                const endTime = performance.now();
                const detectionTime = endTime - startTime;
                
                // Update frequency based on result
                updateDetectionFrequency(result);
                
                // Debug logging
                if (ADAPTIVE_CONFIG.DEBUG_MODE) {
                    console.log(`Detection took ${detectionTime.toFixed(2)}ms, interval: ${state.currentInterval}ms`);
                }
                
            } catch (error) {
                console.error('Adaptive Detection: Error in detection callback:', error);
            }
        }
    }
    
    /**
     * Update detection frequency based on face detection results
     */
    function updateDetectionFrequency(detectionResult) {
        const { facesDetected = false, faceCount = 0 } = detectionResult || {};
        
        if (facesDetected && faceCount > 0) {
            // Face detected
            state.faceDetectedCount++;
            state.noFaceCount = 0;
            state.performanceMetrics.faceDetections++;
            
            // Speed up if we've detected faces consistently
            if (state.faceDetectedCount >= ADAPTIVE_CONFIG.FACE_DETECTED_FRAMES) {
                setTargetInterval(ADAPTIVE_CONFIG.MIN_INTERVAL);
            }
            
        } else {
            // No face detected
            state.noFaceCount++;
            state.faceDetectedCount = 0;
            
            // Slow down if no faces for a while
            if (state.noFaceCount >= ADAPTIVE_CONFIG.NO_FACE_THRESHOLD) {
                setTargetInterval(ADAPTIVE_CONFIG.MAX_INTERVAL);
            }
        }
        
        // Apply interval changes
        if (ADAPTIVE_CONFIG.ENABLE_SMOOTHING) {
            smoothTransition();
        } else {
            state.currentInterval = state.targetInterval;
        }
        
        // Update status
        const status = facesDetected ? 
            `High frequency (${state.currentInterval}ms) - ${faceCount} face(s)` : 
            `Low frequency (${state.currentInterval}ms) - No faces`;
        updateStatus(status);
    }
    
    /**
     * Set the target interval
     */
    function setTargetInterval(interval) {
        if (state.targetInterval !== interval) {
            state.targetInterval = interval;
            state.performanceMetrics.intervalChanges++;
            
            console.log(`Adaptive Detection: Target interval changed to ${interval}ms`);
        }
    }
    
    /**
     * Smoothly transition between intervals
     */
    function smoothTransition() {
        if (state.currentInterval === state.targetInterval) {
            return; // Already at target
        }
        
        // Calculate step size for smooth transition
        const difference = state.targetInterval - state.currentInterval;
        const step = difference / (ADAPTIVE_CONFIG.TRANSITION_SPEED / 50); // 50ms steps
        
        // Start transition if not already running
        if (!state.isTransitioning) {
            state.isTransitioning = true;
            
            transitionInterval = setInterval(() => {
                if (Math.abs(state.currentInterval - state.targetInterval) < Math.abs(step)) {
                    // Reached target
                    state.currentInterval = state.targetInterval;
                    state.isTransitioning = false;
                    clearInterval(transitionInterval);
                    transitionInterval = null;
                } else {
                    // Step towards target
                    state.currentInterval += step;
                    state.currentInterval = Math.max(
                        ADAPTIVE_CONFIG.MIN_INTERVAL,
                        Math.min(ADAPTIVE_CONFIG.MAX_INTERVAL, state.currentInterval)
                    );
                }
            }, 50);
        }
    }
    
    /**
     * Update status display
     */
    function updateStatus(status) {
        // Calculate CPU savings
        const optimalDetections = state.performanceMetrics.totalDetections;
        const wouldBeDetections = (Date.now() - state.lastDetectionTime) / ADAPTIVE_CONFIG.MIN_INTERVAL;
        const savings = ((wouldBeDetections - optimalDetections) / wouldBeDetections * 100).toFixed(1);
        state.performanceMetrics.cpuSaved = Math.max(0, savings);
        
        if (statusUpdateCallback) {
            statusUpdateCallback({
                status: status,
                currentInterval: state.currentInterval,
                targetInterval: state.targetInterval,
                metrics: state.performanceMetrics
            });
        }
        
        // Add to page UI if element exists
        const adaptiveStatus = document.getElementById('adaptive-detection-status');
        if (adaptiveStatus) {
            adaptiveStatus.innerHTML = `
                <div style="font-size: 11px; color: #666;">
                    Adaptive: ${status}<br>
                    CPU Saved: ${state.performanceMetrics.cpuSaved}%
                </div>
            `;
        }
    }
    
    /**
     * Get performance metrics
     */
    function getPerformanceMetrics() {
        return {
            ...state.performanceMetrics,
            currentInterval: state.currentInterval,
            targetInterval: state.targetInterval,
            adaptiveRatio: (ADAPTIVE_CONFIG.MAX_INTERVAL / state.currentInterval).toFixed(1) + 'x'
        };
    }
    
    // Export module
    window.AdaptiveDetectionFrequency = {
        init: init,
        config: ADAPTIVE_CONFIG
    };
    
})();
