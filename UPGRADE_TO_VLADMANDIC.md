# Upgrade Guide: Migrating to @vladmandic/face-api

This guide will help you upgrade the Age Estimator Live plugin from the original face-api.js to @vladmandic/face-api.

## Why Upgrade?

- **Active Maintenance**: @vladmandic/face-api is actively maintained while the original face-api.js is no longer updated
- **Better Performance**: Optimized for modern browsers and devices
- **Bug Fixes**: Many issues from the original library have been resolved
- **TypeScript Support**: Better type definitions and modern JavaScript features
- **Enhanced Features**: Additional detection options and improved accuracy

## Step 1: Download @vladmandic/face-api

### Option A: Using npm (Recommended)
```bash
# Navigate to the libs directory
cd /Users/video/DevKinsta/public/local-model-age-estin/wp-content/plugins/Age-estimator-live/libs

# Download the library
npm pack @vladmandic/face-api
tar -xf vladmandic-face-api-*.tgz
cp package/dist/face-api.min.js ./face-api.min.js
rm -rf package vladmandic-face-api-*.tgz
```

### Option B: Direct Download
1. Visit: https://github.com/vladmandic/face-api/tree/main/dist
2. Download `face-api.min.js`
3. Place it in the `libs` directory

### Option C: Using wget/curl
```bash
cd /Users/video/DevKinsta/public/local-model-age-estin/wp-content/plugins/Age-estimator-live/libs
wget https://raw.githubusercontent.com/vladmandic/face-api/main/dist/face-api.min.js
```

## Step 2: Update Model Files (Optional)

The current models should work, but you can optionally update them:

```bash
cd /Users/video/DevKinsta/public/local-model-age-estin/wp-content/plugins/Age-estimator-live/models

# Download updated models
wget https://github.com/vladmandic/face-api/raw/main/model/age_gender_model-shard1
wget https://github.com/vladmandic/face-api/raw/main/model/age_gender_model-weights_manifest.json
wget https://github.com/vladmandic/face-api/raw/main/model/face_expression_model-shard1
wget https://github.com/vladmandic/face-api/raw/main/model/face_expression_model-weights_manifest.json
wget https://github.com/vladmandic/face-api/raw/main/model/face_landmark_68_model-shard1
wget https://github.com/vladmandic/face-api/raw/main/model/face_landmark_68_model-weights_manifest.json
wget https://github.com/vladmandic/face-api/raw/main/model/face_recognition_model-shard1
wget https://github.com/vladmandic/face-api/raw/main/model/face_recognition_model-shard2
wget https://github.com/vladmandic/face-api/raw/main/model/face_recognition_model-weights_manifest.json
wget https://github.com/vladmandic/face-api/raw/main/model/ssd_mobilenetv1_model-shard1
wget https://github.com/vladmandic/face-api/raw/main/model/ssd_mobilenetv1_model-shard2
wget https://github.com/vladmandic/face-api/raw/main/model/ssd_mobilenetv1_model-weights_manifest.json
```

## Step 3: Code Compatibility Changes

The @vladmandic/face-api is mostly compatible with the original, but there are some minor differences:

### API Changes Made in This Update:

1. **Model Loading**: Updated to prioritize local models over CDN
2. **Detection Options**: Enhanced with better default settings
3. **Error Handling**: Improved error messages and fallback handling

## Step 4: Testing

After upgrading:

1. Clear your browser cache
2. Test the age estimation functionality
3. Check browser console for any errors
4. Verify that face detection works correctly

## Rollback Instructions

If you need to rollback:

```bash
cd /Users/video/DevKinsta/public/local-model-age-estin/wp-content/plugins/Age-estimator-live/libs
mv face-api.min.js face-api.min.js.vladmandic
mv face-api.min.js.backup face-api.min.js
```

## Troubleshooting

### Common Issues:

1. **Models not loading**: Ensure model files are in the correct directory
2. **Face detection not working**: Check browser console for errors
3. **Performance issues**: Try adjusting detection settings in the admin panel

### Debug Mode:

Add this to your browser console to enable debug logging:
```javascript
window.ageEstimatorDebug = true;
```

## Support

For issues specific to @vladmandic/face-api, see:
- GitHub: https://github.com/vladmandic/face-api
- Documentation: https://github.com/vladmandic/face-api#documentation
