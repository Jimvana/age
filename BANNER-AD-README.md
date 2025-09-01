# Fullscreen Banner Ad Feature - Enhanced Camera-Aware Version

## Overview
The fullscreen banner ad feature has been **enhanced** to display banner advertisements only when **BOTH conditions are met**:
1. **Camera view is actively running** (not just when fullscreen is entered)
2. **Fullscreen mode is active**

This ensures the banner only appears over the camera view when users are actually using the age estimation feature.

## Key Features ✨

### ✅ Camera-Aware Display
- Banner **only shows** when camera is active AND fullscreen is on
- Banner **disappears immediately** when camera stops or fullscreen exits
- Real-time monitoring of camera state changes

### ✅ Smart State Detection
- Multiple detection methods for robust camera state monitoring
- Video element monitoring (srcObject, visibility, play state)
- UI element monitoring (stop button, status indicators)
- Global variable tracking from age estimator scripts

### ✅ Enhanced User Experience
- Smooth animations when banner appears/disappears
- Proper positioning over camera view
- No interference with camera functionality
- Debug tools for troubleshooting

## Files Updated
1. **JavaScript**: `js/fullscreen-banner-ad.js` - Enhanced camera state monitoring
2. **CSS**: `css/fullscreen-banner-ad.css` - Improved positioning and visual feedback
3. **Debug Tool**: `banner-debug-test.html` - Testing and troubleshooting tool

## Quick Setup

### Step 1: Ensure Banner is Configured
1. Go to **WordPress Admin > Age Estimator > Settings**
2. Click the **"Display Options"** tab
3. Scroll to **"Fullscreen Banner Ad"** section
4. ✅ Check **"Show banner ad in fullscreen mode"**
5. Upload your banner image and configure settings

### Step 2: Test the Enhanced Functionality
1. Go to a page with the age estimator shortcode `[age_estimator]`
2. **Start the camera** by clicking "Start Monitoring" 
3. **Enter fullscreen** (double-click camera or use fullscreen button)
4. ✅ **Banner should appear** over the camera view
5. **Exit fullscreen** or **stop camera**
6. ✅ **Banner should disappear**

### Step 3: Debug if Needed
1. Open `banner-debug-test.html` in your browser
2. Copy the debug script and paste it into browser console on your age estimator page
3. Use the debug panel to test different scenarios

## Behavior Matrix

| Camera State | Fullscreen State | Banner Display |
|-------------|------------------|----------------|
| ❌ Inactive | ❌ Not Fullscreen | ❌ Hidden |
| ❌ Inactive | ✅ Fullscreen | ❌ Hidden |
| ✅ Active | ❌ Not Fullscreen | ❌ Hidden |
| ✅ Active | ✅ Fullscreen | ✅ **Visible** |

## Test Scenarios

### ✅ Scenario 1: Normal Operation
1. Start camera → Enter fullscreen → **Banner appears**
2. Exit fullscreen → **Banner disappears**
3. Re-enter fullscreen → **Banner appears again**

### ✅ Scenario 2: Camera Required
1. Do NOT start camera → Enter fullscreen → **Banner stays hidden**
2. Start camera while in fullscreen → **Banner appears**

### ✅ Scenario 3: Camera Stop Override
1. Start camera → Enter fullscreen → **Banner appears**
2. Stop camera while in fullscreen → **Banner disappears immediately**

## Enhanced Features

### 🔍 Debug Tools
```javascript
// Available in browser console:
debugBannerAd()        // Check current state
debugCameraCheck()     // Force camera state check
debugToggleFullscreen() // Toggle fullscreen mode
debugForceShow()       // Force show banner (testing)

// Get detailed state info:
window.ageEstimatorBannerAd.getState()
```

### 📊 State Monitoring
- Real-time camera state detection every 500ms
- Video element mutation observers
- UI state change monitoring
- Global variable synchronization

### 🎨 Visual Feedback
- Debug mode shows banner state indicators
- CSS classes for different banner states
- Animation improvements for smooth transitions
- Better mobile responsiveness

## Configuration Options

All previous configuration options remain the same:
- **Height**: 50-200 pixels
- **Position**: Top or Bottom
- **Opacity**: 30-100% transparency
- **Click URL**: Optional link when banner is clicked
- **Image Upload**: Banner image (recommended: 1200x100px)

## Browser Console Commands

### Quick Status Check
```javascript
// Check if banner ad is working
window.ageEstimatorBannerAd.getState()
```

### Force Camera State Check
```javascript
// Manually trigger camera state detection
window.ageEstimatorBannerAd.forceCameraCheck()
```

### Override Banner Display (Testing)
```javascript
// Force show banner regardless of state
window.ageEstimatorBannerAd.setVisible(true)

// Hide banner
window.ageEstimatorBannerAd.setVisible(false)
```

## Event Tracking

The enhanced banner tracks additional events:

```javascript
// Listen for enhanced banner events
document.addEventListener('age_estimator_banner_show', function(e) {
    console.log('Banner shown:', e.detail);
    // e.detail includes: cameraActive, fullscreen, position, height, opacity
});

document.addEventListener('age_estimator_banner_hide', function(e) {
    console.log('Banner hidden:', e.detail);
    // e.detail includes: reason (fullscreen_exit or camera_inactive)
});
```

## Troubleshooting

### Banner Not Appearing
1. ✅ Check camera is started ("Start Monitoring" clicked)
2. ✅ Verify fullscreen mode is active
3. ✅ Confirm banner is enabled in settings
4. ✅ Check banner image URL is accessible
5. ✅ Look for JavaScript errors in browser console

### Banner Appearing When It Shouldn't
1. 🔍 Use debug tools to check camera state detection
2. 🔍 Verify video element has proper srcObject
3. 🔍 Check if monitoring UI elements are correctly detected

### Debug Mode
Enable debug mode by adding `age-estimator-debug` class to your container:

```javascript
document.querySelector('.age-estimator-photo-container').classList.add('age-estimator-debug');
```

This will show:
- Visual borders around banner area
- State indicators
- Debug information overlays

## Performance

- **Lightweight**: ~12KB JavaScript + ~4KB CSS
- **Efficient**: Camera state checked every 500ms (only when needed)
- **Optimized**: Event-driven updates for state changes
- **Memory-safe**: Proper cleanup of event listeners

## Compatibility

- ✅ **Chrome/Edge**: Full support with hardware acceleration
- ✅ **Firefox**: Full support
- ✅ **Safari**: Full support
- ⚠️ **Mobile browsers**: Limited fullscreen API support
- ⚠️ **Older browsers**: May need polyfills

## Security

- **XSS Protection**: All content properly sanitized
- **External links**: Open in new tab with security attributes
- **Content validation**: Image URLs validated before display
- **Event isolation**: Banner events properly namespaced

---

## Support

If you encounter issues:

1. **Use the debug tools** in `banner-debug-test.html`
2. **Check browser console** for error messages
3. **Test different scenarios** using the debug panel
4. **Verify settings** in WordPress admin
5. **Test cross-browser** compatibility

**Enhanced Version**: 2.0  
**Compatible with**: Age Estimator Live 2.0+  
**Last updated**: August 2025

🎯 **Ready to use!** The banner will now intelligently appear only when both camera and fullscreen are active.
