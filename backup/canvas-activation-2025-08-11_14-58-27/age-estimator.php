<?php
/**
 * Plugin Name: Age Estimator Live
 * Plugin URI: https://yourwebsite.com/age-estimator-live
 * Description: Live age estimation using continuous facial monitoring with automatic capture (Local detection or AWS Rekognition)
 * Version: 2.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: age-estimator
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AGE_ESTIMATOR_VERSION', '2.0');
define('AGE_ESTIMATOR_PATH', plugin_dir_path(__FILE__));
define('AGE_ESTIMATOR_URL', plugin_dir_url(__FILE__));
define('AGE_ESTIMATOR_BASENAME', plugin_basename(__FILE__));

/**
 * Main Age Estimator Class
 */
class AgeEstimator {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Initialize plugin after all plugins are loaded
     */
    public function init() {
        // Check and update database schema if needed
        $this->check_database_updates();
        
        // Add hooks
        add_action('init', array($this, 'load_textdomain'));
        add_action('init', array($this, 'load_includes'), 20); // Load includes after textdomain
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('age_estimator', array($this, 'render_shortcode'));
        add_shortcode('age_estimator_photo', array($this, 'render_shortcode')); // Support old shortcode
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('age-estimator', false, dirname(AGE_ESTIMATOR_BASENAME) . '/languages');
    }
    
    /**
     * Check and update database schema if needed
     */
    private function check_database_updates() {
        $current_db_version = get_option('age_estimator_db_version', '1.0');
        $target_db_version = '2.0';
        
        if (version_compare($current_db_version, $target_db_version, '<')) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'age_estimator_checks';
            
            // Update table schema
            $this->update_table_schema($table_name);
            
            // Update version
            update_option('age_estimator_db_version', $target_db_version);
        }
    }
    
    /**
     * Load required files
     */
    public function load_includes() {
        // Get the current mode
        $mode = get_option('age_estimator_mode', 'simple');
        
        // Load AWS Rekognition API handler if in AWS mode
        if ($mode === 'aws') {
            $aws_file = AGE_ESTIMATOR_PATH . 'includes/aws-rekognition-api.php';
            if (file_exists($aws_file)) {
                require_once $aws_file;
            }
        }
        
        // Load AJAX handler
        $ajax_file = AGE_ESTIMATOR_PATH . 'includes/ajax-handler.php';
        if (file_exists($ajax_file)) {
            require_once $ajax_file;
        }
        
        // Load admin settings
        if (is_admin()) {
            $admin_file = AGE_ESTIMATOR_PATH . 'includes/admin-settings.php';
            if (file_exists($admin_file)) {
                require_once $admin_file;
            }
            
            // Load admin stats
            $stats_file = AGE_ESTIMATOR_PATH . 'includes/admin-stats.php';
            if (file_exists($stats_file)) {
                require_once $stats_file;
            }
            
            // Load upgrade notice for @vladmandic/face-api
            $upgrade_notice_file = AGE_ESTIMATOR_PATH . 'includes/vladmandic-upgrade-notice.php';
            if (file_exists($upgrade_notice_file)) {
                require_once $upgrade_notice_file;
            }
            
            // Load admin email settings
            $admin_email_file = AGE_ESTIMATOR_PATH . 'includes/admin-email-settings.php';
            if (file_exists($admin_email_file)) {
                require_once $admin_email_file;
            }
            
            // Load user PIN management for admin
            $user_pin_manager_file = AGE_ESTIMATOR_PATH . 'includes/class-user-pin-manager.php';
            if (file_exists($user_pin_manager_file)) {
                require_once $user_pin_manager_file;
            }
        }
        
        // Load user settings (frontend)
        $user_settings_file = AGE_ESTIMATOR_PATH . 'includes/user-settings/class-user-settings.php';
        if (file_exists($user_settings_file)) {
            require_once $user_settings_file;
        }
        
        // Load enhanced user settings if available
        $enhanced_settings_file = AGE_ESTIMATOR_PATH . 'includes/user-settings/class-user-settings-enhanced.php';
        if (file_exists($enhanced_settings_file)) {
            require_once $enhanced_settings_file; // Fixed version loaded
        }
        
        // Load PIN fix for enhanced settings
        $pin_fix_file = AGE_ESTIMATOR_PATH . 'includes/class-pin-fix.php';
        if (file_exists($pin_fix_file)) {
            require_once $pin_fix_file;
        }
        
        // Load settings migration helper
        $migration_file = AGE_ESTIMATOR_PATH . 'includes/class-settings-migration.php';
        if (file_exists($migration_file) && is_admin()) {
            require_once $migration_file;
        }
        
        // Load API tracker
        $api_tracker_file = AGE_ESTIMATOR_PATH . 'includes/class-api-tracker.php';
        if (file_exists($api_tracker_file)) {
            require_once $api_tracker_file;
        }
        
        // Load compliance emailer
        $emailer_file = AGE_ESTIMATOR_PATH . 'includes/class-compliance-emailer.php';
        if (file_exists($emailer_file)) {
            require_once $emailer_file;
        }
        
        // Load user email preferences
        $email_prefs_file = AGE_ESTIMATOR_PATH . 'includes/class-user-email-preferences.php';
        if (file_exists($email_prefs_file)) {
            require_once $email_prefs_file;
        }
        
        // Load compliance log manager
        $log_manager_file = AGE_ESTIMATOR_PATH . 'includes/class-compliance-log-manager.php';
        if (file_exists($log_manager_file)) {
            require_once $log_manager_file;
        }
        
        // Load PIN fields visibility fix
        $pin_fields_fix_file = AGE_ESTIMATOR_PATH . 'pin-fields-fix.php';
        if (file_exists($pin_fields_fix_file)) {
            require_once $pin_fields_fix_file;
        }
        
        // Load PIN debug widget (admin only)
        if (is_admin()) {
            $pin_debug_file = AGE_ESTIMATOR_PATH . 'pin-debug-widget.php';
            if (file_exists($pin_debug_file)) {
                require_once $pin_debug_file;
            }
        }
        
        // Load settings PIN protection - DISABLED FOR DIRECT ACCESS
        // PIN protection completely disabled - settings accessible without PIN
        /*
        $pin_protection_file = AGE_ESTIMATOR_PATH . 'includes/class-settings-pin-protection.php';
        if (file_exists($pin_protection_file)) {
            require_once $pin_protection_file;
            // Force initialize PIN protection immediately
            if (class_exists('AgeEstimatorSettingsPinProtection')) {
                AgeEstimatorSettingsPinProtection::get_instance();
            }
        }
        */
        
        // Add PIN cleanup admin tool
        if (is_admin()) {
            $admin_cleanup_file = AGE_ESTIMATOR_PATH . 'admin-pin-cleanup.php';
            if (file_exists($admin_cleanup_file)) {
                require_once $admin_cleanup_file;
            }
        }
        
        // COMPLETE PIN PROTECTION OVERRIDE - FORCE DISABLE
        add_action('init', function() {
            // Remove all PIN protection hooks and filters
            remove_all_filters('age_estimator_render_enhanced_settings');
            remove_all_actions('wp_ajax_age_estimator_verify_settings_pin');
            remove_all_actions('wp_ajax_age_estimator_check_pin_session');
            remove_all_actions('wp_ajax_age_estimator_lock_settings');
            
            // Add override filter for enhanced settings
            add_filter('age_estimator_render_enhanced_settings', function($content, $atts) {
                if (!is_user_logged_in()) {
                    return $content; // Still require WordPress login
                }
                
                // Add notice that PIN protection is disabled
                $notice = '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 12px; margin: 10px 0; border-radius: 4px; font-size: 14px;"><strong>🔓 PIN Protection Disabled</strong> - Settings accessible without PIN</div>';
                return $notice . $content;
            }, 1, 2);
        }, 1);
        
        // Prevent PIN protection scripts from loading
        add_action('wp_enqueue_scripts', function() {
            global $post;
            
            if (is_a($post, 'WP_Post') && 
                (has_shortcode($post->post_content, 'age_estimator_settings_enhanced') || 
                 has_shortcode($post->post_content, 'age_estimator_user_settings'))) {
                
                // Remove PIN protection scripts
                wp_dequeue_script('age-estimator-pin-protection');
                wp_dequeue_script('age-estimator-pin-simplified');
                wp_deregister_script('age-estimator-pin-protection');
                wp_deregister_script('age-estimator-pin-simplified');
                
                // Remove PIN protection styles
                wp_dequeue_style('age-estimator-pin-protection');
                wp_deregister_style('age-estimator-pin-protection');
            }
        }, 99);
        
        // Add JavaScript override to hide any remaining PIN forms
        add_action('wp_footer', function() {
            global $post;
            
            if (is_a($post, 'WP_Post') && 
                (has_shortcode($post->post_content, 'age_estimator_settings_enhanced') || 
                 has_shortcode($post->post_content, 'age_estimator_user_settings'))) {
                ?>
                <script>
                jQuery(document).ready(function($) {
                    console.log('🔓 PIN Protection Override Active');
                    
                    // Remove any PIN forms
                    $('.age-estimator-pin-protection, .age-estimator-pin-setup, .pin-container, .pin-setup-container').remove();
                    
                    // Ensure settings are visible
                    $('.settings-container, .enhanced-settings, .user-settings-enhanced, .age-estimator-settings-enhanced').show();
                    
                    // Override PIN protection object
                    if (window.ageEstimatorPinProtection) {
                        window.ageEstimatorPinProtection = null;
                    }
                });
                </script>
                <?php
            }
        }, 99);
        
        // Load banner ad assets
        $banner_assets_file = AGE_ESTIMATOR_PATH . 'includes/banner-ad-assets.php';
        if (file_exists($banner_assets_file)) {
            require_once $banner_assets_file;
        }
        
        // Note: Email utility files have been disabled to prevent errors
        // They can be manually included if needed - see UTILITY_FILES_README.md
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Load and initialize API tracker to create tables
        require_once AGE_ESTIMATOR_PATH . 'includes/class-api-tracker.php';
        $tracker = AgeEstimatorAPITracker::get_instance();
        $tracker->create_tables();
        // Set default options
        $default_options = array(
            // Mode selection
            'age_estimator_mode' => 'simple', // 'simple' or 'aws'
            
            // Common settings - FORCE FULLSCREEN MODE
            'age_estimator_display_style' => 'fullscreen',
            'age_estimator_show_emotions' => true,
            'age_estimator_show_attributes' => true,
            'age_estimator_privacy_mode' => false,
            // Continuous mode is now always enabled in Age Estimator Live
            
            // AWS settings
            'age_estimator_aws_access_key' => '',
            'age_estimator_aws_secret_key' => '',
            'age_estimator_aws_region' => 'us-east-1',
            
            // Age gating settings
            'age_estimator_enable_age_gate' => false,
            'age_estimator_minimum_age' => 21,
            'age_estimator_age_gate_message' => 'You must be {age} or older to access this content.',
            'age_estimator_age_gate_redirect' => '',
            
            // Privacy and compliance settings
            'age_estimator_require_consent' => true,
            'age_estimator_consent_text' => 'I consent to the processing of my facial image for age verification purposes. My image will be processed securely and deleted immediately after verification.',
            'age_estimator_data_retention_hours' => 0,
            
            // Retail mode settings
            'age_estimator_enable_logging' => false
        );
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
        
        // Migrate old settings if they exist
        $this->migrate_old_settings();
        
        // Create retail mode database tables
        $this->create_retail_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables for retail mode logging
     */
    private function create_retail_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Compliance checks table
        $table_name = $wpdb->prefix . 'age_estimator_checks';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            check_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            staff_member varchar(100) DEFAULT '',
            estimated_age int(3) DEFAULT 0,
            alert_level varchar(10) DEFAULT '',
            id_checked boolean DEFAULT false,
            id_result varchar(50) DEFAULT '',
            sale_completed boolean DEFAULT false,
            notes text DEFAULT '',
            image_hash varchar(64) DEFAULT '',
            gender varchar(20) DEFAULT '',
            confidence float DEFAULT 0,
            detection_mode varchar(20) DEFAULT 'aws',
            user_id bigint(20) unsigned DEFAULT 0,
            user_ip varchar(45) DEFAULT '',
            session_id varchar(64) DEFAULT '',
            face_detected int(1) DEFAULT 1,
            age_gate_result varchar(20) DEFAULT '',
            capture_time varchar(50) DEFAULT '',
            averaged boolean DEFAULT false,
            samples_count int(3) DEFAULT 0,
            samples_range varchar(20) DEFAULT '',
            std_dev float DEFAULT 0,
            PRIMARY KEY (id),
            KEY check_time (check_time),
            KEY alert_level (alert_level),
            KEY user_id (user_id),
            KEY detection_mode (detection_mode),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Check if columns exist and add them if they don't
        $this->update_table_schema($table_name);
    }
    
    /**
     * Update table schema to add missing columns
     */
    private function update_table_schema($table_name) {
        global $wpdb;
        
        // Check for detection_mode column
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s 
             AND TABLE_NAME = %s 
             AND COLUMN_NAME = 'detection_mode'",
            DB_NAME,
            $table_name
        ));
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN detection_mode varchar(20) DEFAULT 'aws'");
        }
        
        // Check for other potentially missing columns
        $columns_to_check = array(
            'gender' => "varchar(20) DEFAULT ''",
            'confidence' => "float DEFAULT 0",
            'user_id' => "bigint(20) unsigned DEFAULT 0",
            'user_ip' => "varchar(45) DEFAULT ''",
            'session_id' => "varchar(64) DEFAULT ''",
            'face_detected' => "int(1) DEFAULT 1",
            'age_gate_result' => "varchar(20) DEFAULT ''",
            'capture_time' => "varchar(50) DEFAULT ''",
            'averaged' => "boolean DEFAULT false",
            'samples_count' => "int(3) DEFAULT 0",
            'samples_range' => "varchar(20) DEFAULT ''",
            'std_dev' => "float DEFAULT 0"
        );
        
        foreach ($columns_to_check as $column => $definition) {
            $column_exists = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = %s 
                 AND TABLE_NAME = %s 
                 AND COLUMN_NAME = %s",
                DB_NAME,
                $table_name,
                $column
            ));
            
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN $column $definition");
            }
        }
    }
    
    /**
     * Migrate settings from old plugin versions
     */
    private function migrate_old_settings() {
        // Map of old option names to new ones
        $migration_map = array(
            'age_estimator_photo_aws_access_key' => 'age_estimator_aws_access_key',
            'age_estimator_photo_aws_secret_key' => 'age_estimator_aws_secret_key',
            'age_estimator_photo_aws_region' => 'age_estimator_aws_region',
            'age_estimator_photo_display_style' => 'age_estimator_display_style',
            'age_estimator_photo_show_emotions' => 'age_estimator_show_emotions',
            'age_estimator_photo_show_attributes' => 'age_estimator_show_attributes',
            'age_estimator_photo_privacy_mode' => 'age_estimator_privacy_mode',
            'age_estimator_photo_enable_age_gate' => 'age_estimator_enable_age_gate',
            'age_estimator_photo_minimum_age' => 'age_estimator_minimum_age',
            'age_estimator_photo_age_gate_message' => 'age_estimator_age_gate_message',
            'age_estimator_photo_age_gate_redirect' => 'age_estimator_age_gate_redirect',
            'age_estimator_photo_require_consent' => 'age_estimator_require_consent',
            'age_estimator_photo_consent_text' => 'age_estimator_consent_text',
            'age_estimator_photo_data_retention_hours' => 'age_estimator_data_retention_hours'
        );
        
        foreach ($migration_map as $old_key => $new_key) {
            $old_value = get_option($old_key);
            if ($old_value !== false) {
                update_option($new_key, $old_value);
                delete_option($old_key); // Clean up old option
            }
        }
        
        // If AWS credentials exist, set mode to AWS
        if (!empty(get_option('age_estimator_aws_access_key')) && !empty(get_option('age_estimator_aws_secret_key'))) {
            update_option('age_estimator_mode', 'aws');
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only load on pages with the shortcode
        global $post;
        if (!is_a($post, 'WP_Post') || (!has_shortcode($post->post_content, 'age_estimator') && !has_shortcode($post->post_content, 'age_estimator_photo'))) {
            return;
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'age-estimator',
            AGE_ESTIMATOR_URL . 'css/age-estimator-photo.css',
            array(),
            AGE_ESTIMATOR_VERSION
        );
        
        // Enqueue overlay display styles for continuous mode
        wp_enqueue_style(
            'age-estimator-overlay',
            AGE_ESTIMATOR_URL . 'css/photo-continuous-overlay.css',
            array('age-estimator'),
            AGE_ESTIMATOR_VERSION
        );
        
        // Enqueue age averaging styles
        wp_enqueue_style(
            'age-estimator-averaging',
            AGE_ESTIMATOR_URL . 'css/age-averaging.css',
            array('age-estimator'),
            AGE_ESTIMATOR_VERSION
        );
        
        // ALWAYS enqueue retail mode styles - forced retail mode
        wp_enqueue_style(
            'age-estimator-retail',
            AGE_ESTIMATOR_URL . 'css/photo-retail-mode.css',
            array('age-estimator'),
            AGE_ESTIMATOR_VERSION
        );
        
        // Get current mode
        $mode = get_option('age_estimator_mode', 'simple');
        // Continuous mode is always enabled in Age Estimator Live
        $continuous = true;
        
        // Always enqueue face-api.js for continuous monitoring
        wp_enqueue_script(
            'face-api',
            AGE_ESTIMATOR_URL . 'libs/face-api.min.js',
            array(),
            '0.22.2',
            true
        );
        
        // Enqueue face tracker module for caching optimization
        wp_enqueue_script(
            'face-tracker',
            AGE_ESTIMATOR_URL . 'js/face-tracker.js',
            array('face-api'),
            AGE_ESTIMATOR_VERSION,
            true
        );
        
        // Enqueue sound manager for pass/fail notifications
        wp_enqueue_script(
            'age-estimator-sounds',
            AGE_ESTIMATOR_URL . 'js/age-estimator-sounds.js',
            array(),
            AGE_ESTIMATOR_VERSION,
            true
        );
        
        // Optional: Enqueue sound styles
        wp_enqueue_style(
            'age-estimator-sounds',
            AGE_ESTIMATOR_URL . 'css/age-estimator-sounds.css',
            array(),
            AGE_ESTIMATOR_VERSION
        );
        
        // Use the optimized continuous version with overlay display
        wp_enqueue_script(
            'age-estimator',
            AGE_ESTIMATOR_URL . 'js/photo-age-estimator-continuous-overlay.js',
            array('jquery', 'face-api', 'face-tracker'),
            AGE_ESTIMATOR_VERSION,
            true
        );
        
        // ALWAYS enqueue retail mode script - forced retail mode
        wp_enqueue_script(
            'age-estimator-retail',
            AGE_ESTIMATOR_URL . 'js/photo-age-estimator-retail.js',
            array('jquery', 'age-estimator'),
            AGE_ESTIMATOR_VERSION,
            true
        );
        
        // Localize script with parameters
        $this->localize_script();
    }
    
    /**
     * Localize script with parameters
     */
    private function localize_script() {
        $mode = get_option('age_estimator_mode', 'simple');
        // Continuous mode is always enabled in Age Estimator Live
        $continuous_mode = true;
        
        $params = array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('age_estimator_nonce'),
            'mode' => $mode,
            'showEmotions' => get_option('age_estimator_show_emotions', true) ? '1' : '0',
            'showAttributes' => get_option('age_estimator_show_attributes', true) ? '1' : '0',
            'privacyMode' => get_option('age_estimator_privacy_mode', false) ? '1' : '0',
            'requireConsent' => get_option('age_estimator_require_consent', true) ? '1' : '0',
            'consentText' => get_option('age_estimator_consent_text', 'I consent to the processing of my facial image for age verification purposes. My image will be processed securely and deleted immediately after verification.'),
            'enableAgeGate' => get_option('age_estimator_enable_age_gate', false) ? '1' : '0',
            'minimumAge' => get_option('age_estimator_minimum_age', 21),
            'pluginUrl' => AGE_ESTIMATOR_URL,
            'modelsUrl' => AGE_ESTIMATOR_URL . 'models/',
            'modelsPath' => AGE_ESTIMATOR_URL . 'models/',  // Added for backward compatibility
            'version' => AGE_ESTIMATOR_VERSION,
            'continuousMode' => '1', // Always enabled in Age Estimator Live
            
            // Face tracking settings
            'minFaceSize' => intval(get_option('age_estimator_min_face_size', 150)),
            'maxFaceSize' => intval(get_option('age_estimator_max_face_size', 350)),
            'faceSensitivity' => floatval(get_option('age_estimator_face_sensitivity', 0.4)),
            'cacheDuration' => intval(get_option('age_estimator_cache_duration', 30)),
            'captureDelay' => intval(get_option('age_estimator_capture_delay', 500)),
            'cooldownPeriod' => intval(get_option('age_estimator_cooldown_period', 5000)),
            
            // Retail mode settings - FORCED ON
            'retailMode' => '1', // Always force retail mode
            'retailPin' => get_option('age_estimator_retail_pin', ''),
            'challengeAge' => intval(get_option('age_estimator_challenge_age', 25)),
            'enableLogging' => get_option('age_estimator_enable_logging', false) ? '1' : '0',
            'retailSettingsUrl' => get_option('age_estimator_retail_settings_url', ''),
            
            // Kiosk mode settings
            'kioskMode' => get_option('age_estimator_kiosk_mode', false) ? '1' : '0',
            'kioskImage' => get_option('age_estimator_kiosk_image', ''),
            'kioskDisplayTime' => intval(get_option('age_estimator_kiosk_display_time', 5)),
            
            // Sound notification settings
            'enableSounds' => get_option('age_estimator_enable_sounds', false) ? '1' : '0',
            'passSoundUrl' => get_option('age_estimator_pass_sound_url', ''),
            'failSoundUrl' => get_option('age_estimator_fail_sound_url', ''),
            'soundVolume' => floatval(get_option('age_estimator_sound_volume', 0.7)),
            
            // Age averaging settings (for simple mode)
            'enableAveraging' => get_option('age_estimator_enable_averaging', false) ? '1' : '0',
            'averageSamples' => intval(get_option('age_estimator_average_samples', 5))
        );
        
        // Add user-specific settings if logged in
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            
            // Get user-specific settings
            $user_face_tracking_distance = get_user_meta($user_id, 'age_estimator_face_tracking_distance', true);
            $user_retail_mode = get_user_meta($user_id, 'age_estimator_retail_mode_enabled', true);
            $user_age_gating = get_user_meta($user_id, 'age_estimator_age_gating_enabled', true);
            $user_age_threshold = get_user_meta($user_id, 'age_estimator_age_gating_threshold', true);
            
            // Override with user settings if they exist
            if (!empty($user_face_tracking_distance)) {
                $params['faceSensitivity'] = floatval($user_face_tracking_distance);
            }
            // Always force retail mode regardless of user settings
            $params['retailMode'] = '1';
            $params['userRetailMode'] = true; // Flag to indicate user-specific retail mode
            if (!empty($user_age_gating)) {
                $params['enableAgeGate'] = $user_age_gating;
                $params['userAgeGating'] = true; // Flag to indicate user-specific age gating
            }
            if (!empty($user_age_threshold)) {
                $params['minimumAge'] = intval($user_age_threshold);
            }
            
            // Add user meta for frontend access
            $params['userMeta'] = array(
                'faceTrackingDistance' => floatval($user_face_tracking_distance ?: 0.4),
                'retailModeEnabled' => $user_retail_mode === '1',
                'ageGatingEnabled' => $user_age_gating === '1',
                'ageGatingThreshold' => intval($user_age_threshold ?: 18)
            );
            
            // Add logged in flag
            $params['isLoggedIn'] = true;
        } else {
            $params['isLoggedIn'] = false;
        }
        
        // For backward compatibility
        wp_localize_script('age-estimator', 'ageEstimatorParams', $params);
        wp_localize_script('age-estimator', 'ageEstimatorPhotoParams', $params);
        
        // Also make user meta available globally for user settings JS
        if (is_user_logged_in()) {
            wp_localize_script('age-estimator', 'ageEstimatorUserMeta', $params['userMeta']);
        }
    }
    
    /**
     * Render shortcode - FULLSCREEN MODE ONLY
     */
    public function render_shortcode($atts) {
        // Continuous mode is always enabled in Age Estimator Live
        $default_button_text = __('Start Monitoring', 'age-estimator');
        
        // Parse attributes - FORCE FULLSCREEN MODE ONLY
        $atts = shortcode_atts(array(
            'title' => __('Age Estimator Live', 'age-estimator'),
            'button_text' => $default_button_text,
            'style' => 'fullscreen', // Always force fullscreen mode
            'class' => 'age-estimator-fullscreen-only'
        ), $atts);
        
        // Force fullscreen style regardless of what's passed in or saved in settings
        $atts['style'] = 'fullscreen';
        
        // Check configuration based on mode
        $mode = get_option('age_estimator_mode', 'simple');
        if ($mode === 'aws' && !$this->is_aws_configured()) {
            return '<div class="age-estimator-error">Age Estimator is not configured. Please configure AWS Rekognition in the admin settings.</div>';
        }
        
        // Start output buffering
        ob_start();
        
        // Get template
        $template = AGE_ESTIMATOR_PATH . 'templates/photo-' . $atts['style'] . '.php';
        
        if (file_exists($template)) {
            include $template;
        } else {
            // Fallback template
            ?>
            <div class="age-estimator-photo-container <?php echo esc_attr($atts['class']); ?>" data-mode="<?php echo esc_attr($mode); ?>" data-continuous="true">
                <?php 
                // Display logo if enabled
                $use_logo = get_option('age_estimator_use_logo', false);
                $logo_url = get_option('age_estimator_logo_url', '');
                $logo_height = get_option('age_estimator_logo_height', 40);
                
                if ($use_logo && !empty($logo_url)) {
                    ?>
                    <div class="age-estimator-logo-container" style="text-align: center; margin-bottom: 20px;">
                        <img src="<?php echo esc_url($logo_url); ?>" 
                             alt="<?php _e('Logo', 'age-estimator'); ?>" 
                             style="max-height: <?php echo esc_attr($logo_height); ?>px; width: auto; display: inline-block;" 
                             class="age-estimator-logo" />
                    </div>
                    <?php
                }
                ?>
                <div id="age-estimator-photo-camera" class="age-estimator-photo-camera">
                    <div class="age-estimator-photo-camera-placeholder">
                        <p>Click "Start Camera" to begin</p>
                    </div>
                    <video id="age-estimator-photo-video" style="display: none;" autoplay playsinline></video>
                    <canvas id="age-estimator-photo-canvas" style="display: none;"></canvas>
                    <img id="age-estimator-photo-preview" style="display: none;" alt="Captured photo">
                </div>
                
                <div class="age-estimator-photo-controls">
                    <button id="age-estimator-photo-start-camera" class="age-estimator-photo-button primary">
                        <?php echo esc_html($atts['button_text']); ?>
                    </button>
                    <button id="age-estimator-photo-take-photo" class="age-estimator-photo-button success" style="display: none;">
                        Take Photo
                    </button>
                    <button id="age-estimator-photo-retake" class="age-estimator-photo-button secondary" style="display: none;">
                        Retake Photo
                    </button>
                    <button id="age-estimator-photo-stop-camera" class="age-estimator-photo-button danger" style="display: none;">
                        Stop Camera
                    </button>
                </div>
                
                <div id="age-estimator-photo-loading" class="age-estimator-photo-loading" style="display: none;">
                    <div class="spinner"></div>
                    <p>Analyzing photo...</p>
                </div>
                
                <div id="age-estimator-photo-result" class="age-estimator-photo-result">
                    <!-- Results will be displayed here -->
                </div>
            </div>
            <?php
        }
        
        // Return output
        return ob_get_clean();
    }
    
    /**
     * Check if AWS is configured
     */
    private function is_aws_configured() {
        $access_key = get_option('age_estimator_aws_access_key', '');
        $secret_key = get_option('age_estimator_aws_secret_key', '');
        
        return !empty($access_key) && !empty($secret_key);
    }
}

/**
 * Helper functions for backward compatibility
 */
function age_estimator_is_aws_configured() {
    $access_key = get_option('age_estimator_aws_access_key', '');
    $secret_key = get_option('age_estimator_aws_secret_key', '');
    
    return !empty($access_key) && !empty($secret_key);
}

function age_estimator_photo_is_aws_configured() {
    return age_estimator_is_aws_configured();
}

function age_estimator_get_option($option_name, $default = null) {
    $full_option_name = 'age_estimator_' . $option_name;
    return get_option($full_option_name, $default);
}

function age_estimator_photo_get_option($option_name, $default = null) {
    return age_estimator_get_option($option_name, $default);
}

// Initialize plugin
new AgeEstimator();
