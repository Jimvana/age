/**
 * Fullscreen Banner Ad Handler - Kiosk-Aware Version
 * Age Estimator Live Plugin
 * Enhanced to hide banner when kiosk display is visible
 */

(function($) {
    'use strict';
    
    class FullscreenBannerAd {
        constructor() {
            this.banner = null;
            this.container = null;
            this.isFullscreen = false;
            this.isCameraActive = false;
            this.isKioskVisible = false; // NEW: Track kiosk state
            this.videoElement = null;
            this.kioskElement = null; // NEW: Kiosk display element
            this.settings = {
                enabled: false,
                height: 100,
                position: 'bottom',
                opacity: 0.9,
                image: '',
                link: ''
            };
            
            this.init();
        }
        
        init() {
            this.container = document.querySelector('.age-estimator-photo-container');
            this.banner = document.getElementById('age-estimator-banner-ad');
            this.videoElement = document.getElementById('age-estimator-photo-video');
            this.kioskElement = document.getElementById('age-estimator-kiosk-display'); // NEW: Get kiosk element
            
            if (!this.container || !this.banner) {
                console.log('Banner Ad: Container or banner element not found');
                return;
            }
            
            // Get settings from banner image data attributes
            this.loadSettings();
            
            if (!this.settings.enabled) {
                console.log('Banner Ad: Feature disabled');
                return;
            }
            
            // Setup banner styling
            this.setupBanner();
            
            // Add fullscreen event listeners
            this.addFullscreenListeners();
            
            // Add camera state monitoring
            this.addCameraMonitoring();
            
            // NEW: Add kiosk state monitoring
            this.addKioskMonitoring();
            
            // Add fullscreen trigger
            this.addFullscreenTrigger();
            
            console.log('Banner Ad: Initialized successfully (Kiosk-Aware)', this.settings);
        }
        
        loadSettings() {
            // Check if banner is enabled by looking for the banner element with image
            const bannerImage = this.banner.querySelector('.age-estimator-banner-image');
            
            if (!bannerImage) {
                this.settings.enabled = false;
                return;
            }
            
            this.settings.enabled = true;
            this.settings.height = parseInt(bannerImage.dataset.height) || 100;
            this.settings.position = bannerImage.dataset.position || 'bottom';
            this.settings.opacity = parseFloat(bannerImage.dataset.opacity) || 0.9;
            this.settings.image = bannerImage.src || '';
            
            // Check for link
            const bannerLink = this.banner.querySelector('.age-estimator-banner-link');
            this.settings.link = bannerLink ? bannerLink.href : '';
        }
        
        setupBanner() {
            if (!this.banner) return;
            
            // CRITICAL: Force proper positioning
            this.banner.style.position = 'fixed';
            this.banner.style.left = '0';
            this.banner.style.right = '0';
            this.banner.style.zIndex = '99999';
            this.banner.style.height = this.settings.height + 'px';
            this.banner.style.opacity = this.settings.opacity;
            
            // Set position
            if (this.settings.position === 'top') {
                this.banner.style.top = '0';
                this.banner.style.bottom = 'auto';
            } else {
                this.banner.style.bottom = '0';
                this.banner.style.top = 'auto';
            }
            
            // Add position class
            this.banner.classList.add('position-' + this.settings.position);
            
            // Ensure banner is initially hidden
            this.banner.style.display = 'none';
            this.banner.classList.remove('show-banner');
            
            console.log('Banner Ad: Setup complete with height:', this.settings.height, 'position:', this.settings.position);
        }
        
        addFullscreenListeners() {
            // Multiple event listeners for cross-browser compatibility
            document.addEventListener('fullscreenchange', () => this.handleFullscreenChange());
            document.addEventListener('webkitfullscreenchange', () => this.handleFullscreenChange());
            document.addEventListener('mozfullscreenchange', () => this.handleFullscreenChange());
            document.addEventListener('MSFullscreenChange', () => this.handleFullscreenChange());
            
            // Also listen for escape key to ensure we catch manual exits
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isFullscreen) {
                    // Small delay to let fullscreen exit first
                    setTimeout(() => this.handleFullscreenChange(), 100);
                }
            });
        }
        
        addCameraMonitoring() {
            // Monitor camera state changes
            const self = this;
            
            // Check for camera state periodically
            setInterval(() => {
                self.checkCameraState();
            }, 500);
            
            // Listen for video element changes if available
            if (this.videoElement) {
                // Monitor video src changes
                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'src') {
                            self.checkCameraState();
                        }
                    });
                });
                
                observer.observe(this.videoElement, {
                    attributes: true,
                    attributeFilter: ['src']
                });
                
                // Monitor video display changes
                const styleObserver = new MutationObserver(() => {
                    self.checkCameraState();
                });
                
                styleObserver.observe(this.videoElement, {
                    attributes: true,
                    attributeFilter: ['style']
                });
            }
            
            // Check initial camera state
            this.checkCameraState();
        }
        
        // NEW: Add kiosk monitoring functionality
        addKioskMonitoring() {
            if (!this.kioskElement) {
                console.log('Banner Ad: Kiosk element not found, skipping kiosk monitoring');
                return;
            }
            
            const self = this;
            
            // Check for kiosk state periodically
            setInterval(() => {
                self.checkKioskState();
            }, 250); // Check more frequently for responsive kiosk detection
            
            // Monitor kiosk display changes using MutationObserver
            const kioskObserver = new MutationObserver(() => {
                self.checkKioskState();
            });
            
            // Observe style changes (display, visibility)
            kioskObserver.observe(this.kioskElement, {
                attributes: true,
                attributeFilter: ['style', 'class']
            });
            
            // Also observe if content changes
            kioskObserver.observe(this.kioskElement, {
                childList: true,
                subtree: true
            });
            
            // Check initial kiosk state
            this.checkKioskState();
            
            console.log('Banner Ad: Kiosk monitoring enabled');
        }
        
        // NEW: Check if kiosk display is currently visible
        checkKioskState() {
            if (!this.kioskElement) {
                this.isKioskVisible = false;
                return;
            }
            
            let newKioskState = false;
            
            // Method 1: Check if kiosk element is displayed and visible
            const computedStyle = window.getComputedStyle(this.kioskElement);
            const isDisplayed = this.kioskElement.style.display !== 'none' && computedStyle.display !== 'none';
            const isVisible = this.kioskElement.style.visibility !== 'hidden' && computedStyle.visibility !== 'hidden';
            const hasOpacity = parseFloat(computedStyle.opacity) > 0;
            
            newKioskState = isDisplayed && isVisible && hasOpacity;
            
            // Method 2: Check if kiosk has visible content (image)
            if (newKioskState) {
                const kioskImage = this.kioskElement.querySelector('img');
                if (!kioskImage || kioskImage.style.display === 'none') {
                    newKioskState = false;
                }
            }
            
            // Method 3: Check if kiosk element has actual dimensions
            if (newKioskState) {
                const rect = this.kioskElement.getBoundingClientRect();
                if (rect.width === 0 || rect.height === 0) {
                    newKioskState = false;
                }
            }
            
            // Update kiosk state if changed
            if (newKioskState !== this.isKioskVisible) {
                this.isKioskVisible = newKioskState;
                console.log('Banner Ad: Kiosk visibility changed to:', this.isKioskVisible ? 'Visible' : 'Hidden');
                
                // Update banner visibility based on new kiosk state
                this.updateBannerVisibility();
            }
        }
        
        checkCameraState() {
            let newCameraState = false;
            
            // Method 1: Check if video element has srcObject and is visible
            if (this.videoElement) {
                const hasStream = this.videoElement.srcObject !== null;
                const isVisible = this.videoElement.style.display !== 'none';
                newCameraState = hasStream && isVisible;
            }
            
            // Method 2: Check for specific DOM indicators
            if (!newCameraState) {
                // Look for stop camera button being visible (indicates camera is active)
                const stopButton = document.getElementById('age-estimator-photo-stop-camera');
                if (stopButton && stopButton.style.display !== 'none') {
                    newCameraState = true;
                }
                
                // Look for monitoring status indicator
                const statusIndicator = document.getElementById('age-estimator-status');
                if (statusIndicator && statusIndicator.style.display !== 'none') {
                    newCameraState = true;
                }
            }
            
            // Method 3: Check video element playing state
            if (!newCameraState && this.videoElement) {
                newCameraState = !this.videoElement.paused && !this.videoElement.ended && this.videoElement.readyState > 2;
            }
            
            // Update camera state if changed
            if (newCameraState !== this.isCameraActive) {
                this.isCameraActive = newCameraState;
                console.log('Banner Ad: Camera state changed to:', this.isCameraActive ? 'Active' : 'Inactive');
                
                // Update banner visibility based on new state
                this.updateBannerVisibility();
            }
        }
        
        addFullscreenTrigger() {
            // Add double-click to enter fullscreen
            const cameraArea = this.container.querySelector('#age-estimator-photo-camera');
            if (cameraArea) {
                cameraArea.addEventListener('dblclick', () => {
                    this.toggleFullscreen();
                });
                
                // Add visual hint
                cameraArea.style.cursor = 'pointer';
                cameraArea.title = 'Double-click to enter fullscreen mode';
            }
            
            // Add fullscreen button if it doesn't exist
            this.addFullscreenButton();
        }
        
        addFullscreenButton() {
            // Check if fullscreen button already exists
            if (this.container.querySelector('.fullscreen-button')) {
                return;
            }
            
            const controlsContainer = this.container.querySelector('.age-estimator-photo-controls');
            if (!controlsContainer) return;
            
            const fullscreenButton = document.createElement('button');
            fullscreenButton.className = 'age-estimator-photo-button fullscreen-button';
            fullscreenButton.innerHTML = '⛶ Fullscreen';
            fullscreenButton.style.marginLeft = '10px';
            
            fullscreenButton.addEventListener('click', () => {
                this.toggleFullscreen();
            });
            
            controlsContainer.appendChild(fullscreenButton);
        }
        
        toggleFullscreen() {
            if (!document.fullscreenElement && 
                !document.webkitFullscreenElement && 
                !document.mozFullScreenElement && 
                !document.msFullscreenElement) {
                
                // Enter fullscreen
                this.enterFullscreen();
            } else {
                // Exit fullscreen
                this.exitFullscreen();
            }
        }
        
        enterFullscreen() {
            const element = this.container;
            
            if (element.requestFullscreen) {
                element.requestFullscreen();
            } else if (element.webkitRequestFullscreen) {
                element.webkitRequestFullscreen();
            } else if (element.mozRequestFullScreen) {
                element.mozRequestFullScreen();
            } else if (element.msRequestFullscreen) {
                element.msRequestFullscreen();
            }
        }
        
        exitFullscreen() {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }
        
        handleFullscreenChange() {
            const isNowFullscreen = !!(document.fullscreenElement || 
                                     document.webkitFullscreenElement || 
                                     document.mozFullScreenElement || 
                                     document.msFullscreenElement);
            
            console.log('Banner Ad: Fullscreen state changed to:', isNowFullscreen);
            
            if (isNowFullscreen !== this.isFullscreen) {
                this.isFullscreen = isNowFullscreen;
                this.updateFullscreenButton();
                this.updateBannerVisibility();
            }
        }
        
        // MODIFIED: Updated banner visibility logic to include kiosk state
        updateBannerVisibility() {
            // Banner should only be shown when ALL these conditions are met:
            // 1. Fullscreen mode is active
            // 2. Camera is active  
            // 3. Kiosk display is NOT visible
            // 4. Banner is enabled
            const shouldShow = this.isFullscreen && 
                              this.isCameraActive && 
                              !this.isKioskVisible && // NEW: Hide banner when kiosk is visible
                              this.settings.enabled;
            
            console.log('Banner Ad: Evaluating visibility (Kiosk-Aware):', {
                isFullscreen: this.isFullscreen,
                isCameraActive: this.isCameraActive,
                isKioskVisible: this.isKioskVisible, // NEW: Log kiosk state
                enabled: this.settings.enabled,
                shouldShow: shouldShow
            });
            
            if (shouldShow) {
                this.showBanner();
            } else {
                this.hideBanner();
            }
        }
        
        showBanner() {
            if (!this.banner || !this.settings.enabled) return;
            
            console.log('Banner Ad: SHOWING banner (fullscreen + camera active + no kiosk)');
            
            // FORCE SHOW - Override any conflicting styles
            this.banner.style.display = 'block';
            this.banner.style.visibility = 'visible';
            this.banner.style.opacity = this.settings.opacity;
            this.banner.style.position = 'fixed';
            this.banner.style.zIndex = '99999';
            this.banner.style.left = '0';
            this.banner.style.right = '0';
            this.banner.style.height = this.settings.height + 'px';
            
            // Set position again
            if (this.settings.position === 'top') {
                this.banner.style.top = '0';
                this.banner.style.bottom = 'auto';
            } else {
                this.banner.style.bottom = '0';
                this.banner.style.top = 'auto';
            }
            
            // Add class for CSS targeting
            this.banner.classList.add('show-banner');
            
            // Remove any existing animation classes
            this.banner.classList.remove('banner-exiting');
            
            // Add entrance animation
            this.banner.classList.add('banner-entering');
            
            // Remove animation class after animation completes
            setTimeout(() => {
                this.banner.classList.remove('banner-entering');
            }, 500);
            
            // Track banner display for analytics
            this.trackBannerEvent('show');
            
            // Trigger custom event
            document.dispatchEvent(new CustomEvent('age_estimator_banner_show', {
                detail: {
                    position: this.settings.position,
                    height: this.settings.height,
                    opacity: this.settings.opacity,
                    cameraActive: this.isCameraActive,
                    fullscreen: this.isFullscreen,
                    kioskVisible: this.isKioskVisible // NEW: Include kiosk state
                }
            }));
        }
        
        hideBanner() {
            if (!this.banner) return;
            
            let hideReason = 'unknown';
            if (!this.isFullscreen) hideReason = 'fullscreen_exit';
            else if (!this.isCameraActive) hideReason = 'camera_inactive';
            else if (this.isKioskVisible) hideReason = 'kiosk_visible'; // NEW: Kiosk hide reason
            
            console.log('Banner Ad: Hiding banner -', hideReason);
            
            // Remove show class
            this.banner.classList.remove('show-banner');
            
            // Remove entrance animation if still present
            this.banner.classList.remove('banner-entering');
            
            // Add exit animation
            this.banner.classList.add('banner-exiting');
            
            // Hide after animation
            setTimeout(() => {
                this.banner.style.display = 'none';
                this.banner.style.visibility = 'hidden';
                this.banner.classList.remove('banner-exiting');
            }, 300);
            
            // Track banner hide for analytics
            this.trackBannerEvent('hide');
            
            // Trigger custom event
            document.dispatchEvent(new CustomEvent('age_estimator_banner_hide', {
                detail: {
                    reason: hideReason
                }
            }));
        }
        
        updateFullscreenButton() {
            const button = this.container.querySelector('.fullscreen-button');
            if (button) {
                button.innerHTML = this.isFullscreen ? '⛶ Exit Fullscreen' : '⛶ Fullscreen';
            }
        }
        
        trackBannerEvent(action) {
            // Track banner events for analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', 'banner_ad_' + action, {
                    'event_category': 'Age Estimator',
                    'event_label': 'Fullscreen Banner',
                    'custom_map': {
                        'camera_state': this.isCameraActive ? 'active' : 'inactive',
                        'fullscreen_state': this.isFullscreen ? 'active' : 'inactive',
                        'kiosk_state': this.isKioskVisible ? 'visible' : 'hidden' // NEW: Track kiosk state
                    }
                });
            }
            
            // WordPress action hook
            if (typeof wp !== 'undefined' && wp.hooks) {
                wp.hooks.doAction('age_estimator_banner_' + action, {
                    position: this.settings.position,
                    height: this.settings.height,
                    opacity: this.settings.opacity,
                    cameraActive: this.isCameraActive,
                    fullscreen: this.isFullscreen,
                    kioskVisible: this.isKioskVisible // NEW: Include kiosk state
                });
            }
            
            console.log(`Banner Ad: Event tracked - ${action}`, {
                camera: this.isCameraActive,
                fullscreen: this.isFullscreen,
                kiosk: this.isKioskVisible // NEW: Log kiosk state
            });
        }
        
        // Public method to force check camera state (for debugging)
        forceCameraCheck() {
            this.checkCameraState();
            this.checkKioskState(); // NEW: Also check kiosk state
            return {
                camera: this.isCameraActive,
                fullscreen: this.isFullscreen,
                kiosk: this.isKioskVisible, // NEW: Include kiosk state
                banner: this.banner ? this.banner.style.display : 'no banner element',
                bannerVisible: this.banner ? this.banner.style.visibility : 'no banner element'
            };
        }
        
        // Public method to update settings
        updateSettings(newSettings) {
            this.settings = { ...this.settings, ...newSettings };
            this.setupBanner();
            this.updateBannerVisibility();
            
            console.log('Banner Ad: Settings updated', this.settings);
        }
        
        // Public method to manually show/hide banner (for testing)
        setVisible(visible) {
            if (visible) {
                // Force show regardless of state (for testing)
                this.showBanner();
            } else {
                this.hideBanner();
            }
        }
        
        // UPDATED: Get current state including kiosk information
        getState() {
            return {
                isFullscreen: this.isFullscreen,
                isCameraActive: this.isCameraActive,
                isKioskVisible: this.isKioskVisible, // NEW: Include kiosk state
                bannerVisible: this.banner && this.banner.style.display !== 'none',
                bannerVisibility: this.banner ? this.banner.style.visibility : 'no banner',
                settings: this.settings,
                videoElement: this.videoElement ? {
                    hasStream: this.videoElement.srcObject !== null,
                    isVisible: this.videoElement.style.display !== 'none',
                    playing: !this.videoElement.paused && !this.videoElement.ended
                } : null,
                kioskElement: this.kioskElement ? { // NEW: Kiosk element info
                    exists: true,
                    isVisible: this.isKioskVisible,
                    display: this.kioskElement.style.display,
                    visibility: this.kioskElement.style.visibility,
                    dimensions: this.kioskElement.getBoundingClientRect()
                } : { exists: false }
            };
        }
    }
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        // Small delay to ensure other scripts have loaded
        setTimeout(() => {
            window.ageEstimatorBannerAd = new FullscreenBannerAd();
            
            // Debug helper - accessible in browser console
            window.debugBannerAd = () => {
                if (window.ageEstimatorBannerAd) {
                    console.log('Banner Ad Debug (Kiosk-Aware):', window.ageEstimatorBannerAd.getState());
                    return window.ageEstimatorBannerAd.forceCameraCheck();
                }
                return 'Banner Ad not initialized';
            };
            
            // NEW: Debug kiosk state specifically
            window.debugKioskState = () => {
                if (window.ageEstimatorBannerAd) {
                    const state = window.ageEstimatorBannerAd.getState();
                    console.log('Kiosk State Debug:', {
                        isKioskVisible: state.isKioskVisible,
                        kioskElement: state.kioskElement,
                        shouldHideBanner: state.isKioskVisible ? 'Yes - Banner should be hidden' : 'No - Kiosk not blocking'
                    });
                    return state.kioskElement;
                }
                return 'Banner Ad not initialized';
            };
            
            // Force show function for testing
            window.forceShowBanner = () => {
                if (window.ageEstimatorBannerAd) {
                    window.ageEstimatorBannerAd.setVisible(true);
                    console.log('Banner force shown for testing');
                }
            };
            
            // Force hide function for testing  
            window.forceHideBanner = () => {
                if (window.ageEstimatorBannerAd) {
                    window.ageEstimatorBannerAd.setVisible(false);
                    console.log('Banner force hidden');
                }
            };
            
            // NEW: Test kiosk integration
            window.testKioskIntegration = () => {
                if (window.ageEstimatorBannerAd) {
                    console.log('Testing kiosk integration...');
                    const state = window.ageEstimatorBannerAd.getState();
                    
                    console.log('Current State:', {
                        fullscreen: state.isFullscreen,
                        camera: state.isCameraActive,
                        kiosk: state.isKioskVisible,
                        banner: state.bannerVisible
                    });
                    
                    console.log('Expected Behavior:');
                    console.log('- Banner should show ONLY when: Fullscreen=true AND Camera=true AND Kiosk=false');
                    console.log('- Banner should hide when: Kiosk becomes visible');
                    
                    return state;
                }
                return 'Banner Ad not initialized';
            };
        }, 100);
    });
    
    // Also initialize on window load as fallback
    $(window).on('load', function() {
        if (!window.ageEstimatorBannerAd) {
            window.ageEstimatorBannerAd = new FullscreenBannerAd();
        }
    });
    
})(jQuery);
