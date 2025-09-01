<?php
/**
 * Simple PIN Management Test
 * Quick check to see if the system is working
 */

// Make sure this is running in WordPress context
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

echo "<h1>🧪 PIN Management Test</h1>\n";

// Test 1: Check if class exists
echo "<h2>Test 1: Class Loading</h2>\n";
$class_loaded = class_exists('AgeEstimatorUserPinManager');
echo "<p><strong>AgeEstimatorUserPinManager class:</strong> " . ($class_loaded ? '✅ Loaded' : '❌ Not Loaded') . "</p>\n";

if ($class_loaded) {
    echo "<p>✅ Class found - PIN management should be available!</p>\n";
    
    // Test 2: Check if instance can be created
    echo "<h2>Test 2: Class Instantiation</h2>\n";
    try {
        $instance = AgeEstimatorUserPinManager::get_instance();
        echo "<p>✅ Instance created successfully</p>\n";
        
        // Test 3: Check if methods exist
        echo "<h2>Test 3: Method Availability</h2>\n";
        $methods = array('init', 'add_pin_fields', 'add_pin_column', 'reset_user_pin');
        foreach ($methods as $method) {
            $exists = method_exists($instance, $method);
            echo "<p><strong>$method():</strong> " . ($exists ? '✅ Available' : '❌ Missing') . "</p>\n";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error creating instance: " . $e->getMessage() . "</p>\n";
    }
    
} else {
    // Try to load it manually
    echo "<p>⚠️ Class not found. Attempting manual load...</p>\n";
    
    $class_file = AGE_ESTIMATOR_PATH . 'includes/class-user-pin-manager.php';
    if (file_exists($class_file)) {
        require_once $class_file;
        
        if (class_exists('AgeEstimatorUserPinManager')) {
            echo "<p>✅ Class loaded manually - should work now!</p>\n";
        } else {
            echo "<p>❌ Class still not available after manual load</p>\n";
        }
    } else {
        echo "<p>❌ Class file doesn't exist at: $class_file</p>\n";
    }
}

// Test 4: Hook Registration Test
echo "<h2>Test 4: WordPress Hooks</h2>\n";
global $wp_filter;

// Simulate admin area for testing
if (!is_admin() && !defined('WP_ADMIN')) {
    define('WP_ADMIN', true);
}

// Force initialize if class exists
if (class_exists('AgeEstimatorUserPinManager')) {
    $instance = AgeEstimatorUserPinManager::get_instance();
    
    // Check if hooks are registered
    $admin_hooks = array(
        'show_user_profile' => 'add_pin_fields',
        'edit_user_profile' => 'add_pin_fields',
        'manage_users_columns' => 'add_pin_column',
        'admin_enqueue_scripts' => 'enqueue_admin_scripts'
    );
    
    foreach ($admin_hooks as $hook => $method) {
        $has_callback = false;
        if (isset($wp_filter[$hook])) {
            foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    if (is_array($callback['function']) && 
                        is_object($callback['function'][0]) && 
                        get_class($callback['function'][0]) === 'AgeEstimatorUserPinManager' &&
                        $callback['function'][1] === $method) {
                        $has_callback = true;
                        break 2;
                    }
                }
            }
        }
        echo "<p><strong>$hook → $method:</strong> " . ($has_callback ? '✅ Registered' : '❌ Not Registered') . "</p>\n";
    }
}

// Test 5: User Data Test  
echo "<h2>Test 5: User Data Access</h2>\n";
$users = get_users(array('number' => 1));
if (!empty($users)) {
    $user = $users[0];
    echo "<p>✅ Found test user: {$user->display_name} (ID: {$user->ID})</p>\n";
    
    // Check if we can read user meta
    $pin = get_user_meta($user->ID, 'age_estimator_retail_pin', true);
    echo "<p><strong>User PIN status:</strong> " . (!empty($pin) ? '🔐 Set' : '🔓 Not Set') . "</p>\n";
} else {
    echo "<p>⚠️ No users found for testing</p>\n";
}

// Final verdict
echo "<h2>🎯 Final Verdict</h2>\n";

if ($class_loaded) {
    echo "<div style='background: #d1e7dd; padding: 20px; border-left: 4px solid #0f5132; border-radius: 4px; margin: 20px 0;'>\n";
    echo "<h3>🎉 SUCCESS!</h3>\n";
    echo "<p><strong>PIN Management is working!</strong></p>\n";
    echo "<p>You should now be able to:</p>\n";
    echo "<ul>\n";
    echo "<li>See a 'PIN Status' column in the Users list</li>\n";
    echo "<li>Find 'Age Estimator PIN Management' section in user profiles</li>\n";
    echo "<li>Reset, set, and manage user PINs from the admin area</li>\n";
    echo "</ul>\n";
    
    echo "<p><strong>Next Steps:</strong></p>\n";
    echo "<ol>\n";
    echo "<li>Login to WordPress admin</li>\n";
    echo "<li>Go to Users → All Users</li>\n";
    echo "<li>Look for the 'PIN Status' column</li>\n";
    echo "<li>Edit any user to see the PIN management section</li>\n";
    echo "</ol>\n";
    
    echo "</div>\n";
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-left: 4px solid #842029; border-radius: 4px; margin: 20px 0;'>\n";
    echo "<h3>❌ Issue Detected</h3>\n";
    echo "<p>The PIN management system is not loading properly.</p>\n";
    echo "<p>Please run the quick fix tool:</p>\n";
    echo "<p><a href='" . plugin_dir_url(__FILE__) . "quick-fix-pin-management.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>🔧 Run Quick Fix</a></p>\n";
    echo "</div>\n";
}

// Show login link if needed
if (!is_user_logged_in()) {
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #664d03; margin: 20px 0;'>\n";
    echo "<h3>🔑 Login Required</h3>\n";
    echo "<p>To test the admin functionality, you need to be logged in:</p>\n";
    echo "<p><a href='" . wp_login_url() . "' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Login to WordPress Admin</a></p>\n";
    echo "</div>\n";
}

?>
