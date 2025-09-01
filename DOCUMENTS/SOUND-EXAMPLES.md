# Sound Notification Examples for Age Estimator

## Recommended Sound Types

### Pass Sounds (Age Verification Successful)
- **Positive chime** - A pleasant ascending tone
- **Success bell** - Single or double bell ring
- **Soft ping** - Gentle notification sound
- **Approval beep** - Short, positive beep

### Fail Sounds (Age Verification Failed)
- **Warning tone** - Descending or flat tone
- **Alert beep** - Double beep pattern
- **Denial buzz** - Short buzzer sound
- **Error chime** - Distinct but not harsh

## Sound File Requirements
- **Format**: MP3, WAV, or OGG
- **Duration**: Keep under 2 seconds for best UX
- **File size**: Under 500KB recommended
- **Volume**: Normalize to prevent jarring playback

## Free Sound Resources
1. **Freesound.org** - Creative Commons sounds
2. **Zapsplat.com** - Free with account
3. **Soundbible.com** - Public domain sounds
4. **YouTube Audio Library** - Free for use

## Example Implementation

### Option 1: Use System Sounds
You can use simple system-like sounds:
- Pass: `https://www.soundjay.com/misc/bell-ringing-05.wav`
- Fail: `https://www.soundjay.com/misc/fail-buzzer-02.wav`

### Option 2: Create Custom Sounds
Use online tools like:
- **BeepBox.co** - Simple online synthesizer
- **Audacity** - Free audio editor
- **Chrome Music Lab** - Browser-based creation

### Option 3: Purchase Premium Sounds
- **AudioJungle** - Professional sound effects
- **Pond5** - High-quality audio library
- **PremiumBeat** - Curated sound effects

## Implementation Tips

1. **Test Different Volumes**: Start at 70% and adjust based on environment
2. **Consider Context**: Retail environments may need louder sounds
3. **Accessibility**: Ensure sounds are distinct for users with hearing differences
4. **Browser Compatibility**: Test across different browsers
5. **Mobile Considerations**: Some mobile browsers require user interaction before playing sounds

## Quick Setup Example

1. Upload your sound files to WordPress Media Library
2. Copy the URLs of uploaded files
3. Go to Age Estimator settings
4. Enable Sound Notifications
5. Paste URLs in Pass/Fail sound fields
6. Adjust volume to taste
7. Test with the preview buttons
8. Save settings

The sounds will automatically preload when the age estimator loads, ensuring instant playback when results come in!
