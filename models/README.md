# Face-API.js Models Directory

This directory contains the pre-trained models required for face detection and analysis.

## Required Models

### Basic Face Detection
- `ssd_mobilenetv1_model-*` - Face detection model

### Age Estimation (Local Mode)
- `age_gender_model-*` - Age and gender prediction
- `face_expression_model-*` - Facial expression recognition

### Face Tracking (AWS Mode Optimization)
- `face_landmark_68_model-*` - 68-point facial landmark detection (REQUIRED)
- `face_recognition_model-*` - Face descriptor extraction for caching (REQUIRED)

## Downloading Models

If any models are missing, you can download them using:

### Option 1: Web Browser
Navigate to: `your-site.com/wp-content/plugins/Age-Estimator-live/download-all-models.php`

### Option 2: Command Line
```bash
cd /path/to/plugin
./download-models.sh
```

### Option 3: Quick Fix (Missing Face Tracking Models)
Navigate to: `your-site.com/wp-content/plugins/Age-Estimator-live/download-missing-models.php`

## Model Sizes

- SSD MobileNetV1: ~5.7 MB
- Age Gender: ~420 KB  
- Face Expression: ~330 KB
- Face Landmark 68: ~350 KB
- Face Recognition: ~6.2 MB

Total: ~13 MB

## Important Notes

1. **Face Tracking Models**: The face landmark and recognition models are REQUIRED for the face tracking optimization feature to work in AWS mode.

2. **File Permissions**: Ensure the models directory has proper read permissions (755).

3. **CDN Alternative**: Models are loaded from local files for better performance and reliability.

## Troubleshooting

If you see "Unexpected token '<'" errors:
- The model files are missing or corrupted
- Run the download script to fetch them
- Check file permissions
- Clear browser cache and reload
