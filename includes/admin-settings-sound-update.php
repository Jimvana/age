<?php
/**
 * Sound Notification Settings Update for Age Estimator Plugin
 * Add this code to your existing admin-settings.php file
 */

// Add these to your register_settings() function:

// Sound Notifications Section
add_settings_section(
    'age_estimator_sound_section',
    'Sound Notifications',
    'age_estimator_sound_section_callback',
    'age-estimator-settings'
);

// Enable Sound Notifications
register_setting('age_estimator_settings', 'age_estimator_enable_sounds', array(
    'type' => 'boolean',
    'sanitize_callback' => 'rest_sanitize_boolean',
    'default' => false
));

add_settings_field(
    'age_estimator_enable_sounds',
    'Enable Sound Notifications',
    'age_estimator_enable_sounds_callback',
    'age-estimator-settings',
    'age_estimator_sound_section'
);

// Pass Sound URL
register_setting('age_estimator_settings', 'age_estimator_pass_sound_url', array(
    'type' => 'string',
    'sanitize_callback' => 'esc_url_raw',
    'default' => ''
));

add_settings_field(
    'age_estimator_pass_sound_url',
    'Pass Sound',
    'age_estimator_pass_sound_callback',
    'age-estimator-settings',
    'age_estimator_sound_section'
);

// Fail Sound URL
register_setting('age_estimator_settings', 'age_estimator_fail_sound_url', array(
    'type' => 'string',
    'sanitize_callback' => 'esc_url_raw',
    'default' => ''
));

add_settings_field(
    'age_estimator_fail_sound_url',
    'Fail Sound',
    'age_estimator_fail_sound_callback',
    'age-estimator-settings',
    'age_estimator_sound_section'
);

// Sound Volume
register_setting('age_estimator_settings', 'age_estimator_sound_volume', array(
    'type' => 'number',
    'sanitize_callback' => 'age_estimator_sanitize_volume',
    'default' => 0.7
));

add_settings_field(
    'age_estimator_sound_volume',
    'Sound Volume',
    'age_estimator_sound_volume_callback',
    'age-estimator-settings',
    'age_estimator_sound_section'
);

// Callback functions for the new settings:

function age_estimator_sound_section_callback() {
    ?>
    <p>Configure sound notifications that play when age verification results are returned. 
    Sounds will be preloaded for instant playback.</p>
    <p class="description">Supported formats: MP3, WAV, OGG</p>
    <?php
}

function age_estimator_enable_sounds_callback() {
    $value = get_option('age_estimator_enable_sounds', false);
    ?>
    <label>
        <input type="checkbox" name="age_estimator_enable_sounds" value="1" <?php checked($value, true); ?>>
        Play sound notifications for pass/fail results
    </label>
    <?php
}

function age_estimator_pass_sound_callback() {
    $value = get_option('age_estimator_pass_sound_url', '');
    $upload_link = esc_url(admin_url('media-new.php'));
    ?>
    <input type="url" name="age_estimator_pass_sound_url" value="<?php echo esc_attr($value); ?>" 
           class="regular-text" placeholder="https://example.com/pass-sound.mp3">
    <button type="button" class="button age-estimator-media-button" data-target="age_estimator_pass_sound_url">
        Choose Sound
    </button>
    <button type="button" class="button age-estimator-play-sound" data-sound-url="<?php echo esc_attr($value); ?>" <?php echo empty($value) ? 'disabled' : ''; ?>>
        Test
    </button>
    <p class="description">
        Sound played when age verification passes (age ≥ minimum age). 
        <a href="<?php echo $upload_link; ?>" target="_blank">Upload new sound</a>
    </p>
    <div id="pass-sound-preview" style="margin-top: 10px;">
        <?php if (!empty($value)): ?>
            <audio id="pass-sound-audio" preload="auto">
                <source src="<?php echo esc_attr($value); ?>">
            </audio>
        <?php endif; ?>
    </div>
    <?php
}

function age_estimator_fail_sound_callback() {
    $value = get_option('age_estimator_fail_sound_url', '');
    $upload_link = esc_url(admin_url('media-new.php'));
    ?>
    <input type="url" name="age_estimator_fail_sound_url" value="<?php echo esc_attr($value); ?>" 
           class="regular-text" placeholder="https://example.com/fail-sound.mp3">
    <button type="button" class="button age-estimator-media-button" data-target="age_estimator_fail_sound_url">
        Choose Sound
    </button>
    <button type="button" class="button age-estimator-play-sound" data-sound-url="<?php echo esc_attr($value); ?>" <?php echo empty($value) ? 'disabled' : ''; ?>>
        Test
    </button>
    <p class="description">
        Sound played when age verification fails (age < minimum age). 
        <a href="<?php echo $upload_link; ?>" target="_blank">Upload new sound</a>
    </p>
    <div id="fail-sound-preview" style="margin-top: 10px;">
        <?php if (!empty($value)): ?>
            <audio id="fail-sound-audio" preload="auto">
                <source src="<?php echo esc_attr($value); ?>">
            </audio>
        <?php endif; ?>
    </div>
    <?php
}

function age_estimator_sound_volume_callback() {
    $value = get_option('age_estimator_sound_volume', 0.7);
    ?>
    <input type="range" name="age_estimator_sound_volume" value="<?php echo esc_attr($value); ?>" 
           min="0" max="1" step="0.1" style="width: 200px;">
    <span id="volume-display"><?php echo esc_html($value * 100); ?>%</span>
    <p class="description">
        Adjust the volume for all sound notifications (0-100%)
    </p>
    <script>
        document.querySelector('input[name="age_estimator_sound_volume"]').addEventListener('input', function(e) {
            document.getElementById('volume-display').textContent = Math.round(e.target.value * 100) + '%';
        });
    </script>
    <?php
}

function age_estimator_sanitize_volume($input) {
    $value = floatval($input);
    return max(0, min(1, $value)); // Ensure between 0 and 1
}

// Add to the enqueue_admin_scripts function:
function age_estimator_enqueue_sound_admin_scripts($hook) {
    if ($hook !== 'toplevel_page_age-estimator-settings' && $hook !== 'settings_page_age-estimator') {
        return;
    }
    
    // Add media uploader
    wp_enqueue_media();
    
    // Add custom admin script for sound handling
    wp_add_inline_script('jquery', '
        jQuery(document).ready(function($) {
            // Media uploader for sound files
            $(".age-estimator-media-button").on("click", function(e) {
                e.preventDefault();
                var button = $(this);
                var targetInput = button.data("target");
                
                var mediaUploader = wp.media({
                    title: "Choose Sound File",
                    button: {
                        text: "Use this sound"
                    },
                    library: {
                        type: ["audio"]
                    },
                    multiple: false
                }).on("select", function() {
                    var attachment = mediaUploader.state().get("selection").first().toJSON();
                    $("input[name=\'" + targetInput + "\']").val(attachment.url);
                    button.siblings(".age-estimator-play-sound").prop("disabled", false).data("sound-url", attachment.url);
                }).open();
            });
            
            // Test sound playback
            $(".age-estimator-play-sound").on("click", function(e) {
                e.preventDefault();
                var soundUrl = $(this).data("sound-url");
                if (soundUrl) {
                    var audio = new Audio(soundUrl);
                    var volume = $("input[name=\'age_estimator_sound_volume\']").val() || 0.7;
                    audio.volume = parseFloat(volume);
                    audio.play().catch(function(error) {
                        alert("Error playing sound: " + error.message);
                    });
                }
            });
        });
    ');
}
add_action('admin_enqueue_scripts', 'age_estimator_enqueue_sound_admin_scripts');

// Add to the localization function (in your main plugin file or where params are set):
add_filter('age_estimator_localize_params', function($params) {
    // Add sound parameters
    $params['enableSounds'] = get_option('age_estimator_enable_sounds', false) ? '1' : '0';
    $params['passSoundUrl'] = get_option('age_estimator_pass_sound_url', '');
    $params['failSoundUrl'] = get_option('age_estimator_fail_sound_url', '');
    $params['soundVolume'] = get_option('age_estimator_sound_volume', 0.7);
    
    return $params;
});
