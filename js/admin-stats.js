/**
 * Admin Statistics JavaScript for Age Estimator
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize charts if elements exist
    if ($('#daily-usage-chart').length) {
        loadDailyUsageChart();
    }
    
    if ($('#hourly-distribution-chart').length) {
        loadHourlyDistributionChart();
    }
    
    // Export CSV handler
    $('#export-stats').on('click', function(e) {
        e.preventDefault();
        
        const period = $('#period').val();
        const date = $('input[name="date"]').val();
        
        $.ajax({
            url: ageEstimatorStats.ajaxUrl,
            type: 'POST',
            data: {
                action: 'age_estimator_export_stats',
                nonce: ageEstimatorStats.nonce,
                period: period,
                date: date
            },
            success: function(response) {
                if (response.success) {
                    // Create and download CSV file
                    const blob = new Blob([response.data.content], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = response.data.filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                }
            }
        });
    });
    
    /**
     * Load daily usage chart
     */
    function loadDailyUsageChart() {
        const endDate = $('input[name="date"]').val() || new Date().toISOString().split('T')[0];
        const startDate = new Date(endDate);
        startDate.setDate(startDate.getDate() - 30);
        
        $.ajax({
            url: ageEstimatorStats.ajaxUrl,
            type: 'POST',
            data: {
                action: 'age_estimator_get_stats',
                nonce: ageEstimatorStats.nonce,
                type: 'daily',
                start_date: startDate.toISOString().split('T')[0],
                end_date: endDate
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    const ctx = document.getElementById('daily-usage-chart').getContext('2d');
                    
                    const labels = response.data.map(item => {
                        const date = new Date(item.call_date);
                        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    });
                    
                    const apiCalls = response.data.map(item => parseInt(item.total_calls));
                    const uniqueUsers = response.data.map(item => parseInt(item.unique_users));
                    const faces = response.data.map(item => parseInt(item.total_faces));
                    
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels.reverse(),
                            datasets: [{
                                label: 'API Calls',
                                data: apiCalls.reverse(),
                                borderColor: 'rgb(75, 192, 192)',
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                tension: 0.1
                            }, {
                                label: 'Unique Users',
                                data: uniqueUsers.reverse(),
                                borderColor: 'rgb(255, 99, 132)',
                                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                tension: 0.1
                            }, {
                                label: 'Faces Detected',
                                data: faces.reverse(),
                                borderColor: 'rgb(255, 205, 86)',
                                backgroundColor: 'rgba(255, 205, 86, 0.2)',
                                tension: 0.1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Daily API Usage (Last 30 Days)'
                                },
                                legend: {
                                    position: 'bottom'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                }
            }
        });
    }
    
    /**
     * Load hourly distribution chart
     */
    function loadHourlyDistributionChart() {
        const date = $('input[name="date"]').val() || new Date().toISOString().split('T')[0];
        
        $.ajax({
            url: ageEstimatorStats.ajaxUrl,
            type: 'POST',
            data: {
                action: 'age_estimator_get_stats',
                nonce: ageEstimatorStats.nonce,
                type: 'hourly',
                end_date: date
            },
            success: function(response) {
                if (response.success) {
                    const ctx = document.getElementById('hourly-distribution-chart').getContext('2d');
                    
                    // Initialize all hours with 0
                    const hourlyData = new Array(24).fill(0);
                    
                    // Fill with actual data
                    response.data.forEach(item => {
                        hourlyData[parseInt(item.hour)] = parseInt(item.calls);
                    });
                    
                    const labels = Array.from({length: 24}, (_, i) => {
                        const hour = i;
                        const ampm = hour >= 12 ? 'PM' : 'AM';
                        const displayHour = hour === 0 ? 12 : (hour > 12 ? hour - 12 : hour);
                        return `${displayHour}${ampm}`;
                    });
                    
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'API Calls',
                                data: hourlyData,
                                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Hourly Distribution of API Calls'
                                },
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                }
            }
        });
    }
    
    // Auto-refresh stats every 60 seconds if on current date
    const currentDate = new Date().toISOString().split('T')[0];
    const selectedDate = $('input[name="date"]').val();
    
    if (selectedDate === currentDate) {
        setInterval(function() {
            location.reload();
        }, 60000); // 60 seconds
    }
});
