<?php
/**
 * PIN Protection Cleanup Script
 * Run this once to clear all PIN sessions and temporary access
 */

// Load WordPress - try multiple common paths
if (!defined('ABSPATH')) {
    $possible_paths = array(
        dirname(__FILE__) . '/../../../../wp-config.php',  // Standard WordPress structure
        dirname(__FILE__) . '/../../../../../wp-config.php', // If in subdirectory
        $_SERVER['DOCUMENT_ROOT'] . '/wp-config.php',      // Document root
        '/Users/video/DevKinsta/public/local-model-age-estin/wp-config.php', // Direct path for local
        dirname(__FILE__) . '/../../../../../../wp-config.php' // Another level up
    );
    
    $wp_loaded = false;
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            require_once($path);
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        // Try to auto-detect from current directory
        $current_dir = __DIR__;
        while ($current_dir !== '/' && $current_dir !== '') {
            if (file_exists($current_dir . '/wp-config.php')) {
                require_once($current_dir . '/wp-config.php');
                $wp_loaded = true;
                break;
            }
            $current_dir = dirname($current_dir);
        }
    }
    
    if (!$wp_loaded) {
        die('<h1>WordPress Auto-Detection Failed</h1><p>Could not automatically find WordPress. Please run this script from WordPress admin instead.</p><p><a href="/wp-admin/admin.php?page=age-estimator">Go to WordPress Admin</a></p>');
    }
}

// Clean up all PIN-related user meta for all users
function cleanup_pin_sessions() {
    global $wpdb;
    
    $cleaned_count = 0;
    
    // Remove PIN session data
    $result1 = $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'age_estimator_pin_session_time'");
    $result2 = $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'simple_pin_temp_access'");
    $result3 = $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'age_estimator_pin_temp_access'");
    
    $cleaned_count = $result1 + $result2 + $result3;
    
    return $cleaned_count;
}

// Run cleanup
$cleaned = cleanup_pin_sessions();

// Output results
?>
<!DOCTYPE html>
<html>
<head>
    <title>PIN Protection Cleanup Complete</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .button { display: inline-block; background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔓 PIN Protection Cleanup Complete</h1>
    
    <div class="success">
        <h2>✅ Cleanup Results</h2>
        <p><strong>PIN Sessions Cleared:</strong> <?php echo $cleaned; ?> records</p>
        <p><strong>Status:</strong> All PIN protection completely removed</p>
    </div>
    
    <div class="info">
        <h2>📋 What was cleaned:</h2>
        <ul>
            <li>✅ PIN session timestamps</li>
            <li>✅ Temporary access flags</li>
            <li>✅ All PIN-related user metadata</li>
        </ul>
        
        <h2>🚀 What happens now:</h2>
        <ul>
            <li>✅ Settings page accessible without PIN</li>
            <li>✅ No PIN forms will appear</li>
            <li>✅ Direct access for logged-in users</li>
            <li>✅ All settings sections available</li>
        </ul>
    </div>
    
    <p style="text-align: center;">
        <a href="<?php echo home_url('?page_id=29'); ?>" class="button">🎯 Test Settings Access Now</a>
    </p>
    
    <h3>🧪 Testing Steps:</h3>
    <ol>
        <li>Make sure you're logged into WordPress</li>
        <li>Clear your browser cache (Ctrl+F5 or Cmd+Shift+R)</li>
        <li>Visit your settings page</li>
        <li>You should see settings directly (no PIN form)</li>
    </ol>
    
    <h3>🔍 If you still see issues:</h3>
    <ul>
        <li>Try incognito/private browsing mode</li>
        <li>Hard refresh the page</li>
        <li>Check browser console for any errors</li>
        <li>The green notice should confirm PIN protection is disabled</li>
    </ul>
    
    <div style="background: #fff3cd; padding: 15px; border-radius: 6px; margin: 20px 0;">
        <p><strong>📝 Note:</strong> You can delete this cleanup file now - it's only needed once.</p>
    </div>
</body>
</html>
