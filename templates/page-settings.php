<?php
/**
 * Template Name: Age Estimator Settings Page
 * Description: A custom page template for the Age Estimator settings panel
 * 
 * This template provides a dedicated page for user settings with a clean,
 * focused interface. Copy this file to your theme directory and select it
 * as the page template when creating your settings page.
 * 
 * @package AgeEstimator
 * @since 2.0
 */

// Check if user is logged in (removed automatic redirect to allow login button display)
$is_logged_in = is_user_logged_in();

get_header(); 
?>

<style>
    /* Custom page styles for settings */
    .age-estimator-settings-page {
        min-height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px 0;
    }
    
    .settings-page-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    .settings-page-header {
        text-align: center;
        color: white;
        margin-bottom: 40px;
    }
    
    .settings-page-header h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 10px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
    }
    
    .settings-page-header p {
        font-size: 1.2rem;
        opacity: 0.95;
    }
    
    .user-info-bar {
        background: rgba(255,255,255,0.1);
        padding: 15px 25px;
        border-radius: 50px;
        display: inline-flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 30px;
        backdrop-filter: blur(10px);
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid white;
    }
    
    .user-name {
        color: white;
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    .quick-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-bottom: 40px;
        flex-wrap: wrap;
    }
    
    .quick-action-btn {
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 12px 24px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255,255,255,0.3);
    }
    
    .quick-action-btn:hover {
        background: rgba(255,255,255,0.3);
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    .navigation-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .nav-buttons {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .nav-btn {
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 10px 20px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255,255,255,0.3);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .nav-btn:hover {
        background: rgba(255,255,255,0.3);
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        color: white;
        text-decoration: none;
    }
    
    .nav-btn.back-btn {
        background: rgba(255,255,255,0.15);
    }
    
    .nav-btn.logout-btn {
        background: rgba(220, 53, 69, 0.2);
        border-color: rgba(220, 53, 69, 0.3);
    }
    
    .nav-btn.logout-btn:hover {
        background: rgba(220, 53, 69, 0.3);
    }
    
    .nav-btn.login-btn {
        background: rgba(40, 167, 69, 0.2);
        border-color: rgba(40, 167, 69, 0.3);
    }
    
    .nav-btn.login-btn:hover {
        background: rgba(40, 167, 69, 0.3);
    }
    
    .settings-main-wrapper {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .settings-page-header h1 {
            font-size: 2rem;
        }
        
        .quick-actions {
            flex-direction: column;
            align-items: center;
        }
        
        .quick-action-btn {
            width: 100%;
            max-width: 300px;
            text-align: center;
        }
        
        .navigation-bar {
            flex-direction: column;
            gap: 10px;
        }
        
        .nav-buttons {
            width: 100%;
            justify-content: center;
        }
        
        .nav-btn {
            flex: 1;
            justify-content: center;
            min-width: 120px;
        }
    }
    
    /* Integration with theme */
    body.page-template-age-estimator-settings {
        background: transparent;
    }
    
    body.page-template-age-estimator-settings .site-header,
    body.page-template-age-estimator-settings .site-footer {
        position: relative;
        z-index: 10;
    }
</style>

<div class="age-estimator-settings-page">
    <div class="settings-page-container">
        
        <!-- Navigation Bar -->
        <div class="navigation-bar">
            <div class="nav-buttons">
                <a href="javascript:history.back()" class="nav-btn back-btn">
                    ← <?php _e('Back', 'age-estimator'); ?>
                </a>
            </div>
            <div class="nav-buttons">
                <?php if ($is_logged_in): ?>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="nav-btn logout-btn">
                        <?php _e('Logout', 'age-estimator'); ?> →
                    </a>
                <?php else: ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="nav-btn login-btn">
                        <?php _e('Login', 'age-estimator'); ?> →
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Page Header -->
        <div class="settings-page-header">
            <?php if ($is_logged_in): ?>
                <?php
                $current_user = wp_get_current_user();
                $avatar_url = get_avatar_url($current_user->ID, array('size' => 80));
                ?>
                
                <div class="user-info-bar">
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>" class="user-avatar">
                    <span class="user-name"><?php echo esc_html($current_user->display_name); ?></span>
                </div>
            <?php endif; ?>
            
            <h1><?php _e('Age Estimator Settings', 'age-estimator'); ?></h1>
            <p><?php _e('Customize your age verification experience', 'age-estimator'); ?></p>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="#general" class="quick-action-btn">
                ⚙️ <?php _e('General Settings', 'age-estimator'); ?>
            </a>
            <a href="#detection" class="quick-action-btn">
                👤 <?php _e('Face Detection', 'age-estimator'); ?>
            </a>
            <a href="#retail" class="quick-action-btn">
                🏪 <?php _e('Retail Mode', 'age-estimator'); ?>
            </a>
            <a href="#stats" class="quick-action-btn">
                📊 <?php _e('View Statistics', 'age-estimator'); ?>
            </a>
        </div>
        
        <?php if ($is_logged_in): ?>
            <!-- Settings Panel -->
            <div class="settings-main-wrapper">
                <?php
                // Display the enhanced settings shortcode
                echo do_shortcode('[age_estimator_settings_enhanced theme="light" layout="sidebar" show_stats="true" allow_export="true"]');
                ?>
            </div>
        <?php else: ?>
            <!-- Login Required Message -->
            <div class="settings-main-wrapper" style="padding: 60px 40px; text-align: center;">
                <h2 style="margin-bottom: 20px; color: #666;"><?php _e('Login Required', 'age-estimator'); ?></h2>
                <p style="color: #999; margin-bottom: 30px; font-size: 1.1rem;">
                    <?php _e('You need to be logged in to access the settings panel.', 'age-estimator'); ?>
                </p>
                <a href="<?php echo wp_login_url(get_permalink()); ?>" class="quick-action-btn" style="background: #667eea; border: none; display: inline-block;">
                    <?php _e('Login to Continue', 'age-estimator'); ?> →
                </a>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<!-- Additional JavaScript for page-specific functionality -->
<script>
jQuery(document).ready(function($) {
    // Smooth scroll to sections when quick action buttons are clicked
    $('.quick-action-btn').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        // Trigger navigation in settings panel
        $('.settings-nav a[href="' + target + '"]').click();
        
        // Smooth scroll to settings panel
        $('html, body').animate({
            scrollTop: $('.settings-main-wrapper').offset().top - 50
        }, 500);
    });
    
    // Add page-specific event tracking
    $(document).on('ageEstimator:settingsSaved', function(e, section, data) {
        // Custom notification or action after settings are saved
        console.log('Settings saved in section:', section);
        
        // Optional: Show a toast notification
        showToast('Settings saved successfully!', 'success');
    });
    
    // Custom toast notification function
    function showToast(message, type) {
        var toast = $('<div>', {
            class: 'custom-toast toast-' + type,
            text: message,
            css: {
                position: 'fixed',
                bottom: '20px',
                right: '20px',
                background: type === 'success' ? '#28a745' : '#dc3545',
                color: 'white',
                padding: '15px 25px',
                borderRadius: '50px',
                boxShadow: '0 10px 30px rgba(0,0,0,0.3)',
                zIndex: 10000,
                fontWeight: '600',
                display: 'none'
            }
        }).appendTo('body');
        
        toast.fadeIn(300).delay(3000).fadeOut(300, function() {
            $(this).remove();
        });
    }
    
    // Add keyboard navigation hints
    $(document).on('keydown', function(e) {
        // Show keyboard shortcuts with Shift + ?
        if (e.shiftKey && e.key === '?') {
            showKeyboardShortcuts();
        }
    });
    
    function showKeyboardShortcuts() {
        var modal = $('<div>', {
            class: 'keyboard-shortcuts-modal',
            html: `
                <div class="shortcuts-content">
                    <h3>Keyboard Shortcuts</h3>
                    <ul>
                        <li><kbd>Ctrl</kbd> + <kbd>S</kbd> - Save current section</li>
                        <li><kbd>Ctrl</kbd> + <kbd>E</kbd> - Export settings</li>
                        <li><kbd>Esc</kbd> - Close modals</li>
                        <li><kbd>Shift</kbd> + <kbd>?</kbd> - Show this help</li>
                    </ul>
                    <button class="close-shortcuts">Close</button>
                </div>
            `,
            css: {
                position: 'fixed',
                top: 0,
                left: 0,
                right: 0,
                bottom: 0,
                background: 'rgba(0,0,0,0.8)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                zIndex: 10001
            }
        }).appendTo('body');
        
        modal.find('.shortcuts-content').css({
            background: 'white',
            padding: '30px',
            borderRadius: '15px',
            maxWidth: '400px'
        });
        
        modal.find('.close-shortcuts, .keyboard-shortcuts-modal').on('click', function(e) {
            if (e.target === this) {
                modal.fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
    }
    
    // Auto-save indicator
    var autoSaveTimer;
    $(document).on('change', '.settings-form input, .settings-form select', function() {
        clearTimeout(autoSaveTimer);
        
        // Show auto-save indicator
        if (!$('.auto-save-indicator').length) {
            $('<div class="auto-save-indicator">Auto-saving...</div>').css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                background: '#ffc107',
                color: '#856404',
                padding: '10px 20px',
                borderRadius: '50px',
                fontSize: '14px',
                fontWeight: '600',
                zIndex: 10000
            }).appendTo('body');
        }
        
        // Hide indicator after 2 seconds
        autoSaveTimer = setTimeout(function() {
            $('.auto-save-indicator').fadeOut(300, function() {
                $(this).remove();
            });
        }, 2000);
    });
    
    // Add confirmation before leaving if there are unsaved changes
    var hasUnsavedChanges = false;
    
    $(document).on('change', '.settings-form input, .settings-form select', function() {
        hasUnsavedChanges = true;
    });
    
    $(document).on('ageEstimator:settingsSaved', function() {
        hasUnsavedChanges = false;
    });
    
    $(window).on('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            var message = 'You have unsaved changes. Are you sure you want to leave?';
            e.returnValue = message;
            return message;
        }
    });
});
</script>

<?php get_footer(); ?>
