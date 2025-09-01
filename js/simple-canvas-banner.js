/**
 * Simplified Canvas Banner Ad - Display Only
 * Age Estimator Live Plugin
 * Simplified version that focuses on display without click functionality
 */

(function($) {
    'use strict';
    
    class SimpleCanvasBanner {
        constructor() {
            this.bannerCanvas = null;
            this.bannerCtx = null;
            this.container = null;
            this.videoElement = null;
            this.bannerImage = new Image();
            this.isFullscreen = false;
            this.isCameraActive = false;
            this.bannerLoaded = false;
            
            // Simplified settings with defaults
            this.settings = {
                enabled: true, // Force enabled for testing
                height: 100,
                position: 'bottom',
                opacity: 0.9,
                image: '', // Will be loaded from WordPress settings
                padding: 10
            };
            
            this.init();
        }
        
        init() {
            console.log('🎨 Simple Canvas Banner: Starting initialization...');
            
            this.container = document.querySelector('.age-estimator-photo-container');
            this.videoElement = document.getElementById('age-estimator-photo-video');
            
            if (!this.container) {
                console.error('❌ Canvas Banner: Container not found');
                return;
            }
            
            console.log('✅ Canvas Banner: Container found');
            
            // Load banner settings
            this.loadBannerSettings();
            
            // Create canvas immediately
            this.createBannerCanvas();
            
            // Load banner image if available
            if (this.settings.image) {
                this.loadBannerImage();
            } else {
                console.log('⚠️ Canvas Banner: No image configured, creating test banner');
                this.createTestBanner();
            }
            
            // Setup event listeners
            this.setupEventListeners();
            
            console.log('🎨 Simple Canvas Banner: Initialized', this.settings);
        }
        
        loadBannerSettings() {
            // Try to load from existing banner configuration
            const existingBanner = document.getElementById('age-estimator-banner-ad');
            
            if (existingBanner) {
                const bannerImage = existingBanner.querySelector('.age-estimator-banner-image');
                if (bannerImage) {
                    this.settings.height = parseInt(bannerImage.dataset.height) || 100;
                    this.settings.position = bannerImage.dataset.position || 'bottom';
                    this.settings.opacity = parseFloat(bannerImage.dataset.opacity) || 0.9;
                    this.settings.image = bannerImage.src || '';
                    
                    console.log('✅ Canvas Banner: Loaded settings from existing banner');
                    console.log('🖼️ Canvas Banner: Image URL:', this.settings.image);
                    
                    // Hide original banner
                    existingBanner.style.display = 'none';
                } else {
                    console.log('⚠️ Canvas Banner: No banner image found in existing banner');
                    // Try to get settings directly from WordPress options if available
                    this.loadFromWordPressGlobals();
                }
            } else {
                console.log('⚠️ Canvas Banner: No existing banner found');
                // Try to get settings directly from WordPress options if available
                this.loadFromWordPressGlobals();
            }
        }
        
        loadFromWordPressGlobals() {
            // Try to get banner settings from WordPress localized script data
            if (typeof ageEstimatorParams !== 'undefined') {
                console.log('📋 Canvas Banner: Checking WordPress params...');
                // Check if banner settings are available in params
                if (ageEstimatorParams.bannerEnabled) {
                    this.settings.enabled = true;
                    this.settings.image = ageEstimatorParams.bannerImage || '';
                    this.settings.height = ageEstimatorParams.bannerHeight || 100;
                    this.settings.position = ageEstimatorParams.bannerPosition || 'bottom';
                    this.settings.opacity = ageEstimatorParams.bannerOpacity || 0.9;
                    console.log('✅ Canvas Banner: Loaded settings from WordPress params');
                    return;
                }
            }
            
            // As a last resort, try to find settings in page content
            const metaTag = document.querySelector('meta[name="age-estimator-banner"]');
            if (metaTag) {
                try {
                    const settings = JSON.parse(metaTag.content);
                    Object.assign(this.settings, settings);
                    console.log('✅ Canvas Banner: Loaded settings from meta tag');
                    return;
                } catch (e) {
                    console.log('⚠️ Canvas Banner: Failed to parse meta tag settings');
                }
            }
            
            console.log('📋 Canvas Banner: No WordPress settings found, using test banner');
        }
        
        createBannerCanvas() {
            console.log('🖼️ Canvas Banner: Creating canvas element...');
            
            // Create canvas element
            this.bannerCanvas = document.createElement('canvas');
            this.bannerCanvas.id = 'age-estimator-banner-canvas';
            this.bannerCanvas.style.cssText = `
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                z-index: 50000 !important;
                pointer-events: none !important;
                display: none !important;
                border: 2px solid red !important;
            `; // Red border for debugging
            
            this.bannerCtx = this.bannerCanvas.getContext('2d');
            
            // Find camera container
            const cameraContainer = this.container.querySelector('#age-estimator-photo-camera');
            if (cameraContainer) {
                cameraContainer.style.position = 'relative';
                cameraContainer.appendChild(this.bannerCanvas);
                console.log('✅ Canvas Banner: Canvas added to camera container');
            } else {
                console.error('❌ Canvas Banner: Camera container not found');
                return;
            }
            
            // Set initial canvas size
            this.updateCanvasSize();
        }
        
        loadBannerImage() {
            if (!this.settings.image) {
                console.log('⚠️ Canvas Banner: No image URL provided, creating test banner');
                this.createTestBanner();
                return;
            }
            
            console.log('🖼️ Canvas Banner: Loading image from:', this.settings.image);
            
            this.bannerImage.onload = () => {
                this.bannerLoaded = true;
                console.log('✅ Canvas Banner: Image loaded successfully!');
                console.log('🖼️ Canvas Banner: Image dimensions:', this.bannerImage.width, 'x', this.bannerImage.height);
                this.updateBannerVisibility();
            };
            
            this.bannerImage.onerror = (error) => {
                console.error('❌ Canvas Banner: Failed to load image:', this.settings.image);
                console.error('❌ Canvas Banner: Error details:', error);
                
                // Try loading without CORS first
                if (this.bannerImage.crossOrigin) {
                    console.log('🔄 Canvas Banner: Retrying without CORS...');
                    this.bannerImage.crossOrigin = null;
                    this.bannerImage.src = this.settings.image;
                    return;
                }
                
                // If still fails, try with cache busting
                if (!this.settings.image.includes('?')) {
                    console.log('🔄 Canvas Banner: Retrying with cache busting...');
                    this.bannerImage.src = this.settings.image + '?cb=' + Date.now();
                    return;
                }
                
                console.log('🔄 Canvas Banner: All retries failed, falling back to test banner');
                this.createTestBanner();
            };
            
            // Try with CORS first, will retry without if it fails
            this.bannerImage.crossOrigin = 'anonymous';
            this.bannerImage.src = this.settings.image;
        }
        
        createTestBanner() {
            console.log('🧪 Canvas Banner: Creating test banner...');
            
            // Create a simple test banner
            this.bannerLoaded = true;
            this.bannerImage = null; // Clear image to use text banner
            this.updateBannerVisibility();
        }
        
        setupEventListeners() {
            console.log('👂 Canvas Banner: Setting up event listeners...');
            
            // Fullscreen change listeners
            document.addEventListener('fullscreenchange', () => this.handleFullscreenChange());
            document.addEventListener('webkitfullscreenchange', () => this.handleFullscreenChange());
            document.addEventListener('mozfullscreenchange', () => this.handleFullscreenChange());
            document.addEventListener('MSFullscreenChange', () => this.handleFullscreenChange());
            
            // Window resize
            window.addEventListener('resize', () => {
                setTimeout(() => this.updateCanvasSize(), 100);
            });
            
            // Start monitoring
            this.startMonitoring();
        }
        
        startMonitoring() {
            console.log('🔍 Canvas Banner: Starting monitoring...');
            
            setInterval(() => {
                this.checkStates();
            }, 500);
            
            // Initial check
            this.checkStates();
        }
        
        checkStates() {
            // Check camera state
            let newCameraState = false;
            
            if (this.videoElement) {
                const hasStream = this.videoElement.srcObject !== null;
                const isVisible = this.videoElement.style.display !== 'none';
                const isPlaying = !this.videoElement.paused && !this.videoElement.ended;
                newCameraState = hasStream && isVisible;
            }
            
            // Check UI buttons as fallback
            if (!newCameraState) {
                const stopButton = document.getElementById('age-estimator-photo-stop-camera');
                if (stopButton && stopButton.style.display !== 'none') {
                    newCameraState = true;
                }
            }
            
            // Check fullscreen state
            const newFullscreenState = !!(document.fullscreenElement || 
                                        document.webkitFullscreenElement || 
                                        document.mozFullScreenElement || 
                                        document.msFullscreenElement);
            
            // Update states if changed
            if (newCameraState !== this.isCameraActive) {
                this.isCameraActive = newCameraState;
                console.log('📹 Canvas Banner: Camera state changed to:', this.isCameraActive ? 'ACTIVE' : 'INACTIVE');
                this.updateBannerVisibility();
            }
            
            if (newFullscreenState !== this.isFullscreen) {
                this.isFullscreen = newFullscreenState;
                console.log('🖥️ Canvas Banner: Fullscreen state changed to:', this.isFullscreen ? 'ON' : 'OFF');
                setTimeout(() => {
                    this.updateCanvasSize();
                    this.updateBannerVisibility();
                }, 100);
            }
        }
        
        handleFullscreenChange() {
            // Handled in checkStates for consistency
        }
        
        updateCanvasSize() {
            if (!this.bannerCanvas) return;
            
            let width = 640;
            let height = 480;
            
            // Try to get video dimensions
            if (this.videoElement) {
                const rect = this.videoElement.getBoundingClientRect();
                if (rect.width > 0 && rect.height > 0) {
                    width = rect.width;
                    height = rect.height;
                    console.log('📐 Canvas Banner: Using video dimensions:', width, 'x', height);
                }
            }
            
            // Fallback to camera container dimensions
            if (width === 640 && height === 480) {
                const cameraContainer = this.container.querySelector('#age-estimator-photo-camera');
                if (cameraContainer) {
                    const rect = cameraContainer.getBoundingClientRect();
                    if (rect.width > 0 && rect.height > 0) {
                        width = rect.width;
                        height = rect.height;
                        console.log('📐 Canvas Banner: Using container dimensions:', width, 'x', height);
                    }
                }
            }
            
            // Fullscreen dimensions
            if (this.isFullscreen) {
                width = window.innerWidth;
                height = window.innerHeight;
                console.log('📐 Canvas Banner: Using fullscreen dimensions:', width, 'x', height);
            }
            
            // Set canvas size
            this.bannerCanvas.width = width;
            this.bannerCanvas.height = height;
            this.bannerCanvas.style.width = width + 'px';
            this.bannerCanvas.style.height = height + 'px';
            
            console.log('📐 Canvas Banner: Canvas sized to:', width, 'x', height);
            
            // Redraw if banner should be visible
            if (this.shouldShowBanner()) {
                this.drawBanner();
            }
        }
        
        updateBannerVisibility() {
            const shouldShow = this.shouldShowBanner();
            
            console.log('👁️ Canvas Banner: Visibility check:', {
                fullscreen: this.isFullscreen,
                camera: this.isCameraActive,
                loaded: this.bannerLoaded,
                enabled: this.settings.enabled,
                shouldShow: shouldShow
            });
            
            if (shouldShow) {
                this.showBanner();
            } else {
                this.hideBanner();
            }
        }
        
        shouldShowBanner() {
            return this.isFullscreen && this.isCameraActive && this.bannerLoaded && this.settings.enabled;
        }
        
        showBanner() {
            if (!this.bannerCanvas) return;
            
            console.log('🎯 Canvas Banner: SHOWING BANNER');
            
            this.bannerCanvas.style.display = 'block';
            this.drawBanner();
            
            // Update status indicator
            const statusEl = document.getElementById('canvas-banner-status');
            if (statusEl) {
                statusEl.textContent = 'Canvas Banner: VISIBLE';
                statusEl.style.color = 'green';
            }
        }
        
        hideBanner() {
            if (!this.bannerCanvas) return;
            
            console.log('🚫 Canvas Banner: HIDING BANNER');
            
            this.bannerCanvas.style.display = 'none';
            this.clearCanvas();
            
            // Update status indicator
            const statusEl = document.getElementById('canvas-banner-status');
            if (statusEl) {
                statusEl.textContent = 'Canvas Banner: HIDDEN';
                statusEl.style.color = 'red';
            }
        }
        
        drawBanner() {
            if (!this.bannerCtx) return;
            
            const canvas = this.bannerCanvas;
            const ctx = this.bannerCtx;
            
            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Calculate banner area
            const bannerHeight = this.settings.height;
            const bannerWidth = canvas.width;
            let bannerY;
            
            if (this.settings.position === 'top') {
                bannerY = this.settings.padding;
            } else {
                bannerY = canvas.height - bannerHeight - this.settings.padding;
            }
            
            // Draw background
            ctx.fillStyle = `rgba(0, 0, 0, ${0.5 * this.settings.opacity})`;
            ctx.fillRect(0, bannerY, bannerWidth, bannerHeight);
            
            // Draw border
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 3;
            ctx.strokeRect(0, bannerY, bannerWidth, bannerHeight);
            
            // Check if we have a real image loaded (not null and has src)
            if (this.bannerImage && this.bannerImage.src && this.bannerImage.complete && this.bannerImage.naturalWidth > 0) {
                // Draw image banner
                console.log('🎨 Canvas Banner: Drawing image banner');
                this.drawImageBanner(ctx, bannerY, bannerWidth, bannerHeight);
            } else {
                // Draw text banner
                console.log('🎨 Canvas Banner: Drawing test text banner');
                this.drawTextBanner(ctx, bannerY, bannerWidth, bannerHeight);
            }
            
            console.log('🎨 Canvas Banner: Banner drawn at Y:', bannerY, 'Height:', bannerHeight);
        }
        
        drawImageBanner(ctx, bannerY, bannerWidth, bannerHeight) {
            const imageAspect = this.bannerImage.width / this.bannerImage.height;
            const bannerAspect = bannerWidth / bannerHeight;
            
            let drawWidth, drawHeight, drawX, drawY;
            
            if (imageAspect > bannerAspect) {
                drawWidth = bannerWidth - (this.settings.padding * 2);
                drawHeight = drawWidth / imageAspect;
                drawX = this.settings.padding;
                drawY = bannerY + (bannerHeight - drawHeight) / 2;
            } else {
                drawHeight = bannerHeight - (this.settings.padding * 2);
                drawWidth = drawHeight * imageAspect;
                drawX = (bannerWidth - drawWidth) / 2;
                drawY = bannerY + this.settings.padding;
            }
            
            ctx.globalAlpha = this.settings.opacity;
            ctx.drawImage(this.bannerImage, drawX, drawY, drawWidth, drawHeight);
            ctx.globalAlpha = 1;
        }
        
        drawTextBanner(ctx, bannerY, bannerWidth, bannerHeight) {
            // Draw test text banner
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 24px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            
            const centerX = bannerWidth / 2;
            const centerY = bannerY + (bannerHeight / 2);
            
            ctx.fillText('🎯 CANVAS BANNER TEST', centerX, centerY - 10);
            ctx.font = '16px Arial';
            ctx.fillText('Banner is working!', centerX, centerY + 15);
        }
        
        clearCanvas() {
            if (this.bannerCtx && this.bannerCanvas) {
                this.bannerCtx.clearRect(0, 0, this.bannerCanvas.width, this.bannerCanvas.height);
            }
        }
        
        // Public API
        getState() {
            return {
                isFullscreen: this.isFullscreen,
                isCameraActive: this.isCameraActive,
                bannerVisible: this.bannerCanvas && this.bannerCanvas.style.display !== 'none',
                bannerLoaded: this.bannerLoaded,
                settings: this.settings,
                bannerImage: this.bannerImage ? {
                    src: this.bannerImage.src,
                    width: this.bannerImage.width,
                    height: this.bannerImage.height,
                    complete: this.bannerImage.complete,
                    naturalWidth: this.bannerImage.naturalWidth,
                    naturalHeight: this.bannerImage.naturalHeight
                } : null,
                canvasSize: this.bannerCanvas ? {
                    width: this.bannerCanvas.width,
                    height: this.bannerCanvas.height
                } : null
            };
        }
        
        setVisible(visible) {
            if (visible) {
                this.showBanner();
            } else {
                this.hideBanner();
            }
        }
        
        forceTest() {
            console.log('🧪 Canvas Banner: FORCE TEST MODE');
            this.bannerLoaded = true;
            this.settings.enabled = true;
            this.updateCanvasSize();
            this.showBanner();
        }
        
        forceLoadImage() {
            console.log('🔄 Canvas Banner: FORCE RELOAD IMAGE');
            
            // Clear current image
            this.bannerImage = new Image();
            this.bannerLoaded = false;
            
            // Reload settings and image
            this.loadBannerSettings();
            
            if (this.settings.image) {
                this.loadBannerImage();
            } else {
                console.log('⚠️ Canvas Banner: No image URL found after reload');
                this.createTestBanner();
            }
        }
    }
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        setTimeout(() => {
            console.log('🚀 Initializing Simple Canvas Banner...');
            
            // Initialize simple canvas banner
            window.simpleCanvasBanner = new SimpleCanvasBanner();
            
            // Debug helpers
            window.debugCanvasBanner = () => {
                if (window.simpleCanvasBanner) {
                    const state = window.simpleCanvasBanner.getState();
                    console.log('🔍 Canvas Banner Debug:', state);
                    return state;
                }
                return 'Canvas Banner not initialized';
            };
            
            window.forceShowCanvasBanner = () => {
                if (window.simpleCanvasBanner) {
                    window.simpleCanvasBanner.forceTest();
                    console.log('🎯 Canvas Banner: FORCED TO SHOW');
                }
            };
            
            window.forceHideCanvasBanner = () => {
                if (window.simpleCanvasBanner) {
                    window.simpleCanvasBanner.setVisible(false);
                    console.log('🚫 Canvas Banner: FORCED TO HIDE');
                }
            };
            
            window.reloadCanvasBanner = () => {
                if (window.simpleCanvasBanner) {
                    window.simpleCanvasBanner.forceLoadImage();
                    console.log('🔄 Canvas Banner: RELOADING IMAGE FROM SETTINGS');
                }
            };
            
        }, 500);
    });
    
})(jQuery);