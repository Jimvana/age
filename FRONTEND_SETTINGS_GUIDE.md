# Frontend Settings Page - Implementation Guide

## Overview
The Age Estimator Live plugin now includes a comprehensive frontend settings page that allows users to customize their age estimation experience directly from the frontend of your WordPress site.

## Features

### 1. **User-Specific Settings**
- Each logged-in user can have their own personalized settings
- Settings are stored as user meta data
- Non-logged-in users use global defaults

### 2. **Settings Categories**

#### General Settings
- Show/hide age estimation results
- Display confidence scores
- Result display duration
- Age gating configuration

#### Face Detection
- Face match sensitivity (0.1 - 1.0)
- Detection frequency
- Face size requirements
- Face tracking options
- Multi-face detection
- Age averaging samples

#### Retail Mode (Challenge 25)
- Enable/disable retail compliance mode
- Set challenge age threshold
- Staff PIN protection
- Transaction logging
- Email notifications for compliance

#### Privacy & Security
- Privacy mode (blur faces)
- Consent requirements
- Data retention periods
- Session timeout
- Two-factor authentication

#### Notifications
- Sound effects configuration
- Volume control
- Custom sound selection
- Visual feedback (screen flash)
- Success/failure colors

#### Advanced Settings
- Detection mode (Local/AWS/Hybrid)
- Cache duration
- Hardware acceleration
- Emotion detection
- Gender detection
- Facial attributes
- Import/Export settings

#### Statistics Dashboard
- Total scans count
- Success/failure rates
- Average age statistics
- Daily usage charts
- Data export options

## Implementation

### Method 1: Using Shortcodes

#### Basic Shortcode
```php
[age_estimator_user_settings]
```

#### Enhanced Shortcode with Options
```php
[age_estimator_settings_enhanced theme="light" layout="sidebar" show_stats="true" allow_export="true"]
```

**Shortcode Parameters:**
- `theme`: "light", "dark", or "auto" (follows system preference)
- `layout`: "sidebar" (default), "tabs", or "accordion"
- `show_stats`: "true" or "false" - Show statistics panel
- `allow_export`: "true" or "false" - Allow settings export/import

### Method 2: Creating a Settings Page

1. **Create a new WordPress page:**
   - Title: "Age Estimator Settings" (or your preference)
   - Add the shortcode to the page content
   - Publish the page

2. **Add to your menu:**
   - Go to Appearance > Menus
   - Add the settings page to your menu
   - You can add it to:
     - Main navigation
     - User account menu
     - Footer menu

### Method 3: Programmatic Integration

#### In your theme's functions.php:
```php
// Add settings link to user menu
add_filter('wp_nav_menu_items', 'add_age_estimator_settings_link', 10, 2);
function add_age_estimator_settings_link($items, $args) {
    if (is_user_logged_in() && $args->theme_location == 'primary') {
        $settings_url = home_url('/age-estimator-settings/');
        $items .= '<li><a href="' . $settings_url . '">Age Settings</a></li>';
    }
    return $items;
}
```

#### Add to WooCommerce My Account:
```php
// Add to WooCommerce account menu
add_filter('woocommerce_account_menu_items', 'add_age_settings_to_account');
function add_age_settings_to_account($items) {
    $items['age-settings'] = __('Age Estimator Settings', 'age-estimator');
    return $items;
}

add_action('woocommerce_account_age-settings_endpoint', 'age_settings_content');
function age_settings_content() {
    echo do_shortcode('[age_estimator_settings_enhanced]');
}
```

## File Structure

```
Age-estimator-live/
├── includes/
│   └── user-settings/
│       ├── class-user-settings.php (Original)
│       └── class-user-settings-enhanced.php (Enhanced version)
├── css/
│   ├── user-settings.css (Original styles)
│   └── user-settings-enhanced.css (Enhanced styles)
├── js/
│   ├── user-settings.js (Original JavaScript)
│   └── user-settings-enhanced.js (Enhanced JavaScript)
└── templates/
    └── settings/
        └── frontend-settings.php (Optional template)
```

## Activation

### To enable the enhanced settings:

1. **Update your main plugin file** (`age-estimator.php`):
```php
// In the load_includes() method, add:
$enhanced_settings = AGE_ESTIMATOR_PATH . 'includes/user-settings/class-user-settings-enhanced.php';
if (file_exists($enhanced_settings)) {
    require_once $enhanced_settings;
}
```

2. **The system will automatically load:**
   - Enhanced CSS when the shortcode is detected
   - Enhanced JavaScript with all functionality
   - Chart.js for statistics visualization

## User Permissions

### Default Permissions:
- **Logged-in users**: Full access to all personal settings
- **Administrators**: Can view and manage all user settings
- **Shop managers**: Can access retail mode features
- **Guests**: Read-only access to view defaults

### Custom Permissions:
```php
// Add custom capability for settings access
add_action('init', 'add_age_estimator_capabilities');
function add_age_estimator_capabilities() {
    $role = get_role('subscriber');
    $role->add_cap('manage_age_settings');
}
```

## JavaScript API

### Access Settings Manager:
```javascript
// Get the settings manager instance
const settingsManager = window.ageEstimatorSettings;

// Listen for settings changes
jQuery(document).on('ageEstimator:settingsSaved', function(e, section, data) {
    console.log('Settings saved:', section, data);
});

// Programmatically save settings
settingsManager.saveSettings('general', {
    show_results: true,
    minimum_age: 21
});

// Export settings programmatically
settingsManager.exportSettings();

// Get current settings
const currentSettings = settingsManager.settings.currentSettings;
```

## REST API Endpoints

The plugin provides REST API endpoints for settings management:

### Get User Settings:
```
GET /wp-json/age-estimator/v1/user-settings
Authorization: Bearer {token}
```

### Update User Settings:
```
POST /wp-json/age-estimator/v1/user-settings
Authorization: Bearer {token}
Content-Type: application/json

{
    "section": "general",
    "settings": {
        "show_results": true,
        "minimum_age": 18
    }
}
```

## Hooks and Filters

### PHP Hooks:

```php
// Modify default settings
add_filter('age_estimator_default_settings', function($defaults) {
    $defaults['minimum_age'] = 21;
    return $defaults;
});

// After settings save
add_action('age_estimator_settings_saved', function($user_id, $section, $settings) {
    // Custom logic after settings are saved
}, 10, 3);

// Customize settings sections
add_filter('age_estimator_settings_sections', function($sections) {
    // Add custom section
    $sections['custom'] = array(
        'title' => 'Custom Settings',
        'icon' => '⚙️',
        'fields' => array('custom_field_1', 'custom_field_2')
    );
    return $sections;
});
```

### JavaScript Hooks:

```javascript
// Before settings save
jQuery(document).on('ageEstimator:beforeSave', function(e, section, data) {
    // Validate or modify data before saving
});

// After settings load
jQuery(document).on('ageEstimator:settingsLoaded', function(e, settings) {
    // React to loaded settings
});
```

## Styling Customization

### CSS Variables:
The enhanced settings use CSS custom properties that can be overridden:

```css
.age-estimator-settings-enhanced {
    --primary-color: #667eea;
    --primary-dark: #5a67d8;
    --secondary-color: #764ba2;
    --success-color: #28a745;
    --danger-color: #dc3545;
    --text-dark: #2c3e50;
    --border-color: #e9ecef;
}
```

### Custom Theme:
```css
/* In your theme's CSS */
.age-estimator-settings-enhanced[data-theme="custom"] {
    --primary-color: #your-brand-color;
    --secondary-color: #your-accent-color;
}
```

## Security Features

1. **Nonce Verification**: All AJAX requests are protected with WordPress nonces
2. **Capability Checks**: User permissions are verified for all operations
3. **Data Sanitization**: All input is sanitized before storage
4. **PIN Encryption**: Retail PINs are hashed using WordPress password hashing
5. **XSS Protection**: All output is properly escaped

## Performance Optimization

1. **Lazy Loading**: Settings panels load on demand
2. **Auto-save**: Changes are automatically saved every 60 seconds
3. **Debounced Inputs**: Range sliders and search use debouncing
4. **Cached Settings**: Settings are cached in browser for instant access
5. **Conditional Scripts**: JavaScript and CSS only load when needed

## Troubleshooting

### Common Issues:

1. **Settings not saving:**
   - Check browser console for JavaScript errors
   - Verify user is logged in
   - Check nonce is valid
   - Ensure proper permissions

2. **Styles not loading:**
   - Clear WordPress cache
   - Check if shortcode is properly placed
   - Verify CSS file paths

3. **JavaScript errors:**
   - Ensure jQuery is loaded
   - Check for conflicts with other plugins
   - Verify Chart.js is loaded for statistics

### Debug Mode:
```javascript
// Enable debug mode in console
window.ageEstimatorDebug = true;

// This will log all settings operations
```

## Migration from Original Settings

If you have users with existing settings from the original system:

```php
// Run this once to migrate settings
function migrate_user_settings() {
    $users = get_users();
    foreach ($users as $user) {
        // Get old settings
        $old_face_distance = get_user_meta($user->ID, 'age_estimator_face_tracking_distance', true);
        
        if ($old_face_distance) {
            // Map to new setting name
            update_user_meta($user->ID, 'age_estimator_face_sensitivity', $old_face_distance);
        }
    }
}
// Run once: migrate_user_settings();
```

## Browser Compatibility

- **Chrome/Edge**: Full support (v90+)
- **Firefox**: Full support (v88+)
- **Safari**: Full support (v14+)
- **Mobile browsers**: Responsive design, touch-optimized

## Support

For issues or questions:
1. Check the browser console for errors
2. Enable debug mode for detailed logging
3. Check the WordPress error log
4. Ensure all files are properly uploaded
5. Verify WordPress and PHP versions meet requirements

## Future Enhancements

Planned features for next version:
- [ ] Preset configurations (Quick setup templates)
- [ ] A/B testing for settings
- [ ] Analytics integration
- [ ] Webhook notifications
- [ ] Multi-language support
- [ ] Role-based setting restrictions
- [ ] Setting profiles (switch between configurations)
- [ ] Backup scheduling
- [ ] API rate limiting controls
- [ ] Custom detection algorithms

## License

This enhanced settings system is part of the Age Estimator Live plugin and follows the same license terms.
