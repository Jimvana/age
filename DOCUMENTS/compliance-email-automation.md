# Age Estimator Compliance Email Automation

This documentation explains how to configure and use the automated compliance email system for the Age Estimator Live plugin.

## Overview

The compliance email system automatically sends daily reports to users containing their age verification activity. This helps with:
- Regulatory compliance tracking
- User transparency
- Activity monitoring
- Audit trail maintenance

## Features

### 1. Automated Daily Emails
- Sends compliance logs to users at a scheduled time each day
- Only sends to users who had activity that day
- Includes detailed verification logs with timestamps

### 2. User Preferences
- Users can opt-in/out of emails
- Choice of frequency: Daily, Weekly, or Monthly
- Available via user profile or shortcode

### 3. Admin Controls
- Enable/disable system-wide emails
- Configure send time
- Send test emails
- Force manual send
- View email logs

### 4. Email Content
- Professional HTML emails
- Summary statistics
- Detailed activity logs
- Session tracking
- Success/failure status

## Configuration

### Step 1: Enable the System

1. Navigate to **Age Estimator → Email Settings** in WordPress admin
2. Set "Enable Daily Emails" to "Yes"
3. Configure send time (default: 11:00 PM)
4. Save settings

### Step 2: Configure Email Settings

- **From Name**: The sender name for emails
- **From Email**: The sender email address
- **Reply-To**: Optional reply-to address

### Step 3: Test the System

1. Click "Send Test Email to Admin" to verify emails work
2. Check that the test email arrives with proper formatting
3. Use "Force Send Today's Emails Now" to test the batch process

## User Management

### User Profile Settings

Users can manage their preferences in their WordPress profile:
1. Navigate to **Users → Your Profile**
2. Find "Age Verification Compliance Reports" section
3. Toggle email preferences and set frequency

### Shortcode for Frontend

Add email preferences to any page:
```
[age_estimator_email_preferences]
```

This displays a form where users can:
- Enable/disable emails
- Choose frequency (daily/weekly/monthly)
- Save preferences via AJAX

## Email Schedule

### Daily Schedule
- Emails sent at configured time each day
- Only users with activity receive emails
- Includes all verification attempts for that day

### Weekly Schedule (User Preference)
- Sent on Mondays
- Includes summary of past week's activity

### Monthly Schedule (User Preference)
- Sent on 1st of each month
- Includes summary of previous month's activity

## Email Content Structure

Each email includes:

1. **Header**
   - Site name and branding
   - Report date

2. **Summary Statistics**
   - Total verifications
   - Faces detected
   - Successful attempts
   - Failed attempts

3. **Detailed Log Table**
   - Timestamp for each verification
   - Number of faces detected
   - Success/failure status
   - Error messages (if any)
   - Session ID (truncated)

4. **Footer**
   - Site information
   - Timezone notice
   - Unsubscribe instructions

## Customization

### Modifying Email Template

To customize the email template, edit the `get_email_body()` method in:
```
/includes/class-compliance-emailer.php
```

### Adding Custom Data

To include additional data in emails:

1. Modify the `get_user_detailed_logs()` method to fetch more data
2. Update the email template to display new fields
3. Ensure proper sanitization of all output

### Styling

The emails use inline CSS for maximum compatibility. Key classes:
- `.header` - Email header section
- `.summary` - Statistics summary
- `.stats-grid` - Statistics layout
- `.details` - Detailed logs section

## Troubleshooting

### Emails Not Sending

1. Check WordPress cron is running:
   - Install WP Crontrol plugin to verify
   - Ensure `age_estimator_send_daily_compliance_emails` is scheduled

2. Verify email configuration:
   - Test WordPress email with other plugins
   - Check spam folders
   - Verify SMTP settings if using custom mail configuration

3. Check user preferences:
   - Ensure users haven't opted out
   - Verify users have valid email addresses

### Performance Issues

For sites with many users:
1. Consider using a proper email queue plugin
2. Implement batch processing for large user bases
3. Use external email service (SendGrid, AWS SES, etc.)

## Database Tables

The system uses existing tables:
- `wp_age_estimator_api_calls` - Stores all verification attempts
- `wp_usermeta` - Stores user email preferences

## Hooks and Filters

### Available Filters

```php
// Modify email subject
add_filter('age_estimator_compliance_email_subject', function($subject, $date) {
    return 'Custom Subject: ' . $subject;
}, 10, 2);

// Modify email headers
add_filter('age_estimator_compliance_email_headers', function($headers) {
    $headers[] = 'Bcc: admin@example.com';
    return $headers;
});

// Change data retention period (default: 90 days)
add_filter('age_estimator_stats_retention_days', function($days) {
    return 180; // Keep 6 months
});
```

### Available Actions

```php
// Run after emails are sent
add_action('age_estimator_daily_emails_sent', function($count) {
    error_log("Sent $count compliance emails");
});

// Run before sending individual email
add_action('age_estimator_before_send_email', function($user_id, $date) {
    // Custom logic here
}, 10, 2);
```

## Security Considerations

1. **Data Privacy**
   - Emails contain sensitive compliance data
   - Ensure secure email transmission
   - Consider encryption for sensitive deployments

2. **User Permissions**
   - Only users can modify their own preferences
   - Admins can view logs but not individual user data
   - Implement additional access controls as needed

3. **Data Retention**
   - Compliance logs auto-delete after 90 days (configurable)
   - Email logs kept for 30 days
   - Adjust based on regulatory requirements

## Support

For issues or questions:
1. Check WordPress error logs
2. Enable WP_DEBUG for detailed error messages
3. Test with minimal plugin configuration
4. Verify server email capabilities

## Future Enhancements

Potential improvements:
- PDF attachments for compliance reports
- Custom email templates per user role
- Integration with third-party email services
- Real-time notifications for specific events
- Mobile app push notifications
