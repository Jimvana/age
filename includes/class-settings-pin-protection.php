<?php
/**
 * Settings PIN Protection Class for Age Estimator Plugin
 * Protects user settings access with PIN authentication
 * 
 * @package AgeEstimator
 * @since 2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AgeEstimatorSettingsPinProtection {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Initialize immediately rather than waiting for 'init'
        $this->init();
    }
    
    public function init() {
        // Add AJAX handlers for PIN verification
        add_action('wp_ajax_age_estimator_verify_settings_pin', array($this, 'verify_settings_pin'));
        add_action('wp_ajax_age_estimator_check_pin_session', array($this, 'check_pin_session'));
        add_action('wp_ajax_age_estimator_lock_settings', array($this, 'lock_settings'));
        
        // Enqueue scripts for PIN protection
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Hook into the enhanced settings shortcode to add PIN protection
        add_filter('age_estimator_render_enhanced_settings', array($this, 'maybe_show_pin_form'), 10, 2);
    }
    
    /**
     * Enqueue PIN protection scripts
     * MODIFIED: Scripts disabled - PIN protection bypassed
     */
    public function enqueue_scripts() {
        // PIN protection scripts disabled
        // No scripts or styles will be loaded
        return;
        
        /* ORIGINAL SCRIPT LOADING CODE - COMMENTED OUT
        global $post;
        
        // Only load on pages with the enhanced settings shortcode
        if (is_a($post, 'WP_Post') && 
            (has_shortcode($post->post_content, 'age_estimator_settings_enhanced') || 
             has_shortcode($post->post_content, 'age_estimator_user_settings'))) {
            
            wp_enqueue_style(
                'age-estimator-pin-protection',
                AGE_ESTIMATOR_URL . 'css/pin-protection.css',
                array(),
                AGE_ESTIMATOR_VERSION
            );
            
            wp_enqueue_script(
                'age-estimator-pin-protection',
                AGE_ESTIMATOR_URL . 'js/pin-protection.js',
                array('jquery'),
                AGE_ESTIMATOR_VERSION,
                true
            );
            
            wp_localize_script('age-estimator-pin-protection', 'ageEstimatorPinProtection', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('age_estimator_pin_protection'),
                'messages' => array(
                    'pinRequired' => __('Please enter your 4-digit PIN', 'age-estimator'),
                    'invalidPin' => __('Invalid PIN. Please try again.', 'age-estimator'),
                    'pinNotSet' => __('No PIN has been set. Please set up a PIN in the retail settings first.', 'age-estimator'),
                    'sessionExpired' => __('Your session has expired. Please enter your PIN again.', 'age-estimator'),
                    'locked' => __('Settings have been locked successfully.', 'age-estimator'),
                    'errorGeneric' => __('An error occurred. Please try again.', 'age-estimator')
                ),
                'sessionTimeout' => 15 * 60 * 1000, // 15 minutes in milliseconds
                'isLoggedIn' => is_user_logged_in()
            ));
        }
        */
    }
    
    /**
     * Check if user has a PIN set
     */
    private function has_pin_set($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
        return !empty($pin);
    }
    
    /**
     * Check if PIN session is valid
     */
    private function is_pin_session_valid($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $session_time = get_user_meta($user_id, 'age_estimator_pin_session_time', true);
        $session_timeout = 15 * 60; // 15 minutes
        
        if (empty($session_time)) {
            return false;
        }
        
        return (time() - intval($session_time)) < $session_timeout;
    }
    
    /**
     * Set PIN session
     */
    private function set_pin_session($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        update_user_meta($user_id, 'age_estimator_pin_session_time', time());
    }
    
    /**
     * Clear PIN session
     */
    private function clear_pin_session($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        delete_user_meta($user_id, 'age_estimator_pin_session_time');
    }
    
    /**
     * Render PIN entry form
     */
    public function render_pin_form($message = '', $error = false) {
        $user = wp_get_current_user();
        
        ob_start();
        ?>
        <div class="age-estimator-pin-protection">
            <div class="pin-container">
                <div class="pin-header">
                    <div class="pin-icon">🔒</div>
                    <h2><?php _e('Settings Access Protection', 'age-estimator'); ?></h2>
                    <p><?php _e('Please enter your PIN to access the settings', 'age-estimator'); ?></p>
                </div>
                
                <?php if (!empty($message)): ?>
                <div class="pin-message <?php echo $error ? 'error' : 'success'; ?>">
                    <?php echo esc_html($message); ?>
                </div>
                <?php endif; ?>
                
                <div class="pin-form-wrapper">
                    <form id="pin-access-form" class="pin-form">
                        <div class="user-info">
                            <div class="user-avatar">
                                <?php echo get_avatar(get_current_user_id(), 40); ?>
                            </div>
                            <div class="user-details">
                                <div class="user-name"><?php echo esc_html($user->display_name); ?></div>
                                <div class="user-email"><?php echo esc_html($user->user_email); ?></div>
                            </div>
                        </div>
                        
                        <div class="pin-input-group">
                            <label for="settings-pin"><?php _e('Enter 4-digit PIN:', 'age-estimator'); ?></label>
                            <input type="password" 
                                   id="settings-pin" 
                                   name="pin" 
                                   maxlength="4" 
                                   pattern="\d{4}" 
                                   placeholder="****"
                                   class="pin-input"
                                   autocomplete="off"
                                   required>
                        </div>
                        
                        <div class="pin-actions">
                            <button type="submit" class="btn btn-primary pin-submit">
                                <span class="btn-text"><?php _e('Access Settings', 'age-estimator'); ?></span>
                                <span class="btn-loading" style="display: none;">
                                    <span class="spinner"></span>
                                    <?php _e('Verifying...', 'age-estimator'); ?>
                                </span>
                            </button>
                        </div>
                        
                        <div class="pin-help">
                            <p>
                                <?php _e('Your PIN is the same one you set in the retail settings.', 'age-estimator'); ?>
                                <br>
                                <?php _e('If you haven\'t set a PIN yet, you\'ll need to set one in the retail section after accessing the settings.', 'age-estimator'); ?>
                            </p>
                        </div>
                        
                        <?php wp_nonce_field('age_estimator_pin_protection', 'pin_nonce'); ?>
                    </form>
                </div>
                
                <div class="pin-footer">
                    <div class="security-note">
                        <small>
                            🔐 <?php _e('For security, your session will expire after 15 minutes of inactivity.', 'age-estimator'); ?>
                        </small>
                    </div>
                    
                    <div class="pin-logout">
                        <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="logout-link">
                            <?php _e('Logout', 'age-estimator'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render PIN setup form for users without a PIN
     */
    public function render_pin_setup_form() {
        ob_start();
        ?>
        <div class="age-estimator-pin-setup">
            <div class="pin-setup-container">
                <div class="pin-setup-header">
                    <div class="setup-icon">🔐</div>
                    <h2><?php _e('PIN Setup Required', 'age-estimator'); ?></h2>
                    <p><?php _e('You need to set up a 4-digit PIN to protect your settings access', 'age-estimator'); ?></p>
                </div>
                
                <div class="pin-setup-content">
                    <div class="setup-notice">
                        <div class="notice-icon">⚠️</div>
                        <div class="notice-text">
                            <h3><?php _e('No PIN Configured', 'age-estimator'); ?></h3>
                            <p><?php _e('To access the settings panel, you first need to set up a 4-digit PIN in the retail settings section.', 'age-estimator'); ?></p>
                        </div>
                    </div>
                    
                    <div class="setup-steps">
                        <h4><?php _e('How to set up your PIN:', 'age-estimator'); ?></h4>
                        <ol>
                            <li><?php _e('Use the temporary access below to reach the settings', 'age-estimator'); ?></li>
                            <li><?php _e('Navigate to the "Retail Mode" section', 'age-estimator'); ?></li>
                            <li><?php _e('Set your 4-digit PIN in the "Staff PIN" field', 'age-estimator'); ?></li>
                            <li><?php _e('Save your settings', 'age-estimator'); ?></li>
                            <li><?php _e('Next time you visit, you\'ll need to enter this PIN', 'age-estimator'); ?></li>
                        </ol>
                    </div>
                    
                    <div class="setup-actions">
                        <button id="temporary-access-btn" class="btn btn-primary">
                            <span class="btn-icon">🔓</span>
                            <?php _e('One-Time Access to Set PIN', 'age-estimator'); ?>
                        </button>
                    </div>
                    
                    <div class="setup-warning">
                        <div class="warning-icon">🛡️</div>
                        <div class="warning-text">
                            <strong><?php _e('Important:', 'age-estimator'); ?></strong>
                            <?php _e('After you set your PIN, you will need to enter it every time you want to access these settings. Make sure to remember it!', 'age-estimator'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler to verify PIN
     */
    public function verify_settings_pin() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_pin_protection')) {
            wp_send_json_error(array(
                'message' => __('Security verification failed', 'age-estimator')
            ));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in', 'age-estimator')
            ));
        }
        
        $user_id = get_current_user_id();
        $entered_pin = sanitize_text_field($_POST['pin'] ?? '');
        
        // Validate PIN format
        if (!preg_match('/^\d{4}$/', $entered_pin)) {
            wp_send_json_error(array(
                'message' => __('PIN must be exactly 4 digits', 'age-estimator')
            ));
        }
        
        // Get stored PIN
        $stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
        
        // Check if PIN is set
        if (empty($stored_pin)) {
            wp_send_json_error(array(
                'message' => __('No PIN has been set. Please set up a PIN first.', 'age-estimator'),
                'no_pin' => true
            ));
        }
        
        // Verify PIN
        if (wp_check_password($entered_pin, $stored_pin)) {
            // Set session
            $this->set_pin_session($user_id);
            
            wp_send_json_success(array(
                'message' => __('PIN verified successfully', 'age-estimator'),
                'redirect' => true
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Invalid PIN. Please try again.', 'age-estimator')
            ));
        }
    }
    
    /**
     * AJAX handler to check PIN session
     */
    public function check_pin_session() {
        if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_pin_protection')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $valid = $this->is_pin_session_valid($user_id);
        
        wp_send_json_success(array(
            'valid' => $valid,
            'has_pin' => $this->has_pin_set($user_id)
        ));
    }
    
    /**
     * AJAX handler to lock settings (clear session)
     */
    public function lock_settings() {
        if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_pin_protection')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $this->clear_pin_session($user_id);
        
        wp_send_json_success(array(
            'message' => __('Settings locked successfully', 'age-estimator')
        ));
    }
    
    /**
     * Hook into the enhanced settings shortcode rendering
     * MODIFIED: PIN protection bypassed - direct access to settings
     */
    public function maybe_show_pin_form($content, $atts) {
        // PIN protection bypassed - return content directly
        // This allows direct access to settings without PIN requirement
        return $content;
        
        /* ORIGINAL PIN PROTECTION CODE - COMMENTED OUT
        // Only protect if user is logged in
        if (!is_user_logged_in()) {
            return $content; // Let the original login form show
        }
        
        $user_id = get_current_user_id();
        
        // Check if PIN is set
        if (!$this->has_pin_set($user_id)) {
            // Show PIN setup form with temporary access
            return $this->render_pin_setup_form();
        }
        
        // Check if PIN session is valid
        if (!$this->is_pin_session_valid($user_id)) {
            // Show PIN entry form
            return $this->render_pin_form();
        }
        
        // PIN session is valid, show the settings with a lock button
        return $this->add_lock_button($content);
        */
    }
    
    /**
     * Add lock button to the settings interface
     */
    private function add_lock_button($content) {
        $lock_button = '
        <div class="settings-security-bar">
            <div class="security-status">
                <span class="security-icon">🔓</span>
                <span class="security-text">' . __('Settings Unlocked', 'age-estimator') . '</span>
            </div>
            <button id="lock-settings-btn" class="btn btn-outline btn-sm">
                <span class="btn-icon">🔒</span>
                ' . __('Lock Settings', 'age-estimator') . '
            </button>
        </div>';
        
        // Insert the lock button after the settings header
        $content = preg_replace(
            '/(<div class="settings-header">.*?<\/div>)/s',
            '$1' . $lock_button,
            $content
        );
        
        return $content;
    }
}

// PIN protection is now initialized directly in the main plugin file
