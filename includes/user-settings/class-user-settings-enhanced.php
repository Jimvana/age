<?php
/**
 * FIXED Enhanced User Settings Class for Age Estimator Plugin
 * This version includes all missing render methods and fixes AJAX handler issues
 * 
 * @package AgeEstimator
 * @since 2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AgeEstimatorUserSettingsEnhanced {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Settings sections configuration
     */
    private $settings_sections = array();
    
    /**
     * Get instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        // Delay initialization until 'init' hook
        add_action('init', array($this, 'initialize'), 30);
    }
    
    /**
     * Initialize the settings after text domain is loaded
     */
    public function initialize() {
        $this->define_settings_sections();
        $this->init_hooks();
    }
    
    /**
     * Define all settings sections
     */
    private function define_settings_sections() {
        $this->settings_sections = array(
            'general' => array(
                'title' => __('General Settings', 'age-estimator'),
                'icon' => '🎯',
                'fields' => array(
                    'show_results', 'show_confidence', 'result_display_time',
                    'age_gating_enabled', 'minimum_age'
                )
            ),
            'detection' => array(
                'title' => __('Face Detection', 'age-estimator'),
                'icon' => '👤',
                'fields' => array(
                    'face_sensitivity', 'detection_interval', 'min_face_size',
                    'max_face_size', 'face_tracking', 'multi_face', 'averaging_samples'
                )
            ),
            'retail' => array(
                'title' => __('Retail Mode', 'age-estimator'),
                'icon' => '🏪',
                'fields' => array(
                    'retail_mode_enabled', 'challenge_age', 'retail_pin',
                    'enable_logging', 'email_alerts', 'staff_email'
                )
            ),
            'privacy' => array(
                'title' => __('Privacy & Security', 'age-estimator'),
                'icon' => '🔒',
                'fields' => array(
                    'privacy_mode', 'require_consent', 'data_retention',
                    'session_timeout', 'two_factor'
                )
            ),
            'notifications' => array(
                'title' => __('Notifications', 'age-estimator'),
                'icon' => '🔔',
                'fields' => array(
                    'enable_sounds', 'sound_volume', 'pass_sound', 'fail_sound',
                    'screen_flash', 'success_color', 'failure_color'
                )
            ),
            'advanced' => array(
                'title' => __('Advanced', 'age-estimator'),
                'icon' => '⚡',
                'fields' => array(
                    'detection_mode', 'cache_duration', 'hardware_accel',
                    'emotion_detection', 'gender_detection', 'facial_attributes'
                )
            )
        );
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register shortcodes
        add_shortcode('age_estimator_user_settings', array($this, 'render_settings_panel'));
        add_shortcode('age_estimator_settings_enhanced', array($this, 'render_enhanced_settings'));
        
        // Register AJAX handlers
        add_action('wp_ajax_age_estimator_save_user_settings', array($this, 'save_user_settings'));
        add_action('wp_ajax_age_estimator_validate_user_pin', array($this, 'validate_user_pin'));
        add_action('wp_ajax_age_estimator_get_user_settings', array($this, 'get_user_settings'));
        add_action('wp_ajax_age_estimator_export_settings', array($this, 'export_settings'));
        add_action('wp_ajax_age_estimator_import_settings', array($this, 'import_settings'));
        add_action('wp_ajax_age_estimator_get_stats', array($this, 'get_user_stats'));
        add_action('wp_ajax_age_estimator_clear_user_data', array($this, 'clear_user_data'));
        add_action('wp_ajax_age_estimator_test_detection', array($this, 'test_detection'));
        
        // Enqueue scripts for settings page
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Register REST API endpoints for settings
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('age-estimator/v1', '/user-settings', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_settings'),
            'permission_callback' => function() {
                return is_user_logged_in();
            }
        ));
        
        register_rest_route('age-estimator/v1', '/user-settings', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_save_settings'),
            'permission_callback' => function() {
                return is_user_logged_in();
            }
        ));
    }
    
    /**
     * Enqueue enhanced scripts and styles
     */
    public function enqueue_scripts() {
        global $post;
        
        // Check if our shortcode is present
        if (is_a($post, 'WP_Post') && 
            (has_shortcode($post->post_content, 'age_estimator_user_settings') || 
             has_shortcode($post->post_content, 'age_estimator_settings_enhanced'))) {
            
            // Enqueue enhanced styles
            wp_enqueue_style(
                'age-estimator-user-settings-enhanced',
                AGE_ESTIMATOR_URL . 'css/user-settings-enhanced.css',
                array(),
                AGE_ESTIMATOR_VERSION
            );
            
            // Enqueue enhanced scripts
            wp_enqueue_script(
                'age-estimator-user-settings-enhanced',
                AGE_ESTIMATOR_URL . 'js/user-settings-enhanced.js',
                array('jquery', 'wp-api'),
                AGE_ESTIMATOR_VERSION,
                true
            );
            
            // Enqueue Chart.js for statistics
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js',
                array(),
                '3.9.1',
                true
            );
            
            // Localize script with enhanced data
            wp_localize_script('age-estimator-user-settings-enhanced', 'ageEstimatorEnhanced', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'apiUrl' => rest_url('age-estimator/v1/'),
                'nonce' => wp_create_nonce('age_estimator_user_settings'),
                'restNonce' => wp_create_nonce('wp_rest'),
                'userId' => get_current_user_id(),
                'sections' => $this->settings_sections,
                'currentSettings' => $this->get_all_user_settings(),
                'messages' => array(
                    'saveSuccess' => __('Settings saved successfully!', 'age-estimator'),
                    'saveError' => __('Error saving settings. Please try again.', 'age-estimator'),
                    'pinRequired' => __('Please enter a 4-digit PIN.', 'age-estimator'),
                    'pinMismatch' => __('PIN confirmation does not match.', 'age-estimator'),
                    'importSuccess' => __('Settings imported successfully!', 'age-estimator'),
                    'importError' => __('Error importing settings. Invalid file format.', 'age-estimator'),
                    'exportSuccess' => __('Settings exported successfully!', 'age-estimator'),
                    'clearSuccess' => __('Data cleared successfully!', 'age-estimator'),
                    'clearError' => __('Error clearing data.', 'age-estimator'),
                    'testStarted' => __('Detection test started. Check your camera...', 'age-estimator')
                ),
                'defaults' => $this->get_default_settings()
            ));
        }
    }
    
    /**
     * Get default settings
     */
    private function get_default_settings() {
        return array(
            // General
            'camera_autostart' => false,
            'show_results' => true,
            'show_confidence' => true,
            'result_display_time' => 5,
            'age_gating_enabled' => false,
            'minimum_age' => 18,
            
            // Detection
            'face_sensitivity' => 0.4,
            'detection_interval' => 500,
            'min_face_size' => 150,
            'max_face_size' => 350,
            'face_tracking' => true,
            'multi_face' => false,
            'averaging_samples' => 5,
            
            // Retail
            'retail_mode_enabled' => false,
            'challenge_age' => 25,
            'enable_logging' => false,
            'email_alerts' => false,
            'staff_email' => '',
            
            // Privacy
            'privacy_mode' => false,
            'require_consent' => true,
            'data_retention' => 0,
            'session_timeout' => 15,
            'two_factor' => false,
            
            // Notifications
            'enable_sounds' => false,
            'sound_volume' => 70,
            'pass_sound' => 'default',
            'fail_sound' => 'default',
            'screen_flash' => false,
            'success_color' => '#28a745',
            'failure_color' => '#dc3545',
            
            // Advanced
            'detection_mode' => 'local',
            'cache_duration' => 30,
            'hardware_accel' => true,
            'emotion_detection' => false,
            'gender_detection' => false,
            'facial_attributes' => false
        );
    }
    
    /**
     * Get all user settings
     */
    private function get_all_user_settings($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $defaults = $this->get_default_settings();
        $settings = array();
        
        foreach ($defaults as $key => $default_value) {
            $meta_key = 'age_estimator_' . $key;
            $value = get_user_meta($user_id, $meta_key, true);
            
            if ($value === '' || $value === false) {
                $settings[$key] = $default_value;
            } else {
                // Handle boolean values
                if (is_bool($default_value)) {
                    $settings[$key] = ($value === '1' || $value === true || $value === 'true');
                } else {
                    $settings[$key] = $value;
                }
            }
        }
        
        return $settings;
    }
    
    /**
     * Render enhanced settings panel
     */
    public function render_enhanced_settings($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'theme' => 'light', // light, dark, auto
            'layout' => 'sidebar', // sidebar, tabs, accordion
            'show_stats' => true,
            'allow_export' => true,
            'show_login_button' => true
        ), $atts);
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return $this->render_login_form($atts);
        }
        
        // Start building content
        $content = $this->build_enhanced_settings_content($atts);
        
        // Apply PIN protection filter
        $content = apply_filters('age_estimator_render_enhanced_settings', $content, $atts);
        
        return $content;
    }
    
    /**
     * Render settings panel (fallback to enhanced)
     */
    public function render_settings_panel($atts = array()) {
        return $this->render_enhanced_settings($atts);
    }
    
    /**
     * Build the actual enhanced settings content
     */
    private function build_enhanced_settings_content($atts) {
        
        $user_id = get_current_user_id();
        $settings = $this->get_all_user_settings($user_id);
        $stats = $this->get_user_statistics($user_id);
        
        ob_start();
        ?>
        <div class="age-estimator-settings-enhanced" 
             data-theme="<?php echo esc_attr($atts['theme']); ?>"
             data-layout="<?php echo esc_attr($atts['layout']); ?>">
            
            <div class="settings-header">
                <div class="settings-header-content">
                    <h1><?php _e('Age Estimator Settings', 'age-estimator'); ?></h1>
                    <p><?php _e('Customize your age estimation experience', 'age-estimator'); ?></p>
                </div>
            </div>
            
            <div class="settings-wrapper">
                <!-- Sidebar Navigation -->
                <?php if ($atts['layout'] === 'sidebar'): ?>
                <aside class="settings-sidebar">
                    <?php if ($atts['show_login_button']): ?>
                    <!-- User Info Section -->
                    <div class="sidebar-user-info">
                        <div class="user-avatar">
                            <?php echo get_avatar(get_current_user_id(), 40, '', '', array('class' => 'user-avatar-img')); ?>
                        </div>
                        <div class="user-details">
                            <div class="user-name"><?php echo esc_html(wp_get_current_user()->display_name); ?></div>
                            <div class="user-role"><?php echo esc_html(ucfirst(wp_get_current_user()->roles[0] ?? 'User')); ?></div>
                        </div>
                        <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="sidebar-logout-btn" title="<?php _e('Logout', 'age-estimator'); ?>">
                            <span class="logout-icon">🚪</span>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <nav>
                        <ul class="settings-nav">
                            <?php foreach ($this->settings_sections as $section_id => $section): ?>
                            <li>
                                <a href="#<?php echo esc_attr($section_id); ?>" 
                                   class="nav-link <?php echo $section_id === 'general' ? 'active' : ''; ?>">
                                    <span class="icon"><?php echo $section['icon']; ?></span>
                                    <?php echo esc_html($section['title']); ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                            <?php if ($atts['show_stats']): ?>
                            <li>
                                <a href="#stats" class="nav-link">
                                    <span class="icon">📊</span>
                                    <?php _e('Statistics', 'age-estimator'); ?>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </aside>
                <?php endif; ?>
                
                <!-- Main Content -->
                <main class="settings-content">
                    <div id="alert-container"></div>
                    
                    <!-- Settings Panels -->
                    <?php foreach ($this->settings_sections as $section_id => $section): ?>
                    <div id="<?php echo esc_attr($section_id); ?>" 
                         class="settings-panel <?php echo $section_id === 'general' ? 'active' : ''; ?>">
                        <?php $this->render_section($section_id, $section, $settings); ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Statistics Panel -->
                    <?php if ($atts['show_stats']): ?>
                    <div id="stats" class="settings-panel">
                        <?php $this->render_statistics_panel($stats); ?>
                    </div>
                    <?php endif; ?>
                </main>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render individual section
     */
    private function render_section($section_id, $section, $settings) {
        ?>
        <div class="panel-header">
            <h2><?php echo esc_html($section['title']); ?></h2>
        </div>
        
        <form class="settings-form" data-section="<?php echo esc_attr($section_id); ?>">
            <?php
            // Render fields based on section
            switch ($section_id) {
                case 'general':
                    $this->render_general_fields($settings);
                    break;
                case 'detection':
                    $this->render_detection_fields($settings);
                    break;
                case 'retail':
                    $this->render_retail_fields($settings);
                    break;
                case 'privacy':
                    $this->render_privacy_fields($settings);
                    break;
                case 'notifications':
                    $this->render_notification_fields($settings);
                    break;
                case 'advanced':
                    $this->render_advanced_fields($settings);
                    break;
            }
            ?>
            
            <div class="button-group">
                <button type="submit" class="btn btn-primary">
                    <?php _e('Save Changes', 'age-estimator'); ?>
                </button>
                <button type="button" class="btn btn-outline" onclick="resetSection('<?php echo esc_attr($section_id); ?>')">
                    <?php _e('Reset to Default', 'age-estimator'); ?>
                </button>
            </div>
        </form>
        <?php
    }
    
    /**
     * Render general settings fields
     */
    private function render_general_fields($settings) {
        ?>
        <div class="form-section">
            <h3><?php _e('Camera Settings', 'age-estimator'); ?></h3>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="camera_autostart" 
                           <?php checked($settings['camera_autostart'], true); ?>>
                    <?php _e('Auto-Start Camera', 'age-estimator'); ?>
                </label>
                <p class="form-help"><?php _e('Automatically start the camera when the plugin loads', 'age-estimator'); ?></p>
            </div>
        </div>
        
        <div class="form-section">
            <h3><?php _e('Display Options', 'age-estimator'); ?></h3>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="show_results" 
                           <?php checked($settings['show_results'], true); ?>>
                    <?php _e('Show Age Estimation Results', 'age-estimator'); ?>
                </label>
                <p class="form-help"><?php _e('Display estimated age on screen', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="show_confidence" 
                           <?php checked($settings['show_confidence'], true); ?>>
                    <?php _e('Show Confidence Score', 'age-estimator'); ?>
                </label>
                <p class="form-help"><?php _e('Display accuracy percentage', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="result_display_time"><?php _e('Result Display Duration', 'age-estimator'); ?></label>
                <div class="range-group">
                    <input type="range" name="result_display_time" id="result_display_time" 
                           min="1" max="10" value="<?php echo esc_attr($settings['result_display_time']); ?>" 
                           class="range-slider">
                    <span class="range-value"><?php echo esc_html($settings['result_display_time']); ?>s</span>
                </div>
                <p class="form-help"><?php _e('How long to display age results before clearing', 'age-estimator'); ?></p>
            </div>
        </div>
        
        <div class="form-section">
            <h3><?php _e('Age Gating', 'age-estimator'); ?></h3>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="age_gating_enabled" 
                           <?php checked($settings['age_gating_enabled'], true); ?>>
                    <?php _e('Enable Age Restriction', 'age-estimator'); ?>
                </label>
                <p class="form-help"><?php _e('Restrict access based on age', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="minimum_age"><?php _e('Minimum Age Requirement', 'age-estimator'); ?></label>
                <input type="number" name="minimum_age" id="minimum_age" 
                       class="form-control small" 
                       min="13" max="25" 
                       value="<?php echo esc_attr($settings['minimum_age']); ?>">
                <p class="form-help"><?php _e('Users must appear at least this age to proceed', 'age-estimator'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render detection settings fields - FIXED METHOD
     */
    private function render_detection_fields($settings) {
        ?>
        <div class="form-section">
            <h3><?php _e('Detection Settings', 'age-estimator'); ?></h3>
            
            <div class="form-group">
                <label for="face_sensitivity"><?php _e('Face Detection Sensitivity', 'age-estimator'); ?></label>
                <div class="range-group">
                    <input type="range" name="face_sensitivity" id="face_sensitivity" 
                           min="0.1" max="0.9" step="0.1" 
                           value="<?php echo esc_attr($settings['face_sensitivity']); ?>" 
                           class="range-slider">
                    <span class="range-value"><?php echo esc_html($settings['face_sensitivity']); ?></span>
                </div>
                <p class="form-help"><?php _e('Lower values = more sensitive detection', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="detection_interval"><?php _e('Detection Interval (ms)', 'age-estimator'); ?></label>
                <input type="number" name="detection_interval" id="detection_interval" 
                       class="form-control small" 
                       min="100" max="2000" step="100"
                       value="<?php echo esc_attr($settings['detection_interval']); ?>">
                <p class="form-help"><?php _e('How often to check for faces (in milliseconds)', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="face_tracking" 
                           <?php checked($settings['face_tracking'], true); ?>>
                    <?php _e('Enable Face Tracking', 'age-estimator'); ?>
                </label>
                <p class="form-help"><?php _e('Track faces between frames for smoother detection', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="multi_face" 
                           <?php checked($settings['multi_face'], true); ?>>
                    <?php _e('Multi-Face Detection', 'age-estimator'); ?>
                </label>
                <p class="form-help"><?php _e('Detect and process multiple faces simultaneously', 'age-estimator'); ?></p>
            </div>
        </div>
        
        <div class="form-section">
            <h3><?php _e('Face Size Settings', 'age-estimator'); ?></h3>
            
            <div class="form-group">
                <label for="min_face_size"><?php _e('Minimum Face Size (px)', 'age-estimator'); ?></label>
                <input type="number" name="min_face_size" id="min_face_size" 
                       class="form-control small" 
                       min="50" max="300" 
                       value="<?php echo esc_attr($settings['min_face_size']); ?>">
                <p class="form-help"><?php _e('Minimum face size to detect', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="max_face_size"><?php _e('Maximum Face Size (px)', 'age-estimator'); ?></label>
                <input type="number" name="max_face_size" id="max_face_size" 
                       class="form-control small" 
                       min="200" max="800" 
                       value="<?php echo esc_attr($settings['max_face_size']); ?>">
                <p class="form-help"><?php _e('Maximum face size to detect', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="averaging_samples"><?php _e('Averaging Samples', 'age-estimator'); ?></label>
                <input type="number" name="averaging_samples" id="averaging_samples" 
                       class="form-control small" 
                       min="1" max="10" 
                       value="<?php echo esc_attr($settings['averaging_samples']); ?>">
                <p class="form-help"><?php _e('Number of samples to average for age estimation', 'age-estimator'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render retail mode settings fields - FIXED METHOD
     */
    private function render_retail_fields($settings) {
        ?>
        <div class="form-section">
            <h3><?php _e('Retail Compliance', 'age-estimator'); ?></h3>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="retail_mode_enabled" 
                           <?php checked($settings['retail_mode_enabled'], true); ?>>
                    <?php _e('Enable Retail Mode', 'age-estimator'); ?>
                </label>
                <p class="form-help"><?php _e('Enable Challenge 25 compliance features', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="challenge_age"><?php _e('Challenge Age', 'age-estimator'); ?></label>
                <input type="number" name="challenge_age" id="challenge_age" 
                       class="form-control small" 
                       min="18" max="30" 
                       value="<?php echo esc_attr($settings['challenge_age']); ?>">
                <p class="form-help"><?php _e('Challenge customers who appear under this age', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="retail_pin"><?php _e('Staff PIN', 'age-estimator'); ?></label>
                <input type="password" name="retail_pin" id="retail_pin" 
                       class="form-control small" 
                       maxlength="4" pattern="\d{4}"
                       placeholder="****">
                <p class="form-help"><?php _e('4-digit PIN for override access', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="retail_pin_confirm"><?php _e('Confirm PIN', 'age-estimator'); ?></label>
                <input type="password" name="retail_pin_confirm" id="retail_pin_confirm" 
                       class="form-control small" 
                       maxlength="4" pattern="\d{4}"
                       placeholder="****">
                <p class="form-help"><?php _e('Re-enter PIN to confirm', 'age-estimator'); ?></p>
            </div>
        </div>
        
        <div class="form-section">
            <h3><?php _e('Logging & Alerts', 'age-estimator'); ?></h3>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="enable_logging" 
                           <?php checked($settings['enable_logging'], true); ?>>
                    <?php _e('Enable Transaction Logging', 'age-estimator'); ?>
                </label>
                <p class="form-help"><?php _e('Log all age verification attempts', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="email_alerts" 
                           <?php checked($settings['email_alerts'], true); ?>>
                    <?php _e('Email Alerts', 'age-estimator'); ?>
                </label>
                <p class="form-help"><?php _e('Send email alerts for failed verifications', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="staff_email"><?php _e('Staff Email', 'age-estimator'); ?></label>
                <input type="email" name="staff_email" id="staff_email" 
                       class="form-control medium" 
                       value="<?php echo esc_attr($settings['staff_email'] ?? ''); ?>"
                       placeholder="manager@example.com">
                <p class="form-help"><?php _e('Email address for compliance alerts', 'age-estimator'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render privacy settings fields - FIXED METHOD
     */
    private function render_privacy_fields($settings) {
        ?>
        <div class="form-section">
            <h3><?php _e('Privacy Options', 'age-estimator'); ?></h3>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="privacy_mode" 
                           <?php checked($settings['privacy_mode'], true); ?>>
                    <?php _e('Privacy Mode', 'age-estimator'); ?>
                </label>
                <p class="form-help"><?php _e('Blur faces in camera preview', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="require_consent" 
                           <?php checked($settings['require_consent'], true); ?>>
                    <?php _e('Require Consent', 'age-estimator'); ?>
                </label>
                <p class="form-help"><?php _e('Users must consent before camera activation', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="data_retention"><?php _e('Data Retention (days)', 'age-estimator'); ?></label>
                <input type="number" name="data_retention" id="data_retention" 
                       class="form-control small" 
                       min="0" max="365" 
                       value="<?php echo esc_attr($settings['data_retention']); ?>">
                <p class="form-help"><?php _e('How long to retain scan data (0 = no retention)', 'age-estimator'); ?></p>
            </div>
        </div>
        
        <div class="form-section">
            <h3><?php _e('Security Settings', 'age-estimator'); ?></h3>
            
            <div class="form-group">
                <label for="session_timeout"><?php _e('Session Timeout (minutes)', 'age-estimator'); ?></label>
                <input type="number" name="session_timeout" id="session_timeout" 
                       class="form-control small" 
                       min="5" max="60" 
                       value="<?php echo esc_attr($settings['session_timeout']); ?>">
                <p class="form-help"><?php _e('Auto-logout after inactivity', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="two_factor" 
                           <?php checked($settings['two_factor'], true); ?>>
                    <?php _e('Two-Factor Authentication', 'age-estimator'); ?>
                </label>
                <p class="form-help"><?php _e('Require 2FA for settings access', 'age-estimator'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render notification settings fields - FIXED METHOD
     */
    private function render_notification_fields($settings) {
        ?>
        <div class="form-section">
            <h3><?php _e('Sound Settings', 'age-estimator'); ?></h3>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="enable_sounds" 
                           <?php checked($settings['enable_sounds'], true); ?>>
                    <?php _e('Enable Sound Effects', 'age-estimator'); ?>
                </label>
                <p class="form-help"><?php _e('Play sounds for pass/fail results', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="sound_volume"><?php _e('Sound Volume', 'age-estimator'); ?></label>
                <div class="range-group">
                    <input type="range" name="sound_volume" id="sound_volume" 
                           min="0" max="100" 
                           value="<?php echo esc_attr($settings['sound_volume']); ?>" 
                           class="range-slider">
                    <span class="range-value"><?php echo esc_html($settings['sound_volume']); ?>%</span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="pass_sound"><?php _e('Pass Sound', 'age-estimator'); ?></label>
                <select name="pass_sound" id="pass_sound" class="form-control">
                    <option value="default" <?php selected($settings['pass_sound'], 'default'); ?>><?php _e('Default Chime', 'age-estimator'); ?></option>
                    <option value="bell" <?php selected($settings['pass_sound'], 'bell'); ?>><?php _e('Bell', 'age-estimator'); ?></option>
                    <option value="success" <?php selected($settings['pass_sound'], 'success'); ?>><?php _e('Success Tone', 'age-estimator'); ?></option>
                    <option value="custom" <?php selected($settings['pass_sound'], 'custom'); ?>><?php _e('Custom', 'age-estimator'); ?></option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="fail_sound"><?php _e('Fail Sound', 'age-estimator'); ?></label>
                <select name="fail_sound" id="fail_sound" class="form-control">
                    <option value="default" <?php selected($settings['fail_sound'], 'default'); ?>><?php _e('Default Buzzer', 'age-estimator'); ?></option>
                    <option value="buzzer" <?php selected($settings['fail_sound'], 'buzzer'); ?>><?php _e('Buzzer', 'age-estimator'); ?></option>
                    <option value="warning" <?php selected($settings['fail_sound'], 'warning'); ?>><?php _e('Warning Tone', 'age-estimator'); ?></option>
                    <option value="custom" <?php selected($settings['fail_sound'], 'custom'); ?>><?php _e('Custom', 'age-estimator'); ?></option>
                </select>
            </div>
        </div>
        
        <div class="form-section">
            <h3><?php _e('Visual Feedback', 'age-estimator'); ?></h3>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="screen_flash" 
                           <?php checked($settings['screen_flash'], true); ?>>
                    <?php _e('Screen Flash', 'age-estimator'); ?>
                </label>
                <p class="form-help"><?php _e('Flash screen on pass/fail', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="success_color"><?php _e('Success Color', 'age-estimator'); ?></label>
                <input type="color" name="success_color" id="success_color" 
                       value="<?php echo esc_attr($settings['success_color']); ?>">
            </div>
            
            <div class="form-group">
                <label for="failure_color"><?php _e('Failure Color', 'age-estimator'); ?></label>
                <input type="color" name="failure_color" id="failure_color" 
                       value="<?php echo esc_attr($settings['failure_color']); ?>">
            </div>
        </div>
        <?php
    }
    
    /**
     * Render advanced settings fields - FIXED METHOD
     */
    private function render_advanced_fields($settings) {
        ?>
        <div class="form-section">
            <h3><?php _e('Detection Mode', 'age-estimator'); ?></h3>
            
            <div class="form-group">
                <label for="detection_mode"><?php _e('Detection Mode', 'age-estimator'); ?></label>
                <select name="detection_mode" id="detection_mode" class="form-control">
                    <option value="local" <?php selected($settings['detection_mode'], 'local'); ?>><?php _e('Local (Face-API.js)', 'age-estimator'); ?></option>
                    <option value="aws" <?php selected($settings['detection_mode'], 'aws'); ?>><?php _e('AWS Rekognition', 'age-estimator'); ?></option>
                    <option value="hybrid" <?php selected($settings['detection_mode'], 'hybrid'); ?>><?php _e('Hybrid (Local + AWS)', 'age-estimator'); ?></option>
                </select>
                <p class="form-help"><?php _e('Choose face detection backend', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="cache_duration"><?php _e('Cache Duration (seconds)', 'age-estimator'); ?></label>
                <input type="number" name="cache_duration" id="cache_duration" 
                       class="form-control small" 
                       min="0" max="3600" 
                       value="<?php echo esc_attr($settings['cache_duration']); ?>">
                <p class="form-help"><?php _e('How long to cache detection results', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="hardware_accel" 
                           <?php checked($settings['hardware_accel'], true); ?>>
                    <?php _e('Hardware Acceleration', 'age-estimator'); ?>
                </label>
                <p class="form-help"><?php _e('Use GPU acceleration when available', 'age-estimator'); ?></p>
            </div>
        </div>
        
        <div class="form-section">
            <h3><?php _e('Experimental Features', 'age-estimator'); ?></h3>
            
            <div class="info-card warning">
                <h4><?php _e('Warning', 'age-estimator'); ?></h4>
                <p><?php _e('These features are experimental and may affect performance or accuracy.', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="emotion_detection" 
                           <?php checked($settings['emotion_detection'], true); ?>>
                    <?php _e('Emotion Detection', 'age-estimator'); ?>
                </label>
                <p class="form-help"><?php _e('Detect facial expressions and emotions', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="gender_detection" 
                           <?php checked($settings['gender_detection'], true); ?>>
                    <?php _e('Gender Detection', 'age-estimator'); ?>
                </label>
                <p class="form-help"><?php _e('Include gender estimation in results', 'age-estimator'); ?></p>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="facial_attributes" 
                           <?php checked($settings['facial_attributes'], true); ?>>
                    <?php _e('Facial Attributes', 'age-estimator'); ?>
                </label>
                <p class="form-help"><?php _e('Detect additional facial attributes', 'age-estimator'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save user settings via AJAX - FIXED METHOD
     */
    public function save_user_settings() {
        error_log('save_user_settings called');
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_user_settings')) {
            error_log('Nonce verification failed');
            wp_send_json_error('Invalid security token');
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            error_log('User not logged in');
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $section = sanitize_text_field($_POST['section'] ?? 'general');
        $settings = $_POST['settings'] ?? array();
        
        error_log('Saving settings for user ' . $user_id . ', section: ' . $section);
        error_log('Settings data: ' . print_r($settings, true));
        
        // Validate and save each setting
        foreach ($settings as $key => $value) {
            $meta_key = 'age_estimator_' . sanitize_key($key);
            
            // Special handling for different field types
            if ($key === 'retail_pin' && !empty($value)) {
                // Hash PIN before storing
                $value = wp_hash_password(sanitize_text_field($value));
            } elseif (in_array($key, array('camera_autostart', 'show_results', 'show_confidence', 'age_gating_enabled', 
                                          'face_tracking', 'multi_face', 'retail_mode_enabled',
                                          'enable_logging', 'email_alerts', 'privacy_mode',
                                          'require_consent', 'two_factor', 'enable_sounds',
                                          'screen_flash', 'hardware_accel', 'emotion_detection',
                                          'gender_detection', 'facial_attributes'))) {
                // Boolean fields
                $value = $value === 'true' || $value === '1' ? '1' : '0';
            } elseif (is_numeric($value)) {
                // Numeric fields
                $value = is_float($value) ? floatval($value) : intval($value);
            } else {
                // Text fields
                $value = sanitize_text_field($value);
            }
            
            $result = update_user_meta($user_id, $meta_key, $value);
            error_log("Updated $meta_key = $value, result: " . ($result ? 'success' : 'failed'));
        }
        
        // Log the settings change
        $this->log_settings_change($user_id, $section);
        
        wp_send_json_success(array(
            'message' => __('Settings saved successfully!', 'age-estimator'),
            'section' => $section
        ));
    }
    
    /**
     * Get user statistics - FIXED METHOD
     */
    public function get_user_stats() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_user_settings')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $stats = $this->get_user_statistics($user_id);
        
        wp_send_json_success($stats);
    }
    
    /**
     * Export settings - FIXED METHOD
     */
    public function export_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_user_settings')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $settings = $this->get_all_user_settings($user_id);
        
        wp_send_json_success(array(
            'settings' => $settings,
            'exported_at' => current_time('mysql'),
            'user_id' => $user_id
        ));
    }
    
    /**
     * Import settings - FIXED METHOD
     */
    public function import_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_user_settings')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $settings = json_decode(stripslashes($_POST['settings']), true);
        
        if (!is_array($settings)) {
            wp_send_json_error('Invalid settings format');
        }
        
        // Import each setting
        foreach ($settings as $key => $value) {
            $meta_key = 'age_estimator_' . sanitize_key($key);
            update_user_meta($user_id, $meta_key, $value);
        }
        
        wp_send_json_success(array(
            'message' => __('Settings imported successfully!', 'age-estimator')
        ));
    }
    
    /**
     * Clear user data - FIXED METHOD
     */
    public function clear_user_data() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_user_settings')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $data_type = sanitize_text_field($_POST['data_type']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'age_estimator_checks';
        
        switch ($data_type) {
            case 'statistics':
                $wpdb->delete($table_name, array('user_id' => $user_id));
                break;
            case 'settings':
                $defaults = $this->get_default_settings();
                foreach ($defaults as $key => $value) {
                    delete_user_meta($user_id, 'age_estimator_' . $key);
                }
                break;
            case 'all':
                // Clear both stats and settings
                $wpdb->delete($table_name, array('user_id' => $user_id));
                $defaults = $this->get_default_settings();
                foreach ($defaults as $key => $value) {
                    delete_user_meta($user_id, 'age_estimator_' . $key);
                }
                break;
        }
        
        wp_send_json_success(array(
            'message' => __('Data cleared successfully!', 'age-estimator')
        ));
    }
    
    /**
     * Get user settings via AJAX - ADDED METHOD
     */
    public function get_user_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_user_settings')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $settings = $this->get_all_user_settings($user_id);
        
        wp_send_json_success($settings);
    }
    
    /**
     * Validate user PIN - ADDED METHOD
     */
    public function validate_user_pin() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_user_settings')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $pin = sanitize_text_field($_POST['pin'] ?? '');
        $stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
        
        if (empty($stored_pin)) {
            wp_send_json_error('No PIN set');
        }
        
        if (wp_check_password($pin, $stored_pin)) {
            wp_send_json_success(array('message' => 'PIN validated'));
        } else {
            wp_send_json_error('Invalid PIN');
        }
    }
    
    /**
     * Test detection - ADDED METHOD
     */
    public function test_detection() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_user_settings')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        wp_send_json_success(array(
            'message' => 'Detection test started'
        ));
    }
    
    /**
     * Get user statistics
     */
    private function get_user_statistics($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'age_estimator_checks';
        
        // Get basic stats
        $total_scans = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        $successful = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND alert_level = 'pass'",
            $user_id
        ));
        
        $failed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND alert_level IN ('fail', 'warning')",
            $user_id
        ));
        
        $avg_age = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(estimated_age) FROM $table_name WHERE user_id = %d AND estimated_age > 0",
            $user_id
        ));
        
        // Get daily stats for the last 7 days
        $daily_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(check_time) as date, COUNT(*) as count 
             FROM $table_name 
             WHERE user_id = %d 
             AND check_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY DATE(check_time)
             ORDER BY date ASC",
            $user_id
        ));
        
        return array(
            'total_scans' => $total_scans ?: 0,
            'successful' => $successful ?: 0,
            'failed' => $failed ?: 0,
            'average_age' => round($avg_age ?: 0, 1),
            'daily_stats' => $daily_stats
        );
    }
    
    /**
     * Render statistics panel
     */
    private function render_statistics_panel($stats) {
        ?>
        <div class="panel-header">
            <h2><?php _e('Usage Statistics', 'age-estimator'); ?></h2>
            <p><?php _e('View your age estimation usage analytics', 'age-estimator'); ?></p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total_scans']); ?></div>
                <div class="stat-label"><?php _e('Total Scans', 'age-estimator'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['successful']); ?></div>
                <div class="stat-label"><?php _e('Successful', 'age-estimator'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['failed']); ?></div>
                <div class="stat-label"><?php _e('Failed', 'age-estimator'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['average_age']; ?></div>
                <div class="stat-label"><?php _e('Avg Age', 'age-estimator'); ?></div>
            </div>
        </div>
        
        <div class="form-section">
            <h3><?php _e('Usage Chart', 'age-estimator'); ?></h3>
            <canvas id="usage-chart"></canvas>
        </div>
        
        <div class="button-group">
            <button type="button" class="btn btn-secondary" onclick="exportStats()">
                <?php _e('Export Statistics', 'age-estimator'); ?>
            </button>
            <button type="button" class="btn btn-outline" onclick="refreshStats()">
                <?php _e('Refresh Data', 'age-estimator'); ?>
            </button>
        </div>
        
        <script>
        // Initialize chart with stats data
        var statsData = <?php echo json_encode($stats['daily_stats']); ?>;
        </script>
        <?php
    }
    
    /**
     * Log settings change
     */
    private function log_settings_change($user_id, $section) {
        // Log to database or file
        $log_entry = array(
            'user_id' => $user_id,
            'section' => $section,
            'timestamp' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        );
        
        // Store in options table or custom table
        $logs = get_option('age_estimator_settings_logs', array());
        $logs[] = $log_entry;
        
        // Keep only last 100 entries
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        update_option('age_estimator_settings_logs', $logs);
    }
    
    /**
     * Render login form for non-logged-in users
     */
    private function render_login_form($atts) {
        ob_start();
        ?>
        <div class="age-estimator-login-wrapper" 
             data-theme="<?php echo esc_attr($atts['theme']); ?>">
            
            <div class="login-container">
                <div class="login-header">
                    <h2><?php _e('Age Estimator Settings', 'age-estimator'); ?></h2>
                    <p><?php _e('Please log in to access your settings', 'age-estimator'); ?></p>
                </div>
                
                <div class="login-content">
                    <div class="login-notice">
                        <div class="notice-icon">🔒</div>
                        <div class="notice-text">
                            <h3><?php _e('Login Required', 'age-estimator'); ?></h3>
                            <p><?php _e('You must be logged in to customize your age estimation settings.', 'age-estimator'); ?></p>
                        </div>
                    </div>
                    
                    <div class="login-actions">
                        <a href="<?php echo wp_login_url(get_permalink()); ?>" class="btn btn-primary login-btn">
                            <span class="btn-icon">👤</span>
                            <?php _e('Login', 'age-estimator'); ?>
                        </a>
                        
                        <?php if (get_option('users_can_register')): ?>
                        <a href="<?php echo wp_registration_url(); ?>" class="btn btn-outline register-btn">
                            <span class="btn-icon">📝</span>
                            <?php _e('Register', 'age-estimator'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="login-features">
                        <h4><?php _e('What you can do with an account:', 'age-estimator'); ?></h4>
                        <ul>
                            <li><?php _e('Customize face detection sensitivity', 'age-estimator'); ?></li>
                            <li><?php _e('Enable retail compliance features', 'age-estimator'); ?></li>
                            <li><?php _e('Configure privacy and security settings', 'age-estimator'); ?></li>
                            <li><?php _e('Set up notifications and alerts', 'age-estimator'); ?></li>
                            <li><?php _e('View usage statistics and analytics', 'age-estimator'); ?></li>
                            <li><?php _e('Export and import your settings', 'age-estimator'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * REST API endpoints
     */
    public function rest_get_settings($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('not_logged_in', 'Not logged in', array('status' => 401));
        }
        
        $user_id = get_current_user_id();
        $settings = $this->get_all_user_settings($user_id);
        
        return rest_ensure_response($settings);
    }
    
    public function rest_save_settings($request) {
        if (!is_user_logged_in()) {
            return new WP_Error('not_logged_in', 'Not logged in', array('status' => 401));
        }
        
        $user_id = get_current_user_id();
        $settings = $request->get_json_params();
        
        foreach ($settings as $key => $value) {
            $meta_key = 'age_estimator_' . sanitize_key($key);
            update_user_meta($user_id, $meta_key, $value);
        }
        
        return rest_ensure_response(array('message' => 'Settings saved successfully'));
    }
}

// Initialize the enhanced class after init action
add_action('init', function() {
    AgeEstimatorUserSettingsEnhanced::get_instance();
}, 25);
