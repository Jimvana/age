# Manual JavaScript Updates Required

Due to the complexity of the JavaScript changes, please manually update the following methods in `js/photo-age-estimator-continuous-overlay.js`:

## 1. Remove the force hide code at the bottom of the file

Find and remove this entire section:
```javascript
// Force hide fullscreen button and metrics on mobile
(function() {
    'use strict';
    
    function forceHideMobileElements() {
        // ... remove all this code ...
    }
    // ... remove all related code ...
})();
```

## 2. Update the fullscreen methods

Replace the following methods with the improved versions from the Mobile Fullscreen Fix:
- `isMobileDevice()`
- `isIOS()` (new method)
- `toggleFullscreen()`
- `isFullscreen()`
- `enterFullscreen()`
- `enterPseudoFullscreen()` (new method)
- `exitFullscreen()`
- `exitPseudoFullscreen()` (new method)
- `adjustFullscreenLayout()` (new method)
- `addIOSFullscreenCloseButton()` (new method)
- `updateElementReferences()` (new method)
- `restoreOriginalReferences()` (new method)
- `showMobileFullscreenHint()` (new method)

## 3. Update startCamera method

In the `video.onloadedmetadata` callback, find where the fullscreen button visibility is set and update it to:

```javascript
// Show fullscreen button (including on mobile)
const fullscreenButton = document.getElementById('age-estimator-fullscreen');
if (fullscreenButton) {
    fullscreenButton.style.display = 'block';
    
    // Make button larger on mobile for easier tapping
    if (this.isMobileDevice()) {
        fullscreenButton.style.width = '50px';
        fullscreenButton.style.height = '50px';
    }
}

// Add camera active class
const container = document.querySelector('.age-estimator-photo-container');
if (container) {
    container.classList.add('camera-active');
}
```

## 4. Update stopCamera method

Add this to remove the camera active class:

```javascript
const container = document.querySelector('.age-estimator-photo-container');
if (container) {
    container.classList.remove('camera-active');
}
```

## Testing

After making these changes:
1. Test on Android devices - fullscreen should show all layers
2. Test on iOS devices - custom fullscreen wrapper should work
3. Test on desktop - standard fullscreen should still work

If you need the complete updated methods, they are available in the artifacts created by Claude.
