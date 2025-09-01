# Age Averaging Feature Implementation Guide

This guide shows how to implement the age averaging feature for the Age Estimator Live plugin.

## Overview

The averaging feature allows the plugin to take multiple age readings and average them for a more accurate pass/fail decision. This is only available in Simple (face-api.js) mode.

## Files to Modify

### 1. Admin Settings (`includes/admin-settings.php`)

Already modified to include:
- New setting `age_estimator_enable_averaging` (checkbox)
- New setting `age_estimator_average_samples` (number, 3-10)
- UI elements in the General tab
- JavaScript to show/hide settings based on mode

### 2. Main Plugin File (`age-estimator.php`)

Already modified to include the new parameters in the localize_script function:
```php
'enableAveraging' => get_option('age_estimator_enable_averaging', false) ? '1' : '0',
'averageSamples' => intval(get_option('age_estimator_average_samples', 5))
```

### 3. JavaScript File (`js/photo-age-estimator-continuous.js`)

Add the following at the beginning of the file (after the adaptiveState declaration):

```javascript
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
```

In the `initializeElements` function, add after the metricsDisplay creation:

```javascript
// Add averaging progress display
if (AVERAGING_CONFIG.enabled && !settings.useAws) {
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
    cameraContainer.appendChild(averagingDisplay);
}
```

Replace the `analyzeWithLocal` function with the modified version that includes averaging logic (see the artifacts above).

Add the new averaging functions:
- `startAgeAveraging()`
- `captureNextSample()`
- `showAveragingProgress()`
- `updateAveragingProgress()`
- `calculateAndDisplayAverage()`
- `displayAveragedResults()`

### 4. CSS File (`css/age-estimator-photo.css`)

Add the CSS styles from the artifact above.

## How It Works

1. When averaging is enabled and Simple mode is active:
   - The first capture triggers the averaging collection process
   - The plugin automatically captures the configured number of samples (3-10)
   - Each capture has a 1-second delay between samples
   - Progress is shown in a UI overlay

2. After all samples are collected:
   - The average age is calculated
   - Standard deviation is computed for confidence
   - Results show the average age with sample details
   - Pass/fail decision is based on the average age

3. The results display includes:
   - Average age prominently displayed
   - List of all collected age samples
   - Age range (min-max)
   - Standard deviation
   - Visual indicator that averaging was used

## Testing

1. Enable averaging in admin settings:
   - Go to Age Estimator settings
   - In General tab, check "Enable age averaging"
   - Set number of samples (e.g., 5)
   - Save settings

2. Test the feature:
   - Use the shortcode on a page
   - Click "Start Monitoring"
   - When a face is detected and in range, it will start collecting samples
   - Watch the progress indicator
   - See the averaged result with all sample details

## Notes

- Averaging only works in Simple mode (face-api.js)
- AWS mode does not support averaging due to API cost considerations
- The feature adds about 5-10 seconds to the detection process (depending on samples)
- More samples = more accurate but slower results
