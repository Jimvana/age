# Age Estimator Live

## Overview
Age Estimator Live is a WordPress plugin that provides live age estimation using continuous facial monitoring with automatic capture. When activated, the plugin continuously monitors for faces and automatically captures photos when a person comes within the optimal distance from the camera.

## Key Features

### 🎯 Live Monitoring
- **Continuous face detection** while camera is active
- **Automatic capture** when face is at optimal distance (150-350 pixels)
- **Visual feedback** with colored face boxes:
  - 🔴 Red = Too far
  - 🟢 Green = In range (automatic capture)
  - 🟠 Orange = Too close

### 📊 Real-time Feedback
- **Status indicator** showing detection state
- **Countdown timer** before automatic capture
- **Flash effect** on capture
- **Multiple result display** (up to 5 recent captures)
- **5-second cooldown** between captures

### 🔧 Detection Modes
- **Simple Mode**: Uses face-api.js for local browser-based detection (no external API required)
- **AWS Mode**: Uses AWS Rekognition for enhanced accuracy (requires AWS account)

## Installation

1. Upload the `age-estimator` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Settings > Age Estimator Live** to configure the plugin

## Configuration

### Basic Settings
1. Go to **WordPress Admin → Settings → Age Estimator Live**
2. Choose your detection mode:
   - **Simple**: Local detection (recommended for most users)
   - **AWS**: AWS Rekognition (requires AWS credentials)

### AWS Setup (Optional)
If using AWS mode:
1. Enter your AWS Access Key ID
2. Enter your AWS Secret Access Key
3. Select your AWS Region
4. Click "Test AWS Connection" to verify

### Display Options
- **Show Emotions**: Display emotion detection results
- **Show Attributes**: Display additional face attributes
- **Privacy Mode**: Hide captured photo after analysis

### Age Gating
- Enable age verification requirement
- Set minimum age threshold
- Customize age gate message
- Set redirect URL for underage users

## Usage

Add the shortcode to any page or post:
```
[age_estimator]
```

### Shortcode Attributes
- `title="Your Title"` - Change the title text
- `button_text="Your Button Text"` - Change the button text
- `style="inline|modal|fullscreen"` - Override the display style
- `class="your-custom-class"` - Add custom CSS classes

Example:
```
[age_estimator title="Live Age Verification" button_text="Start Live Monitoring" style="modal"]
```

## How It Works

1. User clicks "Start Monitoring"
2. Camera activates and begins continuous face detection
3. Colored boxes appear around detected faces:
   - Move closer if red box appears
   - Stay still when green box appears
   - Move back if orange box appears
4. Automatic capture occurs after 0.5 seconds in green zone
5. Age estimation results appear immediately
6. System waits 5 seconds before allowing next capture

## Configuration Options

You can fine-tune the monitoring behavior by editing these values in `/js/photo-age-estimator.js`:

```javascript
const MONITORING_CONFIG = {
    checkInterval: 100,      // Detection frequency (ms)
    minFaceSize: 150,       // Minimum face width (pixels)
    maxFaceSize: 350,       // Maximum face width (pixels)
    captureDelay: 500,      // Delay before capture (ms)
    cooldownPeriod: 5000,   // Time between captures (ms)
    minConfidence: 0.7      // Detection confidence threshold
};
```

## Technical Requirements

### Browser Support
- Chrome 60+
- Firefox 60+
- Safari 11+
- Edge 79+
- Mobile browsers with camera support

### Server Requirements
- WordPress 5.0 or higher
- PHP 7.2 or higher
- HTTPS required for camera access

## Privacy & Security

- **Local Processing**: In Simple mode, all face detection happens in the browser
- **No Storage**: Photos are not stored unless data retention is configured
- **Immediate Deletion**: Images are processed and immediately discarded
- **Consent Options**: Built-in consent management features
- **GDPR Compliant**: Designed with privacy regulations in mind

## Troubleshooting

### Models Not Loading
1. Check browser console for errors
2. Verify `/models/` directory exists and contains all required files
3. Clear browser cache and reload

### Camera Not Working
1. Ensure HTTPS is enabled
2. Check camera permissions in browser
3. Close other applications using the camera

### Poor Detection
1. Ensure good lighting
2. Face camera directly
3. Remove obstructions (sunglasses, masks)
4. Adjust minConfidence setting if needed

## Support

For support, feature requests, or bug reports, please visit:
[Your Support URL]

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- face-api.js for local face detection
- AWS Rekognition for cloud-based detection
- Built with ❤️ for the WordPress community
