# Compliance Log Management Feature

## Overview
We've added comprehensive compliance log management functionality to the Age Estimator Live plugin. This feature allows administrators to:

1. **Manually clear logs** - with options to clear all logs or logs older than a specified number of days
2. **Automatically clear logs** - with scheduled clearing on a daily, weekly, or monthly basis
3. **Configure retention periods** - specify how many days to keep logs before automatic deletion

## Files Added/Modified

### New Files:
1. `/includes/class-compliance-log-manager.php` - Main compliance log management class
2. `/js/admin-logs.js` - JavaScript for the admin interface

### Modified Files:
1. `/includes/admin-settings.php` - Added compliance log management UI to the logs page
2. `/age-estimator.php` - Added include for the compliance log manager

## Features

### Manual Log Clearing
- **Clear Old Logs** button - prompts for number of days and clears logs older than that
- **Clear All Logs** button - removes all compliance logs after double confirmation

### Automatic Log Clearing
- Enable/disable automatic clearing via checkbox
- Choose schedule: Daily, Weekly, or Monthly
- Set retention period (7-365 days)
- Uses WordPress cron system for scheduled tasks

### Settings
The following new options are registered:
- `age_estimator_auto_clear_logs` - Enable/disable automatic clearing
- `age_estimator_auto_clear_schedule` - Schedule frequency (daily/weekly/monthly)
- `age_estimator_log_retention_days` - Number of days to retain logs

## Usage

### Accessing the Feature
1. Go to WordPress Admin
2. Navigate to Age Estimator > Compliance Logs
3. The Log Management section appears at the top of the page

### Manual Clearing
1. Click "Clear Old Logs" to remove logs older than X days
2. Click "Clear All Logs" to remove all compliance logs (requires double confirmation)

### Automatic Clearing Setup
1. Check "Automatically clear old logs"
2. Select schedule frequency
3. Set retention period in days
4. Click "Save Auto-Clear Settings"

## Technical Details

### Database Operations
- Uses WordPress database API for all operations
- `TRUNCATE TABLE` for clearing all logs (better performance)
- Date-based `DELETE` queries for clearing old logs
- Proper sanitization and security checks

### Security
- Capability check: `manage_options` required
- Nonce verification for AJAX requests
- Double confirmation for destructive operations

### WordPress Integration
- Uses WordPress cron for scheduled tasks
- Custom cron schedule for monthly clearing
- Proper hook cleanup on deactivation

## Notes
- Log statistics are displayed showing total logs and date range
- Page refreshes automatically after successful log clearing
- Error messages displayed if operations fail
- All strings are translation-ready