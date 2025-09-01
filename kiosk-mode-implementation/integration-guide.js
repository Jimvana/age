/**
 * Kiosk Mode Integration for photo-age-estimator-continuous.js
 * This shows exactly where to add the kiosk mode code in your existing file
 */

// ============================================================
// STEP 1: Add these variables after the existing variables section (around line 30)
// ============================================================

// Kiosk mode variables
let kioskMode = false;
let kioskImage = '';
let kioskDisplayTime = 5;
let kioskTimeout = null;
let kioskDisplay = null;

// ============================================================
// STEP 2: Add to the photoAgeEstimator.init function (around line 85)
// ============================================================

// Add this after "this.initializeElements();" line:
this.initializeKioskMode();

// ============================================================
// STEP 3: Add these methods to the photoAgeEstimator object
// ============================================================

// Add after the init function:
initializeKioskMode: function() {
    const container = document.querySelector('.age-estimator-photo-container');
    if (container) {
        // Get kiosk mode settings
        kioskMode = container.getAttribute('data-kiosk-mode') === 'true';
        kioskImage = container.getAttribute('data-kiosk-image');
        kioskDisplayTime = parseInt(container.getAttribute('data-kiosk-display-time')) || 5;
        kioskDisplay = document.getElementById('age-estimator-kiosk-display');
        
        console.log('Kiosk Mode:', kioskMode ? 'Enabled' : 'Disabled');
        
        // Start with kiosk display if enabled and camera not active
        if (kioskMode && kioskDisplay && !stream) {
            this.showKioskDisplay();
        }
    }
},

showKioskDisplay: function() {
    if (!kioskMode || !kioskDisplay) return;
    
    console.log('Showing kiosk display');
    
    // Hide camera and results
    if (video) video.style.display = 'none';
    if (overlayCanvas) overlayCanvas.style.display = 'none';
    if (resultContainer) {
        resultContainer.style.display = 'none';
        resultContainer.innerHTML = '';
    }
    
    // Show kiosk display
    kioskDisplay.style.display = 'block';
    
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
    if (!kioskMode) return;
    
    console.log(`Scheduling return to kiosk in ${kioskDisplayTime} seconds`);
    
    // Clear any existing timeout
    if (kioskTimeout) {
        clearTimeout(kioskTimeout);
    }
    
    // Schedule return to kiosk display
    kioskTimeout = setTimeout(() => {
        this.showKioskDisplay();
        // Reset monitoring state
        currentFaceInRange = false;
        faceInRangeStartTime = 0;
        isProcessing = false;
    }, kioskDisplayTime * 1000);
},

// ============================================================
// STEP 4: Modify the startCamera function
// ============================================================

// In the startCamera function, after "stream = userStream;" add:
// Hide kiosk when camera starts
this.hideKioskDisplay();

// ============================================================
// STEP 5: Modify the stopCamera function
// ============================================================

// In the stopCamera function, after stopping the stream, add:
// Show kiosk when camera stops
if (kioskMode) {
    this.showKioskDisplay();
}

// ============================================================
// STEP 6: Modify the checkForFaces function
// ============================================================

// In the checkForFaces function, when faces are detected:
// After "if (detections.length > 0) {" add:
this.hideKioskDisplay();

// When no faces are detected for a while, you might want to show kiosk
// This depends on your specific implementation

// ============================================================
// STEP 7: Modify the displayResults function
// ============================================================

// At the end of displayResults function, add:
// Schedule return to kiosk after showing results
this.scheduleReturnToKiosk();

// ============================================================
// STEP 8: Modify the clearResults function
// ============================================================

// If you have a clearResults function, you might want to add:
// Clear any kiosk timeout when clearing results manually
if (kioskTimeout) {
    clearTimeout(kioskTimeout);
    kioskTimeout = null;
}

// ============================================================
// EXAMPLE: Complete checkForFaces modification
// ============================================================

checkForFaces: async function() {
    if (!video || !canvas || isProcessing || !detectionActive) {
        return;
    }
    
    try {
        const detections = await faceapi.detectAllFaces(
            video, 
            new faceapi.TinyFaceDetectorOptions()
        );
        
        if (detections.length > 0) {
            // Hide kiosk when face detected
            this.hideKioskDisplay();
            
            const face = detections[0];
            const box = face.box;
            
            // Check if face is in the right size range
            if (box.width >= MONITORING_CONFIG.minFaceSize && 
                box.width <= MONITORING_CONFIG.maxFaceSize) {
                
                if (!currentFaceInRange) {
                    currentFaceInRange = true;
                    faceInRangeStartTime = Date.now();
                    console.log('Face in range, waiting for capture delay...');
                }
                
                // Check if face has been in range long enough
                const timeInRange = Date.now() - faceInRangeStartTime;
                if (timeInRange >= MONITORING_CONFIG.captureDelay) {
                    // Check cooldown period
                    const timeSinceLastCapture = Date.now() - lastCaptureTime;
                    if (timeSinceLastCapture >= MONITORING_CONFIG.cooldownPeriod) {
                        console.log('Capturing face...');
                        await this.captureAndAnalyze();
                        lastCaptureTime = Date.now();
                        currentFaceInRange = false;
                    }
                }
            } else {
                currentFaceInRange = false;
                faceInRangeStartTime = 0;
            }
            
            // Draw face box
            this.drawFaceBox(box);
            
        } else {
            // No face detected
            currentFaceInRange = false;
            faceInRangeStartTime = 0;
            this.clearOverlay();
            
            // If no face for a while and not processing, consider showing kiosk
            // You might want to add a delay here before showing kiosk again
        }
        
    } catch (error) {
        console.error('Error checking for faces:', error);
    }
}