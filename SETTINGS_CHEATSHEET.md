# 🚀 QUICK SETTINGS CHEAT SHEET

## Most Common Usage Patterns

### PHP - Get Settings
```php
// Quick one-liner for any setting
$value = get_user_meta(get_current_user_id(), 'age_estimator_SETTING_NAME', true);

// Common settings
$min_age = get_user_meta(get_current_user_id(), 'age_estimator_minimum_age', true) ?: 18;
$retail_mode = get_user_meta(get_current_user_id(), 'age_estimator_retail_mode_enabled', true) === '1';
$face_sensitivity = get_user_meta(get_current_user_id(), 'age_estimator_face_sensitivity', true) ?: 0.4;
```

### JavaScript - Get Settings
```javascript
// From localized params (already loaded)
const minAge = ageEstimatorParams.userMeta.ageGatingThreshold;
const retailMode = ageEstimatorParams.userMeta.retailModeEnabled;
const faceSensitivity = ageEstimatorParams.userMeta.faceTrackingDistance;

// Quick check
if (ageEstimatorParams.retailMode === '1') {
    // Retail mode is ON
}
```

## Setting Names Quick Reference

| Feature | Setting Key | Type | Default | Range/Values |
|---------|------------|------|---------|--------------|
| **GENERAL** |
| Show Results | `show_results` | bool | true | true/false |
| Show Confidence | `show_confidence` | bool | true | true/false |
| Result Display Time | `result_display_time` | int | 5 | 1-10 seconds |
| Age Gating Enabled | `age_gating_enabled` | bool | false | true/false |
| Minimum Age | `minimum_age` | int | 18 | 13-25 |
| **DETECTION** |
| Face Sensitivity | `face_sensitivity` | float | 0.4 | 0.1-1.0 |
| Detection Interval | `detection_interval` | int | 500 | 100-2000 ms |
| Min Face Size | `min_face_size` | int | 150 | 50-300 px |
| Max Face Size | `max_face_size` | int | 350 | 200-500 px |
| Face Tracking | `face_tracking` | bool | true | true/false |
| Multi Face | `multi_face` | bool | false | true/false |
| Averaging Samples | `averaging_samples` | int | 5 | 1-10 |
| **RETAIL MODE** |
| Retail Mode Enabled | `retail_mode_enabled` | bool | false | true/false |
| Challenge Age | `challenge_age` | int | 25 | 18-30 |
| Enable Logging | `enable_logging` | bool | false | true/false |
| Email Alerts | `email_alerts` | bool | false | true/false |
| Staff Email | `staff_email` | string | - | email |
| Retail PIN | `retail_pin` | string | - | 4 digits (hashed) |
| **PRIVACY** |
| Privacy Mode | `privacy_mode` | bool | false | true/false |
| Require Consent | `require_consent` | bool | true | true/false |
| Data Retention | `data_retention` | int | 0 | 0-720 hours |
| Session Timeout | `session_timeout` | int | 15 | 5-60 minutes |
| **NOTIFICATIONS** |
| Enable Sounds | `enable_sounds` | bool | false | true/false |
| Sound Volume | `sound_volume` | int | 70 | 0-100 |
| Pass Sound | `pass_sound` | string | default | default/bell/success |
| Fail Sound | `fail_sound` | string | default | default/buzzer/warning |
| Screen Flash | `screen_flash` | bool | false | true/false |
| Success Color | `success_color` | string | #28a745 | hex color |
| Failure Color | `failure_color` | string | #dc3545 | hex color |
| **ADVANCED** |
| Detection Mode | `detection_mode` | string | local | local/aws/hybrid |
| Cache Duration | `cache_duration` | int | 30 | 0-3600 seconds |
| Hardware Accel | `hardware_accel` | bool | true | true/false |
| Emotion Detection | `emotion_detection` | bool | false | true/false |
| Gender Detection | `gender_detection` | bool | false | true/false |

## Copy-Paste Functions

### PHP All-Settings Getter
```php
function get_age_settings($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    $prefix = 'age_estimator_';
    $settings = [];
    $keys = ['show_results','show_confidence','result_display_time','age_gating_enabled',
             'minimum_age','face_sensitivity','detection_interval','min_face_size',
             'max_face_size','face_tracking','multi_face','averaging_samples',
             'retail_mode_enabled','challenge_age','enable_logging','email_alerts',
             'staff_email','privacy_mode','require_consent','data_retention',
             'session_timeout','enable_sounds','sound_volume','pass_sound',
             'fail_sound','screen_flash','success_color','failure_color',
             'detection_mode','cache_duration','hardware_accel','emotion_detection',
             'gender_detection'];
    foreach ($keys as $key) {
        $settings[$key] = get_user_meta($user_id, $prefix . $key, true);
    }
    return $settings;
}
```

### JavaScript Settings Object
```javascript
const AgeSettings = {
    get: (key) => ageEstimatorParams?.userMeta?.[key] || null,
    minAge: () => parseInt(AgeSettings.get('ageGatingThreshold')) || 18,
    retailMode: () => AgeSettings.get('retailModeEnabled') === true,
    faceSensitivity: () => parseFloat(AgeSettings.get('faceTrackingDistance')) || 0.4,
    soundsEnabled: () => ageEstimatorParams?.enableSounds === '1',
    all: () => ageEstimatorParams?.userMeta || {}
};

// Usage: 
// const age = AgeSettings.minAge();
// if (AgeSettings.retailMode()) { ... }
```

## In Your Age Detection Code

```javascript
// At initialization
const settings = {
    minAge: AgeSettings.minAge(),
    sensitivity: AgeSettings.faceSensitivity(),
    retail: AgeSettings.retailMode()
};

// In detection
if (estimatedAge < settings.minAge) {
    // FAIL
    if (settings.retail) {
        // Show "Check ID" message
    }
} else {
    // PASS
}
```

## Database Queries

```sql
-- Get all settings for a user
SELECT meta_key, meta_value 
FROM wp_usermeta 
WHERE user_id = 123 
AND meta_key LIKE 'age_estimator_%';

-- Find all users with retail mode enabled
SELECT user_id 
FROM wp_usermeta 
WHERE meta_key = 'age_estimator_retail_mode_enabled' 
AND meta_value = '1';
```

## WP-CLI Commands

```bash
# Get a user's setting
wp user meta get 123 age_estimator_minimum_age

# Set a user's setting
wp user meta update 123 age_estimator_minimum_age 21

# List all age estimator settings for a user
wp user meta list 123 --keys=age_estimator_*
```

## Testing in Console

```javascript
// Check current settings in browser console
console.log('All settings:', ageEstimatorParams);
console.log('User settings:', ageEstimatorParams.userMeta);
console.log('Min age:', ageEstimatorParams.userMeta.ageGatingThreshold);
console.log('Retail mode:', ageEstimatorParams.retailMode);

// If on settings page
if (window.ageEstimatorSettings) {
    console.log('Current settings:', ageEstimatorSettings.settings.currentSettings);
}
```

---
📌 **Save this file for quick reference!**
Location: `/wp-content/plugins/Age-estimator-live/SETTINGS_CHEATSHEET.md`
