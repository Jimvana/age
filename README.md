# Face Detection Camera App

A real-time face detection web application with configurable settings and distance filtering for access control systems.

## Features

### ✅ Core Functionality
- **Real-time Face Detection**: Uses face-api.js for accurate face detection
- **Age & Gender Recognition**: Estimates age and gender for each detected face
- **Access Control Logic**: Red boxes for under-age (ID required), Green boxes for approved
- **Distance Filtering**: Only detects faces within configurable size range (prevents false positives from background)

### ✅ Advanced Features
- **Configurable Settings**: Centralized settings system with visual UI
- **Detection Zone Visualization**: Visual indicators showing detection range
- **Performance Optimizations**: Smoothing, detection limits, and efficient processing
- **Loading States**: Professional loading overlay during model initialization
- **Error Handling**: Comprehensive error messages and fallbacks
- **Mobile Optimized**: Responsive design for tablets and mobile devices

### ✅ Settings Management
- **Settings Panel**: Click ⚙️ button or press 'S' to access settings
- **Real-time Updates**: Changes apply immediately without restart
- **Persistent Settings**: Settings saved to browser localStorage
- **Export/Import**: Backup and restore settings configurations
- **Validation**: Input validation prevents invalid configurations

## Quick Start

1. **Start the Server**:
   ```bash
   python3 https_server.py
   ```

2. **Access the App**:
   - Local: https://localhost:8443
   - Network: https://[your-ip]:8443

3. **Accept Security Warning**:
   - Click "Advanced" → "Proceed to localhost (unsafe)"

4. **Allow Camera Permissions** when prompted

## Settings Configuration

### Face Detection Settings
- **Min Face Size**: Minimum face width in pixels (default: 80)
- **Max Face Size**: Maximum face width in pixels (default: 300)
- **Detection Confidence**: Minimum confidence score (0.1 - 1.0)
- **Detection Interval**: Processing frequency in milliseconds

### Age & Gender Settings
- **Age Threshold**: Age for ID requirement (default: 25)
- **Show Age**: Display estimated age on faces
- **Show Gender**: Display estimated gender on faces

### Visual Settings
- **Show Detection Zone**: Visual zone indicators
- **Text Size**: Font size for labels
- **Border Colors**: Customize detection box colors

### Camera Settings
- **Camera Facing**: Front or back camera
- **Resolution**: Video capture resolution (640x480 at 30fps)

## Distance Filtering

The app includes intelligent distance filtering to prevent false detections:

- **Close Range**: Faces smaller than min size are ignored (too far)
- **Optimal Range**: Faces within min-max size range are detected
- **Too Close**: Faces larger than max size are ignored (too close to camera)

This ensures only people at appropriate distances trigger the access control system.

## Deployment Options

### Local Network Deployment
- Run on local machine with HTTPS
- Access from devices on same network
- No external hosting costs

### Cloud Deployment
- **Vercel**: Free tier, easy HTTPS, global CDN
- **Netlify**: Similar to Vercel, great for static sites
- **Heroku**: Good for custom server configurations
- **AWS/GCP**: Scalable enterprise solutions

### Temporary External Access
- **ngrok**: Instant HTTPS tunnels for testing
- **localtunnel**: Free tunneling service

## File Structure

```
├── index.html              # Main application
├── https_server.py         # Local HTTPS server
├── settings/
│   ├── config.js           # Configuration settings
│   └── ui.js               # Settings UI components
├── face-api/               # Face detection models
├── manifest.json           # PWA manifest
├── cert.pem               # SSL certificate
├── key.pem                # SSL private key
└── README.md              # This file
```

## Browser Compatibility

- **Chrome/Edge**: Full support
- **Firefox**: Full support
- **Safari**: Full support (iOS 11.3+)
- **Mobile Browsers**: Chrome, Safari, Samsung Internet

## HTTPS Requirements

Camera access requires HTTPS in modern browsers. The app includes:
- Self-signed SSL certificates for local development
- Automatic HTTPS detection and warnings
- Instructions for certificate acceptance

## Troubleshooting

### Camera Not Working
1. Ensure HTTPS is enabled
2. Check camera permissions in browser
3. Try refreshing the page
4. Check browser console for errors

### Models Not Loading
1. Check network connection
2. Ensure face-api folder is accessible
3. Check browser console for specific errors
4. Try clearing browser cache

### Settings Not Saving
1. Check browser localStorage permissions
2. Try clearing browser data
3. Check for JavaScript errors in console

## Development

### Adding New Settings
1. Add to `CONFIG` object in `settings/config.js`
2. Add UI controls in `settings/ui.js`
3. Update validation in `validateConfig()`
4. Use settings in main application logic

### Performance Tuning
- Adjust `detectionInterval` for processing speed (default: 200ms for low-end devices)
- Modify `maxDetections` to limit processing load (default: 3 for low-end devices)
- Enable/disable smoothing based on needs (disabled on low-end devices)
- Adjust model input size for accuracy vs speed
- Automatic device detection optimizes settings for low-end Android devices

## License

This project is for educational and demonstration purposes.
