# Email Force Send Fix Instructions

## The Problem
The "Force Send Today's Emails" button only sends emails to users who have API activity on TODAY's date. If there's no activity today (which is common), it will send 0 emails even though you have historical data.

## Quick Solution

### Option 1: Include the Enhanced Fix (Recommended)
Add this line to your main plugin file `age-estimator.php` after the opening PHP tag:

```php
// Include enhanced email force send fix
if (file_exists(plugin_dir_path(__FILE__) . 'enhanced-email-force-send.php')) {
    require_once plugin_dir_path(__FILE__) . 'enhanced-email-force-send.php';
}
```

This will:
- Add a new "Email Debug" menu item to help diagnose issues
- Allow you to send emails for any specific date
- Automatically find the most recent date with activity if today has none

### Option 2: Quick Test
1. Go to **Age Estimator → Email Debug** in your WordPress admin
2. You'll see:
   - A summary of recent activity by date
   - Which users are eligible for emails
   - Options to send emails for yesterday or other dates

### Option 3: Manual Database Check
Run this SQL query to see which dates have user activity:

```sql
SELECT call_date, COUNT(*) as total_calls, COUNT(DISTINCT user_id) as unique_users
FROM wp_age_estimator_api_calls
WHERE user_id > 0
GROUP BY call_date
ORDER BY call_date DESC
LIMIT 10;
```

## Understanding the Issue

The compliance emailer only sends emails to users who have activity on the exact date you're trying to send for. This means:

- **Daily emails**: Only users who used the age verifier TODAY
- **Force send**: Also only looks at TODAY's activity
- **No activity today = 0 emails sent**

## Permanent Fix Options

### 1. Send Yesterday's Emails Instead
Modify the force send to always look at yesterday's data (when you know there was activity).

### 2. Send Most Recent Activity
Modify the system to find the most recent date with activity and send for that date.

### 3. Batch Historical Emails
Send emails for all dates that haven't been processed yet.

## Testing Email Configuration

To verify your email settings are working:

1. The test email feature should work (it creates dummy data)
2. Check your WordPress email logs or SMTP plugin logs
3. Verify the from/reply-to addresses are valid
4. Check spam folders

## Next Steps

1. Include the enhanced fix file in your plugin
2. Use the Email Debug page to diagnose issues
3. Send emails for dates that actually have activity
4. Consider scheduling emails to run the morning after activity (e.g., send yesterday's report today)
