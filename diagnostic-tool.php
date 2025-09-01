<?php
/**
 * Diagnostic Tool - Check Form Values
 * 
 * This will help us understand what's actually happening with the form values
 */

// Make sure this is running in WordPress context
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

echo "<h2>Age Estimator Settings Diagnostic</h2>\n";

// Check the problematic options
$problem_options = [
    'age_estimator_average_samples' => ['expected_default' => 5, 'min' => 3],
    'age_estimator_logo_height' => ['expected_default' => 40, 'min' => 20],
    'age_estimator_kiosk_display_time' => ['expected_default' => 5, 'min' => 1],
    'age_estimator_minimum_age' => ['expected_default' => 21, 'min' => 1],
];

echo "<h3>Problem Options Analysis</h3>\n";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr><th>Option Name</th><th>Current Value</th><th>Expected Default</th><th>Min Required</th><th>Status</th></tr>\n";

$issues_found = 0;

foreach ($problem_options as $option_name => $config) {
    $current_value = get_option($option_name);
    $expected_default = $config['expected_default'];
    $min_required = $config['min'];
    
    $status = 'OK';
    $style = 'background-color: lightgreen;';
    
    if ($current_value === false) {
        $display_value = 'FALSE (not set)';
        $status = 'NOT SET';
        $style = 'background-color: orange;';
        $issues_found++;
    } elseif ($current_value === 0 || $current_value === '0') {
        $display_value = '0';
        $status = 'ZERO VALUE - WILL CAUSE VALIDATION ERROR';
        $style = 'background-color: red; color: white;';
        $issues_found++;
    } elseif ($current_value < $min_required) {
        $display_value = $current_value;
        $status = 'BELOW MINIMUM';
        $style = 'background-color: red; color: white;';
        $issues_found++;
    } else {
        $display_value = $current_value;
    }
    
    echo "<tr style='$style'>\n";
    echo "<td><strong>$option_name</strong></td>\n";
    echo "<td>$display_value</td>\n";
    echo "<td>$expected_default</td>\n";
    echo "<td>$min_required</td>\n";
    echo "<td>$status</td>\n";
    echo "</tr>\n";
}

echo "</table>\n";

if ($issues_found > 0) {
    echo "<div style='background: red; color: white; padding: 15px; margin: 20px 0;'>\n";
    echo "<h3>❌ Issues Found: $issues_found</h3>\n";
    echo "<p>These values will cause HTML5 form validation to fail because they are below the minimum requirements.</p>\n";
    echo "<button onclick='fixValues()' style='background: white; color: red; padding: 10px; border: none; cursor: pointer; font-weight: bold;'>Click to Fix These Values</button>\n";
    echo "</div>\n";
} else {
    echo "<div style='background: green; color: white; padding: 15px; margin: 20px 0;'>\n";
    echo "<h3>✅ No Database Issues Found</h3>\n";
    echo "<p>All option values look correct in the database. If you're still having form validation issues, it might be:</p>\n";
    echo "<ul><li>Browser caching</li><li>WordPress caching plugin</li><li>JavaScript interference</li><li>Form processing issue</li></ul>\n";
    echo "</div>\n";
}

// Check for caching plugins
echo "<h3>Potential Interference Check</h3>\n";

$active_plugins = get_option('active_plugins', []);
$caching_keywords = ['cache', 'optimize', 'speed', 'minify', 'wp-rocket', 'w3-total-cache', 'wp-fastest-cache'];

$caching_plugins = [];
foreach ($active_plugins as $plugin) {
    foreach ($caching_keywords as $keyword) {
        if (strpos(strtolower($plugin), $keyword) !== false) {
            $caching_plugins[] = $plugin;
            break;
        }
    }
}

if (!empty($caching_plugins)) {
    echo "<p style='background: orange; padding: 10px;'><strong>⚠️ Caching plugins detected:</strong></p>\n";
    echo "<ul>\n";
    foreach ($caching_plugins as $plugin) {
        echo "<li>$plugin</li>\n";
    }
    echo "</ul>\n";
    echo "<p>Try clearing all caches and testing again.</p>\n";
} else {
    echo "<p style='color: green;'>✅ No caching plugins detected.</p>\n";
}

?>

<script>
function fixValues() {
    if (confirm('This will update the problematic values to their defaults. Continue?')) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=fix_values'
        }).then(response => response.text()).then(data => {
            alert('Values updated! Refreshing page...');
            window.location.reload();
        });
    }
}
</script>

<?php

// Handle the fix request
if ($_POST['action'] === 'fix_values') {
    foreach ($problem_options as $option_name => $config) {
        $current_value = get_option($option_name);
        if ($current_value === false || $current_value === 0 || $current_value === '0' || $current_value < $config['min']) {
            update_option($option_name, $config['expected_default']);
            echo "Fixed $option_name to {$config['expected_default']}<br>";
        }
    }
    exit;
}
?>
