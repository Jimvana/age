<?php
/**
 * Quick Test Page for Frontend Settings
 * 
 * Access this file directly to see the settings page in action:
 * http://your-site.local/wp-content/plugins/Age-estimator-live/test-frontend-settings.php
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is logged in
if (!is_user_logged_in()) {
    auth_redirect();
}

// Get current user
$current_user = wp_get_current_user();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Age Estimator Settings - Test Page</title>
    <?php wp_head(); ?>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .test-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .test-header h1 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }
        .test-header p {
            margin: 5px 0 0;
            color: #666;
        }
        .user-info {
            float: right;
            text-align: right;
        }
        .user-info strong {
            color: #667eea;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .settings-demos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .demo-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .demo-card h3 {
            margin-top: 0;
            color: #333;
            font-size: 18px;
            margin-bottom: 15px;
        }
        .demo-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .demo-card pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 6px;
            font-size: 12px;
            overflow-x: auto;
            margin: 10px 0;
        }
        .demo-button {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .demo-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .main-settings {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 40px;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        .back-link {
            display: inline-block;
            margin: 20px 0;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 6px;
            transition: all 0.3s;
        }
        .back-link:hover {
            background: rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>
    <div class="test-header clearfix">
        <div class="user-info">
            <p>Logged in as: <strong><?php echo esc_html($current_user->display_name); ?></strong></p>
            <p>User ID: <?php echo $current_user->ID; ?> | Role: <?php echo implode(', ', $current_user->roles); ?></p>
        </div>
        <h1>🎯 Age Estimator Settings - Test Page</h1>
        <p>Test different configurations of the frontend settings panel</p>
    </div>

    <div class="container">
        <a href="<?php echo admin_url(); ?>" class="back-link">← Back to WordPress Admin</a>
        
        <div class="settings-demos">
            <div class="demo-card">
                <h3>🎨 Default Configuration</h3>
                <p>Standard settings panel with sidebar navigation, light theme, and all features enabled.</p>
                <pre>[age_estimator_settings_enhanced]</pre>
                <a href="#default" class="demo-button" onclick="loadDemo('default'); return false;">Load Demo</a>
            </div>
            
            <div class="demo-card">
                <h3>🌙 Dark Theme</h3>
                <p>Settings panel with dark theme for better visibility in low-light conditions.</p>
                <pre>[age_estimator_settings_enhanced theme="dark"]</pre>
                <a href="#dark" class="demo-button" onclick="loadDemo('dark'); return false;">Load Demo</a>
            </div>
            
            <div class="demo-card">
                <h3>📑 Tabs Layout</h3>
                <p>Horizontal tabs navigation instead of sidebar. Better for mobile devices.</p>
                <pre>[age_estimator_settings_enhanced layout="tabs"]</pre>
                <a href="#tabs" class="demo-button" onclick="loadDemo('tabs'); return false;">Load Demo</a>
            </div>
            
            <div class="demo-card">
                <h3>🔄 Accordion Layout</h3>
                <p>Collapsible sections for a more compact view. All sections visible at once.</p>
                <pre>[age_estimator_settings_enhanced layout="accordion"]</pre>
                <a href="#accordion" class="demo-button" onclick="loadDemo('accordion'); return false;">Load Demo</a>
            </div>
            
            <div class="demo-card">
                <h3>📊 Without Statistics</h3>
                <p>Settings panel without the statistics dashboard. Cleaner for basic users.</p>
                <pre>[age_estimator_settings_enhanced show_stats="false"]</pre>
                <a href="#no-stats" class="demo-button" onclick="loadDemo('no-stats'); return false;">Load Demo</a>
            </div>
            
            <div class="demo-card">
                <h3>🔒 No Export/Import</h3>
                <p>Disable settings export/import feature for security-conscious installations.</p>
                <pre>[age_estimator_settings_enhanced allow_export="false"]</pre>
                <a href="#no-export" class="demo-button" onclick="loadDemo('no-export'); return false;">Load Demo</a>
            </div>
            
            <div class="demo-card">
                <h3>🎯 Original Settings</h3>
                <p>The original, simpler settings interface. Basic functionality only.</p>
                <pre>[age_estimator_user_settings]</pre>
                <a href="#original" class="demo-button" onclick="loadDemo('original'); return false;">Load Demo</a>
            </div>
            
            <div class="demo-card">
                <h3>⚡ Full Featured</h3>
                <p>All features enabled with dark theme and tabs layout. Maximum functionality.</p>
                <pre>[age_estimator_settings_enhanced theme="dark" layout="tabs" show_stats="true" allow_export="true"]</pre>
                <a href="#full" class="demo-button" onclick="loadDemo('full'); return false;">Load Demo</a>
            </div>
        </div>
        
        <div class="main-settings" id="settings-container">
            <h2 style="text-align: center; color: #667eea; margin-bottom: 30px;">Select a Demo Above to Load Settings</h2>
            <p style="text-align: center; color: #666;">Choose any configuration from the cards above to see how the settings panel looks and works with different options.</p>
        </div>
        
        <div class="demo-card" style="max-width: 100%; margin-top: 40px;">
            <h3>📚 Quick Reference</h3>
            <p><strong>Available Shortcode Parameters:</strong></p>
            <ul style="color: #666; line-height: 1.8;">
                <li><strong>theme:</strong> "light" (default), "dark", or "auto" (follows system preference)</li>
                <li><strong>layout:</strong> "sidebar" (default), "tabs", or "accordion"</li>
                <li><strong>show_stats:</strong> "true" (default) or "false" - Show/hide statistics panel</li>
                <li><strong>allow_export:</strong> "true" (default) or "false" - Enable/disable settings export/import</li>
            </ul>
            
            <p><strong>Usage in WordPress:</strong></p>
            <ol style="color: #666; line-height: 1.8;">
                <li>Create a new page in WordPress</li>
                <li>Add the shortcode with your desired parameters</li>
                <li>Publish the page</li>
                <li>Add the page to your menu for easy user access</li>
            </ol>
            
            <p><strong>Files Location:</strong></p>
            <ul style="color: #666; line-height: 1.8;">
                <li>PHP Class: <code>/includes/user-settings/class-user-settings-enhanced.php</code></li>
                <li>JavaScript: <code>/js/user-settings-enhanced.js</code></li>
                <li>CSS: <code>/css/user-settings-enhanced.css</code></li>
                <li>Documentation: <code>/FRONTEND_SETTINGS_GUIDE.md</code></li>
            </ul>
        </div>
    </div>

    <script>
        function loadDemo(type) {
            const container = document.getElementById('settings-container');
            container.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner" style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite;"></div><p>Loading settings panel...</p></div>';
            
            let shortcode = '';
            switch(type) {
                case 'default':
                    shortcode = '[age_estimator_settings_enhanced]';
                    break;
                case 'dark':
                    shortcode = '[age_estimator_settings_enhanced theme="dark"]';
                    break;
                case 'tabs':
                    shortcode = '[age_estimator_settings_enhanced layout="tabs"]';
                    break;
                case 'accordion':
                    shortcode = '[age_estimator_settings_enhanced layout="accordion"]';
                    break;
                case 'no-stats':
                    shortcode = '[age_estimator_settings_enhanced show_stats="false"]';
                    break;
                case 'no-export':
                    shortcode = '[age_estimator_settings_enhanced allow_export="false"]';
                    break;
                case 'original':
                    shortcode = '[age_estimator_user_settings]';
                    break;
                case 'full':
                    shortcode = '[age_estimator_settings_enhanced theme="dark" layout="tabs" show_stats="true" allow_export="true"]';
                    break;
            }
            
            // Make AJAX call to render shortcode
            jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'age_estimator_render_shortcode',
                shortcode: shortcode,
                nonce: '<?php echo wp_create_nonce('age_estimator_test'); ?>'
            }, function(response) {
                if (response.success) {
                    container.innerHTML = response.data;
                    // Re-initialize any JavaScript
                    if (window.ageEstimatorSettings) {
                        window.ageEstimatorSettings = new AgeEstimatorSettingsManager();
                    }
                } else {
                    container.innerHTML = '<div style="padding: 20px; background: #f8d7da; color: #721c24; border-radius: 8px;">Error loading settings: ' + (response.data || 'Unknown error') + '</div>';
                }
            });
        }
        
        // Add spinning animation
        const style = document.createElement('style');
        style.textContent = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
        document.head.appendChild(style);
    </script>

    <?php wp_footer(); ?>
</body>
</html>

<?php
// Add AJAX handler for rendering shortcode
add_action('wp_ajax_age_estimator_render_shortcode', 'age_estimator_test_render_shortcode');
function age_estimator_test_render_shortcode() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_test')) {
        wp_send_json_error('Invalid security token');
    }
    
    $shortcode = stripslashes($_POST['shortcode']);
    $content = do_shortcode($shortcode);
    
    wp_send_json_success($content);
}
?>
