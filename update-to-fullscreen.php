<?php
/**
 * Age Estimator - Force Fullscreen Database Update
 * 
 * Run this once to update existing installations to use fullscreen mode only.
 * You can run this by visiting: yoursite.com/wp-content/plugins/Age-estimator-live/update-to-fullscreen.php
 * 
 * WARNING: Remove this file after running it once for security!
 */

// Simple security check
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    die('To run this update, visit: ' . $_SERVER['REQUEST_URI'] . '?confirm=yes');
}

// Load WordPress
require_once('../../../../wp-config.php');

// Update the database settings
update_option('age_estimator_display_style', 'fullscreen');
update_option('age_estimator_fullscreen_only_mode', true);

echo "<h1>✅ Age Estimator Updated to Fullscreen Mode</h1>";
echo "<p><strong>Database settings updated successfully!</strong></p>";
echo "<ul>";
echo "<li>Display style set to: <strong>fullscreen</strong></li>";
echo "<li>Fullscreen-only mode enabled: <strong>true</strong></li>";
echo "</ul>";

echo "<h2>📋 What was changed:</h2>";
echo "<ol>";
echo "<li><strong>Main plugin file (age-estimator.php):</strong> Modified render_shortcode function to force fullscreen mode</li>";
echo "<li><strong>Admin settings (includes/admin-settings.php):</strong> Removed display style dropdown and added fullscreen-only notice</li>";
echo "<li><strong>Template (templates/photo-inline.php):</strong> Added fullscreen functionality, buttons, and styling</li>";
echo "<li><strong>Database settings:</strong> Updated to use fullscreen as default and only option</li>";
echo "</ol>";

echo "<h2>🖥️ How to use:</h2>";
echo "<ul>";
echo "<li>All age estimator shortcodes will now display in fullscreen mode</li>";
echo "<li>Users can double-click the camera area to enter fullscreen</li>";
echo "<li>A fullscreen toggle button is available when the camera is running</li>";
echo "<li>Admin settings now show a notice about fullscreen-only mode</li>";
echo "</ul>";

echo "<h2>⚠️ Important:</h2>";
echo "<p style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 4px;'>";
echo "<strong>Security Warning:</strong> Please delete this file (update-to-fullscreen.php) now that the update is complete!";
echo "</p>";

echo "<p style='margin-top: 30px;'>";
echo "<a href='" . admin_url('admin.php?page=age-estimator-settings') . "' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Go to Age Estimator Settings</a>";
echo "</p>";

?>
