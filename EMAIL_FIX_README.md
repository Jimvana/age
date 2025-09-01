# Age Estimator Email Issue - Solution Guide

## Problem Summary

The "Force Send Today's Emails Now" button shows 0 emails sent because:

1. **Wrong Table Query**: The email system is looking for data in `wp_age_estimator_api_calls` table, but your compliance checks are stored in `wp_age_estimator_checks` table.

2. **User Identification**: The system only sends emails to registered WordPress users with valid email addresses, but your compliance checks might be recording staff names instead of user IDs.

## Quick Solution

### Step 1: Test Your Email System

Visit this URL to test if WordPress can send emails:
```
https://yourdomain.com/wp-content/plugins/Age-estimator-live/test-email.php
```

### Step 2: Apply the Fix

Add this line to your theme's `functions.php` file:

```php
// Fix Age Estimator email sending
if (file_exists(WP_PLUGIN_DIR . '/Age-estimator-live/compliance-email-fix.php')) {
    require_once(WP_PLUGIN_DIR . '/Age-estimator-live/compliance-email-fix.php');
}
```

### Step 3: Test the Fix

After applying the fix, you can test it by:

1. Going to the Email Settings page
2. Clicking "Force Send Today's Emails Now"
3. Or visiting: `https://yourdomain.com/wp-admin/admin.php?page=age-estimator-email-settings&test_compliance_email=1`

## What the Fix Does

1. **Queries the Correct Table**: Looks for compliance checks in `age_estimator_checks` instead of `api_calls`
2. **Better User Matching**: Attempts to match staff names to WordPress users
3. **Proper Email Formatting**: Formats compliance check data into the email report

## Debugging Tools

I've created several debugging tools:

1. **Email Debug Tool**: `/wp-content/plugins/Age-estimator-live/debug-email.php`
   - Shows email configuration
   - Displays database statistics
   - Tests email sending

2. **Simple Test**: `/wp-content/plugins/Age-estimator-live/test-email.php`
   - Quick email test
   - Database check
   - Shows recent compliance checks

## Important Notes

### For Emails to Work:

1. **Users must be logged in** when performing age checks, OR
2. **Staff names must match** WordPress user display names
3. **Users must have valid email addresses**
4. **WP Mail SMTP** must be properly configured

### Current Limitations:

- Guest users (not logged in) cannot receive emails
- The system needs either a user_id or a staff_member name that matches a WordPress user

## Long-term Solution

Consider modifying the age check process to:

1. Always track the WordPress user_id when someone is logged in
2. Create a mapping between staff names and WordPress user accounts
3. Ensure the API tracker properly logs all age verification attempts

## Support

If emails still aren't sending after applying this fix:

1. Check that WP Mail SMTP is sending test emails successfully
2. Verify users have valid email addresses
3. Ensure compliance checks are being recorded with proper user information
4. Check WordPress error logs for any email-related errors
