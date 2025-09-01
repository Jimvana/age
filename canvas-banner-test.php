<?php
/**
 * Canvas Banner Test Page
 * Test the new canvas banner system
 */

// Add admin menu item for testing
add_action('admin_menu', function() {
    add_submenu_page(
        'options-general.php',
        'Canvas Banner Test',
        'Canvas Banner Test',
        'manage_options',
        'age-estimator-canvas-test',
        function() {
            echo '<div class="wrap">';
            echo '<h1>🎨 Canvas Banner Test</h1>';
            echo '<div style="background: #f0f0f1; padding: 15px; border-radius: 5px; margin: 20px 0;">';
            echo '<h3>🔧 Browser Console Commands:</h3>';
            echo '<ul>';
            echo '<li><code>debugCanvasBanner()</code> - Check banner state</li>';
            echo '<li><code>forceShowCanvasBanner()</code> - Force show banner</li>';
            echo '<li><code>forceHideCanvasBanner()</code> - Force hide banner</li>';
            echo '</ul>';
            echo '<h3>📋 Instructions:</h3>';
            echo '<ol>';
            echo '<li>Configure your banner ad in Age Estimator settings</li>';
            echo '<li>Start the camera below</li>';
            echo '<li>Enter fullscreen mode</li>';
            echo '<li>Banner should appear as canvas overlay</li>';
            echo '</ol>';
            echo '</div>';
            echo '<div style="margin-top: 20px; border: 1px solid #ccc; padding: 20px; background: white;">';
            echo do_shortcode('[age_estimator]');
            echo '</div>';
            echo '<script>';
            echo 'document.addEventListener("DOMContentLoaded", function() {';
            echo '  document.querySelector(".age-estimator-photo-container").classList.add("age-estimator-debug");';
            echo '});';
            echo '</script>';
            echo '</div>';
        }
    );
});
?>
