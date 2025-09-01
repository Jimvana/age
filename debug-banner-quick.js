/**
 * Quick Banner Debug Script
 * Run this in browser console on your Age Estimator page
 */

console.log('🎯 Starting Banner Debug Session...');

// Check if banner ad system is loaded
if (typeof window.ageEstimatorBannerAd === 'undefined') {
    console.error('❌ Banner Ad system not loaded!');
    console.log('💡 Try refreshing the page and wait a few seconds before running this script');
} else {
    console.log('✅ Banner Ad system found');
    
    // Get current state
    const state = window.ageEstimatorBannerAd.getState();
    console.log('📊 Current State:', state);
    
    // Check banner element
    const banner = document.getElementById('age-estimator-banner-ad');
    if (!banner) {
        console.error('❌ Banner element not found in DOM!');
        console.log('💡 Check if banner is enabled in WordPress settings');
    } else {
        console.log('✅ Banner element found');
        console.log('📐 Banner styles:', {
            display: banner.style.display,
            visibility: banner.style.visibility,
            position: banner.style.position,
            zIndex: banner.style.zIndex,
            opacity: banner.style.opacity
        });
    }
    
    // Function to force show banner for testing
    window.testBannerShow = function() {
        console.log('🧪 TESTING: Force showing banner...');
        
        if (banner) {
            // Nuclear option - force all styles
            banner.style.display = 'block';
            banner.style.visibility = 'visible';
            banner.style.position = 'fixed';
            banner.style.zIndex = '999999';
            banner.style.opacity = '0.9';
            banner.style.left = '0';
            banner.style.right = '0';
            banner.style.bottom = '0';
            banner.style.height = '100px';
            banner.style.width = '100%';
            banner.classList.add('show-banner', 'force-banner-visible');
            
            console.log('✅ Banner force-shown for 5 seconds');
            
            // Hide after 5 seconds
            setTimeout(() => {
                banner.classList.remove('force-banner-visible');
                banner.style.display = 'none';
                banner.style.visibility = 'hidden';
                console.log('⏰ Test banner hidden');
            }, 5000);
        }
    };
    
    // Function to debug camera state
    window.testCameraState = function() {
        console.log('📷 TESTING: Camera state detection...');
        
        const video = document.getElementById('age-estimator-photo-video');
        const stopButton = document.getElementById('age-estimator-photo-stop-camera');
        const statusIndicator = document.getElementById('age-estimator-status');
        
        console.log('📹 Video element:', {
            found: !!video,
            srcObject: video ? !!video.srcObject : 'not found',
            display: video ? video.style.display : 'not found',
            paused: video ? video.paused : 'not found',
            readyState: video ? video.readyState : 'not found'
        });
        
        console.log('🛑 Stop button:', {
            found: !!stopButton,
            display: stopButton ? stopButton.style.display : 'not found'
        });
        
        console.log('📊 Status indicator:', {
            found: !!statusIndicator,
            display: statusIndicator ? statusIndicator.style.display : 'not found'
        });
        
        // Force camera check
        const result = window.ageEstimatorBannerAd.forceCameraCheck();
        console.log('🔍 Force camera check result:', result);
    };
    
    // Function to test fullscreen
    window.testFullscreen = function() {
        console.log('🖥️ TESTING: Fullscreen state...');
        
        const isFullscreen = !!(document.fullscreenElement || 
                              document.webkitFullscreenElement || 
                              document.mozFullScreenElement || 
                              document.msFullscreenElement);
        
        console.log('📺 Fullscreen state:', isFullscreen);
        
        if (!isFullscreen) {
            console.log('💡 Try entering fullscreen mode to test banner');
            const container = document.querySelector('.age-estimator-photo-container');
            if (container) {
                console.log('🎯 Container found, attempting fullscreen...');
                if (container.requestFullscreen) {
                    container.requestFullscreen();
                } else if (container.webkitRequestFullscreen) {
                    container.webkitRequestFullscreen();
                } else if (container.mozRequestFullScreen) {
                    container.mozRequestFullScreen();
                } else if (container.msRequestFullscreen) {
                    container.msRequestFullscreen();
                }
            }
        }
    };
    
    // Auto-run diagnostics
    console.log('🔍 Running automatic diagnostics...');
    window.testCameraState();
    
    console.log('');
    console.log('🧪 Test Functions Available:');
    console.log('• testBannerShow() - Force show banner for 5 seconds');
    console.log('• testCameraState() - Check camera detection');
    console.log('• testFullscreen() - Check/enter fullscreen mode');
    console.log('• debugBannerAd() - Full state check');
    console.log('');
    
    // Check if we're in the right conditions
    if (state.isFullscreen && state.isCameraActive) {
        console.log('🎉 PERFECT CONDITIONS: Camera active + Fullscreen mode');
        console.log('📺 Banner should be visible now!');
        
        if (!state.bannerVisible) {
            console.warn('⚠️ Banner should be showing but isn\'t detected as visible');
            console.log('🧪 Running testBannerShow() in 2 seconds...');
            setTimeout(window.testBannerShow, 2000);
        }
    } else {
        console.log('⏳ WAITING FOR CONDITIONS:');
        console.log('Camera active:', state.isCameraActive ? '✅' : '❌');
        console.log('Fullscreen mode:', state.isFullscreen ? '✅' : '❌');
        
        if (!state.isCameraActive) {
            console.log('💡 Start the camera first ("Start Monitoring" button)');
        }
        if (!state.isFullscreen) {
            console.log('💡 Enter fullscreen mode (double-click camera or fullscreen button)');
        }
    }
}

// Monitor state changes
let lastState = null;
setInterval(() => {
    if (window.ageEstimatorBannerAd) {
        const currentState = window.ageEstimatorBannerAd.getState();
        
        if (!lastState || 
            lastState.isCameraActive !== currentState.isCameraActive || 
            lastState.isFullscreen !== currentState.isFullscreen ||
            lastState.bannerVisible !== currentState.bannerVisible) {
            
            console.log('📈 STATE CHANGE:', {
                camera: currentState.isCameraActive ? '📹 ON' : '📹 OFF',
                fullscreen: currentState.isFullscreen ? '🖥️ ON' : '🖥️ OFF',
                banner: currentState.bannerVisible ? '🎯 VISIBLE' : '🎯 HIDDEN'
            });
            
            lastState = currentState;
        }
    }
}, 1000);

console.log('✅ Debug session ready! Use the test functions above to diagnose issues.');
