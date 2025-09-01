/**
 * Kiosk Mode Admin JavaScript
 * Add this to your admin.js file
 */

// Add this code to your existing admin.js file
document.addEventListener('DOMContentLoaded', function() {
    // Kiosk mode toggle
    const kioskModeCheckbox = document.querySelector('input[name="age_estimator_kiosk_mode"]');
    const kioskSettings = document.querySelectorAll('.kiosk-settings');
    
    if (kioskModeCheckbox) {
        kioskModeCheckbox.addEventListener('change', function() {
            kioskSettings.forEach(function(element) {
                element.style.display = this.checked ? 'block' : 'none';
            }, this);
        });
    }
    
    // Media uploader for kiosk image
    const uploadButton = document.getElementById('upload-kiosk-image');
    if (uploadButton) {
        uploadButton.addEventListener('click', function(e) {
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
                document.getElementById('age_estimator_kiosk_image').value = attachment.url;
                
                // Update or create preview
                let preview = document.querySelector('.kiosk-image-preview');
                if (!preview) {
                    preview = document.createElement('div');
                    preview.className = 'kiosk-image-preview';
                    uploadButton.parentNode.appendChild(preview);
                }
                preview.innerHTML = '<img src="' + attachment.url + '" style="max-width: 300px; height: auto; border: 1px solid #ddd;" />';
            });
            
            // Open the media uploader
            mediaUploader.open();
        });
    }
});