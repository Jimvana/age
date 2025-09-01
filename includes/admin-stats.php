<?php
/**
 * Admin API Statistics Page for Age Estimator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AgeEstimatorAdminStats {
    
    private $tracker;
    
    public function __construct() {
        $this->tracker = AgeEstimatorAPITracker::get_instance();
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers for stats
        add_action('wp_ajax_age_estimator_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_age_estimator_export_stats', array($this, 'ajax_export_stats'));
    }
    
    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        // Add submenu under the main Age Estimator menu
        add_submenu_page(
            'age-estimator-settings',
            __('API Usage Statistics', 'age-estimator'),
            __('API Statistics', 'age-estimator'),
            'manage_options',
            'age-estimator-stats',
            array($this, 'render_stats_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_assets($hook) {
        // Check if we're on the stats page
        if ($hook !== 'toplevel_page_age-estimator-settings' && $hook !== 'age-estimator_page_age-estimator-stats') {
            return;
        }
        
        // Only load on stats page
        if (!isset($_GET['page']) || $_GET['page'] !== 'age-estimator-stats') {
            return;
        }
        
        // Enqueue Chart.js for graphs
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            array(),
            '3.9.1',
            true
        );
        
        // Enqueue custom admin script
        wp_enqueue_script(
            'age-estimator-admin-stats',
            AGE_ESTIMATOR_URL . 'js/admin-stats.js',
            array('jquery', 'chartjs'),
            AGE_ESTIMATOR_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('age-estimator-admin-stats', 'ageEstimatorStats', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('age_estimator_stats_nonce')
        ));
        
        // Enqueue custom admin styles
        wp_enqueue_style(
            'age-estimator-admin-stats',
            AGE_ESTIMATOR_URL . 'css/admin-stats.css',
            array(),
            AGE_ESTIMATOR_VERSION
        );
    }
    
    /**
     * Render the statistics page
     */
    public function render_stats_page() {
        // Get current filters
        $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'month';
        $date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : current_time('Y-m-d');
        
        // Get overview stats
        $overview_stats = $this->tracker->get_stats($period, null, $date);
        
        // Get user stats
        $user_stats = $this->tracker->get_user_stats(50, 0, $period, $date);
        
        // Get billing period stats (assuming billing starts on 1st of month)
        $billing_stats = $this->tracker->get_billing_period_stats(1);
        
        ?>
        <div class="wrap age-estimator-stats-wrap">
            <h1><?php echo esc_html__('Age Estimator API Usage Statistics', 'age-estimator'); ?></h1>
            
            <!-- Period Selector -->
            <div class="stats-controls">
                <form method="get" action="">
                    <input type="hidden" name="page" value="age-estimator-stats">
                    
                    <label for="period"><?php _e('View Period:', 'age-estimator'); ?></label>
                    <select name="period" id="period">
                        <option value="day" <?php selected($period, 'day'); ?>><?php _e('Daily', 'age-estimator'); ?></option>
                        <option value="week" <?php selected($period, 'week'); ?>><?php _e('Weekly', 'age-estimator'); ?></option>
                        <option value="month" <?php selected($period, 'month'); ?>><?php _e('Monthly', 'age-estimator'); ?></option>
                        <option value="year" <?php selected($period, 'year'); ?>><?php _e('Yearly', 'age-estimator'); ?></option>
                    </select>
                    
                    <input type="date" name="date" value="<?php echo esc_attr($date); ?>" max="<?php echo current_time('Y-m-d'); ?>">
                    
                    <button type="submit" class="button"><?php _e('Update', 'age-estimator'); ?></button>
                    
                    <a href="#" class="button" id="export-stats"><?php _e('Export CSV', 'age-estimator'); ?></a>
                </form>
            </div>
            
            <!-- Overview Stats -->
            <div class="stats-overview">
                <h2><?php _e('Overview', 'age-estimator'); ?></h2>
                
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-value"><?php echo number_format($overview_stats['total_calls'] ?? 0); ?></div>
                        <div class="stat-label"><?php _e('Total API Calls', 'age-estimator'); ?></div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-value"><?php echo number_format($overview_stats['unique_users'] ?? 0); ?></div>
                        <div class="stat-label"><?php _e('Unique Users', 'age-estimator'); ?></div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-value"><?php echo number_format($overview_stats['total_faces'] ?? 0); ?></div>
                        <div class="stat-label"><?php _e('Total Faces Detected', 'age-estimator'); ?></div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-value">
                            <?php 
                            $success_rate = ($overview_stats['total_calls'] > 0) 
                                ? round(($overview_stats['successful_calls'] / $overview_stats['total_calls']) * 100, 1)
                                : 0;
                            echo $success_rate . '%';
                            ?>
                        </div>
                        <div class="stat-label"><?php _e('Success Rate', 'age-estimator'); ?></div>
                    </div>
                </div>
                
                <!-- Billing Period Alert -->
                <?php if ($billing_stats): ?>
                <div class="billing-alert">
                    <h3><?php _e('Current Billing Period', 'age-estimator'); ?></h3>
                    <p>
                        <?php 
                        printf(
                            __('Total API calls this billing period: %s', 'age-estimator'),
                            '<strong>' . number_format($billing_stats['total_calls'] ?? 0) . '</strong>'
                        );
                        ?>
                    </p>
                    <?php
                    // AWS Rekognition pricing alert (as of 2024, it's $1 per 1000 API calls)
                    $estimated_cost = ($billing_stats['total_calls'] ?? 0) / 1000 * 1.00;
                    if ($estimated_cost > 0):
                    ?>
                    <p class="cost-estimate">
                        <?php 
                        printf(
                            __('Estimated AWS Rekognition cost: $%s', 'age-estimator'),
                            number_format($estimated_cost, 2)
                        );
                        ?>
                    </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Charts Section -->
            <div class="stats-charts">
                <div class="chart-container">
                    <h3><?php _e('Daily Usage Chart', 'age-estimator'); ?></h3>
                    <canvas id="daily-usage-chart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3><?php _e('Hourly Distribution', 'age-estimator'); ?></h3>
                    <canvas id="hourly-distribution-chart"></canvas>
                </div>
            </div>
            
            <!-- User Statistics Table -->
            <div class="user-stats-section">
                <h2><?php _e('User Statistics', 'age-estimator'); ?></h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('User', 'age-estimator'); ?></th>
                            <th><?php _e('Total Calls', 'age-estimator'); ?></th>
                            <th><?php _e('Successful', 'age-estimator'); ?></th>
                            <th><?php _e('Failed', 'age-estimator'); ?></th>
                            <th><?php _e('Faces Detected', 'age-estimator'); ?></th>
                            <th><?php _e('Active Days', 'age-estimator'); ?></th>
                            <th><?php _e('Sessions', 'age-estimator'); ?></th>
                            <th><?php _e('Last Activity', 'age-estimator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($user_stats)): ?>
                        <tr>
                            <td colspan="8"><?php _e('No data available for the selected period.', 'age-estimator'); ?></td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($user_stats as $stat): ?>
                            <?php $user_details = $this->tracker->get_user_details($stat['user_id']); ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($user_details['display_name']); ?></strong>
                                    <?php if ($user_details['email']): ?>
                                        <br><small><?php echo esc_html($user_details['email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($stat['total_calls']); ?></td>
                                <td><?php echo number_format($stat['successful_calls']); ?></td>
                                <td><?php echo number_format($stat['failed_calls']); ?></td>
                                <td><?php echo number_format($stat['total_faces']); ?></td>
                                <td><?php echo number_format($stat['active_days']); ?></td>
                                <td><?php echo number_format($stat['unique_sessions']); ?></td>
                                <td><?php echo human_time_diff(strtotime($stat['last_call']), current_time('timestamp')) . ' ' . __('ago', 'age-estimator'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Additional Information -->
            <div class="stats-info">
                <h3><?php _e('Information', 'age-estimator'); ?></h3>
                <ul>
                    <li><?php _e('API calls are tracked when AWS Rekognition is used for face detection.', 'age-estimator'); ?></li>
                    <li><?php _e('Statistics are retained for 90 days by default.', 'age-estimator'); ?></li>
                    <li><?php _e('Face count represents the number of faces detected in each API call.', 'age-estimator'); ?></li>
                    <li><?php _e('Guest users are tracked by IP address and session.', 'age-estimator'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler to get statistics
     */
    public function ajax_get_stats() {
        check_ajax_referer('age_estimator_stats_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'daily';
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d', strtotime('-30 days'));
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : current_time('Y-m-d');
        
        switch ($type) {
            case 'daily':
                $data = $this->tracker->get_daily_stats($start_date, $end_date);
                break;
                
            case 'hourly':
                $data = $this->tracker->get_hourly_distribution($end_date);
                break;
                
            default:
                $data = array();
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler to export statistics
     */
    public function ajax_export_stats() {
        check_ajax_referer('age_estimator_stats_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'month';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : current_time('Y-m-d');
        
        $csv_data = $this->tracker->export_stats_csv($period, $date);
        
        // Generate CSV content
        $csv_content = '';
        foreach ($csv_data as $row) {
            $csv_content .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }
        
        wp_send_json_success(array(
            'filename' => 'age-estimator-stats-' . $period . '-' . $date . '.csv',
            'content' => $csv_content
        ));
    }
}

// Initialize admin stats
new AgeEstimatorAdminStats();
