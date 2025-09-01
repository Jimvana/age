<?php
/**
 * Upgrade notice for @vladmandic/face-api migration
 */

// Add admin notice for upgrade
add_action('admin_notices', 'age_estimator_vladmandic_upgrade_notice');

function age_estimator_vladmandic_upgrade_notice() {
    // Check if we're on the Age Estimator settings page
    $screen = get_current_screen();
    if ($screen->id !== 'settings_page_age-estimator-settings') {
        return;
    }
    
    // Check if the new library is already installed
    $libs_dir = AGE_ESTIMATOR_PATH . 'libs/';
    $face_api_file = $libs_dir . 'face-api.min.js';
    $backup_file = $libs_dir . 'face-api.min.js.backup';
    
    // If backup exists but new library might not be installed
    if (file_exists($backup_file) && (!file_exists($face_api_file) || filesize($face_api_file) < 1000000)) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <h2>Age Estimator Live - Library Upgrade Available</h2>
            <p><strong>An upgrade to @vladmandic/face-api is available!</strong></p>
            <p>This upgrade provides:</p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li>Better performance and accuracy</li>
                <li>Active maintenance and bug fixes</li>
                <li>Enhanced face detection features</li>
                <li>Improved browser compatibility</li>
            </ul>
            
            <h3>How to upgrade:</h3>
            <ol style="list-style: decimal; margin-left: 20px;">
                <li>Open Terminal and navigate to the plugin directory:<br>
                    <code style="background: #f0f0f0; padding: 2px 5px;">cd <?php echo AGE_ESTIMATOR_PATH; ?></code>
                </li>
                <li>Run the download script:<br>
                    <code style="background: #f0f0f0; padding: 2px 5px;">bash download-vladmandic-face-api.sh</code>
                </li>
                <li>Clear your browser cache</li>
                <li>Test the age estimation functionality</li>
            </ol>
            
            <p><strong>Alternative method:</strong> Download manually from 
                <a href="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@latest/dist/face-api.min.js" target="_blank">
                    https://cdn.jsdelivr.net/npm/@vladmandic/face-api@latest/dist/face-api.min.js
                </a>
                and save it as <code>face-api.min.js</code> in the <code>libs</code> directory.
            </p>
            
            <p style="margin-top: 15px;">
                <a href="<?php echo AGE_ESTIMATOR_URL; ?>UPGRADE_TO_VLADMANDIC.md" target="_blank" class="button button-primary">
                    View Full Upgrade Guide
                </a>
            </p>
        </div>
        <?php
    } elseif (file_exists($face_api_file) && filesize($face_api_file) > 1000000) {
        // Check if it's the vladmandic version by file size (vladmandic version is typically larger)
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>✅ @vladmandic/face-api is installed!</strong> Your Age Estimator is using the latest face detection library.</p>
        </div>
        <?php
    }
}

// Add a check on plugin activation
register_activation_hook(AGE_ESTIMATOR_BASENAME, 'age_estimator_check_face_api_version');

function age_estimator_check_face_api_version() {
    $libs_dir = AGE_ESTIMATOR_PATH . 'libs/';
    $face_api_file = $libs_dir . 'face-api.min.js';
    
    // If face-api.min.js doesn't exist, log a notice
    if (!file_exists($face_api_file)) {
        error_log('Age Estimator Live: face-api.min.js not found. Please run the upgrade script to install @vladmandic/face-api.');
    }
}
