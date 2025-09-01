#!/bin/bash

# Simple installation script for @vladmandic/face-api upgrade
# This script downloads the @vladmandic/face-api library for the Age Estimator Live plugin

echo "===================================="
echo "Age Estimator Live - @vladmandic/face-api Upgrade"
echo "===================================="

# Navigate to the libs directory
PLUGIN_DIR="/Users/video/DevKinsta/public/local-model-age-estin/wp-content/plugins/Age-estimator-live"
LIBS_DIR="$PLUGIN_DIR/libs"

echo "Navigating to libs directory..."
cd "$LIBS_DIR" || exit 1

# Download the library
echo "Downloading @vladmandic/face-api..."
curl -L -o face-api.min.js "https://cdn.jsdelivr.net/npm/@vladmandic/face-api@latest/dist/face-api.min.js"

# Check if download was successful
if [ -f "face-api.min.js" ]; then
    echo ""
    echo "✅ Successfully downloaded @vladmandic/face-api!"
    echo ""
    echo "File size: $(ls -lh face-api.min.js | awk '{print $5}')"
    echo "Location: $LIBS_DIR/face-api.min.js"
    echo ""
    echo "===================================="
    echo "✨ Upgrade complete!"
    echo ""
    echo "Next steps:"
    echo "1. Clear your browser cache"
    echo "2. Test the age estimation functionality"
    echo "3. Check the browser console for any errors"
    echo ""
    echo "If you need to rollback:"
    echo "mv face-api.min.js face-api.min.js.vladmandic"
    echo "mv face-api.min.js.backup face-api.min.js"
    echo "===================================="
else
    echo ""
    echo "❌ Download failed!"
    echo "Please check your internet connection and try again."
    echo ""
    echo "Alternative: Download manually from:"
    echo "https://cdn.jsdelivr.net/npm/@vladmandic/face-api@latest/dist/face-api.min.js"
    exit 1
fi
