/**
 * Kiosk Mode JavaScript Additions
 * Add this to your photo-age-estimator-continuous.js file
 */

// Add these variables at the top of the file
let kioskMode = false;
let kioskImage = '';
let kioskDisplayTime = 5;
let kioskTimeout = null;
let kioskDisplay = null;

// Add to your initialization function
function initializeKioskMode(container) {
    // Get kiosk mode settings
    kioskMode = container.getAttribute('data-kiosk-mode') === 'true';
    kioskImage = container.getAttribute('data-kiosk-image');
    kioskDisplayTime = parseInt(container.getAttribute('data-kiosk-display-time')) || 5;
    kioskDisplay = document.getElementById('age-estimator-kiosk-display');

    // Start with kiosk display if enabled
    if (kioskMode && kioskDisplay) {
        showKioskDisplay();
    }
}

// Kiosk mode functions
function showKioskDisplay() {
    if (!kioskMode || !kioskDisplay) return;
    
    console.log('Showing kiosk display');
    
    // Hide camera and results
    const videoElement = document.getElementById('age-estimator-photo-video');
    const overlayCanvas = document.getElementById('age-estimator-photo-overlay');
    const resultDiv = document.getElementById('age-estimator-photo-result');
    
    if (videoElement) videoElement.style.display = 'none';
    if (overlayCanvas) overlayCanvas.style.display = 'none';
    if (resultDiv) resultDiv.style.display = 'none';
    
    // Show kiosk display
    kioskDisplay.style.display = 'block';
    
    // Clear any existing timeout
    if (kioskTimeout) {
        clearTimeout(kioskTimeout);
        kioskTimeout = null;
    }
}

function hideKioskDisplay() {
    if (!kioskDisplay) return;
    
    console.log('Hiding kiosk display');
    kioskDisplay.style.display = 'none';
    
    // Show camera when kiosk is hidden
    const videoElement = document.getElementById('age-estimator-photo-video');
    if (videoElement && cameraActive) {
        videoElement.style.display = 'block';
    }
}

function scheduleReturnToKiosk() {
    if (!kioskMode) return;
    
    console.log(`Scheduling return to kiosk in ${kioskDisplayTime} seconds`);
    
    // Clear any existing timeout
    if (kioskTimeout) {
        clearTimeout(kioskTimeout);
    }
    
    // Schedule return to kiosk display
    kioskTimeout = setTimeout(() => {
        showKioskDisplay();
        // Clear the result
        const resultDiv = document.getElementById('age-estimator-photo-result');
        if (resultDiv) {
            resultDiv.innerHTML = '';
            resultDiv.style.display = 'none';
        }
    }, kioskDisplayTime * 1000);
}

// Add to your face detection handling
// When a face is detected:
function onFaceDetected(detections) {
    if (detections && detections.length > 0) {
        hideKioskDisplay();
        // ... your existing face processing code
    } else if (kioskMode && !kioskTimeout) {
        // No face detected and not already scheduled to return
        scheduleReturnToKiosk();
    }
}

// Add to your age result display function
// After displaying the age result:
function displayAgeResult(result) {
    // ... your existing result display code
    
    // Schedule return to kiosk after showing result
    if (kioskMode) {
        scheduleReturnToKiosk();
    }
}

// Integration example for continuous mode
// In your continuous detection loop:
if (typeof startContinuousMode !== 'undefined') {
    const originalStartContinuousMode = startContinuousMode;
    startContinuousMode = function() {
        // Initialize kiosk mode
        const container = document.querySelector('.age-estimator-photo-container');
        if (container) {
            initializeKioskMode(container);
        }
        
        // Call original function
        return originalStartContinuousMode.apply(this, arguments);
    };
}