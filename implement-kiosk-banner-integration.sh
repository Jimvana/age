#!/bin/bash

# Kiosk-Aware Banner Ad Implementation Script
# This script backs up the original banner ad file and replaces it with the kiosk-aware version

echo "🎯 Implementing Kiosk-Aware Banner Ad System..."
echo "=================================================================="

# Define paths
PLUGIN_DIR="/Users/video/DevKinsta/public/age-estimation/wp-content/plugins/Age-estimator-live"
JS_DIR="$PLUGIN_DIR/js"
ORIGINAL_FILE="$JS_DIR/fullscreen-banner-ad.js"
NEW_FILE="$JS_DIR/fullscreen-banner-ad-kiosk-aware.js"
BACKUP_DIR="$PLUGIN_DIR/backup/kiosk-banner-integration-$(date +%Y%m%d_%H%M%S)"

# Create backup directory
echo "📁 Creating backup directory..."
mkdir -p "$BACKUP_DIR"

# Backup original file if it exists
if [ -f "$ORIGINAL_FILE" ]; then
    echo "💾 Backing up original fullscreen-banner-ad.js..."
    cp "$ORIGINAL_FILE" "$BACKUP_DIR/fullscreen-banner-ad-original.js"
    echo "✅ Original file backed up to: $BACKUP_DIR/fullscreen-banner-ad-original.js"
else
    echo "⚠️  Original fullscreen-banner-ad.js not found - this might be a fresh installation"
fi

# Replace the original file with the kiosk-aware version
if [ -f "$NEW_FILE" ]; then
    echo "🔄 Replacing fullscreen-banner-ad.js with kiosk-aware version..."
    cp "$NEW_FILE" "$ORIGINAL_FILE"
    echo "✅ Kiosk-aware banner ad installed successfully!"
else
    echo "❌ Error: Kiosk-aware file not found at $NEW_FILE"
    exit 1
fi

# Create documentation
echo "📋 Creating implementation documentation..."
cat > "$BACKUP_DIR/KIOSK_BANNER_INTEGRATION.md" << 'EOF'
# Kiosk-Aware Banner Ad Implementation

## What Was Changed

The fullscreen banner ad system has been enhanced to work intelligently with the kiosk mode system.

### Key Changes

1. **Kiosk State Monitoring**: The banner ad now monitors the kiosk display element (#age-estimator-kiosk-display)
2. **Smart Visibility Logic**: Banner only shows when ALL conditions are met:
   - Fullscreen mode is active
   - Camera is active
   - Kiosk display is NOT visible
   - Banner is enabled in settings

### New Behavior

| Scenario | Camera | Fullscreen | Kiosk Visible | Banner Display |
|----------|--------|------------|---------------|----------------|
| Normal operation | ✅ Active | ✅ Active | ❌ Hidden | ✅ **SHOW** |
| Kiosk ad showing | ✅ Active | ✅ Active | ✅ **Visible** | ❌ **HIDE** |
| Camera off | ❌ Inactive | ✅ Active | ✅ Visible | ❌ Hide |
| Not fullscreen | ✅ Active | ❌ Inactive | ❌ Hidden | ❌ Hide |

### Debug Functions

New browser console commands available:

```javascript
// Debug overall state
debugBannerAd()

// Debug kiosk state specifically  
debugKioskState()

// Test the integration
testKioskIntegration()

// Force show/hide for testing
forceShowBanner()
forceHideBanner()
```

### Technical Details

- **Kiosk Detection**: Monitors display, visibility, opacity, and dimensions of kiosk element
- **Real-time Updates**: Uses MutationObserver for instant response to kiosk changes
- **Performance**: Checks kiosk state every 250ms for responsive behavior
- **Fallback Safe**: If kiosk element not found, works like original banner system

### Files Modified

- `js/fullscreen-banner-ad.js` - Replaced with kiosk-aware version
- Original backed up to this directory as `fullscreen-banner-ad-original.js`

### Testing Scenarios

1. **Test Kiosk Integration**:
   - Enable kiosk mode in admin
   - Start camera and enter fullscreen
   - Wait for kiosk ad to appear - banner should disappear
   - Trigger face detection - kiosk hides, banner should appear

2. **Test Normal Operation**:
   - Disable kiosk mode
   - Start camera and enter fullscreen
   - Banner should appear normally

3. **Test Mixed Scenarios**:
   - Enable kiosk mode
   - Enter fullscreen without starting camera
   - Both kiosk and banner should remain hidden
   - Start camera - kiosk should appear, banner stays hidden

### Rollback Instructions

If you need to revert to the original version:

```bash
cp fullscreen-banner-ad-original.js ../js/fullscreen-banner-ad.js
```

## Implementation Date
$(date)

## Status
✅ Successfully implemented and ready for testing
EOF

echo "📋 Documentation created: $BACKUP_DIR/KIOSK_BANNER_INTEGRATION.md"

# Create a test script
echo "🧪 Creating test script..."
cat > "$BACKUP_DIR/test-kiosk-banner.js" << 'EOF'
/**
 * Test Script for Kiosk-Aware Banner Ad
 * 
 * Run this in browser console to test the integration
 */

function testKioskBannerIntegration() {
    console.log('🧪 Testing Kiosk-Aware Banner Ad Integration');
    console.log('===============================================');
    
    // Check if banner ad system is loaded
    if (typeof window.ageEstimatorBannerAd === 'undefined') {
        console.error('❌ Banner ad system not loaded');
        return false;
    }
    
    // Get current state
    const state = window.ageEstimatorBannerAd.getState();
    
    console.log('📊 Current State:', {
        fullscreen: state.isFullscreen,
        camera: state.isCameraActive,
        kiosk: state.isKioskVisible,
        banner: state.bannerVisible
    });
    
    // Test kiosk element detection
    const kioskElement = document.getElementById('age-estimator-kiosk-display');
    if (!kioskElement) {
        console.warn('⚠️  Kiosk element not found - kiosk mode may not be enabled');
    } else {
        console.log('✅ Kiosk element found');
        console.log('📱 Kiosk element state:', {
            display: kioskElement.style.display,
            visibility: kioskElement.style.visibility,
            dimensions: kioskElement.getBoundingClientRect()
        });
    }
    
    // Test banner element
    const bannerElement = document.getElementById('age-estimator-banner-ad');
    if (!bannerElement) {
        console.warn('⚠️  Banner element not found');
    } else {
        console.log('✅ Banner element found');
        console.log('📊 Banner element state:', {
            display: bannerElement.style.display,
            visibility: bannerElement.style.visibility,
            zIndex: bannerElement.style.zIndex
        });
    }
    
    // Expected behavior analysis
    console.log('\n🎯 Expected Behavior Analysis:');
    const shouldShowBanner = state.isFullscreen && state.isCameraActive && !state.isKioskVisible;
    console.log(`Banner should ${shouldShowBanner ? 'SHOW' : 'HIDE'} based on current state`);
    
    if (state.bannerVisible === shouldShowBanner) {
        console.log('✅ Banner behavior is CORRECT');
    } else {
        console.log('❌ Banner behavior is INCORRECT');
        console.log(`Expected: ${shouldShowBanner}, Actual: ${state.bannerVisible}`);
    }
    
    return true;
}

// Auto-run test
testKioskBannerIntegration();
EOF

echo "🧪 Test script created: $BACKUP_DIR/test-kiosk-banner.js"

# Show summary
echo ""
echo "🎉 KIOSK-AWARE BANNER AD IMPLEMENTATION COMPLETE!"
echo "=================================================================="
echo "✅ Original file backed up"
echo "✅ Kiosk-aware version installed"
echo "✅ Documentation created"
echo "✅ Test script available"
echo ""
echo "📋 What's New:"
echo "   • Banner ad now respects kiosk display state"
echo "   • Banner automatically hides when kiosk ad is visible"
echo "   • Enhanced debug functions available"
echo "   • Real-time monitoring with MutationObserver"
echo ""
echo "🧪 To Test:"
echo "   1. Visit your age estimator page"
echo "   2. Open browser console"
echo "   3. Run: testKioskIntegration()"
echo "   4. Test various scenarios (fullscreen + camera + kiosk)"
echo ""
echo "📁 Backup Location: $BACKUP_DIR"
echo "📋 Documentation: $BACKUP_DIR/KIOSK_BANNER_INTEGRATION.md"
echo ""
echo "🔧 Debug Commands (in browser console):"
echo "   debugBannerAd()        - Check overall state"
echo "   debugKioskState()      - Check kiosk specifically"
echo "   testKioskIntegration() - Run integration test"
echo ""
echo "Ready to test! 🚀"
