<?php
/**
 * User Email Preferences for Age Estimator
 * 
 * Allows users to manage their compliance email preferences
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AgeEstimatorUserEmailPreferences {
    
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
        // Add user profile fields
        add_action('show_user_profile', array($this, 'add_user_profile_fields'));
        add_action('edit_user_profile', array($this, 'add_user_profile_fields'));
        
        // Save user profile fields
        add_action('personal_options_update', array($this, 'save_user_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_profile_fields'));
        
        // Add shortcode for email preferences form
        add_shortcode('age_estimator_email_preferences', array($this, 'render_preferences_form'));
        
        // Handle AJAX preference updates
        add_action('wp_ajax_age_estimator_update_email_prefs', array($this, 'ajax_update_preferences'));
        add_action('wp_ajax_nopriv_age_estimator_update_email_prefs', array($this, 'ajax_update_preferences'));
    }
    
    /**
     * Add fields to user profile
     */
    public function add_user_profile_fields($user) {
        $opt_out = get_user_meta($user->ID, 'age_estimator_compliance_emails_opt_out', true);
        $frequency = get_user_meta($user->ID, 'age_estimator_email_frequency', true);
        
        if (!$frequency) {
            $frequency = 'daily';
        }
        ?>
        <h3><?php _e('Age Verification Compliance Reports', 'age-estimator'); ?></h3>
        <table class="form-table">
            <tr>
                <th>
                    <label for="age_estimator_compliance_emails">
                        <?php _e('Receive Compliance Reports', 'age-estimator'); ?>
                    </label>
                </th>
                <td>
                    <input type="checkbox" name="age_estimator_compliance_emails" id="age_estimator_compliance_emails" 
                           value="yes" <?php checked($opt_out !== 'yes'); ?> />
                    <label for="age_estimator_compliance_emails">
                        <?php _e('Send me email reports of my age verification activity', 'age-estimator'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="age_estimator_email_frequency">
                        <?php _e('Report Frequency', 'age-estimator'); ?>
                    </label>
                </th>
                <td>
                    <select name="age_estimator_email_frequency" id="age_estimator_email_frequency">
                        <option value="daily" <?php selected($frequency, 'daily'); ?>>
                            <?php _e('Daily', 'age-estimator'); ?>
                        </option>
                        <option value="weekly" <?php selected($frequency, 'weekly'); ?>>
                            <?php _e('Weekly (Mondays)', 'age-estimator'); ?>
                        </option>
                        <option value="monthly" <?php selected($frequency, 'monthly'); ?>>
                            <?php _e('Monthly (1st of month)', 'age-estimator'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('How often you would like to receive compliance reports.', 'age-estimator'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <script>
        jQuery(document).ready(function($) {
            $('#age_estimator_compliance_emails').change(function() {
                if ($(this).is(':checked')) {
                    $('#age_estimator_email_frequency').prop('disabled', false);
                } else {
                    $('#age_estimator_email_frequency').prop('disabled', true);
                }
            }).trigger('change');
        });
        </script>
        <?php
    }
    
    /**
     * Save user profile fields
     */
    public function save_user_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        
        $opt_out = isset($_POST['age_estimator_compliance_emails']) ? 'no' : 'yes';
        update_user_meta($user_id, 'age_estimator_compliance_emails_opt_out', $opt_out);
        
        if (isset($_POST['age_estimator_email_frequency'])) {
            update_user_meta($user_id, 'age_estimator_email_frequency', sanitize_text_field($_POST['age_estimator_email_frequency']));
        }
    }
    
    /**
     * Render preferences form shortcode
     */
    public function render_preferences_form($atts = array()) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to manage your email preferences.', 'age-estimator') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $opt_out = get_user_meta($user_id, 'age_estimator_compliance_emails_opt_out', true);
        $frequency = get_user_meta($user_id, 'age_estimator_email_frequency', true);
        
        if (!$frequency) {
            $frequency = 'daily';
        }
        
        ob_start();
        ?>
        <div class="age-estimator-email-preferences">
            <h3><?php _e('Email Preferences', 'age-estimator'); ?></h3>
            <form id="age-estimator-email-prefs-form" method="post">
                <p>
                    <label>
                        <input type="checkbox" name="receive_emails" id="receive_emails" 
                               value="yes" <?php checked($opt_out !== 'yes'); ?> />
                        <?php _e('Send me compliance report emails', 'age-estimator'); ?>
                    </label>
                </p>
                
                <p>
                    <label for="email_frequency"><?php _e('Frequency:', 'age-estimator'); ?></label>
                    <select name="email_frequency" id="email_frequency">
                        <option value="daily" <?php selected($frequency, 'daily'); ?>>
                            <?php _e('Daily', 'age-estimator'); ?>
                        </option>
                        <option value="weekly" <?php selected($frequency, 'weekly'); ?>>
                            <?php _e('Weekly', 'age-estimator'); ?>
                        </option>
                        <option value="monthly" <?php selected($frequency, 'monthly'); ?>>
                            <?php _e('Monthly', 'age-estimator'); ?>
                        </option>
                    </select>
                </p>
                
                <p>
                    <button type="submit" class="button button-primary">
                        <?php _e('Save Preferences', 'age-estimator'); ?>
                    </button>
                    <span class="age-estimator-prefs-message" style="display:none;"></span>
                </p>
                
                <?php wp_nonce_field('age_estimator_email_prefs', 'email_prefs_nonce'); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#age-estimator-email-prefs-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $message = $('.age-estimator-prefs-message');
                var $button = $form.find('button[type="submit"]');
                
                $button.prop('disabled', true);
                $message.hide();
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'age_estimator_update_email_prefs',
                        receive_emails: $('#receive_emails').is(':checked') ? 'yes' : 'no',
                        frequency: $('#email_frequency').val(),
                        nonce: $('#email_prefs_nonce').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $message.text(response.data.message).css('color', 'green').show();
                        } else {
                            $message.text(response.data.message).css('color', 'red').show();
                        }
                    },
                    error: function() {
                        $message.text('<?php _e('An error occurred. Please try again.', 'age-estimator'); ?>').css('color', 'red').show();
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                    }
                });
            });
            
            $('#receive_emails').change(function() {
                $('#email_frequency').prop('disabled', !$(this).is(':checked'));
            }).trigger('change');
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Handle AJAX preference updates
     */
    public function ajax_update_preferences() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'age_estimator_email_prefs')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'age-estimator')));
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'age-estimator')));
        }
        
        $user_id = get_current_user_id();
        $receive_emails = $_POST['receive_emails'] === 'yes';
        $frequency = sanitize_text_field($_POST['frequency']);
        
        // Update user meta
        update_user_meta($user_id, 'age_estimator_compliance_emails_opt_out', $receive_emails ? 'no' : 'yes');
        update_user_meta($user_id, 'age_estimator_email_frequency', $frequency);
        
        wp_send_json_success(array(
            'message' => __('Your preferences have been saved.', 'age-estimator')
        ));
    }
}

// Initialize the preferences handler
AgeEstimatorUserEmailPreferences::get_instance();
