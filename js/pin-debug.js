/**
 * Simple PIN Test for Age Estimator Settings
 * Add this JavaScript to debug PIN saving issues
 */

console.log('🔧 PIN Debug Tools Loaded');

// Override form submission to debug
jQuery(document).ready(function($) {
    
    // Monitor all form submissions
    $(document).on('submit', '.settings-form', function(e) {
        console.log('🚀 Form submitted:', e.target);
        const $form = $(this);
        const section = $form.data('section');
        console.log('📋 Section:', section);
        
        if (section === 'retail') {
            console.log('🏪 RETAIL FORM DETECTED');
            
            // Check if PIN fields exist
            const $pinField = $form.find('[name="retail_pin"]');
            const $confirmField = $form.find('[name="retail_pin_confirm"]');
            
            console.log('🔐 PIN field found:', $pinField.length > 0);
            console.log('🔐 Confirm field found:', $confirmField.length > 0);
            
            if ($pinField.length > 0) {
                console.log('🔐 PIN value:', $pinField.val());
                console.log('🔐 PIN type:', $pinField.attr('type'));
            }
            
            if ($confirmField.length > 0) {
                console.log('🔐 Confirm value:', $confirmField.val());
                console.log('🔐 Confirm type:', $confirmField.attr('type'));
            }
            
            // Get all form data
            const formData = {};
            $form.find('input, select, textarea').each(function() {
                const $input = $(this);
                const name = $input.attr('name');
                if (name) {
                    if ($input.attr('type') === 'checkbox') {
                        formData[name] = $input.is(':checked');
                    } else {
                        formData[name] = $input.val();
                    }
                }
            });
            
            console.log('📋 Complete form data:', formData);
            
            // Check retail mode enabled
            console.log('🏪 Retail mode enabled:', formData.retail_mode_enabled);
            
            // Validate PIN
            if (formData.retail_pin) {
                console.log('🔐 PIN validation:');
                console.log('  - Length:', formData.retail_pin.length);
                console.log('  - Is numeric:', /^\d+$/.test(formData.retail_pin));
                console.log('  - Is 4 digits:', /^\d{4}$/.test(formData.retail_pin));
                
                if (formData.retail_pin_confirm) {
                    console.log('  - Matches confirm:', formData.retail_pin === formData.retail_pin_confirm);
                }
            }
        }
    });
    
    // Monitor AJAX requests
    $(document).ajaxSend(function(event, xhr, settings) {
        if (settings.data && settings.data.indexOf('age_estimator_save_user_settings') > -1) {
            console.log('📡 AJAX Request Sent:', settings.data);
        }
    });
    
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.data && settings.data.indexOf('age_estimator_save_user_settings') > -1) {
            console.log('📡 AJAX Response:', xhr.responseText);
            try {
                const response = JSON.parse(xhr.responseText);
                console.log('📡 Parsed Response:', response);
                
                if (response.success) {
                    console.log('✅ Save successful!', response.data);
                } else {
                    console.log('❌ Save failed!', response.data);
                }
            } catch (e) {
                console.log('⚠️ Could not parse response');
            }
        }
    });
    
    // Add test buttons
    if ($('[data-section="retail"]').length > 0) {
        const $testDiv = $('<div style="margin: 20px 0; padding: 15px; background: #f0f8ff; border: 1px solid #0073aa; border-radius: 5px;"></div>');
        $testDiv.html(`
            <h4 style="margin: 0 0 10px 0; color: #0073aa;">🔧 PIN Debug Tools</h4>
            <button type="button" id="test-pin-form-data" style="margin-right: 10px;">Test Form Data</button>
            <button type="button" id="test-pin-save" style="margin-right: 10px;">Test Direct Save</button>
            <button type="button" id="clear-console">Clear Console</button>
        `);
        
        $('[data-section="retail"] .button-group').before($testDiv);
        
        // Test form data
        $('#test-pin-form-data').click(function() {
            console.log('🧪 TESTING FORM DATA COLLECTION');
            const $form = $('.settings-form[data-section="retail"]');
            const data = {};
            
            $form.find('input, select, textarea').each(function() {
                const $input = $(this);
                const name = $input.attr('name');
                if (name) {
                    if ($input.attr('type') === 'checkbox') {
                        data[name] = $input.is(':checked');
                    } else {
                        data[name] = $input.val();
                    }
                    console.log(`  ${name} = ${data[name]} (${$input.attr('type')})`);
                }
            });
            
            console.log('📋 Final data:', data);
        });
        
        // Test direct save
        $('#test-pin-save').click(function() {
            console.log('🧪 TESTING DIRECT PIN SAVE');
            
            const testData = {
                retail_mode_enabled: true,
                challenge_age: 25,
                retail_pin: '1234',
                enable_logging: true
            };
            
            console.log('📡 Sending test data:', testData);
            
            if (typeof ageEstimatorEnhanced === 'undefined') {
                console.log('❌ ageEstimatorEnhanced not available');
                return;
            }
            
            $.ajax({
                url: ageEstimatorEnhanced.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'age_estimator_save_user_settings',
                    nonce: ageEstimatorEnhanced.nonce,
                    section: 'retail',
                    settings: testData
                },
                success: function(response) {
                    console.log('✅ Direct test SUCCESS:', response);
                    alert('Direct PIN save test passed! Check console for details.');
                },
                error: function(xhr, status, error) {
                    console.log('❌ Direct test ERROR:', error);
                    alert('Direct PIN save test failed! Check console for details.');
                }
            });
        });
        
        // Clear console
        $('#clear-console').click(function() {
            console.clear();
            console.log('🧹 Console cleared - PIN debug tools ready');
        });
    }
});

console.log('✅ PIN Debug Tools Ready - Look for retail section on page');
