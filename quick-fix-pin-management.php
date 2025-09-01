<?php
/**
 * Quick Fix for PIN Management Loading Issue
 * This will force the PIN management system to load properly
 */

// Make sure this is running in WordPress context
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

echo "<h1>🔧 PIN Management Quick Fix</h1>\n";

// Check admin status first
if (!is_user_logged_in()) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #842029; margin: 20px 0;'>\n";
    echo "<h3>❌ Not Logged In</h3>\n";
    echo "<p>You need to be logged in to WordPress admin to use this fix.</p>\n";
    echo "<p><a href='" . wp_login_url() . "' style='background: #0073aa; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🔑 Login to WordPress</a></p>\n";
    echo "</div>\n";
    exit;
}

if (!current_user_can('manage_options')) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #842029; margin: 20px 0;'>\n";
    echo "<h3>❌ Permission Denied</h3>\n";
    echo "<p>You need administrator privileges to run this fix.</p>\n";
    echo "</div>\n";
    exit;
}

echo "<div style='background: #d1e7dd; padding: 15px; border-left: 4px solid #0f5132; margin: 20px 0;'>\n";
echo "<h3>✅ Ready to Fix</h3>\n";
echo "<p>You're logged in as: <strong>{$current_user->display_name}</strong></p>\n";
echo "</div>\n";

// Step 1: Check current status
echo "<h2>📊 Current Status</h2>\n";
$pin_manager_loaded = class_exists('AgeEstimatorUserPinManager');
echo "<p><strong>PIN Manager Class:</strong> " . ($pin_manager_loaded ? '✅ Loaded' : '❌ Not Loaded') . "</p>\n";

// Step 2: Force load the class
echo "<h2>🔧 Applying Fix</h2>\n";

try {
    $class_file = AGE_ESTIMATOR_PATH . 'includes/class-user-pin-manager.php';
    
    if (!file_exists($class_file)) {
        throw new Exception("Class file not found: $class_file");
    }
    
    echo "<p>✅ Found class file</p>\n";
    
    // Load the class
    require_once $class_file;
    echo "<p>✅ Class file loaded</p>\n";
    
    // Check if class exists now
    if (!class_exists('AgeEstimatorUserPinManager')) {
        throw new Exception("Class still not available after loading file");
    }
    
    echo "<p>✅ Class is now available</p>\n";
    
    // Initialize the class
    $instance = AgeEstimatorUserPinManager::get_instance();
    echo "<p>✅ Instance created</p>\n";
    
    // Force run the init method
    $instance->init();
    echo "<p>✅ Initialization complete</p>\n";
    
    echo "<div style='background: #d1e7dd; padding: 15px; border-left: 4px solid #0f5132; margin: 20px 0;'>\n";
    echo "<h3>🎉 Success!</h3>\n";
    echo "<p>PIN management system is now loaded and initialized.</p>\n";
    echo "<p><strong>Next steps:</strong></p>\n";
    echo "<ol>\n";
    echo "<li>Visit the users page to see the PIN Status column</li>\n";
    echo "<li>Edit any user profile to see the PIN management section</li>\n";
    echo "<li>The system should now work properly</li>\n";
    echo "</ol>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #842029; margin: 20px 0;'>\n";
    echo "<h3>❌ Fix Failed</h3>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
    echo "</div>\n";
}

// Step 3: Create permanent fix by updating the main plugin file
echo "<h2>🛠️ Permanent Fix</h2>\n";

if (current_user_can('manage_options')) {
    
    // Check if we need to update the main plugin file
    $main_plugin_file = AGE_ESTIMATOR_PATH . 'age-estimator.php';
    $plugin_content = file_get_contents($main_plugin_file);
    
    if (strpos($plugin_content, 'class-user-pin-manager.php') === false) {
        echo "<p>⚠️ The main plugin file needs to be updated to permanently load the PIN manager.</p>\n";
        
        echo "<form method='post' style='margin: 20px 0;'>\n";
        echo "<input type='hidden' name='action' value='update_main_plugin'>\n";
        echo "<button type='submit' style='background: #00a32a; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>🔧 Apply Permanent Fix</button>\n";
        echo "<p style='font-size: 12px; color: #666;'>This will modify the main plugin file to always load the PIN manager.</p>\n";
        echo "</form>\n";
        
        // Handle permanent fix
        if (isset($_POST['action']) && $_POST['action'] === 'update_main_plugin') {
            try {
                // Create backup
                $backup_file = $main_plugin_file . '.backup.' . date('Y-m-d-H-i-s');
                copy($main_plugin_file, $backup_file);
                echo "<p>✅ Backup created: " . basename($backup_file) . "</p>\n";
                
                // Check the current content
                if (strpos($plugin_content, 'class-user-pin-manager.php') === false) {
                    // Find the right place to insert the code (after admin email settings)
                    $search = "            // Load admin email settings\n            \$admin_email_file = AGE_ESTIMATOR_PATH . 'includes/admin-email-settings.php';\n            if (file_exists(\$admin_email_file)) {\n                require_once \$admin_email_file;\n            }";
                    
                    $replace = $search . "\n            \n            // Load user PIN management for admin\n            \$user_pin_manager_file = AGE_ESTIMATOR_PATH . 'includes/class-user-pin-manager.php';\n            if (file_exists(\$user_pin_manager_file)) {\n                require_once \$user_pin_manager_file;\n            }";
                    
                    $updated_content = str_replace($search, $replace, $plugin_content);
                    
                    if ($updated_content !== $plugin_content) {
                        file_put_contents($main_plugin_file, $updated_content);
                        echo "<p>✅ Main plugin file updated successfully!</p>\n";
                        
                        echo "<div style='background: #d1e7dd; padding: 15px; border-left: 4px solid #0f5132; margin: 20px 0;'>\n";
                        echo "<h3>🎉 Permanent Fix Applied!</h3>\n";
                        echo "<p>The PIN management system will now load automatically on every page load.</p>\n";
                        echo "</div>\n";
                    } else {
                        echo "<p>⚠️ Could not find the right place to insert code. Manual fix may be needed.</p>\n";
                    }
                } else {
                    echo "<p>ℹ️ Plugin file already contains PIN manager loading code.</p>\n";
                }
                
            } catch (Exception $e) {
                echo "<p>❌ Error applying permanent fix: " . $e->getMessage() . "</p>\n";
            }
        }
        
    } else {
        echo "<p>✅ Main plugin file already configured correctly.</p>\n";
    }
}

// Step 4: Test links
echo "<h2>🧪 Test the Fix</h2>\n";
echo "<div style='background: #cff4fc; padding: 15px; border-left: 4px solid #055160; margin: 20px 0;'>\n";
echo "<h3>Test Links</h3>\n";
echo "<p><a href='" . admin_url('users.php') . "' target='_blank' style='background: #0073aa; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>🎯 Users List (Check PIN Status Column)</a></p>\n";

// Get first user for profile test
$users = get_users(array('number' => 1));
if (!empty($users)) {
    echo "<p><a href='" . admin_url('user-edit.php?user_id=' . $users[0]->ID) . "' target='_blank' style='background: #00a32a; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>👤 User Profile (Check PIN Management Section)</a></p>\n";
}

echo "<p><a href='" . plugin_dir_url(__FILE__) . "pin-diagnostic.php' target='_blank' style='background: #6c757d; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🔍 Run Diagnostic Again</a></p>\n";
echo "</div>\n";

// Clean up instructions
echo "<h2>🧹 Clean Up</h2>\n";
echo "<p>After confirming everything works, you can delete these temporary files:</p>\n";
echo "<ul>\n";
echo "<li><code>pin-diagnostic.php</code></li>\n";
echo "<li><code>quick-fix-pin-management.php</code> (this file)</li>\n";
echo "<li><code>test-pin-management.php</code></li>\n";
echo "</ul>\n";

?>
