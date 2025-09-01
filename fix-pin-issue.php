<?php
/**
 * Quick PIN Issue Fix
 * Run this file in your browser to diagnose and fix PIN authentication issues
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

// Make sure user is logged in
if (!is_user_logged_in()) {
    echo '<h1>Please log in first</h1>';
    echo '<p><a href="' . wp_login_url($_SERVER['REQUEST_URI']) . '">Login</a></p>';
    exit;
}

$user_id = get_current_user_id();
$current_user = wp_get_current_user();

echo "<h1>🔧 PIN Issue Fix Tool</h1>";
echo "<p>User: {$current_user->display_name} (ID: {$user_id})</p>";

// Check current PIN status
echo "<h2>Current PIN Status</h2>";
$stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
echo "<p><strong>PIN in database:</strong> " . (!empty($stored_pin) ? '🔐 Set (' . strlen($stored_pin) . ' chars)' : '🔓 Not Set') . "</p>";

if (!empty($stored_pin)) {
    // Test if it's hashed or plain text
    $is_hashed = (strlen($stored_pin) > 10 && strpos($stored_pin, '$') !== false);
    echo "<p><strong>PIN format:</strong> " . ($is_hashed ? '✅ Properly Hashed' : '⚠️ Plain Text (needs fixing)') . "</p>";
    
    // Show first few characters for verification (safely)
    if (!$is_hashed && strlen($stored_pin) === 4) {
        echo "<p><strong>PIN preview:</strong> " . str_repeat('*', strlen($stored_pin)) . " (4 digits - looks correct)</p>";
    }
} else {
    echo "<p>ℹ️ No PIN found in database</p>";
}

// Handle form submission for PIN fix
if (isset($_POST['action'])) {
    echo "<hr>";
    echo "<h2>🔧 Fix Applied</h2>";
    
    if ($_POST['action'] === 'set_pin' && !empty($_POST['new_pin'])) {
        $new_pin = sanitize_text_field($_POST['new_pin']);
        
        // Validate PIN
        if (preg_match('/^\d{4}$/', $new_pin)) {
            // Hash the PIN properly
            $hashed_pin = wp_hash_password($new_pin);
            
            // Save the hashed PIN
            $result = update_user_meta($user_id, 'age_estimator_retail_pin', $hashed_pin);
            
            if ($result) {
                echo "<div style='background: #d1e7dd; padding: 15px; border: 1px solid #badbcc; border-radius: 4px; margin: 15px 0;'>";
                echo "<h3>✅ PIN Set Successfully!</h3>";
                echo "<p>Your PIN has been properly hashed and stored.</p>";
                echo "<p><strong>Next steps:</strong> Try accessing your settings page now with PIN: <strong>{$new_pin}</strong></p>";
                echo "</div>";
                
                // Update the displayed status
                $stored_pin = $hashed_pin;
            } else {
                echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 15px 0;'>";
                echo "<h3>❌ PIN Save Failed</h3>";
                echo "<p>Could not save the PIN to the database.</p>";
                echo "</div>";
            }
        } else {
            echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 4px; margin: 15px 0;'>";
            echo "<h3>⚠️ Invalid PIN</h3>";
            echo "<p>PIN must be exactly 4 digits (numbers only).</p>";
            echo "</div>";
        }
    } elseif ($_POST['action'] === 'fix_existing') {
        if (!empty($stored_pin)) {
            // Check if it's already hashed
            $is_hashed = (strlen($stored_pin) > 10 && strpos($stored_pin, '$') !== false);
            
            if (!$is_hashed) {
                // It's plain text, let's hash it
                $hashed_pin = wp_hash_password($stored_pin);
                $result = update_user_meta($user_id, 'age_estimator_retail_pin', $hashed_pin);
                
                if ($result) {
                    echo "<div style='background: #d1e7dd; padding: 15px; border: 1px solid #badbcc; border-radius: 4px; margin: 15px 0;'>";
                    echo "<h3>✅ PIN Fixed Successfully!</h3>";
                    echo "<p>Your existing PIN has been properly hashed.</p>";
                    echo "<p><strong>Your PIN remains the same:</strong> Try accessing settings now with your original PIN.</p>";
                    echo "</div>";
                } else {
                    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 15px 0;'>";
                    echo "<h3>❌ Fix Failed</h3>";
                    echo "<p>Could not update the PIN in the database.</p>";
                    echo "</div>";
                }
            } else {
                echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 15px 0;'>";
                echo "<h3>ℹ️ PIN Already Properly Formatted</h3>";
                echo "<p>Your PIN is already properly hashed. The issue might be elsewhere.</p>";
                echo "</div>";
            }
        }
    } elseif ($_POST['action'] === 'test_pin' && !empty($_POST['test_pin'])) {
        $test_pin = sanitize_text_field($_POST['test_pin']);
        
        if (!empty($stored_pin)) {
            $is_valid = wp_check_password($test_pin, $stored_pin);
            
            echo "<div style='background: " . ($is_valid ? "#d1e7dd" : "#f8d7da") . "; padding: 15px; border: 1px solid " . ($is_valid ? "#badbcc" : "#f5c6cb") . "; border-radius: 4px; margin: 15px 0;'>";
            echo "<h3>" . ($is_valid ? "✅ PIN Test: VALID" : "❌ PIN Test: INVALID") . "</h3>";
            
            if ($is_valid) {
                echo "<p>Great! Your PIN works correctly. The issue might be in the frontend validation.</p>";
                echo "<p>Try clearing your browser cache and cookies, then test again.</p>";
            } else {
                echo "<p>The PIN you entered doesn't match what's stored in the database.</p>";
                echo "<p>Use the 'Set New PIN' option below to fix this.</p>";
            }
            echo "</div>";
        } else {
            echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 4px; margin: 15px 0;'>";
            echo "<h3>⚠️ No PIN to Test</h3>";
            echo "<p>No PIN is stored in the database.</p>";
            echo "</div>";
        }
    }
}

// Show fix options
echo "<hr>";
echo "<h2>🛠️ Fix Options</h2>";

?>
<style>
.fix-option {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin: 15px 0;
}
.fix-option h3 {
    margin-top: 0;
    color: #495057;
}
.btn {
    background: #007cba;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    margin: 5px 0;
}
.btn:hover {
    background: #005a87;
}
.btn-success {
    background: #28a745;
}
.btn-success:hover {
    background: #1e7e34;
}
.btn-warning {
    background: #ffc107;
    color: #212529;
}
.btn-warning:hover {
    background: #e0a800;
}
input[type="password"], input[type="text"] {
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
    width: 150px;
    text-align: center;
    font-family: monospace;
}
</style>

<div class="fix-option">
    <h3>🎯 Option 1: Test Your Current PIN</h3>
    <p>Enter the PIN you think you set (1234) to test if it works:</p>
    <form method="post" style="margin: 15px 0;">
        <input type="hidden" name="action" value="test_pin">
        <input type="password" name="test_pin" placeholder="1234" maxlength="4" pattern="\d{4}" required>
        <button type="submit" class="btn btn-warning">🧪 Test PIN</button>
    </form>
</div>

<?php if (!empty($stored_pin) && strlen($stored_pin) === 4): ?>
<div class="fix-option">
    <h3>🔧 Option 2: Fix Existing PIN (Convert to Proper Format)</h3>
    <p>Your PIN appears to be stored as plain text. Click below to convert it to the proper hashed format:</p>
    <form method="post" style="margin: 15px 0;">
        <input type="hidden" name="action" value="fix_existing">
        <button type="submit" class="btn btn-success">🔨 Fix Existing PIN</button>
    </form>
    <small>This will keep your current PIN but store it securely.</small>
</div>
<?php endif; ?>

<div class="fix-option">
    <h3>🆕 Option 3: Set New PIN</h3>
    <p>Set a completely new PIN (this will override any existing PIN):</p>
    <form method="post" style="margin: 15px 0;">
        <input type="hidden" name="action" value="set_pin">
        <input type="password" name="new_pin" placeholder="1234" maxlength="4" pattern="\d{4}" required>
        <button type="submit" class="btn">🔐 Set New PIN</button>
    </form>
    <small>Enter exactly 4 digits (numbers only).</small>
</div>

<hr>
<h2>📋 Technical Details</h2>
<details>
    <summary><strong>Click to view technical information</strong></summary>
    <div style="background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 4px; font-family: monospace; font-size: 12px;">
        <p><strong>Database Key:</strong> age_estimator_retail_pin</p>
        <p><strong>User ID:</strong> <?php echo $user_id; ?></p>
        <p><strong>Stored Value:</strong> <?php echo !empty($stored_pin) ? substr($stored_pin, 0, 20) . '...' : 'empty'; ?></p>
        <p><strong>Value Length:</strong> <?php echo !empty($stored_pin) ? strlen($stored_pin) : 0; ?> characters</p>
        
        <?php
        // Check for other potential PIN fields
        $all_meta = get_user_meta($user_id);
        $pin_related = array();
        foreach ($all_meta as $key => $value) {
            if (stripos($key, 'pin') !== false || stripos($key, 'age_estimator') !== false) {
                $pin_related[$key] = $value[0];
            }
        }
        
        if (!empty($pin_related)) {
            echo "<p><strong>Related meta fields:</strong></p>";
            echo "<ul>";
            foreach ($pin_related as $key => $value) {
                echo "<li><strong>$key:</strong> " . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "</li>";
            }
            echo "</ul>";
        }
        ?>
    </div>
</details>

<hr>
<p>
    <a href="<?php echo admin_url('users.php'); ?>" class="btn">👥 Go to Users</a>
    <a href="<?php echo get_permalink(); ?>" class="btn">🔄 Refresh</a>
</p>

<?php
// Clean up any debug output
ob_end_flush();
?>
