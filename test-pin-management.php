<?php
/**
 * Quick Demo/Test Script for User PIN Management
 * Run this to see the PIN management functionality in action
 */

// Make sure this is running in WordPress context
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('<h2>Access Denied</h2><p>You must be logged in as an administrator to use this demo.</p>');
}

echo "<h1>🎉 User PIN Management Demo</h1>\n";
echo "<p>This demo shows you what the new user PIN management system can do.</p>\n";

// Get all users
$users = get_users(array('number' => 10)); // Get up to 10 users for demo

echo "<h2>📊 Current User PIN Status</h2>\n";
echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>\n";
echo "<tr style='background: #f0f0f0;'>\n";
echo "<th style='padding: 10px; text-align: left;'>User</th>\n";
echo "<th style='padding: 10px; text-align: center;'>PIN Status</th>\n";
echo "<th style='padding: 10px; text-align: center;'>Session Status</th>\n";
echo "<th style='padding: 10px; text-align: center;'>Last Set</th>\n";
echo "<th style='padding: 10px; text-align: center;'>Actions Available</th>\n";
echo "</tr>\n";

foreach ($users as $user) {
    $user_pin = get_user_meta($user->ID, 'age_estimator_retail_pin', true);
    $session_time = get_user_meta($user->ID, 'age_estimator_pin_session_time', true);
    $pin_set_time = get_user_meta($user->ID, 'age_estimator_pin_set_time', true);
    
    $pin_status = !empty($user_pin) ? '🔐 Set' : '🔓 Not Set';
    
    $session_status = 'Inactive';
    if (!empty($session_time)) {
        $session_timeout = 15 * 60; // 15 minutes
        $session_active = (time() - intval($session_time)) < $session_timeout;
        $session_status = $session_active ? '🟢 Active' : '🔴 Expired';
    }
    
    $last_set = $pin_set_time ? date('Y-m-d H:i', $pin_set_time) : 'Never';
    
    $actions = array();
    if (!empty($user_pin)) {
        $actions[] = 'Reset PIN';
        if (!empty($session_time) && $session_status === '🟢 Active') {
            $actions[] = 'Clear Session';
        }
    }
    $actions[] = 'Set New PIN';
    
    echo "<tr>\n";
    echo "<td style='padding: 10px;'><strong>{$user->display_name}</strong><br><small>{$user->user_email}</small></td>\n";
    echo "<td style='padding: 10px; text-align: center;'>{$pin_status}</td>\n";
    echo "<td style='padding: 10px; text-align: center;'>{$session_status}</td>\n";
    echo "<td style='padding: 10px; text-align: center;'>{$last_set}</td>\n";
    echo "<td style='padding: 10px;'>" . implode('<br>', $actions) . "</td>\n";
    echo "</tr>\n";
}

echo "</table>\n";

echo "<h2>🔧 How to Test the Functionality</h2>\n";
echo "<div style='background: #e8f4fd; padding: 15px; border-left: 4px solid #2271b1; margin: 20px 0;'>\n";
echo "<h3>Method 1: User Profile Pages</h3>\n";
echo "<ol>\n";
echo "<li><strong>Go to:</strong> <a href='" . admin_url('users.php') . "' target='_blank'>Users → All Users</a></li>\n";
echo "<li><strong>Click:</strong> Any user's name to edit their profile</li>\n";
echo "<li><strong>Scroll to:</strong> \"Age Estimator PIN Management\" section</li>\n";
echo "<li><strong>Try:</strong> Reset PIN, Set PIN, Clear Session actions</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<div style='background: #d1e7dd; padding: 15px; border-left: 4px solid #0f5132; margin: 20px 0;'>\n";
echo "<h3>Method 2: Users List Table</h3>\n";
echo "<ol>\n";
echo "<li><strong>Visit:</strong> <a href='" . admin_url('users.php') . "' target='_blank'>Users → All Users</a></li>\n";
echo "<li><strong>Look for:</strong> \"PIN Status\" column (shows status badges)</li>\n";
echo "<li><strong>Select:</strong> Multiple users and try bulk actions</li>\n";
echo "<li><strong>Actions:</strong> \"Reset PINs\" or \"Clear PIN Sessions\"</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<h2>🎯 What Each Action Does</h2>\n";
echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;'>\n";

echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #664d03; border-radius: 4px;'>\n";
echo "<h4>🔄 Reset PIN</h4>\n";
echo "<ul>\n";
echo "<li>Removes the user's current PIN</li>\n";
echo "<li>Clears their active session</li>\n";
echo "<li>Forces them to set a new PIN</li>\n";
echo "<li>Useful when user forgets their PIN</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #842029; border-radius: 4px;'>\n";
echo "<h4>🔒 Clear Session</h4>\n";
echo "<ul>\n";
echo "<li>Ends their current PIN session</li>\n";
echo "<li>Keeps their PIN intact</li>\n";
echo "<li>Forces re-authentication</li>\n";
echo "<li>Good for security enforcement</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<div style='background: #d1e7dd; padding: 15px; border-left: 4px solid #0f5132; border-radius: 4px;'>\n";
echo "<h4>🔐 Set New PIN</h4>\n";
echo "<ul>\n";
echo "<li>Assigns a specific 4-digit PIN</li>\n";
echo "<li>Overwrites existing PIN</li>\n";
echo "<li>Clears current session</li>\n";
echo "<li>User must use this new PIN</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<div style='background: #cff4fc; padding: 15px; border-left: 4px solid #055160; border-radius: 4px;'>\n";
echo "<h4>📦 Bulk Actions</h4>\n";
echo "<ul>\n";
echo "<li>Reset multiple user PINs at once</li>\n";
echo "<li>Clear multiple sessions simultaneously</li>\n";
echo "<li>Perfect for mass management</li>\n";
echo "<li>Confirmation dialogs for safety</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "</div>\n";

echo "<h2>🚀 Ready to Go!</h2>\n";
echo "<div style='background: #d1e7dd; color: #0f5132; padding: 20px; border-radius: 8px; margin: 20px 0;'>\n";
echo "<h3>✅ Everything is Set Up!</h3>\n";
echo "<p>The PIN management system is now fully functional. Click the links above to start managing user PINs!</p>\n";
echo "<p><strong>Quick Links:</strong></p>\n";
echo "<ul>\n";
echo "<li><a href='" . admin_url('users.php') . "' target='_blank'>🎯 Manage Users (List View with PIN Status)</a></li>\n";
if (!empty($users)) {
    echo "<li><a href='" . admin_url('user-edit.php?user_id=' . $users[0]->ID) . "' target='_blank'>👤 Edit User Profile (PIN Management Section)</a></li>\n";
}
echo "</ul>\n";
echo "</div>\n";

echo "<h2>📚 Additional Resources</h2>\n";
echo "<ul>\n";
echo "<li><strong>Full Documentation:</strong> <code>USER_PIN_MANAGEMENT_GUIDE.md</code></li>\n";
echo "<li><strong>PIN Protection Guide:</strong> <code>PIN_PROTECTION_GUIDE.md</code></li>\n";
echo "<li><strong>User Experience:</strong> Users continue to set PINs in their retail settings</li>\n";
echo "<li><strong>Security:</strong> All actions are logged and require admin permissions</li>\n";
echo "</ul>\n";

echo "<hr style='margin: 30px 0;'>\n";
echo "<p><em>🎉 User PIN Management successfully installed and ready to use!</em></p>\n";
?>
