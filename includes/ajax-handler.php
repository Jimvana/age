<?php
/**
 * AJAX Handler for Age Estimator Plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load API tracker
require_once AGE_ESTIMATOR_PATH . 'includes/class-api-tracker.php';

class AgeEstimatorAjax {
    
    public function __construct() {
        // Register AJAX handlers - support both new and old action names for backward compatibility
        add_action('wp_ajax_age_estimator_detect', array($this, 'handle_age_detection'));
        add_action('wp_ajax_nopriv_age_estimator_detect', array($this, 'handle_age_detection'));
        add_action('wp_ajax_age_estimator_photo_detect', array($this, 'handle_age_detection'));
        add_action('wp_ajax_nopriv_age_estimator_photo_detect', array($this, 'handle_age_detection'));
        
        // Retail mode handlers
        add_action('wp_ajax_age_estimator_log_check', array($this, 'handle_log_check'));
        add_action('wp_ajax_nopriv_age_estimator_log_check', array($this, 'handle_log_check'));
        add_action('wp_ajax_age_estimator_log_id_confirmation', array($this, 'handle_log_id_confirmation'));
        add_action('wp_ajax_nopriv_age_estimator_log_id_confirmation', array($this, 'handle_log_id_confirmation'));
        
        // Simple mode logging handler
        add_action('wp_ajax_age_estimator_log_simple_scan', array($this, 'handle_log_simple_scan'));
        add_action('wp_ajax_nopriv_age_estimator_log_simple_scan', array($this, 'handle_log_simple_scan'));
    }
    
    /**
     * Handle age detection request
     */
    public function handle_age_detection() {
        // Verify nonce - support both old and new nonce names
        $nonce_valid = check_ajax_referer('age_estimator_nonce', 'nonce', false) || 
                      check_ajax_referer('age_estimator_photo_nonce', 'nonce', false);
        
        if (!$nonce_valid) {
            wp_send_json_error(array(
                'message' => 'Security check failed',
                'details' => 'Invalid or expired security token'
            ));
            return;
        }
        
        // Get the current mode
        $mode = get_option('age_estimator_mode', 'simple');
        
        // Get image data
        $image_data = isset($_POST['image']) ? $_POST['image'] : '';
        
        if (empty($image_data)) {
            wp_send_json_error(array(
                'message' => 'No image data received',
                'details' => 'Please capture a photo first'
            ));
            return;
        }
        
        // Handle based on mode
        if ($mode === 'simple') {
            // Simple mode - client-side processing, just acknowledge receipt
            wp_send_json_success(array(
                'message' => 'Image received for client-side processing',
                'mode' => 'simple'
            ));
        } else {
            // AWS mode - server-side processing
            $this->handle_aws_detection($image_data);
        }
    }
    
    /**
     * Handle AWS Rekognition detection
     */
    private function handle_aws_detection($image_data) {
        // Check if AWS is configured
        $access_key = get_option('age_estimator_aws_access_key');
        $secret_key = get_option('age_estimator_aws_secret_key');
        
        if (empty($access_key) || empty($secret_key)) {
            wp_send_json_error(array(
                'message' => 'AWS Rekognition is not configured',
                'details' => 'Please configure AWS credentials in the plugin settings'
            ));
            return;
        }
        
        // Remove data URL prefix if present
        if (strpos($image_data, 'data:image') === 0) {
            $image_data = preg_replace('/^data:image\/\w+;base64,/', '', $image_data);
        }
        
        // Decode base64 image
        $image_binary = base64_decode($image_data);
        
        if ($image_binary === false) {
            wp_send_json_error(array(
                'message' => 'Invalid image data',
                'details' => 'Could not decode the image data'
            ));
            return;
        }
        
        // Debug: Check image size and validity
        error_log('AWS Detection: Image size = ' . strlen($image_binary) . ' bytes');
        error_log('AWS Detection: First 2 bytes = ' . sprintf('%02X %02X', ord($image_binary[0]), ord($image_binary[1])));
        error_log('AWS Detection: Valid JPEG = ' . ((ord($image_binary[0]) === 0xFF && ord($image_binary[1]) === 0xD8) ? 'YES' : 'NO'));
        
        // Load AWS Rekognition API
        if (!class_exists('AgeEstimatorAWSRekognition')) {
            $aws_file = AGE_ESTIMATOR_PATH . 'includes/aws-rekognition-api.php';
            if (file_exists($aws_file)) {
                require_once $aws_file;
            } else {
                wp_send_json_error(array(
                    'message' => 'AWS API not found',
                    'details' => 'The AWS Rekognition API file is missing'
                ));
                return;
            }
        }
        
        try {
            // Initialize AWS Rekognition
            $aws = new AgeEstimatorAWSRekognition(
                $access_key,
                $secret_key,
                get_option('age_estimator_aws_region', 'us-east-1')
            );
            
            // Detect faces
            $result = $aws->detect_faces($image_binary);
            
            error_log('AWS Detection: Result = ' . print_r($result, true));
            
            // Track API call
            $tracker = AgeEstimatorAPITracker::get_instance();
            $face_count = 0;
            
            if ($result['success'] && isset($result['data']['faces'])) {
                $face_count = count($result['data']['faces']);
            }
            
            $tracker->track_api_call(array(
                'face_count' => $face_count,
                'response_status' => $result['success'] ? 'success' : 'error',
                'error_message' => !$result['success'] ? $result['message'] : null
            ));
            
            if ($result['success']) {
                // Get display options
                $show_emotions = get_option('age_estimator_show_emotions', true);
                $show_attributes = get_option('age_estimator_show_attributes', true);
                
                // Filter response based on settings
                if (!$show_emotions && isset($result['data']['faces'])) {
                    foreach ($result['data']['faces'] as &$face) {
                        unset($face['emotions']);
                    }
                }
                
                if (!$show_attributes && isset($result['data']['faces'])) {
                    foreach ($result['data']['faces'] as &$face) {
                        // Keep only age and gender
                        $age = isset($face['ageRange']) ? $face['ageRange'] : null;
                        $gender = isset($face['gender']) ? $face['gender'] : null;
                        
                        // Remove all attributes
                        foreach ($face as $key => $value) {
                            if (!in_array($key, array('ageRange', 'gender'))) {
                                unset($face[$key]);
                            }
                        }
                    }
                }
                
                // Check age gating if enabled
                $enable_age_gate = get_option('age_estimator_enable_age_gate', false);
                if ($enable_age_gate && isset($result['data']['faces'][0]['ageRange'])) {
                    $minimum_age = intval(get_option('age_estimator_minimum_age', 21));
                    $estimated_age = $result['data']['faces'][0]['ageRange']['Low'];
                    
                    if ($estimated_age < $minimum_age) {
                        $result['data']['ageGateResult'] = array(
                            'passed' => false,
                            'minimumAge' => $minimum_age,
                            'estimatedAge' => $estimated_age,
                            'message' => str_replace('{age}', $minimum_age, get_option('age_estimator_age_gate_message', 'You must be {age} or older to access this content.')),
                            'redirectUrl' => get_option('age_estimator_age_gate_redirect', '')
                        );
                    } else {
                        $result['data']['ageGateResult'] = array(
                            'passed' => true,
                            'minimumAge' => $minimum_age,
                            'estimatedAge' => $estimated_age
                        );
                    }
                }
                
                // Add privacy mode flag
                $result['data']['privacyMode'] = get_option('age_estimator_privacy_mode', false);
                
                wp_send_json_success($result['data']);
            } else {
                wp_send_json_error(array(
                    'message' => $result['message'],
                    'details' => isset($result['details']) ? $result['details'] : 'Unknown error occurred'
                ));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'An error occurred during face detection',
                'details' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Handle retail mode check logging
     */
    public function handle_log_check() {
        // Verify nonce
        if (!check_ajax_referer('age_estimator_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => 'Security check failed'
            ));
            return;
        }
        
        // Check if logging is enabled
        if (!get_option('age_estimator_enable_logging', false)) {
            wp_send_json_success(array(
                'message' => 'Logging disabled'
            ));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'age_estimator_checks';
        
        // Get the actual detection mode from settings
        $detection_mode = get_option('age_estimator_mode', 'simple');
        
        // Get current user info
        $user_id = get_current_user_id();
        $user_ip = $this->get_user_ip();
        $session_id = $this->get_session_id();
        
        // Prepare data
        $data = array(
            'check_time' => current_time('mysql'),
            'estimated_age' => intval($_POST['age'] ?? 0),
            'alert_level' => sanitize_text_field($_POST['alert_level'] ?? ''),
            'staff_member' => sanitize_text_field($_POST['staff'] ?? 'Unknown'),
            'image_hash' => sanitize_text_field($_POST['image_hash'] ?? ''),
            'detection_mode' => $detection_mode,  // Use the actual mode setting
            'face_detected' => 1,  // If we're logging, a face was detected
            'confidence' => floatval($_POST['confidence'] ?? 0),
            'gender' => sanitize_text_field($_POST['gender'] ?? ''),
            'user_id' => $user_id,
            'user_ip' => $user_ip,
            'session_id' => $session_id,
            'capture_time' => sanitize_text_field($_POST['capture_time'] ?? '')
        );
        
        // Insert into database
        $result = $wpdb->insert($table_name, $data);
        
        if ($result === false) {
            wp_send_json_error(array(
                'message' => 'Failed to log check',
                'details' => $wpdb->last_error
            ));
        } else {
            wp_send_json_success(array(
                'check_id' => $wpdb->insert_id,
                'message' => 'Check logged successfully'
            ));
        }
    }
    
    /**
     * Handle retail mode ID confirmation logging
     */
    public function handle_log_id_confirmation() {
        // Verify nonce
        if (!check_ajax_referer('age_estimator_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => 'Security check failed'
            ));
            return;
        }
        
        // Check if logging is enabled
        if (!get_option('age_estimator_enable_logging', false)) {
            wp_send_json_success(array(
                'message' => 'Logging disabled'
            ));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'age_estimator_checks';
        
        // Get the result type
        $result = sanitize_text_field($_POST['result'] ?? '');
        
        // Map result to database fields
        $id_checked = false;
        $id_result = '';
        $sale_completed = false;
        
        switch($result) {
            case 'no-sale':
                $id_checked = false;
                $id_result = 'no_id';
                $sale_completed = false;
                break;
            case 'verified-over':
                $id_checked = true;
                $id_result = 'verified_over';
                $sale_completed = true;
                break;
            case 'verified-under':
                $id_checked = true;
                $id_result = 'verified_under';
                $sale_completed = false;
                break;
            case 'proceed':
                $id_checked = false;
                $id_result = 'not_required';
                $sale_completed = true;
                break;
        }
        
        // Get the actual detection mode from settings
        $detection_mode = get_option('age_estimator_mode', 'simple');
        
        // Insert a new record for the ID confirmation
        $data = array(
            'check_time' => current_time('mysql'),
            'staff_member' => sanitize_text_field($_POST['staff'] ?? 'Unknown'),
            'id_checked' => $id_checked,
            'id_result' => $id_result,
            'sale_completed' => $sale_completed,
            'notes' => 'ID confirmation: ' . $result,
            'detection_mode' => $detection_mode  // Use the actual mode setting
        );
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result === false) {
            wp_send_json_error(array(
                'message' => 'Failed to log ID confirmation',
                'details' => $wpdb->last_error
            ));
        } else {
            wp_send_json_success(array(
                'confirmation_id' => $wpdb->insert_id,
                'message' => 'ID confirmation logged successfully'
            ));
        }
    }
    
    /**
     * Handle simple mode scan logging
     */
    public function handle_log_simple_scan() {
        // Verify nonce
        if (!check_ajax_referer('age_estimator_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => 'Security check failed'
            ));
            return;
        }
        
        // Check if logging is enabled
        if (!get_option('age_estimator_enable_logging', false)) {
            wp_send_json_success(array(
                'message' => 'Logging disabled'
            ));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'age_estimator_checks';
        
        // Get current user info
        $user_id = get_current_user_id();
        $user_ip = $this->get_user_ip();
        $session_id = $this->get_session_id();
        
        // Prepare data
        $data = array(
            'check_time' => current_time('mysql'),
            'estimated_age' => intval($_POST['age'] ?? 0),
            'gender' => sanitize_text_field($_POST['gender'] ?? ''),
            'confidence' => floatval($_POST['confidence'] ?? 0),
            'detection_mode' => 'simple',
            'user_id' => $user_id,
            'user_ip' => $user_ip,
            'session_id' => $session_id,
            'face_detected' => intval($_POST['face_detected'] ?? 0),
            'age_gate_result' => sanitize_text_field($_POST['age_gate_result'] ?? ''),
            'capture_time' => sanitize_text_field($_POST['capture_time'] ?? ''),
            'averaged' => isset($_POST['averaged']) ? (bool)$_POST['averaged'] : false,
            'samples_count' => intval($_POST['samples_count'] ?? 0),
            'samples_range' => sanitize_text_field($_POST['samples_range'] ?? ''),
            'std_dev' => floatval($_POST['std_dev'] ?? 0)
        );
        
        // Insert into database
        $result = $wpdb->insert($table_name, $data);
        
        if ($result === false) {
            wp_send_json_error(array(
                'message' => 'Failed to log scan',
                'details' => $wpdb->last_error
            ));
        } else {
            wp_send_json_success(array(
                'scan_id' => $wpdb->insert_id,
                'message' => 'Scan logged successfully'
            ));
        }
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
}

// Initialize AJAX handler
new AgeEstimatorAjax();

// Backward compatibility
if (!class_exists('AgeEstimatorPhotoAjax')) {
    class AgeEstimatorPhotoAjax extends AgeEstimatorAjax {}
}
