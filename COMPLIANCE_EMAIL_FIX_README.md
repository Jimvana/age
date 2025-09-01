# Compliance Email Fix - README

## What is this file?

The file `_compliance-email-fix.php.disabled` is a patch for the Age Estimator plugin's compliance email functionality. It's designed to fix issues with the email sending process.

## Why was it disabled?

This file was causing a PHP Fatal Error because it was trying to use the `AgeEstimatorComplianceEmailer` class before it was loaded. WordPress was attempting to load this file directly, which caused the error.

## How to use this fix:

If you need to use this compliance email fix, you have several options:

### Option 1: Include it in your theme's functions.php
Add this line to your theme's functions.php file:
```php
require_once WP_PLUGIN_DIR . '/Age-estimator-live/_compliance-email-fix.php.disabled';
```

### Option 2: Create it as a mu-plugin
1. Create a directory: `/wp-content/mu-plugins/` (if it doesn't exist)
2. Copy the contents of `_compliance-email-fix.php.disabled` to a new file in that directory
3. Name it something like `age-estimator-email-fix.php`

### Option 3: Include it in the main plugin
If you're a developer, you can add this to the main plugin's `load_includes()` method in `age-estimator.php`:
```php
// Load compliance email fix
$email_fix_file = AGE_ESTIMATOR_PATH . '_compliance-email-fix.php.disabled';
if (file_exists($email_fix_file)) {
    require_once $email_fix_file;
}
```

## What does this fix do?

This fix modifies the behavior of the compliance email system to:
1. Query the compliance checks table directly instead of making API calls
2. Send daily compliance reports to users who had age verification checks
3. Include detailed statistics and logs in the emails

## Important Notes:

- The file has been renamed with `.disabled` extension to prevent WordPress from loading it automatically
- The file includes safety checks to ensure it only loads when the Age Estimator plugin is active
- It uses WordPress hooks to modify the emailer behavior without directly editing the core plugin files
