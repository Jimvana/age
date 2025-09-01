<?php
/**
 * PIN Management Diagnostic Tool
 * Check why PIN management isn't showing up
 */

// Make sure this is running in WordPress context
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

echo "<h1>🔍 PIN Management Diagnostic</h1>\n";

// Check if user is logged in
$current_user = wp_get_current_user();
echo "<h2>User Authentication Status</h2>\n";
echo "<p><strong>Logged in:</strong> " . (is_user_logged_in() ? 'Yes' : 'No') . "</p>\n";
if (is_user_logged_in()) {
    echo "<p><strong>User:</strong> {$current_user->display_name} ({$current_user->user_login})</p>\n";
    echo "<p><strong>User ID:</strong> {$current_user->ID}</p>\n";
    echo "<p><strong>User Role:</strong> " . implode(', ', $current_user->roles) . "</p>\n";
    
    // Check capabilities
    echo "<h3>Capabilities Check</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>manage_options:</strong> " . (current_user_can('manage_options') ? '✅ Yes' : '❌ No') . "</li>\n";
    echo "<li><strong>edit_users:</strong> " . (current_user_can('edit_users') ? '✅ Yes' : '❌ No') . "</li>\n";
    echo "<li><strong>list_users:</strong> " . (current_user_can('list_users') ? '✅ Yes' : '❌ No') . "</li>\n";
    echo "</ul>\n";
}

// Check if the class files exist
echo "<h2>File System Check</h2>\n";
$files_to_check = array(
    'Main Plugin' => AGE_ESTIMATOR_PATH . 'age-estimator.php',
    'User PIN Manager Class' => AGE_ESTIMATOR_PATH . 'includes/class-user-pin-manager.php',
    'Admin CSS' => AGE_ESTIMATOR_PATH . 'css/admin-user-pin.css',
    'Admin JS' => AGE_ESTIMATOR_PATH . 'js/admin-user-pin.js'
);

foreach ($files_to_check as $name => $path) {
    $exists = file_exists($path);
    echo "<p><strong>$name:</strong> " . ($exists ? '✅ Exists' : '❌ Missing') . " <code>$path</code></p>\n";
}

// Check if classes are loaded
echo "<h2>Class Loading Check</h2>\n";
echo "<p><strong>AgeEstimatorUserPinManager class:</strong> " . (class_exists('AgeEstimatorUserPinManager') ? '✅ Loaded' : '❌ Not Loaded') . "</p>\n";

// Check if hooks are registered
echo "<h2>WordPress Hooks Check</h2>\n";
global $wp_filter;

$hooks_to_check = array(
    'show_user_profile',
    'edit_user_profile', 
    'personal_options_update',
    'edit_user_profile_update',
    'manage_users_columns',
    'admin_enqueue_scripts'
);

foreach ($hooks_to_check as $hook) {
    $has_callbacks = isset($wp_filter[$hook]) && !empty($wp_filter[$hook]->callbacks);
    echo "<p><strong>$hook:</strong> " . ($has_callbacks ? '✅ Has callbacks' : '❌ No callbacks') . "</p>\n";
    
    if ($has_callbacks) {
        foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function']) && is_object($callback['function'][0])) {
                    $class_name = get_class($callback['function'][0]);
                    if (strpos($class_name, 'AgeEstimator') !== false) {
                        echo "<small style='margin-left: 20px;'>└─ Found: $class_name::{$callback['function'][1]} (priority: $priority)</small><br>\n";
                    }
                }
            }
        }
    }
}

// Check current screen
if (is_admin()) {
    $screen = get_current_screen();
    echo "<h2>Admin Screen Info</h2>\n";
    echo "<p><strong>Current Screen ID:</strong> " . ($screen ? $screen->id : 'Not available') . "</p>\n";
    echo "<p><strong>Is Admin:</strong> " . (is_admin() ? '✅ Yes' : '❌ No') . "</p>\n";
}

// Try to manually instantiate the class
echo "<h2>Manual Class Test</h2>\n";
try {
    if (class_exists('AgeEstimatorUserPinManager')) {
        echo "<p>✅ Class exists, attempting to get instance...</p>\n";
        $instance = AgeEstimatorUserPinManager::get_instance();
        echo "<p>✅ Instance created successfully!</p>\n";
    } else {
        echo "<p>❌ Class doesn't exist. Trying to load manually...</p>\n";
        
        $class_file = AGE_ESTIMATOR_PATH . 'includes/class-user-pin-manager.php';
        if (file_exists($class_file)) {
            require_once $class_file;
            echo "<p>✅ File loaded manually</p>\n";
            
            if (class_exists('AgeEstimatorUserPinManager')) {
                echo "<p>✅ Class now available!</p>\n";
                $instance = AgeEstimatorUserPinManager::get_instance();
                echo "<p>✅ Instance created!</p>\n";
            } else {
                echo "<p>❌ Class still not available after loading file</p>\n";
            }
        } else {
            echo "<p>❌ Class file doesn't exist</p>\n";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}

// Check plugin loading order
echo "<h2>Plugin Loading Check</h2>\n";
$active_plugins = get_option('active_plugins', array());
echo "<p><strong>Active Plugins:</strong></p>\n";
echo "<ul>\n";
foreach ($active_plugins as $plugin) {
    $highlight = (strpos($plugin, 'age-estimator') !== false || strpos($plugin, 'Age-estimator') !== false) ? ' style="background: yellow;"' : '';
    echo "<li$highlight>$plugin</li>\n";
}
echo "</ul>\n";

// Check WordPress version and PHP version
echo "<h2>Environment Check</h2>\n";
echo "<p><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</p>\n";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>\n";
echo "<p><strong>Plugin Path:</strong> " . AGE_ESTIMATOR_PATH . "</p>\n";
echo "<p><strong>Plugin URL:</strong> " . AGE_ESTIMATOR_URL . "</p>\n";

// Show what we need to fix
echo "<h2>🔧 Recommended Fixes</h2>\n";

if (!current_user_can('manage_options')) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #842029; margin: 20px 0;'>\n";
    echo "<h3>❌ Permission Issue</h3>\n";
    echo "<p>Your user account doesn't have the required 'manage_options' capability. You need to be an Administrator.</p>\n";
    echo "</div>\n";
}

if (!class_exists('AgeEstimatorUserPinManager')) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #842029; margin: 20px 0;'>\n";
    echo "<h3>❌ Class Loading Issue</h3>\n";
    echo "<p>The AgeEstimatorUserPinManager class is not loaded. This could be because:</p>\n";
    echo "<ul>\n";
    echo "<li>The file doesn't exist (check file paths above)</li>\n";
    echo "<li>The main plugin isn't loading the class properly</li>\n";
    echo "<li>There's a PHP syntax error in the class file</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
}

echo "<button onclick='window.location.reload()' style='background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 20px 0;'>🔄 Run Diagnosis Again</button>\n";

// Quick fix button
if (current_user_can('manage_options')) {
    echo "<h2>🚀 Quick Fix Tool</h2>\n";
    echo "<p>Click below to force-load and initialize the PIN management system:</p>\n";
    echo "<form method='post' style='margin: 20px 0;'>\n";
    echo "<input type='hidden' name='action' value='force_init_pin_management'>\n";
    echo "<button type='submit' style='background: #00a32a; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>🔧 Force Initialize PIN Management</button>\n";
    echo "</form>\n";
}

// Handle the quick fix
if (isset($_POST['action']) && $_POST['action'] === 'force_init_pin_management' && current_user_can('manage_options')) {
    echo "<div style='background: #d1e7dd; padding: 15px; border-left: 4px solid #0f5132; margin: 20px 0;'>\n";
    echo "<h3>🔧 Force Initialization Result</h3>\n";
    
    try {
        // Force load the class
        $class_file = AGE_ESTIMATOR_PATH . 'includes/class-user-pin-manager.php';
        if (file_exists($class_file)) {
            require_once $class_file;
            echo "<p>✅ Class file loaded</p>\n";
            
            // Initialize the class
            if (class_exists('AgeEstimatorUserPinManager')) {
                $instance = AgeEstimatorUserPinManager::get_instance();
                echo "<p>✅ Class instance created</p>\n";
                
                // Force run the init method
                if (method_exists($instance, 'init')) {
                    $instance->init();
                    echo "<p>✅ Init method called</p>\n";
                }
                
                echo "<p><strong>Success!</strong> Try visiting a user profile page now.</p>\n";
                echo "<p><a href='" . admin_url('users.php') . "' target='_blank' style='background: #0073aa; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🎯 Go to Users Page</a></p>\n";
            } else {
                echo "<p>❌ Class still not available after loading</p>\n";
            }
        } else {
            echo "<p>❌ Class file not found at: $class_file</p>\n";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error during initialization: " . $e->getMessage() . "</p>\n";
    }
    
    echo "</div>\n";
}

?>
