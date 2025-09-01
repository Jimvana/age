jQuery(document).ready(function($) {
    // Compliance Logs Page Scripts
    if ($('#clear-old-logs').length || $('#clear-all-logs').length) {
        
        // Clear old logs button
        $('#clear-old-logs').on('click', function() {
            var days = prompt('Clear logs older than how many days?', '90');
            
            if (days === null) {
                return;
            }
            
            days = parseInt(days);
            if (isNaN(days) || days < 1) {
                alert('Please enter a valid number of days.');
                return;
            }
            
            if (!confirm('This will permanently delete all logs older than ' + days + ' days. Continue?')) {
                return;
            }
            
            var button = $(this);
            button.prop('disabled', true).text('Clearing...');
            
            $.ajax({
                url: ageEstimatorAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'age_estimator_clear_logs',
                    nonce: ageEstimatorAdmin.nonce,
                    clear_type: 'old',
                    days: days
                },
                success: function(response) {
                    if (response.success) {
                        $('#clear-logs-result').html(
                            '<div class="notice notice-success"><p>' + response.data.message + '</p></div>'
                        );
                        // Reload page after 2 seconds
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $('#clear-logs-result').html(
                            '<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>'
                        );
                    }
                },
                error: function() {
                    $('#clear-logs-result').html(
                        '<div class="notice notice-error"><p>An error occurred while clearing logs.</p></div>'
                    );
                },
                complete: function() {
                    button.prop('disabled', false).text('Clear Old Logs');
                }
            });
        });
        
        // Clear all logs button
        $('#clear-all-logs').on('click', function() {
            if (!confirm('WARNING: This will permanently delete ALL compliance logs. This action cannot be undone. Continue?')) {
                return;
            }
            
            if (!confirm('Are you absolutely sure? All compliance data will be lost.')) {
                return;
            }
            
            var button = $(this);
            button.prop('disabled', true).text('Clearing...');
            
            $.ajax({
                url: ageEstimatorAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'age_estimator_clear_logs',
                    nonce: ageEstimatorAdmin.nonce,
                    clear_type: 'all'
                },
                success: function(response) {
                    if (response.success) {
                        $('#clear-logs-result').html(
                            '<div class="notice notice-success"><p>' + response.data.message + '</p></div>'
                        );
                        // Reload page after 2 seconds
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $('#clear-logs-result').html(
                            '<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>'
                        );
                    }
                },
                error: function() {
                    $('#clear-logs-result').html(
                        '<div class="notice notice-error"><p>An error occurred while clearing logs.</p></div>'
                    );
                },
                complete: function() {
                    button.prop('disabled', false).text('Clear All Logs');
                }
            });
        });
    }
});
