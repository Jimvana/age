/**
 * Sound Notification Manager for Age Estimator
 * Handles preloading and playing of pass/fail sounds
 */

(function() {
    'use strict';
    
    // Sound manager object
    const AgeEstimatorSounds = {
        // Audio objects
        passSound: null,
        failSound: null,
        enabled: false,
        volume: 0.7,
        isIOS: /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream,
        audioUnlocked: false,
        
        // Initialize sounds
        init: function() {
            // Check if sounds are enabled
            if (typeof ageEstimatorPhotoParams === 'undefined') {
                console.log('Age Estimator Sounds: Parameters not available');
                return;
            }
            
            this.enabled = ageEstimatorPhotoParams.enableSounds === '1';
            
            if (!this.enabled) {
                console.log('Age Estimator Sounds: Sound notifications disabled');
                return;
            }
            
            console.log('Age Estimator Sounds: Initializing sound system');
            console.log('Age Estimator Sounds: iOS detected:', this.isIOS);
            
            // Get sound URLs
            const passSoundUrl = ageEstimatorPhotoParams.passSoundUrl;
            const failSoundUrl = ageEstimatorPhotoParams.failSoundUrl;
            this.volume = parseFloat(ageEstimatorPhotoParams.soundVolume) || 0.7;
            
            // Set up iOS audio unlock
            if (this.isIOS) {
                this.setupIOSAudioUnlock();
                // Show unlock prompt after a short delay
                setTimeout(() => {
                    this.showIOSUnlockPrompt();
                }, 2000);
            }
            
            // Preload sounds
            if (passSoundUrl) {
                this.preloadSound('pass', passSoundUrl);
            }
            
            if (failSoundUrl) {
                this.preloadSound('fail', failSoundUrl);
            }
        },
        
        // iOS-specific audio unlock setup
        setupIOSAudioUnlock: function() {
            console.log('Age Estimator Sounds: Setting up iOS audio unlock');
            
            // Try to unlock on any user interaction
            const unlockEvents = ['touchstart', 'touchend', 'mousedown', 'click'];
            
            const unlockHandler = () => {
                if (!this.audioUnlocked) {
                    this.unlockAudioContext();
                }
            };
            
            unlockEvents.forEach(event => {
                document.addEventListener(event, unlockHandler, { once: true });
            });
        },
        
        // Unlock audio context for iOS
        unlockAudioContext: function() {
            console.log('Age Estimator Sounds: Attempting to unlock audio context');
            
            // Method 1: Try Web Audio API first
            if (window.AudioContext || window.webkitAudioContext) {
                try {
                    const AudioContext = window.AudioContext || window.webkitAudioContext;
                    const audioContext = new AudioContext();
                    
                    // Create empty buffer and play it
                    const buffer = audioContext.createBuffer(1, 1, 22050);
                    const source = audioContext.createBufferSource();
                    source.buffer = buffer;
                    source.connect(audioContext.destination);
                    source.start(0);
                    
                    // Resume if suspended
                    if (audioContext.state === 'suspended') {
                        audioContext.resume().then(() => {
                            console.log('Age Estimator Sounds: Web Audio context resumed');
                        });
                    }
                    
                    console.log('Age Estimator Sounds: Web Audio unlock attempted');
                } catch (e) {
                    console.error('Age Estimator Sounds: Web Audio unlock failed:', e);
                }
            }
            
            // Method 2: HTML5 Audio with multiple attempts
            const attempts = [
                // Attempt 1: Silent WAV
                () => {
                    const audio = new Audio();
                    audio.src = 'data:audio/wav;base64,UklGRigAAABXQVZFZm10IBIAAAABAAEARKwAAIhYAQACABAAAABkYXRhAgAAAAEA';
                    audio.volume = 0.1;
                    return audio.play();
                },
                // Attempt 2: Silent MP3
                () => {
                    const audio = new Audio();
                    audio.src = 'data:audio/mp3;base64,SUQzBAAAAAAAI1RTU0UAAAAPAAADTGF2ZjU2LjM2LjEwMAAAAAAAAAAAAAAA//OEAAAAAAAAAAAAAAAAAAAAAAAASW5mbwAAAA8AAAAEAAABIADAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDV1dXV1dXV1dXV1dXV1dXV1dXV1dXV1dXV6urq6urq6urq6urq6urq6urq6urq6urq6v////////////////////////////////8AAAAATGF2YzU2LjQxAAAAAAAAAAAAAAAAJAAAAAAAAAAAASDs90hvAAAAAAAAAAAAAAAAAAAA//MUZAAAAAGkAAAAAAAAA0gAAAAATEFN//MUZAMAAAGkAAAAAAAAA0gAAAAARTMu//MUZAYAAAGkAAAAAAAAA0gAAAAAOTku//MUZAkAAAGkAAAAAAAAA0gAAAAANVVV';
                    audio.volume = 0.1;
                    return audio.play();
                },
                // Attempt 3: Play actual sounds at volume 0
                () => {
                    if (this.passSound) {
                        const tempVolume = this.passSound.volume;
                        this.passSound.volume = 0;
                        const promise = this.passSound.play();
                        this.passSound.pause();
                        this.passSound.currentTime = 0;
                        this.passSound.volume = tempVolume;
                        return promise;
                    }
                    return Promise.reject('No pass sound');
                }
            ];
            
            // Try each method
            attempts.forEach((attempt, index) => {
                try {
                    const promise = attempt();
                    if (promise && promise.then) {
                        promise
                            .then(() => {
                                console.log(`Age Estimator Sounds: Audio unlock method ${index + 1} succeeded`);
                                this.audioUnlocked = true;
                                
                                // Force reload sounds
                                this.reloadSounds();
                            })
                            .catch(error => {
                                console.error(`Age Estimator Sounds: Audio unlock method ${index + 1} failed:`, error);
                            });
                    }
                } catch (e) {
                    console.error(`Age Estimator Sounds: Audio unlock method ${index + 1} exception:`, e);
                }
            });
        },
        
        // Reload sounds after unlock
        reloadSounds: function() {
            console.log('Age Estimator Sounds: Reloading sounds...');
            
            if (this.passSound) {
                this.passSound.load();
                // Try to preplay at volume 0
                const originalVolume = this.passSound.volume;
                this.passSound.volume = 0;
                this.passSound.play().then(() => {
                    this.passSound.pause();
                    this.passSound.currentTime = 0;
                    this.passSound.volume = originalVolume;
                    console.log('Age Estimator Sounds: Pass sound preloaded');
                }).catch(e => console.error('Pass sound preload failed:', e));
            }
            
            if (this.failSound) {
                this.failSound.load();
                // Try to preplay at volume 0
                const originalVolume = this.failSound.volume;
                this.failSound.volume = 0;
                this.failSound.play().then(() => {
                    this.failSound.pause();
                    this.failSound.currentTime = 0;
                    this.failSound.volume = originalVolume;
                    console.log('Age Estimator Sounds: Fail sound preloaded');
                }).catch(e => console.error('Fail sound preload failed:', e));
            }
        },
        
        // Preload a sound
        preloadSound: function(type, url) {
            console.log(`Age Estimator Sounds: Preloading ${type} sound from ${url}`);
            
            const audio = new Audio();
            
            // iOS-specific attributes
            audio.preload = 'auto';
            audio.volume = this.volume;
            audio.muted = false;
            
            // Only set crossOrigin if the URL is from a different domain
            try {
                const audioUrl = new URL(url);
                const currentUrl = new URL(window.location.href);
                if (audioUrl.hostname !== currentUrl.hostname) {
                    audio.crossOrigin = 'anonymous';
                    console.log(`Age Estimator Sounds: Setting crossOrigin for external domain: ${audioUrl.hostname}`);
                }
            } catch (e) {
                // Invalid URL or relative path, don't set crossOrigin
                console.log('Age Estimator Sounds: Using relative/same-origin URL');
            }
            
            // For iOS, we need to set these attributes before setting src
            if (this.isIOS) {
                audio.setAttribute('playsinline', 'true');
                audio.setAttribute('webkit-playsinline', 'true');
            }
            
            // Set up event listeners
            audio.addEventListener('loadstart', () => {
                console.log(`Age Estimator Sounds: ${type} sound load started`);
            });
            
            audio.addEventListener('loadedmetadata', () => {
                console.log(`Age Estimator Sounds: ${type} sound metadata loaded, duration: ${audio.duration}`);
            });
            
            audio.addEventListener('canplay', () => {
                console.log(`Age Estimator Sounds: ${type} sound can start playing`);
            });
            
            audio.addEventListener('canplaythrough', () => {
                console.log(`Age Estimator Sounds: ${type} sound loaded and ready (can play through)`);
                console.log(`Ready state: ${audio.readyState}, Network state: ${audio.networkState}`);
            });
            
            audio.addEventListener('error', (e) => {
                console.error(`Age Estimator Sounds: Error loading ${type} sound:`, e);
                console.error('Audio error details:', {
                    error: audio.error,
                    code: audio.error ? audio.error.code : 'N/A',
                    message: audio.error ? audio.error.message : 'N/A',
                    networkState: audio.networkState,
                    readyState: audio.readyState,
                    src: audio.src
                });
            });
            
            // Set source and start loading
            audio.src = url;
            
            // For iOS, we might need to load in response to user interaction
            if (this.isIOS && !this.audioUnlocked) {
                console.log(`Age Estimator Sounds: iOS detected, deferring ${type} sound load until user interaction`);
                // Still set the source, but don't force load yet
            } else {
                audio.load();
            }
            
            // Store reference
            if (type === 'pass') {
                this.passSound = audio;
            } else if (type === 'fail') {
                this.failSound = audio;
            }
        },
        
        // Play pass sound
        playPassSound: function() {
            if (!this.enabled || !this.passSound) {
                console.log('Age Estimator Sounds: Pass sound not available');
                return;
            }
            
            console.log('Age Estimator Sounds: Playing pass sound');
            this.showSoundIndicator('pass');
            this.playSound(this.passSound);
        },
        
        // Play fail sound
        playFailSound: function() {
            if (!this.enabled || !this.failSound) {
                console.log('Age Estimator Sounds: Fail sound not available');
                return;
            }
            
            console.log('Age Estimator Sounds: Playing fail sound');
            this.showSoundIndicator('fail');
            this.playSound(this.failSound);
        },
        
        // Show visual sound indicator
        showSoundIndicator: function(type) {
            // Remove existing indicator
            const existing = document.querySelector('.age-estimator-sound-indicator');
            if (existing) {
                existing.remove();
            }
            
            // Create new indicator
            const indicator = document.createElement('div');
            indicator.className = `age-estimator-sound-indicator playing ${type}`;
            indicator.textContent = type === 'pass' ? '🔊 PASS' : '🔊 FAIL';
            document.body.appendChild(indicator);
            
            // Remove after animation
            setTimeout(() => {
                indicator.remove();
            }, 2000);
        },
        
        // Show iOS unlock prompt
        showIOSUnlockPrompt: function() {
            if (!this.isIOS || this.audioUnlocked) return;
            
            const prompt = document.createElement('div');
            prompt.className = 'ios-audio-unlock-prompt show';
            prompt.innerHTML = '🔊 Tap to enable sound notifications';
            
            prompt.addEventListener('click', () => {
                console.log('Age Estimator Sounds: iOS unlock prompt clicked');
                this.unlockAudioContext();
                
                // Immediately try to play sounds at volume 0 to prime them
                setTimeout(() => {
                    if (this.passSound && this.failSound) {
                        console.log('Age Estimator Sounds: Priming sounds after unlock...');
                        
                        // Store original volumes
                        const passVol = this.passSound.volume;
                        const failVol = this.failSound.volume;
                        
                        // Play at volume 0
                        this.passSound.volume = 0.01;
                        this.failSound.volume = 0.01;
                        
                        // Play both sounds
                        Promise.all([
                            this.passSound.play().catch(e => console.log('Pass prime failed:', e)),
                            this.failSound.play().catch(e => console.log('Fail prime failed:', e))
                        ]).then(() => {
                            console.log('Age Estimator Sounds: Sounds primed successfully');
                            // Stop and reset
                            this.passSound.pause();
                            this.passSound.currentTime = 0;
                            this.passSound.volume = passVol;
                            
                            this.failSound.pause();
                            this.failSound.currentTime = 0;
                            this.failSound.volume = failVol;
                            
                            this.audioUnlocked = true;
                        });
                    }
                }, 100);
                
                prompt.remove();
            });
            
            document.body.appendChild(prompt);
            
            // Auto-hide after 10 seconds
            setTimeout(() => {
                prompt.remove();
            }, 10000);
        },
        
        // Play a sound with error handling
        playSound: function(audio) {
            try {
                // For iOS, ensure audio is unlocked first
                if (this.isIOS && !this.audioUnlocked) {
                    console.log('Age Estimator Sounds: iOS audio not yet unlocked, attempting unlock');
                    this.unlockAudioContext();
                    
                    // Queue the sound to play after unlock
                    setTimeout(() => {
                        if (this.audioUnlocked) {
                            this.playSound(audio);
                        }
                    }, 500);
                    return;
                }
                
                console.log('Age Estimator Sounds: Attempting to play sound...');
                console.log('Audio state:', {
                    src: audio.src,
                    readyState: audio.readyState,
                    volume: audio.volume,
                    muted: audio.muted,
                    paused: audio.paused
                });
                
                // Method 1: Try direct play first
                const playDirectly = () => {
                    audio.currentTime = 0;
                    audio.volume = this.volume;
                    audio.muted = false;
                    return audio.play();
                };
                
                // Method 2: Clone and play (iOS workaround)
                const playClone = () => {
                    const clone = audio.cloneNode(true);
                    clone.volume = this.volume;
                    clone.muted = false;
                    
                    // Clean up clone after playing
                    clone.addEventListener('ended', () => {
                        clone.remove();
                    });
                    
                    document.body.appendChild(clone);
                    return clone.play();
                };
                
                // Method 3: Create new Audio instance
                const playNew = () => {
                    const newAudio = new Audio(audio.src);
                    newAudio.volume = this.volume;
                    newAudio.muted = false;
                    
                    return newAudio.play();
                };
                
                // Try methods in sequence
                const tryPlay = async () => {
                    const methods = [
                        { name: 'Direct', fn: playDirectly },
                        { name: 'Clone', fn: playClone },
                        { name: 'New Instance', fn: playNew }
                    ];
                    
                    for (const method of methods) {
                        try {
                            console.log(`Age Estimator Sounds: Trying ${method.name} method...`);
                            await method.fn();
                            console.log(`Age Estimator Sounds: ${method.name} method succeeded!`);
                            this.audioUnlocked = true;
                            return; // Success, exit
                        } catch (error) {
                            console.error(`Age Estimator Sounds: ${method.name} method failed:`, error.message);
                            
                            // If it's a not allowed error on iOS, show the prompt again
                            if (error.name === 'NotAllowedError' && this.isIOS) {
                                this.showIOSUnlockPrompt();
                            }
                        }
                    }
                    
                    console.error('Age Estimator Sounds: All play methods failed');
                };
                
                // Execute the play attempts
                tryPlay();
                
            } catch (error) {
                console.error('Age Estimator Sounds: Exception playing sound:', error);
            }
        },
        
        // Update volume
        setVolume: function(volume) {
            this.volume = Math.max(0, Math.min(1, volume));
            
            if (this.passSound) {
                this.passSound.volume = this.volume;
            }
            
            if (this.failSound) {
                this.failSound.volume = this.volume;
            }
        },
        
        // Check if sounds are ready
        isReady: function() {
            return this.enabled && 
                   ((this.passSound && this.passSound.readyState >= 3) || 
                    (this.failSound && this.failSound.readyState >= 3));
        },
        
        // Debug/Test function to help diagnose iOS issues
        testSounds: function() {
            console.log('Age Estimator Sounds: Testing sound system...');
            console.log('=== Configuration ===');
            console.log('Enabled:', this.enabled);
            console.log('iOS:', this.isIOS);
            console.log('Audio Unlocked:', this.audioUnlocked);
            console.log('Volume:', this.volume);
            
            console.log('\n=== Sound Objects ===');
            if (this.passSound) {
                console.log('Pass Sound:', {
                    src: this.passSound.src,
                    readyState: this.passSound.readyState,
                    duration: this.passSound.duration,
                    error: this.passSound.error,
                    networkState: this.passSound.networkState
                });
            } else {
                console.log('Pass Sound: NOT LOADED');
            }
            
            if (this.failSound) {
                console.log('Fail Sound:', {
                    src: this.failSound.src,
                    readyState: this.failSound.readyState,
                    duration: this.failSound.duration,
                    error: this.failSound.error,
                    networkState: this.failSound.networkState
                });
            } else {
                console.log('Fail Sound: NOT LOADED');
            }
            
            console.log('\n=== Browser Audio Support ===');
            console.log('Web Audio API:', !!(window.AudioContext || window.webkitAudioContext));
            console.log('HTML5 Audio:', typeof Audio !== 'undefined');
            
            // Test audio format support
            if (typeof Audio !== 'undefined') {
                const testAudio = new Audio();
                console.log('Audio formats:');
                console.log('- MP3:', testAudio.canPlayType('audio/mpeg'));
                console.log('- WAV:', testAudio.canPlayType('audio/wav'));
                console.log('- OGG:', testAudio.canPlayType('audio/ogg'));
            }
            
            console.log('\n=== Testing Playback ===');
            
            // Test with a simple beep
            console.log('1. Testing with generated beep...');
            this.testBeep();
            
            // Test actual sounds after delay
            setTimeout(() => {
                if (this.passSound) {
                    console.log('\n2. Testing pass sound...');
                    this.playPassSound();
                }
            }, 1000);
            
            setTimeout(() => {
                if (this.failSound) {
                    console.log('\n3. Testing fail sound...');
                    this.playFailSound();
                }
            }, 3000);
        },
        
        // Test with a generated beep sound
        testBeep: function() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) {
                    console.log('Web Audio API not supported');
                    return;
                }
                
                const audioContext = new AudioContext();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 440; // A4 note
                gainNode.gain.value = 0.3;
                
                oscillator.start();
                oscillator.stop(audioContext.currentTime + 0.2);
                
                console.log('Beep test executed - did you hear it?');
                
                // Resume context if needed
                if (audioContext.state === 'suspended') {
                    audioContext.resume();
                }
                
            } catch (e) {
                console.error('Beep test failed:', e);
            }
        },
        
        // Create test button for debugging (only in debug mode)
        createTestButton: function() {
            if (window.location.hash !== '#debug') return;
            
            const testButton = document.createElement('button');
            testButton.textContent = 'Test Age Sounds';
            testButton.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 9999; padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;';
            
            testButton.addEventListener('click', () => {
                this.testSounds();
            });
            
            document.body.appendChild(testButton);
            
            console.log('Age Estimator Sounds: Test button created (debug mode)');
        }
    };
    
    // Make globally accessible
    window.AgeEstimatorSounds = AgeEstimatorSounds;
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            AgeEstimatorSounds.init();
            AgeEstimatorSounds.createTestButton();
        });
    } else {
        AgeEstimatorSounds.init();
        AgeEstimatorSounds.createTestButton();
    }
})();
