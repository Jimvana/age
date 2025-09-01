<?php
/**
 * Frontend Settings Integration Snippet
 * Add this to your age-estimator.php file's load_includes() method
 * to automatically load the enhanced settings system
 */

// Add this code to the load_includes() method in age-estimator.php:

// ===============================================
// START: Enhanced Frontend Settings Integration
// ===============================================

// Check if we should use enhanced settings
$use_enhanced = get_option('age_estimator_use_enhanced_settings', true); // Default to true

// Load user settings (frontend)
if ($use_enhanced) {
    // Try to load enhanced version first
    $enhanced_settings_file = AGE_ESTIMATOR_PATH . 'includes/user-settings/class-user-settings-enhanced.php';
    $original_settings_file = AGE_ESTIMATOR_PATH . 'includes/user-settings/class-user-settings.php';
    
    if (file_exists($enhanced_settings_file)) {
        require_once $enhanced_settings_file;
        
        // Also load original for backward compatibility if it exists
        if (file_exists($original_settings_file)) {
            // Check if class doesn't already exist to avoid conflicts
            if (!class_exists('AgeEstimatorUserSettings')) {
                require_once $original_settings_file;
            }
        }
    } else if (file_exists($original_settings_file)) {
        // Fall back to original if enhanced doesn't exist
        require_once $original_settings_file;
    }
} else {
    // Use original settings only
    $user_settings_file = AGE_ESTIMATOR_PATH . 'includes/user-settings/class-user-settings.php';
    if (file_exists($user_settings_file)) {
        require_once $user_settings_file;
    }
}

// ===============================================
// END: Enhanced Frontend Settings Integration
// ===============================================

// Alternative: Simple one-liner if you always want to load both
// (Add to load_includes() method)
/*
// Load both original and enhanced settings for maximum compatibility
foreach (array('class-user-settings.php', 'class-user-settings-enhanced.php') as $settings_file) {
    $file_path = AGE_ESTIMATOR_PATH . 'includes/user-settings/' . $settings_file;
    if (file_exists($file_path)) {
        require_once $file_path;
    }
}
*/

// ===============================================
// ADMIN NOTICE: Show when enhanced settings are available
// ===============================================
// Add this to your admin initialization

add_action('admin_notices', 'age_estimator_enhanced_settings_notice');
function age_estimator_enhanced_settings_notice() {
    // Only show on Age Estimator admin pages
    $screen = get_current_screen();
    if (strpos($screen->id, 'age-estimator') === false && strpos($screen->id, 'age_estimator') === false) {
        return;
    }
    
    // Check if enhanced settings are available but not activated
    $enhanced_file = plugin_dir_path(__FILE__) . 'includes/user-settings/class-user-settings-enhanced.php';
    $enhanced_active = get_option('age_estimator_use_enhanced_settings', false);
    
    if (file_exists($enhanced_file) && !$enhanced_active) {
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong>Age Estimator Live:</strong> 
                Enhanced frontend settings are available! 
                <a href="<?php echo admin_url('admin.php?page=age-estimator-activate-enhanced'); ?>" class="button button-primary" style="margin-left: 10px;">
                    Activate Enhanced Settings
                </a>
            </p>
        </div>
        <?php
    } else if ($enhanced_active) {
        // Check if settings page exists
        $page = get_page_by_title('Age Estimator Settings');
        if (!$page) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>Age Estimator Live:</strong> 
                    Enhanced settings are active but no settings page exists. 
                    <a href="<?php echo admin_url('admin.php?page=age-estimator-create-settings-page'); ?>" class="button button-primary" style="margin-left: 10px;">
                        Create Settings Page
                    </a>
                </p>
            </div>
            <?php
        }
    }
}

// ===============================================
// ACTIVATION HANDLER: Process enhanced settings activation
// ===============================================

add_action('admin_menu', 'age_estimator_add_activation_pages');
function age_estimator_add_activation_pages() {
    // Hidden page for activation
    add_submenu_page(
        null, // Hidden from menu
        'Activate Enhanced Settings',
        'Activate Enhanced Settings',
        'manage_options',
        'age-estimator-activate-enhanced',
        'age_estimator_activate_enhanced_settings'
    );
    
    // Hidden page for creating settings page
    add_submenu_page(
        null,
        'Create Settings Page',
        'Create Settings Page',
        'manage_options',
        'age-estimator-create-settings-page',
        'age_estimator_create_settings_page'
    );
}

function age_estimator_activate_enhanced_settings() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    // Activate enhanced settings
    update_option('age_estimator_use_enhanced_settings', true);
    
    // Create settings page
    age_estimator_create_frontend_settings_page();
    
    // Redirect to settings
    wp_redirect(admin_url('options-general.php?page=age-estimator-settings&enhanced=activated'));
    exit;
}

function age_estimator_create_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    age_estimator_create_frontend_settings_page();
    
    wp_redirect(admin_url('options-general.php?page=age-estimator-settings&page-created=true'));
    exit;
}

function age_estimator_create_frontend_settings_page() {
    $page_title = 'Age Estimator Settings';
    $page_content = '[age_estimator_settings_enhanced theme="light" layout="sidebar" show_stats="true" allow_export="true"]';
    
    // Check if page already exists
    $page = get_page_by_title($page_title);
    
    if (!$page) {
        $page_data = array(
            'post_title'    => $page_title,
            'post_content'  => $page_content,
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_author'   => get_current_user_id(),
            'meta_input'    => array(
                '_age_estimator_settings_page' => 'yes'
            )
        );
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id) {
            // Store the page ID for reference
            update_option('age_estimator_settings_page_id', $page_id);
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            return $page_id;
        }
    }
    
    return $page ? $page->ID : false;
}

// ===============================================
// HELPER FUNCTION: Get settings page URL
// ===============================================

function age_estimator_get_settings_page_url() {
    $page_id = get_option('age_estimator_settings_page_id');
    
    if ($page_id) {
        return get_permalink($page_id);
    }
    
    // Try to find by title
    $page = get_page_by_title('Age Estimator Settings');
    if ($page) {
        return get_permalink($page->ID);
    }
    
    return false;
}

// ===============================================
// ADD SETTINGS LINK TO PLUGINS PAGE
// ===============================================

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'age_estimator_add_settings_link');
function age_estimator_add_settings_link($links) {
    $settings_url = age_estimator_get_settings_page_url();
    
    if ($settings_url) {
        $settings_link = '<a href="' . $settings_url . '" target="_blank">Frontend Settings</a>';
        array_unshift($links, $settings_link);
    }
    
    // Also add backend settings link
    $admin_link = '<a href="' . admin_url('options-general.php?page=age-estimator-settings') . '">Admin Settings</a>';
    array_unshift($links, $admin_link);
    
    return $links;
}

// ===============================================
// WIDGET: Age Estimator Settings Link
// ===============================================

class Age_Estimator_Settings_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'age_estimator_settings_widget',
            'Age Estimator Settings Link',
            array('description' => 'Display a link to Age Estimator settings')
        );
    }
    
    public function widget($args, $instance) {
        if (!is_user_logged_in()) {
            return;
        }
        
        $settings_url = age_estimator_get_settings_page_url();
        if (!$settings_url) {
            return;
        }
        
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        ?>
        <div class="age-estimator-settings-widget">
            <a href="<?php echo esc_url($settings_url); ?>" class="button button-primary">
                <?php echo esc_html($instance['button_text'] ?: 'Manage Age Settings'); ?>
            </a>
        </div>
        <?php
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Age Estimator';
        $button_text = !empty($instance['button_text']) ? $instance['button_text'] : 'Manage Age Settings';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Title:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('button_text')); ?>">Button Text:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('button_text')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('button_text')); ?>" 
                   type="text" value="<?php echo esc_attr($button_text); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['button_text'] = (!empty($new_instance['button_text'])) ? sanitize_text_field($new_instance['button_text']) : '';
        return $instance;
    }
}

// Register the widget
add_action('widgets_init', function() {
    register_widget('Age_Estimator_Settings_Widget');
});
