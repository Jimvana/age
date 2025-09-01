# Age Estimator Photo Plugin - AWS Integration & Age Gating Guide

## 🚀 New Features Added

### 1. AWS Rekognition Integration
- **Primary alternative** to Azure Face API
- **5,000 free images/month** for first 12 months  
- **$1 per 1,000 images** after free tier
- Enterprise-grade reliability and accuracy
- Age ranges with confidence scores

### 2. Age Gating & Verification System
- **Configurable minimum age** (13-99 years)
- **Custom messages** for age verification failures
- **Optional redirect URLs** for failed verifications
- **Automatic age verification** based on facial analysis
- **Legal compliance** features built-in

### 3. Privacy & GDPR Compliance
- **Explicit user consent** requirements
- **Customizable consent text**
- **Data retention policies** (0-8760 hours)
- **Immediate deletion** options (recommended)
- **Privacy mode** with face blurring

## 🔧 Setup Instructions

### AWS Rekognition Setup

1. **Create AWS Account**
   - Go to [AWS Console](https://aws.amazon.com/)
   - Sign up for a new account (free tier available)

2. **Create IAM User**
   ```
   1. Go to IAM → Users → Add User
   2. Username: age-estimator-bot
   3. Access type: Programmatic access
   4. Attach policy: AmazonRekognitionFullAccess
   5. Save Access Key ID and Secret Access Key
   ```

3. **Configure Plugin**
   ```
   WordPress Admin → Settings → Age Estimator Photo
   → Amazon AWS Rekognition Settings
   → Enter your credentials
   → Test connection
   ```

### Age Gating Configuration

1. **Enable Age Gating**
   ```
   Age Gating & Verification → Enable Age Gating ✓
   ```

2. **Set Minimum Age**
   - **18 years**: General adult content
   - **21 years**: Alcohol/tobacco content  
   - **Custom**: Any age 13-99

3. **Customize Messages**
   ```
   Age Gate Message: "You must be {age} or older to access this content."
   → {age} automatically replaced with minimum age
   ```

4. **Optional Redirect**
   ```
   Redirect URL: https://example.com/age-verification-failed
   → Leave empty to show message only
   ```

## ⚖️ Legal Compliance Guide

### GDPR Requirements (EU Users)

**✅ MANDATORY SETTINGS:**
```
Privacy & Compliance → Require User Consent ✓
Data Retention: 0 hours (immediate deletion)
```

**✅ REQUIRED CONSENT TEXT:**
```
"I consent to the processing of my facial image for age verification 
purposes. My image will be processed securely and deleted immediately 
after verification."
```

### Age Verification Legal Notice

**⚠️ IMPORTANT:** Facial age estimation should be part of a comprehensive age verification system, not the sole method. Consider additional verification for high-risk content.

**RECOMMENDED APPROACH:**
1. Facial age estimation (primary screening)
2. ID document verification (secondary verification)  
3. Credit card verification (final verification)

### Data Protection Best Practices

1. **Immediate Deletion** (Recommended)
   ```
   Data Retention: 0 hours
   → Images deleted immediately after processing
   ```

2. **User Consent**
   ```
   Require User Consent: ✓ ENABLED
   → Clear explanation of data processing
   ```

3. **Privacy Mode**
   ```
   Privacy Mode: ✓ ENABLED
   → Faces blurred after detection
   ```

## 🔄 Detection Priority System

The plugin uses an intelligent fallback system:

1. **Azure Face API** (if enabled & available)
2. **AWS Rekognition** (if Azure fails or disabled)
3. **Local face-api.js** (client-side fallback)

### Recommended Configuration

**For Maximum Accuracy:**
```
✓ Use Azure Face API
✓ Use AWS Rekognition  
→ Azure primary, AWS fallback
```

**For Cost Optimization:**
```
✗ Use Azure Face API
✓ Use AWS Rekognition
→ AWS only (5,000 free/month)
```

**For Privacy-First:**
```
✗ Use Azure Face API
✗ Use AWS Rekognition
→ Client-side only (no data transmission)
```

## 📊 Accuracy Comparison

| Service | Accuracy | Free Tier | Cost | Privacy |
|---------|----------|-----------|------|---------|
| Azure Face API | 95%+ | 30,000/month | $1/1000 | Server-side |
| AWS Rekognition | 95%+ | 5,000/month (12mo) | $1/1000 | Server-side |
| face-api.js | 85-90% | Unlimited | Free | Client-side |

## 🛡️ Security Features

### Data Handling
- **No permanent storage** of facial images
- **Encrypted transmission** to cloud APIs
- **Automatic cleanup** after processing
- **Session-based** age verification status

### Rate Limiting
- **Built-in protection** against API abuse
- **Nonce verification** for all requests
- **User permission checks**

### Error Handling
- **Graceful fallbacks** when APIs fail
- **Detailed logging** for debugging
- **User-friendly error messages**

## 📝 Shortcode Usage

### Basic Usage
```
[age_estimator_photo]
```

### With Age Gating
```
[age_estimator_photo title="Age Verification Required"]
```

### Custom Styling
```
[age_estimator_photo style="popup" class="my-custom-class"]
```

## 🔍 Troubleshooting

### AWS Connection Issues
1. **Check credentials** in AWS IAM
2. **Verify region** matches your AWS setup
3. **Test connection** in admin panel
4. **Check error logs** for detailed messages

### Age Gating Not Working
1. **Enable age gating** in settings
2. **Set minimum age** appropriately  
3. **Check consent requirements**
4. **Verify JavaScript** is not blocked

### Privacy Compliance
1. **Enable consent requirement**
2. **Set data retention** to 0 hours
3. **Update consent text** for your jurisdiction
4. **Review local privacy laws**

## 📈 Cost Estimates

### AWS Rekognition Pricing

| Monthly Usage | Year 1 Cost | Year 2+ Cost |
|---------------|-------------|--------------|
| 1,000 images | FREE | $1.00 |
| 5,000 images | FREE | $5.00 |
| 10,000 images | $5.00 | $10.00 |
| 50,000 images | $45.00 | $50.00 |

### Optimization Tips
1. **Cache results** to avoid duplicate API calls
2. **Use client-side** for non-critical applications
3. **Batch process** multiple images
4. **Monitor usage** in AWS Console

## 🔧 Advanced Configuration

### WordPress Constants
```php
// Disable all cloud APIs (privacy mode)
define('AGE_ESTIMATOR_FORCE_LOCAL', true);

// Custom API timeout
define('AGE_ESTIMATOR_API_TIMEOUT', 30);

// Debug mode
define('AGE_ESTIMATOR_DEBUG', true);
```

### Hooks & Filters
```php
// Modify age verification result
add_filter('age_estimator_photo_age_verified', function($verified, $age) {
    // Custom logic here
    return $verified;
}, 10, 2);

// Custom consent text
add_filter('age_estimator_photo_consent_text', function($text) {
    return 'Custom consent message';
});
```

## 📞 Support & Resources

- **AWS Rekognition Docs**: https://docs.aws.amazon.com/rekognition/
- **GDPR Compliance**: https://gdpr.eu/
- **Plugin Support**: Check error logs first, then contact support

---

**✅ Your plugin is now configured for AWS Rekognition with full legal compliance!**

Remember to test the age gating functionality thoroughly before deploying to production, and ensure you comply with local privacy and age verification regulations.
