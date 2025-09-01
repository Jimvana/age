<?php
/**
 * Admin Settings for Age Estimator Plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load API tracker
require_once AGE_ESTIMATOR_PATH . 'includes/class-api-tracker.php';

// Load sound settings fields
require_once AGE_ESTIMATOR_PATH . 'includes/admin-settings-sound-fields.php';

class AgeEstimatorAdmin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array($this, 'show_admin_notices'));
        
        // Check database on admin init
        add_action('admin_init', array($this, 'check_database_schema'));
        
        // Add AJAX handlers for testing connections - support both old and new action names
        add_action('wp_ajax_test_aws_connection', array($this, 'test_aws_connection'));
        add_action('wp_ajax_test_aws_connection_photo', array($this, 'test_aws_connection'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            'Age Estimator Live',
            'Age Estimator',
            'manage_options',
            'age-estimator-settings',
            array($this, 'settings_page'),
            'dashicons-id-alt',
            30
        );
        
        // Settings submenu
        add_submenu_page(
            'age-estimator-settings',
            'Age Estimator Settings',
            'Settings',
            'manage_options',
            'age-estimator-settings',
            array($this, 'settings_page')
        );
        
        // API Statistics submenu
        if (class_exists('AgeEstimatorAdminStats')) {
            // Stats page is handled by AgeEstimatorAdminStats class
        }
        
        // Compliance Logs submenu (if retail mode or logging is enabled)
        if (get_option('age_estimator_retail_mode', false) || get_option('age_estimator_enable_logging', false)) {
            add_submenu_page(
                'age-estimator-settings',
                'Compliance Logs',
                'Compliance Logs',
                'manage_options',
                'age-estimator-logs',
                array($this, 'compliance_logs_page')
            );
        }
        
        // Keep the old options page location for backward compatibility
        add_options_page(
            'Age Estimator Live Settings',
            'Age Estimator Live',
            'manage_options',
            'age-estimator',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Mode selection
        register_setting('age_estimator_settings', 'age_estimator_mode', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_mode'),
            'default' => 'simple'
        ));
        
        // AWS Rekognition settings
        register_setting('age_estimator_settings', 'age_estimator_aws_access_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_aws_secret_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_aws_region', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'us-east-1'
        ));
        
        // Display settings
        register_setting('age_estimator_settings', 'age_estimator_display_style', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_display_style'),
            'default' => 'inline'
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_show_emotions', array(
            'type' => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => true
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_show_attributes', array(
            'type' => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => true
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_privacy_mode', array(
            'type' => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => false
        ));
        
        // Age gating settings
        register_setting('age_estimator_settings', 'age_estimator_enable_age_gate', array(
            'type' => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => false
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_minimum_age', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 21
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_age_gate_message', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'You must be {age} or older to access this content.'
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_age_gate_redirect', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        
        // Privacy settings
        register_setting('age_estimator_settings', 'age_estimator_require_consent', array(
            'type' => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => true
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_consent_text', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => 'I consent to the processing of my facial image for age verification purposes. My image will be processed securely and deleted immediately after verification.'
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_data_retention_hours', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 0
        ));
        
        // Retail mode logging setting
        register_setting('age_estimator_settings', 'age_estimator_enable_logging', array(
            'type' => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => false
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_retail_settings_url', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        
        // Continuous mode is now always enabled in Age Estimator Live
        
        // Logo settings
        register_setting('age_estimator_settings', 'age_estimator_use_logo', array(
            'type' => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => false
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_logo_url', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_logo_height', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 40
        ));
        
        // Sound notification settings
        register_setting('age_estimator_settings', 'age_estimator_enable_sounds', array(
            'type' => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => false
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_pass_sound_url', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_fail_sound_url', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_sound_volume', array(
            'type' => 'number',
            'sanitize_callback' => array($this, 'sanitize_volume'),
            'default' => 0.7
        ));
        
        // Retail Mode Settings
        register_setting('age_estimator_settings', 'age_estimator_retail_mode', array(
            'type' => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => false
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_retail_pin', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_pin'),
            'default' => ''
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_challenge_age', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 25
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_enable_logging', array(
            'type' => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => false
        ));
        
        // Averaging settings for simple mode
        register_setting('age_estimator_settings', 'age_estimator_enable_averaging', array(
            'type' => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => false
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_average_samples', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 5
        ));
        
        // Face Tracking Settings
        register_setting('age_estimator_settings', 'age_estimator_min_face_size', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 150
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_max_face_size', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 350
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_face_sensitivity', array(
            'type' => 'number',
            'sanitize_callback' => array($this, 'sanitize_float'),
            'default' => 0.4
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_cache_duration', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 30
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_capture_delay', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 500
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_cooldown_period', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 5000
        ));
        
        // Kiosk Mode Settings
        register_setting('age_estimator_settings', 'age_estimator_kiosk_mode', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_kiosk_image', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_kiosk_display_time', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 5
        ));
        
        // Fullscreen Banner Ad Settings
        register_setting('age_estimator_settings', 'age_estimator_enable_banner_ad', array(
            'type' => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => false
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_banner_ad_image', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_banner_ad_link', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_banner_ad_height', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 100
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_banner_ad_position', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_banner_position'),
            'default' => 'bottom'
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_banner_ad_opacity', array(
            'type' => 'number',
            'sanitize_callback' => array($this, 'sanitize_opacity'),
            'default' => 0.9
        ));

    }
    
    /**
     * Sanitize mode selection
     */
    public function sanitize_mode($value) {
        return in_array($value, array('simple', 'aws')) ? $value : 'simple';
    }
    
    /**
     * Sanitize display style - FORCE FULLSCREEN ONLY
     */
    public function sanitize_display_style($value) {
        // Always return fullscreen regardless of input
        return 'fullscreen';
    }
    
    /**
     * Sanitize checkbox value
     */
    public function sanitize_checkbox($value) {
        return $value ? true : false;
    }
    
    /**
     * Sanitize float value
     */
    public function sanitize_float($value) {
        return floatval($value);
    }
    
    /**
     * Sanitize PIN value
     */
    public function sanitize_pin($value) {
        $value = preg_replace('/[^0-9]/', '', $value);
        return substr($value, 0, 4);
    }
    
    /**
     * Sanitize volume value
     */
    public function sanitize_volume($input) {
        $value = floatval($input);
        return max(0, min(1, $value)); // Ensure between 0 and 1
    }
    
    /**
     * Sanitize banner position value
     */
    public function sanitize_banner_position($value) {
        return in_array($value, array('top', 'bottom')) ? $value : 'bottom';
    }
    
    /**
     * Sanitize opacity value
     */
    public function sanitize_opacity($input) {
        $value = floatval($input);
        return max(0, min(1, $value)); // Ensure between 0 and 1
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Check for various possible admin page hooks
        if ($hook !== 'settings_page_age-estimator' && 
            $hook !== 'toplevel_page_age-estimator-settings' &&
            $hook !== 'age-estimator_page_age-estimator-settings' &&
            $hook !== 'age-estimator_page_age-estimator-logs') {
            return;
        }
        
        wp_enqueue_script('age-estimator-admin', AGE_ESTIMATOR_URL . 'js/admin.js', array('jquery'), AGE_ESTIMATOR_VERSION, true);
        
        // Enqueue logs script on logs page
        if ($hook === 'age-estimator_page_age-estimator-logs') {
            wp_enqueue_script('age-estimator-admin-logs', AGE_ESTIMATOR_URL . 'js/admin-logs.js', array('jquery'), AGE_ESTIMATOR_VERSION, true);
        }
        
        // Localize script with both possible object names for backward compatibility
        $localization_data = array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('age_estimator_admin_nonce'),
            'testingConnection' => __('Testing connection...', 'age-estimator'),
            'connectionSuccess' => __('Connection successful!', 'age-estimator'),
            'connectionError' => __('Connection failed. Please check your credentials.', 'age-estimator')
        );
        
        wp_localize_script('age-estimator-admin', 'ageEstimatorAdmin', $localization_data);
        wp_localize_script('age-estimator-admin', 'ageEstimatorPhotoAdmin', $localization_data);
        
        // Also localize for logs script
        if ($hook === 'age-estimator_page_age-estimator-logs') {
            wp_localize_script('age-estimator-admin-logs', 'ageEstimatorAdmin', $localization_data);
        }
        
        // Enqueue media scripts for kiosk image upload
        wp_enqueue_media();
        
        wp_enqueue_style('age-estimator-admin', AGE_ESTIMATOR_URL . 'css/admin.css', array(), AGE_ESTIMATOR_VERSION);
    }
    
    /**
     * Compliance logs page
     */
    public function compliance_logs_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'age_estimator_checks';
        
        // Handle CSV export
        if (isset($_POST['export_report']) && check_admin_referer('age_estimator_export_logs')) {
            $this->export_compliance_report();
            return;
        }
        
        // Get log statistics
        $log_manager = new AgeEstimatorComplianceLogManager();
        $log_stats = $log_manager->get_log_stats();
        
        // Get today's stats
        $today = current_time('Y-m-d');
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_checks,
                SUM(CASE WHEN alert_level = 'red' THEN 1 ELSE 0 END) as challenges,
                SUM(CASE WHEN id_checked = 1 THEN 1 ELSE 0 END) as ids_checked,
                SUM(CASE WHEN id_result IN ('no_id', 'verified_under') THEN 1 ELSE 0 END) as sales_refused,
                SUM(CASE WHEN detection_mode = 'simple' THEN 1 ELSE 0 END) as simple_checks,
                SUM(CASE WHEN detection_mode = 'aws' THEN 1 ELSE 0 END) as aws_checks
            FROM $table_name 
            WHERE DATE(check_time) = %s
        ", $today));
        
        // Get recent logs
        $recent_logs = $wpdb->get_results("
            SELECT * FROM $table_name 
            ORDER BY check_time DESC 
            LIMIT 50
        ");
        
        ?>
        <div class="wrap">
            <h1><?php _e('Age Estimator Compliance Logs', 'age-estimator'); ?></h1>
            
            <!-- Log Management Section -->
            <div class="card" style="background: #f8f9fa; border-left: 4px solid #2271b1;">
                <h2><?php _e('Log Management', 'age-estimator'); ?></h2>
                
                <div class="log-stats" style="margin-bottom: 20px;">
                    <p>
                        <strong><?php _e('Total Logs:', 'age-estimator'); ?></strong> <?php echo number_format($log_stats['total_logs']); ?><br>
                        <?php if ($log_stats['oldest_log']): ?>
                            <strong><?php _e('Date Range:', 'age-estimator'); ?></strong> 
                            <?php echo date('M d, Y', strtotime($log_stats['oldest_log'])); ?> - 
                            <?php echo date('M d, Y', strtotime($log_stats['newest_log'])); ?>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="log-actions">
                    <button type="button" class="button button-secondary" id="clear-old-logs">
                        <?php _e('Clear Old Logs', 'age-estimator'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="clear-all-logs" style="color: #d63638;">
                        <?php _e('Clear All Logs', 'age-estimator'); ?>
                    </button>
                    
                    <div id="clear-logs-result" style="margin-top: 10px;"></div>
                </div>
                
                <div class="auto-clear-settings" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <h3><?php _e('Automatic Clearing', 'age-estimator'); ?></h3>
                    <form method="post" action="options.php" style="display: inline-block;">
                        <?php settings_fields('age_estimator_settings'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Auto-Clear', 'age-estimator'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="age_estimator_auto_clear_logs" value="1" 
                                               <?php checked(get_option('age_estimator_auto_clear_logs', false)); ?> />
                                        <?php _e('Automatically clear old logs', 'age-estimator'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Schedule', 'age-estimator'); ?></th>
                                <td>
                                    <select name="age_estimator_auto_clear_schedule">
                                        <option value="daily" <?php selected(get_option('age_estimator_auto_clear_schedule', 'weekly'), 'daily'); ?>>
                                            <?php _e('Daily', 'age-estimator'); ?>
                                        </option>
                                        <option value="weekly" <?php selected(get_option('age_estimator_auto_clear_schedule', 'weekly'), 'weekly'); ?>>
                                            <?php _e('Weekly', 'age-estimator'); ?>
                                        </option>
                                        <option value="monthly" <?php selected(get_option('age_estimator_auto_clear_schedule', 'weekly'), 'monthly'); ?>>
                                            <?php _e('Monthly', 'age-estimator'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Retention Period', 'age-estimator'); ?></th>
                                <td>
                                    <input type="number" name="age_estimator_log_retention_days" 
                                           value="<?php echo esc_attr(get_option('age_estimator_log_retention_days', 90)); ?>" 
                                           min="7" max="365" class="small-text" />
                                    <span><?php _e('days', 'age-estimator'); ?></span>
                                    <p class="description"><?php _e('Logs older than this will be automatically deleted.', 'age-estimator'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button(__('Save Auto-Clear Settings', 'age-estimator'), 'primary', 'submit', false); ?>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <h2><?php echo sprintf(__('Today\'s Statistics (%s)', 'age-estimator'), esc_html($today)); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <tr>
                        <td><strong><?php _e('Total Checks:', 'age-estimator'); ?></strong></td>
                        <td><?php echo intval($stats->total_checks); ?></td>
                        <td><strong><?php _e('Challenges Made:', 'age-estimator'); ?></strong></td>
                        <td><?php echo intval($stats->challenges); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('IDs Verified:', 'age-estimator'); ?></strong></td>
                        <td><?php echo intval($stats->ids_checked); ?></td>
                        <td><strong><?php _e('Sales Refused:', 'age-estimator'); ?></strong></td>
                        <td><?php echo intval($stats->sales_refused); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Face-API.js Scans:', 'age-estimator'); ?></strong></td>
                        <td><?php echo intval($stats->simple_checks); ?></td>
                        <td><strong><?php _e('AWS Rekognition Scans:', 'age-estimator'); ?></strong></td>
                        <td><?php echo intval($stats->aws_checks); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2><?php _e('Export Compliance Report', 'age-estimator'); ?></h2>
                <p><?php _e('Generate compliance reports for management or regulatory review.', 'age-estimator'); ?></p>
                <form method="post" action="">
                    <?php wp_nonce_field('age_estimator_export_logs'); ?>
                    <label><?php _e('Date Range:', 'age-estimator'); ?></label>
                    <input type="date" name="start_date" value="<?php echo esc_attr($today); ?>" required>
                    <?php _e('to', 'age-estimator'); ?>
                    <input type="date" name="end_date" value="<?php echo esc_attr($today); ?>" required>
                    <button type="submit" name="export_report" class="button button-primary">
                        <?php _e('Export to CSV', 'age-estimator'); ?>
                    </button>
                </form>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2><?php _e('Recent Compliance Checks', 'age-estimator'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Date/Time', 'age-estimator'); ?></th>
                            <th><?php _e('Mode', 'age-estimator'); ?></th>
                            <th><?php _e('User', 'age-estimator'); ?></th>
                            <th><?php _e('Age', 'age-estimator'); ?></th>
                            <th><?php _e('Gender', 'age-estimator'); ?></th>
                            <th><?php _e('Alert', 'age-estimator'); ?></th>
                            <th><?php _e('ID Checked', 'age-estimator'); ?></th>
                            <th><?php _e('Result', 'age-estimator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_logs): ?>
                            <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log->check_time); ?></td>
                                <td>
                                    <?php if ($log->detection_mode === 'simple'): ?>
                                        <span style="color: #4caf50;" title="Face-API.js">📷 Simple</span>
                                    <?php else: ?>
                                        <span style="color: #2196f3;" title="AWS Rekognition">☁️ AWS</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($log->user_id > 0) {
                                        $user = get_userdata($log->user_id);
                                        echo $user ? esc_html($user->display_name) : 'User #' . $log->user_id;
                                    } else {
                                        echo esc_html($log->staff_member ?: 'Guest');
                                    }
                                    ?>
                                </td>
                                <td><?php echo $log->estimated_age > 0 ? esc_html($log->estimated_age) : '-'; ?></td>
                                <td><?php echo esc_html($log->gender ?: '-'); ?></td>
                                <td>
                                    <?php if ($log->alert_level === 'red'): ?>
                                        <span style="color: #d32f2f; font-weight: bold;">🔴 <?php _e('Red', 'age-estimator'); ?></span>
                                    <?php elseif ($log->alert_level === 'amber'): ?>
                                        <span style="color: #f57c00; font-weight: bold;">🟡 <?php _e('Amber', 'age-estimator'); ?></span>
                                    <?php elseif ($log->alert_level === 'green'): ?>
                                        <span style="color: #388e3c; font-weight: bold;">🟢 <?php _e('Green', 'age-estimator'); ?></span>
                                    <?php elseif ($log->age_gate_result): ?>
                                        <?php if ($log->age_gate_result === 'passed'): ?>
                                            <span style="color: #388e3c;">✓ <?php _e('Pass', 'age-estimator'); ?></span>
                                        <?php else: ?>
                                            <span style="color: #d32f2f;">✗ <?php _e('Fail', 'age-estimator'); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php echo esc_html($log->alert_level ?: '-'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $log->id_checked ? __('Yes', 'age-estimator') : __('No', 'age-estimator'); ?></td>
                                <td><?php echo esc_html($log->id_result ?: '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8"><?php _e('No compliance checks recorded yet.', 'age-estimator'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Export compliance report to CSV
     */
    private function export_compliance_report() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'age_estimator_checks';
        
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table_name 
            WHERE DATE(check_time) BETWEEN %s AND %s
            ORDER BY check_time DESC
        ", $start_date, $end_date), ARRAY_A);
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="compliance-report-' . 
               $start_date . '-to-' . $end_date . '.csv"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($output, array(
            'Date/Time',
            'Detection Mode',
            'User/Staff',
            'User ID',
            'User IP',
            'Session ID',
            'Estimated Age',
            'Gender',
            'Confidence',
            'Alert Level',
            'Age Gate Result',
            'ID Checked',
            'ID Result',
            'Sale Completed',
            'Notes'
        ));
        
        // Write data
        foreach ($results as $row) {
            // Get user name if user_id exists
            $user_name = $row['staff_member'] ?: 'Guest';
            if ($row['user_id'] > 0) {
                $user = get_userdata($row['user_id']);
                if ($user) {
                    $user_name = $user->display_name;
                }
            }
            
            fputcsv($output, array(
                $row['check_time'],
                $row['detection_mode'] ?: 'aws',
                $user_name,
                $row['user_id'] ?: '0',
                $row['user_ip'] ?: '',
                $row['session_id'] ?: '',
                $row['estimated_age'] > 0 ? $row['estimated_age'] : '',
                $row['gender'] ?: '',
                $row['confidence'] ?: '',
                $row['alert_level'] ?: '',
                $row['age_gate_result'] ?: '',
                $row['id_checked'] ? 'Yes' : 'No',
                $row['id_result'] ?: '',
                $row['sale_completed'] ? 'Yes' : 'No',
                $row['notes'] ?: ''
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('age_estimator_settings'); ?>
                
                <h2 class="nav-tab-wrapper">
                    <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'age-estimator'); ?></a>
                    <a href="#aws" class="nav-tab"><?php _e('AWS Settings', 'age-estimator'); ?></a>
                    <a href="#retail" class="nav-tab"><?php _e('Retail Mode', 'age-estimator'); ?></a>
                    <a href="#face-tracking" class="nav-tab"><?php _e('Face Tracking', 'age-estimator'); ?></a>
                    <a href="#display" class="nav-tab"><?php _e('Display Options', 'age-estimator'); ?></a>
                    <a href="#age-gate" class="nav-tab"><?php _e('Age Gating', 'age-estimator'); ?></a>
                    <a href="#privacy" class="nav-tab"><?php _e('Privacy & Compliance', 'age-estimator'); ?></a>
                </h2>
                
                <div id="general" class="tab-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Mode', 'age-estimator'); ?></th>
                            <td>
                                <select name="age_estimator_mode" id="age_estimator_mode">
                                    <option value="simple" <?php selected(get_option('age_estimator_mode', 'simple'), 'simple'); ?>>
                                        <?php _e('Simple (Face-API.js - No external API required)', 'age-estimator'); ?>
                                    </option>
                                    <option value="aws" <?php selected(get_option('age_estimator_mode'), 'aws'); ?>>
                                        <?php _e('AWS Rekognition (Requires AWS account)', 'age-estimator'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php _e('Choose between Simple mode (uses JavaScript face detection) or AWS mode (uses AWS Rekognition service).', 'age-estimator'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Continuous Monitoring', 'age-estimator'); ?></th>
                            <td>
                                <p class="description">
                                    <strong><?php _e('✓ Always Enabled', 'age-estimator'); ?></strong><br>
                                    <?php _e('Age Estimator Live continuously monitors for faces and automatically captures when someone comes within the optimal distance.', 'age-estimator'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <!-- Age Averaging Settings -->
                        <tr class="averaging-settings" style="<?php echo get_option('age_estimator_mode', 'simple') === 'simple' ? '' : 'display: none;'; ?>">
                            <th scope="row"><?php _e('Age Averaging', 'age-estimator'); ?></th>
                            <td>
                                <label for="age_estimator_enable_averaging">
                                    <input type="checkbox" id="age_estimator_enable_averaging" name="age_estimator_enable_averaging" 
                                           value="1" <?php checked(get_option('age_estimator_enable_averaging', false)); ?> />
                                    <?php _e('Enable age averaging for more accurate results', 'age-estimator'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Take multiple age readings and average them for the final pass/fail decision.', 'age-estimator'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr class="averaging-samples" style="<?php echo (get_option('age_estimator_mode', 'simple') === 'simple' && get_option('age_estimator_enable_averaging', false)) ? '' : 'display: none;'; ?>">
                            <th scope="row">
                                <label for="age_estimator_average_samples"><?php _e('Number of Samples', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="age_estimator_average_samples" name="age_estimator_average_samples" 
                                       value="<?php echo esc_attr(get_option('age_estimator_average_samples', 5)); ?>" 
                                       min="3" max="10" class="small-text" />
                                <span><?php _e('readings', 'age-estimator'); ?></span>
                                <p class="description">
                                    <?php _e('Number of age estimations to average (3-10). More samples = more accurate but slower.', 'age-estimator'); ?>
                                </p>
                            </td>
                        </tr>

                    </table>
                </div>
                
                <div id="aws" class="tab-content" style="display: none;">
                    <h3><?php _e('AWS Rekognition Settings', 'age-estimator'); ?></h3>
                    <p class="description">
                        <?php _e('These settings are only required if you select AWS mode above.', 'age-estimator'); ?>
                    </p>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="age_estimator_aws_access_key"><?php _e('AWS Access Key ID', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="age_estimator_aws_access_key" name="age_estimator_aws_access_key" 
                                       value="<?php echo esc_attr(get_option('age_estimator_aws_access_key')); ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="age_estimator_aws_secret_key"><?php _e('AWS Secret Access Key', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="age_estimator_aws_secret_key" name="age_estimator_aws_secret_key" 
                                       value="<?php echo esc_attr(get_option('age_estimator_aws_secret_key')); ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="age_estimator_aws_region"><?php _e('AWS Region', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <select name="age_estimator_aws_region" id="age_estimator_aws_region">
                                    <?php
                                    $regions = array(
                                        'us-east-1' => 'US East (N. Virginia)',
                                        'us-east-2' => 'US East (Ohio)',
                                        'us-west-1' => 'US West (N. California)',
                                        'us-west-2' => 'US West (Oregon)',
                                        'eu-west-1' => 'EU (Ireland)',
                                        'eu-west-2' => 'EU (London)',
                                        'eu-central-1' => 'EU (Frankfurt)',
                                        'ap-southeast-1' => 'Asia Pacific (Singapore)',
                                        'ap-southeast-2' => 'Asia Pacific (Sydney)',
                                        'ap-northeast-1' => 'Asia Pacific (Tokyo)',
                                        'ap-northeast-2' => 'Asia Pacific (Seoul)',
                                        'ap-south-1' => 'Asia Pacific (Mumbai)',
                                        'ca-central-1' => 'Canada (Central)',
                                        'sa-east-1' => 'South America (São Paulo)'
                                    );
                                    $current_region = get_option('age_estimator_aws_region', 'us-east-1');
                                    foreach ($regions as $code => $name) {
                                        ?>
                                        <option value="<?php echo esc_attr($code); ?>" <?php selected($current_region, $code); ?>>
                                            <?php echo esc_html($name); ?>
                                        </option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"></th>
                            <td>
                                <button type="button" class="button" id="test-aws-connection">
                                    <?php _e('Test AWS Connection', 'age-estimator'); ?>
                                </button>
                                <div id="test-aws-result" style="margin-top: 10px;"></div>
                            </td>
                        </tr>

                    </table>
                </div>
                
                <div id="retail" class="tab-content" style="display: none;">
                    <h3><?php _e('Retail Mode (Challenge 25)', 'age-estimator'); ?></h3>
                    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px 15px; margin: 10px 0; border-radius: 4px;">
                        <strong>⚠️ <?php _e('Legal Notice:', 'age-estimator'); ?></strong> <?php _e('This tool assists with Challenge 25 compliance but does NOT replace the legal requirement to check physical ID. Staff must always verify ID for age-restricted sales.', 'age-estimator'); ?>
                    </div>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Enable Retail Mode', 'age-estimator'); ?></th>
                            <td>
                                <label for="age_estimator_retail_mode">
                                    <input type="checkbox" id="age_estimator_retail_mode" name="age_estimator_retail_mode" 
                                           value="1" <?php checked(get_option('age_estimator_retail_mode', false)); ?> />
                                    <?php _e('Enable retail compliance mode with Challenge 25 alerts', 'age-estimator'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Transforms the age estimator into a point-of-sale compliance tool with clear ID check prompts.', 'age-estimator'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="age_estimator_retail_pin"><?php _e('Retail Mode PIN', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="age_estimator_retail_pin" name="age_estimator_retail_pin" 
                                       value="<?php echo esc_attr(get_option('age_estimator_retail_pin', '')); ?>" 
                                       placeholder="<?php _e('Enter 4-digit PIN', 'age-estimator'); ?>" 
                                       maxlength="4" pattern="[0-9]{4}" class="small-text" />
                                <p class="description">
                                    <?php _e('Optional PIN to prevent public access to retail mode. Leave blank for no PIN.', 'age-estimator'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="age_estimator_challenge_age"><?php _e('Challenge Age', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="age_estimator_challenge_age" name="age_estimator_challenge_age" 
                                       value="<?php echo esc_attr(get_option('age_estimator_challenge_age', 25)); ?>" 
                                       min="18" max="30" class="small-text" />
                                <span><?php _e('years', 'age-estimator'); ?></span>
                                <p class="description">
                                    <?php _e('Age threshold for mandatory ID checks (default: 25 for UK Challenge 25 policy).', 'age-estimator'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Compliance Logging', 'age-estimator'); ?></th>
                            <td>
                                <label for="age_estimator_enable_logging">
                                    <input type="checkbox" id="age_estimator_enable_logging" name="age_estimator_enable_logging" 
                                           value="1" <?php checked(get_option('age_estimator_enable_logging', false)); ?> />
                                    <?php _e('Log all age checks for compliance reporting', 'age-estimator'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Stores anonymized check data for compliance audits and training purposes.', 'age-estimator'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="age_estimator_retail_settings_url"><?php _e('Retail Settings Page URL', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="age_estimator_retail_settings_url" name="age_estimator_retail_settings_url" 
                                       value="<?php echo esc_attr(get_option('age_estimator_retail_settings_url', '')); ?>" 
                                       placeholder="<?php _e('https://example.com/settings', 'age-estimator'); ?>" 
                                       class="large-text" />
                                <p class="description">
                                    <?php _e('URL to link to when clicking the retail mode header. Leave blank to disable linking.', 'age-estimator'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <?php if (get_option('age_estimator_enable_logging', false)): ?>
                    <div style="background: #e8f4fd; border: 1px solid #bee5eb; padding: 15px; margin: 20px 0; border-radius: 4px;">
                        <h4 style="margin-top: 0;"><?php _e('Today\'s Statistics', 'age-estimator'); ?></h4>
                        <?php
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'age_estimator_checks';
                        $today = current_time('Y-m-d');
                        
                        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                            $stats = $wpdb->get_row($wpdb->prepare("
                                SELECT 
                                    COUNT(*) as total_checks,
                                    SUM(CASE WHEN alert_level = 'red' THEN 1 ELSE 0 END) as challenges,
                                    SUM(CASE WHEN id_checked = 1 THEN 1 ELSE 0 END) as ids_checked
                                FROM $table_name 
                                WHERE DATE(check_time) = %s
                            ", $today));
                            ?>
                            <p>
                                <strong><?php _e('Total Checks:', 'age-estimator'); ?></strong> <?php echo intval($stats->total_checks); ?><br>
                                <strong><?php _e('Challenges Made:', 'age-estimator'); ?></strong> <?php echo intval($stats->challenges); ?><br>
                                <strong><?php _e('IDs Verified:', 'age-estimator'); ?></strong> <?php echo intval($stats->ids_checked); ?>
                            </p>
                            <p>
                                <a href="<?php echo admin_url('admin.php?page=age-estimator-logs'); ?>" class="button button-secondary">
                                    <?php _e('View Compliance Logs', 'age-estimator'); ?>
                                </a>
                            </p>
                        <?php } else { ?>
                            <p><?php _e('No data yet. Logging will begin when retail mode is used.', 'age-estimator'); ?></p>
                        <?php } ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div id="face-tracking" class="tab-content" style="display: none;">
                    <h3><?php _e('Face Tracking Settings', 'age-estimator'); ?></h3>
                    <p class="description">
                        <?php _e('Fine-tune the face detection and caching behavior for optimal performance.', 'age-estimator'); ?>
                    </p>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="age_estimator_min_face_size"><?php _e('Minimum Face Distance', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <input type="range" id="age_estimator_min_face_size" name="age_estimator_min_face_size" 
                                       value="<?php echo esc_attr(get_option('age_estimator_min_face_size', 150)); ?>" 
                                       min="50" max="300" step="10" class="slider" />
                                <span id="min-face-size-value" class="slider-value"><?php echo esc_attr(get_option('age_estimator_min_face_size', 150)); ?>px</span>
                                <p class="description"><?php _e('Minimum face width in pixels to trigger capture. Lower = farther away.', 'age-estimator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="age_estimator_max_face_size"><?php _e('Maximum Face Distance', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <input type="range" id="age_estimator_max_face_size" name="age_estimator_max_face_size" 
                                       value="<?php echo esc_attr(get_option('age_estimator_max_face_size', 350)); ?>" 
                                       min="200" max="600" step="10" class="slider" />
                                <span id="max-face-size-value" class="slider-value"><?php echo esc_attr(get_option('age_estimator_max_face_size', 350)); ?>px</span>
                                <p class="description"><?php _e('Maximum face width in pixels. Higher = closer to camera.', 'age-estimator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="age_estimator_face_sensitivity"><?php _e('Face Matching Sensitivity', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <input type="range" id="age_estimator_face_sensitivity" name="age_estimator_face_sensitivity" 
                                       value="<?php echo esc_attr(get_option('age_estimator_face_sensitivity', 0.4) * 100); ?>" 
                                       min="20" max="60" step="5" class="slider" />
                                <span id="face-sensitivity-value" class="slider-value"><?php echo esc_attr(get_option('age_estimator_face_sensitivity', 0.4)); ?></span>
                                <p class="description"><?php _e('How similar faces must be to match (0.2 = very sensitive, 0.6 = less sensitive).', 'age-estimator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="age_estimator_cache_duration"><?php _e('Cache Duration', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <input type="range" id="age_estimator_cache_duration" name="age_estimator_cache_duration" 
                                       value="<?php echo esc_attr(get_option('age_estimator_cache_duration', 30)); ?>" 
                                       min="10" max="120" step="5" class="slider" />
                                <span id="cache-duration-value" class="slider-value"><?php echo esc_attr(get_option('age_estimator_cache_duration', 30)); ?>s</span>
                                <p class="description"><?php _e('How long to cache face detection results (in seconds).', 'age-estimator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="age_estimator_capture_delay"><?php _e('Capture Delay', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <input type="range" id="age_estimator_capture_delay" name="age_estimator_capture_delay" 
                                       value="<?php echo esc_attr(get_option('age_estimator_capture_delay', 500)); ?>" 
                                       min="200" max="2000" step="100" class="slider" />
                                <span id="capture-delay-value" class="slider-value"><?php echo esc_attr(get_option('age_estimator_capture_delay', 500)); ?>ms</span>
                                <p class="description"><?php _e('Wait time after face is in range before capturing (milliseconds).', 'age-estimator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="age_estimator_cooldown_period"><?php _e('Cooldown Period', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <input type="range" id="age_estimator_cooldown_period" name="age_estimator_cooldown_period" 
                                       value="<?php echo esc_attr(get_option('age_estimator_cooldown_period', 5000)); ?>" 
                                       min="1000" max="10000" step="500" class="slider" />
                                <span id="cooldown-period-value" class="slider-value"><?php echo esc_attr(get_option('age_estimator_cooldown_period', 5000) / 1000); ?>s</span>
                                <p class="description"><?php _e('Wait time before capturing another face after a successful capture.', 'age-estimator'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <style>
                        .slider {
                            width: 300px;
                            margin-right: 15px;
                        }
                        .slider-value {
                            display: inline-block;
                            width: 60px;
                            font-weight: bold;
                            color: #2271b1;
                        }
                        input[type="range"] {
                            -webkit-appearance: none;
                            appearance: none;
                            height: 6px;
                            background: #ddd;
                            outline: none;
                            border-radius: 3px;
                        }
                        input[type="range"]::-webkit-slider-thumb {
                            -webkit-appearance: none;
                            appearance: none;
                            width: 16px;
                            height: 16px;
                            background: #2271b1;
                            cursor: pointer;
                            border-radius: 50%;
                        }
                        input[type="range"]::-moz-range-thumb {
                            width: 16px;
                            height: 16px;
                            background: #2271b1;
                            cursor: pointer;
                            border-radius: 50%;
                            border: none;
                        }
                    </style>
                    <script>
                        jQuery(document).ready(function($) {
                            // Update slider values in real-time
                            $('#age_estimator_min_face_size').on('input', function() {
                                $('#min-face-size-value').text($(this).val() + 'px');
                            });
                            $('#age_estimator_max_face_size').on('input', function() {
                                $('#max-face-size-value').text($(this).val() + 'px');
                            });
                            $('#age_estimator_face_sensitivity').on('input', function() {
                                $('#face-sensitivity-value').text(($(this).val() / 100).toFixed(2));
                            });
                            $('#age_estimator_cache_duration').on('input', function() {
                                $('#cache-duration-value').text($(this).val() + 's');
                            });
                            $('#age_estimator_capture_delay').on('input', function() {
                                $('#capture-delay-value').text($(this).val() + 'ms');
                            });
                            $('#age_estimator_cooldown_period').on('input', function() {
                                $('#cooldown-period-value').text(($(this).val() / 1000).toFixed(1) + 's');
                            });
                        });
                    </script>
                </div>
                
                <div id="display" class="tab-content" style="display: none;">
                    <h3><?php _e('Display Options', 'age-estimator'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Display Style', 'age-estimator'); ?></th>
                            <td>
                                <div style="background: #e7f3ff; border: 1px solid #2196f3; border-radius: 4px; padding: 15px;">
                                    <strong>🖥️ <?php _e('Fullscreen Mode Only', 'age-estimator'); ?></strong><br>
                                    <?php _e('This Age Estimator is configured to use Fullscreen display mode exclusively for the best user experience. Inline and Modal popup options have been removed.', 'age-estimator'); ?>
                                </div>
                                <!-- Hidden field to maintain fullscreen setting -->
                                <input type="hidden" name="age_estimator_display_style" value="fullscreen" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Show Emotions', 'age-estimator'); ?></th>
                            <td>
                                <label for="age_estimator_show_emotions">
                                    <input type="checkbox" id="age_estimator_show_emotions" name="age_estimator_show_emotions" 
                                           value="1" <?php checked(get_option('age_estimator_show_emotions', true)); ?> />
                                    <?php _e('Display emotion detection results', 'age-estimator'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Show Attributes', 'age-estimator'); ?></th>
                            <td>
                                <label for="age_estimator_show_attributes">
                                    <input type="checkbox" id="age_estimator_show_attributes" name="age_estimator_show_attributes" 
                                           value="1" <?php checked(get_option('age_estimator_show_attributes', true)); ?> />
                                    <?php _e('Display additional face attributes', 'age-estimator'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Privacy Mode', 'age-estimator'); ?></th>
                            <td>
                                <label for="age_estimator_privacy_mode">
                                    <input type="checkbox" id="age_estimator_privacy_mode" name="age_estimator_privacy_mode" 
                                           value="1" <?php checked(get_option('age_estimator_privacy_mode', false)); ?> />
                                    <?php _e('Hide captured photo after analysis', 'age-estimator'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Logo Settings', 'age-estimator'); ?></th>
                            <td>
                                <label for="age_estimator_use_logo">
                                    <input type="checkbox" id="age_estimator_use_logo" name="age_estimator_use_logo" 
                                           value="1" <?php checked(get_option('age_estimator_use_logo', false)); ?> />
                                    <?php _e('Display logo on age estimator', 'age-estimator'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr class="logo-settings" <?php echo get_option('age_estimator_use_logo', false) ? '' : 'style="display: none;"'; ?>>
                            <th scope="row">
                                <label for="age_estimator_logo_url"><?php _e('Logo URL', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="age_estimator_logo_url" name="age_estimator_logo_url" 
                                       value="<?php echo esc_attr(get_option('age_estimator_logo_url')); ?>" 
                                       class="large-text" />
                                <p class="description"><?php _e('Enter the URL of your logo image. You can upload an image to your Media Library and copy its URL here.', 'age-estimator'); ?></p>
                                <?php if (get_option('age_estimator_logo_url')): ?>
                                    <div style="margin-top: 10px;">
                                        <img src="<?php echo esc_attr(get_option('age_estimator_logo_url')); ?>" 
                                             style="max-height: <?php echo esc_attr(get_option('age_estimator_logo_height', 40)); ?>px; max-width: 200px;" 
                                             alt="Logo preview" />
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr class="logo-settings" <?php echo get_option('age_estimator_use_logo', false) ? '' : 'style="display: none;"'; ?>>
                            <th scope="row">
                                <label for="age_estimator_logo_height"><?php _e('Logo Height', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="age_estimator_logo_height" name="age_estimator_logo_height" 
                                       value="<?php echo esc_attr(get_option('age_estimator_logo_height', 40)); ?>" 
                                       min="20" max="200" class="small-text" />
                                <span><?php _e('pixels', 'age-estimator'); ?></span>
                                <p class="description"><?php _e('Set the display height of your logo (width will scale proportionally)', 'age-estimator'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- Sound Notification Settings -->
                    <div class="settings-section" style="margin-top: 30px;">
                        <h3><?php _e('Sound Notification Settings', 'age-estimator'); ?></h3>
                        <p class="description"><?php _e('Configure sound notifications that play when age verification results are returned.', 'age-estimator'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Sound Notifications', 'age-estimator'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="age_estimator_enable_sounds" id="age_estimator_enable_sounds" value="1" <?php checked(get_option('age_estimator_enable_sounds', false)); ?> />
                                        <?php _e('Play sound notifications for pass/fail results', 'age-estimator'); ?>
                                    </label>
                                    <p class="description"><?php _e('Supported formats: MP3, WAV, OGG', 'age-estimator'); ?></p>
                                </td>
                            </tr>
                            
                            <tr class="sound-settings" style="<?php echo get_option('age_estimator_enable_sounds', false) ? '' : 'display: none;'; ?>">
                                <th scope="row">
                                    <label for="age_estimator_pass_sound_url"><?php _e('Pass Sound', 'age-estimator'); ?></label>
                                </th>
                                <td>
                                    <input type="url" id="age_estimator_pass_sound_url" name="age_estimator_pass_sound_url" 
                                           value="<?php echo esc_attr(get_option('age_estimator_pass_sound_url', '')); ?>" 
                                           class="regular-text" placeholder="https://example.com/pass-sound.mp3" />
                                    <button type="button" class="button age-estimator-media-button" data-target="age_estimator_pass_sound_url">
                                        <?php _e('Choose Sound', 'age-estimator'); ?>
                                    </button>
                                    <button type="button" class="button age-estimator-play-sound" data-sound-url="<?php echo esc_attr(get_option('age_estimator_pass_sound_url', '')); ?>" <?php echo empty(get_option('age_estimator_pass_sound_url', '')) ? 'disabled' : ''; ?>>
                                        <?php _e('Test', 'age-estimator'); ?>
                                    </button>
                                    <p class="description">
                                        <?php _e('Sound played when age verification passes (age ≥ minimum age).', 'age-estimator'); ?>
                                        <a href="<?php echo admin_url('media-new.php'); ?>" target="_blank"><?php _e('Upload new sound', 'age-estimator'); ?></a>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr class="sound-settings" style="<?php echo get_option('age_estimator_enable_sounds', false) ? '' : 'display: none;'; ?>">
                                <th scope="row">
                                    <label for="age_estimator_fail_sound_url"><?php _e('Fail Sound', 'age-estimator'); ?></label>
                                </th>
                                <td>
                                    <input type="url" id="age_estimator_fail_sound_url" name="age_estimator_fail_sound_url" 
                                           value="<?php echo esc_attr(get_option('age_estimator_fail_sound_url', '')); ?>" 
                                           class="regular-text" placeholder="https://example.com/fail-sound.mp3" />
                                    <button type="button" class="button age-estimator-media-button" data-target="age_estimator_fail_sound_url">
                                        <?php _e('Choose Sound', 'age-estimator'); ?>
                                    </button>
                                    <button type="button" class="button age-estimator-play-sound" data-sound-url="<?php echo esc_attr(get_option('age_estimator_fail_sound_url', '')); ?>" <?php echo empty(get_option('age_estimator_fail_sound_url', '')) ? 'disabled' : ''; ?>>
                                        <?php _e('Test', 'age-estimator'); ?>
                                    </button>
                                    <p class="description">
                                        <?php _e('Sound played when age verification fails (age < minimum age).', 'age-estimator'); ?>
                                        <a href="<?php echo admin_url('media-new.php'); ?>" target="_blank"><?php _e('Upload new sound', 'age-estimator'); ?></a>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr class="sound-settings" style="<?php echo get_option('age_estimator_enable_sounds', false) ? '' : 'display: none;'; ?>">
                                <th scope="row">
                                    <label for="age_estimator_sound_volume"><?php _e('Sound Volume', 'age-estimator'); ?></label>
                                </th>
                                <td>
                                    <input type="range" id="age_estimator_sound_volume" name="age_estimator_sound_volume" 
                                           value="<?php echo esc_attr(get_option('age_estimator_sound_volume', 0.7)); ?>" 
                                           min="0" max="1" step="0.1" class="slider age-estimator-volume" />
                                    <span id="volume-display" class="slider-value"><?php echo esc_html(get_option('age_estimator_sound_volume', 0.7) * 100); ?>%</span>
                                    <p class="description">
                                        <?php _e('Adjust the volume for all sound notifications (0-100%)', 'age-estimator'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Kiosk Mode Section -->
                    <div class="settings-section" style="margin-top: 30px;">
                        <h3><?php _e('Kiosk Mode Settings', 'age-estimator'); ?></h3>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Kiosk Mode', 'age-estimator'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="age_estimator_kiosk_mode" id="age_estimator_kiosk_mode" value="1" <?php checked(get_option('age_estimator_kiosk_mode', false)); ?> />
                                        <?php _e('Display an advertisement image when no face is detected', 'age-estimator'); ?>
                                    </label>
                                    <p class="description"><?php _e('Perfect for retail environments to show ads between customers.', 'age-estimator'); ?></p>
                                </td>
                            </tr>
                            
                            <tr class="kiosk-settings" style="<?php echo get_option('age_estimator_kiosk_mode', false) ? '' : 'display: none;'; ?>">
                                <th scope="row">
                                    <label for="age_estimator_kiosk_image"><?php _e('Advertisement Image URL', 'age-estimator'); ?></label>
                                </th>
                                <td>
                                    <input type="url" id="age_estimator_kiosk_image" name="age_estimator_kiosk_image" 
                                           value="<?php echo esc_attr(get_option('age_estimator_kiosk_image', '')); ?>" 
                                           class="large-text" />
                                    <button type="button" class="button button-secondary" id="upload-kiosk-image"><?php _e('Upload Image', 'age-estimator'); ?></button>
                                    <p class="description"><?php _e('Enter the URL of the image to display when no face is detected.', 'age-estimator'); ?></p>
                                    
                                    <?php if (get_option('age_estimator_kiosk_image')): ?>
                                        <div class="kiosk-image-preview" style="margin-top: 10px;">
                                            <img src="<?php echo esc_url(get_option('age_estimator_kiosk_image')); ?>" style="max-width: 300px; height: auto; border: 1px solid #ddd;" />
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <tr class="kiosk-settings" style="<?php echo get_option('age_estimator_kiosk_mode', false) ? '' : 'display: none;'; ?>">
                                <th scope="row">
                                    <label for="age_estimator_kiosk_display_time"><?php _e('Display Time After Detection (seconds)', 'age-estimator'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="age_estimator_kiosk_display_time" name="age_estimator_kiosk_display_time" 
                                           value="<?php echo esc_attr(get_option('age_estimator_kiosk_display_time', 5)); ?>" 
                                           min="1" max="60" class="small-text" />
                                    <p class="description"><?php _e('How long to show the age result before returning to the ad (1-60 seconds).', 'age-estimator'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Fullscreen Banner Ad Section -->
                    <div class="settings-section" style="margin-top: 30px;">
                        <h3><?php _e('Fullscreen Banner Ad', 'age-estimator'); ?></h3>
                        <p class="description"><?php _e('Display a banner advertisement at the bottom of the camera view when in fullscreen mode.', 'age-estimator'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Banner Ad', 'age-estimator'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="age_estimator_enable_banner_ad" id="age_estimator_enable_banner_ad" value="1" <?php checked(get_option('age_estimator_enable_banner_ad', false)); ?> />
                                        <?php _e('Show banner ad in fullscreen mode', 'age-estimator'); ?>
                                    </label>
                                    <p class="description"><?php _e('Displays a customizable banner advertisement at the bottom of the camera view when in fullscreen.', 'age-estimator'); ?></p>
                                </td>
                            </tr>
                            
                            <tr class="banner-ad-settings" style="<?php echo get_option('age_estimator_enable_banner_ad', false) ? '' : 'display: none;'; ?>">
                                <th scope="row">
                                    <label for="age_estimator_banner_ad_image"><?php _e('Banner Image URL', 'age-estimator'); ?></label>
                                </th>
                                <td>
                                    <input type="url" id="age_estimator_banner_ad_image" name="age_estimator_banner_ad_image" 
                                           value="<?php echo esc_attr(get_option('age_estimator_banner_ad_image', '')); ?>" 
                                           class="large-text" />
                                    <button type="button" class="button button-secondary" id="upload-banner-ad-image"><?php _e('Upload Image', 'age-estimator'); ?></button>
                                    <p class="description"><?php _e('Enter the URL of the banner image. Recommended size: 1200x100 pixels (JPG or PNG).', 'age-estimator'); ?></p>
                                    
                                    <?php if (get_option('age_estimator_banner_ad_image')): ?>
                                        <div class="banner-ad-image-preview" style="margin-top: 10px;">
                                            <img src="<?php echo esc_url(get_option('age_estimator_banner_ad_image')); ?>" style="max-width: 400px; height: auto; border: 1px solid #ddd;" />
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <tr class="banner-ad-settings" style="<?php echo get_option('age_estimator_enable_banner_ad', false) ? '' : 'display: none;'; ?>">
                                <th scope="row">
                                    <label for="age_estimator_banner_ad_link"><?php _e('Click URL (Optional)', 'age-estimator'); ?></label>
                                </th>
                                <td>
                                    <input type="url" id="age_estimator_banner_ad_link" name="age_estimator_banner_ad_link" 
                                           value="<?php echo esc_attr(get_option('age_estimator_banner_ad_link', '')); ?>" 
                                           class="large-text" placeholder="https://example.com" />
                                    <p class="description"><?php _e('URL to open when the banner is clicked. Leave blank to disable clicking.', 'age-estimator'); ?></p>
                                </td>
                            </tr>
                            
                            <tr class="banner-ad-settings" style="<?php echo get_option('age_estimator_enable_banner_ad', false) ? '' : 'display: none;'; ?>">
                                <th scope="row">
                                    <label for="age_estimator_banner_ad_height"><?php _e('Banner Height', 'age-estimator'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="age_estimator_banner_ad_height" name="age_estimator_banner_ad_height" 
                                           value="<?php echo esc_attr(get_option('age_estimator_banner_ad_height', 100)); ?>" 
                                           min="50" max="200" class="small-text" />
                                    <span><?php _e('pixels', 'age-estimator'); ?></span>
                                    <p class="description"><?php _e('Height of the banner in fullscreen mode (50-200 pixels).', 'age-estimator'); ?></p>
                                </td>
                            </tr>
                            
                            <tr class="banner-ad-settings" style="<?php echo get_option('age_estimator_enable_banner_ad', false) ? '' : 'display: none;'; ?>">
                                <th scope="row">
                                    <label for="age_estimator_banner_ad_position"><?php _e('Banner Position', 'age-estimator'); ?></label>
                                </th>
                                <td>
                                    <select name="age_estimator_banner_ad_position" id="age_estimator_banner_ad_position">
                                        <option value="bottom" <?php selected(get_option('age_estimator_banner_ad_position', 'bottom'), 'bottom'); ?>>
                                            <?php _e('Bottom', 'age-estimator'); ?>
                                        </option>
                                        <option value="top" <?php selected(get_option('age_estimator_banner_ad_position'), 'top'); ?>>
                                            <?php _e('Top', 'age-estimator'); ?>
                                        </option>
                                    </select>
                                    <p class="description"><?php _e('Position of the banner in fullscreen mode.', 'age-estimator'); ?></p>
                                </td>
                            </tr>
                            
                            <tr class="banner-ad-settings" style="<?php echo get_option('age_estimator_enable_banner_ad', false) ? '' : 'display: none;'; ?>">
                                <th scope="row">
                                    <label for="age_estimator_banner_ad_opacity"><?php _e('Banner Opacity', 'age-estimator'); ?></label>
                                </th>
                                <td>
                                    <input type="range" id="age_estimator_banner_ad_opacity" name="age_estimator_banner_ad_opacity" 
                                           value="<?php echo esc_attr(get_option('age_estimator_banner_ad_opacity', 0.9)); ?>" 
                                           min="0.3" max="1" step="0.1" class="slider" />
                                    <span id="banner-opacity-display" class="slider-value"><?php echo esc_html(get_option('age_estimator_banner_ad_opacity', 0.9) * 100); ?>%</span>
                                    <p class="description"><?php _e('Transparency of the banner (30-100%). Lower values make it more transparent.', 'age-estimator'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div id="age-gate" class="tab-content" style="display: none;">
                    <h3><?php _e('Age Gating Settings', 'age-estimator'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Enable Age Gate', 'age-estimator'); ?></th>
                            <td>
                                <label for="age_estimator_enable_age_gate">
                                    <input type="checkbox" id="age_estimator_enable_age_gate" name="age_estimator_enable_age_gate" 
                                           value="1" <?php checked(get_option('age_estimator_enable_age_gate', false)); ?> />
                                    <?php _e('Enable age verification requirement', 'age-estimator'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="age_estimator_minimum_age"><?php _e('Minimum Age', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="age_estimator_minimum_age" name="age_estimator_minimum_age" 
                                       value="<?php echo esc_attr(get_option('age_estimator_minimum_age', 21)); ?>" 
                                       min="1" max="100" class="small-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="age_estimator_age_gate_message"><?php _e('Age Gate Message', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="age_estimator_age_gate_message" name="age_estimator_age_gate_message" 
                                       value="<?php echo esc_attr(get_option('age_estimator_age_gate_message', 'You must be {age} or older to access this content.')); ?>" 
                                       class="large-text" />
                                <p class="description"><?php _e('Use {age} as a placeholder for the minimum age.', 'age-estimator'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="age_estimator_age_gate_redirect"><?php _e('Redirect URL', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="age_estimator_age_gate_redirect" name="age_estimator_age_gate_redirect" 
                                       value="<?php echo esc_attr(get_option('age_estimator_age_gate_redirect')); ?>" 
                                       class="large-text" />
                                <p class="description"><?php _e('Where to redirect users who don\'t meet the age requirement (optional).', 'age-estimator'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="privacy" class="tab-content" style="display: none;">
                    <h3><?php _e('Privacy & Compliance Settings', 'age-estimator'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Require Consent', 'age-estimator'); ?></th>
                            <td>
                                <label for="age_estimator_require_consent">
                                    <input type="checkbox" id="age_estimator_require_consent" name="age_estimator_require_consent" 
                                           value="1" <?php checked(get_option('age_estimator_require_consent', true)); ?> />
                                    <?php _e('Require user consent before capturing photo', 'age-estimator'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="age_estimator_consent_text"><?php _e('Consent Text', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <textarea id="age_estimator_consent_text" name="age_estimator_consent_text" 
                                          rows="4" class="large-text"><?php echo esc_textarea(get_option('age_estimator_consent_text', 'I consent to the processing of my facial image for age verification purposes. My image will be processed securely and deleted immediately after verification.')); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="age_estimator_data_retention_hours"><?php _e('Data Retention', 'age-estimator'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="age_estimator_data_retention_hours" name="age_estimator_data_retention_hours" 
                                       value="<?php echo esc_attr(get_option('age_estimator_data_retention_hours', 0)); ?>" 
                                       min="0" class="small-text" />
                                <span><?php _e('hours', 'age-estimator'); ?></span>
                                <p class="description"><?php _e('How long to retain captured images (0 = immediate deletion).', 'age-estimator'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button(); ?>
            </form>
            
            <!-- API Usage Statistics Widget -->
            <?php if (get_option('age_estimator_mode') === 'aws'): ?>
            <div class="age-estimator-stats-widget" style="background: #f0f8ff; border: 1px solid #2271b1; border-radius: 4px; padding: 20px; margin: 20px 0;">
                <h3 style="margin-top: 0;"><?php _e('API Usage Statistics', 'age-estimator'); ?></h3>
                <?php
                $tracker = AgeEstimatorAPITracker::get_instance();
                $today_stats = $tracker->get_stats('day');
                $month_stats = $tracker->get_stats('month');
                ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 15px 0;">
                    <div>
                        <strong><?php _e('Today:', 'age-estimator'); ?></strong><br>
                        <?php echo number_format($today_stats['total_calls'] ?? 0); ?> <?php _e('API calls', 'age-estimator'); ?><br>
                        <?php echo number_format($today_stats['unique_users'] ?? 0); ?> <?php _e('unique users', 'age-estimator'); ?>
                    </div>
                    <div>
                        <strong><?php _e('This Month:', 'age-estimator'); ?></strong><br>
                        <?php echo number_format($month_stats['total_calls'] ?? 0); ?> <?php _e('API calls', 'age-estimator'); ?><br>
                        <?php echo number_format($month_stats['unique_users'] ?? 0); ?> <?php _e('unique users', 'age-estimator'); ?>
                    </div>
                </div>
                <p style="margin-bottom: 0;">
                    <a href="<?php echo admin_url('admin.php?page=age-estimator-stats'); ?>" class="button button-primary">
                        <?php _e('View Detailed Statistics', 'age-estimator'); ?>
                    </a>
                </p>
            </div>
            <?php endif; ?>
            
            <div class="age-estimator-instructions">  
                <h3><?php _e('Usage Instructions', 'age-estimator'); ?></h3>
                <p><?php _e('To display the age estimator on your site, use the following shortcode:', 'age-estimator'); ?></p>
                <code>[age_estimator]</code>
                <p><?php _e('You can customize the shortcode with these attributes:', 'age-estimator'); ?></p>
                <ul>
                    <li><code>title="Your Title"</code> - <?php _e('Change the title text', 'age-estimator'); ?></li>
                    <li><code>button_text="Your Button Text"</code> - <?php _e('Change the button text', 'age-estimator'); ?></li>
                    <li><code>style="inline|modal|fullscreen"</code> - <?php _e('Override the display style', 'age-estimator'); ?></li>
                    <li><code>class="your-custom-class"</code> - <?php _e('Add custom CSS classes', 'age-estimator'); ?></li>
                </ul>
                <p><?php _e('Example:', 'age-estimator'); ?> <code>[age_estimator title="Verify Your Age" button_text="Scan Face" style="modal"]</code></p>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab navigation
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                $('.tab-content').hide();
                $($(this).attr('href')).show();
            });
            
            // Show/hide AWS settings based on mode
            $('#age_estimator_mode').on('change', function() {
                if ($(this).val() === 'aws') {
                    $('a[href="#aws"]').show();
                    $('.averaging-settings').hide();
                    $('.averaging-samples').hide();
                } else {
                    $('a[href="#aws"]').hide();
                    $('.averaging-settings').show();
                    if ($('#age_estimator_enable_averaging').is(':checked')) {
                        $('.averaging-samples').show();
                    }
                    if ($('a[href="#aws"]').hasClass('nav-tab-active')) {
                        $('a[href="#general"]').click();
                    }
                }
            }).trigger('change');
            
            // Show/hide averaging samples based on checkbox
            $('#age_estimator_enable_averaging').on('change', function() {
                if ($(this).is(':checked') && $('#age_estimator_mode').val() === 'simple') {
                    $('.averaging-samples').show();
                } else {
                    $('.averaging-samples').hide();
                }
            });
            
            // Show/hide logo settings based on checkbox
            $('#age_estimator_use_logo').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.logo-settings').show();
                } else {
                    $('.logo-settings').hide();
                }
            });
            
            // Show/hide sound settings based on checkbox
            $('#age_estimator_enable_sounds').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.sound-settings').show();
                } else {
                    $('.sound-settings').hide();
                }
            });
            
            // Update volume display
            $('#age_estimator_sound_volume').on('input', function() {
                $('#volume-display').text(Math.round($(this).val() * 100) + '%');
            });
            
            // Media uploader for sound files
            $('.age-estimator-media-button').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                var targetInput = button.data('target');
                
                var mediaUploader = wp.media({
                    title: 'Choose Sound File',
                    button: {
                        text: 'Use this sound'
                    },
                    library: {
                        type: ['audio']
                    },
                    multiple: false
                }).on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('input[name="' + targetInput + '"]').val(attachment.url);
                    button.siblings('.age-estimator-play-sound').prop('disabled', false).data('sound-url', attachment.url);
                }).open();
            });
            
            // Test sound playback
            $('.age-estimator-play-sound').on('click', function(e) {
                e.preventDefault();
                var soundUrl = $(this).data('sound-url');
                if (soundUrl) {
                    var audio = new Audio(soundUrl);
                    var volume = $('#age_estimator_sound_volume').val() || 0.7;
                    audio.volume = parseFloat(volume);
                    audio.play().catch(function(error) {
                        alert('Error playing sound: ' + error.message);
                    });
                }
            });
            
            // Show/hide kiosk settings based on checkbox
            $('#age_estimator_kiosk_mode').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.kiosk-settings').show();
                } else {
                    $('.kiosk-settings').hide();
                }
            });
            
            // Media uploader for kiosk image
            $('#upload-kiosk-image').on('click', function(e) {
                e.preventDefault();
                
                var mediaUploader = wp.media({
                    title: 'Choose Advertisement Image',
                    button: {
                        text: 'Use this image'
                    },
                    library: {
                        type: ['image']
                    },
                    multiple: false
                }).on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#age_estimator_kiosk_image').val(attachment.url);
                    
                    // Update preview
                    var preview = '<div class="kiosk-image-preview" style="margin-top: 10px;"><img src="' + attachment.url + '" style="max-width: 300px; height: auto; border: 1px solid #ddd;" /></div>';
                    $('.kiosk-image-preview').remove();
                    $('#age_estimator_kiosk_image').closest('td').append(preview);
                }).open();
            });
            
            // Show/hide banner ad settings based on checkbox
            $('#age_estimator_enable_banner_ad').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.banner-ad-settings').show();
                } else {
                    $('.banner-ad-settings').hide();
                }
            });
            
            // Media uploader for banner ad image
            $('#upload-banner-ad-image').on('click', function(e) {
                e.preventDefault();
                
                var mediaUploader = wp.media({
                    title: 'Choose Banner Image',
                    button: {
                        text: 'Use this image'
                    },
                    library: {
                        type: ['image']
                    },
                    multiple: false
                }).on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#age_estimator_banner_ad_image').val(attachment.url);
                    
                    // Update preview
                    var preview = '<div class="banner-ad-image-preview" style="margin-top: 10px;"><img src="' + attachment.url + '" style="max-width: 400px; height: auto; border: 1px solid #ddd;" /></div>';
                    $('.banner-ad-image-preview').remove();
                    $('#age_estimator_banner_ad_image').closest('td').append(preview);
                }).open();
            });
            
            // Update banner opacity display
            $('#age_estimator_banner_ad_opacity').on('input', function() {
                $('#banner-opacity-display').text(Math.round($(this).val() * 100) + '%');
            });
        });
        </script>
        <?php
    }
    
    /**
     * Test AWS connection
     */
    public function test_aws_connection() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_admin_nonce')) {
            wp_send_json_error(array(
                'message' => 'Security check failed'
            ));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Unauthorized'
            ));
            return;
        }
        
        // Get credentials from the saved options
        $access_key = get_option('age_estimator_aws_access_key', '');
        $secret_key = get_option('age_estimator_aws_secret_key', '');
        $region = get_option('age_estimator_aws_region', 'us-east-1');
        
        if (empty($access_key) || empty($secret_key)) {
            wp_send_json_error(array(
                'message' => 'AWS credentials not configured. Please save your settings first.'
            ));
            return;
        }
        
        // Test connection
        $result = $this->test_aws_credentials($access_key, $secret_key, $region);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => $result['message']
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message']
            ));
        }
    }
    
    /**
     * Test AWS credentials
     */
    private function test_aws_credentials($access_key, $secret_key, $region) {
        // Include AWS API if not already loaded
        if (!class_exists('AgeEstimatorAWSRekognition')) {
            $aws_file = AGE_ESTIMATOR_PATH . 'includes/aws-rekognition-api.php';
            if (file_exists($aws_file)) {
                require_once $aws_file;
            } else {
                return array(
                    'success' => false,
                    'message' => __('AWS API file not found.', 'age-estimator')
                );
            }
        }
        
        try {
            // Test the connection
            $aws = new AgeEstimatorAWSRekognition($access_key, $secret_key, $region);
            return $aws->test_connection();
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'AWS Connection Error: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        // Only show on our settings page
        $screen = get_current_screen();
        if (!$screen || ($screen->id !== 'settings_page_age-estimator' && 
            $screen->id !== 'toplevel_page_age-estimator-settings')) {
            return;
        }
        
        // Check if AWS mode is selected but not configured
        $mode = get_option('age_estimator_mode', 'simple');
        if ($mode === 'aws') {
            $access_key = get_option('age_estimator_aws_access_key');
            $secret_key = get_option('age_estimator_aws_secret_key');
            
            if (empty($access_key) || empty($secret_key)) {
                ?>
                <div class="notice notice-warning">
                    <p><?php _e('AWS mode is selected but AWS credentials are not configured. Please add your AWS Access Key and Secret Key.', 'age-estimator'); ?></p>
                </div>
                <?php
            }
        }
    }
    
    /**
     * Check and update database schema
     */
    public function check_database_schema() {
        // Only run once per admin session
        if (get_transient('age_estimator_db_checked')) {
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'age_estimator_checks';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if ($table_exists) {
            // Get existing columns
            $existing_columns = $wpdb->get_col("DESCRIBE $table_name", 0);
            
            // List of columns that should exist
            $required_columns = array(
                'detection_mode' => "ADD COLUMN detection_mode varchar(20) DEFAULT 'aws'",
                'gender' => "ADD COLUMN gender varchar(20) DEFAULT ''",
                'confidence' => "ADD COLUMN confidence float DEFAULT 0",
                'user_id' => "ADD COLUMN user_id bigint(20) unsigned DEFAULT 0",
                'user_ip' => "ADD COLUMN user_ip varchar(45) DEFAULT ''",
                'session_id' => "ADD COLUMN session_id varchar(64) DEFAULT ''",
                'face_detected' => "ADD COLUMN face_detected int(1) DEFAULT 1",
                'age_gate_result' => "ADD COLUMN age_gate_result varchar(20) DEFAULT ''",
                'capture_time' => "ADD COLUMN capture_time varchar(50) DEFAULT ''",
                'averaged' => "ADD COLUMN averaged boolean DEFAULT false",
                'samples_count' => "ADD COLUMN samples_count int(3) DEFAULT 0",
                'samples_range' => "ADD COLUMN samples_range varchar(20) DEFAULT ''",
                'std_dev' => "ADD COLUMN std_dev float DEFAULT 0"
            );
            
            $updates_made = false;
            
            // Add missing columns
            foreach ($required_columns as $column => $definition) {
                if (!in_array($column, $existing_columns)) {
                    $query = "ALTER TABLE $table_name $definition";
                    $result = $wpdb->query($query);
                    if ($result !== false) {
                        $updates_made = true;
                    }
                }
            }
            
            if ($updates_made) {
                // Show success notice
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>';
                    echo __('Age Estimator database has been updated successfully.', 'age-estimator');
                    echo '</p></div>';
                });
            }
        }
        
        // Set transient to prevent running on every page load
        set_transient('age_estimator_db_checked', true, HOUR_IN_SECONDS);
    }

}

// Initialize admin
new AgeEstimatorAdmin();

// Backward compatibility for old class name
if (!class_exists('AgeEstimatorPhotoAdmin')) {
    class AgeEstimatorPhotoAdmin extends AgeEstimatorAdmin {}
}
