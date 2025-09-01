<?php
/**
 * Fixed Compliance Email System for Age Estimator
 * 
 * This is a modified version that properly handles email sending
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AgeEstimatorComplianceEmailerFixed {
    
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
     * Send daily emails with debugging
     */
    public function send_daily_emails_debug() {
        // Get settings
        $send_emails = get_option('age_estimator_send_compliance_emails', 'yes');
        if ($send_emails !== 'yes') {
            return array('error' => 'Email sending is disabled in settings');
        }
        
        // Get today's date
        $today = current_time('Y-m-d');
        
        // Get API tracker instance
        $tracker = AgeEstimatorAPITracker::get_instance();
        
        // Debug: Get all user stats for today
        $user_stats = $tracker->get_user_stats(9999, 0, 'day', $today);
        
        $debug_info = array(
            'date' => $today,
            'total_users_found' => count($user_stats),
            'users' => array(),
            'emails_sent' => 0,
            'emails_failed' => 0
        );
        
        // Process each user
        foreach ($user_stats as $user_stat) {
            $user_info = array(
                'user_id' => $user_stat['user_id'],
                'total_calls' => $user_stat['total_calls'],
                'email_sent' => false,
                'reason' => ''
            );
            
            if ($user_stat['user_id'] > 0) { // Registered user
                $user = get_user_by('ID', $user_stat['user_id']);
                if ($user && $user->user_email) {
                    $user_info['email'] = $user->user_email;
                    $user_info['display_name'] = $user->display_name;
                    
                    // Check if user has opted out
                    $opted_out = get_user_meta($user_stat['user_id'], 'age_estimator_compliance_emails_opt_out', true);
                    if ($opted_out === 'yes') {
                        $user_info['reason'] = 'User opted out';
                    } else {
                        // Send email
                        $sent = $this->send_user_compliance_email($user_stat['user_id'], $today, 'day');
                        $user_info['email_sent'] = $sent;
                        if ($sent) {
                            $debug_info['emails_sent']++;
                        } else {
                            $debug_info['emails_failed']++;
                            $user_info['reason'] = 'Email send failed';
                        }
                    }
                } else {
                    $user_info['reason'] = 'No email address found';
                }
            } else {
                $user_info['reason'] = 'Guest user (no email)';
            }
            
            $debug_info['users'][] = $user_info;
        }
        
        // Log the email batch
        $this->log_email_batch($debug_info['emails_sent'], $today);
        
        return $debug_info;
    }
    
    /**
     * Send test email to current admin user
     */
    public function send_test_email_to_admin() {
        $admin_user_id = get_current_user_id();
        if (!$admin_user_id) {
            return array('error' => 'No logged in user');
        }
        
        $user = get_user_by('ID', $admin_user_id);
        if (!$user || !$user->user_email) {
            return array('error' => 'Admin user has no email address');
        }
        
        // Create dummy stats for testing
        $stats = array(
            'total_calls' => 5,
            'total_faces' => 5,
            'successful_calls' => 4,
            'failed_calls' => 1
        );
        
        // Create dummy logs for testing
        $detailed_logs = array(
            array(
                'call_time' => current_time('mysql'),
                'face_count' => 1,
                'response_status' => 'success',
                'error_message' => null,
                'session_id' => 'test-session-001'
            ),
            array(
                'call_time' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'face_count' => 2,
                'response_status' => 'success',
                'error_message' => null,
                'session_id' => 'test-session-002'
            )
        );
        
        $today = current_time('Y-m-d');
        $subject = $this->get_email_subject($today, 'day');
        $body = $this->get_email_body($user, $stats, $detailed_logs, $today, 'day');
        
        // Get email settings
        $from_name = get_option('age_estimator_email_from_name', get_bloginfo('name'));
        $from_email = get_option('age_estimator_email_from_address', get_option('admin_email'));
        $reply_to = get_option('age_estimator_email_reply_to', '');
        
        // Set email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>'
        );
        
        if (!empty($reply_to)) {
            $headers[] = 'Reply-To: ' . $reply_to;
        }
        
        // Send email
        $sent = wp_mail($user->user_email, $subject, $body, $headers);
        
        return array(
            'success' => $sent,
            'to_email' => $user->user_email,
            'subject' => $subject,
            'from' => $from_name . ' <' . $from_email . '>'
        );
    }
    
    /**
     * Send compliance email to a specific user
     */
    public function send_user_compliance_email($user_id, $date, $period = 'day') {
        // Get user data
        $user = get_user_by('ID', $user_id);
        if (!$user || !$user->user_email) {
            return false;
        }
        
        // Check if user has opted out
        $opted_out = get_user_meta($user_id, 'age_estimator_compliance_emails_opt_out', true);
        if ($opted_out === 'yes') {
            return false;
        }
        
        // Get API tracker instance
        $tracker = AgeEstimatorAPITracker::get_instance();
        
        // Get user's stats for the period
        $stats = $tracker->get_stats($period, $user_id, $date);
        
        // Get detailed call logs for the period
        $detailed_logs = $this->get_user_detailed_logs($user_id, $date, $period);
        
        // Prepare email content
        $subject = $this->get_email_subject($date, $period);
        $body = $this->get_email_body($user, $stats, $detailed_logs, $date, $period);
        
        // Get email settings
        $from_name = get_option('age_estimator_email_from_name', get_bloginfo('name'));
        $from_email = get_option('age_estimator_email_from_address', get_option('admin_email'));
        $reply_to = get_option('age_estimator_email_reply_to', '');
        
        // Set email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>'
        );
        
        if (!empty($reply_to)) {
            $headers[] = 'Reply-To: ' . $reply_to;
        }
        
        // Send email
        $sent = wp_mail($user->user_email, $subject, $body, $headers);
        
        // Log email send status
        $this->log_email_send($user_id, $date, $sent);
        
        return $sent;
    }
    
    /**
     * Get detailed logs for a specific user and date/period
     */
    private function get_user_detailed_logs($user_id, $date, $period = 'day') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'age_estimator_api_calls';
        
        // Build WHERE clause based on period
        switch ($period) {
            case 'day':
                $where_clause = $wpdb->prepare('user_id = %d AND call_date = %s', $user_id, $date);
                break;
            case 'week':
                $week_start = date('Y-m-d', strtotime('monday this week', strtotime($date)));
                $week_end = date('Y-m-d', strtotime('sunday this week', strtotime($date)));
                $where_clause = $wpdb->prepare('user_id = %d AND call_date BETWEEN %s AND %s', $user_id, $week_start, $week_end);
                break;
            case 'month':
                $month_start = date('Y-m-01', strtotime('-1 month', strtotime($date)));
                $month_end = date('Y-m-t', strtotime('-1 month', strtotime($date)));
                $where_clause = $wpdb->prepare('user_id = %d AND call_date BETWEEN %s AND %s', $user_id, $month_start, $month_end);
                break;
            default:
                $where_clause = $wpdb->prepare('user_id = %d AND call_date = %s', $user_id, $date);
        }
        
        $query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY call_time DESC LIMIT 100";
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get email subject
     */
    private function get_email_subject($date, $period = 'day') {
        $site_name = get_bloginfo('name');
        
        switch ($period) {
            case 'week':
                $week_start = date_i18n(get_option('date_format'), strtotime('monday this week', strtotime($date)));
                $week_end = date_i18n(get_option('date_format'), strtotime('sunday this week', strtotime($date)));
                return sprintf(
                    __('Your %s Weekly Age Verification Report - %s to %s', 'age-estimator'),
                    $site_name,
                    $week_start,
                    $week_end
                );
            case 'month':
                $month = date_i18n('F Y', strtotime('-1 month', strtotime($date)));
                return sprintf(
                    __('Your %s Monthly Age Verification Report - %s', 'age-estimator'),
                    $site_name,
                    $month
                );
            default:
                $formatted_date = date_i18n(get_option('date_format'), strtotime($date));
                return sprintf(
                    __('Your %s Age Verification Compliance Report - %s', 'age-estimator'),
                    $site_name,
                    $formatted_date
                );
        }
    }
    
    /**
     * Get email body HTML
     */
    private function get_email_body($user, $stats, $detailed_logs, $date, $period = 'day') {
        // Format period description
        switch ($period) {
            case 'week':
                $period_desc = sprintf(
                    __('week of %s to %s', 'age-estimator'),
                    date_i18n(get_option('date_format'), strtotime('monday this week', strtotime($date))),
                    date_i18n(get_option('date_format'), strtotime('sunday this week', strtotime($date)))
                );
                break;
            case 'month':
                $period_desc = date_i18n('F Y', strtotime('-1 month', strtotime($date)));
                break;
            default:
                $period_desc = date_i18n(get_option('date_format'), strtotime($date));
        }
        $site_name = get_bloginfo('name');
        $site_url = get_site_url();
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($site_name); ?> Compliance Report</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background-color: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    margin-bottom: 30px;
                }
                .header h1 {
                    margin: 0;
                    color: #2c3e50;
                    font-size: 24px;
                }
                .summary {
                    background-color: #e9ecef;
                    padding: 20px;
                    border-radius: 8px;
                    margin-bottom: 30px;
                }
                .summary h2 {
                    margin-top: 0;
                    color: #495057;
                    font-size: 20px;
                }
                .stats-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                    margin-top: 15px;
                }
                .stat-box {
                    background-color: #fff;
                    padding: 15px;
                    border-radius: 5px;
                    text-align: center;
                }
                .stat-number {
                    font-size: 28px;
                    font-weight: bold;
                    color: #007bff;
                }
                .stat-label {
                    font-size: 14px;
                    color: #6c757d;
                }
                .details {
                    margin-bottom: 30px;
                }
                .details h2 {
                    color: #495057;
                    font-size: 20px;
                    margin-bottom: 15px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                th, td {
                    padding: 10px;
                    text-align: left;
                    border-bottom: 1px solid #dee2e6;
                }
                th {
                    background-color: #f8f9fa;
                    font-weight: bold;
                    color: #495057;
                }
                .success {
                    color: #28a745;
                }
                .error {
                    color: #dc3545;
                }
                .footer {
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 1px solid #dee2e6;
                    font-size: 14px;
                    color: #6c757d;
                    text-align: center;
                }
                .footer a {
                    color: #007bff;
                    text-decoration: none;
                }
                .unsubscribe {
                    margin-top: 20px;
                    font-size: 12px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo esc_html($site_name); ?> Age Verification Report</h1>
                <p>Compliance report for <?php echo esc_html($period_desc); ?></p>
            </div>
            
            <div class="summary">
                <h2>Hello <?php echo esc_html($user->display_name); ?>,</h2>
                <p>Here's your age verification activity summary for this <?php echo $period === 'day' ? 'day' : ($period === 'week' ? 'week' : 'month'); ?>:</p>
                
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-number"><?php echo intval($stats['total_calls']); ?></div>
                        <div class="stat-label">Total Verifications</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo intval($stats['total_faces']); ?></div>
                        <div class="stat-label">Faces Detected</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo intval($stats['successful_calls']); ?></div>
                        <div class="stat-label">Successful</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?php echo intval($stats['failed_calls']); ?></div>
                        <div class="stat-label">Failed</div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($detailed_logs)) : ?>
            <div class="details">
                <h2>Verification Log Details</h2>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo $period === 'day' ? 'Time' : 'Date & Time'; ?></th>
                            <th>Faces</th>
                            <th>Status</th>
                            <th>Session ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detailed_logs as $log) : ?>
                        <tr>
                            <td><?php echo $period === 'day' ? date_i18n('g:i:s A', strtotime($log['call_time'])) : date_i18n(get_option('date_format') . ' g:i A', strtotime($log['call_time'])); ?></td>
                            <td><?php echo intval($log['face_count']); ?></td>
                            <td class="<?php echo $log['response_status'] === 'success' ? 'success' : 'error'; ?>">
                                <?php echo esc_html(ucfirst($log['response_status'])); ?>
                                <?php if ($log['error_message']) : ?>
                                    <br><small><?php echo esc_html($log['error_message']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(substr($log['session_id'], 0, 8)); ?>...</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="footer">
                <p>This is an automated compliance report from <a href="<?php echo esc_url($site_url); ?>"><?php echo esc_html($site_name); ?></a></p>
                <p>All times are in <?php echo esc_html(wp_timezone_string()); ?> timezone.</p>
                
                <div class="unsubscribe">
                    <p>To stop receiving these emails, please update your preferences in your <a href="<?php echo esc_url($site_url . '/wp-admin/profile.php'); ?>">user profile</a>.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Log email batch send
     */
    private function log_email_batch($count, $date) {
        $log_entry = array(
            'date' => $date,
            'count' => $count,
            'timestamp' => current_time('mysql')
        );
        
        $email_logs = get_option('age_estimator_email_logs', array());
        array_unshift($email_logs, $log_entry);
        
        // Keep only last 30 days of logs
        $email_logs = array_slice($email_logs, 0, 30);
        
        update_option('age_estimator_email_logs', $email_logs);
    }
    
    /**
     * Log individual email send
     */
    private function log_email_send($user_id, $date, $success) {
        // You can extend this to log to a custom table if needed
        if (!$success) {
            error_log(sprintf(
                'Age Estimator: Failed to send compliance email to user %d for date %s',
                $user_id,
                $date
            ));
        }
    }
}
