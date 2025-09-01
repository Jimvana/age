# Quick Setup Guide - Frontend Settings Page

## 🚀 Getting Started in 3 Easy Steps

### Step 1: Create a New Page
1. Go to **WordPress Admin → Pages → Add New**
2. Title your page "Settings" or "My Settings"
3. In the page content, add this shortcode:
```
[age_estimator_settings_enhanced]
```

### Step 2: Save and Publish
1. Click **Publish** or **Update**
2. View the page to see your settings panel

### Step 3: Add to Navigation (Optional)
1. Go to **Appearance → Menus**
2. Add your new settings page to your menu
3. Save the menu

---

## 📋 Available Shortcodes

### Basic Settings Panel
```
[age_estimator_user_settings]
```
*Simple, original settings interface*

### Enhanced Settings Panel
```
[age_estimator_settings_enhanced]
```
*Modern, feature-rich interface with all options*

### Customized Enhanced Panel
```
[age_estimator_settings_enhanced theme="dark" layout="tabs" show_stats="true"]
```
*Dark theme with tab navigation*

---

## 🎨 Customization Options

### Theme Options
- `theme="light"` - Light mode (default)
- `theme="dark"` - Dark mode
- `theme="auto"` - Follows system preference

### Layout Options
- `layout="sidebar"` - Sidebar navigation (default)
- `layout="tabs"` - Tab navigation
- `layout="accordion"` - Accordion style

### Feature Toggles
- `show_stats="true/false"` - Show/hide statistics
- `allow_export="true/false"` - Enable/disable export

---

## 🔗 Direct Links to Sections

You can link directly to specific settings sections:

```html
<a href="/your-settings-page/#general">General Settings</a>
<a href="/your-settings-page/#detection">Face Detection</a>
<a href="/your-settings-page/#retail">Retail Mode</a>
<a href="/your-settings-page/#privacy">Privacy</a>
<a href="/your-settings-page/#notifications">Notifications</a>
<a href="/your-settings-page/#advanced">Advanced</a>
<a href="/your-settings-page/#stats">Statistics</a>
```

---

## 👤 User Access Requirements

### Login Required
- Users must be logged in to access settings
- Non-logged users are redirected to login page

### Optional: Restrict by User Role
Add to your theme's `functions.php`:
```php
// Only allow administrators
add_filter('age_estimator_settings_access', function($access) {
    return current_user_can('manage_options');
});
```

---

## 🎯 Common Use Cases

### 1. Member Dashboard Page
Create a dashboard with multiple sections:
```html
<h2>Welcome, [display_name]!</h2>

<!-- Quick Stats -->
<div class="member-stats">
    [age_estimator_user_stats]
</div>

<!-- Settings Panel -->
<div class="member-settings">
    [age_estimator_settings_enhanced layout="accordion"]
</div>
```

### 2. Kiosk Settings Page
For retail/kiosk setups:
```html
[age_estimator_settings_enhanced 
    theme="light" 
    layout="tabs" 
    show_stats="false" 
    allow_export="false"]
```

### 3. Admin Configuration Page
Full-featured admin interface:
```html
[age_estimator_settings_enhanced 
    theme="auto" 
    layout="sidebar" 
    show_stats="true" 
    allow_export="true"]
```

---

## 🛠️ Troubleshooting

### Settings Not Showing?
1. Check if plugin is activated
2. Verify user is logged in
3. Clear browser cache
4. Check browser console for errors

### Styles Look Wrong?
1. Check for theme conflicts
2. Try adding to page with no sidebar
3. Use page template: `page-settings.php`

### Can't Save Settings?
1. Check user permissions
2. Verify AJAX is working
3. Check PHP error logs
4. Ensure write permissions on uploads folder

---

## 📱 Mobile Optimization

The settings panel is fully responsive and works on:
- Smartphones (iOS/Android)
- Tablets (iPad/Android tablets)
- Desktop browsers
- Touch-enabled devices

### Mobile-Specific Features
- Collapsible navigation
- Touch-friendly controls
- Optimized form inputs
- Swipe gestures (tabs layout)

---

## 🔐 Security Features

### Built-in Protection
- ✅ CSRF protection (nonces)
- ✅ User isolation
- ✅ Input sanitization
- ✅ SQL injection prevention
- ✅ XSS protection
- ✅ Password hashing for PINs

### Additional Security
For extra security, add to `.htaccess`:
```apache
# Protect settings pages
<FilesMatch "settings">
    Require valid-user
</FilesMatch>
```

---

## 📊 Usage Tracking

### View Statistics
The settings panel includes usage statistics:
- Total age checks
- Success/failure rates
- Average ages
- Daily trends

### Export Data
Users can export their data:
1. Go to Statistics section
2. Click "Export Statistics"
3. Choose format (CSV/JSON)

---

## 🎨 Custom Styling

### Add Custom CSS
```css
/* Change primary color */
.age-estimator-settings-enhanced {
    --primary-color: #your-color;
}

/* Custom button style */
.age-estimator-settings-enhanced .btn-primary {
    background: linear-gradient(135deg, #color1, #color2);
}
```

### Override Templates
Copy template files to your theme:
```
/your-theme/age-estimator/settings-panel.php
/your-theme/age-estimator/settings-form.php
```

---

## 📝 Examples in Different Page Builders

### Elementor
1. Add "Shortcode" widget
2. Enter: `[age_estimator_settings_enhanced]`

### Gutenberg Block Editor
1. Add "Shortcode" block
2. Enter: `[age_estimator_settings_enhanced]`

### WPBakery/Visual Composer
1. Add "Raw HTML" element
2. Enter: `[age_estimator_settings_enhanced]`

### Divi Builder
1. Add "Code" module
2. Enter: `[age_estimator_settings_enhanced]`

---

## ⌨️ Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl+S` | Save current section |
| `Ctrl+E` | Export settings |
| `Shift+?` | Show help |
| `Esc` | Close dialogs |

---

## 📞 Support

### Documentation
- Main documentation: `/FRONTEND_SETTINGS_README.md`
- API reference: `/docs/api-reference.md`
- Hooks guide: `/docs/hooks-filters.md`

### Getting Help
1. Check browser console for errors
2. Enable WordPress debug mode
3. Review server error logs
4. Contact support with details

---

## ✅ Checklist for Launch

- [ ] Create settings page
- [ ] Add shortcode
- [ ] Test as different user roles
- [ ] Add to navigation menu
- [ ] Customize theme/colors
- [ ] Test on mobile devices
- [ ] Set up user permissions
- [ ] Configure email notifications
- [ ] Test import/export
- [ ] Review security settings

---

## 🎉 You're Ready!

Your frontend settings page is now ready to use. Users can:
- Customize their experience
- Manage privacy settings
- Configure retail mode
- View statistics
- Export their data

For advanced customization, see the full documentation.
