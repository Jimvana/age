# Age Estimator Live - Continuous Monitoring Guide

## Overview
Age Estimator Live uses continuous monitoring to automatically detect faces and capture photos when a person comes within the optimal distance from the camera. This is the core functionality of the plugin.

## How It Works

### Face Detection
- The camera continuously monitors for faces in real-time (checks every 100ms)
- Uses face-api.js for local face detection with high accuracy
- Requires minimum confidence level of 0.7 for detection

### Proximity Detection
- Measures face size on screen to determine distance
- **Too Far**: Face width < 150 pixels (red indicator)
- **Optimal Range**: Face width 150-350 pixels (green indicator)
- **Too Close**: Face width > 350 pixels (orange indicator)

### Automatic Capture
- When a face enters the optimal range, a countdown begins
- After 500ms of stable detection, photo is automatically captured
- Visual flash effect indicates capture
- 5-second cooldown period prevents multiple captures of the same person

## Visual Feedback

### On-Screen Indicators
1. **Face Box**: Colored rectangle around detected face
   - Red = Too far
   - Green = In range
   - Orange = Too close

2. **Status Indicator**: Top-right corner shows current detection status
   - "Monitoring..." - Active scanning
   - "Face detected (XXXpx) - In range" - Ready to capture
   - "Analyzing..." - Processing captured photo

3. **Overlay Messages**: Real-time guidance
   - "Move closer" / "In range" / "Too close"
   - Countdown timer when capturing

## Configuration

### Adjust Settings (Optional)
To customize the behavior, edit these constants in `js/photo-age-estimator.js`:

```javascript
const MONITORING_CONFIG = {
    checkInterval: 100,        // Detection frequency (ms)
    minFaceSize: 150,         // Minimum face width (pixels)
    maxFaceSize: 350,         // Maximum face width (pixels)
    captureDelay: 500,        // Delay before capture (ms)
    cooldownPeriod: 5000,     // Time between captures (ms)
    minConfidence: 0.7        // Detection confidence threshold
};
```

## User Experience

### For Site Visitors
1. Click "Start Monitoring"
2. Allow camera access
3. Position face in view
4. Move closer until green indicator appears
5. Hold position briefly for automatic capture
6. View results immediately

### Results Display
- Latest results appear at the top
- Up to 5 recent captures are displayed
- Each result shows:
  - Estimated age
  - Pass/Fail status (if age gate enabled)
  - Capture timestamp
  - Thumbnail of captured photo

## Technical Requirements

### Browser Support
- Modern browsers with getUserMedia API
- Chrome 60+, Firefox 60+, Safari 11+, Edge 79+

### Performance
- Minimal CPU usage with optimized detection loop
- Efficient memory management
- Smooth 60fps video feed maintained

## Privacy & Security

### Data Handling
- Photos processed locally (Simple mode) or via AWS (AWS mode)
- No persistent storage unless configured
- Immediate deletion after analysis by default

### User Consent
- Camera permission required
- Optional consent popup before first use
- Clear visual indicators when monitoring active

## Troubleshooting

### Common Issues

1. **"Models not loaded" error**
   - Ensure face-api.js models are properly downloaded
   - Check browser console for loading errors
   - Verify `/models/` directory contains all required files

2. **Poor detection accuracy**
   - Ensure good lighting conditions
   - Face camera directly
   - Remove obstructions (sunglasses, masks)
   - Check minimum confidence setting

3. **Performance issues**
   - Reduce checkInterval for slower devices
   - Ensure GPU acceleration enabled in browser
   - Close other camera-using applications

## Future Enhancements

Potential improvements for future versions:
- Multiple face detection and queuing
- Adjustable proximity thresholds via UI
- Sound effects for capture feedback
- Integration with access control systems
- Analytics and reporting features
