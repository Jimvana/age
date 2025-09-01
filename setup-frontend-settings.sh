#!/bin/bash

# Age Estimator Live - Frontend Settings Setup Script
# This script helps activate the enhanced frontend settings

echo "=================================================="
echo "Age Estimator Live - Frontend Settings Setup"
echo "=================================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Plugin path
PLUGIN_PATH="/Users/video/DevKinsta/public/local-model-age-estin/wp-content/plugins/Age-estimator-live"

echo -e "${BLUE}Checking plugin files...${NC}"

# Check if enhanced settings files exist
if [ -f "$PLUGIN_PATH/includes/user-settings/class-user-settings-enhanced.php" ]; then
    echo -e "${GREEN}✓${NC} Enhanced PHP class found"
else
    echo -e "${RED}✗${NC} Enhanced PHP class not found"
    exit 1
fi

if [ -f "$PLUGIN_PATH/js/user-settings-enhanced.js" ]; then
    echo -e "${GREEN}✓${NC} Enhanced JavaScript found"
else
    echo -e "${RED}✗${NC} Enhanced JavaScript not found"
    exit 1
fi

if [ -f "$PLUGIN_PATH/css/user-settings-enhanced.css" ]; then
    echo -e "${GREEN}✓${NC} Enhanced CSS found"
else
    echo -e "${RED}✗${NC} Enhanced CSS not found"
    exit 1
fi

echo ""
echo -e "${BLUE}Creating backup of original files...${NC}"

# Create backup directory
mkdir -p "$PLUGIN_PATH/includes/user-settings/backup"
mkdir -p "$PLUGIN_PATH/js/backup"
mkdir -p "$PLUGIN_PATH/css/backup"

# Backup original files if they exist
if [ -f "$PLUGIN_PATH/includes/user-settings/class-user-settings.php" ]; then
    cp "$PLUGIN_PATH/includes/user-settings/class-user-settings.php" \
       "$PLUGIN_PATH/includes/user-settings/backup/class-user-settings.php.$(date +%Y%m%d_%H%M%S)"
    echo -e "${GREEN}✓${NC} Backed up original PHP class"
fi

if [ -f "$PLUGIN_PATH/js/user-settings.js" ]; then
    cp "$PLUGIN_PATH/js/user-settings.js" \
       "$PLUGIN_PATH/js/backup/user-settings.js.$(date +%Y%m%d_%H%M%S)"
    echo -e "${GREEN}✓${NC} Backed up original JavaScript"
fi

if [ -f "$PLUGIN_PATH/css/user-settings.css" ]; then
    cp "$PLUGIN_PATH/css/user-settings.css" \
       "$PLUGIN_PATH/css/backup/user-settings.css.$(date +%Y%m%d_%H%M%S)"
    echo -e "${GREEN}✓${NC} Backed up original CSS"
fi

echo ""
echo -e "${BLUE}Setting up enhanced settings...${NC}"

# Create activation file
cat > "$PLUGIN_PATH/activate-enhanced-settings.php" << 'EOF'
<?php
/**
 * Activation script for enhanced settings
 * Run this file once to activate the enhanced settings system
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

echo "<h2>Activating Enhanced Settings...</h2>";

// Check if enhanced settings class exists
$enhanced_file = AGE_ESTIMATOR_PATH . 'includes/user-settings/class-user-settings-enhanced.php';
if (file_exists($enhanced_file)) {
    echo "✓ Enhanced settings file found<br>";
    
    // Update option to use enhanced settings
    update_option('age_estimator_use_enhanced_settings', true);
    echo "✓ Enhanced settings activated<br>";
    
    // Create a test page with the shortcode
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
        );
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id) {
            echo "✓ Settings page created successfully<br>";
            echo "Page URL: <a href='" . get_permalink($page_id) . "' target='_blank'>" . get_permalink($page_id) . "</a><br>";
        }
    } else {
        echo "✓ Settings page already exists<br>";
        echo "Page URL: <a href='" . get_permalink($page->ID) . "' target='_blank'>" . get_permalink($page->ID) . "</a><br>";
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
    echo "✓ Permalinks refreshed<br>";
    
    echo "<br><strong>Enhanced settings activated successfully!</strong><br>";
    echo "<br>You can now:<br>";
    echo "1. Visit the settings page created above<br>";
    echo "2. Add the shortcode to any page: [age_estimator_settings_enhanced]<br>";
    echo "3. Or use the original shortcode: [age_estimator_user_settings]<br>";
    
} else {
    echo "✗ Enhanced settings file not found at: $enhanced_file<br>";
}
EOF

echo -e "${GREEN}✓${NC} Created activation script"

echo ""
echo "=================================================="
echo -e "${GREEN}Setup Complete!${NC}"
echo "=================================================="
echo ""
echo "To activate the enhanced settings, you have several options:"
echo ""
echo -e "${YELLOW}Option 1: Quick Activation (Recommended)${NC}"
echo "1. Visit: http://your-site.local/wp-content/plugins/Age-estimator-live/activate-enhanced-settings.php"
echo "2. This will automatically create a settings page and activate the system"
echo ""
echo -e "${YELLOW}Option 2: Manual Shortcode${NC}"
echo "Add this shortcode to any page:"
echo "[age_estimator_settings_enhanced]"
echo ""
echo -e "${YELLOW}Option 3: Basic Shortcode${NC}"
echo "Use the original shortcode (will load enhanced version if available):"
echo "[age_estimator_user_settings]"
echo ""
echo -e "${BLUE}Available Shortcode Parameters:${NC}"
echo "- theme: 'light', 'dark', or 'auto'"
echo "- layout: 'sidebar', 'tabs', or 'accordion'"
echo "- show_stats: 'true' or 'false'"
echo "- allow_export: 'true' or 'false'"
echo ""
echo -e "${BLUE}Example with all options:${NC}"
echo '[age_estimator_settings_enhanced theme="dark" layout="tabs" show_stats="true" allow_export="true"]'
echo ""
echo -e "${GREEN}Documentation:${NC}"
echo "See FRONTEND_SETTINGS_GUIDE.md for complete documentation"
echo ""
