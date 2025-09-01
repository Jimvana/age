# Kiosk Mode Implementation Guide

This guide will help you implement the "Kiosk Mode" feature for your Age Estimator plugin, which displays advertisement images when no face is detected.

## Features

1. **Advertisement Display**: Shows a customizable image when no face is detected
2. **Admin Panel Integration**: Easy configuration through WordPress admin
3. **Automatic Transitions**: Smoothly switches between ad display and age estimation
4. **Configurable Display Time**: Set how long age results are shown before returning to the ad
5. **Media Library Integration**: Upload ads directly from WordPress media library

## Step-by-Step Implementation

### Step 1: Update Admin Settings

1. Open `/wp-content/plugins/Age-estimator-live/includes/admin-settings.php`
2. In the `register_settings()` method, add the kiosk mode settings from `admin-settings-patch.php`
3. In the `settings_page()` method, add the HTML for kiosk settings after the Logo section

### Step 2: Update the Template

1. Open `/wp-content/plugins/Age-estimator-live/templates/photo-inline.php`
2. Update the opening `<div>` tag to include the kiosk data attributes
3. Add the kiosk display HTML after the camera placeholder div

### Step 3: Add JavaScript Functionality

1. Open `/wp-content/plugins/Age-estimator-live/js/photo-age-estimator-continuous.js`
2. Add the kiosk mode variables at the top of the file
3. Add the initialization code and kiosk functions from `javascript-additions.js`
4. Integrate the face detection and result display hooks

### Step 4: Add CSS Styles

1. Open `/wp-content/plugins/Age-estimator-live/css/photo-age-estimator.css`
2. Add all the styles from `kiosk-styles.css`

### Step 5: Update Admin JavaScript

1. Open `/wp-content/plugins/Age-estimator-live/js/admin.js`
2. Add the kiosk mode toggle and media uploader code from `admin-javascript.js`

## Configuration

After implementation, you can configure Kiosk Mode from the WordPress admin:

1. Go to **Age Estimator > Settings**
2. Find the **Kiosk Mode Settings** section
3. Enable **Kiosk Mode** by checking the checkbox
4. Upload or enter the URL of your advertisement image
5. Set the display time (how long to show age results before returning to the ad)
6. Save your settings

## How It Works

1. **Initial State**: When kiosk mode is enabled, the advertisement image is displayed
2. **Face Detection**: When a face is detected, the ad is hidden and the camera view is shown
3. **Age Estimation**: The age is estimated and displayed as normal
4. **Return to Ad**: After the configured display time, the system returns to showing the advertisement
5. **Continuous Monitoring**: The system continues to monitor for new faces

## Testing

1. Enable Kiosk Mode in the admin panel
2. Upload a test advertisement image
3. Set display time to 5 seconds for testing
4. Visit the page with the `[age_estimator]` shortcode
5. Verify the ad displays initially
6. Step in front of the camera to trigger face detection
7. Confirm the ad disappears and age estimation works
8. Wait for the configured time to see the ad return

## Troubleshooting

### Ad Not Displaying
- Check that Kiosk Mode is enabled in settings
- Verify the image URL is correct and accessible
- Check browser console for JavaScript errors

### Ad Not Hiding When Face Detected
- Ensure face detection is working properly
- Check that the `hideKioskDisplay()` function is being called
- Verify the kiosk display element exists in the DOM

### Results Not Returning to Ad
- Check the display time setting
- Verify `scheduleReturnToKiosk()` is being called after results
- Check for JavaScript errors preventing the timeout

## Customization Options

### Different Ad Formats
You can modify the kiosk display to support:
- Video advertisements
- Rotating image galleries
- HTML content/animations

### Advanced Scheduling
Consider adding:
- Time-based ad rotation
- Different ads for different times of day
- A/B testing different advertisements

### Analytics Integration
Track:
- How many times each ad is displayed
- Engagement rates (faces detected after ad display)
- Average time between detections

## Files Modified

1. `/includes/admin-settings.php` - Added kiosk settings
2. `/templates/photo-inline.php` - Added kiosk display HTML
3. `/js/photo-age-estimator-continuous.js` - Added kiosk logic
4. `/css/photo-age-estimator.css` - Added kiosk styles
5. `/js/admin.js` - Added admin UI functionality