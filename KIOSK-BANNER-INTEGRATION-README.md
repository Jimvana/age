# Kiosk-Aware Banner Ad Integration

## Overview
The banner ad system has been enhanced to work intelligently with the kiosk mode system. The banner will now automatically hide when the kiosk ad image is displayed.

## What This Solves
Previously, both the banner ad and kiosk ad could appear simultaneously, creating visual conflicts. Now the banner ad respects the kiosk state and hides when the kiosk ad is visible.

## New Behavior Matrix

| Camera State | Fullscreen | Kiosk Display | Banner Display | Scenario |
|-------------|------------|---------------|----------------|----------|
| ✅ Active | ✅ Active | ❌ Hidden | ✅ **SHOW** | Normal operation |
| ✅ Active | ✅ Active | ✅ **Visible** | ❌ **HIDE** | Kiosk ad showing |
| ❌ Inactive | ✅ Active | ✅ Visible | ❌ Hide | Camera off |
| ✅ Active | ❌ Inactive | ❌ Hidden | ❌ Hide | Not fullscreen |

## Key Features

### 🎯 Smart Visibility Logic
- Banner only shows when ALL conditions are met:
  - Fullscreen mode is active
  - Camera is active  
  - Kiosk display is NOT visible
  - Banner is enabled in settings

### 🔍 Real-time Kiosk Detection
- Monitors kiosk element (`#age-estimator-kiosk-display`) in real-time
- Uses MutationObserver for instant response to changes
- Checks display, visibility, opacity, and dimensions
- Updates every 250ms for responsive behavior

### 🧪 Enhanced Debug Tools
New browser console commands:
```javascript
debugBannerAd()        // Check overall state
debugKioskState()      // Check kiosk state specifically  
testKioskIntegration() // Run full integration test
forceShowBanner()      // Force show for testing
forceHideBanner()      // Force hide for testing
```

## Installation

### Quick Installation
```bash
cd /Users/video/DevKinsta/public/age-estimation/wp-content/plugins/Age-estimator-live
chmod +x implement-kiosk-banner-integration.sh
./implement-kiosk-banner-integration.sh
```

### Manual Installation
1. Backup current `js/fullscreen-banner-ad.js`
2. Replace with `js/fullscreen-banner-ad-kiosk-aware.js`
3. Test the integration

## Testing Scenarios

### ✅ Scenario 1: Normal Operation
1. Enable banner ad in settings
2. Start camera and enter fullscreen
3. **Expected**: Banner appears over camera view
4. **Expected**: Banner disappears when exiting fullscreen

### ✅ Scenario 2: Kiosk Integration  
1. Enable both banner ad and kiosk mode
2. Start camera and enter fullscreen  
3. Wait for kiosk ad to appear (when no face detected)
4. **Expected**: Banner disappears when kiosk ad shows
5. Move face into view to trigger detection
6. **Expected**: Kiosk ad hides, banner reappears

### ✅ Scenario 3: Mixed States
1. Enable kiosk mode
2. Enter fullscreen WITHOUT starting camera
3. **Expected**: Both kiosk and banner remain hidden
4. Start camera
5. **Expected**: Kiosk appears, banner stays hidden

## Technical Implementation

### Files Created/Modified
- `js/fullscreen-banner-ad-kiosk-aware.js` - New kiosk-aware version
- `implement-kiosk-banner-integration.sh` - Installation script
- Original `js/fullscreen-banner-ad.js` - Will be backed up and replaced

### Key Technical Changes
1. **Kiosk State Monitoring**: Added `isKioskVisible` property and monitoring
2. **Enhanced Visibility Logic**: Updated `updateBannerVisibility()` to include kiosk state  
3. **MutationObserver**: Real-time monitoring of kiosk element changes
4. **Debug Functions**: Enhanced debugging with kiosk state information
5. **Event Tracking**: Updated analytics to include kiosk state

### Compatibility
- ✅ Fully backward compatible
- ✅ Works with existing banner ad settings
- ✅ Works with existing kiosk mode settings
- ✅ Falls back gracefully if kiosk element not found

## Troubleshooting

### Banner Not Hiding When Kiosk Shows
1. Check kiosk element exists: `document.getElementById('age-estimator-kiosk-display')`
2. Run debug: `debugKioskState()`
3. Verify kiosk mode is enabled in admin settings

### Banner Not Showing When Expected
1. Run debug: `debugBannerAd()`
2. Check all conditions: fullscreen + camera + no kiosk + enabled
3. Verify banner is enabled in admin settings

### Debug Commands Not Working
1. Ensure page has fully loaded
2. Check browser console for JavaScript errors
3. Verify the kiosk-aware version is installed

## Rollback

If you need to revert to the original version:
```bash
cd /Users/video/DevKinsta/public/age-estimation/wp-content/plugins/Age-estimator-live
# Restore from backup (check backup directory for specific timestamp)
cp backup/kiosk-banner-integration-*/fullscreen-banner-ad-original.js js/fullscreen-banner-ad.js
```

## Support

The enhanced system includes comprehensive logging and debug tools. Use the browser console commands to diagnose any issues:

```javascript
// Quick health check
testKioskIntegration()

// Detailed state info  
debugBannerAd()

// Kiosk-specific debugging
debugKioskState()
```

---

**Status**: ✅ Ready for implementation  
**Compatibility**: All existing functionality preserved  
**Testing**: Comprehensive test scenarios included  
**Support**: Enhanced debug tools and logging  
