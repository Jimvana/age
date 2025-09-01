/**
 * Canvas-Based Banner Ad Handler
 * Age Estimator Live Plugin
 * Canvas overlay version for better camera integration
 */

(function($) {
    'use strict';
    
    class CanvasBannerAd {
        constructor() {
            this.bannerCanvas = null;
            this.bannerCtx = null;
            this.container = null;
            this.cameraCanvas = null;
            this.videoElement = null;
            this.bannerImage = new Image();
            this.isFullscreen = false;
            this.isCameraActive = false;
            this.bannerLoaded = false;
            this.animationFrame = null;
            
            this.settings = {
                enabled: false,
                height: 100,
                position: 'bottom', // 'top' or 'bottom'
                opacity: 0.9,
                image: '',
                link: '',
                padding: 10
            };
            
            this.init();
        }
        
        init() {
            this.container = document.querySelector('.age-estimator-photo-container');
            this.videoElement = document.getElementById('age-estimator-photo-video');
            this.cameraCanvas = document.getElementById('age-estimator-photo-overlay');
            
            if (!this.container) {
                console.log('Canvas Banner Ad: Container not found');
                return;
            }
            
            // Load settings from existing banner element
            this.loadSettings();
            
            if (!this.settings.enabled) {
                console.log('Canvas Banner Ad: Feature disabled');
                return;
            }
            
            // Create canvas overlay
            this.createBannerCanvas();
            
            // Load banner image
            this.loadBannerImage();
            
            // Setup event listeners
            this.setupEventListeners();
            
            console.log('Canvas Banner Ad: Initialized successfully', this.settings);
        }
        
        loadSettings() {
            // Check for existing banner configuration
            const existingBanner = document.getElementById('age-estimator-banner-ad');
            
            if (!existingBanner) {
                this.settings.enabled = false;
                return;
            }
            
            const bannerImage = existingBanner.querySelector('.age-estimator-banner-image');
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
            const bannerLink = existingBanner.querySelector('.age-estimator-banner-link');
            this.settings.link = bannerLink ? bannerLink.href : '';
            
            // Hide original banner element
            existingBanner.style.display = 'none';
        }
        
        createBannerCanvas() {
            // Create canvas element for banner overlay
            this.bannerCanvas = document.createElement('canvas');
            this.bannerCanvas.id = 'age-estimator-banner-canvas';
            this.bannerCanvas.style.position = 'absolute';
            this.bannerCanvas.style.top = '0';
            this.bannerCanvas.style.left = '0';
            this.bannerCanvas.style.zIndex = '10000';
            this.bannerCanvas.style.pointerEvents = 'none';
            this.bannerCanvas.style.display = 'none';
            
            this.bannerCtx = this.bannerCanvas.getContext('2d');
            
            // Add canvas to camera container
            const cameraContainer = this.container.querySelector('#age-estimator-photo-camera');
            if (cameraContainer) {
                cameraContainer.style.position = 'relative';
                cameraContainer.appendChild(this.bannerCanvas);
            }
            
            // Setup click handling if there's a link
            if (this.settings.link) {
                this.bannerCanvas.style.pointerEvents = 'auto';
                this.bannerCanvas.style.cursor = 'pointer';
                this.bannerCanvas.classList.add('clickable');
                this.bannerCanvas.addEventListener('click', (e) => {
                    this.handleBannerClick(e);
                });
            }
        }
        
        loadBannerImage() {
            if (!this.settings.image) {
                console.log('Canvas Banner Ad: No image specified');
                return;
            }
            
            this.bannerImage.onload = () => {
                this.bannerLoaded = true;
                console.log('Canvas Banner Ad: Image loaded successfully');
                this.updateCanvasSize();
                this.redrawBanner();
            };
            
            this.bannerImage.onerror = () => {
                console.error('Canvas Banner Ad: Failed to load banner image:', this.settings.image);
                this.bannerLoaded = false;
            };
            
            this.bannerImage.crossOrigin = 'anonymous'; // Enable CORS if needed
            this.bannerImage.src = this.settings.image;
        }
        
        setupEventListeners() {
            // Fullscreen change listeners
            document.addEventListener('fullscreenchange', () => this.handleFullscreenChange());
            document.addEventListener('webkitfullscreenchange', () => this.handleFullscreenChange());
            document.addEventListener('mozfullscreenchange', () => this.handleFullscreenChange());
            document.addEventListener('MSFullscreenChange', () => this.handleFullscreenChange());
            
            // Window resize listener
            window.addEventListener('resize', () => this.updateCanvasSize());
            
            // Video element listeners
            if (this.videoElement) {
                this.videoElement.addEventListener('loadedmetadata', () => this.updateCanvasSize());
                this.videoElement.addEventListener('resize', () => this.updateCanvasSize());
            }
            
            // Camera state monitoring
            this.startCameraMonitoring();
        }
        
        startCameraMonitoring() {
            // Monitor camera state changes
            setInterval(() => {
                this.checkCameraState();
            }, 500);
            
            // Initial check
            this.checkCameraState();
        }
        
        checkCameraState() {
            let newCameraState = false;
            
            // Check video element
            if (this.videoElement) {
                const hasStream = this.videoElement.srcObject !== null;
                const isVisible = this.videoElement.style.display !== 'none';
                const isPlaying = !this.videoElement.paused && !this.videoElement.ended;
                newCameraState = hasStream && isVisible && isPlaying;
            }
            
            // Check UI indicators
            if (!newCameraState) {
                const stopButton = document.getElementById('age-estimator-photo-stop-camera');
                if (stopButton && stopButton.style.display !== 'none') {
                    newCameraState = true;
                }
            }
            
            // Update state if changed
            if (newCameraState !== this.isCameraActive) {
                this.isCameraActive = newCameraState;
                console.log('Canvas Banner Ad: Camera state changed to:', this.isCameraActive ? 'Active' : 'Inactive');
                this.updateBannerVisibility();
            }
        }
        
        handleFullscreenChange() {
            const isNowFullscreen = !!(document.fullscreenElement || 
                                     document.webkitFullscreenElement || 
                                     document.mozFullScreenElement || 
                                     document.msFullscreenElement);
            
            console.log('Canvas Banner Ad: Fullscreen state changed to:', isNowFullscreen);
            
            if (isNowFullscreen !== this.isFullscreen) {
                this.isFullscreen = isNowFullscreen;
                
                // Update canvas size for new fullscreen state
                setTimeout(() => {
                    this.updateCanvasSize();
                    this.updateBannerVisibility();
                }, 100);
            }
        }
        
        updateCanvasSize() {
            if (!this.bannerCanvas || !this.videoElement) return;
            
            const video = this.videoElement;
            const rect = video.getBoundingClientRect();
            
            if (rect.width === 0 || rect.height === 0) {
                // Video not yet sized, use container dimensions
                const container = this.container.querySelector('#age-estimator-photo-camera');
                if (container) {
                    const containerRect = container.getBoundingClientRect();
                    this.bannerCanvas.width = containerRect.width;
                    this.bannerCanvas.height = containerRect.height;
                } else {
                    this.bannerCanvas.width = 640;
                    this.bannerCanvas.height = 480;
                }
            } else {
                // Match video dimensions
                this.bannerCanvas.width = rect.width;
                this.bannerCanvas.height = rect.height;
            }
            
            // Position canvas to match video position
            this.bannerCanvas.style.width = this.bannerCanvas.width + 'px';
            this.bannerCanvas.style.height = this.bannerCanvas.height + 'px';
            
            console.log('Canvas Banner Ad: Canvas resized to', this.bannerCanvas.width, 'x', this.bannerCanvas.height);
            
            // Redraw banner after resize
            if (this.shouldShowBanner()) {
                this.redrawBanner();
            }
        }
        
        updateBannerVisibility() {
            const shouldShow = this.shouldShowBanner();
            
            console.log('Canvas Banner Ad: Evaluating visibility:', {
                isFullscreen: this.isFullscreen,
                isCameraActive: this.isCameraActive,
                enabled: this.settings.enabled,
                bannerLoaded: this.bannerLoaded,
                shouldShow: shouldShow
            });
            
            if (shouldShow) {
                this.showBanner();
            } else {
                this.hideBanner();
            }
        }
        
        shouldShowBanner() {
            return this.isFullscreen && 
                   this.isCameraActive && 
                   this.settings.enabled && 
                   this.bannerLoaded;
        }
        
        showBanner() {
            if (!this.bannerCanvas) return;
            
            console.log('Canvas Banner Ad: Showing banner overlay');
            
            this.bannerCanvas.style.display = 'block';
            this.redrawBanner();
            
            // Start animation loop if needed
            if (!this.animationFrame) {
                this.startAnimationLoop();
            }
            
            // Trigger custom event
            document.dispatchEvent(new CustomEvent('age_estimator_canvas_banner_show', {
                detail: {
                    position: this.settings.position,
                    height: this.settings.height,
                    opacity: this.settings.opacity,
                    cameraActive: this.isCameraActive,
                    fullscreen: this.isFullscreen
                }
            }));
        }
        
        hideBanner() {
            if (!this.bannerCanvas) return;
            
            console.log('Canvas Banner Ad: Hiding banner overlay');
            
            this.bannerCanvas.style.display = 'none';
            this.clearCanvas();
            
            // Stop animation loop
            if (this.animationFrame) {
                cancelAnimationFrame(this.animationFrame);
                this.animationFrame = null;
            }
            
            // Trigger custom event
            document.dispatchEvent(new CustomEvent('age_estimator_canvas_banner_hide', {
                detail: {
                    reason: !this.isFullscreen ? 'fullscreen_exit' : 'camera_inactive'
                }
            }));
        }
        
        redrawBanner() {
            if (!this.bannerCtx || !this.bannerLoaded) return;
            
            const canvas = this.bannerCanvas;
            const ctx = this.bannerCtx;
            
            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Calculate banner dimensions
            const bannerHeight = this.settings.height;
            const bannerWidth = canvas.width;
            let bannerY;
            
            if (this.settings.position === 'top') {
                bannerY = this.settings.padding;
            } else {
                bannerY = canvas.height - bannerHeight - this.settings.padding;
            }
            
            // Draw semi-transparent background
            ctx.fillStyle = `rgba(0, 0, 0, ${0.3 * this.settings.opacity})`;
            ctx.fillRect(0, bannerY, bannerWidth, bannerHeight);
            
            // Draw banner image
            ctx.globalAlpha = this.settings.opacity;
            
            // Calculate image scaling to fit banner area
            const imageAspect = this.bannerImage.width / this.bannerImage.height;
            const bannerAspect = bannerWidth / bannerHeight;
            
            let drawWidth, drawHeight, drawX, drawY;
            
            if (imageAspect > bannerAspect) {
                // Image is wider, fit to width
                drawWidth = bannerWidth - (this.settings.padding * 2);
                drawHeight = drawWidth / imageAspect;
                drawX = this.settings.padding;
                drawY = bannerY + (bannerHeight - drawHeight) / 2;
            } else {
                // Image is taller, fit to height
                drawHeight = bannerHeight - (this.settings.padding * 2);
                drawWidth = drawHeight * imageAspect;
                drawX = (bannerWidth - drawWidth) / 2;
                drawY = bannerY + this.settings.padding;
            }
            
            // Draw the image
            ctx.drawImage(this.bannerImage, drawX, drawY, drawWidth, drawHeight);
            
            // Add border effect
            ctx.globalAlpha = this.settings.opacity * 0.8;
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 2;
            ctx.strokeRect(drawX, drawY, drawWidth, drawHeight);
            
            // Reset alpha
            ctx.globalAlpha = 1;
            
            // Store click area for link handling
            this.clickArea = {
                x: drawX,
                y: drawY,
                width: drawWidth,
                height: drawHeight
            };
        }
        
        clearCanvas() {
            if (this.bannerCtx) {
                this.bannerCtx.clearRect(0, 0, this.bannerCanvas.width, this.bannerCanvas.height);
            }
        }
        
        startAnimationLoop() {
            const animate = () => {
                if (this.shouldShowBanner()) {
                    this.redrawBanner();
                    this.animationFrame = requestAnimationFrame(animate);
                } else {
                    this.animationFrame = null;
                }
            };
            
            this.animationFrame = requestAnimationFrame(animate);
        }
        
        handleBannerClick(event) {
            if (!this.settings.link || !this.clickArea) return;
            
            const rect = this.bannerCanvas.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            
            // Check if click is within banner image area
            if (x >= this.clickArea.x && x <= this.clickArea.x + this.clickArea.width &&
                y >= this.clickArea.y && y <= this.clickArea.y + this.clickArea.height) {
                
                console.log('Canvas Banner Ad: Banner clicked, opening link:', this.settings.link);
                window.open(this.settings.link, '_blank', 'noopener,noreferrer');
                
                // Track click event
                this.trackBannerEvent('click');
            }
        }
        
        trackBannerEvent(action) {
            // Track banner events for analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', 'canvas_banner_' + action, {
                    'event_category': 'Age Estimator',
                    'event_label': 'Canvas Banner',
                    'value': 1
                });
            }
            
            console.log(`Canvas Banner Ad: Event tracked - ${action}`);
        }
        
        // Public API methods
        updateSettings(newSettings) {
            this.settings = { ...this.settings, ...newSettings };
            
            if (newSettings.image && newSettings.image !== this.bannerImage.src) {
                this.loadBannerImage();
            } else {
                this.updateBannerVisibility();
            }
            
            console.log('Canvas Banner Ad: Settings updated', this.settings);
        }
        
        setVisible(visible) {
            if (visible) {
                this.showBanner();
            } else {
                this.hideBanner();
            }
        }
        
        getState() {
            return {
                isFullscreen: this.isFullscreen,
                isCameraActive: this.isCameraActive,
                bannerVisible: this.bannerCanvas && this.bannerCanvas.style.display !== 'none',
                bannerLoaded: this.bannerLoaded,
                settings: this.settings,
                canvasSize: this.bannerCanvas ? {
                    width: this.bannerCanvas.width,
                    height: this.bannerCanvas.height
                } : null,
                videoElement: this.videoElement ? {
                    hasStream: this.videoElement.srcObject !== null,
                    isVisible: this.videoElement.style.display !== 'none',
                    playing: !this.videoElement.paused && !this.videoElement.ended
                } : null
            };
        }
        
        destroy() {
            // Clean up
            if (this.animationFrame) {
                cancelAnimationFrame(this.animationFrame);
            }
            
            if (this.bannerCanvas && this.bannerCanvas.parentNode) {
                this.bannerCanvas.parentNode.removeChild(this.bannerCanvas);
            }
            
            console.log('Canvas Banner Ad: Destroyed');
        }
    }
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        setTimeout(() => {
            // Destroy any existing banner ad
            if (window.ageEstimatorBannerAd && typeof window.ageEstimatorBannerAd.destroy === 'function') {
                window.ageEstimatorBannerAd.destroy();
            }
            
            // Initialize canvas-based banner
            window.ageEstimatorCanvasBanner = new CanvasBannerAd();
            
            // Debug helpers
            window.debugCanvasBanner = () => {
                if (window.ageEstimatorCanvasBanner) {
                    console.log('Canvas Banner Debug:', window.ageEstimatorCanvasBanner.getState());
                    return window.ageEstimatorCanvasBanner.getState();
                }
                return 'Canvas Banner not initialized';
            };
            
            window.forceShowCanvasBanner = () => {
                if (window.ageEstimatorCanvasBanner) {
                    window.ageEstimatorCanvasBanner.setVisible(true);
                    console.log('Canvas Banner force shown');
                }
            };
            
            window.forceHideCanvasBanner = () => {
                if (window.ageEstimatorCanvasBanner) {
                    window.ageEstimatorCanvasBanner.setVisible(false);
                    console.log('Canvas Banner force hidden');
                }
            };
            
        }, 100);
    });
    
})(jQuery);