<?php
/**
 * Settings Migration Helper
 * Migrates user settings from basic to enhanced system
 * 
 * @package AgeEstimator
 * @since 2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AgeEstimatorSettingsMigration {
    
    /**
     * Run the migration
     */
    public static function migrate() {
        // Check if migration has already been run
        if (get_option('age_estimator_enhanced_settings_migrated') === '2.0') {
            return array(
                'status' => 'already_migrated',
                'message' => 'Settings have already been migrated to version 2.0'
            );
        }
        
        $migrated_users = 0;
        $errors = array();
        
        // Get all users
        $users = get_users();
        
        foreach ($users as $user) {
            try {
                self::migrate_user_settings($user->ID);
                $migrated_users++;
            } catch (Exception $e) {
                $errors[] = sprintf('User %d: %s', $user->ID, $e->getMessage());
            }
        }
        
        // Mark migration as complete
        update_option('age_estimator_enhanced_settings_migrated', '2.0');
        update_option('age_estimator_enhanced_settings_migration_date', current_time('mysql'));
        
        return array(
            'status' => 'success',
            'migrated_users' => $migrated_users,
            'errors' => $errors,
            'message' => sprintf('Successfully migrated settings for %d users', $migrated_users)
        );
    }
    
    /**
     * Migrate individual user settings
     */
    private static function migrate_user_settings($user_id) {
        // Mapping of old meta keys to new ones
        $migration_map = array(
            // Basic settings that exist
            'age_estimator_face_tracking_distance' => 'age_estimator_face_sensitivity',
            'age_estimator_retail_mode_enabled' => 'age_estimator_retail_mode_enabled',
            'age_estimator_age_gating_enabled' => 'age_estimator_age_gating_enabled',
            'age_estimator_age_gating_threshold' => 'age_estimator_minimum_age',
            'age_estimator_retail_pin' => 'age_estimator_retail_pin', // Keep as is (already hashed)
            
            // Map any plugin-level settings to user-level
            'age_estimator_show_emotions' => 'age_estimator_emotion_detection',
            'age_estimator_show_attributes' => 'age_estimator_facial_attributes',
            'age_estimator_privacy_mode' => 'age_estimator_privacy_mode',
            'age_estimator_require_consent' => 'age_estimator_require_consent',
            'age_estimator_enable_logging' => 'age_estimator_enable_logging',
            'age_estimator_enable_sounds' => 'age_estimator_enable_sounds',
            'age_estimator_sound_volume' => 'age_estimator_sound_volume'
        );
        
        // Migrate existing settings
        foreach ($migration_map as $old_key => $new_key) {
            $value = get_user_meta($user_id, $old_key, true);
            
            if ($value !== '') {
                // Don't overwrite if new key already exists
                $existing = get_user_meta($user_id, $new_key, true);
                if ($existing === '') {
                    update_user_meta($user_id, $new_key, $value);
                    
                    // Optionally remove old meta key
                    // delete_user_meta($user_id, $old_key);
                }
            }
        }
        
        // Set default values for new settings that didn't exist before
        $new_defaults = array(
            'age_estimator_show_results' => '1',
            'age_estimator_show_confidence' => '1',
            'age_estimator_result_display_time' => 5,
            'age_estimator_detection_interval' => 500,
            'age_estimator_min_face_size' => 150,
            'age_estimator_max_face_size' => 350,
            'age_estimator_face_tracking' => '1',
            'age_estimator_multi_face' => '0',
            'age_estimator_averaging_samples' => 5,
            'age_estimator_challenge_age' => 25,
            'age_estimator_email_alerts' => '0',
            'age_estimator_data_retention' => 0,
            'age_estimator_session_timeout' => 15,
            'age_estimator_two_factor' => '0',
            'age_estimator_pass_sound' => 'default',
            'age_estimator_fail_sound' => 'default',
            'age_estimator_screen_flash' => '0',
            'age_estimator_success_color' => '#28a745',
            'age_estimator_failure_color' => '#dc3545',
            'age_estimator_detection_mode' => 'local',
            'age_estimator_cache_duration' => 30,
            'age_estimator_hardware_accel' => '1',
            'age_estimator_gender_detection' => '0'
        );
        
        foreach ($new_defaults as $key => $default_value) {
            $existing = get_user_meta($user_id, $key, true);
            if ($existing === '') {
                update_user_meta($user_id, $key, $default_value);
            }
        }
        
        // Store migration info for this user
        update_user_meta($user_id, 'age_estimator_settings_migrated', '2.0');
        update_user_meta($user_id, 'age_estimator_settings_migration_date', current_time('mysql'));
    }
    
    /**
     * Rollback migration (if needed)
     */
    public static function rollback() {
        // This would restore old settings if needed
        // Implement only if you kept the old meta keys
        
        delete_option('age_estimator_enhanced_settings_migrated');
        delete_option('age_estimator_enhanced_settings_migration_date');
        
        $users = get_users();
        foreach ($users as $user) {
            delete_user_meta($user->ID, 'age_estimator_settings_migrated');
            delete_user_meta($user->ID, 'age_estimator_settings_migration_date');
        }
        
        return array(
            'status' => 'success',
            'message' => 'Migration rolled back successfully'
        );
    }
    
    /**
     * Check migration status
     */
    public static function get_status() {
        $migrated = get_option('age_estimator_enhanced_settings_migrated');
        $migration_date = get_option('age_estimator_enhanced_settings_migration_date');
        
        if ($migrated === '2.0') {
            $users = get_users();
            $migrated_users = 0;
            
            foreach ($users as $user) {
                if (get_user_meta($user->ID, 'age_estimator_settings_migrated', true) === '2.0') {
                    $migrated_users++;
                }
            }
            
            return array(
                'migrated' => true,
                'version' => $migrated,
                'date' => $migration_date,
                'total_users' => count($users),
                'migrated_users' => $migrated_users
            );
        }
        
        return array(
            'migrated' => false,
            'version' => null,
            'date' => null
        );
    }
    
    /**
     * Export old settings (backup before migration)
     */
    public static function backup_settings() {
        $backup = array(
            'timestamp' => current_time('mysql'),
            'version' => '1.0',
            'users' => array()
        );
        
        $users = get_users();
        foreach ($users as $user) {
            $user_settings = array(
                'user_id' => $user->ID,
                'user_login' => $user->user_login,
                'settings' => array()
            );
            
            // Get all age_estimator meta keys
            global $wpdb;
            $meta_keys = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT meta_key FROM {$wpdb->usermeta} 
                WHERE user_id = %d AND meta_key LIKE 'age_estimator_%'",
                $user->ID
            ));
            
            foreach ($meta_keys as $key) {
                $user_settings['settings'][$key] = get_user_meta($user->ID, $key, true);
            }
            
            if (!empty($user_settings['settings'])) {
                $backup['users'][] = $user_settings;
            }
        }
        
        // Save backup
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/age-estimator-backups';
        
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        
        $backup_file = $backup_dir . '/settings-backup-' . date('Y-m-d-His') . '.json';
        file_put_contents($backup_file, json_encode($backup, JSON_PRETTY_PRINT));
        
        return array(
            'status' => 'success',
            'backup_file' => $backup_file,
            'users_backed_up' => count($backup['users'])
        );
    }
    
    /**
     * Restore from backup
     */
    public static function restore_backup($backup_file) {
        if (!file_exists($backup_file)) {
            return array(
                'status' => 'error',
                'message' => 'Backup file not found'
            );
        }
        
        $backup = json_decode(file_get_contents($backup_file), true);
        
        if (!$backup || !isset($backup['users'])) {
            return array(
                'status' => 'error',
                'message' => 'Invalid backup file'
            );
        }
        
        $restored_users = 0;
        
        foreach ($backup['users'] as $user_data) {
            $user = get_user_by('login', $user_data['user_login']);
            
            if ($user) {
                foreach ($user_data['settings'] as $key => $value) {
                    update_user_meta($user->ID, $key, $value);
                }
                $restored_users++;
            }
        }
        
        return array(
            'status' => 'success',
            'restored_users' => $restored_users,
            'message' => sprintf('Restored settings for %d users', $restored_users)
        );
    }
}

// Admin interface for migration
if (is_admin()) {
    add_action('admin_menu', function() {
        add_submenu_page(
            'age-estimator',
            'Settings Migration',
            'Migration Tool',
            'manage_options',
            'age-estimator-migration',
            'age_estimator_migration_page'
        );
    });
    
    function age_estimator_migration_page() {
        // Handle form submission
        if (isset($_POST['action'])) {
            if (!wp_verify_nonce($_POST['migration_nonce'], 'age_estimator_migration')) {
                wp_die('Security check failed');
            }
            
            $result = null;
            
            switch ($_POST['action']) {
                case 'migrate':
                    $result = AgeEstimatorSettingsMigration::migrate();
                    break;
                case 'backup':
                    $result = AgeEstimatorSettingsMigration::backup_settings();
                    break;
                case 'rollback':
                    $result = AgeEstimatorSettingsMigration::rollback();
                    break;
            }
            
            if ($result) {
                echo '<div class="notice notice-' . ($result['status'] === 'success' ? 'success' : 'error') . '"><p>' . esc_html($result['message']) . '</p></div>';
            }
        }
        
        $status = AgeEstimatorSettingsMigration::get_status();
        ?>
        <div class="wrap">
            <h1>Age Estimator Settings Migration</h1>
            
            <div class="card">
                <h2>Migration Status</h2>
                <?php if ($status['migrated']): ?>
                    <p>✅ <strong>Settings have been migrated to version <?php echo esc_html($status['version']); ?></strong></p>
                    <p>Migration date: <?php echo esc_html($status['date']); ?></p>
                    <p>Users migrated: <?php echo esc_html($status['migrated_users']); ?> / <?php echo esc_html($status['total_users']); ?></p>
                <?php else: ?>
                    <p>⚠️ <strong>Settings have not been migrated to the enhanced version</strong></p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>Migration Actions</h2>
                <form method="post">
                    <?php wp_nonce_field('age_estimator_migration', 'migration_nonce'); ?>
                    
                    <p>
                        <button type="submit" name="action" value="backup" class="button">
                            📦 Backup Current Settings
                        </button>
                        <span class="description">Create a backup before migration</span>
                    </p>
                    
                    <?php if (!$status['migrated']): ?>
                    <p>
                        <button type="submit" name="action" value="migrate" class="button button-primary">
                            ⚡ Run Migration
                        </button>
                        <span class="description">Migrate all user settings to enhanced version</span>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($status['migrated']): ?>
                    <p>
                        <button type="submit" name="action" value="rollback" class="button button-secondary" 
                                onclick="return confirm('Are you sure you want to rollback the migration?');">
                            ↩️ Rollback Migration
                        </button>
                        <span class="description">Revert to previous settings (if backups exist)</span>
                    </p>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="card">
                <h2>Instructions</h2>
                <ol>
                    <li><strong>Backup First:</strong> Always create a backup before running migration</li>
                    <li><strong>Run Migration:</strong> Click "Run Migration" to upgrade all user settings</li>
                    <li><strong>Test:</strong> Have users test their settings after migration</li>
                    <li><strong>Rollback if Needed:</strong> Use rollback if issues occur</li>
                </ol>
            </div>
        </div>
        <?php
    }
}

// Auto-run migration on plugin update
register_activation_hook(__FILE__, function() {
    if (get_option('age_estimator_enhanced_settings_migrated') !== '2.0') {
        AgeEstimatorSettingsMigration::backup_settings();
        AgeEstimatorSettingsMigration::migrate();
    }
});
