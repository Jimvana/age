#!/bin/bash

# Download script for @vladmandic/face-api
# This script downloads the latest version of @vladmandic/face-api for the Age Estimator Live plugin

echo "==================================="
echo "Age Estimator Live - Library Upgrade"
echo "Downloading @vladmandic/face-api..."
echo "==================================="

# Get the directory of this script
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
LIBS_DIR="$SCRIPT_DIR/libs"

# Create libs directory if it doesn't exist
mkdir -p "$LIBS_DIR"

# Navigate to libs directory
cd "$LIBS_DIR"

# Download options
echo "Choose download method:"
echo "1) Using wget (recommended)"
echo "2) Using curl"
echo "3) Using npm"
read -p "Enter your choice (1-3): " choice

case $choice in
    1)
        echo "Downloading with wget..."
        wget -O face-api.min.js https://cdn.jsdelivr.net/npm/@vladmandic/face-api@latest/dist/face-api.min.js
        ;;
    2)
        echo "Downloading with curl..."
        curl -o face-api.min.js https://cdn.jsdelivr.net/npm/@vladmandic/face-api@latest/dist/face-api.min.js
        ;;
    3)
        echo "Downloading with npm..."
        npm pack @vladmandic/face-api
        tar -xf vladmandic-face-api-*.tgz
        cp package/dist/face-api.min.js ./face-api.min.js
        rm -rf package vladmandic-face-api-*.tgz
        ;;
    *)
        echo "Invalid choice. Exiting."
        exit 1
        ;;
esac

# Check if download was successful
if [ -f "face-api.min.js" ]; then
    echo "✅ Successfully downloaded @vladmandic/face-api"
    echo "Location: $LIBS_DIR/face-api.min.js"
    
    # Optional: Download updated models
    read -p "Do you want to download updated models? (y/n): " download_models
    
    if [ "$download_models" = "y" ]; then
        echo "Downloading updated models..."
        MODELS_DIR="$SCRIPT_DIR/models"
        mkdir -p "$MODELS_DIR"
        cd "$MODELS_DIR"
        
        # Download model files
        model_files=(
            "age_gender_model-shard1"
            "age_gender_model-weights_manifest.json"
            "face_expression_model-shard1"
            "face_expression_model-weights_manifest.json"
            "face_landmark_68_model-shard1"
            "face_landmark_68_model-weights_manifest.json"
            "face_recognition_model-shard1"
            "face_recognition_model-shard2"
            "face_recognition_model-weights_manifest.json"
            "ssd_mobilenetv1_model-shard1"
            "ssd_mobilenetv1_model-shard2"
            "ssd_mobilenetv1_model-weights_manifest.json"
        )
        
        for file in "${model_files[@]}"; do
            echo "Downloading $file..."
            if command -v wget &> /dev/null; then
                wget -O "$file" "https://raw.githubusercontent.com/vladmandic/face-api/main/model/$file"
            else
                curl -o "$file" "https://raw.githubusercontent.com/vladmandic/face-api/main/model/$file"
            fi
        done
        
        echo "✅ Models downloaded successfully"
    fi
    
    echo ""
    echo "==================================="
    echo "Upgrade complete!"
    echo "Please clear your browser cache and test the plugin."
    echo "==================================="
else
    echo "❌ Download failed. Please check your internet connection and try again."
    exit 1
fi
