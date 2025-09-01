# Changelog - Age Estimator Live

## Version 2.0 (Current)
### Major Changes
- **Renamed to Age Estimator Live** - Plugin now focuses exclusively on live continuous monitoring
- **Continuous monitoring is now the only mode** - Removed manual capture option
- **Removed continuous mode toggle** - The plugin always operates in live monitoring mode
- **Updated all branding** - Changed plugin name, descriptions, and UI text throughout

### Technical Updates
- Simplified codebase by removing conditional mode checking
- Updated `photo-age-estimator.js` to always use continuous monitoring
- Removed `age_estimator_continuous_mode` setting from database
- Updated admin interface to reflect always-on continuous mode
- Version bumped to 2.0 to reflect major change

### User Experience
- Button now always shows "Start Monitoring" 
- Admin panel shows "✓ Always Enabled" for continuous monitoring
- Clearer messaging about live detection functionality
- Updated documentation to focus on live monitoring features

## Version 1.0
### Features
- Initial release with both manual and continuous modes
- Toggle between manual photo capture and automatic detection
- Support for both local (face-api.js) and AWS Rekognition
- Age gating functionality
- Privacy mode options
- Consent management
- Multi-language support
