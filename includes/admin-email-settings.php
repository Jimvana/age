<?php
/**
 * Admin Email Settings Page for Age Estimator
 * 
 * Provides interface for managing compliance email settings
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AgeEstimatorAdminEmailSettings {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
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
    private function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add AJAX handlers
        add_action('wp_ajax_age_estimator_send_test_email', array($this, 'ajax_send_test_email'));
        add_action('wp_ajax_age_estimator_force_send_emails', array($this, 'ajax_force_send_emails'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'age-estimator-settings',
            __('Email Settings', 'age-estimator'),
            __('Email Settings', 'age-estimator'),
            'manage_options',
            'age-estimator-email-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('age_estimator_email_settings', 'age_estimator_send_compliance_emails');
        register_setting('age_estimator_email_settings', 'age_estimator_email_send_time');
        register_setting('age_estimator_email_settings', 'age_estimator_email_from_name');
        register_setting('age_estimator_email_settings', 'age_estimator_email_from_address');
        register_setting('age_estimator_email_settings', 'age_estimator_email_reply_to');
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Get email logs
        $email_logs = get_option('age_estimator_email_logs', array());
        
        // Get current scheduled time
        $next_scheduled = wp_next_scheduled('age_estimator_send_daily_compliance_emails');
        ?>
        <div class="wrap">
            <h1><?php _e('Age Estimator Email Settings', 'age-estimator'); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <?php if ($next_scheduled) : ?>
                        <?php printf(
                            __('Next email batch scheduled for: %s', 'age-estimator'),
                            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_scheduled)
                        ); ?>
                    <?php else : ?>
                        <?php _e('Email batch not scheduled. Save settings to schedule.', 'age-estimator'); ?>
                    <?php endif; ?>
                </p>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields('age_estimator_email_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="age_estimator_send_compliance_emails">
                                <?php _e('Enable Daily Emails', 'age-estimator'); ?>
                            </label>
                        </th>
                        <td>
                            <select name="age_estimator_send_compliance_emails" id="age_estimator_send_compliance_emails">
                                <option value="yes" <?php selected(get_option('age_estimator_send_compliance_emails', 'yes'), 'yes'); ?>>
                                    <?php _e('Yes', 'age-estimator'); ?>
                                </option>
                                <option value="no" <?php selected(get_option('age_estimator_send_compliance_emails', 'yes'), 'no'); ?>>
                                    <?php _e('No', 'age-estimator'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('Enable or disable automatic daily compliance email reports.', 'age-estimator'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="age_estimator_email_send_time">
                                <?php _e('Send Time', 'age-estimator'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="time" name="age_estimator_email_send_time" id="age_estimator_email_send_time" 
                                   value="<?php echo esc_attr(get_option('age_estimator_email_send_time', '23:00')); ?>" />
                            <p class="description">
                                <?php printf(
                                    __('Time to send daily emails (server time). Current server time: %s', 'age-estimator'),
                                    current_time('H:i')
                                ); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="age_estimator_email_from_name">
                                <?php _e('From Name', 'age-estimator'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" name="age_estimator_email_from_name" id="age_estimator_email_from_name" 
                                   value="<?php echo esc_attr(get_option('age_estimator_email_from_name', get_bloginfo('name'))); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                <?php _e('The name that will appear in the "From" field of compliance emails.', 'age-estimator'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="age_estimator_email_from_address">
                                <?php _e('From Email', 'age-estimator'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="email" name="age_estimator_email_from_address" id="age_estimator_email_from_address" 
                                   value="<?php echo esc_attr(get_option('age_estimator_email_from_address', get_option('admin_email'))); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                <?php _e('The email address that will appear in the "From" field.', 'age-estimator'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="age_estimator_email_reply_to">
                                <?php _e('Reply-To Email', 'age-estimator'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="email" name="age_estimator_email_reply_to" id="age_estimator_email_reply_to" 
                                   value="<?php echo esc_attr(get_option('age_estimator_email_reply_to', get_option('admin_email'))); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                <?php _e('Email address for replies (optional).', 'age-estimator'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <hr />
            
            <h2><?php _e('Email Tools', 'age-estimator'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Test Email', 'age-estimator'); ?>
                    </th>
                    <td>
                        <button type="button" class="button" id="send-test-email">
                            <?php _e('Send Test Email to Admin', 'age-estimator'); ?>
                        </button>
                        <span class="test-email-status"></span>
                        <p class="description">
                            <?php _e('Send a test compliance email to the admin email address.', 'age-estimator'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <?php _e('Force Send', 'age-estimator'); ?>
                    </th>
                    <td>
                        <button type="button" class="button" id="force-send-emails">
                            <?php _e('Force Send Today\'s Emails Now', 'age-estimator'); ?>
                        </button>
                        <span class="force-send-status"></span>
                        <p class="description">
                            <?php _e('Manually trigger the daily email batch for today.', 'age-estimator'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <hr />
            
            <h2><?php _e('Recent Email Logs', 'age-estimator'); ?></h2>
            
            <?php if (!empty($email_logs)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'age-estimator'); ?></th>
                            <th><?php _e('Emails Sent', 'age-estimator'); ?></th>
                            <th><?php _e('Sent At', 'age-estimator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($email_logs, 0, 10) as $log) : ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($log['date']))); ?></td>
                                <td><?php echo intval($log['count']); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('time_format'), strtotime($log['timestamp']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e('No email logs available yet.', 'age-estimator'); ?></p>
            <?php endif; ?>
            
            <script>
            jQuery(document).ready(function($) {
                // Send test email
                $('#send-test-email').click(function() {
                    var $button = $(this);
                    var $status = $('.test-email-status');
                    
                    $button.prop('disabled', true);
                    $status.html('<span class="spinner is-active" style="float: none;"></span>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'age_estimator_send_test_email',
                            nonce: '<?php echo wp_create_nonce('age_estimator_test_email'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $status.html('<span style="color: green;">' + response.data.message + '</span>');
                            } else {
                                $status.html('<span style="color: red;">' + response.data.message + '</span>');
                            }
                        },
                        error: function() {
                            $status.html('<span style="color: red;">Error sending test email.</span>');
                        },
                        complete: function() {
                            $button.prop('disabled', false);
                        }
                    });
                });
                
                // Force send emails
                $('#force-send-emails').click(function() {
                    if (!confirm('<?php _e('Are you sure you want to send all daily emails now?', 'age-estimator'); ?>')) {
                        return;
                    }
                    
                    var $button = $(this);
                    var $status = $('.force-send-status');
                    
                    $button.prop('disabled', true);
                    $status.html('<span class="spinner is-active" style="float: none;"></span>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'age_estimator_force_send_emails',
                            nonce: '<?php echo wp_create_nonce('age_estimator_force_send'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $status.html('<span style="color: green;">' + response.data.message + '</span>');
                                // Reload page after 2 seconds to show updated logs
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            } else {
                                $status.html('<span style="color: red;">' + response.data.message + '</span>');
                            }
                        },
                        error: function() {
                            $status.html('<span style="color: red;">Error sending emails.</span>');
                        },
                        complete: function() {
                            $button.prop('disabled', false);
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for sending test email
     */
    public function ajax_send_test_email() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'age-estimator')));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_test_email')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'age-estimator')));
        }
        
        $emailer = AgeEstimatorComplianceEmailer::get_instance();
        $admin_user_id = get_current_user_id();
        
        $sent = $emailer->send_test_email($admin_user_id);
        
        if ($sent) {
            wp_send_json_success(array('message' => __('Test email sent successfully!', 'age-estimator')));
        } else {
            wp_send_json_error(array('message' => __('Failed to send test email.', 'age-estimator')));
        }
    }
    
    /**
     * AJAX handler for forcing email send
     */
    public function ajax_force_send_emails() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'age-estimator')));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_force_send')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'age-estimator')));
        }
        
        $emailer = AgeEstimatorComplianceEmailer::get_instance();
        $emailer->send_daily_emails();
        
        wp_send_json_success(array('message' => __('Email batch triggered successfully!', 'age-estimator')));
    }
}

// Initialize the admin settings
AgeEstimatorAdminEmailSettings::get_instance();
