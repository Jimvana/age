<?php
/**
 * Quick PIN Status & Reset Tool
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

if (!is_user_logged_in()) {
    echo '<h1>Please log in first</h1>';
    echo '<p><a href="' . wp_login_url($_SERVER['REQUEST_URI']) . '">Login</a></p>';
    exit;
}

$user_id = get_current_user_id();
$user = wp_get_current_user();
$stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);

echo "<h1>🔍 PIN Status Check</h1>";
echo "<p><strong>User:</strong> {$user->display_name} (ID: $user_id)</p>";

if (empty($stored_pin)) {
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; border: 1px solid #ffeaa7; margin: 20px 0;'>";
    echo "<h2>📝 No PIN Set</h2>";
    echo "<p>You don't have a PIN set yet. Go to your settings page and set a 4-digit PIN in the Retail Mode section.</p>";
    echo "</div>";
} else {
    $is_hashed = (strlen($stored_pin) > 10 || strpos($stored_pin, '$') !== false);
    
    if ($is_hashed) {
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; border: 1px solid #f5c6cb; margin: 20px 0;'>";
        echo "<h2>🔒 Hashed PIN Found (Problem!)</h2>";
        echo "<p><strong>Current PIN:</strong> <code>[Hashed - " . strlen($stored_pin) . " characters]</code></p>";
        echo "<p><strong>Issue:</strong> Your PIN is stored as a hash, which is causing verification problems.</p>";
        echo "<p><strong>Solution:</strong> Click the button below to reset it so you can set a new plain number PIN.</p>";
        
        if (isset($_POST['reset_my_pin'])) {
            delete_user_meta($user_id, 'age_estimator_retail_pin');
            echo "<div style='background: #d4edda; padding: 15px; margin: 15px 0; border-radius: 4px; border: 1px solid #c3e6cb;'>";
            echo "<strong>✅ PIN Reset!</strong> Your hashed PIN has been removed. Go to settings to set a new 4-digit PIN.";
            echo "</div>";
            echo "<script>setTimeout(() => location.reload(), 2000);</script>";
        } else {
            echo "<form method='post' style='margin-top: 15px;'>";
            echo "<button type='submit' name='reset_my_pin' style='background: #dc3545; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;'>🔄 Reset My PIN</button>";
            echo "</form>";
        }
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; border: 1px solid #c3e6cb; margin: 20px 0;'>";
        echo "<h2>✅ Plain Number PIN (Good!)</h2>";
        echo "<p><strong>Current PIN:</strong> <code>$stored_pin</code></p>";
        echo "<p><strong>Status:</strong> Your PIN is stored correctly as a plain 4-digit number.</p>";
        echo "<p><strong>Test it:</strong> Try entering <strong>$stored_pin</strong> on the settings page.</p>";
        echo "</div>";
        
        // Test form
        echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 8px; border: 1px solid #bee5eb; margin: 20px 0;'>";
        echo "<h3>🧪 Quick Test</h3>";
        echo "<p>Enter your PIN to test verification:</p>";
        echo "<form method='post'>";
        echo "<input type='password' name='test_pin' placeholder='Enter PIN' maxlength='4' style='padding: 10px; font-size: 16px; margin-right: 10px;'>";
        echo "<button type='submit' name='test' style='padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer;'>Test</button>";
        echo "</form>";
        
        if (isset($_POST['test']) && isset($_POST['test_pin'])) {
            $test_pin = sanitize_text_field($_POST['test_pin']);
            if ($test_pin === $stored_pin) {
                echo "<div style='background: #d4edda; padding: 10px; margin-top: 10px; border-radius: 4px; color: #155724;'>";
                echo "✅ <strong>PIN CORRECT!</strong> Verification working perfectly.";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; padding: 10px; margin-top: 10px; border-radius: 4px; color: #721c24;'>";
                echo "❌ <strong>PIN WRONG!</strong> You entered: '$test_pin', stored PIN: '$stored_pin'";
                echo "</div>";
            }
        }
        echo "</div>";
    }
}

echo "<h2>🔧 Quick Actions:</h2>";
echo "<p><a href='" . plugin_dir_url(__FILE__) . "simplify-pin-system.php' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>📱 Apply Simplified PIN System</a></p>";
echo "<p style='margin-top: 10px;'><a href='" . home_url() . "' style='background: #007cba; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px;'>🔐 Go to Settings Page</a></p>";

echo "<h3>📋 What to do next:</h3>";
if (empty($stored_pin)) {
    echo "<ol>";
    echo "<li>Apply the simplified PIN system above</li>";
    echo "<li>Go to your settings page</li>";
    echo "<li>Set a 4-digit PIN (like 1234)</li>";
    echo "<li>Test PIN access</li>";
    echo "</ol>";
} elseif ($is_hashed) {
    echo "<ol>";
    echo "<li>Click 'Reset My PIN' above</li>";
    echo "<li>Apply the simplified PIN system</li>";
    echo "<li>Go to settings and set a new 4-digit PIN</li>";
    echo "<li>Test PIN access</li>";
    echo "</ol>";
} else {
    echo "<ol>";
    echo "<li>Apply the simplified PIN system above (to fix verification)</li>";
    echo "<li>Go to your settings page</li>";
    echo "<li>Enter your PIN: <strong>$stored_pin</strong></li>";
    echo "<li>Should work perfectly!</li>";
    echo "</ol>";
}
?>