# Translation Loading Fix for WordPress 6.7.0

## Problem
WordPress 6.7.0 introduced stricter requirements for when translation functions can be called. The error "_load_textdomain_just_in_time was called incorrectly" occurs when translation functions like `__()` are called before the `init` action.

## Root Cause
The issue was caused by:
1. User settings classes being instantiated immediately when their files were included during the `plugins_loaded` hook
2. The `AgeEstimatorUserSettingsEnhanced` class calling translation functions in methods that were executed in the constructor
3. Text domain being loaded too early in the plugin initialization

## Solution Applied

### 1. Main Plugin File (age-estimator.php)
- Moved text domain loading to the `init` action instead of `plugins_loaded`
- Delayed loading of includes until after text domain is loaded (priority 20 on `init`)
- Created separate `load_textdomain()` method for clarity

### 2. Enhanced User Settings (class-user-settings-enhanced.php)
- Delayed class initialization using the `init` action in the constructor
- Created an `initialize()` method that runs after text domain is loaded
- Changed the immediate instantiation at the bottom of the file to run on the `init` action (priority 25)

### 3. Regular User Settings (class-user-settings.php)
- Changed the immediate instantiation at the bottom of the file to run on the `init` action (priority 25)

## Hook Execution Order
The fixed execution order is now:
1. `plugins_loaded` - Plugin main class `init()` method runs
2. `init` (default priority 10) - Text domain is loaded
3. `init` (priority 20) - Include files are loaded
4. `init` (priority 25) - User settings classes are instantiated
5. `init` (priority 30) - Enhanced settings class `initialize()` method runs

## Testing
After applying these changes:
1. Clear any WordPress caches
2. Refresh the admin page
3. The translation error should no longer appear
4. All translations should work correctly

## WordPress 6.7.0 Compatibility
This fix ensures full compatibility with WordPress 6.7.0's stricter translation loading requirements while maintaining backward compatibility with earlier versions.

## Additional Notes
- Always use translation functions only after the `init` action
- Avoid immediate class instantiation in included files
- Use action hooks to delay initialization when translation functions are needed
