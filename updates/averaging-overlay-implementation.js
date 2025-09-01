// Complete averaging implementation modifications for photo-age-estimator-continuous-overlay.js

// Find the section with configuration constants and add these after MONITORING_CONFIG:

// Age averaging configuration
const AVERAGING_CONFIG = {
    enabled: ageEstimatorPhotoParams?.enableAveraging === '1',
    samplesToAverage: parseInt(ageEstimatorPhotoParams?.averageSamples) || 5,
    sampleDelay: 1000 // Delay between samples in milliseconds
};

// Age averaging state
let ageAveragingState = {
    isCollecting: false,
    samples: [],
    currentSampleCount: 0,
    targetSamples: AVERAGING_CONFIG.samplesToAverage
};

// In the initializeElements function, add after the metricsDisplay creation:

// Add averaging progress display
if (AVERAGING_CONFIG.enabled && settings.mode !== 'aws') {
    const averagingDisplay = document.createElement('div');
    averagingDisplay.id = 'age-estimator-averaging-progress';
    averagingDisplay.style.position = 'absolute';
    averagingDisplay.style.top = '60px';
    averagingDisplay.style.right = '10px';
    averagingDisplay.style.padding = '10px';
    averagingDisplay.style.backgroundColor = 'rgba(0,0,0,0.8)';
    averagingDisplay.style.color = 'white';
    averagingDisplay.style.borderRadius = '5px';
    averagingDisplay.style.fontSize = '14px';
    averagingDisplay.style.display = 'none';
    averagingDisplay.style.minWidth = '200px';
    averagingDisplay.style.zIndex = '1000';
    cameraContainer.appendChild(averagingDisplay);
}

// Add these new functions to the photoAgeEstimator object:

startAgeAveraging: function() {
    console.log('Starting age averaging collection...');
    
    // Reset state
    ageAveragingState.isCollecting = true;
    ageAveragingState.samples = [];
    ageAveragingState.currentSampleCount = 0;
    
    // Show averaging UI
    this.showAveragingProgress();
    
    // Disable capture for cooldown period
    isProcessing = true;
    
    // Start capturing samples
    this.captureNextSample();
},

captureNextSample: function() {
    if (!ageAveragingState.isCollecting) return;
    
    console.log(`Preparing to capture sample ${ageAveragingState.currentSampleCount + 1} of ${ageAveragingState.targetSamples}`);
    
    // Update UI to show we're about to capture
    this.updateAveragingProgress();
    
    // Wait a moment then capture
    setTimeout(() => {
        if (!ageAveragingState.isCollecting) return;
        
        // Capture the image
        this.captureSampleImage();
    }, 500);
},

captureSampleImage: async function() {
    console.log('Capturing sample image for averaging...');
    
    // Show flash effect
    this.showFlashEffect();
    
    // Capture photo
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    // Create a temporary image for this sample
    const sampleImageData = canvas.toDataURL('image/jpeg', 0.95);
    
    try {
        // Analyze the sample
        const img = new Image();
        img.crossOrigin = 'anonymous';
        
        img.onload = async () => {
            try {
                const detections = await faceapi.detectAllFaces(img)
                    .withAgeAndGender()
                    .withFaceExpressions();
                
                if (detections.length > 0) {
                    const age = Math.round(detections[0].age);
                    ageAveragingState.samples.push(age);
                    ageAveragingState.currentSampleCount++;
                    
                    console.log(`Sample ${ageAveragingState.currentSampleCount}: Age ${age}`);
                    
                    this.updateAveragingProgress();
                    
                    if (ageAveragingState.currentSampleCount < ageAveragingState.targetSamples) {
                        // Need more samples
                        setTimeout(() => {
                            this.captureNextSample();
                        }, AVERAGING_CONFIG.sampleDelay);
                    } else {
                        // We have all samples, calculate average
                        this.calculateAndDisplayAverage();
                    }
                } else {
                    console.log('No face detected in sample, retrying...');
                    setTimeout(() => {
                        this.captureNextSample();
                    }, 500);
                }
            } catch (error) {
                console.error('Error analyzing sample:', error);
                this.showError('Error during sample analysis: ' + error.message);
                ageAveragingState.isCollecting = false;
                isProcessing = false;
            }
        };
        
        img.onerror = () => {
            console.error('Failed to load sample image');
            this.showError('Failed to process sample image');
            ageAveragingState.isCollecting = false;
            isProcessing = false;
        };
        
        img.src = sampleImageData;
        
    } catch (error) {
        console.error('Error in sample capture:', error);
        this.showError('Error capturing sample: ' + error.message);
        ageAveragingState.isCollecting = false;
        isProcessing = false;
    }
},

showAveragingProgress: function() {
    const averagingDisplay = document.getElementById('age-estimator-averaging-progress');
    if (averagingDisplay) {
        averagingDisplay.style.display = 'block';
        this.updateAveragingProgress();
    }
},

updateAveragingProgress: function() {
    const averagingDisplay = document.getElementById('age-estimator-averaging-progress');
    if (!averagingDisplay) return;
    
    const progress = ageAveragingState.currentSampleCount;
    const total = ageAveragingState.targetSamples;
    const percentage = Math.round((progress / total) * 100);
    
    let samplesHTML = '<div style="margin-bottom: 10px;">';
    samplesHTML += `<strong style="color: #2196F3;">Collecting Age Samples</strong><br>`;
    samplesHTML += `<span style="font-size: 18px;">Sample ${progress} of ${total}</span>`;
    samplesHTML += '</div>';
    
    // Progress bar
    samplesHTML += `
        <div style="background: #333; height: 20px; border-radius: 10px; overflow: hidden; margin-bottom: 10px;">
            <div style="background: linear-gradient(90deg, #1976D2 0%, #2196F3 50%, #42A5F5 100%); 
                        height: 100%; width: ${percentage}%; transition: width 0.3s;">
            </div>
        </div>
    `;
    
    // Show collected samples
    if (ageAveragingState.samples.length > 0) {
        samplesHTML += '<div style="margin-top: 10px; font-size: 12px;">';
        samplesHTML += '<strong>Ages collected:</strong><br>';
        samplesHTML += '<div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 5px;">';
        ageAveragingState.samples.forEach((age, index) => {
            samplesHTML += `<span style="background: #2196F3; color: white; padding: 2px 8px; 
                                       border-radius: 12px; font-weight: bold;">${age}</span>`;
        });
        samplesHTML += '</div></div>';
    }
    
    averagingDisplay.innerHTML = samplesHTML;
},

calculateAndDisplayAverage: function() {
    console.log('Calculating average from samples:', ageAveragingState.samples);
    
    // Calculate average
    const sum = ageAveragingState.samples.reduce((a, b) => a + b, 0);
    const averageAge = Math.round(sum / ageAveragingState.samples.length);
    
    // Calculate standard deviation for confidence
    const variance = ageAveragingState.samples.reduce((acc, age) => {
        return acc + Math.pow(age - averageAge, 2);
    }, 0) / ageAveragingState.samples.length;
    const stdDev = Math.sqrt(variance);
    
    // Hide averaging progress
    const averagingDisplay = document.getElementById('age-estimator-averaging-progress');
    if (averagingDisplay) {
        averagingDisplay.style.display = 'none';
    }
    
    // Store the last captured image as the result image
    capturedImageData = canvas.toDataURL('image/jpeg', 0.95);
    
    // Display results
    this.displayAveragedResults({
        averageAge: averageAge,
        samples: ageAveragingState.samples,
        stdDev: stdDev,
        minAge: Math.min(...ageAveragingState.samples),
        maxAge: Math.max(...ageAveragingState.samples)
    });
    
    // Reset averaging state
    ageAveragingState.isCollecting = false;
    ageAveragingState.samples = [];
    ageAveragingState.currentSampleCount = 0;
    
    // Reset processing state after cooldown
    setTimeout(() => {
        isProcessing = false;
        lastCaptureTime = Date.now();
    }, MONITORING_CONFIG.cooldownPeriod);
},

displayAveragedResults: function(data) {
    console.log('Displaying averaged results:', data);
    
    // Make sure result container is visible
    if (resultContainer) {
        resultContainer.style.display = 'block';
    }
    
    const minimumAge = parseInt(ageEstimatorPhotoParams.minimumAge || 21);
    const ageGateEnabled = ageEstimatorPhotoParams.enableAgeGate === '1';
    const passed = data.averageAge >= minimumAge;
    
    // Play appropriate sound
    if (typeof AgeEstimatorSounds !== 'undefined' && ageGateEnabled) {
        if (passed) {
            AgeEstimatorSounds.playPassSound();
        } else {
            AgeEstimatorSounds.playFailSound();
        }
    }
    
    let resultHTML = '<div class="continuous-result averaged-result">';
    
    // Add averaging indicator
    resultHTML += `
        <div class="averaging-indicator" style="position: absolute; top: 10px; right: 10px; 
                                               background: #2196F3; color: white; padding: 4px 10px; 
                                               border-radius: 12px; font-size: 12px; font-weight: bold;">
            AVERAGED (${data.samples.length} samples)
        </div>
    `;
    
    if (ageGateEnabled) {
        resultHTML += `
            <div class="age-gate-result ${passed ? 'passed' : 'failed'}">
                <div class="pass-fail-display">
                    ${passed ? 'PASS' : 'FAIL'}
                </div>
            </div>
        `;
    }
    
    resultHTML += `
        <div class="age-result averaged-age-result">
            <h3>Age Verification Complete</h3>
            <div class="primary-result" style="background: rgba(33, 150, 243, 0.1); 
                                             padding: 15px; border-radius: 8px; margin: 10px 0;">
                <p class="age-display">Average Age: <strong style="color: #1976D2; font-size: 28px;">${data.averageAge} years</strong></p>
            </div>
            <div class="averaging-details" style="margin-top: 15px; padding: 10px; 
                                                background: rgba(0,0,0,0.05); border-radius: 5px; 
                                                border-left: 4px solid #2196F3;">
                <p style="margin: 5px 0; font-size: 14px;">
                    <strong style="color: #1976D2;">Sample Details:</strong><br>
                    <span style="font-family: monospace;">Ages collected: ${data.samples.join(', ')}</span><br>
                    <span style="font-family: monospace;">Range: ${data.minAge} - ${data.maxAge} years</span><br>
                    <span style="font-family: monospace;">Standard Deviation: ±${data.stdDev.toFixed(1)} years</span>
                </p>
            </div>
        </div>
    `;
    
    resultHTML += `
        <div class="capture-info">
            <p>Averaged at ${new Date().toLocaleTimeString()}</p>
            <p style="font-size: 11px; color: #666;">Multiple samples collected for accuracy</p>
        </div>
    `;
    
    resultHTML += '</div>';
    
    // Append to results
    const newResult = document.createElement('div');
    newResult.innerHTML = resultHTML;
    newResult.style.marginBottom = '20px';
    newResult.style.paddingBottom = '20px';
    newResult.style.borderBottom = '1px solid #ddd';
    newResult.style.position = 'relative';
    newResult.style.border = '2px solid #2196F3';
    newResult.style.borderRadius = '8px';
    newResult.style.padding = '15px';
    newResult.style.background = 'linear-gradient(135deg, rgba(33, 150, 243, 0.05) 0%, rgba(33, 150, 243, 0.02) 100%)';
    
    resultContainer.insertBefore(newResult, resultContainer.firstChild);
    
    // Keep only last 5 results
    while (resultContainer.children.length > 5) {
        resultContainer.removeChild(resultContainer.lastChild);
    }
    
    // Update metrics
    this.updateMetricsDisplay();
    
    // Schedule return to kiosk after showing results
    this.scheduleReturnToKiosk();
},

// MODIFY the captureAndAnalyze function to check for averaging mode:
// Add this at the beginning of captureAndAnalyze function:

captureAndAnalyze: async function() {
    if (isProcessing || !videoReady) {
        console.log('Skipping capture - processing or video not ready');
        return;
    }
    
    // Check if we should start averaging instead
    if (AVERAGING_CONFIG.enabled && settings.mode !== 'aws' && !ageAveragingState.isCollecting) {
        console.log('Starting age averaging process...');
        this.startAgeAveraging();
        return;
    }
    
    // Continue with normal single capture if not averaging...
    // [rest of the original captureAndAnalyze function]
}