<?php
/**
 * Activate Canvas Banner Ad System
 * Age Estimator Live Plugin
 * 
 * Run this script to activate the canvas banner system
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Allow CLI access or if accessed directly for setup
    if (php_sapi_name() !== 'cli' && !isset($_GET['activate'])) {
        exit('To activate canvas banner system, add ?activate=1 to the URL');
    }
}

class CanvasBannerActivator {
    
    private $pluginDir;
    private $backupDir;
    
    public function __construct() {
        $this->pluginDir = dirname(__FILE__);
        $this->backupDir = $this->pluginDir . '/backup/canvas-activation-' . date('Y-m-d_H-i-s');
    }
    
    public function activate() {
        echo "<h2>🎨 Activating Canvas Banner Ad System</h2>\n";
        
        try {
            // Create backup
            $this->createBackup();
            
            // Update template
            $this->updateTemplate();
            
            // Update main plugin file
            $this->updateMainPlugin();
            
            // Create test page
            $this->createTestPage();
            
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
            echo "<h3>✅ Canvas Banner System Activated Successfully!</h3>\n";
            echo "<ul>\n";
            echo "<li>📁 Backup created: " . basename($this->backupDir) . "</li>\n";
            echo "<li>🔧 Template updated with canvas-banner-active class</li>\n";
            echo "<li>📜 Scripts enqueued in main plugin file</li>\n";
            echo "<li>🧪 Test page created: canvas-banner-test.php</li>\n";
            echo "</ul>\n";
            echo "</div>\n";
            
            echo "<h3>🧪 Test Your Canvas Banner:</h3>\n";
            echo "<ol>\n";
            echo "<li>Go to <strong>WordPress Admin > Settings > Canvas Banner Test</strong></li>\n";
            echo "<li>Configure your banner in Age Estimator settings</li>\n";
            echo "<li>Start camera and enter fullscreen</li>\n";
            echo "<li>Canvas banner should appear over camera view</li>\n";
            echo "</ol>\n";
            
            echo "<h3>🔧 Debug Commands (Browser Console):</h3>\n";
            echo "<ul>\n";
            echo "<li><code>debugCanvasBanner()</code> - Check banner state</li>\n";
            echo "<li><code>forceShowCanvasBanner()</code> - Force show banner</li>\n";
            echo "<li><code>forceHideCanvasBanner()</code> - Force hide banner</li>\n";
            echo "</ul>\n";
            
            return true;
            
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
            echo "<h3>❌ Activation Failed</h3>\n";
            echo "<p>Error: " . $e->getMessage() . "</p>\n";
            echo "</div>\n";
            return false;
        }
    }
    
    private function createBackup() {
        if (!is_dir($this->backupDir)) {
            if (!mkdir($this->backupDir, 0755, true)) {
                throw new Exception("Failed to create backup directory");
            }
        }
        
        $filesToBackup = [
            'templates/photo-inline.php',
            'age-estimator.php'
        ];
        
        foreach ($filesToBackup as $file) {
            $sourcePath = $this->pluginDir . '/' . $file;
            $backupPath = $this->backupDir . '/' . basename($file);
            
            if (file_exists($sourcePath)) {
                copy($sourcePath, $backupPath);
            }
        }
        
        echo "✅ Backup created\n";
    }
    
    private function updateTemplate() {
        $templatePath = $this->pluginDir . '/templates/photo-inline.php';
        
        if (!file_exists($templatePath)) {
            throw new Exception("Template file not found: " . $templatePath);
        }
        
        $content = file_get_contents($templatePath);
        
        // Add canvas-banner-active class if not already present
        if (strpos($content, 'canvas-banner-active') === false) {
            $content = str_replace(
                'class="age-estimator-photo-container age-estimator-photo-fullscreen',
                'class="age-estimator-photo-container age-estimator-photo-fullscreen canvas-banner-active',
                $content
            );
        }
        
        // Add canvas banner status indicator
        if (strpos($content, 'canvas-banner-status') === false) {
            $statusIndicator = '
    <!-- Canvas Banner Status (Debug) -->
    <div class="canvas-banner-status" id="canvas-banner-status">
        Canvas Banner: Ready
    </div>';
            
            $content = str_replace(
                '<!-- Fullscreen mode indicator -->',
                '<!-- Fullscreen mode indicator -->' . $statusIndicator,
                $content
            );
        }
        
        if (file_put_contents($templatePath, $content)) {
            echo "✅ Template updated\n";
        } else {
            throw new Exception("Failed to update template file");
        }
    }
    
    private function updateMainPlugin() {
        $mainFile = $this->pluginDir . '/age-estimator.php';
        
        if (!file_exists($mainFile)) {
            echo "⚠️ Main plugin file not found, skipping script enqueue\n";
            return;
        }
        
        $content = file_get_contents($mainFile);
        
        // Check if canvas banner scripts are already enqueued
        if (strpos($content, 'age-estimator-canvas-banner') !== false) {
            echo "ℹ️ Canvas banner scripts already enqueued\n";
            return;
        }
        
        // Add canvas banner script enqueue
        $enqueueScript = "
        // Canvas Banner Ad Script
        wp_enqueue_script(
            'age-estimator-canvas-banner',
            plugin_dir_url(__FILE__) . 'js/canvas-banner-ad.js',
            array('jquery'),
            '2.0.0',
            true
        );
        
        // Canvas Banner Ad Styles
        wp_enqueue_style(
            'age-estimator-canvas-banner',
            plugin_dir_url(__FILE__) . 'css/canvas-banner-ad.css',
            array(),
            '2.0.0'
        );";
        
        // Find a good place to insert the enqueue (look for existing script enqueues)
        if (strpos($content, 'wp_enqueue_script') !== false) {
            // Insert before existing script enqueues
            $content = preg_replace(
                '/(\s+)(wp_enqueue_script\(\s*[\'"]age-estimator)/',
                $enqueueScript . "\n\n$1$2",
                $content,
                1
            );
        } else {
            // If no existing enqueues found, add to admin_enqueue_scripts hook
            $hookPattern = '/add_action\s*\(\s*[\'"]admin_enqueue_scripts[\'"]\s*,\s*function\s*\(\)\s*\{/';
            if (preg_match($hookPattern, $content)) {
                $content = preg_replace(
                    $hookPattern,
                    '$0' . $enqueueScript,
                    $content,
                    1
                );
            }
        }
        
        if (file_put_contents($mainFile, $content)) {
            echo "✅ Main plugin file updated\n";
        } else {
            throw new Exception("Failed to update main plugin file");
        }
    }
    
    private function createTestPage() {
        $testPath = $this->pluginDir . '/canvas-banner-test.php';
        
        $testContent = '<?php
/**
 * Canvas Banner Test Page
 * Test the new canvas banner system
 */

// Add admin menu item for testing
add_action("admin_menu", function() {
    add_submenu_page(
        "options-general.php",
        "Canvas Banner Test",
        "Canvas Banner Test",
        "manage_options",
        "age-estimator-canvas-test",
        function() {
            echo "<div class=\"wrap\">";
            echo "<h1>🎨 Canvas Banner Test</h1>";
            echo "<div style=\"background: #f0f0f1; padding: 15px; border-radius: 5px; margin: 20px 0;\">";
            echo "<h3>🔧 Browser Console Commands:</h3>";
            echo "<ul>";
            echo "<li><code>debugCanvasBanner()</code> - Check banner state</li>";
            echo "<li><code>forceShowCanvasBanner()</code> - Force show banner</li>";
            echo "<li><code>forceHideCanvasBanner()</code> - Force hide banner</li>";
            echo "</ul>";
            echo "<h3>📋 Instructions:</h3>";
            echo "<ol>";
            echo "<li>Configure your banner ad in Age Estimator settings</li>";
            echo "<li>Start the camera below</li>";
            echo "<li>Enter fullscreen mode</li>";
            echo "<li>Banner should appear as canvas overlay</li>";
            echo "</ol>";
            echo "</div>";
            echo "<div style=\"margin-top: 20px; border: 1px solid #ccc; padding: 20px; background: white;\">";
            echo do_shortcode("[age_estimator]");
            echo "</div>";
            echo "<script>";
            echo "document.addEventListener(\"DOMContentLoaded\", function() {";
            echo "  document.querySelector(\".age-estimator-photo-container\").classList.add(\"age-estimator-debug\");";
            echo "});";
            echo "</script>";
            echo "</div>";
        }
    );
});
?>';
        
        if (file_put_contents($testPath, $testContent)) {
            echo "✅ Test page created\n";
        } else {
            echo "⚠️ Failed to create test page\n";
        }
    }
}

// Handle activation
if (isset($_GET['activate']) || php_sapi_name() === 'cli') {
    $activator = new CanvasBannerActivator();
    $success = $activator->activate();
    
    if (!$success) {
        exit(1);
    }
} else {
    echo "<h2>Canvas Banner Activator</h2>";
    echo "<p>This script will activate the canvas banner system for your Age Estimator plugin.</p>";
    echo "<p><a href='?activate=1' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;'>🎨 Activate Canvas Banner</a></p>";
}
?>