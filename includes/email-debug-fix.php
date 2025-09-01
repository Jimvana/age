<?php
/**
 * Temporary fix for admin email settings to add debugging
 * 
 * Add this code to your theme's functions.php or as a mu-plugin
 */

// Remove the original AJAX handler and add our debug version
add_action('init', function() {
    // Remove original handler
    remove_action('wp_ajax_age_estimator_force_send_emails', array(AgeEstimatorAdminEmailSettings::get_instance(), 'ajax_force_send_emails'));
    
    // Add debug handler
    add_action('wp_ajax_age_estimator_force_send_emails', 'age_estimator_debug_force_send_emails');
});

function age_estimator_debug_force_send_emails() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Permission denied.', 'age-estimator')));
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'age_estimator_force_send')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'age-estimator')));
    }
    
    // Get today's date
    $today = current_time('Y-m-d');
    
    // Check if emails are enabled
    $send_emails = get_option('age_estimator_send_compliance_emails', 'yes');
    if ($send_emails !== 'yes') {
        wp_send_json_error(array('message' => 'Email sending is disabled in settings.'));
        return;
    }
    
    // Get API tracker instance
    $tracker = AgeEstimatorAPITracker::get_instance();
    
    // Get user stats for today
    $user_stats = $tracker->get_user_stats(9999, 0, 'day', $today);
    
    if (empty($user_stats)) {
        wp_send_json_error(array(
            'message' => 'No users found with API activity for today (' . $today . '). Emails are only sent to users who have used the age verification today.'
        ));
        return;
    }
    
    // Count eligible users
    $eligible_users = 0;
    $guest_users = 0;
    $no_email_users = 0;
    $opted_out_users = 0;
    
    foreach ($user_stats as $user_stat) {
        if ($user_stat['user_id'] > 0) {
            $user = get_user_by('ID', $user_stat['user_id']);
            if ($user && $user->user_email) {
                $opted_out = get_user_meta($user_stat['user_id'], 'age_estimator_compliance_emails_opt_out', true);
                if ($opted_out === 'yes') {
                    $opted_out_users++;
                } else {
                    $eligible_users++;
                }
            } else {
                $no_email_users++;
            }
        } else {
            $guest_users++;
        }
    }
    
    if ($eligible_users === 0) {
        $details = array();
        if ($guest_users > 0) $details[] = $guest_users . ' guest users (no email)';
        if ($no_email_users > 0) $details[] = $no_email_users . ' users without email';
        if ($opted_out_users > 0) $details[] = $opted_out_users . ' users opted out';
        
        wp_send_json_error(array(
            'message' => 'No eligible users found for email sending. Found: ' . implode(', ', $details)
        ));
        return;
    }
    
    // Actually send the emails
    $emailer = AgeEstimatorComplianceEmailer::get_instance();
    $emailer->send_daily_emails();
    
    // Get the latest log entry to see how many were sent
    $email_logs = get_option('age_estimator_email_logs', array());
    $latest_log = !empty($email_logs) ? $email_logs[0] : null;
    $sent_count = $latest_log && $latest_log['date'] === $today ? $latest_log['count'] : 0;
    
    wp_send_json_success(array(
        'message' => sprintf(
            'Email batch completed! Sent %d emails to eligible users (out of %d total users with activity today).',
            $sent_count,
            count($user_stats)
        )
    ));
}

// Also add a direct test function
add_action('admin_init', function() {
    if (isset($_GET['test_age_estimator_email']) && current_user_can('manage_options')) {
        $emailer = AgeEstimatorComplianceEmailer::get_instance();
        $result = $emailer->send_test_email();
        
        if ($result) {
            wp_die('Test email sent successfully! Check your email inbox.');
        } else {
            wp_die('Failed to send test email. Check your email configuration.');
        }
    }
});
