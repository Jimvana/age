<?php
/**
 * Quick PIN Status Check
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

if (!is_user_logged_in()) {
    echo '<h1>Please log in first</h1>';
    exit;
}

$user_id = get_current_user_id();
$user = wp_get_current_user();
$stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);

echo "<h1>📊 PIN Status Check</h1>";
echo "<p><strong>User:</strong> {$user->display_name} (ID: $user_id)</p>";

echo "<h2>Current Status:</h2>";
echo "<ul>";
echo "<li><strong>PIN exists:</strong> " . (!empty($stored_pin) ? '✅ Yes' : '❌ No') . "</li>";

if (!empty($stored_pin)) {
    echo "<li><strong>PIN length:</strong> " . strlen($stored_pin) . " characters</li>";
    echo "<li><strong>Format:</strong> " . (preg_match('/^\d{4}$/', $stored_pin) ? '📝 Plain text' : '🔒 Hashed') . "</li>";
    echo "<li><strong>First 10 chars:</strong> <code>" . substr($stored_pin, 0, 10) . "...</code></li>";
}

echo "</ul>";

if (!empty($stored_pin)) {
    echo "<h2>🧪 Test Your PIN:</h2>";
    echo "<p>Enter your PIN to test the verification:</p>";
    echo "<form method='post' style='margin: 20px 0;'>";
    echo "<input type='password' name='test_pin' placeholder='Enter PIN' maxlength='4' style='padding: 10px; font-size: 16px; margin-right: 10px;'>";
    echo "<input type='submit' name='test' value='Test PIN' style='padding: 10px 20px; background: #007cba; color: white; border: none; cursor: pointer;'>";
    echo "</form>";

    if (isset($_POST['test']) && isset($_POST['test_pin'])) {
        $test_pin = sanitize_text_field($_POST['test_pin']);
        echo "<h3>Test Results:</h3>";
        
        if (preg_match('/^\d{4}$/', $test_pin)) {
            $wp_check = wp_check_password($test_pin, $stored_pin);
            $plain_check = ($test_pin === $stored_pin);
            
            echo "<div style='padding: 15px; margin: 10px 0; border-radius: 6px; background: " . ($wp_check ? '#d4edda' : '#f8d7da') . "; border: 1px solid " . ($wp_check ? '#c3e6cb' : '#f5c6cb') . ";'>";
            echo "<p><strong>WordPress hash check:</strong> " . ($wp_check ? '✅ PASS' : '❌ FAIL') . "</p>";
            echo "<p><strong>Plain text check:</strong> " . ($plain_check ? '✅ MATCH' : '❌ NO MATCH') . "</p>";
            
            if ($wp_check) {
                echo "<p><strong>Result:</strong> ✅ Your PIN is working correctly!</p>";
            } elseif ($plain_check) {
                echo "<p><strong>Result:</strong> ⚠️ PIN works but needs to be upgraded to hashed format.</p>";
            } else {
                echo "<p><strong>Result:</strong> ❌ PIN does not match. Either wrong PIN or corrupted data.</p>";
            }
            echo "</div>";
        } else {
            echo "<p style='color: red;'>Please enter exactly 4 digits</p>";
        }
    }
}

echo "<h2>🔧 Quick Actions:</h2>";
echo "<p><a href='" . plugin_dir_url(__FILE__) . "fix-pin-issue-comprehensive.php?test=1' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Apply Comprehensive Fix</a></p>";
echo "<p><a href='" . home_url() . "' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-top: 10px; display: inline-block;'>Go to Settings Page</a></p>";
?>