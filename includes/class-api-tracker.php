<?php
/**
 * API Call Tracking System for Age Estimator
 * 
 * Tracks Rekognition API calls per user with daily, weekly, and monthly stats
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AgeEstimatorAPITracker {
    
    private static $instance = null;
    private $table_name;
    
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
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'age_estimator_api_calls';
        
        // Hook into activation to create table
        register_activation_hook(AGE_ESTIMATOR_BASENAME, array($this, 'create_tables'));
        
        // Schedule cleanup cron job
        add_action('age_estimator_cleanup_old_stats', array($this, 'cleanup_old_stats'));
        
        if (!wp_next_scheduled('age_estimator_cleanup_old_stats')) {
            wp_schedule_event(time(), 'daily', 'age_estimator_cleanup_old_stats');
        }
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user_ip varchar(45) NOT NULL DEFAULT '',
            call_date date NOT NULL,
            call_time datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            face_count int(11) NOT NULL DEFAULT 1,
            response_status varchar(20) NOT NULL DEFAULT 'success',
            error_message text DEFAULT NULL,
            session_id varchar(64) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_date_idx (user_id, call_date),
            KEY ip_date_idx (user_ip, call_date),
            KEY date_idx (call_date),
            KEY session_idx (session_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Add version option
        add_option('age_estimator_db_version', '1.0');
    }
    
    /**
     * Track an API call
     */
    public function track_api_call($data = array()) {
        global $wpdb;
        
        $defaults = array(
            'user_id' => get_current_user_id(),
            'user_ip' => $this->get_user_ip(),
            'call_date' => current_time('Y-m-d'),
            'call_time' => current_time('mysql'),
            'face_count' => 1,
            'response_status' => 'success',
            'error_message' => null,
            'session_id' => $this->get_session_id()
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Sanitize data
        $data['user_id'] = absint($data['user_id']);
        $data['user_ip'] = sanitize_text_field($data['user_ip']);
        $data['face_count'] = absint($data['face_count']);
        $data['response_status'] = sanitize_text_field($data['response_status']);
        $data['error_message'] = $data['error_message'] ? sanitize_text_field($data['error_message']) : null;
        $data['session_id'] = sanitize_text_field($data['session_id']);
        
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = filter_var($_SERVER[$key], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
                if ($ip !== false) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Get or create session ID
     */
    private function get_session_id() {
        if (!session_id()) {
            session_start();
        }
        return session_id();
    }
    
    /**
     * Get statistics for a specific period
     */
    public function get_stats($period = 'day', $user_id = null, $date = null) {
        global $wpdb;
        
        if (!$date) {
            $date = current_time('Y-m-d');
        }
        
        $where_conditions = array();
        $where_values = array();
        
        // Build WHERE clause based on period
        switch ($period) {
            case 'day':
                $where_conditions[] = 'call_date = %s';
                $where_values[] = $date;
                break;
                
            case 'week':
                $week_start = date('Y-m-d', strtotime('monday this week', strtotime($date)));
                $week_end = date('Y-m-d', strtotime('sunday this week', strtotime($date)));
                $where_conditions[] = 'call_date BETWEEN %s AND %s';
                $where_values[] = $week_start;
                $where_values[] = $week_end;
                break;
                
            case 'month':
                $month_start = date('Y-m-01', strtotime($date));
                $month_end = date('Y-m-t', strtotime($date));
                $where_conditions[] = 'call_date BETWEEN %s AND %s';
                $where_values[] = $month_start;
                $where_values[] = $month_end;
                break;
                
            case 'year':
                $year = date('Y', strtotime($date));
                $where_conditions[] = 'YEAR(call_date) = %d';
                $where_values[] = $year;
                break;
        }
        
        // Add user filter if specified
        if ($user_id !== null) {
            $where_conditions[] = 'user_id = %d';
            $where_values[] = $user_id;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Prepare and execute query
        $query = "SELECT 
            COUNT(*) as total_calls,
            COUNT(DISTINCT user_id) as unique_users,
            COUNT(DISTINCT user_ip) as unique_ips,
            SUM(face_count) as total_faces,
            COUNT(CASE WHEN response_status = 'success' THEN 1 END) as successful_calls,
            COUNT(CASE WHEN response_status != 'success' THEN 1 END) as failed_calls,
            MIN(call_time) as first_call,
            MAX(call_time) as last_call
        FROM {$this->table_name}
        {$where_clause}";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        return $wpdb->get_row($query, ARRAY_A);
    }
    
    /**
     * Get detailed stats by user
     */
    public function get_user_stats($limit = 50, $offset = 0, $period = 'month', $date = null) {
        global $wpdb;
        
        if (!$date) {
            $date = current_time('Y-m-d');
        }
        
        $where_conditions = array();
        $where_values = array();
        
        // Build WHERE clause based on period
        switch ($period) {
            case 'day':
                $where_conditions[] = 'call_date = %s';
                $where_values[] = $date;
                break;
                
            case 'week':
                $week_start = date('Y-m-d', strtotime('monday this week', strtotime($date)));
                $week_end = date('Y-m-d', strtotime('sunday this week', strtotime($date)));
                $where_conditions[] = 'call_date BETWEEN %s AND %s';
                $where_values[] = $week_start;
                $where_values[] = $week_end;
                break;
                
            case 'month':
                $month_start = date('Y-m-01', strtotime($date));
                $month_end = date('Y-m-t', strtotime($date));
                $where_conditions[] = 'call_date BETWEEN %s AND %s';
                $where_values[] = $month_start;
                $where_values[] = $month_end;
                break;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Build query
        $query = "SELECT 
            user_id,
            COUNT(*) as total_calls,
            SUM(face_count) as total_faces,
            COUNT(CASE WHEN response_status = 'success' THEN 1 END) as successful_calls,
            COUNT(CASE WHEN response_status != 'success' THEN 1 END) as failed_calls,
            MIN(call_time) as first_call,
            MAX(call_time) as last_call,
            COUNT(DISTINCT call_date) as active_days,
            COUNT(DISTINCT session_id) as unique_sessions
        FROM {$this->table_name}
        {$where_clause}
        GROUP BY user_id
        ORDER BY total_calls DESC
        LIMIT %d OFFSET %d";
        
        // Add limit and offset to values
        $where_values[] = $limit;
        $where_values[] = $offset;
        
        $query = $wpdb->prepare($query, $where_values);
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get time-based statistics (hourly distribution)
     */
    public function get_hourly_distribution($date = null) {
        global $wpdb;
        
        if (!$date) {
            $date = current_time('Y-m-d');
        }
        
        $query = $wpdb->prepare(
            "SELECT 
                HOUR(call_time) as hour,
                COUNT(*) as calls,
                SUM(face_count) as faces
            FROM {$this->table_name}
            WHERE call_date = %s
            GROUP BY HOUR(call_time)
            ORDER BY hour",
            $date
        );
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get daily statistics for a date range
     */
    public function get_daily_stats($start_date, $end_date, $user_id = null) {
        global $wpdb;
        
        $where_conditions = array('call_date BETWEEN %s AND %s');
        $where_values = array($start_date, $end_date);
        
        if ($user_id !== null) {
            $where_conditions[] = 'user_id = %d';
            $where_values[] = $user_id;
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        $query = "SELECT 
            call_date,
            COUNT(*) as total_calls,
            COUNT(DISTINCT user_id) as unique_users,
            SUM(face_count) as total_faces,
            COUNT(CASE WHEN response_status = 'success' THEN 1 END) as successful_calls
        FROM {$this->table_name}
        {$where_clause}
        GROUP BY call_date
        ORDER BY call_date DESC";
        
        $query = $wpdb->prepare($query, $where_values);
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get user details
     */
    public function get_user_details($user_id) {
        if ($user_id > 0) {
            $user = get_user_by('ID', $user_id);
            if ($user) {
                return array(
                    'display_name' => $user->display_name,
                    'email' => $user->user_email,
                    'username' => $user->user_login
                );
            }
        }
        return array(
            'display_name' => 'Guest User',
            'email' => '',
            'username' => 'guest'
        );
    }
    
    /**
     * Export statistics to CSV
     */
    public function export_stats_csv($period = 'month', $date = null) {
        $stats = $this->get_user_stats(9999, 0, $period, $date);
        
        $csv_data = array();
        $csv_data[] = array('User ID', 'Display Name', 'Email', 'Total Calls', 'Successful', 'Failed', 'Total Faces', 'First Call', 'Last Call', 'Active Days', 'Sessions');
        
        foreach ($stats as $stat) {
            $user_details = $this->get_user_details($stat['user_id']);
            $csv_data[] = array(
                $stat['user_id'],
                $user_details['display_name'],
                $user_details['email'],
                $stat['total_calls'],
                $stat['successful_calls'],
                $stat['failed_calls'],
                $stat['total_faces'],
                $stat['first_call'],
                $stat['last_call'],
                $stat['active_days'],
                $stat['unique_sessions']
            );
        }
        
        return $csv_data;
    }
    
    /**
     * Cleanup old statistics (keep last 90 days by default)
     */
    public function cleanup_old_stats() {
        global $wpdb;
        
        $days_to_keep = apply_filters('age_estimator_stats_retention_days', 90);
        $cutoff_date = date('Y-m-d', strtotime("-{$days_to_keep} days"));
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE call_date < %s",
            $cutoff_date
        ));
    }
    
    /**
     * Get total API calls for current billing period
     */
    public function get_billing_period_stats($billing_start_day = 1) {
        $current_date = current_time('Y-m-d');
        $current_day = (int)date('d');
        
        if ($current_day >= $billing_start_day) {
            $start_date = date('Y-m-' . str_pad($billing_start_day, 2, '0', STR_PAD_LEFT));
        } else {
            $start_date = date('Y-m-' . str_pad($billing_start_day, 2, '0', STR_PAD_LEFT), strtotime('-1 month'));
        }
        
        return $this->get_stats('custom', null, $start_date);
    }
}

// Initialize the tracker
AgeEstimatorAPITracker::get_instance();
