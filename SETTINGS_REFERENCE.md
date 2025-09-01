# Age Estimator Settings - Reference Guide

## 📖 How to Access & Use Settings in Your Code

### 1. PHP - Getting User Settings

#### Get Current User's Settings
```php
// Get a specific setting for the current logged-in user
$user_id = get_current_user_id();
$face_sensitivity = get_user_meta($user_id, 'age_estimator_face_sensitivity', true);
$retail_mode = get_user_meta($user_id, 'age_estimator_retail_mode_enabled', true);
$minimum_age = get_user_meta($user_id, 'age_estimator_minimum_age', true);

// Get with defaults if not set
$detection_interval = get_user_meta($user_id, 'age_estimator_detection_interval', true) ?: 500;
```

#### Get All Settings for a User
```php
function get_all_age_estimator_settings($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $settings = array(
        // General
        'show_results' => get_user_meta($user_id, 'age_estimator_show_results', true) ?: true,
        'show_confidence' => get_user_meta($user_id, 'age_estimator_show_confidence', true) ?: true,
        'result_display_time' => get_user_meta($user_id, 'age_estimator_result_display_time', true) ?: 5,
        'age_gating_enabled' => get_user_meta($user_id, 'age_estimator_age_gating_enabled', true) === '1',
        'minimum_age' => get_user_meta($user_id, 'age_estimator_minimum_age', true) ?: 18,
        
        // Detection
        'face_sensitivity' => get_user_meta($user_id, 'age_estimator_face_sensitivity', true) ?: 0.4,
        'detection_interval' => get_user_meta($user_id, 'age_estimator_detection_interval', true) ?: 500,
        'min_face_size' => get_user_meta($user_id, 'age_estimator_min_face_size', true) ?: 150,
        'max_face_size' => get_user_meta($user_id, 'age_estimator_max_face_size', true) ?: 350,
        'face_tracking' => get_user_meta($user_id, 'age_estimator_face_tracking', true) !== '0',
        'multi_face' => get_user_meta($user_id, 'age_estimator_multi_face', true) === '1',
        'averaging_samples' => get_user_meta($user_id, 'age_estimator_averaging_samples', true) ?: 5,
        
        // Retail
        'retail_mode_enabled' => get_user_meta($user_id, 'age_estimator_retail_mode_enabled', true) === '1',
        'challenge_age' => get_user_meta($user_id, 'age_estimator_challenge_age', true) ?: 25,
        'enable_logging' => get_user_meta($user_id, 'age_estimator_enable_logging', true) === '1',
        'email_alerts' => get_user_meta($user_id, 'age_estimator_email_alerts', true) === '1',
        'staff_email' => get_user_meta($user_id, 'age_estimator_staff_email', true),
        
        // Privacy
        'privacy_mode' => get_user_meta($user_id, 'age_estimator_privacy_mode', true) === '1',
        'require_consent' => get_user_meta($user_id, 'age_estimator_require_consent', true) !== '0',
        'data_retention' => get_user_meta($user_id, 'age_estimator_data_retention', true) ?: 0,
        
        // Notifications
        'enable_sounds' => get_user_meta($user_id, 'age_estimator_enable_sounds', true) === '1',
        'sound_volume' => get_user_meta($user_id, 'age_estimator_sound_volume', true) ?: 70,
        'pass_sound' => get_user_meta($user_id, 'age_estimator_pass_sound', true) ?: 'default',
        'fail_sound' => get_user_meta($user_id, 'age_estimator_fail_sound', true) ?: 'default',
        
        // Advanced
        'detection_mode' => get_user_meta($user_id, 'age_estimator_detection_mode', true) ?: 'local',
        'cache_duration' => get_user_meta($user_id, 'age_estimator_cache_duration', true) ?: 30,
        'hardware_accel' => get_user_meta($user_id, 'age_estimator_hardware_accel', true) !== '0',
        'emotion_detection' => get_user_meta($user_id, 'age_estimator_emotion_detection', true) === '1',
        'gender_detection' => get_user_meta($user_id, 'age_estimator_gender_detection', true) === '1',
    );
    
    return $settings;
}

// Usage
$settings = get_all_age_estimator_settings();
echo $settings['minimum_age']; // 18
```

#### Check Retail PIN
```php
function verify_retail_pin($user_id, $entered_pin) {
    $stored_hash = get_user_meta($user_id, 'age_estimator_retail_pin', true);
    if (empty($stored_hash)) {
        return false;
    }
    return wp_check_password($entered_pin, $stored_hash);
}

// Usage
if (verify_retail_pin($user_id, '1234')) {
    // PIN is correct
}
```

### 2. JavaScript - Getting Settings in Frontend

#### Access Settings from Localized Data
```javascript
// These are automatically available when the age estimator scripts are loaded
if (window.ageEstimatorParams) {
    // Global settings (admin-configured)
    const globalMinAge = ageEstimatorParams.minimumAge;
    const retailMode = ageEstimatorParams.retailMode;
    const enableSounds = ageEstimatorParams.enableSounds;
    
    // User-specific settings (if logged in)
    if (ageEstimatorParams.userMeta) {
        const userFaceSensitivity = ageEstimatorParams.userMeta.faceTrackingDistance;
        const userRetailEnabled = ageEstimatorParams.userMeta.retailModeEnabled;
        const userAgeThreshold = ageEstimatorParams.userMeta.ageGatingThreshold;
    }
}
```

#### Access Enhanced Settings Manager
```javascript
// If on a page with the enhanced settings
if (window.ageEstimatorSettings) {
    // Get all current settings
    const allSettings = window.ageEstimatorSettings.settings.currentSettings;
    
    // Get specific settings
    const minAge = allSettings.minimum_age;
    const faceSensitivity = allSettings.face_sensitivity;
    const retailMode = allSettings.retail_mode_enabled;
    
    // Listen for settings changes
    jQuery(document).on('ageEstimator:settingsSaved', function(e, section, data) {
        console.log('Settings changed:', section, data);
        // React to changes
        if (section === 'detection') {
            updateDetectionParameters(data);
        }
    });
}
```

#### Get Settings via AJAX
```javascript
function getUserSettings() {
    jQuery.ajax({
        url: ageEstimatorParams.ajaxUrl,
        type: 'POST',
        data: {
            action: 'age_estimator_get_user_settings',
            nonce: ageEstimatorParams.nonce
        },
        success: function(response) {
            if (response.success) {
                const settings = response.data;
                console.log('User settings:', settings);
                
                // Use settings
                applyUserSettings(settings);
            }
        }
    });
}

function applyUserSettings(settings) {
    // Apply to your age detection logic
    if (window.faceDetector) {
        faceDetector.setSensitivity(settings.face_sensitivity);
        faceDetector.setInterval(settings.detection_interval);
    }
    
    // Apply to UI
    if (settings.show_results) {
        jQuery('#age-result').show();
    }
    
    // Apply sounds
    if (settings.enable_sounds) {
        window.soundVolume = settings.sound_volume / 100;
    }
}
```

### 3. REST API Access

#### Get Settings via REST
```javascript
// Using WordPress REST API
fetch('/wp-json/age-estimator/v1/user-settings', {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
})
.then(response => response.json())
.then(settings => {
    console.log('Settings from REST:', settings);
});
```

#### Update Settings via REST
```javascript
fetch('/wp-json/age-estimator/v1/user-settings', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        section: 'general',
        settings: {
            minimum_age: 21,
            show_results: true
        }
    })
})
.then(response => response.json())
.then(result => {
    console.log('Settings updated:', result);
});
```

### 4. In Your Age Detection Code

#### Example: photo-age-estimator-continuous.js
```javascript
// At the top of your age detection script
(function() {
    // Get user settings
    let userSettings = {
        faceSensitivity: 0.4,
        minAge: 18,
        retailMode: false,
        enableSounds: false,
        detectionInterval: 500,
        minFaceSize: 150,
        maxFaceSize: 350,
        showResults: true,
        averagingSamples: 5
    };
    
    // Override with user-specific settings if available
    if (window.ageEstimatorParams && window.ageEstimatorParams.userMeta) {
        const meta = window.ageEstimatorParams.userMeta;
        userSettings.faceSensitivity = parseFloat(meta.faceTrackingDistance) || userSettings.faceSensitivity;
        userSettings.minAge = parseInt(meta.ageGatingThreshold) || userSettings.minAge;
        userSettings.retailMode = meta.retailModeEnabled;
    }
    
    // Apply settings to face detection
    async function detectFaces() {
        const detections = await faceapi
            .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({
                inputSize: 416,
                scoreThreshold: userSettings.faceSensitivity // Use user setting
            }))
            .withFaceLandmarks()
            .withAgeAndGender();
        
        // Check age against user's minimum
        if (detections.length > 0) {
            const age = Math.round(detections[0].age);
            
            if (age < userSettings.minAge) {
                handleAgeFail(age);
            } else {
                handleAgePass(age);
            }
        }
    }
    
    // Set detection interval from user settings
    setInterval(detectFaces, userSettings.detectionInterval);
})();
```

### 5. Common Setting Names Reference

```php
// Setting Meta Keys (add 'age_estimator_' prefix)
$setting_keys = array(
    // General
    'show_results',           // bool: Show age on screen
    'show_confidence',        // bool: Show confidence score
    'result_display_time',    // int: Seconds to display result
    'age_gating_enabled',     // bool: Enable age restriction
    'minimum_age',           // int: Minimum age requirement
    
    // Detection
    'face_sensitivity',       // float: 0.1-1.0 detection threshold
    'detection_interval',     // int: MS between detections
    'min_face_size',         // int: Minimum face size in pixels
    'max_face_size',         // int: Maximum face size in pixels
    'face_tracking',         // bool: Enable face tracking
    'multi_face',            // bool: Detect multiple faces
    'averaging_samples',     // int: Number of samples to average
    
    // Retail
    'retail_mode_enabled',   // bool: Enable retail/Challenge 25
    'challenge_age',         // int: Age threshold for challenge
    'retail_pin',            // string: Hashed PIN
    'enable_logging',        // bool: Log transactions
    'email_alerts',          // bool: Send email notifications
    'staff_email',           // string: Email for alerts
    
    // Privacy
    'privacy_mode',          // bool: Blur faces in images
    'require_consent',       // bool: Show consent dialog
    'data_retention',        // int: Hours to keep data
    'session_timeout',       // int: Minutes before timeout
    'two_factor',            // bool: Require 2FA
    
    // Notifications
    'enable_sounds',         // bool: Play sound effects
    'sound_volume',          // int: 0-100 volume level
    'pass_sound',            // string: Sound type for pass
    'fail_sound',            // string: Sound type for fail
    'screen_flash',          // bool: Flash screen on result
    'success_color',         // string: Hex color for success
    'failure_color',         // string: Hex color for failure
    
    // Advanced
    'detection_mode',        // string: 'local', 'aws', 'hybrid'
    'cache_duration',        // int: Seconds to cache results
    'hardware_accel',        // bool: Use GPU acceleration
    'emotion_detection',     // bool: Detect emotions
    'gender_detection',      // bool: Detect gender
    'facial_attributes'      // bool: Detect attributes
);
```

### 6. Quick Helper Functions

```php
// PHP Helper Functions - Add to your functions.php or plugin file

function age_estimator_get_setting($setting_name, $user_id = null, $default = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    $value = get_user_meta($user_id, 'age_estimator_' . $setting_name, true);
    return $value !== '' ? $value : $default;
}

function age_estimator_update_setting($setting_name, $value, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    return update_user_meta($user_id, 'age_estimator_' . $setting_name, $value);
}

function age_estimator_is_retail_mode($user_id = null) {
    return age_estimator_get_setting('retail_mode_enabled', $user_id, false) === '1';
}

function age_estimator_get_minimum_age($user_id = null) {
    return intval(age_estimator_get_setting('minimum_age', $user_id, 18));
}

// Usage examples
$min_age = age_estimator_get_minimum_age();
$is_retail = age_estimator_is_retail_mode();
$face_sensitivity = age_estimator_get_setting('face_sensitivity', null, 0.4);
```

```javascript
// JavaScript Helper Functions

const AgeEstimatorSettings = {
    get: function(settingName, defaultValue) {
        if (window.ageEstimatorParams && window.ageEstimatorParams.userMeta) {
            return window.ageEstimatorParams.userMeta[settingName] || defaultValue;
        }
        return defaultValue;
    },
    
    getMinAge: function() {
        return parseInt(this.get('ageGatingThreshold', 18));
    },
    
    isRetailMode: function() {
        return this.get('retailModeEnabled', false) === true;
    },
    
    getFaceSensitivity: function() {
        return parseFloat(this.get('faceTrackingDistance', 0.4));
    },
    
    shouldPlaySounds: function() {
        return window.ageEstimatorParams.enableSounds === '1';
    }
};

// Usage
const minAge = AgeEstimatorSettings.getMinAge();
if (AgeEstimatorSettings.isRetailMode()) {
    console.log('Retail mode is active');
}
```

### 7. WordPress Hooks to React to Setting Changes

```php
// Add to your plugin to react when settings change
add_action('updated_user_meta', 'handle_age_estimator_setting_change', 10, 4);
function handle_age_estimator_setting_change($meta_id, $user_id, $meta_key, $meta_value) {
    if (strpos($meta_key, 'age_estimator_') === 0) {
        // A setting was changed
        $setting_name = str_replace('age_estimator_', '', $meta_key);
        
        // Do something based on the setting
        switch($setting_name) {
            case 'retail_mode_enabled':
                if ($meta_value === '1') {
                    // Retail mode was enabled, maybe send notification
                    do_action('age_estimator_retail_mode_enabled', $user_id);
                }
                break;
                
            case 'email_alerts':
                if ($meta_value === '1') {
                    // Email alerts enabled, verify email is set
                    $email = get_user_meta($user_id, 'age_estimator_staff_email', true);
                    if (empty($email)) {
                        // Prompt user to set email
                    }
                }
                break;
        }
        
        // Log the change if needed
        error_log("Age Estimator setting changed: $setting_name = $meta_value for user $user_id");
    }
}
```

## Save this reference - you'll use these patterns frequently when working with the settings!
