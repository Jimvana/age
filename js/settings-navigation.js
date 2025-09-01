/**
 * Settings Navigation Enhancement
 * Adds navigation buttons (back, login/logout) to settings pages
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Check if we're on a page with settings
        var settingsContainer = $('.age-estimator-settings-container, .age-estimator-enhanced-settings');
        
        if (settingsContainer.length === 0) {
            return; // No settings on this page
        }
        
        // Don't add navigation if it already exists (from template)
        if ($('.settings-navigation-bar').length > 0 || $('.navigation-bar').length > 0) {
            return;
        }
        
        // Create navigation bar
        var navigationHtml = `
            <div class="settings-navigation-bar" style="
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding: 15px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 10px;
                flex-wrap: wrap;
                gap: 10px;
            ">
                <div class="nav-left">
                    <button onclick="history.back()" class="nav-btn-inline back-btn" style="
                        background: rgba(255,255,255,0.2);
                        color: white;
                        padding: 8px 16px;
                        border: 2px solid rgba(255,255,255,0.3);
                        border-radius: 25px;
                        cursor: pointer;
                        font-weight: 600;
                        transition: all 0.3s;
                        backdrop-filter: blur(10px);
                    ">
                        ← ${ageEstimatorSettings.i18n.back || 'Back'}
                    </button>
                </div>
                <div class="nav-right">
                    ${ageEstimatorSettings.is_logged_in ? 
                        `<a href="${ageEstimatorSettings.logout_url}" class="nav-btn-inline logout-btn" style="
                            background: rgba(220, 53, 69, 0.2);
                            color: white;
                            padding: 8px 16px;
                            border: 2px solid rgba(220, 53, 69, 0.3);
                            border-radius: 25px;
                            text-decoration: none;
                            font-weight: 600;
                            transition: all 0.3s;
                            display: inline-block;
                            backdrop-filter: blur(10px);
                        ">
                            ${ageEstimatorSettings.i18n.logout || 'Logout'} →
                        </a>` :
                        `<a href="${ageEstimatorSettings.login_url}" class="nav-btn-inline login-btn" style="
                            background: rgba(40, 167, 69, 0.2);
                            color: white;
                            padding: 8px 16px;
                            border: 2px solid rgba(40, 167, 69, 0.3);
                            border-radius: 25px;
                            text-decoration: none;
                            font-weight: 600;
                            transition: all 0.3s;
                            display: inline-block;
                            backdrop-filter: blur(10px);
                        ">
                            ${ageEstimatorSettings.i18n.login || 'Login'} →
                        </a>`
                    }
                </div>
            </div>
        `;
        
        // Insert navigation before settings container
        settingsContainer.first().before(navigationHtml);
        
        // Add hover effects
        $('.nav-btn-inline').hover(
            function() {
                $(this).css({
                    'background': 'rgba(255,255,255,0.3)',
                    'transform': 'translateY(-2px)',
                    'box-shadow': '0 5px 15px rgba(0,0,0,0.2)'
                });
            },
            function() {
                if ($(this).hasClass('back-btn')) {
                    $(this).css({
                        'background': 'rgba(255,255,255,0.2)',
                        'transform': 'translateY(0)',
                        'box-shadow': 'none'
                    });
                } else if ($(this).hasClass('logout-btn')) {
                    $(this).css({
                        'background': 'rgba(220, 53, 69, 0.2)',
                        'transform': 'translateY(0)',
                        'box-shadow': 'none'
                    });
                } else if ($(this).hasClass('login-btn')) {
                    $(this).css({
                        'background': 'rgba(40, 167, 69, 0.2)',
                        'transform': 'translateY(0)',
                        'box-shadow': 'none'
                    });
                }
            }
        );
        
        // Mobile responsive styles
        if ($(window).width() <= 768) {
            $('.settings-navigation-bar').css({
                'flex-direction': 'column',
                'text-align': 'center'
            });
            
            $('.nav-left, .nav-right').css({
                'width': '100%',
                'text-align': 'center'
            });
            
            $('.nav-btn-inline').css({
                'width': '100%',
                'max-width': '200px',
                'display': 'block',
                'margin': '5px auto'
            });
        }
        
        // Handle window resize
        $(window).resize(function() {
            if ($(window).width() <= 768) {
                $('.settings-navigation-bar').css({
                    'flex-direction': 'column',
                    'text-align': 'center'
                });
                
                $('.nav-left, .nav-right').css({
                    'width': '100%',
                    'text-align': 'center'
                });
                
                $('.nav-btn-inline').css({
                    'width': '100%',
                    'max-width': '200px',
                    'display': 'block',
                    'margin': '5px auto'
                });
            } else {
                $('.settings-navigation-bar').css({
                    'flex-direction': 'row',
                    'text-align': 'left'
                });
                
                $('.nav-left, .nav-right').css({
                    'width': 'auto',
                    'text-align': 'left'
                });
                
                $('.nav-btn-inline').css({
                    'width': 'auto',
                    'max-width': 'none',
                    'display': 'inline-block',
                    'margin': '0'
                });
            }
        });
    });
    
})(jQuery);
