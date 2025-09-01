<?php
/**
 * Compliance Log Manager for Age Estimator Plugin
 * 
 * Handles clearing compliance logs manually and automatically
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AgeEstimatorComplianceLogManager {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add AJAX handlers for manual clearing
        add_action('wp_ajax_age_estimator_clear_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_age_estimator_get_log_stats', array($this, 'ajax_get_log_stats'));
        
        // Schedule automatic clearing
        add_action('init', array($this, 'schedule_automatic_clearing'));
        add_action('age_estimator_clear_logs_cron', array($this, 'auto_clear_logs'));
        
        // Settings registration
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Register settings for log management
     */
    public function register_settings() {
        // Auto-clear settings
        register_setting('age_estimator_settings', 'age_estimator_auto_clear_logs', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_auto_clear_schedule', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_schedule'),
            'default' => 'weekly'
        ));
        
        register_setting('age_estimator_settings', 'age_estimator_log_retention_days', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 90
        ));
    }
    
    /**
     * Sanitize schedule setting
     */
    public function sanitize_schedule($value) {
        $valid_schedules = array('daily', 'weekly', 'monthly');
        return in_array($value, $valid_schedules) ? $value : 'weekly';
    }
    
    /**
     * Schedule automatic clearing
     */
    public function schedule_automatic_clearing() {
        $auto_clear = get_option('age_estimator_auto_clear_logs', false);
        $schedule = get_option('age_estimator_auto_clear_schedule', 'weekly');
        
        if ($auto_clear) {
            // Check if event is already scheduled
            if (!wp_next_scheduled('age_estimator_clear_logs_cron')) {
                // Schedule based on selected frequency
                switch ($schedule) {
                    case 'daily':
                        wp_schedule_event(time(), 'daily', 'age_estimator_clear_logs_cron');
                        break;
                    case 'weekly':
                        wp_schedule_event(time(), 'weekly', 'age_estimator_clear_logs_cron');
                        break;
                    case 'monthly':
                        // WordPress doesn't have monthly by default, so we'll use a custom interval
                        if (!wp_next_scheduled('age_estimator_clear_logs_cron')) {
                            wp_schedule_event(time(), 'age_estimator_monthly', 'age_estimator_clear_logs_cron');
                        }
                        break;
                }
            }
        } else {
            // If auto-clear is disabled, remove the scheduled event
            wp_clear_scheduled_hook('age_estimator_clear_logs_cron');
        }
    }
    
    /**
     * Auto clear logs based on retention settings
     */
    public function auto_clear_logs() {
        $retention_days = get_option('age_estimator_log_retention_days', 90);
        $this->clear_old_logs($retention_days);
        
        // Log the automatic clearing
        error_log('Age Estimator: Automatic log clearing completed. Logs older than ' . $retention_days . ' days were removed.');
    }
    
    /**
     * Clear logs older than specified days
     */
    public function clear_old_logs($days = 90) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'age_estimator_checks';
        
        // Calculate the cutoff date
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
        
        // Delete old records
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE check_time < %s",
            $cutoff_date
        ));
        
        return $deleted;
    }
    
    /**
     * Clear all logs
     */
    public function clear_all_logs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'age_estimator_checks';
        
        // Truncate the table for better performance
        $result = $wpdb->query("TRUNCATE TABLE $table_name");
        
        return $result !== false;
    }
    
    /**
     * Get log statistics
     */
    public function get_log_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'age_estimator_checks';
        
        // Get total count
        $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Get date range
        $oldest_log = $wpdb->get_var("SELECT MIN(check_time) FROM $table_name");
        $newest_log = $wpdb->get_var("SELECT MAX(check_time) FROM $table_name");
        
        // Get logs by mode
        $logs_by_mode = $wpdb->get_results(
            "SELECT detection_mode, COUNT(*) as count 
             FROM $table_name 
             GROUP BY detection_mode",
            ARRAY_A
        );
        
        // Get retail mode specific stats
        $retail_stats = $wpdb->get_row(
            "SELECT 
                COUNT(CASE WHEN id_checked = 1 THEN 1 END) as id_checks,
                COUNT(CASE WHEN sale_completed = 1 THEN 1 END) as completed_sales
             FROM $table_name",
            ARRAY_A
        );
        
        return array(
            'total_logs' => $total_logs,
            'oldest_log' => $oldest_log,
            'newest_log' => $newest_log,
            'logs_by_mode' => $logs_by_mode,
            'retail_stats' => $retail_stats
        );
    }
    
    /**
     * AJAX handler to clear logs
     */
    public function ajax_clear_logs() {
        // Check nonce
        check_ajax_referer('age_estimator_admin_nonce', 'nonce');
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $clear_type = isset($_POST['clear_type']) ? sanitize_text_field($_POST['clear_type']) : 'all';
        
        if ($clear_type === 'old') {
            $days = isset($_POST['days']) ? absint($_POST['days']) : 90;
            $deleted = $this->clear_old_logs($days);
            
            wp_send_json_success(array(
                'message' => sprintf('Cleared %d logs older than %d days', $deleted, $days),
                'deleted' => $deleted
            ));
        } else {
            $success = $this->clear_all_logs();
            
            if ($success) {
                wp_send_json_success(array(
                    'message' => 'All compliance logs have been cleared'
                ));
            } else {
                wp_send_json_error('Failed to clear logs');
            }
        }
    }
    
    /**
     * AJAX handler to get log statistics
     */
    public function ajax_get_log_stats() {
        // Check nonce
        check_ajax_referer('age_estimator_admin_nonce', 'nonce');
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $stats = $this->get_log_stats();
        wp_send_json_success($stats);
    }
}

// Add custom cron schedule for monthly
add_filter('cron_schedules', function($schedules) {
    $schedules['age_estimator_monthly'] = array(
        'interval' => 30 * DAY_IN_SECONDS,
        'display' => __('Monthly', 'age-estimator')
    );
    return $schedules;
});

// Initialize the log manager
new AgeEstimatorComplianceLogManager();
