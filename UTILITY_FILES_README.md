# Utility Files README

## Why were these files disabled?

Several utility files in the Age Estimator plugin have been disabled (renamed with `_` prefix and `.disabled` suffix) to prevent PHP Fatal errors. These files were attempting to use plugin classes before they were loaded, causing the WordPress site to crash.

## Disabled Files

### 1. `_compliance-email-fix.php.disabled`
- **Purpose**: Fixes the process_emails_by_frequency method for compliance emails
- **Issue**: Was using `AgeEstimatorComplianceEmailer` class before it was loaded
- **Usage**: See `COMPLIANCE_EMAIL_FIX_README.md` for implementation instructions

### 2. `_enhanced-email-force-send.php.disabled`
- **Purpose**: Allows sending emails for any date, not just today
- **Issue**: Was using `AgeEstimatorAdminEmailSettings`, `AgeEstimatorAPITracker`, and `AgeEstimatorComplianceEmailer` classes before they were loaded
- **Usage**: Include manually after the plugin is fully loaded

### 3. `_compliance-api-bridge.php.disabled`
- **Purpose**: Bridge between compliance checks and API tracking
- **Issue**: Potential class loading issues
- **Usage**: Include in theme's functions.php if needed

### 4. `_debug-email-force-send.php.disabled`
- **Purpose**: Debug script for email force send issues
- **Issue**: Was being included by enhanced-email-force-send.php
- **Usage**: For debugging only, include manually when needed

### 5. `_test-email-system.php.disabled`
- **Purpose**: Test email functionality
- **Issue**: Direct class usage and potential loading issues
- **Usage**: Access via admin URL with `?test_age_estimator_emails=1` after manual inclusion

### 6. `_test-email.php.disabled`
- **Purpose**: Simple email test accessible via direct URL
- **Issue**: Loads WordPress directly, bypassing normal plugin loading
- **Usage**: Not recommended - use the admin interface instead

### 7. `_debug-email.php.disabled`
- **Purpose**: Debug script for email issues
- **Issue**: Loads WordPress directly
- **Usage**: For debugging only

### 8. `_update-database.php.disabled`
- **Purpose**: Manual database update script
- **Issue**: Loads WordPress directly
- **Usage**: Run once if database updates are needed

## How to Use These Files Safely

### Option 1: Include in Theme's functions.php
```php
// Wait for plugins to be loaded
add_action('plugins_loaded', function() {
    // Include the utility file you need
    $file = WP_PLUGIN_DIR . '/Age-estimator-live/_compliance-email-fix.php.disabled';
    if (file_exists($file)) {
        require_once $file;
    }
}, 20);
```

### Option 2: Create as MU-Plugin
1. Create directory: `/wp-content/mu-plugins/`
2. Create a new file with a proper header:
```php
<?php
/**
 * Plugin Name: Age Estimator Email Fixes
 */

// Wait for plugins to load
add_action('plugins_loaded', function() {
    // Include needed utilities
    require_once WP_PLUGIN_DIR . '/Age-estimator-live/_compliance-email-fix.php.disabled';
}, 20);
```

### Option 3: Manual Execution (for test/debug files)
For files like `_test-email.php.disabled` or `_update-database.php.disabled`:
1. Temporarily rename to remove `.disabled` extension
2. Access via browser
3. Rename back to `.disabled` when done

## Important Notes

1. **Never include these files directly in the main plugin** - they will cause fatal errors
2. **Always check class existence** before using plugin classes:
   ```php
   if (class_exists('AgeEstimatorComplianceEmailer')) {
       // Safe to use the class
   }
   ```
3. **Use proper WordPress hooks** to ensure proper loading order
4. **Test on staging first** before using in production

## File Status Summary

| File | Purpose | Safe to Use? | How to Use |
|------|---------|--------------|------------|
| _compliance-email-fix.php.disabled | Email fix | Yes, with care | Via functions.php or mu-plugin |
| _enhanced-email-force-send.php.disabled | Enhanced email sending | Yes, with care | Via functions.php or mu-plugin |
| _compliance-api-bridge.php.disabled | API tracking bridge | Yes | Via functions.php |
| _debug-email-force-send.php.disabled | Debug tool | Debug only | Manual inclusion |
| _test-email-system.php.disabled | Email testing | Debug only | Via admin URL |
| _test-email.php.disabled | Simple test | Not recommended | Use admin interface |
| _debug-email.php.disabled | Debug tool | Debug only | Manual execution |
| _update-database.php.disabled | DB updates | Once only | Manual execution |

Last Updated: August 4, 2025
