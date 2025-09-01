<?php
/**
 * User PIN Management for WordPress Admin
 * Allows administrators to manage user PINs from user profiles
 * 
 * @package AgeEstimator
 * @since 2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AgeEstimatorUserPinManager {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Initialize immediately since we're already in admin context
        $this->init();
    }
    
    public function init() {
        // Add PIN management fields to user profiles
        add_action('show_user_profile', array($this, 'add_pin_fields'));
        add_action('edit_user_profile', array($this, 'add_pin_fields'));
        
        // Save PIN data
        add_action('personal_options_update', array($this, 'save_pin_fields'));
        add_action('edit_user_profile_update', array($this, 'save_pin_fields'));
        
        // Add AJAX handlers for PIN management
        add_action('wp_ajax_age_estimator_reset_user_pin', array($this, 'reset_user_pin'));
        add_action('wp_ajax_age_estimator_set_user_pin', array($this, 'set_user_pin'));
        add_action('wp_ajax_age_estimator_clear_user_session', array($this, 'clear_user_session'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add PIN management to users list table
        add_filter('manage_users_columns', array($this, 'add_pin_column'));
        add_filter('manage_users_custom_column', array($this, 'display_pin_column'), 10, 3);
        add_filter('manage_users_sortable_columns', array($this, 'make_pin_column_sortable'));
        
        // Add bulk actions for PIN management
        add_filter('bulk_actions-users', array($this, 'add_bulk_actions'));
        add_filter('handle_bulk_actions-users', array($this, 'handle_bulk_actions'), 10, 3);
        
        // Add admin notices
        add_action('admin_notices', array($this, 'show_admin_notices'));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on user profile and users list pages
        if (!in_array($hook, ['profile.php', 'user-edit.php', 'users.php'])) {
            return;
        }
        
        wp_enqueue_style(
            'age-estimator-user-pin-admin',
            AGE_ESTIMATOR_URL . 'css/admin-user-pin.css',
            array(),
            AGE_ESTIMATOR_VERSION
        );
        
        wp_enqueue_script(
            'age-estimator-user-pin-admin',
            AGE_ESTIMATOR_URL . 'js/admin-user-pin.js',
            array('jquery'),
            AGE_ESTIMATOR_VERSION,
            true
        );
        
        wp_localize_script('age-estimator-user-pin-admin', 'ageEstimatorUserPin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('age_estimator_user_pin_admin'),
            'messages' => array(
                'confirmReset' => __('Are you sure you want to reset this user\'s PIN? They will need to set a new one.', 'age-estimator'),
                'confirmClearSession' => __('Are you sure you want to clear this user\'s PIN session? They will need to re-enter their PIN.', 'age-estimator'),
                'pinRequired' => __('Please enter a 4-digit PIN', 'age-estimator'),
                'pinInvalid' => __('PIN must be exactly 4 digits', 'age-estimator'),
                'success' => __('Operation completed successfully', 'age-estimator'),
                'error' => __('An error occurred. Please try again.', 'age-estimator'),
            )
        ));
    }
    
    /**
     * Add PIN management fields to user profile
     */
    public function add_pin_fields($user) {
        // Only show to administrators
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $user_pin = get_user_meta($user->ID, 'age_estimator_retail_pin', true);
        $pin_set = !empty($user_pin);
        $session_time = get_user_meta($user->ID, 'age_estimator_pin_session_time', true);
        $session_active = false;
        
        if (!empty($session_time)) {
            $session_timeout = 15 * 60; // 15 minutes
            $session_active = (time() - intval($session_time)) < $session_timeout;
        }
        
        // Get PIN usage statistics
        $pin_stats = $this->get_user_pin_stats($user->ID);
        
        ?>
        <h2 id="age-estimator-pin-management"><?php _e('Age Estimator PIN Management', 'age-estimator'); ?></h2>
        
        <div class="age-estimator-pin-admin-section">
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php _e('PIN Status', 'age-estimator'); ?></th>
                    <td>
                        <div class="pin-status-display">
                            <?php if ($pin_set): ?>
                                <span class="pin-status pin-set">
                                    <span class="status-icon">🔐</span>
                                    <strong><?php _e('PIN is set', 'age-estimator'); ?></strong>
                                </span>
                                <div class="pin-details">
                                    <div class="pin-detail">
                                        <strong><?php _e('Last Set:', 'age-estimator'); ?></strong>
                                        <?php 
                                        $pin_set_time = get_user_meta($user->ID, 'age_estimator_pin_set_time', true);
                                        if ($pin_set_time) {
                                            echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $pin_set_time);
                                        } else {
                                            _e('Unknown', 'age-estimator');
                                        }
                                        ?>
                                    </div>
                                    <div class="pin-detail">
                                        <strong><?php _e('Session Status:', 'age-estimator'); ?></strong>
                                        <?php if ($session_active): ?>
                                            <span class="session-active">
                                                🟢 <?php _e('Active', 'age-estimator'); ?>
                                                <small>(<?php echo $this->get_session_time_remaining($session_time); ?>)</small>
                                            </span>
                                        <?php else: ?>
                                            <span class="session-inactive">
                                                🔴 <?php _e('Expired/Inactive', 'age-estimator'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="pin-status pin-not-set">
                                    <span class="status-icon">🔓</span>
                                    <strong><?php _e('No PIN set', 'age-estimator'); ?></strong>
                                </span>
                                <p class="description">
                                    <?php _e('This user has not set up a PIN yet. They will be prompted to create one when accessing protected settings.', 'age-estimator'); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                
                <?php if ($pin_set): ?>
                <tr>
                    <th scope="row"><?php _e('PIN Actions', 'age-estimator'); ?></th>
                    <td>
                        <div class="pin-actions">
                            <button type="button" class="button button-secondary" id="reset-user-pin" data-user-id="<?php echo $user->ID; ?>">
                                <span class="dashicons dashicons-update"></span>
                                <?php _e('Reset PIN', 'age-estimator'); ?>
                            </button>
                            
                            <?php if ($session_active): ?>
                            <button type="button" class="button button-secondary" id="clear-user-session" data-user-id="<?php echo $user->ID; ?>">
                                <span class="dashicons dashicons-lock"></span>
                                <?php _e('Clear Session', 'age-estimator'); ?>
                            </button>
                            <?php endif; ?>
                        </div>
                        <p class="description">
                            <strong><?php _e('Reset PIN:', 'age-estimator'); ?></strong> <?php _e('Removes the current PIN. User will need to set a new one.', 'age-estimator'); ?><br>
                            <?php if ($session_active): ?>
                            <strong><?php _e('Clear Session:', 'age-estimator'); ?></strong> <?php _e('Forces the user to re-enter their PIN on next access.', 'age-estimator'); ?>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <?php endif; ?>
                
                <tr>
                    <th scope="row"><?php _e('Set New PIN', 'age-estimator'); ?></th>
                    <td>
                        <div class="set-pin-section">
                            <div class="pin-input-group">
                                <input type="password" 
                                       id="new-user-pin" 
                                       placeholder="<?php _e('Enter 4-digit PIN', 'age-estimator'); ?>" 
                                       maxlength="4" 
                                       pattern="\\d{4}"
                                       class="small-text">
                                <button type="button" class="button button-primary" id="set-user-pin" data-user-id="<?php echo $user->ID; ?>">
                                    <span class="dashicons dashicons-admin-network"></span>
                                    <?php _e('Set PIN', 'age-estimator'); ?>
                                </button>
                            </div>
                            <p class="description">
                                <?php _e('Set a new 4-digit PIN for this user. This will override any existing PIN.', 'age-estimator'); ?>
                            </p>
                        </div>
                    </td>
                </tr>
                
                <?php if (!empty($pin_stats)): ?>
                <tr>
                    <th scope="row"><?php _e('Usage Statistics', 'age-estimator'); ?></th>
                    <td>
                        <div class="pin-stats">
                            <?php if (isset($pin_stats['last_access'])): ?>
                            <div class="stat-item">
                                <strong><?php _e('Last Access:', 'age-estimator'); ?></strong>
                                <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $pin_stats['last_access']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($pin_stats['access_count'])): ?>
                            <div class="stat-item">
                                <strong><?php _e('Total Accesses:', 'age-estimator'); ?></strong>
                                <?php echo number_format($pin_stats['access_count']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <div id="pin-admin-messages" class="notice" style="display: none;">
            <p></p>
        </div>
        <?php
    }
    
    /**
     * Get session time remaining in human readable format
     */
    private function get_session_time_remaining($session_time) {
        $session_timeout = 15 * 60; // 15 minutes
        $time_passed = time() - intval($session_time);
        $time_remaining = $session_timeout - $time_passed;
        
        if ($time_remaining <= 0) {
            return __('Expired', 'age-estimator');
        }
        
        $minutes = floor($time_remaining / 60);
        $seconds = $time_remaining % 60;
        
        return sprintf(__('%d min %d sec remaining', 'age-estimator'), $minutes, $seconds);
    }
    
    /**
     * Get user PIN usage statistics
     */
    private function get_user_pin_stats($user_id) {
        $stats = array();
        
        // Get last access time (from session time if active)
        $session_time = get_user_meta($user_id, 'age_estimator_pin_session_time', true);
        if (!empty($session_time)) {
            $stats['last_access'] = intval($session_time);
        }
        
        // Get access count (you could implement this with a counter)
        $access_count = get_user_meta($user_id, 'age_estimator_pin_access_count', true);
        if (!empty($access_count)) {
            $stats['access_count'] = intval($access_count);
        }
        
        return $stats;
    }
    
    /**
     * Save PIN fields (this is called when profile is saved, but we handle PIN changes via AJAX)
     */
    public function save_pin_fields($user_id) {
        // We handle PIN changes via AJAX, so this is mainly for other fields if needed
    }
    
    /**
     * AJAX handler to reset user PIN
     */
    public function reset_user_pin() {
        // Check permissions and nonce
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'age_estimator_user_pin_admin')) {
            wp_send_json_error(array('message' => __('Permission denied', 'age-estimator')));
        }
        
        $user_id = intval($_POST['user_id']);
        if (!$user_id || !get_userdata($user_id)) {
            wp_send_json_error(array('message' => __('Invalid user ID', 'age-estimator')));
        }
        
        // Remove PIN and clear session
        delete_user_meta($user_id, 'age_estimator_retail_pin');
        delete_user_meta($user_id, 'age_estimator_pin_session_time');
        delete_user_meta($user_id, 'age_estimator_pin_set_time');
        
        // Log the action
        $this->log_pin_action($user_id, 'reset', get_current_user_id());
        
        wp_send_json_success(array(
            'message' => __('PIN reset successfully. User will need to set a new PIN.', 'age-estimator'),
            'action' => 'reset'
        ));
    }
    
    /**
     * AJAX handler to set user PIN
     */
    public function set_user_pin() {
        // Check permissions and nonce
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'age_estimator_user_pin_admin')) {
            wp_send_json_error(array('message' => __('Permission denied', 'age-estimator')));
        }
        
        $user_id = intval($_POST['user_id']);
        $new_pin = sanitize_text_field($_POST['pin']);
        
        if (!$user_id || !get_userdata($user_id)) {
            wp_send_json_error(array('message' => __('Invalid user ID', 'age-estimator')));
        }
        
        // Validate PIN
        if (!preg_match('/^\\d{4}$/', $new_pin)) {
            wp_send_json_error(array('message' => __('PIN must be exactly 4 digits', 'age-estimator')));
        }
        
        // Hash and store the PIN
        $hashed_pin = wp_hash_password($new_pin);
        update_user_meta($user_id, 'age_estimator_retail_pin', $hashed_pin);
        update_user_meta($user_id, 'age_estimator_pin_set_time', time());
        
        // Clear any existing session to force re-authentication
        delete_user_meta($user_id, 'age_estimator_pin_session_time');
        
        // Log the action
        $this->log_pin_action($user_id, 'set', get_current_user_id());
        
        wp_send_json_success(array(
            'message' => __('PIN set successfully. User will need to enter this PIN to access settings.', 'age-estimator'),
            'action' => 'set'
        ));
    }
    
    /**
     * AJAX handler to clear user session
     */
    public function clear_user_session() {
        // Check permissions and nonce
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'age_estimator_user_pin_admin')) {
            wp_send_json_error(array('message' => __('Permission denied', 'age-estimator')));
        }
        
        $user_id = intval($_POST['user_id']);
        if (!$user_id || !get_userdata($user_id)) {
            wp_send_json_error(array('message' => __('Invalid user ID', 'age-estimator')));
        }
        
        // Clear session
        delete_user_meta($user_id, 'age_estimator_pin_session_time');
        
        // Log the action
        $this->log_pin_action($user_id, 'clear_session', get_current_user_id());
        
        wp_send_json_success(array(
            'message' => __('Session cleared. User will need to re-enter PIN on next access.', 'age-estimator'),
            'action' => 'clear_session'
        ));
    }
    
    /**
     * Log PIN management actions
     */
    private function log_pin_action($user_id, $action, $admin_id) {
        $user = get_userdata($user_id);
        $admin = get_userdata($admin_id);
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'action' => $action,
            'user_id' => $user_id,
            'user_login' => $user ? $user->user_login : 'unknown',
            'admin_id' => $admin_id,
            'admin_login' => $admin ? $admin->user_login : 'unknown',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        );
        
        // Store in options table (you could also create a custom table for this)
        $existing_logs = get_option('age_estimator_pin_admin_logs', array());
        $existing_logs[] = $log_entry;
        
        // Keep only last 100 entries
        if (count($existing_logs) > 100) {
            $existing_logs = array_slice($existing_logs, -100);
        }
        
        update_option('age_estimator_pin_admin_logs', $existing_logs);
    }
    
    /**
     * Add PIN column to users list table
     */
    public function add_pin_column($columns) {
        if (current_user_can('manage_options')) {
            $columns['age_estimator_pin'] = __('PIN Status', 'age-estimator');
        }
        return $columns;
    }
    
    /**
     * Display PIN column content
     */
    public function display_pin_column($value, $column_name, $user_id) {
        if ($column_name === 'age_estimator_pin' && current_user_can('manage_options')) {
            $user_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
            $session_time = get_user_meta($user_id, 'age_estimator_pin_session_time', true);
            
            if (!empty($user_pin)) {
                $session_active = false;
                if (!empty($session_time)) {
                    $session_timeout = 15 * 60;
                    $session_active = (time() - intval($session_time)) < $session_timeout;
                }
                
                $status_class = $session_active ? 'pin-active' : 'pin-set';
                $status_text = $session_active ? __('Active', 'age-estimator') : __('Set', 'age-estimator');
                $status_icon = $session_active ? '🟢' : '🔐';
                
                return sprintf(
                    '<span class="pin-status-badge %s">%s %s</span>',
                    esc_attr($status_class),
                    $status_icon,
                    esc_html($status_text)
                );
            } else {
                return '<span class="pin-status-badge pin-not-set">🔓 ' . __('Not Set', 'age-estimator') . '</span>';
            }
        }
        return $value;
    }
    
    /**
     * Make PIN column sortable
     */
    public function make_pin_column_sortable($columns) {
        if (current_user_can('manage_options')) {
            $columns['age_estimator_pin'] = 'age_estimator_pin';
        }
        return $columns;
    }
    
    /**
     * Add bulk actions for PIN management
     */
    public function add_bulk_actions($actions) {
        if (current_user_can('manage_options')) {
            $actions['age_estimator_reset_pins'] = __('Reset PINs', 'age-estimator');
            $actions['age_estimator_clear_sessions'] = __('Clear PIN Sessions', 'age-estimator');
        }
        return $actions;
    }
    
    /**
     * Handle bulk actions
     */
    public function handle_bulk_actions($redirect_to, $action, $user_ids) {
        if (!current_user_can('manage_options')) {
            return $redirect_to;
        }
        
        $count = 0;
        
        if ($action === 'age_estimator_reset_pins') {
            foreach ($user_ids as $user_id) {
                delete_user_meta($user_id, 'age_estimator_retail_pin');
                delete_user_meta($user_id, 'age_estimator_pin_session_time');
                delete_user_meta($user_id, 'age_estimator_pin_set_time');
                $this->log_pin_action($user_id, 'bulk_reset', get_current_user_id());
                $count++;
            }
            $redirect_to = add_query_arg('age_estimator_pins_reset', $count, $redirect_to);
        } elseif ($action === 'age_estimator_clear_sessions') {
            foreach ($user_ids as $user_id) {
                delete_user_meta($user_id, 'age_estimator_pin_session_time');
                $this->log_pin_action($user_id, 'bulk_clear_session', get_current_user_id());
                $count++;
            }
            $redirect_to = add_query_arg('age_estimator_sessions_cleared', $count, $redirect_to);
        }
        
        return $redirect_to;
    }
    
    /**
     * Show admin notices for bulk actions
     */
    public function show_admin_notices() {
        if (isset($_GET['age_estimator_pins_reset'])) {
            $count = intval($_GET['age_estimator_pins_reset']);
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                sprintf(_n('%d user PIN reset.', '%d user PINs reset.', $count, 'age-estimator'), $count)
            );
        }
        
        if (isset($_GET['age_estimator_sessions_cleared'])) {
            $count = intval($_GET['age_estimator_sessions_cleared']);
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                sprintf(_n('%d user session cleared.', '%d user sessions cleared.', $count, 'age-estimator'), $count)
            );
        }
    }
}

// Initialize the user PIN manager for admin areas only
if (is_admin()) {
    add_action('admin_init', function() {
        AgeEstimatorUserPinManager::get_instance();
    }, 10);
}
