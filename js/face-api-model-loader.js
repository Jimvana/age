/**
 * Enhanced Face API Model Loader for @vladmandic/face-api
 * Prioritizes local models with CDN fallbacks
 * Updated for compatibility with @vladmandic/face-api
 */

class FaceAPIModelLoader {
    constructor() {
        // Get the plugin URL from parameters
        const pluginUrl = window.ageEstimatorPhotoParams?.pluginUrl || window.ageEstimatorParams?.pluginUrl || '';
        
        this.modelSources = [
            {
                name: 'Local Models',
                path: pluginUrl + 'models/',
                priority: 1,
                isLocal: true
            },
            {
                name: 'vladmandic CDN',
                path: 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@latest/model/',
                priority: 2
            },
            {
                name: 'vladmandic GitHub CDN',
                path: 'https://raw.githubusercontent.com/vladmandic/face-api/main/model/',
                priority: 3
            },
            {
                name: 'Official GitHub CDN (Legacy)',
                path: 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js-models@master/weights/',
                priority: 4
            }
        ];
        
        this.isModelLoaded = false;
        this.loadedSource = null;
        this.requiredModels = [
            'ssdMobilenetv1',
            'ageGenderNet', 
            'faceExpressionNet',
            'faceLandmark68Net',
            'faceRecognitionNet'
        ];
    }
    
    async loadModels() {
        console.log('FaceAPIModelLoader: Starting model loading for @vladmandic/face-api...');
        
        // Wait for face-api.js to be available
        await this.waitForFaceAPI();
        
        // Check if using @vladmandic/face-api
        if (typeof faceapi !== 'undefined' && faceapi.version) {
            console.log(`FaceAPIModelLoader: Detected face-api version: ${faceapi.version}`);
        }
        
        // Try each source until one works
        for (const source of this.modelSources) {
            try {
                console.log(`FaceAPIModelLoader: Trying ${source.name} - ${source.path}`);
                
                await this.loadFromSource(source);
                
                this.isModelLoaded = true;
                this.loadedSource = source;
                console.log(`FaceAPIModelLoader: Successfully loaded models from ${source.name}`);
                
                // Log model info for debugging
                this.logModelInfo();
                
                return true;
                
            } catch (error) {
                console.warn(`FaceAPIModelLoader: Failed to load from ${source.name}:`, error.message);
                if (source.isLocal) {
                    console.warn('FaceAPIModelLoader: Local models failed, trying CDN sources...');
                }
                continue;
            }
        }
        
        // If all sources fail
        console.error('FaceAPIModelLoader: All model sources failed');
        throw new Error('Unable to load face detection models from any source. Please check your internet connection or model files.');
    }
    
    async waitForFaceAPI() {
        let attempts = 0;
        while (typeof faceapi === 'undefined' && attempts < 50) {
            console.log(`FaceAPIModelLoader: Waiting for face-api.js... attempt ${attempts + 1}`);
            await new Promise(resolve => setTimeout(resolve, 100));
            attempts++;
        }
        
        if (typeof faceapi === 'undefined') {
            throw new Error('@vladmandic/face-api library not loaded. Please ensure face-api.min.js is included.');
        }
        
        console.log('FaceAPIModelLoader: @vladmandic/face-api is available');
    }
    
    async loadFromSource(source) {
        const modelsPath = source.path;
        
        // Configure face-api options for @vladmandic/face-api
        if (faceapi.env && faceapi.env.monkeyPatch) {
            console.log('FaceAPIModelLoader: Configuring @vladmandic/face-api environment...');
            // Set WebGL backend for better performance
            await faceapi.tf?.setBackend('webgl');
        }
        
        // Load models sequentially to avoid race conditions
        try {
            console.log('Loading SSD MobileNet v1...');
            await faceapi.nets.ssdMobilenetv1.loadFromUri(modelsPath);
            
            console.log('Loading Age Gender Net...');
            await faceapi.nets.ageGenderNet.loadFromUri(modelsPath);
            
            console.log('Loading Face Expression Net...');
            await faceapi.nets.faceExpressionNet.loadFromUri(modelsPath);
            
            // Load additional models for face tracking
            console.log('Loading Face Landmark 68 Net...');
            await faceapi.nets.faceLandmark68Net.loadFromUri(modelsPath);
            
            console.log('Loading Face Recognition Net...');
            await faceapi.nets.faceRecognitionNet.loadFromUri(modelsPath);
            
            console.log(`Successfully loaded all models from ${source.name}`);
        } catch (error) {
            throw new Error(`Failed to load models: ${error.message}`);
        }
    }
    
    logModelInfo() {
        console.log('FaceAPIModelLoader: Model Information:');
        console.log('- SSD MobileNet v1:', faceapi.nets.ssdMobilenetv1.isLoaded);
        console.log('- Age Gender Net:', faceapi.nets.ageGenderNet.isLoaded);
        console.log('- Face Expression Net:', faceapi.nets.faceExpressionNet.isLoaded);
        console.log('- Face Landmark 68 Net:', faceapi.nets.faceLandmark68Net.isLoaded);
        console.log('- Face Recognition Net:', faceapi.nets.faceRecognitionNet.isLoaded);
        
        // Log backend info if available
        if (faceapi.tf) {
            console.log('- TensorFlow Backend:', faceapi.tf.getBackend());
        }
    }
    
    getLoadedSource() {
        return this.loadedSource;
    }
    
    isLoaded() {
        return this.isModelLoaded;
    }
    
    // New method for @vladmandic/face-api specific features
    async optimizePerformance() {
        if (faceapi.tf) {
            try {
                // Enable WebGL if available
                if (faceapi.tf.ENV.getBool('WEBGL_VERSION') >= 1) {
                    await faceapi.tf.setBackend('webgl');
                    console.log('FaceAPIModelLoader: WebGL backend enabled for better performance');
                }
                
                // Set memory management flags
                faceapi.tf.ENV.set('WEBGL_DELETE_TEXTURE_THRESHOLD', 0);
                console.log('FaceAPIModelLoader: Performance optimizations applied');
            } catch (error) {
                console.warn('FaceAPIModelLoader: Could not apply performance optimizations:', error);
            }
        }
    }
}

// Make it globally available
window.FaceAPIModelLoader = FaceAPIModelLoader;
