// Face Detection Camera - Configuration Settings
// Edit these values to customize the application's behavior

const CONFIG = {
    // Face Detection Settings
    faceDetection: {
        minFaceSize: 80,        // Minimum face width in pixels for detection
        maxFaceSize: 300,       // Maximum face width in pixels for detection
        scoreThreshold: 0.5,    // Minimum confidence score (0.0 - 1.0)
        detectionInterval: 200  // Detection loop interval in milliseconds (reduced for low-end devices)
    },

    // Age and Gender Settings
    ageGender: {
        ageThreshold: 25,       // Age threshold for ID requirement
        showAge: true,          // Display age numbers on detected faces
        showGender: true,       // Display gender on detected faces
        showExpressions: false  // Display facial expressions
    },

    // UI and Visual Settings
    ui: {
        borderColors: {
            underAge: 'red',    // Color for faces under age threshold
            overAge: 'green',   // Color for faces over age threshold
            neutral: 'blue'     // Color for neutral detections
        },
        textSize: 16,           // Font size for labels
        textColor: 'white',     // Text color
        textOutlineColor: 'black', // Text outline color
        showDetectionZone: true, // Show visual detection zone indicator
        zoneOpacity: 0.3        // Opacity of detection zone overlay
    },

    // Camera Settings
    camera: {
        facingMode: 'user',     // 'user' for front camera, 'environment' for back
        width: { ideal: 640 },  // Preferred video width
        height: { ideal: 480 }, // Preferred video height
        frameRate: { ideal: 30 } // Preferred frame rate
    },

    // Performance Settings
    performance: {
        maxDetections: 3,       // Maximum number of faces to detect simultaneously (reduced for low-end devices)
        enableSmoothing: true,  // Smooth detection results to reduce flickering
        smoothingFactor: 0.8    // Smoothing factor (0.0 = no smoothing, 1.0 = max smoothing)
    },

    // Debug Settings
    debug: {
        showConsoleLogs: true,  // Enable console logging
        showDetectionStats: false, // Show detection statistics overlay
        enableProfiling: false   // Enable performance profiling
    }
};

// Settings validation
function validateConfig() {
    const errors = [];

    // Validate face detection settings
    if (CONFIG.faceDetection.minFaceSize < 20) {
        errors.push('minFaceSize must be at least 20 pixels');
    }
    if (CONFIG.faceDetection.maxFaceSize < CONFIG.faceDetection.minFaceSize) {
        errors.push('maxFaceSize must be greater than minFaceSize');
    }
    if (CONFIG.faceDetection.scoreThreshold < 0 || CONFIG.faceDetection.scoreThreshold > 1) {
        errors.push('scoreThreshold must be between 0.0 and 1.0');
    }

    // Validate age settings
    if (CONFIG.ageGender.ageThreshold < 0 || CONFIG.ageGender.ageThreshold > 120) {
        errors.push('ageThreshold must be between 0 and 120');
    }

    if (errors.length > 0) {
        console.error('Configuration validation errors:', errors);
        return false;
    }

    return true;
}

// Load settings from localStorage
function loadSettings() {
    try {
        const saved = localStorage.getItem('faceDetectionSettings');
        if (saved) {
            const parsedSettings = JSON.parse(saved);
            // Deep merge saved settings with defaults
            Object.keys(parsedSettings).forEach(category => {
                if (CONFIG[category]) {
                    Object.assign(CONFIG[category], parsedSettings[category]);
                }
            });
        }
    } catch (error) {
        console.error('Error loading settings:', error);
    }
}

// Save settings to localStorage
function saveSettings() {
    try {
        localStorage.setItem('faceDetectionSettings', JSON.stringify(CONFIG));
    } catch (error) {
        console.error('Error saving settings:', error);
    }
}

// Reset settings to defaults
function resetSettings() {
    // This would need to be implemented by reloading the default CONFIG
    console.log('Settings reset to defaults');
    location.reload();
}

// Device capability detection for low-end Android optimization
function detectDeviceCapabilities() {
    const userAgent = navigator.userAgent.toLowerCase();
    const isAndroid = /android/.test(userAgent);
    const isLowEnd = isAndroid && (
        /sm-/.test(userAgent) || // Samsung low-end
        /a[0-9]{2}/.test(userAgent) || // Generic Android low-end
        navigator.hardwareConcurrency <= 4 || // Low CPU cores
        (navigator.deviceMemory && navigator.deviceMemory <= 2) // Low RAM
    );

    if (isLowEnd) {
        console.log('Low-end Android device detected, optimizing settings...');

        // Apply low-end optimizations (keep frame rate high to avoid issues)
        CONFIG.faceDetection.detectionInterval = Math.max(CONFIG.faceDetection.detectionInterval, 300);
        CONFIG.performance.maxDetections = Math.min(CONFIG.performance.maxDetections, 2);
        CONFIG.camera.frameRate = { ideal: Math.min(CONFIG.camera.frameRate.ideal, 25) }; // Keep frame rate higher
        CONFIG.performance.enableSmoothing = false; // Disable smoothing for better performance

        console.log('Applied low-end optimizations:', {
            detectionInterval: CONFIG.faceDetection.detectionInterval,
            maxDetections: CONFIG.performance.maxDetections,
            frameRate: CONFIG.camera.frameRate.ideal,
            smoothing: CONFIG.performance.enableSmoothing
        });
    }
}

// Initialize settings
loadSettings();
detectDeviceCapabilities(); // Apply device-specific optimizations
if (!validateConfig()) {
    console.warn('Using default settings due to validation errors');
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { CONFIG, validateConfig, loadSettings, saveSettings, resetSettings };
}
