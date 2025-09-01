# Age Estimator Settings Fix - Manual Installation Guide

## Problem Summary
The `[age_estimator_settings_enhanced]` shortcode is not saving settings due to missing render methods in the `AgeEstimatorUserSettingsEnhanced` class.

## Root Cause
The enhanced settings class is calling render methods (`render_detection_fields`, `render_retail_fields`, etc.) that don't exist, causing PHP fatal errors and preventing settings from saving.

## Solution

### Option 1: Automatic Installation (Recommended)
Run the installation script:
```bash
cd /Users/video/DevKinsta/public/local-model-age-estin/wp-content/plugins/Age-estimator-live/includes/user-settings/fixed
chmod +x install-fix.sh
./install-fix.sh
```

### Option 2: Manual Installation

1. **Backup the original file:**
```bash
cp /Users/video/DevKinsta/public/local-model-age-estin/wp-content/plugins/Age-estimator-live/includes/user-settings/class-user-settings-enhanced.php /Users/video/DevKinsta/public/local-model-age-estin/wp-content/plugins/Age-estimator-live/includes/user-settings/class-user-settings-enhanced-backup.php
```

2. **Replace the original file:**
   - Copy the contents from `fixed/class-user-settings-enhanced-fixed.php`
   - Replace the entire content of `class-user-settings-enhanced.php`
   - Change the class name from `AgeEstimatorUserSettingsEnhancedFixed` to `AgeEstimatorUserSettingsEnhanced`

3. **Clear any WordPress caches**

## What the Fix Includes

### Added Missing Methods:
- `render_detection_fields()` - Face detection settings
- `render_retail_fields()` - Retail mode and compliance settings
- `render_privacy_fields()` - Privacy and security settings  
- `render_notification_fields()` - Sound and visual notifications
- `render_advanced_fields()` - Advanced detection and experimental features

### Fixed AJAX Handlers:
- `save_user_settings()` - Properly saves settings to user meta
- `get_user_settings()` - Retrieves user settings
- `validate_user_pin()` - PIN validation for retail mode
- `test_detection()` - Camera detection testing
- `export_settings()` - Settings export functionality
- `import_settings()` - Settings import functionality
- `clear_user_data()` - Data clearing functionality

### Enhanced Error Handling:
- Added proper nonce verification
- Added user authentication checks
- Added logging for debugging
- Added input validation and sanitization

## Database Schema
The settings are stored as WordPress user meta with keys like:
- `age_estimator_face_sensitivity`
- `age_estimator_retail_mode_enabled`
- `age_estimator_minimum_age`
- etc.

**No database schema changes are needed** - the plugin uses the existing WordPress `wp_usermeta` table.

## Verification Steps

1. **Test the shortcode:**
   Add `[age_estimator_settings_enhanced]` to a page

2. **Check settings save:**
   - Log in as a user
   - Change some settings
   - Click "Save Changes"
   - Refresh the page and verify settings persist

3. **Check browser console:**
   - Open browser developer tools
   - Look for JavaScript errors
   - AJAX requests should return success responses

4. **Check PHP error logs:**
   - Look for PHP fatal errors related to missing methods
   - Should see successful database updates

## Troubleshooting

### If settings still don't save:
1. Check browser network tab for AJAX failures
2. Verify nonce is being generated correctly
3. Check user permissions
4. Clear WordPress object cache
5. Check PHP error logs

### If you need to rollback:
```bash
cp /Users/video/DevKinsta/public/local-model-age-estin/wp-content/plugins/Age-estimator-live/includes/user-settings/class-user-settings-enhanced-backup.php /Users/video/DevKinsta/public/local-model-age-estin/wp-content/plugins/Age-estimator-live/includes/user-settings/class-user-settings-enhanced.php
```

## Debug Commands

### Check user meta:
```sql
SELECT * FROM wp_usermeta WHERE meta_key LIKE 'age_estimator_%' AND user_id = [USER_ID];
```

### Enable WordPress debugging:
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Check AJAX endpoint:
Test direct AJAX call in browser console:
```javascript
jQuery.post(ajaxurl, {
    action: 'age_estimator_save_user_settings',
    nonce: ageEstimatorEnhanced.nonce,
    section: 'general',
    settings: {
        show_results: true,
        minimum_age: 21
    }
}, function(response) {
    console.log(response);
});
```
