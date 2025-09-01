/**
 * Admin JavaScript for Age Estimator Photo Plugin
 * Handles connection testing and admin interface interactions
 */

jQuery(document).ready(function($) {
    
    // AWS connection test
    $('#test-aws-connection').on('click', function() {
        const button = $(this);
        const resultDiv = $('#test-aws-result');
        
        button.prop('disabled', true).text('Testing...');
        resultDiv.html('<p style="color: #666;">Testing AWS connection...</p>');
        
        $.ajax({
            url: ageEstimatorPhotoAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'test_aws_connection_photo',
                nonce: ageEstimatorPhotoAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    resultDiv.html('<p style="color: green;"><strong>✓ ' + response.data.message + '</strong></p>');
                } else {
                    let errorMessage = 'Connection test failed';
                    if (response.data && response.data.message) {
                        errorMessage = response.data.message;
                    }
                    resultDiv.html('<p style="color: red;"><strong>✗ ' + errorMessage + '</strong></p>');
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Connection test failed';
                
                // Try to parse the response for a better error message
                try {
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage = xhr.responseJSON.data.message;
                    } else if (xhr.responseText) {
                        errorMessage = 'Server error: ' + status;
                    }
                } catch (e) {
                    errorMessage = 'Connection test failed: ' + error;
                }
                
                resultDiv.html('<p style="color: red;"><strong>✗ ' + errorMessage + '</strong></p>');
            },
            complete: function() {
                button.prop('disabled', false).text('Test AWS Connection');
            }
        });
    });
    
    // Show/hide age gating options based on checkbox
    $('input[name="age_estimator_enable_age_gate"]').on('change', function() {
        const ageGateSection = $(this).closest('form').find('tr').filter(function() {
            return $(this).find('input[name*="minimum_age"], input[name*="age_gate_message"], input[name*="age_gate_redirect"]').length > 0;
        });
        
        if ($(this).is(':checked')) {
            ageGateSection.show();
        } else {
            ageGateSection.hide();
        }
    }).trigger('change');
    
    // Show/hide consent options based on checkbox
    $('input[name="age_estimator_require_consent"]').on('change', function() {
        const consentSection = $(this).closest('form').find('tr').filter(function() {
            return $(this).find('textarea[name*="consent_text"]').length > 0;
        });
        
        if ($(this).is(':checked')) {
            consentSection.show();
        } else {
            consentSection.hide();
        }
    }).trigger('change');
    
    // Dynamic help text for age settings
    $('input[name="age_estimator_minimum_age"]').on('input', function() {
        const age = parseInt($(this).val());
        const helpText = $(this).next('.description');
        
        if (age === 18) {
            helpText.html('Minimum age required to access content (13-99 years). <strong>Current: 18 (general adult content)</strong>');
        } else if (age === 21) {
            helpText.html('Minimum age required to access content (13-99 years). <strong>Current: 21 (alcohol/tobacco content)</strong>');
        } else {
            helpText.html('Minimum age required to access content (13-99 years). Current: ' + age + ' years');
        }
    });
    
    // Dynamic help text for data retention
    $('input[name="age_estimator_data_retention_hours"]').on('input', function() {
        const hours = parseInt($(this).val());
        const helpText = $(this).next('.description');
        
        if (hours === 0) {
            helpText.html('Hours to retain facial image data (0 = immediate deletion recommended). <strong>Privacy Best Practice:</strong> Set to 0 for immediate deletion after verification. Current: <strong>Immediate deletion</strong>');
        } else if (hours === 24) {
            helpText.html('Hours to retain facial image data (0 = immediate deletion recommended). <strong>Privacy Best Practice:</strong> Set to 0 for immediate deletion after verification. Current: <strong>1 day</strong>');
        } else if (hours === 168) {
            helpText.html('Hours to retain facial image data (0 = immediate deletion recommended). <strong>Privacy Best Practice:</strong> Set to 0 for immediate deletion after verification. Current: <strong>1 week</strong>');
        } else {
            helpText.html('Hours to retain facial image data (0 = immediate deletion recommended). <strong>Privacy Best Practice:</strong> Set to 0 for immediate deletion after verification. Current: <strong>' + hours + ' hours</strong>');
        }
    });
    
    // Warning for high data retention
    $('input[name="age_estimator_data_retention_hours"]').on('input', function() {
        const hours = parseInt($(this).val());
        const warningDiv = $(this).siblings('.retention-warning');
        
        if (hours > 72) { // More than 3 days
            if (warningDiv.length === 0) {
                $(this).after('<div class="retention-warning" style="color: #d54e21; font-weight: bold; margin-top: 5px;">⚠️ Warning: Extended data retention may not comply with privacy regulations</div>');
            }
        } else {
            warningDiv.remove();
        }
    });
    
    // Copy shortcode functionality
    $('.shortcode-copy').on('click', function() {
        const shortcode = $(this).prev('code').text();
        navigator.clipboard.writeText(shortcode).then(function() {
            alert('Shortcode copied to clipboard!');
        }).catch(function() {
            // Fallback for older browsers
            const tempInput = $('<input>');
            $('body').append(tempInput);
            tempInput.val(shortcode).select();
            document.execCommand('copy');
            tempInput.remove();
            alert('Shortcode copied to clipboard!');
        });
    });
    
    // Real-time configuration status updates
    function updateConfigStatus() {
        const mode = $('select[name="age_estimator_mode"]').val();
        const enableAgeGate = $('input[name="age_estimator_enable_age_gate"]').is(':checked');
        const requireConsent = $('input[name="age_estimator_require_consent"]').is(':checked');
        
        // Update detection method status
        let detectionMethod = 'Simple (Face-API.js)';
        if (mode === 'aws') {
            detectionMethod = 'AWS Rekognition';
        }
        
        // Create or update status panel
        let statusPanel = $('.config-status-panel');
        if (statusPanel.length === 0) {
            $('h1').after('<div class="config-status-panel notice notice-info"><h3>Current Configuration</h3><div class="status-content"></div></div>');
            statusPanel = $('.config-status-panel');
        }
        
        const statusHtml = `
            <p><strong>Detection Method:</strong> ${detectionMethod}</p>
            <p><strong>Age Gating:</strong> ${enableAgeGate ? 'Enabled' : 'Disabled'}</p>
            <p><strong>GDPR Consent:</strong> ${requireConsent ? 'Required' : 'Optional'}</p>
        `;
        
        statusPanel.find('.status-content').html(statusHtml);
    }
    
    // Update status when relevant fields change
    $('select[name="age_estimator_mode"], input[name="age_estimator_enable_age_gate"], input[name="age_estimator_require_consent"]').on('change', updateConfigStatus);
    
    // Initial status update
    updateConfigStatus();
    
    // Kiosk mode toggle
    const kioskModeCheckbox = $('#age_estimator_kiosk_mode');
    const kioskSettings = $('.kiosk-settings');
    
    if (kioskModeCheckbox.length) {
        kioskModeCheckbox.on('change', function() {
            if (this.checked) {
                kioskSettings.show();
            } else {
                kioskSettings.hide();
            }
        });
    }
    
    // Media uploader for kiosk image
    const uploadButton = $('#upload-kiosk-image');
    if (uploadButton.length) {
        uploadButton.on('click', function(e) {
            e.preventDefault();
            
            // Create media uploader
            const mediaUploader = wp.media({
                title: 'Choose Advertisement Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            // When an image is selected
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#age_estimator_kiosk_image').val(attachment.url);
                
                // Update or create preview
                let preview = $('.kiosk-image-preview');
                if (preview.length === 0) {
                    uploadButton.parent().append('<div class="kiosk-image-preview" style="margin-top: 10px;"></div>');
                    preview = $('.kiosk-image-preview');
                }
                preview.html('<img src="' + attachment.url + '" style="max-width: 300px; height: auto; border: 1px solid #ddd;" />');
            });
            
            // Open the media uploader
            mediaUploader.open();
        });
    }
});
