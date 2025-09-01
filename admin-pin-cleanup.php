<?php
/**
 * PIN Cleanup Admin Tool
 * Add this to run cleanup from WordPress admin
 */

// Add admin menu item for cleanup
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'PIN Protection Cleanup',
        'PIN Cleanup',
        'manage_options',
        'pin-cleanup',
        'age_estimator_pin_cleanup_page'
    );
});

function age_estimator_pin_cleanup_page() {
    // Handle cleanup action
    if (isset($_POST['cleanup_pins']) && check_admin_referer('pin_cleanup_nonce')) {
        global $wpdb;
        
        // Clean up PIN sessions
        $result1 = $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'age_estimator_pin_session_time'");
        $result2 = $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'simple_pin_temp_access'");
        $result3 = $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'age_estimator_pin_temp_access'");
        
        $total_cleaned = $result1 + $result2 + $result3;
        
        echo '<div class="notice notice-success"><p><strong>✅ Cleanup Complete!</strong> Removed ' . $total_cleaned . ' PIN session records.</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h1>🔓 PIN Protection Cleanup</h1>
        
        <div class="card" style="max-width: 800px;">
            <h2>Current Status</h2>
            <p><strong>✅ PIN Protection:</strong> Disabled in plugin code</p>
            <p><strong>🎯 Settings Access:</strong> Direct access for logged-in users</p>
            <p><strong>🔧 Cleanup:</strong> Remove any remaining PIN session data</p>
        </div>
        
        <div class="card" style="max-width: 800px;">
            <h2>Quick Cleanup</h2>
            <p>Click the button below to clear all PIN session data from the database:</p>
            
            <form method="post" style="margin: 20px 0;">
                <?php wp_nonce_field('pin_cleanup_nonce'); ?>
                <button type="submit" name="cleanup_pins" class="button button-primary button-large">
                    🧹 Clean Up PIN Sessions
                </button>
            </form>
            
            <p><em>This will remove all PIN session timestamps and temporary access flags.</em></p>
        </div>
        
        <div class="card" style="max-width: 800px;">
            <h2>Test Your Settings</h2>
            <p>After cleanup, test your settings page access:</p>
            <a href="<?php echo home_url('?page_id=29'); ?>" class="button button-secondary" target="_blank">
                🎯 Open Settings Page
            </a>
            <p><em>You should see settings directly without any PIN prompts.</em></p>
        </div>
        
        <div class="card" style="max-width: 800px; background: #f0f8ff;">
            <h2>✅ What's Already Done</h2>
            <ul>
                <li>✅ PIN protection disabled in main plugin file</li>
                <li>✅ All PIN verification hooks removed</li>
                <li>✅ PIN protection scripts prevented from loading</li>
                <li>✅ JavaScript override added to hide PIN forms</li>
                <li>✅ Enhanced settings filter overridden</li>
            </ul>
        </div>
    </div>
    
    <style>
    .card { padding: 20px; background: white; border: 1px solid #ccd0d4; margin: 20px 0; border-radius: 4px; }
    .button-large { font-size: 16px !important; padding: 12px 24px !important; height: auto !important; }
    </style>
    <?php
}
