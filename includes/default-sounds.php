<?php
/**
 * Default Sound Files for Age Estimator
 * 
 * These are simple tone sounds encoded as data URIs that can be used as defaults
 * if no custom sounds are uploaded.
 */

// Simple success tone (ascending chime)
define('AGE_ESTIMATOR_DEFAULT_PASS_SOUND', 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZURE');

// Simple fail tone (descending tone) 
define('AGE_ESTIMATOR_DEFAULT_FAIL_SOUND', 'data:audio/wav;base64,UklGRhwGAABXQVZFZm10IBAAAAABAAEAiBUAAIgVAAABAAgAZGF0YQoGAACAgoSFh4mLjI6QkpSWmJqbnJ6goqSmqKqrrK6wsrS2uLq8vr/BwsPFx8nLzM7Q0tTV1tnb3d7g4uTm6Orr7e/x8/X3+Pv9/wABAwUGCAoMDg8REw');

// Add to admin settings as default values
add_filter('age_estimator_default_sounds', function() {
    return array(
        'pass' => AGE_ESTIMATOR_DEFAULT_PASS_SOUND,
        'fail' => AGE_ESTIMATOR_DEFAULT_FAIL_SOUND
    );
});

// Helper function to use in admin settings
function age_estimator_get_default_sound($type) {
    $defaults = apply_filters('age_estimator_default_sounds', array());
    return isset($defaults[$type]) ? $defaults[$type] : '';
}

// Update the admin callbacks to show "Use Default" option
function age_estimator_pass_sound_with_default_callback() {
    $value = get_option('age_estimator_pass_sound_url', '');
    $default = age_estimator_get_default_sound('pass');
    ?>
    <div class="age-estimator-sound-field">
        <input type="url" name="age_estimator_pass_sound_url" value="<?php echo esc_attr($value); ?>" 
               class="regular-text" placeholder="https://example.com/pass-sound.mp3">
        <button type="button" class="button age-estimator-media-button" data-target="age_estimator_pass_sound_url">
            Choose Sound
        </button>
        <button type="button" class="button age-estimator-play-sound" data-sound-url="<?php echo esc_attr($value ?: $default); ?>">
            Test
        </button>
        <?php if (!empty($default)): ?>
        <button type="button" class="button age-estimator-use-default" data-target="age_estimator_pass_sound_url" data-default="<?php echo esc_attr($default); ?>">
            Use Default
        </button>
        <?php endif; ?>
    </div>
    <p class="description">
        Sound played when age verification passes. Leave empty to use default chime.
    </p>
    <?php
}

// JavaScript to handle "Use Default" button
add_action('admin_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.age-estimator-use-default').on('click', function(e) {
            e.preventDefault();
            var target = $(this).data('target');
            var defaultUrl = $(this).data('default');
            $('input[name="' + target + '"]').val(defaultUrl);
            $(this).siblings('.age-estimator-play-sound').data('sound-url', defaultUrl).prop('disabled', false);
        });
    });
    </script>
    <?php
});
