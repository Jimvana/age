# Age Estimator Plugin - Cleanup Summary

## Files Being Cleaned Up

### 1. Backup Files
- All .backup files
- All .old files
- Timestamped backup files

### 2. Test Files (30+ files)
- test-*.php
- test-*.html
- test-*.sh
- *-test.html
- *-test.php
- Azure test files

### 3. Debug Files
- debug-*.html
- debug-*.js
- debug-*.php
- *-debug.html
- *-debug.php
- Diagnostic files

### 4. Demo Files
- fullscreen-demo.html
- overlay-display-demo.html
- face-tracking-flow.html

### 5. One-Time Shell Scripts
- apply-*.sh
- setup scripts
- cleanup scripts

### 6. Utility PHP Scripts
- Model download scripts
- Database fix scripts
- Permission fix scripts
- Setup scripts

### 7. Redundant Documentation
- Individual fix documentation
- Troubleshooting guides for specific issues
- Completion summaries
- Update notices

### 8. Temporary Files
- .DS_Store

## Files Being Kept

### Core Plugin Files
- age-estimator.php (main plugin file)
- age-estimator-photo.php
- age-estimator-photo-simple.php

### Active Directories
- `/css/` - All active stylesheets (except backups)
- `/js/` - All active JavaScript files (except backups)
- `/models/` - Face detection model files
- `/templates/` - Template files
- `/includes/` - Include files (except backups)
- `/libs/` - Library files
- `/sounds/` - Sound files

### Essential Documentation
- README.md
- CHANGELOG.md
- DEVELOPER-DOCUMENTATION.md
- AWS-SETUP-GUIDE.md
- Feature implementation guides (kept for reference)
- Kiosk mode implementation folder

### Configuration
- Essential implementation documentation for active features

## How to Execute Cleanup

1. Make the cleanup script executable:
   ```bash
   chmod +x cleanup-files.sh
   ```

2. Run the cleanup script:
   ```bash
   ./cleanup-files.sh
   ```

3. Move the Delete folder to external drive:
   ```bash
   mv Delete /Volumes/Ventoy/Age-estimator-live/
   ```

## Total Files to Clean Up
- Approximately 100+ files
- This will significantly clean up the project directory
- All core functionality remains intact

## After Cleanup
The plugin directory will be much cleaner with only:
- Core plugin files
- Active code files
- Essential documentation
- Required assets
