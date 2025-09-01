/**
 * Live Settings PIN Debug
 * Add this JavaScript to see exactly what happens during PIN submission
 */

// Add to browser console on your settings page
console.log('🔍 PIN Debug Mode Activated');

// Intercept all AJAX requests to see what's being sent
const originalAjax = jQuery.ajax;
jQuery.ajax = function(options) {
    console.log('🌐 AJAX Request Intercepted:', options);
    
    // Check if this is a PIN-related request
    if (options.data && typeof options.data === 'object') {
        if (options.data.action && options.data.action.includes('pin')) {
            console.log('🔐 PIN-related AJAX detected:', {
                action: options.data.action,
                pin: options.data.pin,
                nonce: options.data.nonce,
                url: options.url,
                full_data: options.data
            });
        }
    } else if (options.data && typeof options.data === 'string' && options.data.includes('pin')) {
        console.log('🔐 PIN-related AJAX (string data):', options.data);
    }
    
    // Store original success/error handlers
    const originalSuccess = options.success;
    const originalError = options.error;
    
    // Override success handler
    options.success = function(response) {
        console.log('✅ AJAX Success Response:', response);
        if (originalSuccess) originalSuccess.apply(this, arguments);
    };
    
    // Override error handler
    options.error = function(xhr, status, error) {
        console.log('❌ AJAX Error Response:', {
            status: status,
            error: error,
            responseText: xhr.responseText,
            xhr: xhr
        });
        if (originalError) originalError.apply(this, arguments);
    };
    
    // Call original AJAX
    return originalAjax.call(this, options);
};

// Also intercept fetch requests
const originalFetch = window.fetch;
window.fetch = function(...args) {
    console.log('🌐 Fetch Request:', args);
    return originalFetch.apply(this, args)
        .then(response => {
            console.log('✅ Fetch Response:', response);
            return response;
        })
        .catch(error => {
            console.log('❌ Fetch Error:', error);
            throw error;
        });
};

// Look for PIN form elements
setTimeout(() => {
    const pinInputs = document.querySelectorAll('input[type="password"], input[id*="pin"], input[name*="pin"]');
    console.log('🔍 Found PIN inputs:', pinInputs);
    
    pinInputs.forEach((input, index) => {
        console.log(`PIN Input ${index}:`, {
            id: input.id,
            name: input.name,
            value: input.value,
            element: input
        });
        
        // Add event listener to see when PIN is entered
        input.addEventListener('input', function() {
            console.log(`PIN Input ${index} changed:`, this.value);
        });
    });
    
    // Look for forms
    const forms = document.querySelectorAll('form');
    console.log('🔍 Found forms:', forms);
    
    forms.forEach((form, index) => {
        console.log(`Form ${index}:`, {
            id: form.id,
            class: form.className,
            action: form.action,
            method: form.method,
            element: form
        });
        
        form.addEventListener('submit', function(e) {
            console.log(`Form ${index} submitted:`, {
                form: this,
                event: e,
                formData: new FormData(this)
            });
        });
    });
    
    // Look for buttons
    const buttons = document.querySelectorAll('button, input[type="submit"]');
    console.log('🔍 Found buttons:', buttons);
    
    buttons.forEach((button, index) => {
        if (button.textContent.toLowerCase().includes('validat') || 
            button.textContent.toLowerCase().includes('access') ||
            button.id.toLowerCase().includes('pin')) {
            console.log(`PIN-related button ${index}:`, {
                text: button.textContent,
                id: button.id,
                class: button.className,
                element: button
            });
            
            button.addEventListener('click', function(e) {
                console.log(`PIN button ${index} clicked:`, {
                    button: this,
                    event: e
                });
            });
        }
    });
}, 1000);

console.log('🎯 Debug setup complete. Now try entering your PIN and watch the console!');
