<?php
/**
 * AWS Rekognition API handler for Age Estimator Photo Plugin
 * Provides age estimation using Amazon Rekognition service
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AgeEstimatorPhotoAWSAPI {
    
    private $access_key;
    private $secret_key;
    private $region;
    private $endpoint;
    
    public function __construct($access_key, $secret_key, $region = 'us-east-1') {
        $this->access_key = $access_key;
        $this->secret_key = $secret_key;
        $this->region = $region;
        $this->endpoint = "https://rekognition.{$region}.amazonaws.com/";
        
        error_log('AWS Rekognition API initialized for region: ' . $region);
    }
    
    /**
     * Validate AWS credentials by making a test request
     */
    public function validate_credentials() {
        try {
            // Make a simple test request to validate credentials
            $test_image = $this->create_test_image();
            $response = $this->detect_face($test_image);
            
            // If we don't get a WP_Error, credentials are valid
            return !is_wp_error($response);
        } catch (Exception $e) {
            error_log('AWS credentials validation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a minimal test image for credential validation
     */
    private function create_test_image() {
        // Create a minimal 1x1 pixel PNG
        $image = imagecreatetruecolor(1, 1);
        ob_start();
        imagepng($image);
        $image_data = ob_get_clean();
        imagedestroy($image);
        return $image_data;
    }
    
    /**
     * Detect faces and estimate age using AWS Rekognition
     */
    public function detect_face($image_data) {
        error_log('AWS Rekognition: Starting face detection...');
        error_log('AWS Rekognition: Image data length = ' . strlen($image_data) . ' bytes');
        
        // Debug: Check what type of data we received
        if (is_string($image_data)) {
            $first_chars = substr($image_data, 0, 50);
            error_log('AWS Rekognition: First 50 chars of image_data: ' . $first_chars);
        }
        
        try {
            // Convert image data to binary if needed
            if (is_string($image_data) && strpos($image_data, 'data:image') === 0) {
                error_log('AWS Rekognition: Processing data URL format');
                $image_binary = $this->process_image_data($image_data);
            } else {
                error_log('AWS Rekognition: Using raw image data');
                $image_binary = $image_data;
            }
            
            if (!$image_binary) {
                error_log('AWS Rekognition: Failed to process image data');
                return new WP_Error('image_processing_failed', 'Failed to process image data');
            }
            
            error_log('AWS Rekognition: Binary image size = ' . strlen($image_binary) . ' bytes');
            
            // Prepare the request
            $request_data = array(
                'Image' => array(
                    'Bytes' => base64_encode($image_binary)
                ),
                'Attributes' => array('ALL') // Get all face attributes including age range
            );
            
            // Make the API request
            $response = $this->make_api_request('DetectFaces', $request_data);
            
            if (is_wp_error($response)) {
                error_log('AWS Rekognition API error: ' . $response->get_error_message());
                return $response;
            }
            
            // Process the response
            $faces = $this->process_faces_response($response);
            
            error_log('AWS Rekognition: Detected ' . count($faces) . ' face(s)');
            error_log('AWS Rekognition: Faces data = ' . print_r($faces, true));
            
            return $faces;
            
        } catch (Exception $e) {
            error_log('AWS Rekognition exception: ' . $e->getMessage());
            return new WP_Error('detection_failed', 'Face detection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Process image data from various formats
     */
    private function process_image_data($image_data) {
        // Handle data URL format
        if (strpos($image_data, 'data:image') === 0) {
            $parts = explode(',', $image_data);
            if (count($parts) === 2) {
                return base64_decode($parts[1]);
            }
        }
        
        // Handle direct base64
        $binary = base64_decode($image_data, true);
        if ($binary !== false) {
            return $binary;
        }
        
        error_log('AWS Rekognition: Failed to process image data format');
        return false;
    }
    
    /**
     * Make authenticated API request to AWS Rekognition
     */
    private function make_api_request($operation, $data) {
        $timestamp = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');
        
        $json_data = json_encode($data);
        
        // Create AWS Signature Version 4
        $headers = array(
            'Content-Type' => 'application/x-amz-json-1.1',
            'X-Amz-Target' => 'RekognitionService.' . $operation,
            'X-Amz-Date' => $timestamp,
            'Authorization' => $this->create_authorization_header($operation, $json_data, $timestamp, $date)
        );
        
        // Make the HTTP request
        $response = wp_remote_post($this->endpoint, array(
            'headers' => $headers,
            'body' => $json_data,
            'timeout' => 30,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            error_log('AWS Rekognition HTTP error: ' . $response->get_error_message());
            return new WP_Error('http_error', 'Failed to connect to AWS: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log('AWS Rekognition response status: ' . $status_code);
        
        if ($status_code !== 200) {
            $error_data = json_decode($body, true);
            $error_message = 'Unknown AWS error';
            
            if (is_array($error_data)) {
                if (isset($error_data['message'])) {
                    $error_message = $error_data['message'];
                } elseif (isset($error_data['__type'])) {
                    // Handle specific AWS error types
                    $error_type = $error_data['__type'];
                    if (strpos($error_type, 'InvalidSignatureException') !== false) {
                        $error_message = 'Invalid AWS credentials. Please check your Access Key and Secret Key.';
                    } elseif (strpos($error_type, 'UnrecognizedClientException') !== false) {
                        $error_message = 'AWS Access Key not recognized. Please verify your credentials.';
                    } elseif (strpos($error_type, 'AccessDeniedException') !== false) {
                        $error_message = 'Access denied. Please ensure your AWS user has Rekognition permissions.';
                    } elseif (strpos($error_type, 'InvalidParameterException') !== false) {
                        $error_message = 'Invalid request parameters. ' . (isset($error_data['message']) ? $error_data['message'] : '');
                    } else {
                        $error_message = 'AWS Error: ' . str_replace('#', '', $error_type);
                    }
                }
            } else {
                // If we can't parse the error, show the raw response
                $error_message = 'AWS API error (Status ' . $status_code . ')';
                if (!empty($body)) {
                    $error_message .= ': ' . substr($body, 0, 200);
                }
            }
            
            error_log('AWS Rekognition error details: ' . print_r($error_data, true));
            
            return new WP_Error('aws_api_error', $error_message, array('status' => $status_code));
        }
        
        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_decode_error', 'Failed to parse AWS response');
        }
        
        error_log('AWS Rekognition raw response: ' . print_r($decoded, true));
        
        return $decoded;
    }
    
    /**
     * Create AWS Authorization header
     */
    private function create_authorization_header($operation, $payload, $timestamp, $date) {
        $service = 'rekognition';
        $algorithm = 'AWS4-HMAC-SHA256';
        
        // Create canonical request
        $canonical_headers = "content-type:application/x-amz-json-1.1\n";
        $canonical_headers .= "host:rekognition.{$this->region}.amazonaws.com\n";
        $canonical_headers .= "x-amz-date:{$timestamp}\n";
        $canonical_headers .= "x-amz-target:RekognitionService.{$operation}\n";
        
        $signed_headers = 'content-type;host;x-amz-date;x-amz-target';
        $payload_hash = hash('sha256', $payload);
        
        $canonical_request = "POST\n/\n\n{$canonical_headers}\n{$signed_headers}\n{$payload_hash}";
        
        // Create string to sign
        $credential_scope = "{$date}/{$this->region}/{$service}/aws4_request";
        $string_to_sign = "{$algorithm}\n{$timestamp}\n{$credential_scope}\n" . hash('sha256', $canonical_request);
        
        // Create signing key
        $signing_key = $this->get_signing_key($date, $service);
        
        // Create signature
        $signature = hash_hmac('sha256', $string_to_sign, $signing_key);
        
        // Create authorization header
        return "{$algorithm} Credential={$this->access_key}/{$credential_scope}, SignedHeaders={$signed_headers}, Signature={$signature}";
    }
    
    /**
     * Generate AWS signing key
     */
    private function get_signing_key($date, $service) {
        $k_date = hash_hmac('sha256', $date, 'AWS4' . $this->secret_key, true);
        $k_region = hash_hmac('sha256', $this->region, $k_date, true);
        $k_service = hash_hmac('sha256', $service, $k_region, true);
        return hash_hmac('sha256', 'aws4_request', $k_service, true);
    }
    
    /**
     * Process AWS Rekognition faces response
     */
    private function process_faces_response($response) {
        $faces = array();
        
        error_log('AWS Rekognition process_faces_response input: ' . print_r($response, true));
        
        if (!isset($response['FaceDetails']) || !is_array($response['FaceDetails'])) {
            error_log('AWS Rekognition: No FaceDetails in response');
            return $faces;
        }
        
        error_log('AWS Rekognition: Found ' . count($response['FaceDetails']) . ' FaceDetails');
        
        foreach ($response['FaceDetails'] as $face_detail) {
            $face = array(
                'confidence' => isset($face_detail['Confidence']) ? round($face_detail['Confidence'], 2) : 0,
                'boundingBox' => $this->format_bounding_box($face_detail),
                'ageRange' => isset($face_detail['AgeRange']) ? $face_detail['AgeRange'] : null,
                'age' => $this->extract_age_estimate($face_detail),
                'gender' => $this->extract_gender($face_detail),
                'emotions' => $this->extract_emotions($face_detail),
                'attributes' => $this->extract_attributes($face_detail)
            );
            
            $faces[] = $face;
        }
        
        return $faces;
    }
    
    /**
     * Extract age estimate from face details
     */
    private function extract_age_estimate($face_detail) {
        if (isset($face_detail['AgeRange'])) {
            $low = isset($face_detail['AgeRange']['Low']) ? $face_detail['AgeRange']['Low'] : 0;
            $high = isset($face_detail['AgeRange']['High']) ? $face_detail['AgeRange']['High'] : 0;
            
            // Return the middle of the range as estimated age
            return round(($low + $high) / 2);
        }
        
        return 0;
    }
    
    /**
     * Extract gender information
     */
    private function extract_gender($face_detail) {
        if (isset($face_detail['Gender'])) {
            return array(
                'value' => strtolower($face_detail['Gender']['Value']),
                'confidence' => round($face_detail['Gender']['Confidence'], 2)
            );
        }
        
        return array('value' => 'unknown', 'confidence' => 0);
    }
    
    /**
     * Extract emotions
     */
    private function extract_emotions($face_detail) {
        $emotions = array();
        
        if (isset($face_detail['Emotions']) && is_array($face_detail['Emotions'])) {
            foreach ($face_detail['Emotions'] as $emotion) {
                $emotions[strtolower($emotion['Type'])] = round($emotion['Confidence'], 2);
            }
        }
        
        return $emotions;
    }
    
    /**
     * Extract other face attributes
     */
    private function extract_attributes($face_detail) {
        $attributes = array();
        
        // Sunglasses
        if (isset($face_detail['Sunglasses'])) {
            $attributes['sunglasses'] = array(
                'value' => $face_detail['Sunglasses']['Value'],
                'confidence' => round($face_detail['Sunglasses']['Confidence'], 2)
            );
        }
        
        // Eyeglasses
        if (isset($face_detail['Eyeglasses'])) {
            $attributes['eyeglasses'] = array(
                'value' => $face_detail['Eyeglasses']['Value'],
                'confidence' => round($face_detail['Eyeglasses']['Confidence'], 2)
            );
        }
        
        // Smile
        if (isset($face_detail['Smile'])) {
            $attributes['smile'] = array(
                'value' => $face_detail['Smile']['Value'],
                'confidence' => round($face_detail['Smile']['Confidence'], 2)
            );
        }
        
        // Beard
        if (isset($face_detail['Beard'])) {
            $attributes['beard'] = array(
                'value' => $face_detail['Beard']['Value'],
                'confidence' => round($face_detail['Beard']['Confidence'], 2)
            );
        }
        
        // Mustache
        if (isset($face_detail['Mustache'])) {
            $attributes['mustache'] = array(
                'value' => $face_detail['Mustache']['Value'],
                'confidence' => round($face_detail['Mustache']['Confidence'], 2)
            );
        }
        
        return $attributes;
    }
    
    /**
     * Format bounding box for consistency with other APIs
     */
    private function format_bounding_box($face_detail) {
        if (!isset($face_detail['BoundingBox'])) {
            return array('left' => 0, 'top' => 0, 'width' => 0, 'height' => 0);
        }
        
        $bbox = $face_detail['BoundingBox'];
        
        return array(
            'left' => isset($bbox['Left']) ? $bbox['Left'] : 0,
            'top' => isset($bbox['Top']) ? $bbox['Top'] : 0,
            'width' => isset($bbox['Width']) ? $bbox['Width'] : 0,
            'height' => isset($bbox['Height']) ? $bbox['Height'] : 0
        );
    }
}

// Add compatibility wrapper for expected class name
if (!class_exists('AgeEstimatorAWSRekognition')) {
    class AgeEstimatorAWSRekognition {
        private $api;
        
        public function __construct($access_key, $secret_key, $region = 'us-east-1') {
            $this->api = new AgeEstimatorPhotoAWSAPI($access_key, $secret_key, $region);
        }
        
        public function detect_faces($image_binary) {
            $result = $this->api->detect_face($image_binary);
            
            if (is_wp_error($result)) {
                return array(
                    'success' => false,
                    'message' => $result->get_error_message(),
                    'details' => $result->get_error_data()
                );
            }
            
            // Return the expected format with faces array
            return array(
                'success' => true,
                'data' => array(
                    'faces' => $result
                )
            );
        }
        
        public function test_connection() {
            try {
                $is_valid = $this->api->validate_credentials();
                
                if ($is_valid) {
                    return array(
                        'success' => true,
                        'message' => 'AWS connection successful! Your credentials are valid.'
                    );
                }
                
                return array(
                    'success' => false,
                    'message' => 'Failed to connect to AWS Rekognition. Please check your credentials and ensure your AWS user has Rekognition permissions.'
                );
            } catch (Exception $e) {
                return array(
                    'success' => false,
                    'message' => 'Connection error: ' . $e->getMessage()
                );
            }
        }
    }
}
