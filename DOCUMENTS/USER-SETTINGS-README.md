# Frontend User Settings Panel - Age Estimator Live

This update adds a frontend user settings panel that allows logged-in users to configure their own personal settings for the Age Estimator plugin.

## Features

### 1. User Settings Shortcode

Add the settings panel to any page using the shortcode:

```
[age_estimator_user_settings]
```

### 2. Available Settings

#### Face Tracking Settings
- **Face Match Distance Threshold** - Controls how strictly faces are matched (0.3-0.6)
  - Lower values = stricter matching
  - Default: 0.4

#### Retail Mode (Challenge 25)
- **Enable Retail Mode** - Activates Challenge 25 compliance features
- **Personal Retail Mode PIN** - Each user can set their own 4-digit PIN
  - PINs are hashed and stored securely
  - Required when switching to retail mode

#### Age Gating
- **Enable Age Gating** - Restrict access based on estimated age
- **Minimum Age** - Users must appear at least this age (13-25)
  - Default: 18

### 3. How User Settings Work

1. **User-specific settings override system defaults** - When a user is logged in, their personal settings take precedence
2. **Settings are stored in user meta** - Each user's preferences are saved to their WordPress user profile
3. **Secure PIN storage** - User PINs are hashed using WordPress's password hashing

### 4. Implementation Details

#### New Files Added:
- `/includes/user-settings/class-user-settings.php` - Main user settings class
- `/css/user-settings.css` - Styles for the settings panel
- `/js/user-settings.js` - JavaScript for settings functionality

#### Modified Files:
- `age-estimator.php` - Added user settings include and user meta to localized params
- `photo-age-estimator-retail.js` - Updated to use user-specific PINs

### 5. Usage Instructions

#### For Site Administrators:

1. Create a new page for user settings (e.g., "Age Estimator Settings")
2. Add the shortcode `[age_estimator_user_settings]`
3. Add a link to this page in your site's menu or user dashboard

#### For Users:

1. Navigate to the settings page
2. Configure your preferences:
   - Adjust face tracking sensitivity
   - Enable retail mode and set your PIN
   - Configure age gating if needed
3. Click "Save Settings"

### 6. Security Features

- **Nonce verification** - All AJAX requests are protected
- **User authentication** - Only logged-in users can access settings
- **PIN hashing** - PINs are never stored in plain text
- **Input validation** - All inputs are validated and sanitized

### 7. Responsive Design

The settings panel is fully responsive and includes:
- Mobile-friendly layout
- Touch-optimized controls
- Dark mode support

### 8. Integration with Existing Features

- **Face Tracking** - User's distance threshold is used in face matching
- **Retail Mode** - User's PIN is required instead of system-wide PIN
- **Age Gating** - User's threshold is applied to their sessions

### 9. Developer Notes

#### Accessing User Settings in JavaScript:
```javascript
// Get user settings
const settings = getUserAgeEstimatorSettings();
console.log(settings.faceTrackingDistance);
console.log(settings.retailModeEnabled);

// Validate user PIN
validateUserRetailPin(pin, function(isValid, data) {
    if (isValid) {
        // PIN is correct
    }
});
```

#### Available Hooks:
- `age_estimator_save_user_settings` - AJAX action for saving settings
- `age_estimator_validate_user_pin` - AJAX action for PIN validation

### 10. Future Enhancements

- Email notifications for retail mode activity
- Export personal compliance logs
- Custom challenge ages per user
- Role-based setting restrictions
