/**
 * Quick Banner Fix - Run this in browser console
 * 
 * Instructions:
 * 1. Go to your Age Estimator page
 * 2. Open browser developer tools (F12)
 * 3. Go to Console tab
 * 4. Copy and paste this entire code
 * 5. Press Enter to run it
 * 6. Start camera and enter fullscreen to test
 */

console.log('🎯 Canvas Banner Fix Script Loading...');

// Force reload the banner with correct settings
function fixCanvasBanner() {
    console.log('🔧 Attempting to fix Canvas Banner...');
    
    // Check if Simple Canvas Banner exists
    if (!window.simpleCanvasBanner) {
        console.error('❌ Simple Canvas Banner not found. Make sure you\'re on the Age Estimator page.');
        return false;
    }
    
    // Get current state
    const currentState = window.simpleCanvasBanner.getState();
    console.log('📊 Current banner state:', currentState);
    
    // Check if banner element exists in DOM
    const bannerElement = document.getElementById('age-estimator-banner-ad');
    if (!bannerElement) {
        console.error('❌ Banner element not found in DOM. Banner might be disabled in admin settings.');
        return false;
    }
    
    const bannerImage = bannerElement.querySelector('.age-estimator-banner-image');
    if (!bannerImage) {
        console.error('❌ Banner image element not found. No image configured in admin settings.');
        return false;
    }
    
    console.log('✅ Banner elements found in DOM');
    console.log('🖼️ Image URL:', bannerImage.src);
    console.log('📐 Image data:', {
        height: bannerImage.dataset.height,
        position: bannerImage.dataset.position,
        opacity: bannerImage.dataset.opacity
    });
    
    // Force reload the banner image
    console.log('🔄 Force reloading banner image...');
    window.simpleCanvasBanner.forceLoadImage();
    
    // Wait a moment then force show for testing
    setTimeout(() => {
        console.log('🎯 Force showing banner for testing...');
        window.simpleCanvasBanner.forceTest();
        
        // Check state again
        const newState = window.simpleCanvasBanner.getState();
        console.log('📊 New banner state:', newState);
        
        if (newState.bannerImage && newState.bannerImage.src) {
            console.log('✅ SUCCESS: Banner image loaded successfully!');
            console.log('📝 To see banner normally: Start camera → Enter fullscreen mode');
        } else {
            console.log('❌ ISSUE: Banner image still not loading properly');
            console.log('💡 Check if image URL is accessible:', bannerImage.src);
            
            // Test image loading directly
            const testImg = new Image();
            testImg.onload = () => console.log('✅ Image URL is accessible');
            testImg.onerror = () => console.log('❌ Image URL is NOT accessible');
            testImg.src = bannerImage.src;
        }
    }, 1000);
    
    return true;
}

// Enhanced debug function
function debugBannerDetailed() {
    console.log('🔍 DETAILED BANNER DEBUG');
    console.log('========================');
    
    // Check if Simple Canvas Banner exists
    if (window.simpleCanvasBanner) {
        console.log('✅ Simple Canvas Banner found');
        const state = window.simpleCanvasBanner.getState();
        console.log('📊 Banner State:', state);
    } else {
        console.log('❌ Simple Canvas Banner not found');
    }
    
    // Check DOM elements
    const bannerElement = document.getElementById('age-estimator-banner-ad');
    if (bannerElement) {
        console.log('✅ Banner DOM element found');
        const bannerImage = bannerElement.querySelector('.age-estimator-banner-image');
        if (bannerImage) {
            console.log('✅ Banner image element found');
            console.log('🖼️ Image details:', {
                src: bannerImage.src,
                height: bannerImage.dataset.height,
                position: bannerImage.dataset.position,
                opacity: bannerImage.dataset.opacity,
                complete: bannerImage.complete,
                naturalWidth: bannerImage.naturalWidth,
                naturalHeight: bannerImage.naturalHeight
            });
        } else {
            console.log('❌ Banner image element not found');
        }
    } else {
        console.log('❌ Banner DOM element not found');
    }
    
    // Check for canvas
    const canvas = document.getElementById('age-estimator-banner-canvas');
    if (canvas) {
        console.log('✅ Banner canvas found:', {
            width: canvas.width,
            height: canvas.height,
            display: canvas.style.display
        });
    } else {
        console.log('❌ Banner canvas not found');
    }
}

// Force banner to show with uploaded image
function forceShowUploadedBanner() {
    console.log('🚀 Force showing uploaded banner image...');
    
    if (!window.simpleCanvasBanner) {
        console.error('❌ Simple Canvas Banner not found');
        return;
    }
    
    // Get the banner image URL from DOM
    const bannerElement = document.getElementById('age-estimator-banner-ad');
    if (bannerElement) {
        const bannerImage = bannerElement.querySelector('.age-estimator-banner-image');
        if (bannerImage && bannerImage.src) {
            console.log('🖼️ Found uploaded image:', bannerImage.src);
            
            // Force update the banner settings
            window.simpleCanvasBanner.settings.image = bannerImage.src;
            window.simpleCanvasBanner.settings.enabled = true;
            window.simpleCanvasBanner.settings.height = parseInt(bannerImage.dataset.height) || 100;
            window.simpleCanvasBanner.settings.position = bannerImage.dataset.position || 'bottom';
            window.simpleCanvasBanner.settings.opacity = parseFloat(bannerImage.dataset.opacity) || 0.9;
            
            console.log('⚙️ Updated banner settings:', window.simpleCanvasBanner.settings);
            
            // Force reload the image
            window.simpleCanvasBanner.bannerImage = new Image();
            window.simpleCanvasBanner.bannerImage.onload = () => {
                window.simpleCanvasBanner.bannerLoaded = true;
                console.log('✅ Banner image loaded successfully!');
                window.simpleCanvasBanner.forceTest();
            };
            window.simpleCanvasBanner.bannerImage.onerror = () => {
                console.error('❌ Failed to load banner image');
            };
            window.simpleCanvasBanner.bannerImage.crossOrigin = 'anonymous';
            window.simpleCanvasBanner.bannerImage.src = bannerImage.src;
        }
    }
}

// Add global functions for easy access
window.fixCanvasBanner = fixCanvasBanner;
window.debugBannerDetailed = debugBannerDetailed;
window.forceShowUploadedBanner = forceShowUploadedBanner;

console.log('✅ Canvas Banner Fix Script Loaded!');
console.log('📝 Available commands:');
console.log('   fixCanvasBanner() - Automatically fix banner issues');
console.log('   debugBannerDetailed() - Show detailed debug info');
console.log('   forceShowUploadedBanner() - Force show your uploaded image');
console.log('');
console.log('🎯 Quick fix: Run fixCanvasBanner() now!');
