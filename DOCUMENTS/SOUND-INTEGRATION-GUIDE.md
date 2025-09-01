/**
 * Integration Guide: Adding Sound Notifications to Age Estimator
 * 
 * This shows how to integrate sound playback into the existing continuous monitoring script
 */

// 1. In your main plugin file (age-estimator.php), add the sound script to enqueue_scripts():

wp_enqueue_script(
    'age-estimator-sounds',
    AGE_ESTIMATOR_URL . 'js/age-estimator-sounds.js',
    array(),
    AGE_ESTIMATOR_VERSION,
    true
);

// Make sure it loads before the main photo estimator script


// 2. In photo-age-estimator-continuous.js, update the displayAwsResults function:
// Find this section around line 1940:

displayAwsResults: function(faces) {
    console.log('Age Estimator Photo Continuous: Displaying AWS results for', faces.length, 'face(s)');
    
    // Make sure result container is visible
    if (resultContainer) {
        resultContainer.style.display = 'block';
    }
    
    const face = faces[0];
    const estimatedAge = Math.round(face.age);
    const minimumAge = parseInt(ageEstimatorPhotoParams.minimumAge || 21);
    const ageGateEnabled = ageEstimatorPhotoParams.enableAgeGate === '1';
    
    // ADD THIS: Play appropriate sound based on pass/fail
    if (typeof AgeEstimatorSounds !== 'undefined' && ageGateEnabled) {
        const passed = estimatedAge >= minimumAge;
        if (passed) {
            AgeEstimatorSounds.playPassSound();
        } else {
            AgeEstimatorSounds.playFailSound();
        }
    }
    
    // ... rest of the function continues as before


// 3. Also update displayCachedAwsResults function (around line 1825):

displayCachedAwsResults: function(cachedData) {
    console.log('Displaying cached AWS results');
    
    // Make sure result container is visible
    if (resultContainer) {
        resultContainer.style.display = 'block';
    }
    
    // Create a minimal result display for cached data
    const estimatedAge = Math.round(cachedData.age);
    const minimumAge = parseInt(ageEstimatorPhotoParams.minimumAge || 21);
    const ageGateEnabled = ageEstimatorPhotoParams.enableAgeGate === '1';
    
    // ADD THIS: Play appropriate sound for cached results too
    if (typeof AgeEstimatorSounds !== 'undefined' && ageGateEnabled) {
        const passed = estimatedAge >= minimumAge;
        if (passed) {
            AgeEstimatorSounds.playPassSound();
        } else {
            AgeEstimatorSounds.playFailSound();
        }
    }
    
    // ... rest of the function continues as before


// 4. Optional: Also add sounds to displayLocalResults if you're using local detection:

displayLocalResults: function(detections) {
    console.log('Age Estimator Photo Continuous: Displaying local results for', detections.length, 'face(s)');
    
    // Make sure result container is visible
    if (resultContainer) {
        resultContainer.style.display = 'block';
    }
    
    const detection = detections[0];
    const estimatedAge = Math.round(detection.age);
    const minimumAge = parseInt(ageEstimatorPhotoParams.minimumAge || 21);
    const ageGateEnabled = ageEstimatorPhotoParams.enableAgeGate === '1';
    
    // ADD THIS: Play sound for local results
    if (typeof AgeEstimatorSounds !== 'undefined' && ageGateEnabled) {
        const passed = estimatedAge >= minimumAge;
        if (passed) {
            AgeEstimatorSounds.playPassSound();
        } else {
            AgeEstimatorSounds.playFailSound();
        }
    }
    
    // ... rest of the function continues as before


// 5. For retail mode (if you have it), update photo-age-estimator-retail.js similarly:
// In the displayResults function, add:

if (typeof AgeEstimatorSounds !== 'undefined') {
    // For retail mode, you might want different logic based on challenge age
    const challengeAge = parseInt(ageEstimatorPhotoParams.challengeAge || 25);
    
    if (estimatedAge < challengeAge) {
        // Under challenge age - play fail sound
        AgeEstimatorSounds.playFailSound();
    } else {
        // Over challenge age - play pass sound
        AgeEstimatorSounds.playPassSound();
    }
}


// 6. Optional: Add a mute button to the UI
// In your initializeElements function, add:

// Create mute button
const muteButton = document.createElement('button');
muteButton.id = 'age-estimator-mute-toggle';
muteButton.className = 'age-estimator-mute-button';
muteButton.innerHTML = '🔊'; // Speaker icon
muteButton.style.position = 'absolute';
muteButton.style.top = '10px';
muteButton.style.left = '10px';
muteButton.style.background = 'rgba(0,0,0,0.5)';
muteButton.style.color = 'white';
muteButton.style.border = 'none';
muteButton.style.borderRadius = '50%';
muteButton.style.width = '40px';
muteButton.style.height = '40px';
muteButton.style.cursor = 'pointer';
muteButton.style.fontSize = '20px';
muteButton.style.display = 'none'; // Hidden until camera starts

muteButton.addEventListener('click', function() {
    if (typeof AgeEstimatorSounds !== 'undefined') {
        if (AgeEstimatorSounds.volume > 0) {
            // Mute
            AgeEstimatorSounds.setVolume(0);
            muteButton.innerHTML = '🔇'; // Muted icon
        } else {
            // Unmute
            AgeEstimatorSounds.setVolume(parseFloat(ageEstimatorPhotoParams.soundVolume) || 0.7);
            muteButton.innerHTML = '🔊'; // Speaker icon
        }
    }
});

cameraContainer.appendChild(muteButton);
