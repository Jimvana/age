# Complete Sound Implementation Instructions for Age Estimator

## Quick Implementation Guide

### 1. Add Sound Script to Main Plugin File

In `age-estimator.php`, find the `enqueue_scripts()` function and add:

```php
// Enqueue sound manager script
wp_enqueue_script(
    'age-estimator-sounds',
    AGE_ESTIMATOR_URL . 'js/age-estimator-sounds.js',
    array(),
    AGE_ESTIMATOR_VERSION,
    true
);

// Optional: Enqueue sound styles
wp_enqueue_style(
    'age-estimator-sounds',
    AGE_ESTIMATOR_URL . 'css/age-estimator-sounds.css',
    array(),
    AGE_ESTIMATOR_VERSION
);
```

### 2. Add Sound Settings to Admin Panel

Include the settings from `includes/admin-settings-sound-update.php` in your existing `includes/admin-settings.php` file. The key sections to add are:

1. Sound settings section registration
2. Setting fields for enable/disable, pass sound URL, fail sound URL, and volume
3. Media uploader integration
4. Test playback functionality

### 3. Add Sound Parameters to Localization

In your main plugin file or wherever you set up `ageEstimatorPhotoParams`, add:

```php
// In the array that gets passed to wp_localize_script
$params['enableSounds'] = get_option('age_estimator_enable_sounds', false) ? '1' : '0';
$params['passSoundUrl'] = get_option('age_estimator_pass_sound_url', '');
$params['failSoundUrl'] = get_option('age_estimator_fail_sound_url', '');
$params['soundVolume'] = get_option('age_estimator_sound_volume', 0.7);
```

### 4. Integrate Sound Playback in JavaScript

In `js/photo-age-estimator-continuous.js`, add sound playback code to these three functions:

#### A. In `displayAwsResults` function:
Find this section (around line 1940):
```javascript
const face = faces[0];
const estimatedAge = Math.round(face.age);
const minimumAge = parseInt(ageEstimatorPhotoParams.minimumAge || 21);
const ageGateEnabled = ageEstimatorPhotoParams.enableAgeGate === '1';
```

Add immediately after:
```javascript
// Play appropriate sound based on pass/fail
if (typeof AgeEstimatorSounds !== 'undefined' && ageGateEnabled) {
    const passed = estimatedAge >= minimumAge;
    if (passed) {
        AgeEstimatorSounds.playPassSound();
    } else {
        AgeEstimatorSounds.playFailSound();
    }
}
```

#### B. In `displayCachedAwsResults` function:
Find this section (around line 1825):
```javascript
const estimatedAge = Math.round(cachedData.age);
const minimumAge = parseInt(ageEstimatorPhotoParams.minimumAge || 21);
const ageGateEnabled = ageEstimatorPhotoParams.enableAgeGate === '1';
```

Add the same sound playback code after.

#### C. In `displayLocalResults` function (if using local detection):
Find the similar section and add the same sound playback code.

### 5. Optional: Add Mute Button

In the `initializeElements` function of `photo-age-estimator-continuous.js`, add:

```javascript
// Create mute button for sound control
if (ageEstimatorPhotoParams.enableSounds === '1') {
    const muteButton = document.createElement('button');
    muteButton.id = 'age-estimator-mute-toggle';
    muteButton.className = 'age-estimator-mute-button';
    muteButton.innerHTML = '🔊';
    muteButton.title = 'Toggle sound';
    muteButton.style.display = 'none'; // Show when camera starts
    
    muteButton.addEventListener('click', function() {
        if (typeof AgeEstimatorSounds !== 'undefined') {
            if (AgeEstimatorSounds.volume > 0) {
                AgeEstimatorSounds.setVolume(0);
                muteButton.innerHTML = '🔇';
                muteButton.title = 'Unmute sound';
            } else {
                AgeEstimatorSounds.setVolume(parseFloat(ageEstimatorPhotoParams.soundVolume) || 0.7);
                muteButton.innerHTML = '🔊';
                muteButton.title = 'Mute sound';
            }
        }
    });
    
    cameraContainer.appendChild(muteButton);
}
```

Then show/hide it when camera starts/stops:
```javascript
// In startCamera function:
const muteBtn = document.getElementById('age-estimator-mute-toggle');
if (muteBtn) muteBtn.style.display = 'block';

// In stopCamera function:
const muteBtn = document.getElementById('age-estimator-mute-toggle');
if (muteBtn) muteBtn.style.display = 'none';
```

## Testing Your Implementation

1. **Test Page**: Open `test-sound-notifications.html` in a browser to verify sounds work
2. **Admin Settings**: Go to Age Estimator settings and configure your sounds
3. **Live Test**: Test with actual age verification to ensure sounds play correctly

## Default Sounds

If you don't have custom sounds, you can use the included default tones by including `includes/default-sounds.php`.

## Troubleshooting

1. **No sound playing**: Check browser console for errors, ensure sounds are enabled
2. **Autoplay blocked**: Some browsers require user interaction first
3. **Sound not loading**: Check URLs are correct and accessible
4. **Volume issues**: Verify system volume and browser tab isn't muted

## Complete File List

- `js/age-estimator-sounds.js` - Main sound manager
- `css/age-estimator-sounds.css` - Optional styling
- `includes/admin-settings-sound-update.php` - Admin settings code
- `includes/default-sounds.php` - Default sound data URIs
- `test-sound-notifications.html` - Test page
- This implementation guide

That's it! Your Age Estimator will now play customizable sounds for pass/fail results.
