<?php
/**
 * Auto Fullscreen Fix - Enable automatic fullscreen when camera starts
 * Run this script once to add auto-fullscreen functionality
 */

$file_path = __DIR__ . '/js/photo-age-estimator-continuous-overlay.js';

echo "Adding auto-fullscreen functionality to continuous overlay version...\n";

if (!file_exists($file_path)) {
    echo "ERROR: File not found: $file_path\n";
    exit(1);
}

// Read the current file
$content = file_get_contents($file_path);

if ($content === false) {
    echo "ERROR: Could not read file\n";
    exit(1);
}

// Check if already patched
if (strpos($content, 'this.enterFullscreen();') !== false && strpos($content, 'Enter fullscreen immediately') !== false) {
    echo "Auto-fullscreen functionality already exists!\n";
    echo "The camera should automatically enter fullscreen when you click 'Start Monitoring'.\n";
    exit(0);
}

// Find the pattern to replace
$search = "startCameraButton.disabled = true;\n                    startCameraButton.textContent = 'Starting...';";

$replace = "startCameraButton.disabled = true;\n                    startCameraButton.textContent = 'Starting...';\n                }\n                \n                // Enter fullscreen immediately while we have user gesture\n                this.enterFullscreen();";

// Check if we can find the pattern
if (strpos($content, $search) === false) {
    echo "WARNING: Could not find exact pattern. Looking for alternative...\n";
    
    // Try alternative pattern
    $search_alt = "startCameraButton.textContent = 'Starting...';";
    $replace_alt = "startCameraButton.textContent = 'Starting...';\n                }\n                \n                // Enter fullscreen immediately while we have user gesture\n                this.enterFullscreen();";
    
    if (strpos($content, $search_alt) !== false) {
        $content = str_replace($search_alt, $replace_alt, $content);
        echo "Applied alternative pattern match.\n";
    } else {
        echo "ERROR: Could not find insertion point. Manual edit required.\n";
        echo "\nPlease manually add this line to the startCamera function:\n";
        echo "this.enterFullscreen();\n";
        echo "\nRight after the line:\n";
        echo "startCameraButton.textContent = 'Starting...';\n";
        exit(1);
    }
} else {
    // Apply the replacement
    $content = str_replace($search, $replace, $content);
}

// Create backup
$backup_path = $file_path . '.backup-' . date('Y-m-d-H-i-s');
if (!copy($file_path, $backup_path)) {
    echo "WARNING: Could not create backup file\n";
}

// Write the modified content
if (file_put_contents($file_path, $content) === false) {
    echo "ERROR: Could not write modified file\n";
    exit(1);
}

echo "✅ SUCCESS! Auto-fullscreen functionality has been added.\n";
echo "📁 Backup created: " . basename($backup_path) . "\n";
echo "\n🎯 WHAT THIS DOES:\n";
echo "- When you click 'Start Monitoring', the camera will automatically enter fullscreen mode\n";
echo "- Works on both desktop and mobile devices\n";
echo "- You can still exit fullscreen using the fullscreen button or ESC key\n";
echo "\n🔧 TO TEST:\n";
echo "1. Go to your Age Estimator page\n";
echo "2. Click 'Start Monitoring'\n";
echo "3. The camera should automatically go fullscreen\n";
echo "\n📝 TO UNDO:\n";
echo "Simply restore the backup file if needed.\n";

?>
