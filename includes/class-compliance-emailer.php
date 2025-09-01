<?php
/**
 * Compliance Email System for Age Estimator
 * 
 * Sends daily compliance logs to users via email
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AgeEstimatorComplianceEmailer {
    
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
        // Schedule daily email cron job
        add_action('age_estimator_send_daily_compliance_emails', array($this, 'send_daily_emails'));
        
        // Schedule the event if not already scheduled
        if (!wp_next_scheduled('age_estimator_send_daily_compliance_emails')) {
            // Schedule for 11:00 PM daily (adjust as needed)
            $timestamp = strtotime('today 23:00:00');
            if ($timestamp < time()) {
                $timestamp = strtotime('tomorrow 23:00:00');
            }
            wp_schedule_event($timestamp, 'daily', 'age_estimator_send_daily_compliance_emails');
        }
        
        // Add settings fields
        add_action('age_estimator_email_settings', array($this, 'add_email_settings'));
    }
    
    /**
     * Send daily compliance emails to all users
     */
    public function send_daily_emails() {
        // Get settings
        $send_emails = get_option('age_estimator_send_compliance_emails', 'yes');
        if ($send_emails !== 'yes') {
            return;
        }
        
        // Get today's date
        $today = current_time('Y-m-d');
        $day_of_week = date('w'); // 0 = Sunday, 1 = Monday, etc.
        $day_of_month = date('j');
        
        // Get API tracker instance
        $tracker = AgeEstimatorAPITracker::get_instance();
        
        // Process daily emails
        $daily_count = $this->process_emails_by_frequency($tracker, 'daily', $today);
        
        // Process weekly emails (on Mondays)
        $weekly_count = 0;
        if ($day_of_week == 1) {
            $weekly_count = $this->process_emails_by_frequency($tracker, 'weekly', $today);
        }
        
        // Process monthly emails (on 1st of month)
        $monthly_count = 0;
        if ($day_of_month == 1) {
            $monthly_count = $this->process_emails_by_frequency($tracker, 'monthly', $today);
        }
        
        // Log the email batch
        $total_count = $daily_count + $weekly_count + $monthly_count;
        $this->log_email_batch($total_count, $today);
    }
    
    /**
     * Process emails for users with specific frequency preference
     */
    private function process_emails_by_frequency($tracker, $frequency, $date) {
        $count = 0;
        
        // Determine the period based on frequency
        switch ($frequency) {
            case 'daily':
                $period = 'day';
                $check_date = $date;
                break;
            case 'weekly':
                $period = 'week';
                $check_date = $date;
                break;
            case 'monthly':
                $period = 'month';
                $check_date = date('Y-m-d', strtotime('-1 month'));
                break;
            default:
                return 0;
        }
        
        // Get all users who had activity in the period
        $user_stats = $tracker->get_user_stats(9999, 0, $period, $check_date);
        
        // Send email to each user with matching frequency preference
        foreach ($user_stats as $user_stat) {
            if ($user_stat['user_id'] > 0) { // Only send to registered users
                $user_frequency = get_user_meta($user_stat['user_id'], 'age_estimator_email_frequency', true);
                if (!$user_frequency) {
                    $user_frequency = 'daily';
                }
                
                if ($user_frequency === $frequency) {
                    $this->send_user_compliance_email($user_stat['user_id'], $date, $period);
                    $count++;
                }
            }
        }
        
        return $count;
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
    
    /**
     * Add email settings to admin panel
     */
    public function add_email_settings() {
        ?>
        <h3><?php _e('Compliance Email Settings', 'age-estimator'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="age_estimator_send_compliance_emails">
                        <?php _e('Send Daily Compliance Emails', 'age-estimator'); ?>
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
                        <?php _e('Enable or disable automatic daily compliance email reports to users.', 'age-estimator'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="age_estimator_email_send_time">
                        <?php _e('Email Send Time', 'age-estimator'); ?>
                    </label>
                </th>
                <td>
                    <input type="time" name="age_estimator_email_send_time" id="age_estimator_email_send_time" 
                           value="<?php echo esc_attr(get_option('age_estimator_email_send_time', '23:00')); ?>" />
                    <p class="description">
                        <?php _e('Time of day to send compliance emails (server time).', 'age-estimator'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Send test email (for debugging)
     */
    public function send_test_email($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $today = current_time('Y-m-d');
        return $this->send_user_compliance_email($user_id, $today);
    }
}

// Initialize the emailer
AgeEstimatorComplianceEmailer::get_instance();
