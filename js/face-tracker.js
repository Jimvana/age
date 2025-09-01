/**
 * Face Tracking Module for Age Estimator
 * Reduces AWS Rekognition API calls by caching face analysis results
 * Updated for compatibility with @vladmandic/face-api
 * 
 * @version 1.1.0
 * @author Age Estimator Plugin
 */

const FaceTracker = (function() {
    'use strict';
    
    // Configuration - get from settings or use defaults
    const config = {
        descriptorThreshold: window.ageEstimatorPhotoParams?.faceSensitivity || 0.4,     // Max euclidean distance for face match
        cacheExpirationMs: (window.ageEstimatorPhotoParams?.cacheDuration || 30) * 1000, // Cache lifetime in milliseconds
        positionThreshold: 150,       // Max pixel movement between frames
        maxCacheSize: 10,             // Maximum faces to cache
        minQualityScore: 0.85        // Minimum quality for caching
    };
    
    // Private variables
    const faceCache = new Map();
    let nextFaceId = 1;
    let initialized = false;
    
    // Metrics for monitoring
    const metrics = {
        totalChecks: 0,
        cacheHits: 0,
        apiCalls: 0,
        startTime: Date.now()
    };
    
    // Face class
    class TrackedFace {
        constructor(descriptor, position) {
            this.id = nextFaceId++;
            this.descriptor = Array.from(descriptor);
            this.position = position;
            this.firstSeen = Date.now();
            this.lastSeen = Date.now();
            this.rekognitionData = null;
            this.hitCount = 0;
        }
        
        update(position) {
            this.lastSeen = Date.now();
            this.position = position;
            this.hitCount++;
        }
    }
    
    // Calculate euclidean distance between descriptors
    function euclideanDistance(desc1, desc2) {
        let sum = 0;
        for (let i = 0; i < desc1.length; i++) {
            sum += Math.pow(desc1[i] - desc2[i], 2);
        }
        return Math.sqrt(sum);
    }
    
    // Calculate position distance
    function positionDistance(pos1, pos2) {
        const dx = pos1.x - pos2.x;
        const dy = pos1.y - pos2.y;
        return Math.sqrt(dx * dx + dy * dy);
    }
    
    // Extract position from detection
    function getPosition(detection) {
        const box = detection.detection.box;
        return {
            x: box.x + box.width / 2,
            y: box.y + box.height / 2
        };
    }
    
    // Log debug info
    function log(message, data) {
        if (window.ageEstimatorDebug) {
            console.log(`[FaceTracker] ${message}`, data || '');
        }
    }
    
    // Public API
    return {
        /**
         * Initialize face tracking
         * @returns {Promise<boolean>} Success status
         */
        init: async function() {
            if (initialized) {
                log('Already initialized');
                return true;
            }
            
            log('Initializing...');
            
            // Check if face-api is available
            if (typeof faceapi === 'undefined') {
                console.error('[FaceTracker] face-api.js not loaded');
                return false;
            }
            
            // Check if face recognition model needs loading
            if (!faceapi.nets.faceRecognitionNet.isLoaded) {
                log('Loading face recognition models...');
                try {
                    const modelsPath = window.ageEstimatorPhotoParams?.modelsPath || '/models';
                    await Promise.all([
                        faceapi.nets.faceLandmark68Net.loadFromUri(modelsPath),
                        faceapi.nets.faceRecognitionNet.loadFromUri(modelsPath)
                    ]);
                    log('Models loaded successfully');
                } catch (error) {
                    console.error('[FaceTracker] Failed to load models:', error);
                    return false;
                }
            }
            
            initialized = true;
            metrics.startTime = Date.now();
            log('Initialization complete');
            return true;
        },
        
        /**
         * Check if a face has been seen before
         * @param {Object} detection - Face detection with descriptor
         * @returns {Object|null} Cached face data or null
         */
        checkFace: function(detection) {
            if (!initialized) {
                console.warn('[FaceTracker] Not initialized. Call init() first.');
                return null;
            }
            
            metrics.totalChecks++;
            
            if (!detection || !detection.descriptor) {
                log('No descriptor provided');
                return null;
            }
            
            const descriptor = detection.descriptor;
            const position = getPosition(detection);
            const quality = detection.detection.score;
            
            // Skip low quality detections
            if (quality < config.minQualityScore) {
                log('Low quality detection, skipping', { quality });
                return null;
            }
            
            // Try to find matching face
            let bestMatch = null;
            let bestDistance = Infinity;
            
            for (const [id, face] of faceCache) {
                const distance = euclideanDistance(descriptor, face.descriptor);
                
                if (distance < config.descriptorThreshold) {
                    const posDist = positionDistance(position, face.position);
                    
                    if (posDist < config.positionThreshold && distance < bestDistance) {
                        bestDistance = distance;
                        bestMatch = face;
                    }
                }
            }
            
            if (bestMatch) {
                // Update tracked face
                bestMatch.update(position);
                metrics.cacheHits++;
                
                log('Cache hit', {
                    faceId: bestMatch.id,
                    distance: bestDistance.toFixed(3),
                    hitCount: bestMatch.hitCount
                });
                
                return bestMatch.rekognitionData;
            }
            
            log('No match found', { 
                cacheSize: faceCache.size,
                bestDistance: bestDistance.toFixed(3)
            });
            
            return null;
        },
        
        /**
         * Add a new face to the cache
         * @param {Object} detection - Face detection with descriptor
         * @param {Object} rekognitionData - Age and other data from Rekognition
         * @returns {number} Face ID
         */
        addFace: function(detection, rekognitionData) {
            if (!initialized || !detection || !detection.descriptor) {
                return null;
            }
            
            const position = getPosition(detection);
            
            // Check cache size
            if (faceCache.size >= config.maxCacheSize) {
                // Remove oldest or least used face
                let removeId = null;
                let oldestTime = Infinity;
                let leastHits = Infinity;
                
                for (const [id, face] of faceCache) {
                    const age = Date.now() - face.firstSeen;
                    const score = face.hitCount / (age / 1000); // Hits per second
                    
                    if (score < leastHits) {
                        leastHits = score;
                        removeId = id;
                    }
                }
                
                if (removeId) {
                    log('Removing face from cache', { faceId: removeId });
                    faceCache.delete(removeId);
                }
            }
            
            // Add new face
            const newFace = new TrackedFace(detection.descriptor, position);
            newFace.rekognitionData = rekognitionData;
            faceCache.set(newFace.id, newFace);
            
            metrics.apiCalls++;
            
            log('Face added to cache', {
                faceId: newFace.id,
                age: rekognitionData.age,
                cacheSize: faceCache.size
            });
            
            return newFace.id;
        },
        
        /**
         * Clean up expired faces
         * @returns {number} Number of faces removed
         */
        cleanup: function() {
            const now = Date.now();
            const toRemove = [];
            
            for (const [id, face] of faceCache) {
                if (now - face.lastSeen > config.cacheExpirationMs) {
                    toRemove.push(id);
                }
            }
            
            toRemove.forEach(id => {
                log('Removing expired face', { faceId: id });
                faceCache.delete(id);
            });
            
            return toRemove.length;
        },
        
        /**
         * Get current metrics
         * @returns {Object} Metrics object
         */
        getMetrics: function() {
            const runtime = (Date.now() - metrics.startTime) / 1000; // seconds
            return {
                totalChecks: metrics.totalChecks,
                cacheHits: metrics.cacheHits,
                apiCalls: metrics.apiCalls,
                cacheSize: faceCache.size,
                hitRate: metrics.totalChecks > 0 ? 
                    (metrics.cacheHits / metrics.totalChecks * 100).toFixed(1) : 0,
                apiReduction: metrics.totalChecks > 0 ?
                    ((1 - (metrics.apiCalls / metrics.totalChecks)) * 100).toFixed(1) : 0,
                checksPerSecond: (metrics.totalChecks / runtime).toFixed(1),
                runtime: runtime.toFixed(0)
            };
        },
        
        /**
         * Get cached faces info
         * @returns {Array} Array of face info
         */
        getCachedFaces: function() {
            const faces = [];
            const now = Date.now();
            
            for (const [id, face] of faceCache) {
                faces.push({
                    id: face.id,
                    age: face.rekognitionData?.age,
                    firstSeen: new Date(face.firstSeen).toLocaleTimeString(),
                    lastSeen: new Date(face.lastSeen).toLocaleTimeString(),
                    cacheAge: ((now - face.firstSeen) / 1000).toFixed(0) + 's',
                    hitCount: face.hitCount,
                    active: (now - face.lastSeen) < 1000
                });
            }
            
            return faces;
        },
        
        /**
         * Clear all cached faces
         */
        clear: function() {
            faceCache.clear();
            metrics.totalChecks = 0;
            metrics.cacheHits = 0;
            metrics.apiCalls = 0;
            metrics.startTime = Date.now();
            log('Cache cleared');
        },
        
        /**
         * Update configuration
         * @param {Object} newConfig - New configuration values
         */
        updateConfig: function(newConfig) {
            Object.assign(config, newConfig);
            log('Configuration updated', config);
        },
        
        /**
         * Get current configuration
         * @returns {Object} Current configuration
         */
        getConfig: function() {
            return { ...config };
        },
        
        /**
         * Enable/disable debug logging
         * @param {boolean} enable - Enable debug mode
         */
        setDebug: function(enable) {
            window.ageEstimatorDebug = enable;
            log('Debug mode ' + (enable ? 'enabled' : 'disabled'));
        }
    };
})();

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FaceTracker;
} else {
    window.FaceTracker = FaceTracker;
}