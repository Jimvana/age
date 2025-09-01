<?php
/**
 * Kiosk Mode Test Page
 * 
 * Usage: Access this file through your WordPress site:
 * http://yoursite.com/wp-content/plugins/Age-estimator-live/kiosk-mode-implementation/test-kiosk-mode.php
 */

// Load WordPress
require_once('../../../../../wp-load.php');

// Check if user has permission
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiosk Mode Test - Age Estimator</title>
    <?php wp_head(); ?>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        
        .test-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .status-card {
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }
        
        .status-card.success {
            background: #d4edda;
            border-color: #c3e6cb;
        }
        
        .status-card.error {
            background: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .status-card h3 {
            margin-top: 0;
            color: #333;
        }
        
        .demo-area {
            margin: 30px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            min-height: 600px;
        }
        
        .control-panel {
            margin: 20px 0;
            padding: 15px;
            background: #e9ecef;
            border-radius: 4px;
        }
        
        .button {
            background: #007cba;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        
        .button:hover {
            background: #005a87;
        }
        
        .log-area {
            background: #000;
            color: #0f0;
            padding: 10px;
            font-family: monospace;
            font-size: 12px;
            height: 150px;
            overflow-y: auto;
            margin-top: 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🎯 Kiosk Mode Test Dashboard</h1>
        <p>Test and verify your Age Estimator Kiosk Mode implementation</p>
        
        <div class="status-grid">
            <?php
            // Check kiosk mode settings
            $kiosk_enabled = get_option('age_estimator_kiosk_mode', false);
            $kiosk_image = get_option('age_estimator_kiosk_image', '');
            $display_time = get_option('age_estimator_kiosk_display_time', 5);
            ?>
            
            <div class="status-card <?php echo $kiosk_enabled ? 'success' : 'error'; ?>">
                <h3>Kiosk Mode Status</h3>
                <p><strong><?php echo $kiosk_enabled ? '✅ ENABLED' : '❌ DISABLED'; ?></strong></p>
                <?php if (!$kiosk_enabled): ?>
                    <p><a href="<?php echo admin_url('admin.php?page=age-estimator-settings'); ?>">Enable in Settings</a></p>
                <?php endif; ?>
            </div>
            
            <div class="status-card <?php echo $kiosk_image ? 'success' : 'error'; ?>">
                <h3>Advertisement Image</h3>
                <?php if ($kiosk_image): ?>
                    <p>✅ Image configured</p>
                    <p style="font-size: 12px; word-break: break-all;"><?php echo esc_html($kiosk_image); ?></p>
                <?php else: ?>
                    <p>❌ No image set</p>
                    <p><a href="<?php echo admin_url('admin.php?page=age-estimator-settings'); ?>">Upload Image</a></p>
                <?php endif; ?>
            </div>
            
            <div class="status-card success">
                <h3>Display Settings</h3>
                <p>⏱️ Display Time: <strong><?php echo $display_time; ?> seconds</strong></p>
                <p>After age is shown, returns to ad</p>
            </div>
        </div>
        
        <?php if ($kiosk_enabled && $kiosk_image): ?>
            <div class="control-panel">
                <h3>Test Controls</h3>
                <button class="button" onclick="simulateNoFace()">Simulate No Face (Show Ad)</button>
                <button class="button" onclick="simulateFaceDetected()">Simulate Face Detected</button>
                <button class="button" onclick="simulateAgeResult()">Simulate Age Result</button>
                <button class="button" onclick="resetDemo()">Reset Demo</button>
                
                <div class="log-area" id="test-log">
                    Test log initialized...
                </div>
            </div>
        <?php endif; ?>
        
        <div class="demo-area">
            <h3>Live Demo</h3>
            <?php echo do_shortcode('[age_estimator]'); ?>
        </div>
    </div>
    
    <script>
        const testLog = document.getElementById('test-log');
        
        function log(message) {
            const time = new Date().toLocaleTimeString();
            testLog.innerHTML += `\n[${time}] ${message}`;
            testLog.scrollTop = testLog.scrollHeight;
        }
        
        function simulateNoFace() {
            log('Simulating no face - showing ad...');
            const kioskDisplay = document.getElementById('age-estimator-kiosk-display');
            if (kioskDisplay) {
                kioskDisplay.style.display = 'block';
                log('✅ Ad display shown');
            } else {
                log('❌ Kiosk display element not found');
            }
        }
        
        function simulateFaceDetected() {
            log('Simulating face detection...');
            const kioskDisplay = document.getElementById('age-estimator-kiosk-display');
            const video = document.getElementById('age-estimator-photo-video');
            
            if (kioskDisplay) {
                kioskDisplay.style.display = 'none';
                log('✅ Ad hidden');
            }
            
            if (video) {
                video.style.display = 'block';
                log('✅ Camera view shown');
            }
        }
        
        function simulateAgeResult() {
            log('Simulating age result display...');
            const resultDiv = document.getElementById('age-estimator-photo-result');
            
            if (resultDiv) {
                resultDiv.innerHTML = '<div style="padding: 20px; background: #e8f5e9; border-radius: 8px;"><h3>Age Estimation Result</h3><p>Estimated Age: <strong>25-30</strong></p></div>';
                resultDiv.style.display = 'block';
                log('✅ Age result displayed');
                
                // Simulate return to kiosk after display time
                const displayTime = <?php echo $display_time; ?>;
                log(`⏱️ Will return to ad in ${displayTime} seconds...`);
                
                setTimeout(() => {
                    simulateNoFace();
                    resultDiv.innerHTML = '';
                    resultDiv.style.display = 'none';
                }, displayTime * 1000);
            }
        }
        
        function resetDemo() {
            log('Resetting demo...');
            location.reload();
        }
        
        // Initial check
        window.addEventListener('load', function() {
            log('Page loaded - checking kiosk mode status...');
            
            const container = document.querySelector('.age-estimator-photo-container');
            if (container) {
                const kioskMode = container.getAttribute('data-kiosk-mode') === 'true';
                log(kioskMode ? '✅ Kiosk mode is active' : '❌ Kiosk mode is not active');
            }
        });
    </script>
    
    <?php wp_footer(); ?>
</body>
</html>