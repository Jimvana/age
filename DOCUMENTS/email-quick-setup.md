# Quick Setup Guide: Compliance Email Automation

## 1. Activation

After updating the plugin, the email system is automatically initialized. No additional activation needed.

## 2. Basic Configuration (5 minutes)

1. **Go to WordPress Admin → Age Estimator → Email Settings**

2. **Configure these essential settings:**
   - Enable Daily Emails: **Yes**
   - Send Time: **23:00** (11 PM) or your preferred time
   - From Name: Your site name
   - From Email: noreply@yourdomain.com

3. **Click "Save Changes"**

## 3. Test the System (2 minutes)

1. Click **"Send Test Email to Admin"** button
2. Check your admin email inbox
3. Verify the email looks correct

## 4. User Instructions

### For Users to Manage Preferences:

**Option A: User Profile**
- Go to Users → Your Profile
- Find "Age Verification Compliance Reports" section
- Toggle preferences

**Option B: Add Frontend Form**
Add this shortcode to any page:
```
[age_estimator_email_preferences]
```

## 5. Monitoring

- Check **Email Settings** page for recent email logs
- Emails only send to users who had activity that day
- System runs automatically at scheduled time

## That's It!

The system is now active and will:
- ✅ Send daily emails at your configured time
- ✅ Only email users with verification activity
- ✅ Include detailed compliance logs
- ✅ Allow users to opt-out if desired

## Need Help?

- Check the full documentation at `/DOCUMENTS/compliance-email-automation.md`
- Review email logs in Email Settings page
- Test with "Force Send Today's Emails Now" button
