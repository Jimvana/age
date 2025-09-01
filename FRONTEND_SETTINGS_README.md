# Frontend Settings Page Documentation
## Age Estimator Live Plugin - Enhanced User Settings

### Overview
The Age Estimator Live plugin now includes a comprehensive frontend settings page that allows users to customize their age estimation experience without accessing the WordPress admin area.

### Features

#### 🎯 General Settings
- Display options (show/hide results, confidence scores)
- Result display duration
- Age gating configuration
- Minimum age requirements

#### 👤 Face Detection Settings
- Face match sensitivity adjustment
- Detection frequency control
- Face size parameters
- Multi-face detection options
- Age averaging samples

#### 🏪 Retail Mode (Challenge 25)
- Enable/disable retail compliance features
- Challenge age threshold
- Staff PIN protection
- Transaction logging
- Email notifications for compliance

#### 🔒 Privacy & Security
- Privacy mode (face blurring)
- Consent requirements
- Data retention periods
- Session timeout settings
- Two-factor authentication

#### 🔔 Notifications & Sounds
- Sound effects configuration
- Volume controls
- Custom sound uploads
- Visual feedback (screen flash)
- Success/failure colors

#### ⚡ Advanced Settings
- Detection mode selection (Local/AWS/Hybrid)
- Cache duration
- Hardware acceleration
- Experimental features (emotion/gender detection)
- Import/export settings

#### 📊 Statistics Dashboard
- Total scans counter
- Success/failure rates
- Average age calculations
- Daily usage charts
- Data export capabilities

### Installation

1. **Activate the Enhanced Settings**
   Add this to your theme's `functions.php` or a custom plugin:
   ```php
   // Load enhanced user settings
   require_once AGE_ESTIMATOR_PATH . 'includes/user-settings/class-user-settings-enhanced.php';
   ```

2. **Create a Settings Page**
   Create a new page in WordPress and add one of these shortcodes:

   **Basic Shortcode:**
   ```
   [age_estimator_user_settings]
   ```

   **Enhanced Shortcode with Options:**
   ```
   [age_estimator_settings_enhanced theme="auto" layout="sidebar" show_stats="true" allow_export="true"]
   ```

### Shortcode Parameters

| Parameter | Options | Default | Description |
|-----------|---------|---------|-------------|
| `theme` | `light`, `dark`, `auto` | `light` | Color theme for the settings panel |
| `layout` | `sidebar`, `tabs`, `accordion` | `sidebar` | Layout style for navigation |
| `show_stats` | `true`, `false` | `true` | Display statistics section |
| `allow_export` | `true`, `false` | `true` | Enable settings export/import |

### Usage Examples

#### Example 1: Basic Settings Page
```html
<!-- In your WordPress page -->
[age_estimator_user_settings]
```

#### Example 2: Dark Mode with Tabs
```html
[age_estimator_settings_enhanced theme="dark" layout="tabs"]
```

#### Example 3: Minimal Settings (No Stats)
```html
[age_estimator_settings_enhanced show_stats="false" allow_export="false"]
```

### User Access Control

#### Logged-in Users Only
The settings page requires users to be logged in. Non-logged-in users will see:
```
You must be logged in to access settings.
```

#### Role-Based Access (Optional)
Add this code to restrict access to specific user roles:
```php
add_filter('age_estimator_settings_access', function($has_access) {
    if (!current_user_can('manage_options')) {
        return false;
    }
    return $has_access;
});
```

### Programmatic Access

#### Get User Settings
```php
$user_id = get_current_user_id();
$face_sensitivity = get_user_meta($user_id, 'age_estimator_face_sensitivity', true);
$retail_mode = get_user_meta($user_id, 'age_estimator_retail_mode_enabled', true);
```

#### Set User Settings
```php
update_user_meta($user_id, 'age_estimator_minimum_age', 21);
update_user_meta($user_id, 'age_estimator_enable_sounds', '1');
```

#### JavaScript API
```javascript
// Get current settings
const settings = window.ageEstimatorSettings.settings.currentSettings;

// Save settings programmatically
jQuery.ajax({
    url: ageEstimatorEnhanced.ajaxUrl,
    type: 'POST',
    data: {
        action: 'age_estimator_save_user_settings',
        nonce: ageEstimatorEnhanced.nonce,
        section: 'general',
        settings: {
            show_results: true,
            minimum_age: 18
        }
    }
});
```

### Hooks and Filters

#### Available Actions
```php
// After settings are saved
do_action('age_estimator_settings_saved', $user_id, $section, $settings);

// Before settings are loaded
do_action('age_estimator_before_load_settings', $user_id);

// After settings are reset
do_action('age_estimator_settings_reset', $user_id, $section);
```

#### Available Filters
```php
// Modify default settings
add_filter('age_estimator_default_settings', function($defaults) {
    $defaults['minimum_age'] = 21;
    return $defaults;
});

// Modify saved settings before storage
add_filter('age_estimator_before_save_settings', function($settings, $user_id) {
    // Custom validation or modification
    return $settings;
}, 10, 2);

// Control settings access
add_filter('age_estimator_settings_access', function($has_access, $user_id) {
    // Custom access control logic
    return $has_access;
}, 10, 2);
```

### REST API Endpoints

The settings system includes REST API endpoints for headless applications:

#### Get Settings
```
GET /wp-json/age-estimator/v1/user-settings
Authorization: Bearer {token}
```

#### Update Settings
```
POST /wp-json/age-estimator/v1/user-settings
Content-Type: application/json
Authorization: Bearer {token}

{
    "section": "general",
    "settings": {
        "minimum_age": 21,
        "show_results": true
    }
}
```

### Styling Customization

#### CSS Variables
Override these CSS variables to customize the appearance:
```css
.age-estimator-settings-enhanced {
    --primary-color: #667eea;
    --primary-dark: #5a67d8;
    --secondary-color: #764ba2;
    --success-color: #28a745;
    --danger-color: #dc3545;
    --text-dark: #2c3e50;
    --text-muted: #6c757d;
    --border-color: #e9ecef;
}
```

#### Custom Styles
Add custom styles to your theme:
```css
/* Change primary button color */
.age-estimator-settings-enhanced .btn-primary {
    background: linear-gradient(135deg, #ff6b6b 0%, #ff8e53 100%);
}

/* Customize stat cards */
.age-estimator-settings-enhanced .stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
```

### Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl/Cmd + S` | Save current section |
| `Ctrl/Cmd + E` | Export settings |
| `Escape` | Close modals |
| `Tab` | Navigate between fields |

### Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari 14+, Chrome Mobile)

### Performance Considerations

1. **Auto-save**: Settings are auto-saved every 60 seconds if changes are detected
2. **Caching**: Settings are cached in browser memory to reduce server requests
3. **Lazy Loading**: Statistics and charts are loaded only when viewed
4. **Debouncing**: Input changes are debounced to prevent excessive saves

### Troubleshooting

#### Settings Not Saving
1. Check browser console for JavaScript errors
2. Verify nonce is valid
3. Ensure user is logged in
4. Check user permissions

#### Styles Not Loading
1. Clear browser cache
2. Check for CSS conflicts with theme
3. Verify file permissions

#### Camera Test Not Working
1. Ensure HTTPS is enabled (required for camera access)
2. Check browser permissions for camera
3. Verify Face-API.js is loaded

### Security Considerations

1. **PIN Storage**: Retail PINs are hashed using WordPress's `wp_hash_password()`
2. **Nonce Verification**: All AJAX requests require valid nonces
3. **User Isolation**: Each user's settings are completely isolated
4. **Data Sanitization**: All inputs are sanitized before storage
5. **SQL Injection Prevention**: Uses WordPress prepared statements

### Migration from Old Settings

If upgrading from the basic settings system:
```php
// Run migration
add_action('init', function() {
    if (get_option('age_estimator_settings_migrated') !== '2.0') {
        // Migrate old settings
        $users = get_users();
        foreach ($users as $user) {
            // Map old meta keys to new ones
            $old_value = get_user_meta($user->ID, 'old_meta_key', true);
            if ($old_value) {
                update_user_meta($user->ID, 'age_estimator_new_key', $old_value);
            }
        }
        update_option('age_estimator_settings_migrated', '2.0');
    }
});
```

### Support and Updates

For issues or feature requests:
1. Check the browser console for errors
2. Enable WordPress debug mode
3. Review server error logs
4. Contact support with detailed information

### Changelog

#### Version 2.0 (Current)
- Enhanced settings interface with modern UI
- Added statistics dashboard
- Implemented auto-save functionality
- Added import/export capabilities
- Improved mobile responsiveness
- Added dark mode support
- REST API integration
- Keyboard shortcuts
- Advanced face detection settings
- Retail mode enhancements

#### Version 1.0
- Basic settings panel
- Core functionality

### License
This settings system is part of the Age Estimator Live plugin and follows the same license terms.
