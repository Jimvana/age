<?php
/**
 * Real-time PIN Debug Tool
 * This will show us exactly what's happening during PIN validation
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

// Add AJAX debug handler
add_action('wp_ajax_debug_pin_verification', function() {
    $user_id = get_current_user_id();
    $entered_pin = sanitize_text_field($_POST['pin'] ?? '');
    
    // Get stored PIN
    $stored_pin = get_user_meta($user_id, 'age_estimator_retail_pin', true);
    
    $debug_info = array(
        'user_id' => $user_id,
        'entered_pin' => $entered_pin,
        'stored_pin_exists' => !empty($stored_pin),
        'stored_pin_length' => strlen($stored_pin),
        'stored_pin_preview' => !empty($stored_pin) ? substr($stored_pin, 0, 20) . '...' : 'empty',
        'is_hashed' => (!empty($stored_pin) && strlen($stored_pin) > 10 && strpos($stored_pin, '$') !== false),
        'wp_check_result' => false,
        'plain_comparison' => false
    );
    
    if (!empty($stored_pin)) {
        // Test hashed comparison
        $debug_info['wp_check_result'] = wp_check_password($entered_pin, $stored_pin);
        
        // Test plain comparison
        $debug_info['plain_comparison'] = ($entered_pin === $stored_pin);
    }
    
    wp_send_json_success($debug_info);
});

// Only show this tool if user is logged in
if (!is_user_logged_in()) {
    echo '<h1>Please log in first</h1>';
    echo '<p><a href="' . wp_login_url($_SERVER['REQUEST_URI']) . '">Login</a></p>';
    exit;
}

$current_user = wp_get_current_user();
?>
<!DOCTYPE html>
<html>
<head>
    <title>PIN Debug Tool</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f0f0f0;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .debug-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .debug-results {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007cba;
        }
        .error-result {
            background: #ffe7e7;
            border-left-color: #dc3545;
        }
        .success-result {
            background: #e7ffe7;
            border-left-color: #28a745;
        }
        input[type="password"] {
            padding: 12px;
            font-size: 18px;
            border: 2px solid #ddd;
            border-radius: 6px;
            width: 150px;
            text-align: center;
            font-family: monospace;
            letter-spacing: 3px;
        }
        button {
            background: #007cba;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
        }
        button:hover {
            background: #005a87;
        }
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .comparison-table th,
        .comparison-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .comparison-table th {
            background: #f5f5f5;
        }
        .status-good { color: #28a745; font-weight: bold; }
        .status-bad { color: #dc3545; font-weight: bold; }
        .status-neutral { color: #6c757d; }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container">
        <h1>🔍 Real-Time PIN Debug</h1>
        <p><strong>User:</strong> <?php echo $current_user->display_name; ?> (ID: <?php echo get_current_user_id(); ?>)</p>
        
        <div class="debug-form">
            <h3>Test Your PIN</h3>
            <p>Enter your PIN below to see exactly what happens during validation:</p>
            
            <form id="debug-pin-form">
                <input type="password" 
                       id="debug-pin" 
                       placeholder="1234" 
                       maxlength="4" 
                       pattern="\d{4}" 
                       required
                       autocomplete="off">
                <button type="submit">🧪 Debug PIN</button>
            </form>
        </div>
        
        <div id="debug-results"></div>
        
        <div id="real-ajax-test" style="margin-top: 30px;">
            <h3>🎯 Real AJAX Test</h3>
            <p>This will call the exact same AJAX endpoint that your settings page uses:</p>
            <button id="test-real-ajax">Test Real Settings PIN Verification</button>
            <div id="real-ajax-results"></div>
        </div>
    </div>
    
    <script>
        // Debug form submission
        $('#debug-pin-form').on('submit', function(e) {
            e.preventDefault();
            
            const pin = $('#debug-pin').val();
            if (!/^\d{4}$/.test(pin)) {
                alert('Please enter exactly 4 digits');
                return;
            }
            
            // Show loading
            $('#debug-results').html('<div class="debug-results"><h3>🔄 Testing...</h3></div>');
            
            $.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                type: 'POST',
                data: {
                    action: 'debug_pin_verification',
                    pin: pin,
                    nonce: 'debug'
                },
                success: function(response) {
                    displayDebugResults(response.data);
                },
                error: function(xhr, status, error) {
                    $('#debug-results').html(`
                        <div class="debug-results error-result">
                            <h3>❌ AJAX Error</h3>
                            <p><strong>Error:</strong> ${error}</p>
                            <p><strong>Status:</strong> ${status}</p>
                            <p><strong>Response:</strong> ${xhr.responseText}</p>
                        </div>
                    `);
                }
            });
        });
        
        function displayDebugResults(data) {
            const status = data.wp_check_result ? 'success-result' : 'error-result';
            
            let html = `
                <div class="debug-results ${status}">
                    <h3>${data.wp_check_result ? '✅ PIN Valid' : '❌ PIN Invalid'}</h3>
                    
                    <table class="comparison-table">
                        <tr>
                            <th>Property</th>
                            <th>Value</th>
                            <th>Status</th>
                        </tr>
                        <tr>
                            <td>User ID</td>
                            <td>${data.user_id}</td>
                            <td><span class="status-neutral">OK</span></td>
                        </tr>
                        <tr>
                            <td>Entered PIN</td>
                            <td>${data.entered_pin}</td>
                            <td><span class="status-neutral">4 digits</span></td>
                        </tr>
                        <tr>
                            <td>Stored PIN Exists</td>
                            <td>${data.stored_pin_exists ? 'Yes' : 'No'}</td>
                            <td><span class="status-${data.stored_pin_exists ? 'good' : 'bad'}">${data.stored_pin_exists ? 'Good' : 'Problem!'}</span></td>
                        </tr>
                        <tr>
                            <td>PIN Length</td>
                            <td>${data.stored_pin_length} characters</td>
                            <td><span class="status-${data.stored_pin_length > 10 ? 'good' : 'bad'}">${data.stored_pin_length > 10 ? 'Hashed' : 'Plain Text?'}</span></td>
                        </tr>
                        <tr>
                            <td>Is Properly Hashed</td>
                            <td>${data.is_hashed ? 'Yes' : 'No'}</td>
                            <td><span class="status-${data.is_hashed ? 'good' : 'bad'}">${data.is_hashed ? 'Good' : 'Needs Fix'}</span></td>
                        </tr>
                        <tr>
                            <td>WordPress Check Result</td>
                            <td>${data.wp_check_result ? 'PASS' : 'FAIL'}</td>
                            <td><span class="status-${data.wp_check_result ? 'good' : 'bad'}">${data.wp_check_result ? 'PIN Correct' : 'PIN Wrong'}</span></td>
                        </tr>
                        <tr>
                            <td>Plain Text Comparison</td>
                            <td>${data.plain_comparison ? 'MATCH' : 'NO MATCH'}</td>
                            <td><span class="status-neutral">Info Only</span></td>
                        </tr>
                    </table>
                    
                    <div style="margin-top: 20px; padding: 15px; background: rgba(0,0,0,0.05); border-radius: 6px;">
                        <strong>Stored PIN Preview:</strong> <code>${data.stored_pin_preview}</code>
                    </div>
            `;
            
            if (data.wp_check_result) {
                html += `
                    <div style="margin-top: 15px; padding: 15px; background: rgba(40, 167, 69, 0.1); border-radius: 6px; color: #155724;">
                        <strong>✅ Diagnosis:</strong> Your PIN is working correctly! The issue must be with the frontend JavaScript or AJAX endpoint.
                    </div>
                `;
            } else {
                html += `
                    <div style="margin-top: 15px; padding: 15px; background: rgba(220, 53, 69, 0.1); border-radius: 6px; color: #721c24;">
                        <strong>❌ Diagnosis:</strong> The PIN validation is failing. ${!data.stored_pin_exists ? 'No PIN found in database.' : !data.is_hashed ? 'PIN might not be properly hashed.' : 'PIN doesn\'t match what you entered.'}
                    </div>
                `;
            }
            
            html += '</div>';
            $('#debug-results').html(html);
        }
        
        // Test the real AJAX endpoint
        $('#test-real-ajax').on('click', function() {
            const pin = $('#debug-pin').val();
            if (!pin) {
                alert('Please enter a PIN first in the form above');
                return;
            }
            
            $('#real-ajax-results').html('<div style="padding: 15px; background: #f8f9fa; margin-top: 15px; border-radius: 6px;"><strong>🔄 Testing real AJAX endpoint...</strong></div>');
            
            // This is the exact call that the settings page makes
            $.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                type: 'POST',
                data: {
                    action: 'age_estimator_verify_settings_pin',
                    pin: pin,
                    nonce: '<?php echo wp_create_nonce("age_estimator_pin_protection"); ?>'
                },
                success: function(response) {
                    const status = response.success ? 'success-result' : 'error-result';
                    $('#real-ajax-results').html(`
                        <div class="debug-results ${status}" style="margin-top: 15px;">
                            <h4>${response.success ? '✅ Real AJAX: SUCCESS' : '❌ Real AJAX: FAILED'}</h4>
                            <p><strong>Message:</strong> ${response.data ? response.data.message || JSON.stringify(response.data) : 'No message'}</p>
                            <details>
                                <summary>Full Response</summary>
                                <pre>${JSON.stringify(response, null, 2)}</pre>
                            </details>
                        </div>
                    `);
                },
                error: function(xhr, status, error) {
                    $('#real-ajax-results').html(`
                        <div class="debug-results error-result" style="margin-top: 15px;">
                            <h4>❌ Real AJAX: ERROR</h4>
                            <p><strong>Error:</strong> ${error}</p>
                            <p><strong>Status:</strong> ${status}</p>
                            <details>
                                <summary>Full Response</summary>
                                <pre>${xhr.responseText}</pre>
                            </details>
                        </div>
                    `);
                }
            });
        });
    </script>
</body>
</html>
