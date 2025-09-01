# Kiosk Mode Console Reference Guide

## What You Should See in Browser Console

### When Page Loads:
```
Age Estimator Photo Continuous Overlay - Initializing...
Age Estimator Photo Continuous Overlay - Settings: {useAws: false, showEmotions: true, ...}
Kiosk Mode: Enabled
Kiosk Image: https://example.com/your-image.png
Kiosk Display Time: 5 seconds
Kiosk display element created and added to DOM with display: block
Age Estimator Photo Continuous Overlay - Starting model loading...
Kiosk display shown
```

### When Starting Camera:
```
Age Estimator Photo Continuous: Starting camera...
Video and overlay now visible
Camera ready - hiding kiosk display
Kiosk display hidden
Monitoring active. Move closer to the camera to trigger automatic capture.
```

### When Face Detected:
```
Face detected, hiding kiosk display
Kiosk display hidden  (if it wasn't already hidden)
```

### When Face Analyzed:
```
Analysis complete! Result displayed next to face.
Kiosk timer set for 5 seconds
```

### When No Face After Timer:
```
Kiosk timer expired, checking if should show kiosk...
Kiosk display shown
```

## Quick Console Commands

Test kiosk visibility:
```javascript
// Check if kiosk is visible
document.getElementById('age-estimator-kiosk-display').style.display

// Show kiosk
photoAgeEstimator.showKioskDisplay()

// Hide kiosk
photoAgeEstimator.hideKioskDisplay()

// Check kiosk settings
ageEstimatorPhotoParams.kioskMode
ageEstimatorPhotoParams.kioskImage
ageEstimatorPhotoParams.kioskDisplayTime
```

## Common Issues and Solutions

### Issue: "Kiosk display element not found"
**Solution**: Kiosk mode is likely disabled. Enable it in WordPress admin settings.

### Issue: Kiosk shows but doesn't hide
**Solution**: Check z-index conflicts. Run:
```javascript
document.getElementById('age-estimator-photo-video').style.zIndex  // Should be "1"
document.getElementById('age-estimator-kiosk-display').style.zIndex  // Should be "20"
```

### Issue: No console logs appearing
**Solution**: Make sure you're on the correct page with the Age Estimator shortcode.

### Issue: "photoAgeEstimator is not defined"
**Solution**: Wait for page to fully load, or the Age Estimator script hasn't loaded yet.

## Visual Indicators

- **Kiosk Visible**: You'll see your advertisement image
- **Kiosk Hidden**: You'll see the camera feed
- **Face Detected**: Orange or green box around face
- **Result Shown**: Age appears next to face with colored border

The kiosk should seamlessly transition between showing your ad and the camera based on face detection!