<?php
/**
 * Clear WordPress Caches
 */

// Include WordPress
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

echo "<h2>Cache Clearing Tool</h2>\n";

// Clear WordPress object cache
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "✅ WordPress object cache flushed<br>\n";
}

// Clear transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%'");
echo "✅ All transients cleared<br>\n";

// Clear any Age Estimator specific caches
delete_transient('age_estimator_db_checked');
echo "✅ Age Estimator transients cleared<br>\n";

echo "<div style='background: green; color: white; padding: 15px; margin: 20px 0;'>\n";
echo "<h3>✅ All Caches Cleared!</h3>\n";
echo "<p>Now try accessing your admin settings page again:</p>\n";
echo "<a href='/wp-admin/admin.php?page=age-estimator-settings' target='_blank' style='color: white; background: darkgreen; padding: 10px; text-decoration: none;'>Open Settings Page</a>\n";
echo "</div>\n";
?>
