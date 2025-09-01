# Age Estimator - Continuous Monitoring Mode
## Developer Documentation

### Table of Contents
1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Technical Implementation](#technical-implementation)
4. [Configuration](#configuration)
5. [Code Flow](#code-flow)
6. [Key Functions](#key-functions)
7. [Extending the Feature](#extending-the-feature)
8. [Troubleshooting](#troubleshooting)
9. [API Reference](#api-reference)

## Overview

The Continuous Monitoring Mode is an advanced feature that automatically detects faces in real-time and captures photos when a person comes within optimal camera range. This eliminates the need for manual photo capture, making it ideal for kiosks, entry points, or hands-free age verification systems.

### Key Features
- Real-time face detection using face-api.js
- Automatic capture based on face proximity (size)
- Visual feedback with colored overlays
- Configurable detection parameters
- Multiple result display with history
- Fallback to manual mode

### How It Works
1. Camera stream starts and face detection runs every 100ms
2. Detected faces are tracked and measured
3. When a face is 150-350 pixels wide (optimal range) for 500ms, capture triggers
4. Photo is analyzed for age estimation
5. Results are displayed with a 5-second cooldown before next capture

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    WordPress Plugin                      │
├─────────────────────────────────────────────────────────┤
│  PHP Layer (age-estimator.php)                         │
│  - Settings management                                  │
│  - Mode selection (manual/continuous)                   │
│  - Script enqueueing                                    │
├─────────────────────────────────────────────────────────┤
│  JavaScript Layer (photo-age-estimator.js)             │
│  ├─ Initialization                                      │
│  │  └─ Mode detection (isContinuousMode flag)         │
│  ├─ Face Detection Engine                             │
│  │  ├─ face-api.js integration                        │
│  │  ├─ Model loading                                  │
│  │  └─ Detection loop (100ms interval)                │
│  ├─ UI Management                                      │
│  │  ├─ Canvas overlay for visual feedback             │
│  │  ├─ Status indicators                              │
│  │  └─ Result display                                 │
│  └─ Analysis Pipeline                                  │
│      ├─ Local analysis (face-api.js)                  │
│      └─ AWS Rekognition (optional)                    │
└─────────────────────────────────────────────────────────┘
```

## Technical Implementation

### 1. Mode Detection

The system determines whether to use continuous or manual mode through:

```javascript
// Check WordPress setting
isContinuousMode = ageEstimatorPhotoParams.continuousMode === '1';

// Also check data attribute as fallback
const container = document.querySelector('.age-estimator-photo-container');
if (container && container.dataset.continuous === 'true') {
    isContinuousMode = true;
}
```

### 2. Face Detection Models

Required face-api.js models:
- **ssd_mobilenetv1**: Face detection
- **ageGenderNet**: Age and gender prediction
- **faceExpressionNet**: Emotion detection

Models are loaded from `/wp-content/plugins/age-estimator/models/`

### 3. Detection Loop

```javascript
monitoringInterval = setInterval(() => {
    if (detectionActive && !isProcessing) {
        this.checkForFaces();
    }
}, MONITORING_CONFIG.checkInterval);
```

### 4. Proximity Detection

Face size (width in pixels) determines distance:
- **< 150px**: Too far (red indicator)
- **150-350px**: Optimal range (green indicator)
- **> 350px**: Too close (orange indicator)

### 5. Capture Logic

```javascript
// Face must be in range for captureDelay milliseconds
if (inRange && currentFaceInRange) {
    const timeInRange = Date.now() - faceInRangeStartTime;
    if (timeInRange >= MONITORING_CONFIG.captureDelay) {
        // Check cooldown period
        const timeSinceLastCapture = Date.now() - lastCaptureTime;
        if (timeSinceLastCapture >= MONITORING_CONFIG.cooldownPeriod) {
            await this.captureAndAnalyze();
        }
    }
}
```

## Configuration

### WordPress Admin Settings

```php
// In admin settings (admin-settings.php)
'age_estimator_continuous_mode' => false, // Enable/disable continuous mode
```

### JavaScript Configuration

```javascript
const MONITORING_CONFIG = {
    checkInterval: 100,        // Detection frequency (ms)
    minFaceSize: 150,         // Minimum face width (pixels)
    maxFaceSize: 350,         // Maximum face width (pixels)
    captureDelay: 500,        // Wait time before capture (ms)
    cooldownPeriod: 5000,     // Time between captures (ms)
    faceStabilityFrames: 3,   // Frames face must be stable
    minConfidence: 0.7        // Detection confidence threshold
};
```

### Adjusting Sensitivity

To make detection more/less sensitive:

```javascript
// More sensitive (captures from further away)
minFaceSize: 100,  // Reduced from 150

// Less sensitive (requires closer proximity)
minFaceSize: 200,  // Increased from 150

// Faster capture
captureDelay: 200,  // Reduced from 500ms

// Slower capture (more stability required)
captureDelay: 1000,  // Increased from 500ms
```

## Code Flow

### Initialization Flow
```
1. photoAgeEstimator.init()
   ├─ Check continuousMode parameter
   ├─ Initialize DOM elements
   ├─ Load face-api.js models
   └─ Setup event listeners

2. User clicks "Start Monitoring"
   ├─ startCamera()
   │  ├─ Get user media stream
   │  ├─ Setup video element
   │  └─ Position overlay canvas
   └─ startMonitoring()
      ├─ Verify models loaded
      ├─ Set detectionActive = true
      └─ Start interval timer
```

### Detection Flow
```
checkForFaces() [every 100ms]
├─ Detect faces in video frame
├─ Calculate face dimensions
├─ Draw visual overlay
├─ Update status indicator
└─ Check capture conditions
   ├─ Is face in range?
   ├─ Has face been stable?
   ├─ Is cooldown period over?
   └─ Trigger capture if all true
```

### Capture Flow
```
captureAndAnalyze()
├─ Set isProcessing = true
├─ Show flash effect
├─ Capture frame to canvas
├─ Convert to base64 image
├─ Analyze with chosen method
│  ├─ Local: face-api.js analysis
│  └─ AWS: Rekognition API call
├─ Display results
└─ Set isProcessing = false
```

## Key Functions

### Core Functions

#### `startMonitoring()`
Initializes the continuous detection loop.

```javascript
startMonitoring: function() {
    // Verify models are loaded
    if (!settings.useAws && !isModelLoaded) {
        this.showError('Face detection models not loaded');
        return;
    }
    
    detectionActive = true;
    
    // Start detection loop
    monitoringInterval = setInterval(() => {
        if (detectionActive && !isProcessing) {
            this.checkForFaces();
        }
    }, MONITORING_CONFIG.checkInterval);
}
```

#### `checkForFaces()`
Performs face detection and evaluates capture conditions.

```javascript
checkForFaces: async function() {
    // Detect faces
    const detections = await faceapi.detectAllFaces(video);
    
    // Draw overlay
    this.drawOverlay(detections);
    
    // Process detections
    if (detections.length > 0) {
        const faceWidth = detections[0].box.width;
        const inRange = faceWidth >= MONITORING_CONFIG.minFaceSize && 
                       faceWidth <= MONITORING_CONFIG.maxFaceSize;
        
        // Handle capture logic
        if (inRange) {
            // Check stability and cooldown
            // Trigger capture if conditions met
        }
    }
}
```

#### `drawOverlay()`
Provides visual feedback on face detection status.

```javascript
drawOverlay: function(detections) {
    const ctx = overlayCanvas.getContext('2d');
    ctx.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
    
    if (detections.length > 0) {
        const face = detections[0];
        const faceWidth = face.box.width;
        
        // Determine color based on proximity
        let color = '#ff0000'; // Red - too far
        if (faceWidth >= MONITORING_CONFIG.minFaceSize && 
            faceWidth <= MONITORING_CONFIG.maxFaceSize) {
            color = '#00ff00'; // Green - in range
        } else if (faceWidth > MONITORING_CONFIG.maxFaceSize) {
            color = '#ffa500'; // Orange - too close
        }
        
        // Draw face box
        ctx.strokeStyle = color;
        ctx.lineWidth = 3;
        ctx.strokeRect(face.box.x, face.box.y, face.box.width, face.box.height);
    }
}
```

### Helper Functions

#### `updateStatus()`
Updates the status indicator with current detection state.

#### `showFlashEffect()`
Creates visual feedback when photo is captured.

#### `displayResults()`
Shows age estimation results (different for continuous vs manual mode).

## Extending the Feature

### Adding Multiple Face Support

```javascript
// In checkForFaces()
const detections = await faceapi.detectAllFaces(video);

// Process all faces instead of just first
detections.forEach((detection, index) => {
    const faceWidth = detection.box.width;
    // Track each face separately
    faceTracks[index] = {
        width: faceWidth,
        inRange: faceWidth >= minSize && faceWidth <= maxSize,
        timestamp: Date.now()
    };
});
```

### Adding Sound Effects

```javascript
// Add to captureAndAnalyze()
const captureSound = new Audio(pluginUrl + 'sounds/capture.mp3');
captureSound.play();
```

### Custom Capture Conditions

```javascript
// Add new conditions to capture logic
const customConditions = {
    minSmileConfidence: 0.8,
    requireEyesOpen: true,
    maxHeadTilt: 15 // degrees
};

// In checkForFaces()
if (detection.expressions.happy > customConditions.minSmileConfidence) {
    // Allow capture
}
```

### Integration with External Systems

```javascript
// After successful capture
captureAndAnalyze: async function() {
    // ... existing capture code ...
    
    // Send to external system
    const webhookData = {
        timestamp: new Date().toISOString(),
        estimatedAge: result.age,
        confidence: result.confidence,
        location: window.location.href
    };
    
    fetch('https://your-api.com/webhook', {
        method: 'POST',
        body: JSON.stringify(webhookData)
    });
}
```

## Troubleshooting

### Common Issues and Solutions

#### 1. Face Detection Not Starting

**Symptoms:**
- No "Checking for faces..." messages in console
- No colored boxes around faces

**Solutions:**
```javascript
// Check if models are loaded
console.log('Models loaded:', isModelLoaded);
console.log('face-api available:', typeof faceapi !== 'undefined');

// Verify models directory
// Navigate to: /wp-content/plugins/age-estimator/check-models.php

// Force model reload
await photoAgeEstimator.loadModels();
```

#### 2. Continuous Mode Not Activating

**Symptoms:**
- "Take Photo" button appears instead of monitoring

**Debug Steps:**
```javascript
// Check mode detection
console.log('Continuous mode:', isContinuousMode);
console.log('Parameter:', ageEstimatorPhotoParams.continuousMode);

// Force continuous mode
isContinuousMode = true;
photoAgeEstimator.startMonitoring();
```

#### 3. Performance Issues

**Symptoms:**
- Laggy video feed
- Delayed detection

**Solutions:**
```javascript
// Reduce detection frequency
MONITORING_CONFIG.checkInterval = 200; // From 100ms

// Lower video resolution
const stream = await navigator.mediaDevices.getUserMedia({
    video: {
        width: { ideal: 320 },  // From 640
        height: { ideal: 240 }  // From 480
    }
});
```

### Debug Mode

Enable detailed logging:

```javascript
// Add to init()
window.AGE_ESTIMATOR_DEBUG = true;

// Wrap console.logs
function debugLog(...args) {
    if (window.AGE_ESTIMATOR_DEBUG) {
        console.log('[Age Estimator]', ...args);
    }
}
```

## API Reference

### Global Objects

#### `photoAgeEstimator`
Main controller object exposed globally.

**Methods:**
- `init()` - Initialize the system
- `startCamera()` - Start camera stream
- `stopCamera()` - Stop camera and monitoring
- `startMonitoring()` - Begin continuous detection
- `checkForFaces()` - Single face detection check
- `captureAndAnalyze()` - Capture and analyze photo

#### `MONITORING_CONFIG`
Configuration object for continuous mode.

**Properties:**
- `checkInterval` (number) - MS between detection checks
- `minFaceSize` (number) - Minimum face width in pixels
- `maxFaceSize` (number) - Maximum face width in pixels
- `captureDelay` (number) - MS to wait before capture
- `cooldownPeriod` (number) - MS between captures
- `minConfidence` (number) - Detection confidence (0-1)

### Events

The system doesn't currently emit custom events, but you can add them:

```javascript
// Add event emitting
const event = new CustomEvent('ageEstimatorCapture', {
    detail: { age: estimatedAge, timestamp: Date.now() }
});
document.dispatchEvent(event);

// Listen for events
document.addEventListener('ageEstimatorCapture', (e) => {
    console.log('Age captured:', e.detail.age);
});
```

### WordPress Hooks

PHP filters and actions:

```php
// Modify default configuration
add_filter('age_estimator_default_options', function($options) {
    $options['age_estimator_continuous_mode'] = true;
    return $options;
});

// After settings save
add_action('update_option_age_estimator_continuous_mode', function($old, $new) {
    // Clear caches, etc.
}, 10, 2);
```

## Best Practices

1. **Always check model loading status** before starting monitoring
2. **Handle errors gracefully** - fall back to manual mode if needed
3. **Optimize for performance** - adjust detection frequency based on device capabilities
4. **Provide clear visual feedback** - users should understand what's happening
5. **Test across devices** - performance varies significantly on mobile vs desktop
6. **Consider privacy** - add notices about continuous monitoring
7. **Log important events** - helps with debugging in production

## Future Enhancements

Potential improvements for future versions:

1. **WebAssembly Integration** - Faster face detection
2. **Progressive Web App** - Offline support
3. **WebRTC Integration** - Remote monitoring
4. **Machine Learning Pipeline** - Custom age models
5. **3D Face Detection** - Better accuracy
6. **Multi-camera Support** - Different angles
7. **Batch Processing** - Queue multiple faces
8. **Analytics Dashboard** - Usage statistics
9. **Accessibility Features** - Audio feedback
10. **Mobile App** - Native performance

---

*Last updated: [Current Date]*
*Version: 1.0*
*Author: Age Estimator Development Team*
