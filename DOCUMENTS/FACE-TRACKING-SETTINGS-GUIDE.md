# Face Tracking Settings Guide

The Age Estimator Live plugin now includes customizable face tracking settings accessible from the WordPress admin panel. These settings allow you to fine-tune the face detection behavior for optimal performance in your specific environment.

## Accessing the Settings

1. Go to WordPress Admin → Settings → Age Estimator Live
2. Click on the "Face Tracking" tab

## Available Settings

### 1. Minimum Face Distance (50-300px)
**Default: 150px**

Controls how far away a face can be from the camera to trigger capture.
- **Lower values** (e.g., 100px): Detects faces from farther away
- **Higher values** (e.g., 200px): Requires faces to be closer

**Tip**: If users need to get too close to the camera, decrease this value.

### 2. Maximum Face Distance (200-600px)
**Default: 350px**

Controls how close a face can be to the camera before it's considered "too close."
- **Lower values** (e.g., 300px): Prevents faces from getting too close
- **Higher values** (e.g., 450px): Allows faces to be very close to camera

**Tip**: If you're getting "too close" warnings frequently, increase this value.

### 3. Face Matching Sensitivity (0.2-0.6)
**Default: 0.4**

Controls how similar faces must be to be considered the same person for caching.
- **Lower values** (e.g., 0.2): Very sensitive - may treat similar faces as different
- **Higher values** (e.g., 0.6): Less sensitive - may treat different faces as the same

**Tip**: If the cache isn't recognizing the same person, increase this value slightly.

### 4. Cache Duration (10-120 seconds)
**Default: 30 seconds**

How long to remember a face's age estimation result before requiring a new API call.
- **Shorter duration**: More accurate but uses more API calls
- **Longer duration**: Fewer API calls but may miss changes

**Tip**: For high-traffic scenarios, increase to 60-120 seconds to maximize API savings.

### 5. Capture Delay (200-2000ms)
**Default: 500ms**

Wait time after a face enters the capture zone before taking a photo.
- **Shorter delay**: Faster capture but may catch faces in motion
- **Longer delay**: More stable captures but feels slower

**Tip**: If faces are blurry or cut off, increase to 1000ms.

### 6. Cooldown Period (1-10 seconds)
**Default: 5 seconds**

Minimum time between capturing different faces or re-capturing the same face.
- **Shorter cooldown**: Can process faces more quickly
- **Longer cooldown**: Prevents rapid repeated captures

**Tip**: For queues of people, reduce to 2-3 seconds. For single-user scenarios, keep at 5+ seconds.

## Optimizing for Different Scenarios

### High-Traffic Retail/Event
```
Min Distance: 120px
Max Distance: 400px
Sensitivity: 0.5
Cache Duration: 60s
Capture Delay: 300ms
Cooldown: 2s
```

### Security/Age Verification Kiosk
```
Min Distance: 150px
Max Distance: 350px
Sensitivity: 0.3
Cache Duration: 30s
Capture Delay: 1000ms
Cooldown: 5s
```

### Mobile/Tablet Usage
```
Min Distance: 100px
Max Distance: 300px
Sensitivity: 0.4
Cache Duration: 45s
Capture Delay: 500ms
Cooldown: 3s
```

## Monitoring Performance

The face tracking system displays real-time metrics at the bottom of the camera view:
- **Cache Size**: Number of faces currently cached
- **Hit Rate**: Percentage of detections served from cache
- **API Saved**: Percentage reduction in API calls

## Troubleshooting

### "No faces detected" frequently
1. Decrease Min Distance to allow detection from farther away
2. Ensure good lighting conditions
3. Check that faces are centered in frame

### Cache not recognizing same person
1. Increase Face Matching Sensitivity (try 0.5 or 0.6)
2. Ensure consistent lighting
3. Check that person isn't moving too much

### Too many API calls
1. Increase Cache Duration
2. Decrease Face Matching Sensitivity
3. Increase Cooldown Period

### Captures are blurry
1. Increase Capture Delay to 1000ms or more
2. Ensure adequate lighting
3. Ask users to hold still briefly

## Best Practices

1. **Start with defaults** - The default settings work well for most scenarios
2. **Adjust one setting at a time** - This helps identify what works best
3. **Monitor the metrics** - Use the real-time display to see the impact of changes
4. **Consider your use case** - High-security needs different settings than high-traffic
5. **Test with real users** - Settings that work in testing may need adjustment in production

## API Cost Savings

With properly configured settings, you can expect:
- **70-90% reduction** in AWS Rekognition API calls
- **Significant cost savings** for high-traffic deployments
- **Faster response times** for returning visitors

The face tracking cache is especially effective in scenarios where:
- The same people use the system repeatedly
- Users may trigger multiple captures
- There's a steady flow of people (events, retail)

Remember: The cache respects the configured duration and will automatically expire entries, ensuring fresh data while maximizing efficiency.
