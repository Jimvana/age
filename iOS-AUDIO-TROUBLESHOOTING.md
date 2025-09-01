# iOS Audio Troubleshooting Guide

## Quick Diagnostics

### 1. Check if sounds are configured correctly
In your browser console, run:
```javascript
// Check configuration
ageEstimatorPhotoParams.enableSounds
ageEstimatorPhotoParams.passSoundUrl
ageEstimatorPhotoParams.failSoundUrl
```

### 2. Test the sound system
Add `#debug` to your URL and click the "Test Age Sounds" button, or run in console:
```javascript
AgeEstimatorSounds.testSounds()
```

### 3. Check specific to your issue
Since you're seeing visual indicators but no sound, check:
```javascript
// Check if files are loading
AgeEstimatorSounds.passSound.error
AgeEstimatorSounds.failSound.error

// Check ready states (should be 4 when fully loaded)
AgeEstimatorSounds.passSound.readyState
AgeEstimatorSounds.failSound.readyState

// Check if URLs are accessible
AgeEstimatorSounds.passSound.src
AgeEstimatorSounds.failSound.src
```

## Common Issues & Solutions

### 1. **Sound files not loading (404 errors)**
- Check if the sound file URLs are correct and accessible
- Try opening the URLs directly in your browser
- Ensure files are in a web-accessible directory

### 2. **CORS issues**
If you see CORS errors in console:
- Host sound files on the same domain as your WordPress site
- Or ensure the sound file server has proper CORS headers

### 3. **iOS Silent Mode**
- Check if your iPhone's physical silent switch is OFF (should show orange)
- Check Safari's audio is not muted
- Try increasing device volume

### 4. **Audio format issues**
Best formats for iOS Safari:
- MP3 (recommended)
- M4A
- WAV (larger file size)

Avoid: OGG, WebM

### 5. **File size**
Keep audio files under 1MB for best compatibility

## Manual Test

Try this in your browser console to test if iOS can play audio at all:

```javascript
// Create and play a test tone
const testAudio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBCuBzvLXiTYIG2m98OScTgwOUajk4bllHQU5k9n1zHkqBSh+0fDEly9vvN6dUBEGOIza8st+LAUqgM7w4Yk2CBpovO2ooU4LDVOX5eSqWBkIR6Hg8bJWCz9+zOXOiDAHF2S77OihUBcFQZ
zY88p7KwUme8zuz4w4CRxywfPTgjQGHW/A7tCNNwkaPGq07OmjUg0LUaXh47FYHAVGneDjrWIZCUCY3+6wYhYEOpbW8ct7KAUme8vvz4w4ChxywPDTgjYGHm/A7NCNNwkaPWu08+mjUg0LUqbi47BYGwZFnd/gsGMYBj6X1vLMeygFJXzL7s+MOQUZ');
testAudio.volume = 0.5;
testAudio.play().then(() => {
    console.log('✅ Audio playback works!');
}).catch(e => {
    console.error('❌ Audio playback failed:', e);
});
```

## File Hosting Solutions

If your audio files aren't loading, try hosting them:

1. **In WordPress Media Library**
   - Upload MP3 files to Media Library
   - Use the full URL from Media Library

2. **In plugin directory**
   - Place files in: `/wp-content/plugins/Age-estimator-live/sounds/`
   - Use URL: `https://yoursite.com/wp-content/plugins/Age-estimator-live/sounds/pass.mp3`

3. **External CDN** (with CORS)
   - CloudFlare R2
   - AWS S3 (with public access)
   - Bunny CDN

## Emergency Fallback

If sounds still don't work, you can use system sounds as a temporary solution:

```javascript
// Add this to test system sounds
window.playSystemSound = function(type) {
    const audio = new Audio();
    if (type === 'pass') {
        // Pleasant ding sound
        audio.src = 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBCuBzvLXiTYIG2m98OScTgwOUajk4bllHQU5k9n1zHkqBSh+0fDEly9vvN6dUBEGOIza8st+LAUqgM7w4Yk2CBpovO2ooU4LDVOX5eSqWBkIR6Hg8bJWCz9+zOXOiDAHF2S77OihUBcFQZ
zY88p7KwUme8zuz4w4CRxywfPTgjQGHW/A7tCNNwkaPGq07OmjUg0LUaXh47FYHAVGneDjrWIZCUCY3+6wYhYEOpbW8ct7KAUme8vvz4w4ChxywPDTgjYGHm/A7NCNNwkaPWu08+mjUg0LUqbi47BYGwZFnd/gsGMYBj6X1vLMeygFJXzL7s+MOQUZ';
    } else {
        // Error buzz
        audio.src = 'data:audio/wav;base64,UklGRiQCAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQACAADr6+vr6+vr6+vr6+vr6+vr6+vr6+vr6+vr6+vr6+vr6+vr6+vr6+vr6+vr6+vr6+vr6+vr6+vr6+vr6+vr6+vr6+vr6+vr6+vr6+vr';
    }
    audio.play();
};
```

Let me know what the console outputs show and I'll help you fix the specific issue!
