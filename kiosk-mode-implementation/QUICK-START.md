# 🚀 QUICK START - Kiosk Mode Implementation

Hey mate! Here's the quickest way to get Kiosk Mode working:

## 📁 What I've Created

All files are in: `/wp-content/plugins/Age-estimator-live/kiosk-mode-implementation/`

## ⚡ Quick Implementation (5 minutes)

### 1️⃣ Admin Settings
**File:** `includes/admin-settings.php`
- Copy code from `admin-settings-patch.php`
- Add the register_setting() calls (3 settings)
- Add the HTML section for Kiosk Mode

### 2️⃣ Template
**File:** `templates/photo-inline.php`
- Look at `photo-inline-complete-example.php` for reference
- Update the container div attributes
- Add the kiosk display div

### 3️⃣ JavaScript
**File:** `js/photo-age-estimator-continuous.js`
- Follow `integration-guide.js` step by step
- Add variables, functions, and integrate with existing code

### 4️⃣ CSS
**File:** `css/photo-age-estimator.css`
- Copy all styles from `kiosk-styles.css`

### 5️⃣ Admin JS
**File:** `js/admin.js`
- Copy code from `admin-javascript.js`

## 🧪 Testing

1. **Test Page**: Visit `/wp-content/plugins/Age-estimator-live/kiosk-mode-implementation/test-kiosk-mode.php`
2. **Enable**: Go to WordPress Admin > Age Estimator > Settings
3. **Configure**: Upload ad image, set display time
4. **Verify**: Check that ad shows when no face is detected

## 💡 How It Works

```
No Face → Show Ad
   ↓
Face Detected → Hide Ad → Show Camera
   ↓
Age Calculated → Show Result
   ↓
Timer (5 sec) → Return to Ad
```

## 🎯 Key Features

- ✅ Shows PNG/JPG ads when idle
- ✅ Auto-hides when customer approaches
- ✅ Returns to ad after showing age
- ✅ Configurable display time
- ✅ WordPress Media Library integration
- ✅ Clean transitions
- ✅ Mobile responsive

## 🔧 Troubleshooting

**Ad not showing?**
- Check Kiosk Mode is enabled
- Verify image URL is correct
- Check browser console for errors

**Not hiding on face detection?**
- Ensure `hideKioskDisplay()` is called
- Check face detection is working

**Not returning to ad?**
- Verify display time is set
- Check `scheduleReturnToKiosk()` is called

## 📞 Need Help?

1. Check `README.md` for detailed docs
2. Use test page to debug
3. Check browser console for errors
4. All implementation files have comments

---

**That's it!** Your kiosk mode should be working. Perfect for retail environments to show ads between customers! 🎉